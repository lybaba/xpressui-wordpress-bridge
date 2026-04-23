import TFormConfig from "./TFormConfig";
import { FormShareCodeRuntime, type TShareCodeTokenAccess } from "./form-persistence-share-code";
import {
  normalizeResumeSnapshot as normalizeRemoteResumeSnapshot,
  parseRemoteCreateResponse as parseRemoteCreateResponsePayload,
  parseRemoteInvalidateResponse as parseRemoteInvalidateResponsePayload,
  parseRemoteLookupResponse as parseRemoteLookupResponsePayload,
  parseRemoteShareCodeClaimResponse as parseRemoteShareCodeClaimResponsePayload,
  parseRemoteShareCodeCreateResponse as parseRemoteShareCodeCreateResponsePayload,
} from "./form-persistence-remote";
import { normalizeShareCodeKey as normalizeShareCodeStorageKey } from "./form-persistence-policy";
import { generateRuntimeId } from "./id";
import { getRemoteResumePolicy } from "./resume-contract";
import { getSerializableStorageValues } from "./form-storage";
import type {
  TFormStorageSnapshot,
  TRemoteResumeCreateRequest,
  TRemoteResumeCreateResponse,
  TRemoteResumeInvalidateResponse,
  TRemoteResumeLookupResponse,
  TRemoteResumeShareCodeClaimRequest,
  TRemoteResumeShareCodeClaimResponse,
  TRemoteResumeShareCodeCreateRequest,
  TRemoteResumeShareCodeCreateResponse,
  TResumeLookupResult,
  TResumeShareCodeClaimDetail,
  TResumeShareCodeClaimStatus,
  TResumeShareCodeInfo,
  TResumeShareCodeRestoreDetail,
  TResumeStatusSummary,
  TResumeTokenInfo,
} from "./form-persistence.types";

// ---------------------------------------------------------------------------
// Internal types
// ---------------------------------------------------------------------------

export type TResumeTokenState = {
  version: 1;
  savedAt: number;
  issuedAt?: number;
  expiresAt?: number;
  snapshot: TFormStorageSnapshot;
  resumeEndpoint?: string;
  remote?: boolean;
  signature?: string;
  signatureVersion?: string;
};

// ---------------------------------------------------------------------------
// Options
// ---------------------------------------------------------------------------

export type TFormResumeRuntimeOptions = {
  getFormConfig(): TFormConfig | null;
  getValues(): Record<string, any>;
  emitEvent(eventName: string, detail: Record<string, any>): boolean;
  getStorageSnapshot(): TFormStorageSnapshot;
  applyResumeSnapshot(snapshot: TFormStorageSnapshot): Record<string, any>;
  createEventDetail(
    values: Record<string, any>,
    result?: any,
    response?: Response,
    error?: unknown,
  ): Record<string, any>;
};

// ---------------------------------------------------------------------------
// FormResumeRuntime
// ---------------------------------------------------------------------------

export class FormResumeRuntime {
  options: TFormResumeRuntimeOptions;
  resumeTokenMemory: Map<string, TResumeTokenState>;
  shareCode: FormShareCodeRuntime;

  constructor(options: TFormResumeRuntimeOptions) {
    this.options = options;
    this.resumeTokenMemory = new Map();
    const tokenAccess: TShareCodeTokenAccess = {
      getResumeEndpoint: () => this.getResumeEndpoint(),
      getShareCodeEndpoint: () => this.getShareCodeEndpoint(),
      getResumeTokenExpiresAt: (savedAt) => this.getResumeTokenExpiresAt(savedAt),
      persistResumeTokenState: (token, state) => this.persistResumeTokenState(token, state),
      applyResumeTokenSignature: (token, state) => this.applyResumeTokenSignature(token, state),
      isResumeTokenSignatureValid: (token, state) => this.isResumeTokenSignatureValid(token, state),
      emitResumeTokenInvalidSignature: (token, state) => this.emitResumeTokenInvalidSignature(token, state),
      listResumeTokens: () => this.listResumeTokens(),
    };
    this.shareCode = new FormShareCodeRuntime(options, tokenAccess);
  }

  // -------------------------------------------------------------------------
  // Local storage access
  // -------------------------------------------------------------------------

  getLocalStorage(): Storage | null {
    if (typeof window === "undefined") {
      return null;
    }

    let storage: Storage | Record<string, any> | undefined;
    try {
      storage = window.localStorage as Storage | Record<string, any> | undefined;
    } catch {
      return null;
    }
    if (
      !storage ||
      typeof (storage as Storage).getItem !== "function" ||
      typeof (storage as Storage).setItem !== "function" ||
      typeof (storage as Storage).removeItem !== "function"
    ) {
      return null;
    }

    return storage as Storage;
  }

  // -------------------------------------------------------------------------
  // Resume config getters
  // -------------------------------------------------------------------------

  getResumeEndpoint(): string | undefined {
    return this.options.getFormConfig()?.storage?.resumeEndpoint;
  }

  getShareCodeEndpoint(): string | undefined {
    return (
      this.options.getFormConfig()?.storage?.shareCodeEndpoint || this.getResumeEndpoint()
    );
  }

  getResumeStorageKey(token: string): string | null {
    const formName = this.options.getFormConfig()?.name;
    if (!formName) {
      return null;
    }

    return `xpressui:resume:${formName}:${token}`;
  }

  getResumeStoragePrefix(): string | null {
    const formName = this.options.getFormConfig()?.name;
    if (!formName) {
      return null;
    }

    return `xpressui:resume:${formName}:`;
  }

  buildResumeEndpointUrl(token: string): string | null {
    const resumeEndpoint = this.getResumeEndpoint();
    if (!resumeEndpoint) {
      return null;
    }

    return `${resumeEndpoint}${resumeEndpoint.includes("?") ? "&" : "?"}token=${encodeURIComponent(token)}`;
  }

  getResumeTokenTtlMs(): number | null {
    const retentionDays = this.options.getFormConfig()?.storage?.resumeTokenTtlDays;
    return typeof retentionDays === "number" && retentionDays > 0
      ? retentionDays * 24 * 60 * 60 * 1000
      : null;
  }

  getResumeTokenExpiresAt(savedAt: number): number | undefined {
    const ttlMs = this.getResumeTokenTtlMs();
    if (!ttlMs) {
      return undefined;
    }
    return savedAt + ttlMs;
  }

  isResumeTokenExpired(savedAt?: number): boolean {
    const ttlMs = this.getResumeTokenTtlMs();
    if (!ttlMs || typeof savedAt !== "number") {
      return false;
    }

    return Date.now() - savedAt > ttlMs;
  }

  getResumeTokenSignatureVersion(): string | undefined {
    const version = this.options.getFormConfig()?.storage?.resumeTokenSignatureVersion;
    return typeof version === "string" && version ? version : undefined;
  }

  // -------------------------------------------------------------------------
  // Resume snapshot helpers
  // -------------------------------------------------------------------------

  buildResumeSnapshot(): TFormStorageSnapshot {
    const currentValues = this.options.getValues();
    const storageSnapshot = this.options.getStorageSnapshot();

    return storageSnapshot
      ? {
          ...storageSnapshot,
          draft:
            storageSnapshot.draft ||
            (Object.keys(currentValues).length
              ? getSerializableStorageValues(currentValues)
              : null),
        }
      : {
          draft: Object.keys(currentValues).length
            ? getSerializableStorageValues(currentValues)
            : null,
          queue: [],
          deadLetter: [],
        };
  }

  getResumeCreatePayload(snapshot: TFormStorageSnapshot): Record<string, any> {
    return {
      operation: "create",
      formName: this.options.getFormConfig()?.name,
      snapshot,
      signatureVersion: this.getResumeTokenSignatureVersion(),
    } satisfies TRemoteResumeCreateRequest;
  }

  // -------------------------------------------------------------------------
  // Signature helpers
  // -------------------------------------------------------------------------

  buildResumeTokenSignaturePayload(token: string, state: TResumeTokenState): Record<string, any> {
    return {
      token,
      formName: this.options.getFormConfig()?.name,
      savedAt: state.savedAt,
      issuedAt: state.issuedAt ?? state.savedAt,
      expiresAt: state.expiresAt,
      snapshot: state.snapshot,
      resumeEndpoint: state.resumeEndpoint,
      remote: state.remote,
      signature: state.signature,
      signatureVersion: state.signatureVersion ?? this.getResumeTokenSignatureVersion(),
    };
  }

  applyResumeTokenSignature(token: string, state: TResumeTokenState): TResumeTokenState {
    const signer = this.options.getFormConfig()?.storage?.signResumeToken;
    const signatureVersion = this.getResumeTokenSignatureVersion();

    if (!signer) {
      return {
        ...state,
        ...(signatureVersion ? { signatureVersion } : {}),
      };
    }

    try {
      const signed = signer(this.buildResumeTokenSignaturePayload(token, state) as any);
      if (typeof signed === "string" && signed) {
        return {
          ...state,
          signature: signed,
          ...(signatureVersion ? { signatureVersion } : {}),
        };
      }
    } catch {
      return state;
    }

    return {
      ...state,
      ...(signatureVersion ? { signatureVersion } : {}),
    };
  }

  isResumeTokenSignatureValid(token: string, state: TResumeTokenState): boolean {
    const verifier = this.options.getFormConfig()?.storage?.verifyResumeToken;
    if (!verifier) {
      return true;
    }

    if (!state.signature) {
      return false;
    }

    try {
      return Boolean(verifier(this.buildResumeTokenSignaturePayload(token, state) as any));
    } catch {
      return false;
    }
  }

  emitResumeTokenInvalidSignature(token: string, state: {
    savedAt?: number;
    resumeEndpoint?: string;
    signatureVersion?: string;
  }): void {
    this.options.emitEvent(
      "xpressui:resume-token-invalid-signature",
      this.options.createEventDetail({}, {
        token,
        savedAt: state.savedAt,
        resumeEndpoint: state.resumeEndpoint,
        signatureVersion: state.signatureVersion,
      }),
    );
  }

  // -------------------------------------------------------------------------
  // Resume token persistence
  // -------------------------------------------------------------------------

  persistResumeTokenState(token: string, state: TResumeTokenState): boolean {
    this.resumeTokenMemory.set(token, state);
    const storage = this.getLocalStorage();
    if (!storage) {
      return false;
    }

    const key = this.getResumeStorageKey(token);
    if (!key) {
      return false;
    }

    try {
      storage.setItem(key, JSON.stringify(state));
      return true;
    } catch {
      return false;
    }
  }

  parseResumeToken(token: string, raw: string | null): (TResumeTokenInfo & {
    snapshot: TFormStorageSnapshot | null;
  }) | null {
    try {
      const parsed = raw
        ? JSON.parse(raw) as Partial<TResumeTokenState>
        : this.resumeTokenMemory.get(token);
      if (!parsed) {
        return null;
      }
      const snapshot =
        parsed?.snapshot && typeof parsed.snapshot === "object"
          ? parsed.snapshot
          : null;
      const savedAt = typeof parsed?.savedAt === "number" ? parsed.savedAt : 0;
      const issuedAt = typeof parsed?.issuedAt === "number" ? parsed.issuedAt : savedAt;
      const expiresAt =
        typeof parsed?.expiresAt === "number"
          ? parsed.expiresAt
          : this.getResumeTokenExpiresAt(savedAt);
      const expired = this.isResumeTokenExpired(savedAt);
      const signatureVersion =
        typeof parsed?.signatureVersion === "string" && parsed.signatureVersion
          ? parsed.signatureVersion
          : this.getResumeTokenSignatureVersion();
      const signatureValid = this.isResumeTokenSignatureValid(token, {
        version: 1,
        savedAt,
        issuedAt,
        expiresAt,
        snapshot: snapshot || { draft: null, queue: [], deadLetter: [] },
        resumeEndpoint:
          typeof parsed?.resumeEndpoint === "string"
            ? parsed.resumeEndpoint
            : this.getResumeEndpoint(),
        remote: Boolean(parsed?.remote),
        signature: typeof parsed?.signature === "string" ? parsed.signature : undefined,
        signatureVersion,
      });

      return {
        token,
        savedAt,
        issuedAt,
        expiresAt,
        expired,
        resumeEndpoint:
          typeof parsed?.resumeEndpoint === "string"
            ? parsed.resumeEndpoint
            : this.getResumeEndpoint(),
        remote: Boolean(parsed?.remote),
        signatureVersion,
        signatureValid,
        snapshot,
      };
    } catch {
      return null;
    }
  }

  // -------------------------------------------------------------------------
  // Remote response parsers
  // -------------------------------------------------------------------------

  normalizeResumeSnapshot(input: unknown): TFormStorageSnapshot | null {
    return normalizeRemoteResumeSnapshot(input);
  }

  parseRemoteCreateResponse(result: unknown): TRemoteResumeCreateResponse | null {
    return parseRemoteCreateResponsePayload(result);
  }

  parseRemoteLookupResponse(
    fallbackToken: string,
    result: unknown,
  ): TRemoteResumeLookupResponse | null {
    return parseRemoteLookupResponsePayload(fallbackToken, result);
  }

  parseRemoteInvalidateResponse(
    token: string,
    response: Response,
    result: unknown,
  ): TRemoteResumeInvalidateResponse | null {
    return parseRemoteInvalidateResponsePayload(token, response, result);
  }

  parseRemoteShareCodeCreateResponse(result: unknown): TRemoteResumeShareCodeCreateResponse | null {
    return parseRemoteShareCodeCreateResponsePayload(result);
  }

  parseRemoteShareCodeClaimResponse(result: unknown): TRemoteResumeShareCodeClaimResponse | null {
    return parseRemoteShareCodeClaimResponsePayload(result);
  }

  // -------------------------------------------------------------------------
  // Resume token CRUD
  // -------------------------------------------------------------------------

  createResumeToken(): string | null {
    if (typeof window === "undefined") {
      return null;
    }

    const token = generateRuntimeId();
    const key = this.getResumeStorageKey(token);
    if (!key) {
      return null;
    }

    const currentValues = this.options.getValues();
    const snapshot = this.buildResumeSnapshot();
    const savedAt = Date.now();

    const baseState: TResumeTokenState = {
      version: 1,
      savedAt,
      issuedAt: savedAt,
      expiresAt: this.getResumeTokenExpiresAt(savedAt),
      snapshot,
      resumeEndpoint: this.getResumeEndpoint(),
    };
    const state = this.applyResumeTokenSignature(token, baseState);

    try {
      if (!this.persistResumeTokenState(token, state)) {
        return null;
      }
      this.options.emitEvent(
        "xpressui:resume-token-created",
        this.options.createEventDetail(currentValues, {
          token,
          savedAt: state.savedAt,
          issuedAt: state.issuedAt,
          expiresAt: state.expiresAt,
          resumeEndpoint: state.resumeEndpoint,
          signatureVersion: state.signatureVersion,
        }),
      );
      return token;
    } catch {
      return null;
    }
  }

  async createResumeTokenAsync(): Promise<string | null> {
    const resumeEndpoint = this.getResumeEndpoint();
    if (!resumeEndpoint) {
      return this.createResumeToken();
    }

    const currentValues = this.options.getValues();
    const snapshot = this.buildResumeSnapshot();

    try {
      const response = await fetch(resumeEndpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(this.getResumeCreatePayload(snapshot)),
      });
      const contentType = response.headers.get("content-type") || "";
      const result = contentType.includes("application/json")
        ? await response.json()
        : await response.text();

      if (!response.ok) {
        throw new Error("Invalid remote resume token response");
      }

      const parsed = this.parseRemoteCreateResponse(result);
      if (!parsed) {
        throw new Error("Invalid remote resume token response");
      }

      const baseState: TResumeTokenState = {
        version: 1,
        savedAt: parsed.savedAt,
        issuedAt: parsed.issuedAt ?? parsed.savedAt,
        expiresAt:
          typeof parsed.expiresAt === "number"
            ? parsed.expiresAt
            : this.getResumeTokenExpiresAt(parsed.savedAt),
        snapshot,
        resumeEndpoint,
        remote: true,
        ...(parsed.signature ? { signature: parsed.signature } : {}),
        ...(parsed.signatureVersion ? { signatureVersion: parsed.signatureVersion } : {}),
      };
      const state = baseState.signature
        ? baseState
        : this.applyResumeTokenSignature(parsed.token, baseState);
      if (!this.isResumeTokenSignatureValid(parsed.token, state)) {
        this.emitResumeTokenInvalidSignature(parsed.token, {
          savedAt: parsed.savedAt,
          resumeEndpoint,
          signatureVersion: state.signatureVersion,
        });
        return null;
      }
      this.persistResumeTokenState(parsed.token, state);

      try {
        this.options.emitEvent(
          "xpressui:resume-token-created",
          this.options.createEventDetail(currentValues, {
            operation: parsed.operation,
            token: parsed.token,
            savedAt: parsed.savedAt,
            issuedAt: state.issuedAt,
            expiresAt: state.expiresAt,
            resumeEndpoint,
            remote: true,
            signatureVersion: state.signatureVersion,
          }, response),
        );
      } catch {
        // Ignore event emission failures; token creation already succeeded.
      }
      return parsed.token;
    } catch {
      return null;
    }
  }

  async lookupResumeToken(token: string): Promise<TResumeLookupResult | null> {
    const resumeEndpoint = this.getResumeEndpoint();
    if (!resumeEndpoint) {
      const key = this.getResumeStorageKey(token);
      const storage = this.getLocalStorage();
      const raw = key && storage ? storage.getItem(key) : null;
      const parsed = this.parseResumeToken(token, raw);
      if (!parsed) {
        return null;
      }
      if (parsed.signatureValid === false) {
        this.deleteResumeToken(token);
        this.emitResumeTokenInvalidSignature(token, {
          savedAt: parsed.savedAt,
          resumeEndpoint: parsed.resumeEndpoint,
          signatureVersion: parsed.signatureVersion,
        });
        return null;
      }
      if (parsed.expired) {
        this.deleteResumeToken(token);
        return null;
      }

      return {
        token: parsed.token,
        savedAt: parsed.savedAt,
        issuedAt: parsed.issuedAt,
        expiresAt: parsed.expiresAt,
        expired: false,
        resumeEndpoint: parsed.resumeEndpoint,
        remote: parsed.remote,
        signatureVersion: parsed.signatureVersion,
        signatureValid: parsed.signatureValid,
        snapshot: parsed.snapshot,
      };
    }

    try {
      const endpointUrl = this.buildResumeEndpointUrl(token);
      if (!endpointUrl) {
        return null;
      }
      const response = await fetch(endpointUrl, { method: "GET" });
      const contentType = response.headers.get("content-type") || "";
      const result = contentType.includes("application/json")
        ? await response.json()
        : null;
      if (!response.ok) {
        return null;
      }

      const parsed = this.parseRemoteLookupResponse(token, result);
      if (!parsed) {
        return null;
      }

      if (parsed.snapshot) {
        const baseState: TResumeTokenState = {
          version: 1,
          savedAt: parsed.savedAt,
          issuedAt: parsed.issuedAt ?? parsed.savedAt,
          expiresAt:
            typeof parsed.expiresAt === "number"
              ? parsed.expiresAt
              : this.getResumeTokenExpiresAt(parsed.savedAt),
          snapshot: parsed.snapshot,
          resumeEndpoint,
          remote: true,
          ...(parsed.signature ? { signature: parsed.signature } : {}),
          ...(parsed.signatureVersion ? { signatureVersion: parsed.signatureVersion } : {}),
        };
        const state = baseState.signature
          ? baseState
          : this.applyResumeTokenSignature(parsed.token, baseState);
        this.persistResumeTokenState(parsed.token, state);
        if (!this.isResumeTokenSignatureValid(parsed.token, state)) {
          this.deleteResumeToken(parsed.token);
          this.emitResumeTokenInvalidSignature(parsed.token, {
            savedAt: parsed.savedAt,
            resumeEndpoint,
            signatureVersion: state.signatureVersion,
          });
          return null;
        }
      }

      return {
        token: parsed.token,
        savedAt: parsed.savedAt,
        issuedAt: parsed.issuedAt ?? parsed.savedAt,
        expiresAt:
          typeof parsed.expiresAt === "number"
            ? parsed.expiresAt
            : this.getResumeTokenExpiresAt(parsed.savedAt),
        expired: false,
        resumeEndpoint,
        remote: true,
        signatureVersion: parsed.signatureVersion || this.getResumeTokenSignatureVersion(),
        signatureValid: true,
        snapshot: parsed.snapshot,
      };
    } catch {
      return null;
    }
  }

  restoreFromResumeToken(token: string): Record<string, any> | null {
    try {
      const key = this.getResumeStorageKey(token);
      const storage = this.getLocalStorage();
      const raw = key && storage ? storage.getItem(key) : null;

      const parsed = this.parseResumeToken(token, raw);
      if (!parsed?.snapshot) {
        return null;
      }
      if (parsed.signatureValid === false) {
        this.deleteResumeToken(token);
        this.emitResumeTokenInvalidSignature(token, {
          savedAt: parsed.savedAt,
          resumeEndpoint: parsed.resumeEndpoint,
          signatureVersion: parsed.signatureVersion,
        });
        return null;
      }
      if (parsed.expired) {
        this.deleteResumeToken(token);
        this.options.emitEvent(
          "xpressui:resume-token-expired",
          this.options.createEventDetail({}, {
            token,
            savedAt: parsed.savedAt,
            resumeEndpoint: parsed.resumeEndpoint,
          }),
        );
        return null;
      }
      const snapshot = parsed.snapshot;

      const restoredDraft = this.options.applyResumeSnapshot(snapshot);
      this.options.emitEvent(
        "xpressui:resume-token-restored",
        this.options.createEventDetail(restoredDraft, {
          token,
          snapshot,
          savedAt: parsed.savedAt,
          issuedAt: parsed.issuedAt,
          expiresAt: parsed.expiresAt,
          resumeEndpoint: parsed.resumeEndpoint,
          signatureVersion: parsed.signatureVersion,
        }),
      );
      return restoredDraft;
    } catch {
      return null;
    }
  }

  async restoreFromResumeTokenAsync(token: string): Promise<Record<string, any> | null> {
    const lookup = await this.lookupResumeToken(token);
    if (!lookup) {
      return null;
    }

    if (lookup.resumeEndpoint && lookup.snapshot) {
      const restoredDraft = this.options.applyResumeSnapshot(lookup.snapshot);
      this.options.emitEvent(
        "xpressui:resume-token-restored",
        this.options.createEventDetail(restoredDraft, {
          token: lookup.token,
          snapshot: lookup.snapshot,
          savedAt: lookup.savedAt,
          issuedAt: lookup.issuedAt,
          expiresAt: lookup.expiresAt,
          resumeEndpoint: lookup.resumeEndpoint,
          remote: true,
          signatureVersion: lookup.signatureVersion,
        }),
      );
      return restoredDraft;
    }

    return this.restoreFromResumeToken(token);
  }

  listResumeTokens(): TResumeTokenInfo[] {
    const prefix = this.getResumeStoragePrefix();
    if (!prefix) {
      return [];
    }

    const tokens: TResumeTokenInfo[] = [];
    const seenTokens = new Set<string>();
    const storage = this.getLocalStorage();

    if (storage) {
      for (let index = storage.length - 1; index >= 0; index -= 1) {
        const key = storage.key(index);
        if (!key || !key.startsWith(prefix)) {
          continue;
        }

        const token = key.slice(prefix.length);
        seenTokens.add(token);
        const parsed = this.parseResumeToken(token, storage.getItem(key));
        if (!parsed) {
          storage.removeItem(key);
          continue;
        }
        if (parsed.signatureValid === false) {
          storage.removeItem(key);
          this.resumeTokenMemory.delete(token);
          this.emitResumeTokenInvalidSignature(token, {
            savedAt: parsed.savedAt,
            resumeEndpoint: parsed.resumeEndpoint,
            signatureVersion: parsed.signatureVersion,
          });
          continue;
        }

        if (parsed.expired) {
          storage.removeItem(key);
          this.resumeTokenMemory.delete(token);
          continue;
        }

        tokens.push({
          token: parsed.token,
          savedAt: parsed.savedAt,
          issuedAt: parsed.issuedAt,
          expiresAt: parsed.expiresAt,
          expired: false,
          resumeEndpoint: parsed.resumeEndpoint,
          remote: parsed.remote,
          signatureVersion: parsed.signatureVersion,
          signatureValid: parsed.signatureValid,
        });
      }
    }

    for (const token of Array.from(this.resumeTokenMemory.keys())) {
      if (seenTokens.has(token)) {
        continue;
      }
      if (storage) {
        this.resumeTokenMemory.delete(token);
        continue;
      }
      const parsed = this.parseResumeToken(token, null);
      if (!parsed || parsed.expired || parsed.signatureValid === false) {
        if (parsed?.signatureValid === false) {
          this.emitResumeTokenInvalidSignature(token, {
            savedAt: parsed.savedAt,
            resumeEndpoint: parsed.resumeEndpoint,
            signatureVersion: parsed.signatureVersion,
          });
        }
        this.resumeTokenMemory.delete(token);
        continue;
      }
      tokens.push({
        token: parsed.token,
        savedAt: parsed.savedAt,
        issuedAt: parsed.issuedAt,
        expiresAt: parsed.expiresAt,
        expired: false,
        resumeEndpoint: parsed.resumeEndpoint,
        remote: parsed.remote,
        signatureVersion: parsed.signatureVersion,
        signatureValid: parsed.signatureValid,
      });
    }

    return tokens.sort((left, right) => right.savedAt - left.savedAt);
  }

  deleteResumeToken(token: string): boolean {
    const key = this.getResumeStorageKey(token);
    const storage = this.getLocalStorage();
    let deleted = false;
    if (key && storage && storage.getItem(key)) {
      storage.removeItem(key);
      deleted = true;
    }
    if (this.resumeTokenMemory.has(token)) {
      this.resumeTokenMemory.delete(token);
      deleted = true;
    }
    if (!deleted) {
      return false;
    }
    this.options.emitEvent(
      "xpressui:resume-token-deleted",
      this.options.createEventDetail(this.options.getValues(), {
        token,
      }),
    );
    return true;
  }

  async invalidateResumeToken(token: string): Promise<boolean> {
    const resumeEndpoint = this.getResumeEndpoint();
    if (!resumeEndpoint) {
      const deleted = this.deleteResumeToken(token);
      if (deleted) {
        this.options.emitEvent(
          "xpressui:resume-token-invalidated",
          this.options.createEventDetail(this.options.getValues(), {
            token,
            remote: false,
          }),
        );
      }
      return deleted;
    }

    try {
      const endpointUrl = this.buildResumeEndpointUrl(token);
      if (!endpointUrl) {
        return false;
      }
      const response = await fetch(endpointUrl, { method: "DELETE" });
      if (response.ok && response.status === 204) {
        const key = this.getResumeStorageKey(token);
        const storage = this.getLocalStorage();
        if (key && storage) {
          storage.removeItem(key);
        }
        this.resumeTokenMemory.delete(token);
        this.options.emitEvent(
          "xpressui:resume-token-invalidated",
          this.options.createEventDetail(this.options.getValues(), {
            operation: "invalidate",
            token,
            resumeEndpoint,
            remote: true,
          }, response),
        );
        return true;
      }

      let result: unknown = null;
      const contentType = response.headers.get("content-type") || "";
      if (contentType.includes("application/json")) {
        try {
          result = await response.json();
        } catch {
          result = null;
        }
      }
      const parsed = this.parseRemoteInvalidateResponse(token, response, result);
      if (!parsed) {
        return false;
      }

      const key = this.getResumeStorageKey(token);
      const storage = this.getLocalStorage();
      if (key && storage) {
        storage.removeItem(key);
      }
      this.resumeTokenMemory.delete(token);
      this.options.emitEvent(
        "xpressui:resume-token-invalidated",
        this.options.createEventDetail(this.options.getValues(), {
          operation: parsed.operation,
          token: parsed.token,
          resumeEndpoint,
          remote: true,
        }, response),
      );
      return true;
    } catch {
      return false;
    }
  }


  // -------------------------------------------------------------------------
  // Share code delegation
  // -------------------------------------------------------------------------

  get shareCodeClaimPolicyState() { return this.shareCode.shareCodeClaimPolicyState; }
  get lastShareCodeClaimDetail() { return this.shareCode.lastShareCodeClaimDetail; }
  get lastShareCodeRestoreDetail() { return this.shareCode.lastShareCodeRestoreDetail; }

  getShareCodeClaimThrottleMs() { return this.shareCode.getShareCodeClaimThrottleMs(); }
  getShareCodeClaimMaxAttempts() { return this.shareCode.getShareCodeClaimMaxAttempts(); }
  getShareCodeClaimWindowMs() { return this.shareCode.getShareCodeClaimWindowMs(); }
  getShareCodeClaimBlockMs() { return this.shareCode.getShareCodeClaimBlockMs(); }
  normalizeShareCodeKey(code: string) { return this.shareCode.normalizeShareCodeKey(code); }
  normalizeRemotePolicyClaimStatus(code: Parameters<FormShareCodeRuntime["normalizeRemotePolicyClaimStatus"]>[0]) { return this.shareCode.normalizeRemotePolicyClaimStatus(code); }
  getShareCodeClaimState(code: string) { return this.shareCode.getShareCodeClaimState(code); }
  emitShareCodeClaimBlocked(code: string, result: Parameters<FormShareCodeRuntime["emitShareCodeClaimBlocked"]>[1]) { return this.shareCode.emitShareCodeClaimBlocked(code, result); }
  emitShareCodeClaimState(result: Parameters<FormShareCodeRuntime["emitShareCodeClaimState"]>[0], response?: Response) { return this.shareCode.emitShareCodeClaimState(result, response); }
  evaluateShareCodeClaimPermission(code: string) { return this.shareCode.evaluateShareCodeClaimPermission(code); }
  markShareCodeClaimFailure(code: string) { return this.shareCode.markShareCodeClaimFailure(code); }
  markShareCodeClaimSuccess(code: string) { return this.shareCode.markShareCodeClaimSuccess(code); }
  getResumeStatusSummary() { return this.shareCode.getResumeStatusSummary(); }
  createResumeShareCode(token: string) { return this.shareCode.createResumeShareCode(token); }
  createResumeShareCodeDetail(token: string) { return this.shareCode.createResumeShareCodeDetail(token); }
  claimResumeShareCode(code: string) { return this.shareCode.claimResumeShareCode(code); }
  claimResumeShareCodeDetail(code: string) { return this.shareCode.claimResumeShareCodeDetail(code); }
  restoreFromShareCodeAsync(code: string) { return this.shareCode.restoreFromShareCodeAsync(code); }
  restoreFromShareCodeDetailAsync(code: string) { return this.shareCode.restoreFromShareCodeDetailAsync(code); }
}
