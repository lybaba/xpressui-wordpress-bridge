import TFieldConfig from "./TFieldConfig";
import TFormConfig, { TFormSubmitRequest } from "./TFormConfig";
import {
  TResumeLookupResult,
  TResumeShareCodeClaimDetail,
  TResumeShareCodeRestoreDetail,
  TResumeShareCodeInfo,
  TResumeStatusSummary,
  TResumeTokenInfo,
} from "./form-persistence";
import { FormStepRuntime, TFormStepProgress, TFormWorkflowSnapshot } from "./form-steps";
import { createStorageAdapter } from "./storage-adapter-factory";
import {
  TStorageHealth,
  TFormStorageAdapter,
  TQueuedSubmission,
} from "./form-storage";
import { submitFormValues } from "./form-submit";
import { validatePublicFormConfig } from "./public-schema";
import { applyQuery, TLocalQueueQuery, TLocalFormIncidentEntry } from "./form-queue-query";
import {
  buildLocalFormOperationalSummary,
  buildLocalFormIncidentSummary,
  TLocalFormAdminSnapshot,
  TLocalFormOperationalSummary,
  TLocalFormIncidentSummary,
  TLocalFormResumeIncidentEntry,
} from "./form-summary-builders";
import { buildAdminResumeHandlers } from "./form-admin-resume";

export type {
  TLocalFormAdminSnapshot,
  TLocalQueueQuery,
  TLocalFormIncidentEntry,
  TLocalFormResumeIncidentEntry,
  TLocalFormOperationalSummary,
  TLocalFormIncidentSummary,
};
export { buildLocalFormOperationalSummary, buildLocalFormIncidentSummary };

async function submitNow(
  values: Record<string, any>,
  submitConfig: TFormSubmitRequest,
  fieldMap?: Record<string, TFieldConfig>,
): Promise<{ response: Response; result: any }> {
  return submitFormValues(values, submitConfig, fieldMap);
}

function getFieldMap(formConfig: TFormConfig): Record<string, TFieldConfig> {
  const fieldMap: Record<string, TFieldConfig> = {};
  Object.values(formConfig.sections || {})
    .flat()
    .forEach((field) => {
      fieldMap[field.name] = field;
    });
  return fieldMap;
}

export type TLocalFormAdminImportMode = "replace" | "merge";

export type TLocalFormAdmin = {
  getSnapshot(): TLocalFormAdminSnapshot;
  getSnapshotAsync(): Promise<TLocalFormAdminSnapshot>;
  exportSnapshot(): TLocalFormAdminSnapshot;
  exportSnapshotAsync(): Promise<TLocalFormAdminSnapshot>;
  importSnapshot(
    snapshot: TLocalFormAdminSnapshot,
    mode?: TLocalFormAdminImportMode,
  ): TLocalFormAdminSnapshot;
  getStorageHealth(): TStorageHealth;
  getResumeStatusSummary(): TResumeStatusSummary;
  listResumeTokens(): TResumeTokenInfo[];
  createResumeShareCode(token: string): Promise<string | null>;
  createResumeShareCodeDetail(token: string): Promise<TResumeShareCodeInfo | null>;
  claimResumeShareCodeDetail(code: string): Promise<TResumeShareCodeClaimDetail | null>;
  claimResumeShareCode(code: string): Promise<TResumeLookupResult | null>;
  restoreFromShareCodeDetail(code: string): Promise<TResumeShareCodeRestoreDetail | null>;
  restoreFromShareCode(code: string): Promise<Record<string, any> | null>;
  deleteResumeToken(token: string): boolean;
  invalidateResumeToken(token: string): Promise<boolean>;
  listQueue(query?: TLocalQueueQuery): TQueuedSubmission[];
  listDeadLetter(query?: TLocalQueueQuery): TQueuedSubmission[];
  getCurrentStepIndex(): number | null;
  getWorkflowSnapshot(values?: Record<string, any>): TFormWorkflowSnapshot;
  getStepProgress(values?: Record<string, any>): TFormStepProgress;
  getWorkflowContext(values?: Record<string, any>): {
    currentStepIndex: number | null;
    stepProgress: TFormStepProgress;
    workflowSnapshot: TFormWorkflowSnapshot;
  };
  getOperationalSummary(values?: Record<string, any>): TLocalFormOperationalSummary;
  getIncidentSummary(limit?: number): TLocalFormIncidentSummary;
  clearDraft(): void;
  clearQueue(): void;
  clearDeadLetter(): void;
  purgeQueue(query?: TLocalQueueQuery): number;
  purgeDeadLetter(query?: TLocalQueueQuery): number;
  requeueDeadLetterEntry(entryId: string): boolean;
  requeueDeadLetterEntries(query?: TLocalQueueQuery): number;
  replayDeadLetterEntry(entryId: string): Promise<boolean>;
  replayDeadLetterEntries(
    query?: TLocalQueueQuery,
  ): Promise<{ succeeded: number; failed: number }>;
};

export function createLocalFormAdmin(formConfig: TFormConfig): TLocalFormAdmin {
  const publicConfig = validatePublicFormConfig(formConfig as unknown as Record<string, any>);
  const storageAdapter: TFormStorageAdapter | null = createStorageAdapter(publicConfig);
  const fieldMap = getFieldMap(publicConfig);
  const resumePrefix = `xpressui:resume:${publicConfig.name}:`;
  const steps = new FormStepRuntime();
  steps.setFormConfig(publicConfig);

  const getResumeTokenTtlMs = (): number | null =>
    typeof publicConfig.storage?.resumeTokenTtlDays === "number" &&
    publicConfig.storage.resumeTokenTtlDays > 0
      ? publicConfig.storage.resumeTokenTtlDays * 24 * 60 * 60 * 1000
      : null;

  const getShareCodeEndpoint = (): string | undefined =>
    publicConfig.storage?.shareCodeEndpoint || publicConfig.storage?.resumeEndpoint;

  const verifyTokenSignature = (payload: Record<string, any>): boolean => {
    const verifier = publicConfig.storage?.verifyResumeToken;
    if (!verifier) return true;
    if (!payload.signature) return false;
    try {
      return Boolean(verifier(payload as any));
    } catch {
      return false;
    }
  };

  const resume = buildAdminResumeHandlers({
    publicConfig,
    resumePrefix,
    storageAdapter,
    getShareCodeEndpoint,
    getResumeTokenTtlMs,
    verifyTokenSignature,
  });

  const { listResumeTokens } = resume;

  const getSnapshot = (): TLocalFormAdminSnapshot => ({
    draft: storageAdapter?.loadDraft() || null,
    queue: storageAdapter?.loadQueue() || [],
    deadLetter: storageAdapter?.loadDeadLetterQueue() || [],
  });

  const getCurrentStepStorageKey = (): string | null => {
    const baseKey = publicConfig.storage?.key;
    if (!baseKey) {
      return null;
    }
    return `${baseKey}:step`;
  };

  const getCurrentStepIndex = (): number | null => {
    if (typeof window === "undefined") {
      return null;
    }
    const key = getCurrentStepStorageKey();
    if (!key) {
      return null;
    }
    const raw = window.localStorage.getItem(key);
    if (!raw) {
      return null;
    }
    const parsed = Number(raw);
    if (!Number.isInteger(parsed) || parsed < 0) {
      return null;
    }
    return parsed;
  };

  const getWorkflowSnapshot = (values?: Record<string, any>): TFormWorkflowSnapshot => {
    const nextValues = values || storageAdapter?.loadDraft() || {};
    const currentStepIndex = getCurrentStepIndex();
    if (
      typeof currentStepIndex === "number"
      && currentStepIndex >= 0
      && currentStepIndex < steps.getStepNames().length
    ) {
      steps.setCurrentStepIndex(currentStepIndex);
    }
    return steps.getWorkflowSnapshot(nextValues);
  };

  const getStepProgress = (): TFormStepProgress => {
    const currentStepIndex = getCurrentStepIndex();
    if (
      typeof currentStepIndex === "number"
      && currentStepIndex >= 0
      && currentStepIndex < steps.getStepNames().length
    ) {
      steps.setCurrentStepIndex(currentStepIndex);
    }
    return steps.getStepProgress();
  };

  const getWorkflowContext = (values?: Record<string, any>) => {
    return {
      currentStepIndex: getCurrentStepIndex(),
      stepProgress: getStepProgress(),
      workflowSnapshot: getWorkflowSnapshot(values),
    };
  };

  const getOperationalSummary = (values?: Record<string, any>): TLocalFormOperationalSummary => {
    return buildLocalFormOperationalSummary({
      storageHealth:
        storageAdapter?.getHealth() || {
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
        },
      snapshot: getSnapshot(),
      resumeTokens: listResumeTokens(),
      workflow: getWorkflowContext(values),
    });
  };

  const getIncidentSummary = (limit = 5): TLocalFormIncidentSummary => {
    return buildLocalFormIncidentSummary({
      snapshot: getSnapshot(),
      resumeTokens: listResumeTokens(),
    }, limit);
  };

  const getSnapshotAsync = async (): Promise<TLocalFormAdminSnapshot> => {
    if (storageAdapter?.hydrate) {
      const hydrated = await storageAdapter.hydrate();
      return hydrated.snapshot;
    }

    return getSnapshot();
  };

  return {
    ...resume,
    getSnapshot,
    getSnapshotAsync,
    exportSnapshot() {
      return getSnapshot();
    },
    async exportSnapshotAsync() {
      return getSnapshotAsync();
    },
    importSnapshot(snapshot, mode = "replace") {
      if (!storageAdapter) {
        return getSnapshot();
      }

      const safeSnapshot: TLocalFormAdminSnapshot = {
        draft: snapshot?.draft && typeof snapshot.draft === "object" ? snapshot.draft : null,
        queue: Array.isArray(snapshot?.queue) ? snapshot.queue : [],
        deadLetter: Array.isArray(snapshot?.deadLetter) ? snapshot.deadLetter : [],
      };

      const nextDraft =
        mode === "merge"
          ? {
              ...(storageAdapter.loadDraft() || {}),
              ...(safeSnapshot.draft || {}),
            }
          : safeSnapshot.draft;

      const mergeEntries = (
        current: TQueuedSubmission[],
        incoming: TQueuedSubmission[],
      ): TQueuedSubmission[] => {
        const merged = [...current];
        const ids = new Set(current.map((entry) => entry.id));
        incoming.forEach((entry) => {
          if (!ids.has(entry.id)) {
            merged.push(entry);
            ids.add(entry.id);
          }
        });
        return merged;
      };

      const nextQueue =
        mode === "merge"
          ? mergeEntries(storageAdapter.loadQueue(), safeSnapshot.queue)
          : safeSnapshot.queue;
      const nextDeadLetter =
        mode === "merge"
          ? mergeEntries(storageAdapter.loadDeadLetterQueue(), safeSnapshot.deadLetter)
          : safeSnapshot.deadLetter;

      if (nextDraft) {
        storageAdapter.saveDraft(nextDraft);
      } else {
        storageAdapter.clearDraft();
      }
      storageAdapter.saveQueue(nextQueue);
      storageAdapter.saveDeadLetterQueue(nextDeadLetter);

      return getSnapshot();
    },
    getStorageHealth() {
      return (
        storageAdapter?.getHealth() || {
          adapter: "local-storage" as const,
          encryptionEnabled: false,
          hasDraft: false,
          queueLength: 0,
          deadLetterLength: 0,
          totalEntries: 0,
          retentionMs: { draft: null, queue: null, deadLetter: null },
        }
      );
    },
    listQueue(query) {
      return applyQuery(storageAdapter?.loadQueue() || [], query);
    },
    listDeadLetter(query) {
      return applyQuery(storageAdapter?.loadDeadLetterQueue() || [], query);
    },
    getCurrentStepIndex,
    getWorkflowSnapshot,
    getStepProgress,
    getWorkflowContext,
    getOperationalSummary,
    getIncidentSummary,
    clearDraft() {
      storageAdapter?.clearDraft();
    },
    clearQueue() {
      storageAdapter?.clearQueue();
    },
    clearDeadLetter() {
      storageAdapter?.clearDeadLetterQueue();
    },
    purgeQueue(query) {
      if (!storageAdapter) {
        return 0;
      }

      const queue = storageAdapter.loadQueue();
      const selected = applyQuery(queue, query);
      if (!selected.length) {
        return 0;
      }

      const selectedIds = new Set(selected.map((entry) => entry.id));
      storageAdapter.saveQueue(queue.filter((entry) => !selectedIds.has(entry.id)));
      return selected.length;
    },
    purgeDeadLetter(query) {
      if (!storageAdapter) {
        return 0;
      }

      const deadLetter = storageAdapter.loadDeadLetterQueue();
      const selected = applyQuery(deadLetter, query);
      if (!selected.length) {
        return 0;
      }

      const selectedIds = new Set(selected.map((entry) => entry.id));
      storageAdapter.saveDeadLetterQueue(
        deadLetter.filter((entry) => !selectedIds.has(entry.id))
      );
      return selected.length;
    },
    requeueDeadLetterEntry(entryId: string) {
      if (!storageAdapter) {
        return false;
      }

      const entry = storageAdapter.removeDeadLetterEntry(entryId);
      if (!entry) {
        return false;
      }

      storageAdapter.enqueueSubmission(entry.values);
      return true;
    },
    requeueDeadLetterEntries(query) {
      if (!storageAdapter) {
        return 0;
      }

      const deadLetter = storageAdapter.loadDeadLetterQueue();
      const selected = applyQuery(deadLetter, query);
      if (!selected.length) {
        return 0;
      }

      const selectedIds = new Set(selected.map((entry) => entry.id));
      storageAdapter.saveDeadLetterQueue(
        deadLetter.filter((entry) => !selectedIds.has(entry.id))
      );
      selected.forEach((entry) => {
        storageAdapter.enqueueSubmission(entry.values);
      });
      return selected.length;
    },
    async replayDeadLetterEntry(entryId: string) {
      if (!storageAdapter || !publicConfig.submit?.endpoint) {
        return false;
      }

      const entry = storageAdapter.removeDeadLetterEntry(entryId);
      if (!entry) {
        return false;
      }

      try {
        await submitNow(entry.values, publicConfig.submit, fieldMap);
        return true;
      } catch (error: any) {
        storageAdapter.enqueueDeadLetter({
          ...entry,
          attempts: entry.attempts + 1,
          updatedAt: Date.now(),
          nextAttemptAt: Date.now(),
          lastError: error?.result?.message || error?.message || "replay_error",
        });
        return false;
      }
    },
    async replayDeadLetterEntries(query) {
      if (!storageAdapter || !publicConfig.submit?.endpoint) {
        return { succeeded: 0, failed: 0 };
      }

      const deadLetter = storageAdapter.loadDeadLetterQueue();
      const selected = applyQuery(deadLetter, query);
      if (!selected.length) {
        return { succeeded: 0, failed: 0 };
      }

      let succeeded = 0;
      let failed = 0;

      for (const selectedEntry of selected) {
        const entry = storageAdapter.removeDeadLetterEntry(selectedEntry.id);
        if (!entry) {
          continue;
        }

        try {
          await submitNow(entry.values, publicConfig.submit, fieldMap);
          succeeded += 1;
        } catch (error: any) {
          failed += 1;
          storageAdapter.enqueueDeadLetter({
            ...entry,
            attempts: entry.attempts + 1,
            updatedAt: Date.now(),
            nextAttemptAt: Date.now(),
            lastError: error?.result?.message || error?.message || "replay_error",
          });
        }
      }

      return { succeeded, failed };
    },
  };
}
