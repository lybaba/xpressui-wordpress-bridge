import TFieldConfig from "./TFieldConfig";
import TFormConfig, { TFormSubmitRequest } from "./TFormConfig";
import {
  CAMERA_PHOTO_TYPE,
  DOCUMENT_SCAN_TYPE,
  HTML_TYPE,
  IMAGE_TYPE,
  IMAGE_GALLERY_TYPE,
  LINK_TYPE,
  MEDIA_TYPE,
  OUTPUT_TYPE,
  RICH_EDITOR_TYPE,
  TEXTAREA_TYPE,
  TEXT_TYPE,
  UPLOAD_FILE_TYPE,
  UPLOAD_IMAGE_TYPE,
  URL_TYPE,
} from "./field";
import { FormDynamicRuntime, TFormActiveTemplateWarning } from "./form-dynamic";
import {
  FormEngineRuntime,
  TDocumentDataReadMode,
  TDocumentDataViewOptions,
  TStoredDocumentData,
} from "./form-engine";
import { FormStepRuntime, TFormStepProgress, TFormWorkflowSnapshot } from "./form-steps";
import { FormUploadRuntime } from "./form-upload";
import {
  buildLocalFormIncidentSummary,
  buildLocalFormOperationalSummary,
  TLocalFormIncidentSummary,
  TLocalFormOperationalSummary,
} from "./form-admin";
import {
  FormPersistenceRuntime,
  TFormQueueState,
  TResumeLookupResult,
  TResumeShareCodeClaimDetail,
  TResumeShareCodeRestoreDetail,
  TResumeShareCodeInfo,
  TResumeStatusSummary,
  TResumeTokenInfo,
  TFormStorageHealth,
  TFormStorageSnapshot,
} from "./form-persistence";

export type TFormRuntimeSubmitResult = {
  response: Response;
  result: any;
};

export type TFormOutputRendererType =
  | "text"
  | "html"
  | "image"
  | "file"
  | "video"
  | "audio"
  | "map"
  | "link"
  | "document";
export type TFormMediaDisplayPolicy = "thumbnail" | "large" | "link" | "gallery" | "embed";
export type TFormOutputSnapshot = Record<string, {
  rendererType: TFormOutputRendererType;
  mediaDisplayPolicy: TFormMediaDisplayPolicy;
  value: any;
}>;

export type TFormRuntimeEmitEvent = (
  eventName: string,
  detail: Record<string, any>,
) => boolean;

export type TFormRuntimeSubmitValues = (
  values: Record<string, any>,
  submitConfig: TFormSubmitRequest,
) => Promise<TFormRuntimeSubmitResult>;

export type TFormRuntimeDynamicAdapters = {
  getFieldContainer(fieldName: string): HTMLElement | null;
  getFieldElement(
    fieldName: string,
  ): HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement | null;
  getFieldValue(fieldName: string): any;
  clearFieldValue(fieldName: string): void;
};

export type TFormRuntimeOptions = {
  getValues?: () => Record<string, any>;
  emitEvent?: TFormRuntimeEmitEvent;
  submitValues?: TFormRuntimeSubmitValues;
  dynamic?: TFormRuntimeDynamicAdapters;
};

function noopEmitEvent(): boolean {
  return true;
}

export type TFormRuntimePublicApi = Pick<
  FormRuntime,
  | "setFormConfig"
  | "setField"
  | "getFields"
  | "normalizeValues"
  | "buildSubmissionValues"
  | "validateValues"
  | "loadDraftValues"
  | "hydrateStorage"
  | "saveDraft"
  | "clearDraft"
  | "scheduleDraftSave"
  | "shouldUseQueue"
  | "enqueueSubmission"
  | "getQueueState"
  | "getStorageSnapshot"
  | "getStorageHealth"
  | "getResumeStatusSummary"
  | "getOperationalSummary"
  | "getIncidentSummary"
  | "getStepNames"
  | "getCurrentStepIndex"
  | "getCurrentStepName"
  | "getStepProgress"
  | "getWorkflowState"
  | "setWorkflowState"
  | "goToWorkflowStep"
  | "getWorkflowSnapshot"
  | "getWorkflowContext"
  | "getOutputSnapshot"
  | "goToStep"
  | "nextStep"
  | "previousStep"
  | "validateCurrentStep"
  | "createResumeToken"
  | "createResumeTokenAsync"
  | "createResumeShareCode"
  | "createResumeShareCodeDetail"
  | "listResumeTokens"
  | "deleteResumeToken"
  | "invalidateResumeToken"
  | "lookupResumeToken"
  | "claimResumeShareCodeDetail"
  | "claimResumeShareCode"
  | "restoreFromResumeToken"
  | "restoreFromResumeTokenAsync"
  | "restoreFromShareCodeDetailAsync"
  | "restoreFromShareCodeAsync"
  | "clearDeadLetterQueue"
  | "requeueDeadLetterEntry"
  | "replayDeadLetterEntry"
  | "flushSubmissionQueue"
  | "connectPersistence"
  | "disconnectPersistence"
  | "updateConditionalFields"
  | "refreshRemoteOptions"
  | "getActiveTemplateWarnings"
  | "clearActiveTemplateWarnings"
  | "getRecentAppliedRules"
  | "clearRecentAppliedRules"
  | "getDocumentData"
  | "getDocumentDataView"
  | "getAllDocumentData"
  | "getAllDocumentDataView"
>;

export class FormRuntime {
  formConfig: TFormConfig | null;
  engine: FormEngineRuntime;
  persistence: FormPersistenceRuntime;
  dynamic: FormDynamicRuntime | null;
  upload: FormUploadRuntime;
  steps: FormStepRuntime;
  options: Required<Pick<TFormRuntimeOptions, "emitEvent" | "getValues" | "submitValues">>;

  constructor(formConfig: TFormConfig | null = null, options: TFormRuntimeOptions = {}) {
    this.formConfig = null;
    this.engine = new FormEngineRuntime();
    this.options = {
      emitEvent: options.emitEvent || noopEmitEvent,
      getValues: options.getValues || (() => ({})),
      submitValues: options.submitValues || (() => {
        throw new Error("unreachable");
      }),
    };
    this.steps = new FormStepRuntime();
    this.upload = new FormUploadRuntime({
      emitEvent: (eventName, detail) => this.options.emitEvent(eventName, detail),
    });
    this.options.submitValues =
      options.submitValues ||
      ((values, submitConfig) => this.upload.submit(values, submitConfig, this.engine.getFields()));
    this.persistence = new FormPersistenceRuntime({
      getFormConfig: () => this.formConfig,
      getValues: () => this.options.getValues(),
      getCurrentStepIndex: () =>
        (this.steps.getStepNames().length > 1 ? this.steps.getCurrentStepIndex() : null),
      setCurrentStepIndex: (index) => {
        if (this.steps.getStepNames().length) {
          this.steps.setCurrentStepIndex(
            Math.max(0, Math.min(index, Math.max(0, this.steps.getStepNames().length - 1))),
          );
        }
      },
      emitEvent: (eventName, detail) => this.options.emitEvent(eventName, detail),
      submitValues: (values, submitConfig) => this.options.submitValues(values, submitConfig),
    });
    this.dynamic = options.dynamic
      ? new FormDynamicRuntime({
          getFieldConfigs: () => Object.values(this.engine.getFields()),
          getRules: () => this.formConfig?.rules || [],
          getFieldContainer: (fieldName) => options.dynamic!.getFieldContainer(fieldName),
          getFieldElement: (fieldName) => options.dynamic!.getFieldElement(fieldName),
          setFieldDisabled: (fieldName, disabled) => {
            const fieldElement = options.dynamic!.getFieldElement(fieldName);
            if (fieldElement) {
              fieldElement.disabled = disabled;
            }
          },
          setFieldError: () => {
            // No built-in error surface in the headless runtime.
          },
          clearFieldErrors: () => {
            // No built-in error surface in the headless runtime.
          },
          setSubmitLocked: () => {
            // The headless runtime does not manage submit button state.
          },
          getFieldValue: (fieldName) => options.dynamic!.getFieldValue(fieldName),
          clearFieldValue: (fieldName) => options.dynamic!.clearFieldValue(fieldName),
          setFieldValue: (fieldName, value) => {
            options.dynamic!.clearFieldValue(fieldName);
            const fieldElement = options.dynamic!.getFieldElement(fieldName);
            if (fieldElement) {
              if (fieldElement instanceof HTMLInputElement && fieldElement.type === "checkbox") {
                fieldElement.checked = Boolean(value);
              } else if (
                fieldElement instanceof HTMLInputElement &&
                fieldElement.type === "file"
              ) {
                if (!value || (Array.isArray(value) && !value.length)) {
                  fieldElement.value = "";
                }
              } else if (fieldElement instanceof HTMLSelectElement && fieldElement.multiple) {
                const selectedValues = Array.isArray(value)
                  ? value.map((entry) => String(entry))
                  : [];
                Array.from(fieldElement.options).forEach((option) => {
                  option.selected = selectedValues.includes(option.value);
                });
              } else {
                fieldElement.value = value === undefined ? "" : String(value);
              }
            }
          },
          getFormValues: () => this.options.getValues(),
          emitEvent: (eventName, detail) => this.options.emitEvent(eventName, detail),
          getEventContext: () => ({
            formConfig: this.formConfig,
            submit: this.formConfig?.submit,
          }),
        })
      : null;

    this.setFormConfig(formConfig);
  }

  setFormConfig(formConfig: TFormConfig | null): void {
    this.formConfig = formConfig;
    this.engine.setFormConfig(formConfig);
    this.persistence.setFormConfig(formConfig);
    this.steps.setFormConfig(formConfig);
    const savedStepIndex = this.persistence.loadCurrentStepIndex();
    if (
      typeof savedStepIndex === "number" &&
      savedStepIndex >= 0 &&
      savedStepIndex < this.steps.getStepNames().length
    ) {
      this.steps.setCurrentStepIndex(savedStepIndex);
    }
  }

  setField(fieldName: string, fieldConfig: TFieldConfig): void {
    this.engine.setField(fieldName, fieldConfig);
  }

  getFields(): Record<string, TFieldConfig> {
    return this.engine.getFields();
  }

  normalizeValues(values: Record<string, any>): Record<string, any> {
    return this.engine.normalizeValues(values);
  }

  buildSubmissionValues(values: Record<string, any>): Record<string, any> {
    return this.engine.buildSubmissionValues(
      values,
      Boolean(this.formConfig?.submit?.includeDocumentData),
      this.formConfig?.submit?.documentDataMode || "full",
      this.formConfig?.submit?.documentFieldPaths,
    );
  }

  validateValues(values: Record<string, any>, stepIndex: number = 0): Record<string, any> {
    return this.engine.validateValues(values, stepIndex);
  }

  loadDraftValues(): Record<string, any> {
    return this.persistence.loadDraftValues();
  }

  async hydrateStorage(): Promise<TFormStorageSnapshot> {
    const result = await this.persistence.hydrateStorage();
    return result?.snapshot || this.persistence.getStorageSnapshot();
  }

  saveDraft(values?: Record<string, any>): void {
    this.persistence.saveDraft(values);
  }

  clearDraft(): void {
    this.persistence.clearDraft();
  }

  scheduleDraftSave(): void {
    this.persistence.scheduleDraftSave();
  }

  shouldUseQueue(): boolean {
    return this.persistence.shouldUseQueue();
  }

  enqueueSubmission(values: Record<string, any>): void {
    this.persistence.enqueueSubmission(values);
  }

  getQueueState(): TFormQueueState {
    return this.persistence.getQueueState();
  }

  getStorageSnapshot(): TFormStorageSnapshot {
    return this.persistence.getStorageSnapshot();
  }

  getStorageHealth(): TFormStorageHealth {
    return this.persistence.getStorageHealth();
  }

  getResumeStatusSummary(): TResumeStatusSummary {
    return this.persistence.getResumeStatusSummary();
  }

  getOperationalSummary(): TLocalFormOperationalSummary {
    return buildLocalFormOperationalSummary({
      storageHealth: this.getStorageHealth(),
      snapshot: this.getStorageSnapshot(),
      resumeTokens: this.listResumeTokens(),
      workflow: {
        currentStepIndex: this.getCurrentStepIndex(),
        stepProgress: this.getStepProgress(),
        workflowSnapshot: this.getWorkflowSnapshot(),
      },
    });
  }

  getIncidentSummary(limit = 5): TLocalFormIncidentSummary {
    return buildLocalFormIncidentSummary({
      snapshot: this.getStorageSnapshot(),
      resumeTokens: this.listResumeTokens(),
    }, limit);
  }

  getStepNames(): string[] {
    return this.steps.getStepNames();
  }

  getCurrentStepIndex(): number {
    return this.steps.getCurrentStepIndex();
  }

  getCurrentStepName(): string | null {
    return this.steps.getCurrentStepName();
  }

  getStepProgress(): TFormStepProgress {
    return this.steps.getStepProgress();
  }

  getWorkflowState(): string {
    return this.steps.getWorkflowState();
  }

  setWorkflowState(nextState: string): void {
    this.steps.setWorkflowState(nextState);
  }

  goToWorkflowStep(state?: string): boolean {
    const moved = this.steps.goToWorkflowStep(state);
    if (moved) {
      this.persistence.saveCurrentStepIndex(this.steps.getCurrentStepIndex());
    }
    return moved;
  }

  getWorkflowSnapshot(): TFormWorkflowSnapshot {
    return this.steps.getWorkflowSnapshot(this.options.getValues());
  }

  getWorkflowContext(): {
    workflowState: string;
    snapshot: TFormWorkflowSnapshot;
  } {
    return {
      workflowState: this.getWorkflowState(),
      snapshot: this.getWorkflowSnapshot(),
    };
  }

  getOutputRendererType(fieldConfig: TFieldConfig): TFormOutputRendererType {
    const subType = String(fieldConfig.subType || fieldConfig.refType || "").toLowerCase();
    const accept = String(fieldConfig.accept || "").toLowerCase();

    if (fieldConfig.type === OUTPUT_TYPE || fieldConfig.type === MEDIA_TYPE) {
      if (subType.includes("html")) {
        return "html";
      }
      if (subType.includes("image")) {
        return "image";
      }
      if (subType.includes("video")) {
        return "video";
      }
      if (subType.includes("audio")) {
        return "audio";
      }
      if (subType.includes("map")) {
        return "map";
      }
      if (
        subType.includes("document")
        || subType.includes("pdf")
        || subType.includes("viewer")
        || subType.includes("embed")
      ) {
        return "document";
      }
      if (subType.includes("file") || subType.includes("document")) {
        return "file";
      }
      if (subType.includes("link") || subType.includes("url")) {
        return "link";
      }
    }

    if (fieldConfig.type === HTML_TYPE || fieldConfig.type === RICH_EDITOR_TYPE) {
      return "html";
    }

    if (
      fieldConfig.type === IMAGE_TYPE ||
      fieldConfig.type === IMAGE_GALLERY_TYPE ||
      fieldConfig.type === UPLOAD_IMAGE_TYPE ||
      fieldConfig.type === CAMERA_PHOTO_TYPE
    ) {
      return "image";
    }

    if (fieldConfig.type === UPLOAD_FILE_TYPE || fieldConfig.type === DOCUMENT_SCAN_TYPE) {
      if (accept.includes("video/")) {
        return "video";
      }
      if (accept.includes("audio/")) {
        return "audio";
      }
      if (accept.includes("application/pdf")) {
        return "document";
      }
      return "file";
    }

    if (fieldConfig.type === LINK_TYPE || fieldConfig.type === URL_TYPE) {
      if (subType.includes("map")) {
        return "map";
      }
      return "link";
    }

    if (fieldConfig.type === "video" || accept.includes("video/")) {
      return "video";
    }

    if (fieldConfig.type === "audio" || accept.includes("audio/")) {
      return "audio";
    }

    if (fieldConfig.type === TEXT_TYPE || fieldConfig.type === TEXTAREA_TYPE) {
      return "text";
    }

    return "text";
  }

  getOutputSnapshot(values?: Record<string, any>): TFormOutputSnapshot {
    const currentValues = values || this.options.getValues();
    const fields = this.engine.getFields();
    return Object.entries(fields).reduce((accumulator, [fieldName, fieldConfig]) => {
      const rendererType = this.getOutputRendererType(fieldConfig);
      const mediaDisplayPolicy: TFormMediaDisplayPolicy =
        rendererType === "file" || rendererType === "link"
          ? "link"
          : rendererType === "document"
            ? "embed"
            : "large";
      accumulator[fieldName] = {
        rendererType,
        mediaDisplayPolicy,
        value: currentValues[fieldName],
      };
      return accumulator;
    }, {} as TFormOutputSnapshot);
  }

  getStepSummary(): Array<{ field: string; label: string; value: any }> {
    return this.steps.getStepSummary(this.options.getValues(), this.engine.getFields());
  }

  goToStep(index: number): boolean {
    if (!this.steps.goToStep(index)) {
      return false;
    }

    this.persistence.saveCurrentStepIndex(this.steps.getCurrentStepIndex());
    return true;
  }

  nextStep(): boolean {
    const currentValues = this.options.getValues();
    const isStepValid = this.validateCurrentStep(currentValues);
    if (!this.steps.nextStep(currentValues, isStepValid)) {
      return false;
    }

    this.persistence.saveCurrentStepIndex(this.steps.getCurrentStepIndex());
    return true;
  }

  previousStep(): boolean {
    const moved = this.steps.previousStep();
    if (moved) {
      this.persistence.saveCurrentStepIndex(this.steps.getCurrentStepIndex());
    }
    return moved;
  }

  validateCurrentStep(values?: Record<string, any>): boolean {
    if (this.steps.getStepNames().length <= 1) {
      return true;
    }

    if (!this.steps.shouldValidateCurrentStepForWorkflow()) {
      return true;
    }

    const currentStepName = this.steps.getCurrentStepName();
    if (!currentStepName) {
      return true;
    }

    const currentStepFields = this.formConfig?.sections?.[currentStepName] || [];
    if (!currentStepFields.length) {
      return true;
    }

    const nextValues = values || this.options.getValues();
    const currentStepIndex = this.getCurrentStepIndex();
    const errors = this.engine.validateValues(nextValues, currentStepIndex);
    return !currentStepFields.some((field) => Boolean(errors[field.name]));
  }

  createResumeToken(): string | null {
    return this.persistence.createResumeToken();
  }

  createResumeTokenAsync(): Promise<string | null> {
    return this.persistence.createResumeTokenAsync();
  }

  createResumeShareCode(token: string): Promise<string | null> {
    return this.persistence.createResumeShareCode(token);
  }

  createResumeShareCodeDetail(token: string): Promise<TResumeShareCodeInfo | null> {
    return this.persistence.createResumeShareCodeDetail(token);
  }

  listResumeTokens(): TResumeTokenInfo[] {
    return this.persistence.listResumeTokens();
  }

  deleteResumeToken(token: string): boolean {
    return this.persistence.deleteResumeToken(token);
  }

  invalidateResumeToken(token: string): Promise<boolean> {
    return this.persistence.invalidateResumeToken(token);
  }

  restoreFromResumeToken(token: string): Record<string, any> | null {
    return this.persistence.restoreFromResumeToken(token);
  }

  lookupResumeToken(token: string): Promise<TResumeLookupResult | null> {
    return this.persistence.lookupResumeToken(token);
  }

  claimResumeShareCode(code: string): Promise<TResumeLookupResult | null> {
    return this.persistence.claimResumeShareCode(code);
  }

  claimResumeShareCodeDetail(code: string): Promise<TResumeShareCodeClaimDetail | null> {
    return this.persistence.claimResumeShareCodeDetail(code);
  }

  restoreFromShareCodeDetailAsync(code: string): Promise<TResumeShareCodeRestoreDetail | null> {
    return this.persistence.restoreFromShareCodeDetailAsync(code);
  }

  restoreFromResumeTokenAsync(token: string): Promise<Record<string, any> | null> {
    return this.persistence.restoreFromResumeTokenAsync(token);
  }

  restoreFromShareCodeAsync(code: string): Promise<Record<string, any> | null> {
    return this.persistence.restoreFromShareCodeAsync(code);
  }

  clearDeadLetterQueue(): void {
    this.persistence.clearDeadLetterQueue();
  }

  requeueDeadLetterEntry(entryId: string): boolean {
    return this.persistence.requeueDeadLetterEntry(entryId);
  }

  replayDeadLetterEntry(entryId: string): Promise<boolean> {
    return this.persistence.replayDeadLetterEntry(entryId);
  }

  flushSubmissionQueue(): Promise<void> {
    return this.persistence.flushSubmissionQueue();
  }

  connectPersistence(): void {
    this.persistence.connect();
  }

  disconnectPersistence(): void {
    this.persistence.disconnect();
  }

  updateConditionalFields(): void {
    this.dynamic?.updateConditionalFields();
  }

  refreshRemoteOptions(sourceFieldName?: string): Promise<void> {
    if (!this.dynamic) {
      return Promise.resolve();
    }

    return this.dynamic.refreshRemoteOptions(sourceFieldName);
  }

  getActiveTemplateWarnings(): TFormActiveTemplateWarning[] {
    return this.dynamic?.getActiveTemplateWarnings() || [];
  }

  clearActiveTemplateWarnings(): void {
    this.dynamic?.clearActiveTemplateWarnings();
  }

  getRecentAppliedRules() {
    return this.dynamic?.getRecentAppliedRules() || [];
  }

  clearRecentAppliedRules(): void {
    this.dynamic?.clearRecentAppliedRules();
  }

  getDocumentData(fieldName: string): TStoredDocumentData | null {
    return this.engine.getDocumentData(fieldName);
  }

  getDocumentDataView(
    fieldName: string,
    mode: TDocumentDataReadMode = "summary",
    options: boolean | TDocumentDataViewOptions = true,
  ): Record<string, any> | null {
    return this.engine.getDocumentDataView(fieldName, mode, options);
  }

  getAllDocumentData(): Record<string, TStoredDocumentData> {
    return this.engine.getAllDocumentData();
  }

  getAllDocumentDataView(
    mode: TDocumentDataReadMode = "summary",
    options: boolean | TDocumentDataViewOptions = true,
  ): Record<string, Record<string, any>> {
    return this.engine.getAllDocumentDataView(mode, options);
  }
}
