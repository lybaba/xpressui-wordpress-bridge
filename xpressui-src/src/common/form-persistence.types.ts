import type { TQueuedSubmission, TStorageHealth } from "./form-storage";
import type {
  TRemoteResumeContractVersion,
  TRemoteResumePolicy,
} from "./resume-contract";

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
