import { TFormSubmitRequest } from "./TFormConfig";

export type TUploadRetryStage = "presign" | "upload";

export type TUploadRetryPolicy = {
  maxAttempts: number;
  baseDelayMs: number;
  maxDelayMs: number;
  jitter: boolean;
};

export type TUploadRetryFailureMeta = {
  stage: TUploadRetryStage;
  attempt: number;
  maxAttempts: number;
  retryable: boolean;
  status?: number;
  reason: string;
};

export function sleep(ms: number): Promise<void> {
  if (ms <= 0) return Promise.resolve();
  return new Promise((resolve) => { setTimeout(resolve, ms); });
}

export function normalizeRetryPolicy(submitConfig: TFormSubmitRequest): TUploadRetryPolicy {
  return {
    maxAttempts: Math.max(1, Number(submitConfig.uploadRetryMaxAttempts ?? 3)),
    baseDelayMs: Math.max(0, Number(submitConfig.uploadRetryBaseDelayMs ?? 500)),
    maxDelayMs: Math.max(0, Number(submitConfig.uploadRetryMaxDelayMs ?? 5_000)),
    jitter: Boolean(submitConfig.uploadRetryJitter),
  };
}

export function shouldRetryUploadError(error: any): boolean {
  const status = Number(error?.response?.status);
  if (Number.isFinite(status) && status > 0) {
    return status === 408 || status === 425 || status === 429 || status >= 500;
  }
  return true;
}

export function getUploadErrorReason(error: any): string {
  return error?.message || error?.result?.error || error?.result?.message || "upload_retry";
}

export function toUploadRetryFailureMeta(
  stage: TUploadRetryStage,
  attempt: number,
  maxAttempts: number,
  retryable: boolean,
  error: any,
): TUploadRetryFailureMeta {
  return {
    stage,
    attempt,
    maxAttempts,
    retryable,
    status: error?.response?.status,
    reason: getUploadErrorReason(error),
  };
}

export function getRetryDelayMs(policy: TUploadRetryPolicy, attempt: number): number {
  const raw = policy.baseDelayMs * Math.pow(2, Math.max(0, attempt - 1));
  const clamped = Math.min(raw, policy.maxDelayMs);
  if (!policy.jitter) return clamped;
  return clamped + Math.floor(Math.random() * Math.min(250, clamped + 1));
}
