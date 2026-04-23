import TFormConfig from "./TFormConfig";
import {
  TResumeLookupResult,
  TResumeShareCodeClaimDetail,
  TResumeShareCodeRestoreDetail,
  TResumeShareCodeInfo,
  TResumeStatusSummary,
  TResumeTokenInfo,
} from "./form-persistence";
import type { TFormStorageAdapter } from "./form-storage";
import type { TLocalFormAdminSnapshot } from "./form-summary-builders";

// ---------------------------------------------------------------------------
// Context passed from form-admin factory
// ---------------------------------------------------------------------------

export type TAdminResumeContext = {
  publicConfig: TFormConfig;
  resumePrefix: string;
  storageAdapter: TFormStorageAdapter | null;
  getShareCodeEndpoint(): string | undefined;
  getResumeTokenTtlMs(): number | null;
  verifyTokenSignature(payload: Record<string, any>): boolean;
};

// ---------------------------------------------------------------------------
// Resume handlers
// ---------------------------------------------------------------------------

export type TAdminResumeHandlers = {
  getResumeStatusSummary(): TResumeStatusSummary;
  listResumeTokens(): TResumeTokenInfo[];
  createResumeShareCode(token: string): Promise<string | null>;
  createResumeShareCodeDetail(token: string): Promise<TResumeShareCodeInfo | null>;
  claimResumeShareCode(code: string): Promise<TResumeLookupResult | null>;
  claimResumeShareCodeDetail(code: string): Promise<TResumeShareCodeClaimDetail | null>;
  restoreFromShareCode(code: string): Promise<Record<string, any> | null>;
  restoreFromShareCodeDetail(code: string): Promise<TResumeShareCodeRestoreDetail | null>;
  deleteResumeToken(token: string): boolean;
  invalidateResumeToken(token: string): Promise<boolean>;
};

export function buildAdminResumeHandlers(ctx: TAdminResumeContext): TAdminResumeHandlers {
  const { publicConfig, resumePrefix, storageAdapter } = ctx;

  const listResumeTokens = (): TResumeTokenInfo[] => {
    if (typeof window === "undefined") {
      return [];
    }

    const ttlMs = ctx.getResumeTokenTtlMs();
    const tokens: TResumeTokenInfo[] = [];
    for (let index = window.localStorage.length - 1; index >= 0; index -= 1) {
      const key = window.localStorage.key(index);
      if (!key || !key.startsWith(resumePrefix)) {
        continue;
      }

      const raw = window.localStorage.getItem(key);
      if (!raw) {
        window.localStorage.removeItem(key);
        continue;
      }

      try {
        const parsed = JSON.parse(raw) as {
          savedAt?: number;
          issuedAt?: number;
          expiresAt?: number;
          resumeEndpoint?: string;
          remote?: boolean;
          signature?: string;
          signatureVersion?: string;
          snapshot?: TResumeLookupResult["snapshot"];
        };
        const savedAt = typeof parsed.savedAt === "number" ? parsed.savedAt : 0;
        const expired = Boolean(ttlMs && savedAt && Date.now() - savedAt > ttlMs);
        if (expired) {
          window.localStorage.removeItem(key);
          continue;
        }
        const signatureVersion =
          typeof parsed.signatureVersion === "string" ? parsed.signatureVersion : undefined;
        const hasSignatureMetadata = Boolean(signatureVersion || parsed.signature);
        tokens.push({
          token: key.slice(resumePrefix.length),
          savedAt,
          ...(typeof parsed.issuedAt === "number" ? { issuedAt: parsed.issuedAt } : {}),
          ...(typeof parsed.expiresAt === "number" ? { expiresAt: parsed.expiresAt } : {}),
          expired: false,
          resumeEndpoint:
            typeof parsed.resumeEndpoint === "string"
              ? parsed.resumeEndpoint
              : publicConfig.storage?.resumeEndpoint,
          remote: Boolean(parsed.remote),
          ...(signatureVersion ? { signatureVersion } : {}),
          ...(hasSignatureMetadata
            ? {
                signatureValid: ctx.verifyTokenSignature({
                  token: key.slice(resumePrefix.length),
                  formName: publicConfig.name,
                  savedAt,
                  issuedAt: typeof parsed.issuedAt === "number" ? parsed.issuedAt : savedAt,
                  expiresAt:
                    typeof parsed.expiresAt === "number" ? parsed.expiresAt : undefined,
                  snapshot: parsed.snapshot ?? null,
                  resumeEndpoint:
                    typeof parsed.resumeEndpoint === "string"
                      ? parsed.resumeEndpoint
                      : publicConfig.storage?.resumeEndpoint,
                  remote: Boolean(parsed.remote),
                  signature: parsed.signature,
                  signatureVersion,
                }),
              }
            : {}),
        });
      } catch {
        window.localStorage.removeItem(key);
      }
    }

    return tokens.sort((left, right) => right.savedAt - left.savedAt);
  };

  const getResumeStatusSummary = (): TResumeStatusSummary => {
    const resumeTokens = listResumeTokens();
    const shareCodeEndpoint = ctx.getShareCodeEndpoint();
    return {
      configured: Boolean(publicConfig.storage?.resumeEndpoint || shareCodeEndpoint),
      ...(publicConfig.storage?.resumeEndpoint
        ? { resumeEndpoint: publicConfig.storage.resumeEndpoint }
        : {}),
      ...(shareCodeEndpoint ? { shareCodeEndpoint } : {}),
      tokens: {
        total: resumeTokens.length,
        remote: resumeTokens.filter((entry) => entry.remote).length,
        signed: resumeTokens.filter((entry) => Boolean(entry.signatureVersion)).length,
        invalidSignature: resumeTokens.filter((entry) => entry.signatureValid === false).length,
        ...(resumeTokens[0]?.savedAt ? { latestSavedAt: resumeTokens[0].savedAt } : {}),
      },
      lastClaim: null,
      lastRestore: null,
    };
  };

  const createResumeShareCodeDetail = async (
    token: string,
  ): Promise<TResumeShareCodeInfo | null> => {
    const endpoint = ctx.getShareCodeEndpoint();
    if (!endpoint || !token) {
      return null;
    }

    try {
      const response = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ operation: "create-share-code", token }),
      });
      const contentType = response.headers.get("content-type") || "";
      const result = contentType.includes("application/json") ? await response.json() : null;
      if (!response.ok || !result || typeof result !== "object") {
        return null;
      }
      const code = (result as Record<string, any>).code;
      if (typeof code !== "string" || !code) {
        return null;
      }
      const payload = result as Record<string, any>;
      return {
        code,
        token: typeof payload.token === "string" && payload.token ? payload.token : token,
        expiresAt: typeof payload.expiresAt === "number" ? payload.expiresAt : undefined,
        endpoint,
      };
    } catch {
      return null;
    }
  };

  const claimResumeShareCodeDetail = async (
    code: string,
  ): Promise<TResumeShareCodeClaimDetail | null> => {
    const endpoint = ctx.getShareCodeEndpoint();
    if (!endpoint || !code) {
      return {
        code,
        status: "invalid_response",
        endpoint,
        message: "Share-code claim is not configured.",
      };
    }

    try {
      const response = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ operation: "claim-share-code", code }),
      });
      const contentType = response.headers.get("content-type") || "";
      const result = contentType.includes("application/json") ? await response.json() : null;
      if (!response.ok || !result || typeof result !== "object") {
        return {
          code,
          status: "network_error",
          endpoint,
          backend: true,
          message: "Share-code claim failed.",
        };
      }

      const parsed = result as Record<string, any>;
      const remotePolicy =
        parsed.policy && typeof parsed.policy === "object"
          ? (parsed.policy as Record<string, any>)
          : null;
      if (remotePolicy && typeof remotePolicy.code === "string") {
        return {
          code,
          status: remotePolicy.code as TResumeShareCodeClaimDetail["status"],
          endpoint,
          backend: true,
          ...(typeof remotePolicy.reason === "string" ? { message: remotePolicy.reason } : {}),
          ...(typeof remotePolicy.retryAfterSeconds === "number"
            ? { retryAfterSeconds: remotePolicy.retryAfterSeconds }
            : {}),
          ...(typeof remotePolicy.blockedUntil === "number"
            ? { blockedUntil: remotePolicy.blockedUntil }
            : {}),
          ...(typeof remotePolicy.expiresAt === "number"
            ? { expiresAt: remotePolicy.expiresAt }
            : {}),
        };
      }
      const token = typeof parsed.token === "string" ? parsed.token : "";
      if (!token) {
        return {
          code,
          status: "invalid_response",
          endpoint,
          backend: true,
          message: "Invalid share-code claim response.",
        };
      }

      const savedAt = typeof parsed.savedAt === "number" ? parsed.savedAt : Date.now();
      const issuedAt = typeof parsed.issuedAt === "number" ? parsed.issuedAt : savedAt;
      const expiresAt = typeof parsed.expiresAt === "number" ? parsed.expiresAt : undefined;
      const signature = typeof parsed.signature === "string" ? parsed.signature : undefined;
      const signatureVersion =
        typeof parsed.signatureVersion === "string"
          ? parsed.signatureVersion
          : publicConfig.storage?.resumeTokenSignatureVersion;
      const snapshot =
        parsed.snapshot && typeof parsed.snapshot === "object"
          ? (parsed.snapshot as TLocalFormAdminSnapshot)
          : null;

      if (
        !ctx.verifyTokenSignature({
          token,
          formName: publicConfig.name,
          savedAt,
          issuedAt,
          expiresAt,
          snapshot,
          resumeEndpoint: publicConfig.storage?.resumeEndpoint,
          remote: true,
          signature,
          signatureVersion,
        })
      ) {
        return {
          code,
          status: "invalid_signature",
          endpoint,
          token,
          savedAt,
          issuedAt,
          expiresAt,
          signatureVersion,
          signatureValid: false,
          snapshot,
          remote: true,
          backend: true,
          message: "Share-code claim signature verification failed.",
        };
      }

      if (typeof window !== "undefined") {
        window.localStorage.setItem(
          `${resumePrefix}${token}`,
          JSON.stringify({
            version: 1,
            savedAt,
            issuedAt,
            expiresAt,
            snapshot,
            resumeEndpoint: publicConfig.storage?.resumeEndpoint,
            remote: true,
            signature,
            signatureVersion,
          }),
        );
      }

      const lookup: TResumeLookupResult = {
        token,
        savedAt,
        issuedAt,
        expiresAt,
        expired: false,
        resumeEndpoint: publicConfig.storage?.resumeEndpoint,
        remote: true,
        signatureVersion,
        signatureValid: true,
        snapshot,
      };
      return {
        code,
        status: "claimed",
        endpoint,
        token,
        savedAt,
        issuedAt,
        expiresAt,
        signatureVersion,
        signatureValid: true,
        snapshot,
        remote: true,
        backend: true,
        lookup,
      };
    } catch {
      return {
        code,
        status: "network_error",
        endpoint,
        backend: true,
        message: "Share-code claim request failed.",
      };
    }
  };

  const restoreFromShareCodeDetail = async (
    code: string,
  ): Promise<TResumeShareCodeRestoreDetail | null> => {
    const claim = await claimResumeShareCodeDetail(code);
    if (!claim || claim.status !== "claimed" || !claim.lookup?.snapshot) {
      return {
        code,
        status: "claim_failed",
        claim,
        message: claim?.message || "Share-code restore could not continue.",
      };
    }

    if (claim.lookup.snapshot.draft) {
      storageAdapter?.saveDraft(claim.lookup.snapshot.draft);
    } else {
      storageAdapter?.clearDraft();
    }
    storageAdapter?.saveQueue(
      Array.isArray(claim.lookup.snapshot.queue) ? claim.lookup.snapshot.queue : [],
    );
    storageAdapter?.saveDeadLetterQueue(
      Array.isArray(claim.lookup.snapshot.deadLetter) ? claim.lookup.snapshot.deadLetter : [],
    );

    return {
      code,
      status: "restored",
      claim,
      restoredValues: (claim.lookup.snapshot?.draft || {}) as Record<string, any>,
      token: claim.lookup.token,
    };
  };

  const deleteResumeToken = (token: string): boolean => {
    if (typeof window === "undefined") {
      return false;
    }

    const key = `${resumePrefix}${token}`;
    if (!window.localStorage.getItem(key)) {
      return false;
    }

    window.localStorage.removeItem(key);
    return true;
  };

  const invalidateResumeToken = async (token: string): Promise<boolean> => {
    const resumeEndpoint = publicConfig.storage?.resumeEndpoint;
    if (!resumeEndpoint) {
      return deleteResumeToken(token);
    }

    try {
      const response = await fetch(
        `${resumeEndpoint}${resumeEndpoint.includes("?") ? "&" : "?"}token=${encodeURIComponent(token)}`,
        { method: "DELETE" },
      );
      if (!response.ok) {
        return false;
      }

      const contentType = response.headers.get("content-type") || "";
      if (contentType.includes("application/json")) {
        const result = (await response.json()) as Record<string, any>;
        if (result && typeof result === "object" && result.invalidated === false) {
          return false;
        }
      }

      if (typeof window !== "undefined") {
        window.localStorage.removeItem(`${resumePrefix}${token}`);
      }
      return true;
    } catch {
      return false;
    }
  };

  return {
    getResumeStatusSummary,
    listResumeTokens,
    createResumeShareCode: async (token) => {
      const detail = await createResumeShareCodeDetail(token);
      return detail?.code || null;
    },
    createResumeShareCodeDetail,
    claimResumeShareCode: async (code) => {
      const detail = await claimResumeShareCodeDetail(code);
      return detail?.lookup || null;
    },
    claimResumeShareCodeDetail,
    restoreFromShareCode: async (code) => {
      const detail = await restoreFromShareCodeDetail(code);
      return detail?.restoredValues || null;
    },
    restoreFromShareCodeDetail,
    deleteResumeToken,
    invalidateResumeToken,
  };
}
