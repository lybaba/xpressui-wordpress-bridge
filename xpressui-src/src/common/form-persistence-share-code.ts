import type { TFormResumeRuntimeOptions, TResumeTokenState } from "./form-persistence-resume";
import {
  parseRemoteShareCodeClaimResponse,
  parseRemoteShareCodeCreateResponse,
} from "./form-persistence-remote";
import { normalizeShareCodeKey as normalizeShareCodeStorageKey } from "./form-persistence-policy";
import { getRemoteResumePolicy } from "./resume-contract";
import type {
  TRemoteResumeShareCodeClaimRequest,
  TRemoteResumeShareCodeCreateRequest,
  TResumeLookupResult,
  TResumeShareCodeClaimDetail,
  TResumeShareCodeClaimStatus,
  TResumeShareCodeInfo,
  TResumeShareCodeRestoreDetail,
  TResumeStatusSummary,
  TResumeTokenInfo,
} from "./form-persistence.types";

// ---------------------------------------------------------------------------
// Token access interface (provided by FormResumeRuntime)
// ---------------------------------------------------------------------------

export type TShareCodeTokenAccess = {
  getResumeEndpoint(): string | undefined;
  getShareCodeEndpoint(): string | undefined;
  getResumeTokenExpiresAt(savedAt: number): number | undefined;
  persistResumeTokenState(token: string, state: TResumeTokenState): boolean;
  applyResumeTokenSignature(token: string, state: TResumeTokenState): TResumeTokenState;
  isResumeTokenSignatureValid(token: string, state: TResumeTokenState): boolean;
  emitResumeTokenInvalidSignature(
    token: string,
    state: { savedAt?: number; resumeEndpoint?: string; signatureVersion?: string },
  ): void;
  listResumeTokens(): TResumeTokenInfo[];
};

// ---------------------------------------------------------------------------
// Internal state type
// ---------------------------------------------------------------------------

type TShareCodeClaimPolicyState = {
  attempts: number;
  windowStartedAt: number;
  lastAttemptAt: number;
  blockedUntil: number;
};

// ---------------------------------------------------------------------------
// FormShareCodeRuntime
// ---------------------------------------------------------------------------

export class FormShareCodeRuntime {
  options: TFormResumeRuntimeOptions;
  token: TShareCodeTokenAccess;
  shareCodeClaimPolicyState: Map<string, TShareCodeClaimPolicyState>;
  lastShareCodeClaimDetail: TResumeShareCodeClaimDetail | null;
  lastShareCodeRestoreDetail: TResumeShareCodeRestoreDetail | null;

  constructor(options: TFormResumeRuntimeOptions, token: TShareCodeTokenAccess) {
    this.options = options;
    this.token = token;
    this.shareCodeClaimPolicyState = new Map();
    this.lastShareCodeClaimDetail = null;
    this.lastShareCodeRestoreDetail = null;
  }

  // -------------------------------------------------------------------------
  // Share code config getters
  // -------------------------------------------------------------------------

  getShareCodeClaimThrottleMs(): number {
    return Math.max(
      0,
      Number(this.options.getFormConfig()?.storage?.shareCodeClaimThrottleMs ?? 0),
    );
  }

  getShareCodeClaimMaxAttempts(): number {
    return Math.max(
      1,
      Number(this.options.getFormConfig()?.storage?.shareCodeClaimMaxAttempts ?? 5),
    );
  }

  getShareCodeClaimWindowMs(): number {
    return Math.max(
      0,
      Number(this.options.getFormConfig()?.storage?.shareCodeClaimWindowMs ?? 5 * 60 * 1000),
    );
  }

  getShareCodeClaimBlockMs(): number {
    return Math.max(
      0,
      Number(this.options.getFormConfig()?.storage?.shareCodeClaimBlockMs ?? 5 * 60 * 1000),
    );
  }

  normalizeShareCodeKey(code: string): string {
    return normalizeShareCodeStorageKey(code);
  }

  normalizeRemotePolicyClaimStatus(
    code: "rate_limited" | "blocked" | "expired" | "invalid_signature" | "not_found",
  ): TResumeShareCodeClaimStatus {
    return code === "rate_limited" ? "throttled" : code;
  }

  // -------------------------------------------------------------------------
  // Share code claim state
  // -------------------------------------------------------------------------

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
      this.options.createEventDetail(this.options.getValues(), {
        code,
        ...result,
      }),
    );
  }

  emitShareCodeClaimState(result: TResumeShareCodeClaimDetail, response?: Response): void {
    this.lastShareCodeClaimDetail = result;
    this.options.emitEvent(
      "xpressui:resume-share-code-claim-state",
      this.options.createEventDetail(this.options.getValues(), result, response),
    );
  }

  evaluateShareCodeClaimPermission(code: string): {
    allowed: boolean;
    detail?: TResumeShareCodeClaimDetail;
  } {
    const now = Date.now();
    const state = this.getShareCodeClaimState(code);
    const maxAttempts = this.getShareCodeClaimMaxAttempts();
    const throttleMs = this.getShareCodeClaimThrottleMs();
    const endpoint = this.token.getShareCodeEndpoint();

    if (throttleMs > 0 && state.lastAttemptAt > 0 && now - state.lastAttemptAt < throttleMs) {
      const detail: TResumeShareCodeClaimDetail = {
        code,
        status: "throttled",
        endpoint,
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
        endpoint,
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
        endpoint,
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

  // -------------------------------------------------------------------------
  // Share code CRUD
  // -------------------------------------------------------------------------

  getResumeStatusSummary(): TResumeStatusSummary {
    const tokens = this.token.listResumeTokens();
    const resumeEndpoint = this.token.getResumeEndpoint();
    const shareCodeEndpoint = this.token.getShareCodeEndpoint();
    return {
      configured: Boolean(resumeEndpoint || shareCodeEndpoint),
      ...(resumeEndpoint ? { resumeEndpoint } : {}),
      ...(shareCodeEndpoint ? { shareCodeEndpoint } : {}),
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

  async createResumeShareCode(token: string): Promise<string | null> {
    const detail = await this.createResumeShareCodeDetail(token);
    return detail?.code || null;
  }

  async createResumeShareCodeDetail(token: string): Promise<TResumeShareCodeInfo | null> {
    const endpoint = this.token.getShareCodeEndpoint();
    if (!endpoint || !token) {
      return null;
    }

    try {
      const response = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
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

      const parsed = parseRemoteShareCodeCreateResponse(result);
      if (!parsed) {
        return null;
      }

      this.options.emitEvent(
        "xpressui:resume-share-code-created",
        this.options.createEventDetail(this.options.getValues(), {
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
    const endpoint = this.token.getShareCodeEndpoint();
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
        headers: { "Content-Type": "application/json" },
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
            ...(remotePolicy.expiresAt !== undefined ? { expiresAt: remotePolicy.expiresAt } : {}),
          };
          this.options.emitEvent(
            "xpressui:resume-share-code-claim-blocked",
            this.options.createEventDetail(this.options.getValues(), {
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

      const parsed = parseRemoteShareCodeClaimResponse(result);
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
            : this.token.getResumeTokenExpiresAt(parsed.savedAt),
        snapshot: parsed.snapshot,
        resumeEndpoint: this.token.getResumeEndpoint(),
        remote: true,
        ...(parsed.signature ? { signature: parsed.signature } : {}),
        ...(parsed.signatureVersion ? { signatureVersion: parsed.signatureVersion } : {}),
      };
      const state = baseState.signature
        ? baseState
        : this.token.applyResumeTokenSignature(parsed.token, baseState);
      if (!this.token.isResumeTokenSignatureValid(parsed.token, state)) {
        this.markShareCodeClaimFailure(code);
        this.token.emitResumeTokenInvalidSignature(parsed.token, {
          savedAt: parsed.savedAt,
          resumeEndpoint: this.token.getResumeEndpoint(),
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

      this.token.persistResumeTokenState(parsed.token, state);
      this.markShareCodeClaimSuccess(code);
      const lookup: TResumeLookupResult = {
        token: parsed.token,
        savedAt: parsed.savedAt,
        issuedAt: state.issuedAt,
        expiresAt: state.expiresAt,
        expired: false,
        resumeEndpoint: this.token.getResumeEndpoint(),
        remote: true,
        signatureVersion: state.signatureVersion,
        signatureValid: true,
        snapshot: parsed.snapshot,
      };

      this.options.emitEvent(
        "xpressui:resume-share-code-claimed",
        this.options.createEventDetail(this.options.getValues(), {
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
        this.options.createEventDetail(this.options.getValues(), detail),
      );
      return detail;
    }

    const restoredDraft = this.options.applyResumeSnapshot(claim.lookup.snapshot);
    this.options.emitEvent(
      "xpressui:resume-token-restored",
      this.options.createEventDetail(restoredDraft, {
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
      this.options.createEventDetail(restoredDraft, detail),
    );
    return detail;
  }
}
