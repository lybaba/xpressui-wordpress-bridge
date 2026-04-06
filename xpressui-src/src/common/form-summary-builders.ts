import { TFormStepProgress, TFormWorkflowSnapshot } from "./form-steps";
import { TStorageHealth, TQueuedSubmission } from "./form-storage";
import { TResumeTokenInfo } from "./form-persistence";
import { TLocalFormIncidentEntry, mapIncidentEntry } from "./form-queue-query";

export type TLocalFormAdminSnapshot = {
  draft: Record<string, any> | null;
  queue: TQueuedSubmission[];
  deadLetter: TQueuedSubmission[];
};

export type TLocalFormResumeIncidentEntry = {
  token: string;
  savedAt: number;
  issuedAt?: number;
  expiresAt?: number;
  remote: boolean;
  signatureVersion?: string;
  signatureValid?: boolean;
};

export type TLocalFormOperationalSummary = {
  storageHealth: TStorageHealth;
  snapshot: {
    hasDraft: boolean;
    queueLength: number;
    deadLetterLength: number;
  };
  queue: {
    pending: number;
    retrying: number;
    deadLetter: number;
    nextAttemptAt?: number;
  };
  resume: {
    total: number;
    local: number;
    remote: number;
    signed: number;
    invalidSignature: number;
    latestSavedAt?: number;
  };
  workflow: {
    currentStepIndex: number | null;
    stepProgress: TFormStepProgress;
    workflowSnapshot: TFormWorkflowSnapshot;
  };
};

export type TLocalFormIncidentSummary = {
  queue: {
    total: number;
    ready: number;
    scheduled: number;
    overdue: number;
    retrying: number;
    oldestCreatedAt?: number;
    nextAttemptAt?: number;
    samples: TLocalFormIncidentEntry[];
  };
  deadLetter: {
    total: number;
    oldestCreatedAt?: number;
    newestUpdatedAt?: number;
    samples: TLocalFormIncidentEntry[];
  };
  resume: {
    total: number;
    local: number;
    remote: number;
    invalidSignature: number;
    samples: TLocalFormResumeIncidentEntry[];
  };
};

export function buildLocalFormOperationalSummary(input: {
  storageHealth: TStorageHealth;
  snapshot: TLocalFormAdminSnapshot;
  resumeTokens: TResumeTokenInfo[];
  workflow: {
    currentStepIndex: number | null;
    stepProgress: TFormStepProgress;
    workflowSnapshot: TFormWorkflowSnapshot;
  };
}): TLocalFormOperationalSummary {
  const queue = input.snapshot.queue || [];
  const deadLetter = input.snapshot.deadLetter || [];
  const nextAttemptAt = queue.reduce<number | undefined>((current, entry) => {
    if (typeof current !== "number") {
      return entry.nextAttemptAt;
    }
    return Math.min(current, entry.nextAttemptAt);
  }, undefined);

  return {
    storageHealth: input.storageHealth,
    snapshot: {
      hasDraft: Boolean(input.snapshot.draft && Object.keys(input.snapshot.draft).length),
      queueLength: queue.length,
      deadLetterLength: deadLetter.length,
    },
    queue: {
      pending: queue.length,
      retrying: queue.filter((entry) => entry.attempts > 0).length,
      deadLetter: deadLetter.length,
      ...(typeof nextAttemptAt === "number" ? { nextAttemptAt } : {}),
    },
    resume: {
      total: input.resumeTokens.length,
      local: input.resumeTokens.filter((entry) => !entry.remote).length,
      remote: input.resumeTokens.filter((entry) => entry.remote).length,
      signed: input.resumeTokens.filter((entry) => Boolean(entry.signatureVersion)).length,
      invalidSignature: input.resumeTokens.filter((entry) => entry.signatureValid === false).length,
      ...(input.resumeTokens[0]?.savedAt ? { latestSavedAt: input.resumeTokens[0].savedAt } : {}),
    },
    workflow: input.workflow,
  };
}

export function buildLocalFormIncidentSummary(
  input: {
    snapshot: TLocalFormAdminSnapshot;
    resumeTokens: TResumeTokenInfo[];
  },
  limit = 5,
  now = Date.now(),
): TLocalFormIncidentSummary {
  const queue = input.snapshot.queue || [];
  const deadLetter = input.snapshot.deadLetter || [];
  const safeLimit = Number.isInteger(limit) && limit > 0 ? limit : 5;
  const oldestQueueCreatedAt = queue.reduce<number | undefined>((current, entry) => {
    if (typeof current !== "number") {
      return entry.createdAt;
    }
    return Math.min(current, entry.createdAt);
  }, undefined);
  const nextQueueAttemptAt = queue.reduce<number | undefined>((current, entry) => {
    if (typeof current !== "number") {
      return entry.nextAttemptAt;
    }
    return Math.min(current, entry.nextAttemptAt);
  }, undefined);
  const oldestDeadLetterCreatedAt = deadLetter.reduce<number | undefined>((current, entry) => {
    if (typeof current !== "number") {
      return entry.createdAt;
    }
    return Math.min(current, entry.createdAt);
  }, undefined);
  const newestDeadLetterUpdatedAt = deadLetter.reduce<number | undefined>((current, entry) => {
    if (typeof current !== "number") {
      return entry.updatedAt;
    }
    return Math.max(current, entry.updatedAt);
  }, undefined);

  return {
    queue: {
      total: queue.length,
      ready: queue.filter((entry) => entry.nextAttemptAt <= now).length,
      scheduled: queue.filter((entry) => entry.nextAttemptAt > now).length,
      overdue: queue.filter((entry) => entry.nextAttemptAt < now).length,
      retrying: queue.filter((entry) => entry.attempts > 0).length,
      ...(typeof oldestQueueCreatedAt === "number" ? { oldestCreatedAt: oldestQueueCreatedAt } : {}),
      ...(typeof nextQueueAttemptAt === "number" ? { nextAttemptAt: nextQueueAttemptAt } : {}),
      samples: [...queue]
        .sort((left, right) => right.updatedAt - left.updatedAt)
        .slice(0, safeLimit)
        .map(mapIncidentEntry),
    },
    deadLetter: {
      total: deadLetter.length,
      ...(typeof oldestDeadLetterCreatedAt === "number"
        ? { oldestCreatedAt: oldestDeadLetterCreatedAt }
        : {}),
      ...(typeof newestDeadLetterUpdatedAt === "number"
        ? { newestUpdatedAt: newestDeadLetterUpdatedAt }
        : {}),
      samples: [...deadLetter]
        .sort((left, right) => right.updatedAt - left.updatedAt)
        .slice(0, safeLimit)
        .map(mapIncidentEntry),
    },
    resume: {
      total: input.resumeTokens.length,
      local: input.resumeTokens.filter((entry) => !entry.remote).length,
      remote: input.resumeTokens.filter((entry) => entry.remote).length,
      invalidSignature: input.resumeTokens.filter((entry) => entry.signatureValid === false).length,
      samples: [...input.resumeTokens]
        .sort((left, right) => right.savedAt - left.savedAt)
        .slice(0, safeLimit)
        .map((entry) => ({
          token: entry.token,
          savedAt: entry.savedAt,
          ...(typeof entry.issuedAt === "number" ? { issuedAt: entry.issuedAt } : {}),
          ...(typeof entry.expiresAt === "number" ? { expiresAt: entry.expiresAt } : {}),
          remote: Boolean(entry.remote),
          ...(entry.signatureVersion ? { signatureVersion: entry.signatureVersion } : {}),
          ...(typeof entry.signatureValid === "boolean"
            ? { signatureValid: entry.signatureValid }
            : {}),
        })),
    },
  };
}
