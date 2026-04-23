import TFormConfig, { TFormSubmitRequest } from "./TFormConfig";
import { isFileFieldType } from "./field";
import {
  FORM_PERSISTENCE_DEFAULT_MAX_RETRY_ATTEMPTS,
  getRetryDelayMs as getDefaultRetryDelayMs,
} from "./form-persistence-policy";
import { createStorageAdapter } from "./storage-adapter-factory";
import {
  getRestorableStorageValues,
  TStorageHydrationResult,
  TFormStorageAdapter,
} from "./form-storage";
import type { TQueuedSubmission } from "./form-storage";
import type {
  TFormQueueState,
  TFormStorageHealth,
  TFormStorageSnapshot,
  TRemoteResumeCreateRequest,
  TRemoteResumeCreateResponse,
  TRemoteResumeInvalidateResponse,
  TRemoteResumeLookupResponse,
  TRemoteResumeOperation,
  TRemoteResumeShareCodeClaimRequest,
  TRemoteResumeShareCodeClaimResponse,
  TRemoteResumeShareCodeCreateRequest,
  TRemoteResumeShareCodeCreateResponse,
  TRemoteShareCodeOperation,
  TResumeLookupResult,
  TResumeShareCodeClaimDetail,
  TResumeShareCodeClaimStatus,
  TResumeShareCodeInfo,
  TResumeShareCodeRestoreDetail,
  TResumeStatusSummary,
  TResumeTokenInfo,
} from "./form-persistence.types";
import { FormResumeRuntime } from "./form-persistence-resume";

export type {
  TFormQueueState,
  TFormStorageHealth,
  TFormStorageSnapshot,
  TRemoteResumeCreateRequest,
  TRemoteResumeCreateResponse,
  TRemoteResumeInvalidateResponse,
  TRemoteResumeLookupResponse,
  TRemoteResumeOperation,
  TRemoteResumeShareCodeClaimRequest,
  TRemoteResumeShareCodeClaimResponse,
  TRemoteResumeShareCodeCreateRequest,
  TRemoteResumeShareCodeCreateResponse,
  TRemoteShareCodeOperation,
  TResumeLookupResult,
  TResumeShareCodeClaimDetail,
  TResumeShareCodeClaimStatus,
  TResumeShareCodeInfo,
  TResumeShareCodeRestoreDetail,
  TResumeStatusSummary,
  TResumeTokenInfo,
} from "./form-persistence.types";

type TFormPersistenceRuntimeOptions = {
  getFormConfig(): TFormConfig | null;
  getValues(): Record<string, any>;
  getCurrentStepIndex?(): number | null;
  setCurrentStepIndex?(index: number): void;
  emitEvent(eventName: string, detail: Record<string, any>): boolean;
  submitValues(
    values: Record<string, any>,
    submitConfig: TFormSubmitRequest,
  ): Promise<{ response: Response; result: any }>;
};

export class FormPersistenceRuntime {
  options: TFormPersistenceRuntimeOptions;
  storageAdapter: TFormStorageAdapter | null;
  draftSaveTimer: number | null;
  syncInFlight: boolean;
  onlineHandler: (() => void) | null;
  resumeRuntime: FormResumeRuntime;

  constructor(options: TFormPersistenceRuntimeOptions) {
    this.options = options;
    this.storageAdapter = null;
    this.draftSaveTimer = null;
    this.syncInFlight = false;
    this.onlineHandler = null;
    this.resumeRuntime = new FormResumeRuntime({
      getFormConfig: () => options.getFormConfig(),
      getValues: () => options.getValues(),
      emitEvent: (name, detail) => options.emitEvent(name, detail),
      getStorageSnapshot: () => this.getStorageSnapshot(),
      applyResumeSnapshot: (snapshot) => this.applyResumeSnapshot(snapshot),
      createEventDetail: (...args) => this.createEventDetail(...args),
    });
  }

  setFormConfig(formConfig: TFormConfig | null): void {
    this.storageAdapter = createStorageAdapter(formConfig);
  }

  disconnect(): void {
    if (this.draftSaveTimer !== null) {
      window.clearTimeout(this.draftSaveTimer);
      this.draftSaveTimer = null;
    }

    if (this.onlineHandler) {
      window.removeEventListener("online", this.onlineHandler);
      this.onlineHandler = null;
    }
  }

  connect(): void {
    if (!this.onlineHandler) {
      this.onlineHandler = () => {
        void this.flushSubmissionQueue();
      };
      window.addEventListener("online", this.onlineHandler);
    }

    void this.flushSubmissionQueue();
  }

  async hydrateStorage(): Promise<TStorageHydrationResult | null> {
    if (!this.storageAdapter?.hydrate) {
      return null;
    }

    const hydrationResult = await this.storageAdapter.hydrate();

    if (hydrationResult.migratedFromLocalStorage) {
      this.options.emitEvent(
        "xpressui:storage-migrated",
        this.createEventDetail(this.options.getValues(), {
          source: hydrationResult.source,
          migratedFromLocalStorage: true,
        }),
      );
    }

    return hydrationResult;
  }

  loadDraftValues(): Record<string, any> {
    return getRestorableStorageValues(this.storageAdapter?.loadDraft() || null);
  }

  getDraftAutoSaveMs(): number {
    return this.options.getFormConfig()?.storage?.autoSaveMs ?? 300;
  }

  getLocalStorage(): Storage | null {
    if (typeof window === "undefined") {
      return null;
    }

    let storage: Storage | Record<string, any> | undefined;
    try {
      storage = window.localStorage as Storage | Record<string, any> | undefined;
    } catch {
      return null;
    }
    if (
      !storage ||
      typeof (storage as Storage).getItem !== "function" ||
      typeof (storage as Storage).setItem !== "function" ||
      typeof (storage as Storage).removeItem !== "function"
    ) {
      return null;
    }

    return storage as Storage;
  }

  // -------------------------------------------------------------------------
  // Step index persistence
  // -------------------------------------------------------------------------

  getCurrentStepStorageKey(): string | null {
    const formConfig = this.options.getFormConfig();
    if (!formConfig) {
      return null;
    }

    const baseKey = formConfig.storage?.key || `xpressui:draft:${formConfig.name}`;
    return `${baseKey}:step`;
  }

  loadCurrentStepIndex(): number | null {
    const key = this.getCurrentStepStorageKey();
    if (!key) {
      return null;
    }

    const storage = this.getLocalStorage();
    if (!storage) {
      return null;
    }

    try {
      const raw = storage.getItem(key);
      if (raw === null) {
        return null;
      }

      const parsed = Number(raw);
      return Number.isInteger(parsed) && parsed >= 0 ? parsed : null;
    } catch {
      return null;
    }
  }

  saveCurrentStepIndex(index?: number | null): void {
    const key = this.getCurrentStepStorageKey();
    if (!key) {
      return;
    }

    const storage = this.getLocalStorage();
    if (!storage) {
      return;
    }

    if (typeof index !== "number" || !Number.isInteger(index) || index < 0) {
      try {
        storage.removeItem(key);
      } catch {
        // Ignore storage write failures.
      }
      return;
    }

    try {
      storage.setItem(key, String(index));
    } catch {
      // Ignore storage write failures.
    }
  }

  // -------------------------------------------------------------------------
  // Snapshot helpers
  // -------------------------------------------------------------------------

  getStorageSnapshot(): TFormStorageSnapshot {
    return {
      draft: this.storageAdapter?.loadDraft() || null,
      queue: this.storageAdapter?.loadQueue() || [],
      deadLetter: this.storageAdapter?.loadDeadLetterQueue() || [],
      currentStepIndex: this.loadCurrentStepIndex() ?? undefined,
    };
  }

  applyResumeSnapshot(snapshot: TFormStorageSnapshot): Record<string, any> {
    if (this.storageAdapter) {
      if (snapshot.draft) {
        this.storageAdapter.saveDraft(snapshot.draft);
      } else {
        this.storageAdapter.clearDraft();
      }
      this.storageAdapter.saveQueue(Array.isArray(snapshot.queue) ? snapshot.queue : []);
      this.storageAdapter.saveDeadLetterQueue(Array.isArray(snapshot.deadLetter) ? snapshot.deadLetter : []);
    }

    if (typeof snapshot.currentStepIndex === "number") {
      this.saveCurrentStepIndex(snapshot.currentStepIndex);
      this.options.setCurrentStepIndex?.(snapshot.currentStepIndex);
    } else {
      this.saveCurrentStepIndex(null);
    }

    return getRestorableStorageValues(
      snapshot.draft && typeof snapshot.draft === "object" ? snapshot.draft : null,
    );
  }

  getStorageHealth(): TFormStorageHealth {
    const storageHealth = this.storageAdapter?.getHealth() || {
      adapter: "local-storage" as const,
      encryptionEnabled: false,
      hasDraft: false,
      queueLength: 0,
      deadLetterLength: 0,
      totalEntries: 0,
      retentionMs: { draft: null, queue: null, deadLetter: null },
    };

    return {
      ...storageHealth,
      queueDisabledReason: this.getQueueDisabledReason(),
      queueEnabled: this.shouldUseQueue(),
    };
  }

  // -------------------------------------------------------------------------
  // Event helpers
  // -------------------------------------------------------------------------

  createEventDetail(
    values: Record<string, any>,
    result?: any,
    response?: Response,
    error?: unknown,
  ): Record<string, any> {
    const formConfig = this.options.getFormConfig();
    return {
      values,
      formConfig,
      submit: formConfig?.submit,
      result,
      response,
      error,
    };
  }

  // -------------------------------------------------------------------------
  // Draft
  // -------------------------------------------------------------------------

  emitDraftRestored(values: Record<string, any>): void {
    this.options.emitEvent("xpressui:draft-restored", this.createEventDetail(values));
  }

  saveDraft(values?: Record<string, any>): void {
    if (!this.storageAdapter) {
      return;
    }

    const draftValues = values || this.options.getValues();
    this.storageAdapter.saveDraft(draftValues);
    this.saveCurrentStepIndex(this.options.getCurrentStepIndex?.() ?? null);
    this.options.emitEvent("xpressui:draft-saved", this.createEventDetail(draftValues));
  }

  scheduleDraftSave(): void {
    if (!this.storageAdapter) {
      return;
    }

    if (this.draftSaveTimer !== null) {
      window.clearTimeout(this.draftSaveTimer);
    }

    this.draftSaveTimer = window.setTimeout(() => {
      this.saveDraft();
      this.draftSaveTimer = null;
    }, this.getDraftAutoSaveMs());
  }

  clearDraft(): void {
    if (!this.storageAdapter) {
      return;
    }

    if (this.draftSaveTimer !== null) {
      window.clearTimeout(this.draftSaveTimer);
      this.draftSaveTimer = null;
    }

    this.storageAdapter.clearDraft();
    this.saveCurrentStepIndex(null);
    this.options.emitEvent("xpressui:draft-cleared", this.createEventDetail({}));
  }

  // -------------------------------------------------------------------------
  // Queue
  // -------------------------------------------------------------------------

  getQueueDisabledReason(): string | undefined {
    const formConfig = this.options.getFormConfig();
    if (!formConfig) {
      return undefined;
    }

    const hasFileFields = Object.values(formConfig.sections || {})
      .flat()
      .some((field) => isFileFieldType(field.type));

    if (hasFileFields) {
      return "file-uploads-are-not-queued";
    }

    return undefined;
  }

  shouldUseQueue(): boolean {
    const mode = this.options.getFormConfig()?.storage?.mode;
    if (this.getQueueDisabledReason()) {
      return false;
    }

    return mode === "queue" || mode === "draft-and-queue";
  }

  getRetryDelayMs(attempts: number): number {
    return getDefaultRetryDelayMs(attempts);
  }

  getMaxRetryAttempts(): number {
    return FORM_PERSISTENCE_DEFAULT_MAX_RETRY_ATTEMPTS;
  }

  getQueueState(): TFormQueueState {
    const queue = this.storageAdapter?.loadQueue() || [];
    const nextEntry = queue[0];
    return {
      queueLength: queue.length,
      deadLetterLength: this.storageAdapter?.loadDeadLetterQueue().length || 0,
      nextAttemptAt: nextEntry?.nextAttemptAt,
      attempts: nextEntry?.attempts,
      disabledReason: this.getQueueDisabledReason(),
    };
  }

  emitQueueState(): void {
    const mode = this.options.getFormConfig()?.storage?.mode;
    const storageEnabled = mode === "queue" || mode === "draft-and-queue";
    if (!storageEnabled) {
      return;
    }

    this.options.emitEvent(
      "xpressui:queue-state",
      this.createEventDetail(this.options.getValues(), this.getQueueState()),
    );
  }

  enqueueSubmission(values: Record<string, any>): void {
    if (!this.storageAdapter || !this.shouldUseQueue()) {
      return;
    }

    const queue = this.storageAdapter.enqueueSubmission(values);
    const nextEntry = queue[0];
    this.options.emitEvent(
      "xpressui:queued",
      this.createEventDetail(values, {
        queueLength: queue.length,
        deadLetterLength: this.storageAdapter.loadDeadLetterQueue().length,
        nextAttemptAt: nextEntry?.nextAttemptAt,
        attempts: nextEntry?.attempts,
      } satisfies TFormQueueState),
    );
    this.emitQueueState();
  }

  clearDeadLetterQueue(): void {
    if (!this.storageAdapter) {
      return;
    }

    this.storageAdapter.clearDeadLetterQueue();
    this.options.emitEvent(
      "xpressui:dead-letter-cleared",
      this.createEventDetail({}, this.getQueueState()),
    );
    this.emitQueueState();
  }

  requeueDeadLetterEntry(entryId: string): boolean {
    if (!this.storageAdapter) {
      return false;
    }

    const entry = this.storageAdapter.removeDeadLetterEntry(entryId);
    if (!entry) {
      return false;
    }

    const resetEntry = entry.values;
    const queue = this.storageAdapter.enqueueSubmission(resetEntry);
    this.options.emitEvent(
      "xpressui:dead-letter-requeued",
      this.createEventDetail(resetEntry, {
        queueLength: queue.length,
        deadLetterLength: this.storageAdapter.loadDeadLetterQueue().length,
        entryId,
      }),
    );
    this.emitQueueState();
    return true;
  }

  async replayDeadLetterEntry(entryId: string): Promise<boolean> {
    const formConfig = this.options.getFormConfig();
    if (!this.storageAdapter || !formConfig?.submit?.endpoint) {
      return false;
    }

    const entry = this.storageAdapter.removeDeadLetterEntry(entryId);
    if (!entry) {
      return false;
    }

    try {
      const { response, result } = await this.options.submitValues(entry.values, formConfig.submit);
      this.options.emitEvent(
        "xpressui:dead-letter-replayed-success",
        this.createEventDetail(entry.values, result, response),
      );
      this.emitQueueState();
      return true;
    } catch (error: any) {
      const replayEntry: TQueuedSubmission = {
        ...entry,
        attempts: entry.attempts + 1,
        updatedAt: Date.now(),
        nextAttemptAt: Date.now() + this.getRetryDelayMs(entry.attempts + 1),
        lastError: error?.result?.message || error?.message || "replay_error",
      };
      const deadLetter = this.storageAdapter.enqueueDeadLetter(replayEntry);
      this.options.emitEvent(
        "xpressui:dead-letter-replayed-error",
        this.createEventDetail(
          entry.values,
          { deadLetterLength: deadLetter.length, entry: replayEntry },
          error?.response,
          error,
        ),
      );
      this.emitQueueState();
      return false;
    }
  }

  async flushSubmissionQueue(): Promise<void> {
    const formConfig = this.options.getFormConfig();
    if (
      !this.storageAdapter ||
      !formConfig?.submit?.endpoint ||
      !this.shouldUseQueue() ||
      this.syncInFlight
    ) {
      return;
    }

    const pending = this.storageAdapter.loadQueue();
    if (!pending.length) {
      return;
    }

    this.syncInFlight = true;
    try {
      while (true) {
        const entry = this.storageAdapter.loadQueue()[0];
        if (!entry) {
          this.emitQueueState();
          break;
        }

        if (entry.nextAttemptAt > Date.now()) {
          break;
        }

        try {
          const { response, result } = await this.options.submitValues(entry.values, formConfig.submit);
          this.storageAdapter.dequeueSubmission();
          this.options.emitEvent(
            "xpressui:sync-success",
            this.createEventDetail(entry.values, result, response),
          );
          this.emitQueueState();
        } catch (error: any) {
          const attempts = entry.attempts + 1;
          const nextEntry: TQueuedSubmission = {
            ...entry,
            attempts,
            updatedAt: Date.now(),
            nextAttemptAt: Date.now() + this.getRetryDelayMs(attempts),
            lastError: error?.result?.message || error?.message || "sync_error",
          };
          if (attempts >= this.getMaxRetryAttempts()) {
            this.storageAdapter.dequeueSubmission();
            const deadLetter = this.storageAdapter.enqueueDeadLetter(nextEntry);
            this.options.emitEvent(
              "xpressui:dead-lettered",
              this.createEventDetail(
                entry.values,
                { deadLetterLength: deadLetter.length, entry: nextEntry },
                error?.response,
                error,
              ),
            );
          } else {
            this.storageAdapter.updateQueueEntry(nextEntry);
          }
          this.options.emitEvent(
            "xpressui:sync-error",
            this.createEventDetail(entry.values, error?.result, error?.response, error),
          );
          this.emitQueueState();
          break;
        }
      }
    } finally {
      this.syncInFlight = false;
    }
  }

  // -------------------------------------------------------------------------
  // Delegation — Resume & Share code (FormResumeRuntime)
  // -------------------------------------------------------------------------

  get resumeTokenMemory() { return this.resumeRuntime.resumeTokenMemory; }
  get shareCodeClaimPolicyState() { return this.resumeRuntime.shareCodeClaimPolicyState; }
  get lastShareCodeClaimDetail() { return this.resumeRuntime.lastShareCodeClaimDetail; }
  get lastShareCodeRestoreDetail() { return this.resumeRuntime.lastShareCodeRestoreDetail; }

  getResumeEndpoint() { return this.resumeRuntime.getResumeEndpoint(); }
  getShareCodeEndpoint() { return this.resumeRuntime.getShareCodeEndpoint(); }
  getResumeStorageKey(token: string) { return this.resumeRuntime.getResumeStorageKey(token); }
  getResumeStoragePrefix() { return this.resumeRuntime.getResumeStoragePrefix(); }
  buildResumeEndpointUrl(token: string) { return this.resumeRuntime.buildResumeEndpointUrl(token); }
  getResumeTokenTtlMs() { return this.resumeRuntime.getResumeTokenTtlMs(); }
  getResumeTokenExpiresAt(savedAt: number) { return this.resumeRuntime.getResumeTokenExpiresAt(savedAt); }
  isResumeTokenExpired(savedAt?: number) { return this.resumeRuntime.isResumeTokenExpired(savedAt); }
  getResumeTokenSignatureVersion() { return this.resumeRuntime.getResumeTokenSignatureVersion(); }
  getResumeCreatePayload(snapshot: TFormStorageSnapshot) { return this.resumeRuntime.getResumeCreatePayload(snapshot); }
  buildResumeSnapshot() { return this.resumeRuntime.buildResumeSnapshot(); }
  persistResumeTokenState(token: string, state: any) { return this.resumeRuntime.persistResumeTokenState(token, state); }
  parseResumeToken(token: string, raw: string | null) { return this.resumeRuntime.parseResumeToken(token, raw); }
  applyResumeTokenSignature(token: string, state: any) { return this.resumeRuntime.applyResumeTokenSignature(token, state); }
  buildResumeTokenSignaturePayload(token: string, state: any) { return this.resumeRuntime.buildResumeTokenSignaturePayload(token, state); }
  isResumeTokenSignatureValid(token: string, state: any) { return this.resumeRuntime.isResumeTokenSignatureValid(token, state); }
  emitResumeTokenInvalidSignature(token: string, state: any) { return this.resumeRuntime.emitResumeTokenInvalidSignature(token, state); }
  normalizeResumeSnapshot(input: unknown) { return this.resumeRuntime.normalizeResumeSnapshot(input); }
  parseRemoteCreateResponse(result: unknown) { return this.resumeRuntime.parseRemoteCreateResponse(result); }
  parseRemoteLookupResponse(fallbackToken: string, result: unknown) { return this.resumeRuntime.parseRemoteLookupResponse(fallbackToken, result); }
  parseRemoteInvalidateResponse(token: string, response: Response, result: unknown) { return this.resumeRuntime.parseRemoteInvalidateResponse(token, response, result); }
  parseRemoteShareCodeCreateResponse(result: unknown) { return this.resumeRuntime.parseRemoteShareCodeCreateResponse(result); }
  parseRemoteShareCodeClaimResponse(result: unknown) { return this.resumeRuntime.parseRemoteShareCodeClaimResponse(result); }
  createResumeToken() { return this.resumeRuntime.createResumeToken(); }
  async createResumeTokenAsync() { return this.resumeRuntime.createResumeTokenAsync(); }
  async lookupResumeToken(token: string) { return this.resumeRuntime.lookupResumeToken(token); }
  restoreFromResumeToken(token: string) { return this.resumeRuntime.restoreFromResumeToken(token); }
  async restoreFromResumeTokenAsync(token: string) { return this.resumeRuntime.restoreFromResumeTokenAsync(token); }
  listResumeTokens() { return this.resumeRuntime.listResumeTokens(); }
  deleteResumeToken(token: string) { return this.resumeRuntime.deleteResumeToken(token); }
  async invalidateResumeToken(token: string) { return this.resumeRuntime.invalidateResumeToken(token); }
  getShareCodeClaimThrottleMs() { return this.resumeRuntime.getShareCodeClaimThrottleMs(); }
  getShareCodeClaimMaxAttempts() { return this.resumeRuntime.getShareCodeClaimMaxAttempts(); }
  getShareCodeClaimWindowMs() { return this.resumeRuntime.getShareCodeClaimWindowMs(); }
  getShareCodeClaimBlockMs() { return this.resumeRuntime.getShareCodeClaimBlockMs(); }
  normalizeShareCodeKey(code: string) { return this.resumeRuntime.normalizeShareCodeKey(code); }
  normalizeRemotePolicyClaimStatus(code: any) { return this.resumeRuntime.normalizeRemotePolicyClaimStatus(code); }
  getShareCodeClaimState(code: string) { return this.resumeRuntime.getShareCodeClaimState(code); }
  emitShareCodeClaimBlocked(code: string, result: any) { return this.resumeRuntime.emitShareCodeClaimBlocked(code, result); }
  emitShareCodeClaimState(result: any, response?: Response) { return this.resumeRuntime.emitShareCodeClaimState(result, response); }
  evaluateShareCodeClaimPermission(code: string) { return this.resumeRuntime.evaluateShareCodeClaimPermission(code); }
  markShareCodeClaimFailure(code: string) { return this.resumeRuntime.markShareCodeClaimFailure(code); }
  markShareCodeClaimSuccess(code: string) { return this.resumeRuntime.markShareCodeClaimSuccess(code); }
  getResumeStatusSummary() { return this.resumeRuntime.getResumeStatusSummary(); }
  async createResumeShareCode(token: string) { return this.resumeRuntime.createResumeShareCode(token); }
  async createResumeShareCodeDetail(token: string) { return this.resumeRuntime.createResumeShareCodeDetail(token); }
  async claimResumeShareCode(code: string) { return this.resumeRuntime.claimResumeShareCode(code); }
  async claimResumeShareCodeDetail(code: string) { return this.resumeRuntime.claimResumeShareCodeDetail(code); }
  async restoreFromShareCodeAsync(code: string) { return this.resumeRuntime.restoreFromShareCodeAsync(code); }
  async restoreFromShareCodeDetailAsync(code: string) { return this.resumeRuntime.restoreFromShareCodeDetailAsync(code); }
}
