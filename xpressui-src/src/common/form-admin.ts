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
import {
  createStorageAdapter,
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
    typeof publicConfig.storage?.resumeTokenTtlDays === "number" && publicConfig.storage.resumeTokenTtlDays > 0
      ? publicConfig.storage.resumeTokenTtlDays * 24 * 60 * 60 * 1000
      : null;

  const getShareCodeEndpoint = (): string | undefined =>
    publicConfig.storage?.shareCodeEndpoint || publicConfig.storage?.resumeEndpoint;

  const verifyTokenSignature = (payload: Record<string, any>): boolean => {
    const verifier = publicConfig.storage?.verifyResumeToken;
    if (!verifier) {
      return true;
    }
    if (!payload.signature) {
      return false;
    }
    try {
      return Boolean(verifier(payload as any));
    } catch {
      return false;
    }
  };

  const listResumeTokens = (): TResumeTokenInfo[] => {
    if (typeof window === "undefined") {
      return [];
    }

    const ttlMs = getResumeTokenTtlMs();
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
                signatureValid: verifyTokenSignature({
                  token: key.slice(resumePrefix.length),
                  formName: publicConfig.name,
                  savedAt,
                  issuedAt: typeof parsed.issuedAt === "number" ? parsed.issuedAt : savedAt,
                  expiresAt: typeof parsed.expiresAt === "number" ? parsed.expiresAt : undefined,
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
        }
      );
    },
    getResumeStatusSummary() {
      const resumeTokens = listResumeTokens();
      return {
        configured: Boolean(publicConfig.storage?.resumeEndpoint || getShareCodeEndpoint()),
        ...(publicConfig.storage?.resumeEndpoint
          ? { resumeEndpoint: publicConfig.storage.resumeEndpoint }
          : {}),
        ...(getShareCodeEndpoint() ? { shareCodeEndpoint: getShareCodeEndpoint() } : {}),
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
    },
    listResumeTokens,
    async createResumeShareCode(token) {
      const detail = await this.createResumeShareCodeDetail(token);
      return detail?.code || null;
    },
    async createResumeShareCodeDetail(token) {
      const endpoint = getShareCodeEndpoint();
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
          }),
        });
        const contentType = response.headers.get("content-type") || "";
        const result = contentType.includes("application/json")
          ? await response.json()
          : null;
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
    },
    async claimResumeShareCode(code) {
      const detail = await this.claimResumeShareCodeDetail(code);
      return detail?.lookup || null;
    },
    async claimResumeShareCodeDetail(code) {
      const endpoint = getShareCodeEndpoint();
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
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            operation: "claim-share-code",
            code,
          }),
        });
        const contentType = response.headers.get("content-type") || "";
        const result = contentType.includes("application/json")
          ? await response.json()
          : null;
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
        const remotePolicy = parsed.policy && typeof parsed.policy === "object"
          ? parsed.policy as Record<string, any>
          : null;
        if (remotePolicy && typeof remotePolicy.code === "string") {
          return {
            code,
            status: remotePolicy.code as TResumeShareCodeClaimDetail["status"],
            endpoint,
            backend: true,
            ...(typeof remotePolicy.reason === "string" ? { message: remotePolicy.reason } : {}),
            ...(typeof remotePolicy.retryAfterSeconds === "number" ? { retryAfterSeconds: remotePolicy.retryAfterSeconds } : {}),
            ...(typeof remotePolicy.blockedUntil === "number" ? { blockedUntil: remotePolicy.blockedUntil } : {}),
            ...(typeof remotePolicy.expiresAt === "number" ? { expiresAt: remotePolicy.expiresAt } : {}),
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
            ? parsed.snapshot as TLocalFormAdminSnapshot
            : null;

        if (
          !verifyTokenSignature({
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

        const lookup = {
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
    },
    async restoreFromShareCode(code) {
      const detail = await this.restoreFromShareCodeDetail(code);
      return detail?.restoredValues || null;
    },
    async restoreFromShareCodeDetail(code) {
      const claim = await this.claimResumeShareCodeDetail(code);
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
      storageAdapter?.saveQueue(Array.isArray(claim.lookup.snapshot.queue) ? claim.lookup.snapshot.queue : []);
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
    },
    deleteResumeToken(token) {
      if (typeof window === "undefined") {
        return false;
      }

      const key = `${resumePrefix}${token}`;
      if (!window.localStorage.getItem(key)) {
        return false;
      }

      window.localStorage.removeItem(key);
      return true;
    },
    async invalidateResumeToken(token) {
      const resumeEndpoint = publicConfig.storage?.resumeEndpoint;
      if (!resumeEndpoint) {
        return this.deleteResumeToken(token);
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
          const result = await response.json() as Record<string, any>;
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
