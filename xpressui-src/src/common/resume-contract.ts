export const REMOTE_RESUME_CONTRACT_VERSION = "resume-contract-v1" as const;

export type TRemoteResumeContractVersion = typeof REMOTE_RESUME_CONTRACT_VERSION;

export type TRemoteResumePolicyCode =
  | "rate_limited"
  | "blocked"
  | "expired"
  | "invalid_signature"
  | "not_found";

export type TRemoteResumePolicy = {
  code: TRemoteResumePolicyCode;
  reason?: string;
  retryAfterSeconds?: number;
  blockedUntil?: number;
  expiresAt?: number;
};

export type TResumeShareCodeClaimPresentation = {
  tone: "success" | "warning" | "error" | "info";
  label: string;
  retryable: boolean;
  restorable: boolean;
};

export function isRemoteResumePolicy(value: unknown): value is TRemoteResumePolicy {
  if (!value || typeof value !== "object") {
    return false;
  }

  const policy = value as Record<string, any>;
  return (
    ["rate_limited", "blocked", "expired", "invalid_signature", "not_found"].includes(policy.code) &&
    (policy.reason === undefined || typeof policy.reason === "string") &&
    (policy.retryAfterSeconds === undefined || typeof policy.retryAfterSeconds === "number") &&
    (policy.blockedUntil === undefined || typeof policy.blockedUntil === "number") &&
    (policy.expiresAt === undefined || typeof policy.expiresAt === "number")
  );
}

export function getRemoteResumePolicy(value: unknown): TRemoteResumePolicy | null {
  if (!value || typeof value !== "object") {
    return null;
  }

  const policy = (value as Record<string, any>).policy;
  return isRemoteResumePolicy(policy) ? policy : null;
}

export function getResumeShareCodeClaimPresentation(
  detail?: {
    status?: string;
  } | null,
): TResumeShareCodeClaimPresentation {
  switch (detail?.status) {
    case "claimed":
      return { tone: "success", label: "Ready to restore", retryable: false, restorable: true };
    case "throttled":
      return { tone: "warning", label: "Temporarily throttled", retryable: true, restorable: false };
    case "blocked":
      return { tone: "warning", label: "Temporarily blocked", retryable: true, restorable: false };
    case "expired":
      return { tone: "error", label: "Expired code", retryable: false, restorable: false };
    case "invalid_signature":
      return { tone: "error", label: "Invalid signature", retryable: false, restorable: false };
    case "not_found":
      return { tone: "error", label: "Code not found", retryable: false, restorable: false };
    case "network_error":
      return { tone: "warning", label: "Network error", retryable: true, restorable: false };
    case "invalid_response":
      return { tone: "error", label: "Invalid response", retryable: false, restorable: false };
    default:
      return { tone: "info", label: "Idle", retryable: false, restorable: false };
  }
}
