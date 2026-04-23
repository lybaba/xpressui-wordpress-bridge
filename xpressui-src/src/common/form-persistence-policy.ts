export const FORM_PERSISTENCE_DEFAULT_MAX_RETRY_ATTEMPTS = 3;

export function normalizeShareCodeKey(code: string): string {
  return String(code || "").trim().toUpperCase();
}

export function getRetryDelayMs(attempts: number): number {
  const baseDelayMs = 1000;
  const maxDelayMs = 30000;
  return Math.min(baseDelayMs * Math.pow(2, Math.max(0, attempts - 1)), maxDelayMs);
}
