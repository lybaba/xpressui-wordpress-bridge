import { TQueuedSubmission } from "./form-storage";

export type TLocalQueueQuery = {
  minAttempts?: number;
  maxAttempts?: number;
  search?: string;
  minAgeMs?: number;
  maxAgeMs?: number;
  nextAttemptBefore?: number;
  nextAttemptAfter?: number;
  errorText?: string;
  sortBy?: "createdAt" | "updatedAt" | "attempts" | "nextAttemptAt";
  sortOrder?: "asc" | "desc";
  limit?: number;
};

export type TLocalFormIncidentEntry = {
  id: string;
  attempts: number;
  createdAt: number;
  updatedAt: number;
  nextAttemptAt: number;
  lastError?: string;
};

export function mapIncidentEntry(entry: TQueuedSubmission): TLocalFormIncidentEntry {
  return {
    id: entry.id,
    attempts: entry.attempts,
    createdAt: entry.createdAt,
    updatedAt: entry.updatedAt,
    nextAttemptAt: entry.nextAttemptAt,
    ...(entry.lastError ? { lastError: entry.lastError } : {}),
  };
}

function matchesQuery(entry: TQueuedSubmission, query?: TLocalQueueQuery): boolean {
  if (!query) {
    return true;
  }

  if (query.minAttempts !== undefined && entry.attempts < query.minAttempts) {
    return false;
  }

  if (query.maxAttempts !== undefined && entry.attempts > query.maxAttempts) {
    return false;
  }

  if (query.search) {
    const haystack = JSON.stringify(entry.values).toLowerCase();
    if (!haystack.includes(query.search.toLowerCase())) {
      return false;
    }
  }

  const ageMs = Date.now() - entry.createdAt;
  if (query.minAgeMs !== undefined && ageMs < query.minAgeMs) {
    return false;
  }

  if (query.maxAgeMs !== undefined && ageMs > query.maxAgeMs) {
    return false;
  }

  if (query.nextAttemptBefore !== undefined && entry.nextAttemptAt >= query.nextAttemptBefore) {
    return false;
  }

  if (query.nextAttemptAfter !== undefined && entry.nextAttemptAt <= query.nextAttemptAfter) {
    return false;
  }

  if (query.errorText) {
    const errorText = (entry.lastError || "").toLowerCase();
    if (!errorText.includes(query.errorText.toLowerCase())) {
      return false;
    }
  }

  return true;
}

export function applyQuery(
  entries: TQueuedSubmission[],
  query?: TLocalQueueQuery,
): TQueuedSubmission[] {
  const filtered = entries.filter((entry) => matchesQuery(entry, query));
  const sortBy = query?.sortBy || "createdAt";
  const sortOrder = query?.sortOrder || "desc";
  const sorted = [...filtered].sort((a, b) => {
    const left = a[sortBy];
    const right = b[sortBy];
    const result = left === right ? 0 : left < right ? -1 : 1;
    return sortOrder === "asc" ? result : -result;
  });

  if (query?.limit !== undefined) {
    return sorted.slice(0, query.limit);
  }

  return sorted;
}
