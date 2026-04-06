import TFormConfig, { TFormSubmitRequest } from "./TFormConfig";
import { isFileFieldType } from "./field";
import { generateRuntimeId } from "./id";
import {
  getRemoteResumePolicy,
  TRemoteResumeContractVersion,
  TRemoteResumePolicy,
} from "./resume-contract";
import {
  createStorageAdapter,
  getSerializableStorageValues,
  getRestorableStorageValues,
  TStorageHealth,
  TStorageHydrationResult,
  TFormStorageAdapter,
  TQueuedSubmission,
} from "./form-storage";

export type TFormQueueState = {
  queueLength: number;
  deadLetterLength: number;
  nextAttemptAt?: number;
  attempts?: number;
  disabledReason?: string;
};

export type TFormStorageSnapshot = {
  draft: Record<string, any> | null;
  queue: TQueuedSubmission[];
  deadLetter: TQueuedSubmission[];
  currentStepIndex?: number;
};

export type TFormStorageHealth = TStorageHealth & {
  queueDisabledReason?: string;
  queueEnabled: boolean;
};

export type TResumeTokenInfo = {
  token: string;
  savedAt: number;
  issuedAt?: number;
  expiresAt?: number;
  expired: boolean;
  resumeEndpoint?: string;
  remote?: boolean;
  signatureVersion?: string;
  signatureValid?: boolean;
};

export type TResumeLookupResult = TResumeTokenInfo & {
  snapshot: TFormStorageSnapshot | null;
};

export type TRemoteResumeOperation = "create" | "lookup" | "invalidate";
export type TRemoteShareCodeOperation = "create-share-code" | "claim-share-code";

export type TRemoteResumeCreateRequest = {
  operation: "create";
  formName?: string;
  snapshot: TFormStorageSnapshot;
  signatureVersion?: string;
};

export type TRemoteResumeCreateResponse = {
  operation: "create";
  contractVersion?: TRemoteResumeContractVersion;
  token: string;
  savedAt: number;
  issuedAt?: number;
  expiresAt?: number;
  signature?: string;
  signatureVersion?: string;
  policy?: TRemoteResumePolicy;
};

export type TRemoteResumeLookupResponse = {
  operation: "lookup";
  contractVersion?: TRemoteResumeContractVersion;
  token: string;
  savedAt: number;
  issuedAt?: number;
  expiresAt?: number;
  signature?: string;
  signatureVersion?: string;
  snapshot: TFormStorageSnapshot | null;
  policy?: TRemoteResumePolicy;
};

export type TRemoteResumeInvalidateResponse = {
  operation: "invalidate";
  contractVersion?: TRemoteResumeContractVersion;
  token: string;
  invalidated: boolean;
  policy?: TRemoteResumePolicy;
};

export type TRemoteResumeShareCodeCreateRequest = {
  operation: "create-share-code";
  token: string;
};

export type TRemoteResumeShareCodeCreateResponse = {
  operation: "create-share-code";
  contractVersion?: TRemoteResumeContractVersion;
  code: string;
  token?: string;
  expiresAt?: number;
  policy?: TRemoteResumePolicy;
};

export type TResumeShareCodeInfo = {
  code: string;
  token?: string;
  expiresAt?: number;
  endpoint?: string;
};

export type TResumeShareCodeClaimStatus =
  | "claimed"
  | "throttled"
  | "blocked"
  | "expired"
  | "invalid_signature"
  | "not_found"
  | "invalid_response"
  | "network_error";

export type TResumeShareCodeClaimDetail = {
  code: string;
  status: TResumeShareCodeClaimStatus;
  endpoint?: string;
  token?: string;
  savedAt?: number;
  issuedAt?: number;
  expiresAt?: number;
  signatureVersion?: string;
  signatureValid?: boolean;
  snapshot?: TFormStorageSnapshot | null;
  remote?: boolean;
  backend?: boolean;
  message?: string;
  retryAfterSeconds?: number;
  blockedUntil?: number;
  lookup?: TResumeLookupResult | null;
};

export type TResumeShareCodeRestoreDetail = {
  code: string;
  status: "restored" | "claim_failed";
  claim: TResumeShareCodeClaimDetail | null;
  restoredValues?: Record<string, any> | null;
  token?: string;
  message?: string;
};

export type TResumeStatusSummary = {
  configured: boolean;
  resumeEndpoint?: string;
  shareCodeEndpoint?: string;
  tokens: {
    total: number;
    remote: number;
    signed: number;
    invalidSignature: number;
    latestSavedAt?: number;
  };
  lastClaim: TResumeShareCodeClaimDetail | null;
  lastRestore: TResumeShareCodeRestoreDetail | null;
};

export type TRemoteResumeShareCodeClaimRequest = {
  operation: "claim-share-code";
  code: string;
};

export type TRemoteResumeShareCodeClaimResponse = {
  operation: "claim-share-code";
  contractVersion?: TRemoteResumeContractVersion;
  code: string;
  token: string;
  savedAt: number;
  issuedAt?: number;
  expiresAt?: number;
  signature?: string;
  signatureVersion?: string;
  snapshot: TFormStorageSnapshot | null;
  policy?: TRemoteResumePolicy;
};

type TResumeTokenState = {
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

type TShareCodeClaimPolicyState = {
  attempts: number;
  windowStartedAt: number;
  lastAttemptAt: number;
  blockedUntil: number;
};

type TFormPersistenceRuntimeOptions = {
  getFormConfig(): TFormConfig | null;
  getValues(): Record<string, any>;
  getCurrentStepIndex?(): number | null;
  setCurrentStepIndex?(index: number): void;
  emitEvent(eventName: string, detail: Record<string, any>): boolean;
  submitValues(
    values: Record<string, any>,
    submitConfig: TFormSubmitRequest,
  ): Promise<{ response: Response; result: any }>;
};

export class FormPersistenceRuntime {
  options: TFormPersistenceRuntimeOptions;
  storageAdapter: TFormStorageAdapter | null;
  draftSaveTimer: number | null;
  syncInFlight: boolean;
  onlineHandler: (() => void) | null;
  resumeTokenMemory: Map<string, TResumeTokenState>;
  shareCodeClaimPolicyState: Map<string, TShareCodeClaimPolicyState>;
  lastShareCodeClaimDetail: TResumeShareCodeClaimDetail | null;
  lastShareCodeRestoreDetail: TResumeShareCodeRestoreDetail | null;

  constructor(options: TFormPersistenceRuntimeOptions) {
    this.options = options;
    this.storageAdapter = null;
    this.draftSaveTimer = null;
    this.syncInFlight = false;
    this.onlineHandler = null;
    this.resumeTokenMemory = new Map();
    this.shareCodeClaimPolicyState = new Map();
    this.lastShareCodeClaimDetail = null;
    this.lastShareCodeRestoreDetail = null;
  }

  setFormConfig(formConfig: TFormConfig | null): void {
    this.storageAdapter = createStorageAdapter(formConfig);
  }

  disconnect(): void {
    if (this.draftSaveTimer !== null) {
      window.clearTimeout(this.draftSaveTimer);
      this.draftSaveTimer = null;
    }

    if (this.onlineHandler) {
      window.removeEventListener("online", this.onlineHandler);
      this.onlineHandler = null;
    }
  }

  connect(): void {
    if (!this.onlineHandler) {
      this.onlineHandler = () => {
        void this.flushSubmissionQueue();
      };
      window.addEventListener("online", this.onlineHandler);
    }

    void this.flushSubmissionQueue();
  }

  async hydrateStorage(): Promise<TStorageHydrationResult | null> {
    if (!this.storageAdapter?.hydrate) {
      return null;
    }

    const hydrationResult = await this.storageAdapter.hydrate();

    if (hydrationResult.migratedFromLocalStorage) {
      this.options.emitEvent(
        "xpressui:storage-migrated",
        this.createEventDetail(this.options.getValues(), {
          source: hydrationResult.source,
          migratedFromLocalStorage: true,
        }),
      );
    }

    return hydrationResult;
  }

  loadDraftValues(): Record<string, any> {
    return getRestorableStorageValues(this.storageAdapter?.loadDraft() || null);
  }

  getDraftAutoSaveMs(): number {
    return this.options.getFormConfig()?.storage?.autoSaveMs ?? 300;
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

  getResumeCreatePayload(snapshot: TFormStorageSnapshot): Record<string, any> {
    return {
      operation: "create",
      formName: this.options.getFormConfig()?.name,
      snapshot,
      signatureVersion: this.getResumeTokenSignatureVersion(),
    } satisfies TRemoteResumeCreateRequest;
  }

  buildResumeEndpointUrl(token: string): string | null {
    const resumeEndpoint = this.getResumeEndpoint();
    if (!resumeEndpoint) {
      return null;
    }

    return `${resumeEndpoint}${resumeEndpoint.includes("?") ? "&" : "?"}token=${encodeURIComponent(token)}`;
  }

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

  normalizeResumeSnapshot(input: unknown): TFormStorageSnapshot | null {
    if (!input || typeof input !== "object") {
      return null;
    }

    const snapshot = input as Record<string, any>;
    return {
      draft:
        snapshot.draft && typeof snapshot.draft === "object"
          ? snapshot.draft as Record<string, any>
          : null,
      queue: Array.isArray(snapshot.queue) ? snapshot.queue : [],
      deadLetter: Array.isArray(snapshot.deadLetter) ? snapshot.deadLetter : [],
      ...(typeof snapshot.currentStepIndex === "number" && Number.isInteger(snapshot.currentStepIndex)
        ? { currentStepIndex: snapshot.currentStepIndex }
        : {}),
    };
  }

  parseRemoteCreateResponse(result: unknown): TRemoteResumeCreateResponse | null {
    const normalizedResult =
      typeof result === "string"
        ? (() => {
            try {
              return JSON.parse(result) as unknown;
            } catch {
              return null;
            }
          })()
        : result;

    if (!normalizedResult || typeof normalizedResult !== "object") {
      return null;
    }

    const response = normalizedResult as Record<string, any>;
    if (typeof response.token !== "string" || !response.token) {
      return null;
    }

    return {
      operation: "create",
      token: response.token,
      savedAt: typeof response.savedAt === "number" ? response.savedAt : Date.now(),
      issuedAt: typeof response.issuedAt === "number" ? response.issuedAt : undefined,
      expiresAt: typeof response.expiresAt === "number" ? response.expiresAt : undefined,
      signature: typeof response.signature === "string" ? response.signature : undefined,
      signatureVersion:
        typeof response.signatureVersion === "string"
          ? response.signatureVersion
          : undefined,
    };
  }

  parseRemoteLookupResponse(
    fallbackToken: string,
    result: unknown,
  ): TRemoteResumeLookupResponse | null {
    const normalizedResult =
      typeof result === "string"
        ? (() => {
            try {
              return JSON.parse(result) as unknown;
            } catch {
              return null;
            }
          })()
        : result;

    if (!normalizedResult || typeof normalizedResult !== "object") {
      return null;
    }

    const response = normalizedResult as Record<string, any>;
    const normalizedSnapshot = this.normalizeResumeSnapshot(response.snapshot);
    const foundFlag = response.found;
    if (foundFlag === false) {
      return null;
    }

    return {
      operation: "lookup",
      token: typeof response.token === "string" && response.token ? response.token : fallbackToken,
      savedAt: typeof response.savedAt === "number" ? response.savedAt : Date.now(),
      issuedAt: typeof response.issuedAt === "number" ? response.issuedAt : undefined,
      expiresAt: typeof response.expiresAt === "number" ? response.expiresAt : undefined,
      signature: typeof response.signature === "string" ? response.signature : undefined,
      signatureVersion:
        typeof response.signatureVersion === "string"
          ? response.signatureVersion
          : undefined,
      snapshot: normalizedSnapshot,
    };
  }

  parseRemoteInvalidateResponse(
    token: string,
    response: Response,
    result: unknown,
  ): TRemoteResumeInvalidateResponse | null {
    if (!response.ok) {
      return null;
    }

    if (response.status === 204 || !result) {
      return {
        operation: "invalidate",
        token,
        invalidated: true,
      };
    }

    const normalizedResult =
      typeof result === "string"
        ? (() => {
            try {
              return JSON.parse(result) as unknown;
            } catch {
              return null;
            }
          })()
        : result;

    if (!normalizedResult || typeof normalizedResult !== "object") {
      return {
        operation: "invalidate",
        token,
        invalidated: true,
      };
    }

    const parsed = normalizedResult as Record<string, any>;
    const invalidated = parsed.invalidated;
    if (invalidated === false) {
      return null;
    }

    return {
      operation: "invalidate",
      token: typeof parsed.token === "string" && parsed.token ? parsed.token : token,
      invalidated: true,
    };
  }

  buildResumeSnapshot(): TFormStorageSnapshot {
    const currentValues = this.options.getValues();
    const storageSnapshot = this.storageAdapter ? this.getStorageSnapshot() : null;

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

  getCurrentStepStorageKey(): string | null {
    const formConfig = this.options.getFormConfig();
    if (!formConfig) {
      return null;
    }

    const baseKey = formConfig.storage?.key || `xpressui:draft:${formConfig.name}`;
    return `${baseKey}:step`;
  }

  loadCurrentStepIndex(): number | null {
    const key = this.getCurrentStepStorageKey();
    if (!key) {
      return null;
    }

    const storage = this.getLocalStorage();
    if (!storage) {
      return null;
    }

    try {
      const raw = storage.getItem(key);
      if (raw === null) {
        return null;
      }

      const parsed = Number(raw);
      return Number.isInteger(parsed) && parsed >= 0 ? parsed : null;
    } catch {
      return null;
    }
  }

  saveCurrentStepIndex(index?: number | null): void {
    const key = this.getCurrentStepStorageKey();
    if (!key) {
      return;
    }

    const storage = this.getLocalStorage();
    if (!storage) {
      return;
    }

    if (typeof index !== "number" || !Number.isInteger(index) || index < 0) {
      try {
        storage.removeItem(key);
      } catch {
        // Ignore storage write failures.
      }
      return;
    }

    try {
      storage.setItem(key, String(index));
    } catch {
      // Ignore storage write failures.
    }
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

  getResumeEndpoint(): string | undefined {
    return this.options.getFormConfig()?.storage?.resumeEndpoint;
  }

  getShareCodeEndpoint(): string | undefined {
    return this.options.getFormConfig()?.storage?.shareCodeEndpoint
      || this.getResumeEndpoint();
  }

  getShareCodeClaimThrottleMs(): number {
    return Math.max(0, Number(this.options.getFormConfig()?.storage?.shareCodeClaimThrottleMs ?? 0));
  }

  getShareCodeClaimMaxAttempts(): number {
    return Math.max(1, Number(this.options.getFormConfig()?.storage?.shareCodeClaimMaxAttempts ?? 5));
  }

  getShareCodeClaimWindowMs(): number {
    return Math.max(0, Number(this.options.getFormConfig()?.storage?.shareCodeClaimWindowMs ?? 5 * 60 * 1000));
  }

  getShareCodeClaimBlockMs(): number {
    return Math.max(0, Number(this.options.getFormConfig()?.storage?.shareCodeClaimBlockMs ?? 5 * 60 * 1000));
  }

  normalizeShareCodeKey(code: string): string {
    return String(code || "").trim().toUpperCase();
  }

  getShareCodeClaimState(code: string): TShareCodeClaimPolicyState {
    const now = Date.now();
    const key = this.normalizeShareCodeKey(code);
    const windowMs = this.getShareCodeClaimWindowMs();
    const state = this.shareCodeClaimPolicyState.get(key) || {
      attempts: 0,
      windowStartedAt: now,
      lastAttemptAt: 0,
      blockedUntil: 0,
    };

    if (windowMs > 0 && now - state.windowStartedAt > windowMs) {
      state.attempts = 0;
      state.windowStartedAt = now;
      state.blockedUntil = 0;
    }

    this.shareCodeClaimPolicyState.set(key, state);
    return state;
  }

  emitShareCodeClaimBlocked(
    code: string,
    result: {
      reason: "throttled" | "max-attempts";
      blockedUntil?: number;
      attempts?: number;
      maxAttempts?: number;
      throttleMs?: number;
    },
  ): void {
    this.options.emitEvent(
      "xpressui:resume-share-code-claim-blocked",
      this.createEventDetail(this.options.getValues(), {
        code,
        ...result,
      }),
    );
  }

  emitShareCodeClaimState(result: TResumeShareCodeClaimDetail, response?: Response): void {
    this.lastShareCodeClaimDetail = result;
    this.options.emitEvent(
      "xpressui:resume-share-code-claim-state",
      this.createEventDetail(this.options.getValues(), result, response),
    );
  }

  normalizeRemotePolicyClaimStatus(
    code: "rate_limited" | "blocked" | "expired" | "invalid_signature" | "not_found",
  ): TResumeShareCodeClaimStatus {
    return code === "rate_limited" ? "throttled" : code;
  }

  getResumeStatusSummary(): TResumeStatusSummary {
    const tokens = this.listResumeTokens();
    return {
      configured: Boolean(this.getResumeEndpoint() || this.getShareCodeEndpoint()),
      ...(this.getResumeEndpoint() ? { resumeEndpoint: this.getResumeEndpoint() } : {}),
      ...(this.getShareCodeEndpoint() ? { shareCodeEndpoint: this.getShareCodeEndpoint() } : {}),
      tokens: {
        total: tokens.length,
        remote: tokens.filter((entry) => entry.remote).length,
        signed: tokens.filter((entry) => Boolean(entry.signatureVersion)).length,
        invalidSignature: tokens.filter((entry) => entry.signatureValid === false).length,
        ...(tokens[0]?.savedAt ? { latestSavedAt: tokens[0].savedAt } : {}),
      },
      lastClaim: this.lastShareCodeClaimDetail ? { ...this.lastShareCodeClaimDetail } : null,
      lastRestore: this.lastShareCodeRestoreDetail ? { ...this.lastShareCodeRestoreDetail } : null,
    };
  }

  evaluateShareCodeClaimPermission(code: string): {
    allowed: boolean;
    detail?: TResumeShareCodeClaimDetail;
  } {
    const now = Date.now();
    const state = this.getShareCodeClaimState(code);
    const maxAttempts = this.getShareCodeClaimMaxAttempts();
    const throttleMs = this.getShareCodeClaimThrottleMs();
    if (throttleMs > 0 && state.lastAttemptAt > 0 && now - state.lastAttemptAt < throttleMs) {
      const detail: TResumeShareCodeClaimDetail = {
        code,
        status: "throttled",
        endpoint: this.getShareCodeEndpoint(),
        blockedUntil: state.lastAttemptAt + throttleMs,
        retryAfterSeconds: Math.max(0, Math.ceil((state.lastAttemptAt + throttleMs - now) / 1000)),
        message: "Share-code claim is temporarily throttled.",
      };
      this.emitShareCodeClaimBlocked(code, {
        reason: "throttled",
        blockedUntil: state.lastAttemptAt + throttleMs,
        attempts: state.attempts,
        maxAttempts: this.getShareCodeClaimMaxAttempts(),
        throttleMs,
      });
      this.emitShareCodeClaimState(detail);
      return { allowed: false, detail };
    }

    if (state.blockedUntil > now) {
      const detail: TResumeShareCodeClaimDetail = {
        code,
        status: "blocked",
        endpoint: this.getShareCodeEndpoint(),
        blockedUntil: state.blockedUntil,
        retryAfterSeconds: Math.max(0, Math.ceil((state.blockedUntil - now) / 1000)),
        message: "Share-code claim is temporarily blocked.",
      };
      this.emitShareCodeClaimBlocked(code, {
        reason: "max-attempts",
        blockedUntil: state.blockedUntil,
        attempts: state.attempts,
        maxAttempts,
      });
      this.emitShareCodeClaimState(detail);
      return { allowed: false, detail };
    }

    if (state.attempts >= maxAttempts) {
      const blockMs = this.getShareCodeClaimBlockMs();
      if (state.blockedUntil <= now && blockMs > 0) {
        state.blockedUntil = now + blockMs;
      }
      this.shareCodeClaimPolicyState.set(this.normalizeShareCodeKey(code), state);
      const detail: TResumeShareCodeClaimDetail = {
        code,
        status: "blocked",
        endpoint: this.getShareCodeEndpoint(),
        blockedUntil: state.blockedUntil || undefined,
        retryAfterSeconds:
          state.blockedUntil && state.blockedUntil > now
            ? Math.max(0, Math.ceil((state.blockedUntil - now) / 1000))
            : undefined,
        message: "Share-code claim is blocked after too many attempts.",
      };
      this.emitShareCodeClaimBlocked(code, {
        reason: "max-attempts",
        blockedUntil: state.blockedUntil || undefined,
        attempts: state.attempts,
        maxAttempts,
      });
      this.emitShareCodeClaimState(detail);
      return { allowed: false, detail };
    }

    state.lastAttemptAt = now;
    this.shareCodeClaimPolicyState.set(this.normalizeShareCodeKey(code), state);
    return { allowed: true };
  }

  markShareCodeClaimFailure(code: string): void {
    const now = Date.now();
    const key = this.normalizeShareCodeKey(code);
    const maxAttempts = this.getShareCodeClaimMaxAttempts();
    const blockMs = this.getShareCodeClaimBlockMs();
    const state = this.getShareCodeClaimState(code);
    state.attempts += 1;
    state.lastAttemptAt = now;
    if (state.attempts >= maxAttempts && blockMs > 0) {
      state.blockedUntil = now + blockMs;
    }
    this.shareCodeClaimPolicyState.set(key, state);
  }

  markShareCodeClaimSuccess(code: string): void {
    this.shareCodeClaimPolicyState.delete(this.normalizeShareCodeKey(code));
  }

  getResumeTokenSignatureVersion(): string | undefined {
    const version = this.options.getFormConfig()?.storage?.resumeTokenSignatureVersion;
    return typeof version === "string" && version ? version : undefined;
  }

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
      this.createEventDetail({}, {
        token,
        savedAt: state.savedAt,
        resumeEndpoint: state.resumeEndpoint,
        signatureVersion: state.signatureVersion,
      }),
    );
  }

  parseRemoteShareCodeCreateResponse(result: unknown): TRemoteResumeShareCodeCreateResponse | null {
    const normalizedResult =
      typeof result === "string"
        ? (() => {
            try {
              return JSON.parse(result) as unknown;
            } catch {
              return null;
            }
          })()
        : result;

    if (!normalizedResult || typeof normalizedResult !== "object") {
      return null;
    }

    const response = normalizedResult as Record<string, any>;
    if (typeof response.code !== "string" || !response.code) {
      return null;
    }

    return {
      operation: "create-share-code",
      code: response.code,
      token: typeof response.token === "string" ? response.token : undefined,
      expiresAt: typeof response.expiresAt === "number" ? response.expiresAt : undefined,
    };
  }

  parseRemoteShareCodeClaimResponse(result: unknown): TRemoteResumeShareCodeClaimResponse | null {
    const normalizedResult =
      typeof result === "string"
        ? (() => {
            try {
              return JSON.parse(result) as unknown;
            } catch {
              return null;
            }
          })()
        : result;

    if (!normalizedResult || typeof normalizedResult !== "object") {
      return null;
    }

    const response = normalizedResult as Record<string, any>;
    if (typeof response.code !== "string" || !response.code) {
      return null;
    }
    if (typeof response.token !== "string" || !response.token) {
      return null;
    }

    return {
      operation: "claim-share-code",
      code: response.code,
      token: response.token,
      savedAt: typeof response.savedAt === "number" ? response.savedAt : Date.now(),
      issuedAt: typeof response.issuedAt === "number" ? response.issuedAt : undefined,
      expiresAt: typeof response.expiresAt === "number" ? response.expiresAt : undefined,
      signature: typeof response.signature === "string" ? response.signature : undefined,
      signatureVersion:
        typeof response.signatureVersion === "string"
          ? response.signatureVersion
          : undefined,
      snapshot: this.normalizeResumeSnapshot(response.snapshot),
    };
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

  applyResumeSnapshot(snapshot: TFormStorageSnapshot): Record<string, any> {
    if (this.storageAdapter) {
      if (snapshot.draft) {
        this.storageAdapter.saveDraft(snapshot.draft);
      } else {
        this.storageAdapter.clearDraft();
      }
      this.storageAdapter.saveQueue(Array.isArray(snapshot.queue) ? snapshot.queue : []);
      this.storageAdapter.saveDeadLetterQueue(Array.isArray(snapshot.deadLetter) ? snapshot.deadLetter : []);
    }

    if (typeof snapshot.currentStepIndex === "number") {
      this.saveCurrentStepIndex(snapshot.currentStepIndex);
      this.options.setCurrentStepIndex?.(snapshot.currentStepIndex);
    } else {
      this.saveCurrentStepIndex(null);
    }

    return getRestorableStorageValues(
      snapshot.draft && typeof snapshot.draft === "object" ? snapshot.draft : null,
    );
  }

  getRetryDelayMs(attempts: number): number {
    const baseDelayMs = 1000;
    const maxDelayMs = 30000;
    return Math.min(baseDelayMs * Math.pow(2, Math.max(0, attempts - 1)), maxDelayMs);
  }

  getMaxRetryAttempts(): number {
    return 3;
  }

  getQueueDisabledReason(): string | undefined {
    const formConfig = this.options.getFormConfig();
    if (!formConfig) {
      return undefined;
    }

    const hasFileFields = Object.values(formConfig.sections || {})
      .flat()
      .some((field) => isFileFieldType(field.type));

    if (hasFileFields) {
      return "file-uploads-are-not-queued";
    }

    return undefined;
  }

  shouldUseQueue(): boolean {
    const mode = this.options.getFormConfig()?.storage?.mode;
    if (this.getQueueDisabledReason()) {
      return false;
    }

    return mode === "queue" || mode === "draft-and-queue";
  }

  createEventDetail(
    values: Record<string, any>,
    result?: any,
    response?: Response,
    error?: unknown,
  ): Record<string, any> {
    const formConfig = this.options.getFormConfig();
    return {
      values,
      formConfig,
      submit: formConfig?.submit,
      result,
      response,
      error,
    };
  }

  emitDraftRestored(values: Record<string, any>): void {
    this.options.emitEvent("xpressui:draft-restored", this.createEventDetail(values));
  }

  saveDraft(values?: Record<string, any>): void {
    if (!this.storageAdapter) {
      return;
    }

    const draftValues = values || this.options.getValues();
    this.storageAdapter.saveDraft(draftValues);
    this.saveCurrentStepIndex(this.options.getCurrentStepIndex?.() ?? null);
    this.options.emitEvent("xpressui:draft-saved", this.createEventDetail(draftValues));
  }

  scheduleDraftSave(): void {
    if (!this.storageAdapter) {
      return;
    }

    if (this.draftSaveTimer !== null) {
      window.clearTimeout(this.draftSaveTimer);
    }

    this.draftSaveTimer = window.setTimeout(() => {
      this.saveDraft();
      this.draftSaveTimer = null;
    }, this.getDraftAutoSaveMs());
  }

  clearDraft(): void {
    if (!this.storageAdapter) {
      return;
    }

    if (this.draftSaveTimer !== null) {
      window.clearTimeout(this.draftSaveTimer);
      this.draftSaveTimer = null;
    }

    this.storageAdapter.clearDraft();
    this.saveCurrentStepIndex(null);
    this.options.emitEvent("xpressui:draft-cleared", this.createEventDetail({}));
  }

  enqueueSubmission(values: Record<string, any>): void {
    if (!this.storageAdapter || !this.shouldUseQueue()) {
      return;
    }

    const queue = this.storageAdapter.enqueueSubmission(values);
    const nextEntry = queue[0];
    this.options.emitEvent(
      "xpressui:queued",
      this.createEventDetail(values, {
        queueLength: queue.length,
        deadLetterLength: this.storageAdapter.loadDeadLetterQueue().length,
        nextAttemptAt: nextEntry?.nextAttemptAt,
        attempts: nextEntry?.attempts,
      } satisfies TFormQueueState),
    );
    this.emitQueueState();
  }

  getQueueState(): TFormQueueState {
    const queue = this.storageAdapter?.loadQueue() || [];
    const nextEntry = queue[0];
    return {
      queueLength: queue.length,
      deadLetterLength: this.storageAdapter?.loadDeadLetterQueue().length || 0,
      nextAttemptAt: nextEntry?.nextAttemptAt,
      attempts: nextEntry?.attempts,
      disabledReason: this.getQueueDisabledReason(),
    };
  }

  emitQueueState(): void {
    const mode = this.options.getFormConfig()?.storage?.mode;
    const storageEnabled = mode === "queue" || mode === "draft-and-queue";
    if (!storageEnabled) {
      return;
    }

    this.options.emitEvent(
      "xpressui:queue-state",
      this.createEventDetail(this.options.getValues(), this.getQueueState()),
    );
  }

  getStorageSnapshot(): TFormStorageSnapshot {
    return {
      draft: this.storageAdapter?.loadDraft() || null,
      queue: this.storageAdapter?.loadQueue() || [],
      deadLetter: this.storageAdapter?.loadDeadLetterQueue() || [],
      currentStepIndex: this.loadCurrentStepIndex() ?? undefined,
    };
  }

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
        this.createEventDetail(currentValues, {
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
        headers: {
          "Content-Type": "application/json",
        },
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
          this.createEventDetail(currentValues, {
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

  async createResumeShareCode(token: string): Promise<string | null> {
    const detail = await this.createResumeShareCodeDetail(token);
    return detail?.code || null;
  }

  async createResumeShareCodeDetail(token: string): Promise<TResumeShareCodeInfo | null> {
    const endpoint = this.getShareCodeEndpoint();
    if (!endpoint || !token) {
      return null;
    }

    try {
      const response = await fetch(endpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          operation: "create-share-code",
          token,
        } satisfies TRemoteResumeShareCodeCreateRequest),
      });
      const contentType = response.headers.get("content-type") || "";
      const result = contentType.includes("application/json")
        ? await response.json()
        : await response.text();
      if (!response.ok) {
        return null;
      }

      const parsed = this.parseRemoteShareCodeCreateResponse(result);
      if (!parsed) {
        return null;
      }

      this.options.emitEvent(
        "xpressui:resume-share-code-created",
        this.createEventDetail(this.options.getValues(), {
          operation: parsed.operation,
          code: parsed.code,
          token: parsed.token || token,
          expiresAt: parsed.expiresAt,
          endpoint,
        }, response),
      );
      return {
        code: parsed.code,
        token: parsed.token || token,
        expiresAt: parsed.expiresAt,
        endpoint,
      };
    } catch {
      return null;
    }
  }

  async claimResumeShareCode(code: string): Promise<TResumeLookupResult | null> {
    const detail = await this.claimResumeShareCodeDetail(code);
    return detail?.lookup || null;
  }

  async claimResumeShareCodeDetail(code: string): Promise<TResumeShareCodeClaimDetail | null> {
    const endpoint = this.getShareCodeEndpoint();
    if (!endpoint || !code) {
      const detail: TResumeShareCodeClaimDetail = {
        code,
        status: "invalid_response",
        endpoint,
        message: "Share-code claim is not configured.",
      };
      this.emitShareCodeClaimState(detail);
      return detail;
    }
    const permission = this.evaluateShareCodeClaimPermission(code);
    if (!permission.allowed) {
      return permission.detail || null;
    }

    try {
      const response = await fetch(endpoint, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          operation: "claim-share-code",
          code,
        } satisfies TRemoteResumeShareCodeClaimRequest),
      });
      const contentType = response.headers.get("content-type") || "";
      const result = contentType.includes("application/json")
        ? await response.json()
        : await response.text();
      if (!response.ok) {
        const remotePolicy = getRemoteResumePolicy(result);
        if (remotePolicy) {
          const detail: TResumeShareCodeClaimDetail = {
            code,
            status: this.normalizeRemotePolicyClaimStatus(remotePolicy.code),
            backend: true,
            endpoint,
            ...(remotePolicy.reason ? { message: remotePolicy.reason } : {}),
            ...(remotePolicy.retryAfterSeconds !== undefined
              ? { retryAfterSeconds: remotePolicy.retryAfterSeconds }
              : {}),
            ...(remotePolicy.blockedUntil !== undefined
              ? { blockedUntil: remotePolicy.blockedUntil }
              : {}),
            ...(remotePolicy.expiresAt !== undefined
              ? { expiresAt: remotePolicy.expiresAt }
              : {}),
          };
          this.options.emitEvent(
            "xpressui:resume-share-code-claim-blocked",
            this.createEventDetail(this.options.getValues(), {
              code,
              reason: remotePolicy.code,
              backend: true,
              endpoint,
              ...(remotePolicy.reason ? { message: remotePolicy.reason } : {}),
              ...(remotePolicy.retryAfterSeconds !== undefined
                ? { retryAfterSeconds: remotePolicy.retryAfterSeconds }
                : {}),
              ...(remotePolicy.blockedUntil !== undefined
                ? { blockedUntil: remotePolicy.blockedUntil }
                : {}),
              ...(remotePolicy.expiresAt !== undefined
                ? { expiresAt: remotePolicy.expiresAt }
                : {}),
            }, response),
          );
          this.emitShareCodeClaimState(detail, response);
        }
        this.markShareCodeClaimFailure(code);
        return remotePolicy
          ? {
              code,
              status: this.normalizeRemotePolicyClaimStatus(remotePolicy.code),
              backend: true,
              endpoint,
              ...(remotePolicy.reason ? { message: remotePolicy.reason } : {}),
              ...(remotePolicy.retryAfterSeconds !== undefined
                ? { retryAfterSeconds: remotePolicy.retryAfterSeconds }
                : {}),
              ...(remotePolicy.blockedUntil !== undefined
                ? { blockedUntil: remotePolicy.blockedUntil }
                : {}),
              ...(remotePolicy.expiresAt !== undefined
                ? { expiresAt: remotePolicy.expiresAt }
                : {}),
            }
          : {
              code,
              status: "network_error",
              backend: true,
              endpoint,
              message: "Share-code claim failed.",
            };
      }

      const parsed = this.parseRemoteShareCodeClaimResponse(result);
      if (!parsed) {
        this.markShareCodeClaimFailure(code);
        const detail: TResumeShareCodeClaimDetail = {
          code,
          status: "invalid_response",
          backend: true,
          endpoint,
          message: "Invalid share-code claim response.",
        };
        this.emitShareCodeClaimState(detail, response);
        return detail;
      }

      if (!parsed.snapshot) {
        this.markShareCodeClaimFailure(code);
        const detail: TResumeShareCodeClaimDetail = {
          code,
          status: "invalid_response",
          backend: true,
          endpoint,
          token: parsed.token,
          message: "Share-code claim response did not include a snapshot.",
        };
        this.emitShareCodeClaimState(detail, response);
        return detail;
      }

      const baseState: TResumeTokenState = {
        version: 1,
        savedAt: parsed.savedAt,
        issuedAt: parsed.issuedAt ?? parsed.savedAt,
        expiresAt:
          typeof parsed.expiresAt === "number"
            ? parsed.expiresAt
            : this.getResumeTokenExpiresAt(parsed.savedAt),
        snapshot: parsed.snapshot,
        resumeEndpoint: this.getResumeEndpoint(),
        remote: true,
        ...(parsed.signature ? { signature: parsed.signature } : {}),
        ...(parsed.signatureVersion ? { signatureVersion: parsed.signatureVersion } : {}),
      };
      const state = baseState.signature
        ? baseState
        : this.applyResumeTokenSignature(parsed.token, baseState);
      if (!this.isResumeTokenSignatureValid(parsed.token, state)) {
        this.markShareCodeClaimFailure(code);
        this.emitResumeTokenInvalidSignature(parsed.token, {
          savedAt: parsed.savedAt,
          resumeEndpoint: this.getResumeEndpoint(),
          signatureVersion: state.signatureVersion,
        });
        const detail: TResumeShareCodeClaimDetail = {
          code,
          status: "invalid_signature",
          endpoint,
          token: parsed.token,
          savedAt: parsed.savedAt,
          issuedAt: state.issuedAt,
          expiresAt: state.expiresAt,
          signatureVersion: state.signatureVersion,
          signatureValid: false,
          snapshot: parsed.snapshot,
          remote: true,
          backend: true,
          message: "Share-code claim signature verification failed.",
        };
        this.emitShareCodeClaimState(detail, response);
        return detail;
      }

      this.persistResumeTokenState(parsed.token, state);
      this.markShareCodeClaimSuccess(code);
      const lookup: TResumeLookupResult = {
        token: parsed.token,
        savedAt: parsed.savedAt,
        issuedAt: state.issuedAt,
        expiresAt: state.expiresAt,
        expired: false,
        resumeEndpoint: this.getResumeEndpoint(),
        remote: true,
        signatureVersion: state.signatureVersion,
        signatureValid: true,
        snapshot: parsed.snapshot,
      };

      this.options.emitEvent(
        "xpressui:resume-share-code-claimed",
        this.createEventDetail(this.options.getValues(), {
          operation: parsed.operation,
          code: parsed.code,
          token: parsed.token,
          savedAt: parsed.savedAt,
          issuedAt: state.issuedAt,
          expiresAt: state.expiresAt,
          signatureVersion: state.signatureVersion,
        }, response),
      );
      const detail: TResumeShareCodeClaimDetail = {
        code: parsed.code,
        status: "claimed",
        endpoint,
        token: parsed.token,
        savedAt: parsed.savedAt,
        issuedAt: state.issuedAt,
        expiresAt: state.expiresAt,
        signatureVersion: state.signatureVersion,
        signatureValid: true,
        snapshot: parsed.snapshot,
        remote: true,
        backend: true,
        lookup,
      };
      this.emitShareCodeClaimState(detail, response);
      return detail;
    } catch {
      this.markShareCodeClaimFailure(code);
      const detail: TResumeShareCodeClaimDetail = {
        code,
        status: "network_error",
        endpoint,
        backend: true,
        message: "Share-code claim request failed.",
      };
      this.emitShareCodeClaimState(detail);
      return detail;
    }
  }

  async restoreFromShareCodeAsync(code: string): Promise<Record<string, any> | null> {
    const detail = await this.restoreFromShareCodeDetailAsync(code);
    return detail?.restoredValues || null;
  }

  async restoreFromShareCodeDetailAsync(code: string): Promise<TResumeShareCodeRestoreDetail | null> {
    const claim = await this.claimResumeShareCodeDetail(code);
    if (!claim || claim.status !== "claimed" || !claim.lookup?.snapshot) {
      const detail: TResumeShareCodeRestoreDetail = {
        code,
        status: "claim_failed",
        claim,
        message: claim?.message || "Share-code restore could not continue.",
      };
      this.lastShareCodeRestoreDetail = detail;
      this.options.emitEvent(
        "xpressui:resume-share-code-restore-state",
        this.createEventDetail(this.options.getValues(), detail),
      );
      return detail;
    }

    const restoredDraft = this.applyResumeSnapshot(claim.lookup.snapshot);
    this.options.emitEvent(
      "xpressui:resume-token-restored",
      this.createEventDetail(restoredDraft, {
        token: claim.lookup.token,
        snapshot: claim.lookup.snapshot,
        savedAt: claim.lookup.savedAt,
        issuedAt: claim.lookup.issuedAt,
        expiresAt: claim.lookup.expiresAt,
        resumeEndpoint: claim.lookup.resumeEndpoint,
        remote: true,
        signatureVersion: claim.lookup.signatureVersion,
        shareCode: code,
      }),
    );
    const detail: TResumeShareCodeRestoreDetail = {
      code,
      status: "restored",
      claim,
      restoredValues: restoredDraft,
      token: claim.lookup.token,
    };
    this.lastShareCodeRestoreDetail = detail;
    this.options.emitEvent(
      "xpressui:resume-share-code-restore-state",
      this.createEventDetail(restoredDraft, detail),
    );
    return detail;
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
          this.createEventDetail({}, {
            token,
            savedAt: parsed.savedAt,
            resumeEndpoint: parsed.resumeEndpoint,
          }),
        );
        return null;
      }
      const snapshot = parsed.snapshot;

      const restoredDraft = this.applyResumeSnapshot(snapshot);
      this.options.emitEvent(
        "xpressui:resume-token-restored",
        this.createEventDetail(restoredDraft, {
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

  async restoreFromResumeTokenAsync(token: string): Promise<Record<string, any> | null> {
    const lookup = await this.lookupResumeToken(token);
    if (!lookup) {
      return null;
    }

    if (lookup.resumeEndpoint && lookup.snapshot) {
      const restoredDraft = this.applyResumeSnapshot(lookup.snapshot);
      this.options.emitEvent(
        "xpressui:resume-token-restored",
        this.createEventDetail(restoredDraft, {
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
      this.createEventDetail(this.options.getValues(), {
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
          this.createEventDetail(this.options.getValues(), {
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
          this.createEventDetail(this.options.getValues(), {
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
        this.createEventDetail(this.options.getValues(), {
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

  getStorageHealth(): TFormStorageHealth {
    const storageHealth = this.storageAdapter?.getHealth() || {
      adapter: "local-storage",
      encryptionEnabled: false,
      hasDraft: false,
      queueLength: 0,
      deadLetterLength: 0,
      totalEntries: 0,
      retentionMs: {
        draft: null,
        queue: null,
        deadLetter: null,
      },
    };

    return {
      ...storageHealth,
      queueDisabledReason: this.getQueueDisabledReason(),
      queueEnabled: this.shouldUseQueue(),
    };
  }

  clearDeadLetterQueue(): void {
    if (!this.storageAdapter) {
      return;
    }

    this.storageAdapter.clearDeadLetterQueue();
    this.options.emitEvent(
      "xpressui:dead-letter-cleared",
      this.createEventDetail({}, this.getQueueState()),
    );
    this.emitQueueState();
  }

  requeueDeadLetterEntry(entryId: string): boolean {
    if (!this.storageAdapter) {
      return false;
    }

    const entry = this.storageAdapter.removeDeadLetterEntry(entryId);
    if (!entry) {
      return false;
    }

    const resetEntry = entry.values;
    const queue = this.storageAdapter.enqueueSubmission(resetEntry);
    this.options.emitEvent(
      "xpressui:dead-letter-requeued",
      this.createEventDetail(resetEntry, {
        queueLength: queue.length,
        deadLetterLength: this.storageAdapter.loadDeadLetterQueue().length,
        entryId,
      }),
    );
    this.emitQueueState();
    return true;
  }

  async replayDeadLetterEntry(entryId: string): Promise<boolean> {
    const formConfig = this.options.getFormConfig();
    if (!this.storageAdapter || !formConfig?.submit?.endpoint) {
      return false;
    }

    const entry = this.storageAdapter.removeDeadLetterEntry(entryId);
    if (!entry) {
      return false;
    }

    try {
      const { response, result } = await this.options.submitValues(entry.values, formConfig.submit);
      this.options.emitEvent(
        "xpressui:dead-letter-replayed-success",
        this.createEventDetail(entry.values, result, response),
      );
      this.emitQueueState();
      return true;
    } catch (error: any) {
      const replayEntry: TQueuedSubmission = {
        ...entry,
        attempts: entry.attempts + 1,
        updatedAt: Date.now(),
        nextAttemptAt: Date.now() + this.getRetryDelayMs(entry.attempts + 1),
        lastError: error?.result?.message || error?.message || "replay_error",
      };
      const deadLetter = this.storageAdapter.enqueueDeadLetter(replayEntry);
      this.options.emitEvent(
        "xpressui:dead-letter-replayed-error",
        this.createEventDetail(
          entry.values,
          {
            deadLetterLength: deadLetter.length,
            entry: replayEntry,
          },
          error?.response,
          error,
        ),
      );
      this.emitQueueState();
      return false;
    }
  }

  async flushSubmissionQueue(): Promise<void> {
    const formConfig = this.options.getFormConfig();
    if (
      !this.storageAdapter ||
      !formConfig?.submit?.endpoint ||
      !this.shouldUseQueue() ||
      this.syncInFlight
    ) {
      return;
    }

    const pending = this.storageAdapter.loadQueue();
    if (!pending.length) {
      return;
    }

    this.syncInFlight = true;
    try {
      while (true) {
        const entry = this.storageAdapter.loadQueue()[0];
        if (!entry) {
          this.emitQueueState();
          break;
        }

        if (entry.nextAttemptAt > Date.now()) {
          break;
        }

        try {
          const { response, result } = await this.options.submitValues(entry.values, formConfig.submit);
          this.storageAdapter.dequeueSubmission();
          this.options.emitEvent(
            "xpressui:sync-success",
            this.createEventDetail(entry.values, result, response),
          );
          this.emitQueueState();
        } catch (error: any) {
          const attempts = entry.attempts + 1;
          const nextEntry: TQueuedSubmission = {
            ...entry,
            attempts,
            updatedAt: Date.now(),
            nextAttemptAt: Date.now() + this.getRetryDelayMs(attempts),
            lastError: error?.result?.message || error?.message || "sync_error",
          };
          if (attempts >= this.getMaxRetryAttempts()) {
            this.storageAdapter.dequeueSubmission();
            const deadLetter = this.storageAdapter.enqueueDeadLetter(nextEntry);
            this.options.emitEvent(
              "xpressui:dead-lettered",
              this.createEventDetail(
                entry.values,
                {
                  deadLetterLength: deadLetter.length,
                  entry: nextEntry,
                },
                error?.response,
                error,
              ),
            );
          } else {
            this.storageAdapter.updateQueueEntry(nextEntry);
          }
          this.options.emitEvent(
            "xpressui:sync-error",
            this.createEventDetail(entry.values, error?.result, error?.response, error),
          );
          this.emitQueueState();
          break;
        }
      }
    } finally {
      this.syncInFlight = false;
    }
  }
}
