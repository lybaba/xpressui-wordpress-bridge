import { createForm, FormApi } from "final-form";
import TFormConfig, {
  TFormValidationI18nConfig,
  TFormSubmitLifecycleStage,
  TFormSubmitRequest,
} from "./common/TFormConfig";
import { TValidator } from "./common/Validator";
import type { TValidationError } from "./common/parse-errors";
import getFormConfig, { getErrorClass, getFieldConfig } from "./dom-utils";
import TFieldConfig from "./common/TFieldConfig";
import { FormEngineRuntime, TDocumentDataViewOptions } from "./common/form-engine";
import {
  DOCUMENT_NORMALIZED_CONTRACT_VERSION,
  createNormalizedDocumentContract,
  TDocumentMrzResult,
  TDocumentScanInsight,
} from "./common/document-contract";
import {
  APPROVAL_STATE_TYPE,
  CAMERA_PHOTO_TYPE,
  CHECKBOXES_TYPE,
  DOCUMENT_SCAN_TYPE,
  isFileFieldType,
  IMAGE_GALLERY_TYPE,
  PRODUCT_LIST_TYPE,
  QUIZ_TYPE,
  RADIO_BUTTONS_TYPE,
  QR_SCAN_TYPE,
  SELECT_PRODUCT_TYPE,
  SETTING_TYPE,
  UPLOAD_FILE_TYPE,
  UNKNOWN_TYPE,
} from "./common/field";
import { FormDynamicRuntime } from "./common/form-dynamic";
import type { TFormActiveTemplateWarning } from "./common/form-dynamic";
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
} from "./common/form-persistence";
import {
  getRemoteResumePolicy,
  getResumeShareCodeClaimPresentation,
  isRemoteResumePolicy,
  REMOTE_RESUME_CONTRACT_VERSION,
} from "./common/resume-contract";
import {
  assertProviderResponseContract,
  buildProviderTransitionCandidates as buildConfiguredProviderTransitionCandidates,
  buildProviderMessagesResult,
  buildSubmitHookErrorResult,
  getProviderContractWarning,
  runValidationHooks,
  resolveApprovalStateUpdate,
  resolveSubmitTransportResult,
  runConfiguredSubmitLifecycleStage,
} from "./common/form-submit-runtime";
import { getRestorableStorageValues } from "./common/form-storage";
import { FormStepRuntime } from "./common/form-steps";
import { FormRuntime } from "./common/form-runtime";
import { FormUploadRuntime, TFormUploadState } from "./common/form-upload";
import {
  buildLocalFormIncidentSummary,
  buildLocalFormOperationalSummary,
} from "./common/form-admin";
import { validatePublicFormConfig } from "./common/public-schema";
import {
  createNormalizedProviderResult,
  isNormalizedProviderResult,
  PROVIDER_RESPONSE_CONTRACT_VERSION,
  getProviderErrorEventName,
  getProviderSuccessEventName,
  normalizeProviderResult,
  registerProvider,
} from "./common/provider-registry";
import type { TFormProviderTransition, TNormalizedProviderResult } from "./common/provider-registry";
import {
  getDocumentScanInsight,
  getDocumentScanSlotCount,
  getFileValueList,
  hasFileValues,
  isDocumentScanField,
  isQrScanField,
} from "./ui/form-ui.document";
import {
  getImageGalleryCatalog as getImageGalleryCatalogItems,
  getImageGallerySelectionItems,
  getProductCartItems as getNormalizedProductCartItems,
  getProductCartTotal as getNormalizedProductCartTotal,
  getProductListCatalog as getProductListCatalogItems,
} from "./ui/form-ui.commerce";
import { createCommerceRuntime } from "./ui/form-ui.commerce-runtime";
import { createOverlayRuntime } from "./ui/form-ui.overlay-runtime";
import { isOpenQuizField as isConfiguredOpenQuizField } from "./ui/form-ui.quiz";
import { createQuizRuntime } from "./ui/form-ui.quiz-runtime";
import {
  collectDomFieldValues,
  getOutputRendererType as getFieldOutputRendererType,
  isFieldViewMode as isConfiguredFieldViewMode,
  readInputElementValue as readFieldInputElementValue,
  readViewValuesAttribute as readConfiguredViewValuesAttribute,
  resolveFieldViewValue as resolveConfiguredFieldViewValue,
} from "./ui/form-ui.view";
import {
  ensureStepControls as ensureConfiguredStepControls,
  getCurrentStepFieldElements as getConfiguredCurrentStepFieldElements,
  getStepButtonLabels as getConfiguredStepButtonLabels,
  getStepUiConfig as getConfiguredStepUiConfig,
  getStepElements as getConfiguredStepElements,
  syncStepControls as syncConfiguredStepControls,
  syncStepVisibility as syncConfiguredStepVisibility,
} from "./ui/form-ui.workflow";
import {
  applyFieldValuePresentation as applyConfiguredFieldValuePresentation,
  bindSelectionFieldEvents as bindConfiguredSelectionFieldEvents,
  bindSimpleFieldEvents as bindConfiguredSimpleFieldEvents,
  getFieldContainer as getConfiguredFieldContainer,
  getFieldElement as getConfiguredFieldElement,
  renderFieldErrorState as renderConfiguredFieldErrorState,
} from "./ui/form-ui.field";
import {
  createDefaultHtmlSanitizer as createConfiguredDefaultHtmlSanitizer,
  createDefaultOutputRenderers as createConfiguredDefaultOutputRenderers,
  escapeHtml as escapeConfiguredHtml,
  getDisplayText as getConfiguredDisplayText,
  getFileMeta as getConfiguredFileMeta,
  getFileMetas as getConfiguredFileMetas,
  getMapSources as getConfiguredMapSources,
  getMediaSources as getConfiguredMediaSources,
  isEmbeddableDocumentSource as isConfiguredEmbeddableDocumentSource,
  isSafeMapEmbedSource as isConfiguredSafeMapEmbedSource,
  normalizeViewValue as normalizeConfiguredViewValue,
  readTemplateTokenValue as readConfiguredTemplateTokenValue,
  renderViewTemplate as renderConfiguredViewTemplate,
  resolveMediaDisplayPolicy as resolveConfiguredMediaDisplayPolicy,
  resolveOutputRendererForField as resolveConfiguredOutputRendererForField,
  resolveViewTemplateValue as resolveConfiguredViewTemplateValue,
  shouldRenderUnsafeHtml as shouldConfiguredRenderUnsafeHtml,
} from "./ui/form-ui.output";
import { createDocumentQrRuntime } from "./ui/form-ui.document-qr-runtime";
export type {
  TFormApprovalState,
  THydratedFormSubmitDetail,
  TFormWorkflowState,
} from "./ui/form-ui.types";
import type {
  TBarcodeDetectorResult,
  TDocumentPerspectiveCorners,
  TFieldOutputRendererOverride,
  TFormApprovalState,
  TFormHtmlSanitizer,
  TFormOutputRenderer,
  TFormRenderMode,
  THydratedFormSubmitDetail,
  TFormWorkflowState,
  TImageGalleryItem,
  TMediaDisplayPolicy,
  TOutputRendererType,
  TProductCartItem,
  TProductListItem,
  TProviderTransitionRouteResult,
  TQrScannerState,
  TWorkflowRouteResult,
} from "./ui/form-ui.types";
export {
  DOCUMENT_NORMALIZED_CONTRACT_VERSION,
  createNormalizedDocumentContract,
  isDocumentNormalizedContractV2,
  summarizeNormalizedDocumentContract,
} from "./common/document-contract";
export { createFormPreset, fieldFactory, stepFactory } from "./common/form-presets";
export { createLocalFormAdmin } from "./common/form-admin";
export { attachFormDebugObserver } from "./common/form-debug";
export { createFormDebugPanel } from "./common/form-debug-panel";
export { createFormAdminPanel } from "./common/form-admin-panel";
export { createFormOpsPanel } from "./common/form-ops-panel";
export { createResumeStatusPanel } from "./common/form-resume-status-panel";
export { getPublicApiManifest } from "./common/public-api-manifest";
export { FormEngineRuntime } from "./common/form-engine";
export { FormDynamicRuntime } from "./common/form-dynamic";
export { FormPersistenceRuntime } from "./common/form-persistence";
export {
  getRemoteResumePolicy,
  getResumeShareCodeClaimPresentation,
  isRemoteResumePolicy,
  REMOTE_RESUME_CONTRACT_VERSION,
} from "./common/resume-contract";
export { FormRuntime } from "./common/form-runtime";
export { FormUploadRuntime } from "./common/form-upload";
export { FormStepRuntime } from "./common/form-steps";
export type { TStorageHealth } from "./common/form-storage";
export {
  PUBLIC_FORM_SCHEMA_VERSION,
  getPublicFormSchemaErrors,
  migratePublicFormConfig,
  validatePublicFormConfig,
} from "./common/public-schema";
export {
  createNormalizedProviderResult,
  createSubmitRequestFromProvider,
  getProviderDefinition,
  getProviderErrorEventName,
  getProviderSuccessEventName,
  isNormalizedProviderResult,
  isProviderResponseEnvelopeV2,
  normalizeProviderResult,
  PROVIDER_RESPONSE_CONTRACT_VERSION,
  resolveProviderTransition,
  registerProvider,
  validateProviderResponseEnvelopeV2,
  validateProviderRequest,
} from "./common/provider-registry";
export type {
  TLocalFormAdmin,
  TLocalFormOperationalSummary,
  TLocalFormIncidentSummary,
  TLocalQueueQuery,
} from "./common/form-admin";
export type {
  TFormDebugEventRecord,
  TFormDebugObserver,
  TFormDebugOptions,
  TFormDebugRuleRecord,
  TFormDebugSnapshot,
  TFormDebugRuleStateRecord,
  TFormDebugTemplateDiagnosticRecord,
  TFormDebugTemplateWarningStateRecord,
} from "./common/form-debug";
export type { TFormDebugPanel, TFormDebugPanelOptions } from "./common/form-debug-panel";
export type { TPublicApiManifest } from "./common/public-api-manifest";
export type { TFormActiveTemplateWarning } from "./common/form-dynamic";
export type { TFormUploadState } from "./common/form-upload";
export type { TFormStepProgress, TFormWorkflowSnapshot } from "./common/form-steps";
export type { TResumeShareCodeClaimPresentation } from "./common/resume-contract";
export type {
  TFormQueueState,
  TResumeLookupResult,
  TRemoteResumeCreateRequest,
  TRemoteResumeCreateResponse,
  TRemoteResumeInvalidateResponse,
  TRemoteResumeLookupResponse,
  TRemoteResumeOperation,
  TResumeShareCodeClaimDetail,
  TResumeShareCodeRestoreDetail,
  TResumeShareCodeInfo,
  TResumeStatusSummary,
  TResumeTokenInfo,
  TFormStorageHealth,
  TFormStorageSnapshot,
} from "./common/form-persistence";
export type {
  TRemoteResumeContractVersion,
  TRemoteResumePolicy,
  TRemoteResumePolicyCode,
} from "./common/resume-contract";
export type { TCreateFormPresetOptions, TFormPresetName } from "./common/form-presets";
export type {
  TFormProviderConfigSchema,
  TNormalizedProviderNextAction,
  TFormProviderTransition,
  TNormalizedProviderResult,
  TProviderResponseContractVersion,
  TProviderResponseEnvelopeV2,
} from "./common/provider-registry";
export type {
  TFormMediaDisplayPolicy,
  TFormOutputRendererType,
  TFormOutputSnapshot,
  TFormRuntimeDynamicAdapters,
  TFormRuntimeEmitEvent,
  TFormRuntimeOptions,
  TFormRuntimePublicApi,
  TFormRuntimeSubmitResult,
  TFormRuntimeSubmitValues,
} from "./common/form-runtime";
export type { TDocumentDataReadMode, TStoredDocumentData } from "./common/form-engine";
export type {
  TDocumentNormalizedContractVersion,
  TDocumentNormalizedFields,
  TDocumentNormalizedQuality,
  TDocumentNormalizedStatus,
  TDocumentMrzResult,
  TDocumentNormalizedContractV2,
  TDocumentScanInsight,
} from "./common/document-contract";
export type {
  TFormValidationI18nCatalog,
  TFormValidationI18nConfig,
  TFormValidationI18nMessages,
  TFormValidationMessageResolver,
  TFormValidationMessageResolverContext,
  TFormUploadPolicyContext,
  TFormUploadPolicyHook,
  TFormUploadPolicyResult,
  TFormUploadPolicyStage,
  TFormSubmitLifecycle,
  TFormSubmitLifecycleContext,
  TFormSubmitLifecycleHook,
  TFormSubmitLifecycleHookResult,
  TFormSubmitLifecycleStage,
} from "./common/TFormConfig";

export class HydratedFormHost extends HTMLElement {
  form: FormApi<any, any> | null;
  registered: Record<string, boolean>;
  formConfig: TFormConfig | null;
  engine: FormEngineRuntime;
  errors: Record<string, boolean>;
  initialized: boolean;
  dynamic: FormDynamicRuntime;
  persistence: FormPersistenceRuntime;
  upload: FormUploadRuntime;
  steps: FormStepRuntime;
  filePreviewUrls: Record<string, string[]>;
  fileDragActive: Record<string, boolean>;
  fileUploadState: Record<string, TFormUploadState | null>;
  ruleFieldErrors: Record<string, string>;
  submitLockedByRules: boolean;
  submitLockMessage: string | null;
  qrScannerState: Record<string, TQrScannerState>;
  qrScannerStreams: Record<string, MediaStream | null>;
  qrScannerTimers: Record<string, number | null>;
  qrScannerRunning: Record<string, boolean>;
  activeDocumentScanSlot: Record<string, number>;
  documentScanInsights: Record<string, TDocumentScanInsight>;
  approvalState: TFormApprovalState | null;
  workflowState: TFormWorkflowState;
  stepNames: string[];
  currentStepIndex: number;
  stepProgressContainer: HTMLElement | null;
  stepActionsContainer: HTMLElement | null;
  stepProgressElement: HTMLElement | null;
  stepProgressBar: HTMLElement | null;
  stepSummaryElement: HTMLElement | null;
  stepBackButton: HTMLButtonElement | null;
  stepNextButton: HTMLButtonElement | null;
  productListCartClickBound: boolean;
  productCartOverlay: HTMLElement | null;
  productCartCloseTimer: number | null;
  pageScrollLockCount: number;
  pageScrollPreviousOverflow: string | null;
  productGalleryOverlay: HTMLElement | null;
  overlayCleanup: (() => void) | null;
  overlayReturnFocusElement: HTMLElement | null;
  hostAriaHiddenBeforeOverlay: string | null;
  viewValues: Record<string, any>;
  stepSubmitFailed: boolean;
  suppressedStepErrorFields: Set<string>;
  outputRenderers: Record<string, TFormOutputRenderer>;
  fieldOutputRenderers: Record<string, TFieldOutputRendererOverride>;
  fieldMediaPolicies: Record<string, TMediaDisplayPolicy>;
  htmlSanitizer: TFormHtmlSanitizer;
  allowUnsafeHtml: boolean;
  documentQrRuntime: ReturnType<typeof createDocumentQrRuntime>;
  quizRuntime: ReturnType<typeof createQuizRuntime>;
  commerceRuntime: ReturnType<typeof createCommerceRuntime>;
  overlayRuntime: ReturnType<typeof createOverlayRuntime>;

  constructor() {
    super();
    this.formConfig = null;
    this.engine = new FormEngineRuntime();
    this.registered = {};
    this.errors = {}
    this.form = null;
    this.initialized = false;
    this.fileUploadState = {};
    this.ruleFieldErrors = {};
    this.submitLockedByRules = false;
    this.submitLockMessage = null;
    this.filePreviewUrls = {};
    this.fileDragActive = {};
    this.qrScannerState = {};
    this.qrScannerStreams = {};
    this.qrScannerTimers = {};
    this.qrScannerRunning = {};
    this.activeDocumentScanSlot = {};
    this.documentScanInsights = {};
    this.approvalState = null;
    this.workflowState = "draft";
    this.stepNames = [];
    this.currentStepIndex = 0;
    this.stepProgressContainer = null;
    this.stepActionsContainer = null;
    this.stepProgressElement = null;
    this.stepProgressBar = null;
    this.stepSummaryElement = null;
    this.stepBackButton = null;
    this.stepNextButton = null;
    this.productListCartClickBound = false;
    this.productCartOverlay = null;
    this.productCartCloseTimer = null;
    this.pageScrollLockCount = 0;
    this.pageScrollPreviousOverflow = null;
    this.productGalleryOverlay = null;
    this.overlayCleanup = null;
    this.overlayReturnFocusElement = null;
    this.hostAriaHiddenBeforeOverlay = null;
    this.viewValues = {};
    this.stepSubmitFailed = false;
    this.suppressedStepErrorFields = new Set();
    this.fieldOutputRenderers = {};
    this.fieldMediaPolicies = {};
    this.htmlSanitizer = createConfiguredDefaultHtmlSanitizer();
    this.outputRenderers = this.createDefaultOutputRenderers();
    this.allowUnsafeHtml = false;
    this.documentQrRuntime = createDocumentQrRuntime(this);
    this.quizRuntime = createQuizRuntime(this);
    this.commerceRuntime = createCommerceRuntime(this);
    this.overlayRuntime = createOverlayRuntime(this);
    this.dynamic = new FormDynamicRuntime({
      getFieldConfigs: () => Object.values(this.engine.getFields()),
      getRules: () => this.formConfig?.rules || [],
      getFieldContainer: (fieldName) => this.getFieldContainer(fieldName),
      getFieldElement: (fieldName) => this.getFieldElement(fieldName),
      setFieldDisabled: (fieldName, disabled) => {
        const fieldElement = this.getFieldElement(fieldName);
        if (fieldElement) {
          fieldElement.disabled = disabled;
        }
      },
      setFieldError: (fieldName, message) => {
        if (!message) {
          delete this.ruleFieldErrors[fieldName];
          this.syncFieldErrorDisplay(fieldName);
          return;
        }
        this.ruleFieldErrors[fieldName] = message;
        this.syncFieldErrorDisplay(fieldName);
      },
      clearFieldErrors: () => {
        const previousFieldNames = Object.keys(this.ruleFieldErrors);
        this.ruleFieldErrors = {};
        previousFieldNames.forEach((fieldName) => this.syncFieldErrorDisplay(fieldName));
      },
      setSubmitLocked: (locked, message) => {
        const nextLocked = Boolean(locked);
        const nextMessage = message || null;
        const changed =
          this.submitLockedByRules !== nextLocked
          || this.submitLockMessage !== nextMessage;
        this.submitLockedByRules = nextLocked;
        this.submitLockMessage = nextMessage;
        if (changed) {
          this.syncStepControls();
          this.emitFormEvent("xpressui:submit-lock", {
            values: this.form?.getState().values || {},
            formConfig: this.formConfig,
            submit: this.formConfig?.submit,
            result: {
              locked: this.submitLockedByRules,
              message: this.submitLockMessage,
            },
          });
        }
      },
      setFieldRequired: (fieldName, required) => {
        this.engine.setRequiredOverride(fieldName, required);
      },
      getSectionContainer: (sectionName) => {
        return (
          this.querySelector<HTMLElement>(`[data-type="section"][data-name="${sectionName}"]`) ?? null
        );
      },
      getFieldValue: (fieldName) => this.getFieldValue(fieldName),
      clearFieldValue: (fieldName) => {
        if (this.form) {
          this.form.change(fieldName, undefined);
        }
      },
      setFieldValue: (fieldName, value) => {
        if (this.form) {
          this.form.change(fieldName, value);
        }
      },
      getFormValues: () => this.form?.getState().values || {},
      emitEvent: (eventName, detail) =>
        this.emitFormEvent(eventName, detail as THydratedFormSubmitDetail),
      getEventContext: () => ({
        formConfig: this.formConfig,
        submit: this.formConfig?.submit,
      }),
    });
    this.steps = new FormStepRuntime();
    this.persistence = new FormPersistenceRuntime({
      getFormConfig: () => this.formConfig,
      getValues: () => this.form?.getState().values || {},
      getCurrentStepIndex: () =>
        (this.isMultiStepMode() && this.steps.getStepNames().length > 1
          ? this.steps.getCurrentStepIndex()
          : null),
      setCurrentStepIndex: (index) => this.setCurrentStepIndex(index),
      emitEvent: (eventName, detail) =>
        this.emitFormEvent(eventName, detail as THydratedFormSubmitDetail),
      submitValues: (values, submitConfig) => this.submitToApi(values, submitConfig),
    });
    this.upload = new FormUploadRuntime({
      emitEvent: (eventName, detail) => {
        const uploadState = detail.result as TFormUploadState | undefined;
        if (uploadState?.fieldNames?.length) {
          uploadState.fieldNames.forEach((fieldName) => {
            this.fileUploadState[fieldName] =
              uploadState.status === "complete" || uploadState.status === "error"
                ? uploadState
                : uploadState;
            const fieldConfig = this.engine.getField(fieldName);
            const selectionElement = this.querySelector(`#${fieldName}_selection`) as HTMLElement | null;
            if (fieldConfig) {
              this.renderFileSelection(fieldConfig, this.getFieldValue(fieldName), selectionElement);
            }
          });
        }

        return this.emitFormEvent(eventName, {
          ...(detail as THydratedFormSubmitDetail),
          formConfig: this.formConfig,
        });
      },
    });
  }

  get validators(): TValidator[] {
    return this.engine.validators;
  }

  get inputFields(): Record<string, TFieldConfig> {
    return this.engine.getFields();
  }

  connectedCallback() {
    if (!this.initialized) {
      this.initialize();
    }
  }

  disconnectedCallback() {
    this.stopAllQrCameras();
    this.clearAllFilePreviewUrls();
    if (this.productGalleryOverlay) {
      this.productGalleryOverlay.remove();
      this.productGalleryOverlay = null;
    }
    if (this.productCartOverlay) {
      this.productCartOverlay.remove();
      this.productCartOverlay = null;
    }
    if (this.productCartCloseTimer !== null && typeof window !== "undefined") {
      window.clearTimeout(this.productCartCloseTimer);
      this.productCartCloseTimer = null;
    }
    if (typeof document !== "undefined" && this.pageScrollLockCount > 0) {
      const body = document.body;
      if (body) {
        body.style.overflow = this.pageScrollPreviousOverflow || "";
      }
      this.pageScrollLockCount = 0;
      this.pageScrollPreviousOverflow = null;
    }
    this.teardownOverlayAccessibility(false);
    this.productListCartClickBound = false;
    this.persistence.disconnect();
  }

  normalizeViewValue = (value: any): any => {
    return normalizeConfiguredViewValue(value);
  }

  getDisplayText = (value: any): string => {
    return getConfiguredDisplayText(value);
  }

  getFileMeta = (value: any): { href: string; label: string } | null => {
    return getConfiguredFileMeta(value);
  }

  getFileMetas = (value: any): Array<{ href: string; label: string }> => {
    return getConfiguredFileMetas(value);
  }

  getMediaSources = (value: any): string[] => {
    return getConfiguredMediaSources(value);
  }

  isEmbeddableDocumentSource = (source: string): boolean => {
    return isConfiguredEmbeddableDocumentSource(source);
  }

  getMapSources = (value: any): string[] => {
    return getConfiguredMapSources(value);
  }

  isSafeMapEmbedSource = (source: string): boolean => {
    return isConfiguredSafeMapEmbedSource(source);
  }

  resolveMediaDisplayPolicy = (
    fieldConfig: TFieldConfig,
    inputElement: HTMLElement | null,
    rendererType?: string,
  ): TMediaDisplayPolicy => {
    return resolveConfiguredMediaDisplayPolicy(
      this.fieldMediaPolicies,
      fieldConfig,
      inputElement,
      rendererType,
    );
  }

  createDefaultOutputRenderers = (): Record<TOutputRendererType, TFormOutputRenderer> => {
    return createConfiguredDefaultOutputRenderers({
      htmlSanitizer: (html, context) => this.htmlSanitizer(html, context),
    });
  }

  getRenderMode = (): TFormRenderMode => {
    const mode = (this.getAttribute("mode") || "").trim().toLowerCase();
    if (
      mode === "view"
      || mode === "hybrid"
      || mode === "form-multi-step"
      || mode === "view-multi-step"
    ) {
      return mode;
    }

    return "form";
  }

  isMultiStepMode = (): boolean => {
    const mode = this.getRenderMode();
    return mode === "form-multi-step" || mode === "view-multi-step";
  }

  getBaseRenderMode = (): "form" | "view" | "hybrid" => {
    const mode = this.getRenderMode();
    if (mode === "view" || mode === "view-multi-step") {
      return "view";
    }
    if (mode === "hybrid") {
      return "hybrid";
    }
    return "form";
  }

  setViewValues = (values: Record<string, any>) => {
    this.viewValues = values && typeof values === "object" ? { ...values } : {};
    if (this.initialized && this.getBaseRenderMode() === "view") {
      const formElement = this.querySelector("form") as HTMLFormElement | null;
      if (formElement) {
        this.applyViewMode(formElement);
      }
    }

    if (this.initialized && this.getBaseRenderMode() === "hybrid" && this.form) {
      Object.entries(this.viewValues).forEach(([fieldName, fieldValue]) => {
        this.form?.change(fieldName, fieldValue);
      });
    }
  }

  getViewValues = (): Record<string, any> => {
    return { ...this.viewValues };
  }

  defaultHtmlSanitizer: TFormHtmlSanitizer = createConfiguredDefaultHtmlSanitizer()

  escapeHtml = (value: string): string => {
    return escapeConfiguredHtml(value);
  }

  readTemplateTokenValue = (values: Record<string, any>, tokenPath: string): any => {
    return readConfiguredTemplateTokenValue(values, tokenPath);
  }

  renderViewTemplate = (
    template: string,
    values: Record<string, any>,
    escapeValues: boolean,
  ): string => {
    return renderConfiguredViewTemplate(template, values, escapeValues);
  }

  resolveViewTemplateValue = (
    fieldConfig: TFieldConfig,
    inputElement: HTMLElement,
    rendererType: string,
    value: any,
    valuesContext?: Record<string, any>,
  ): any => {
    return resolveConfiguredViewTemplateValue(
      fieldConfig,
      inputElement,
      rendererType,
      value,
      valuesContext,
    );
  }

  setHtmlSanitizer = (sanitizer: TFormHtmlSanitizer) => {
    if (typeof sanitizer !== "function") {
      return;
    }

    this.htmlSanitizer = sanitizer;
    this.refreshOutputRendering();
  }

  resetHtmlSanitizer = () => {
    this.htmlSanitizer = this.defaultHtmlSanitizer;
    this.refreshOutputRendering();
  }

  setAllowUnsafeHtml = (enabled: boolean) => {
    this.allowUnsafeHtml = Boolean(enabled);
    this.refreshOutputRendering();
  }

  shouldRenderUnsafeHtml = (inputElement: HTMLElement | null): boolean => {
    return shouldConfiguredRenderUnsafeHtml(this, this.allowUnsafeHtml, inputElement);
  }

  setOutputRenderer = (rendererType: string, renderer: TFormOutputRenderer) => {
    if (!rendererType || typeof renderer !== "function") {
      return;
    }

    this.outputRenderers[rendererType] = renderer;
    this.refreshOutputRendering();
  }

  removeOutputRenderer = (rendererType: string) => {
    if (!rendererType || !this.outputRenderers[rendererType]) {
      return;
    }

    delete this.outputRenderers[rendererType];
    this.refreshOutputRendering();
  }

  setFieldOutputRenderer = (fieldName: string, renderer: TFieldOutputRendererOverride) => {
    if (!fieldName || !renderer) {
      return;
    }

    this.fieldOutputRenderers[fieldName] = renderer;
    this.refreshOutputRendering();
  }

  clearFieldOutputRenderer = (fieldName: string) => {
    if (!fieldName || !this.fieldOutputRenderers[fieldName]) {
      return;
    }

    delete this.fieldOutputRenderers[fieldName];
    this.refreshOutputRendering();
  }

  setFieldMediaPolicy = (fieldName: string, policy: TMediaDisplayPolicy) => {
    if (
      !fieldName
      || (policy !== "thumbnail" && policy !== "large" && policy !== "link" && policy !== "gallery")
    ) {
      return;
    }

    this.fieldMediaPolicies[fieldName] = policy;
    this.refreshOutputRendering();
  }

  clearFieldMediaPolicy = (fieldName: string) => {
    if (!fieldName || !this.fieldMediaPolicies[fieldName]) {
      return;
    }

    delete this.fieldMediaPolicies[fieldName];
    this.refreshOutputRendering();
  }

  resolveOutputRendererForField = (
    fieldConfig: TFieldConfig,
    inputElement: HTMLElement,
  ): { rendererType: string; renderer: TFormOutputRenderer } => {
    return resolveConfiguredOutputRendererForField({
      fieldConfig,
      inputElement,
      outputRenderers: this.outputRenderers,
      fieldOutputRenderers: this.fieldOutputRenderers,
      getOutputRendererType: (nextFieldConfig) => this.getOutputRendererType(nextFieldConfig),
    });
  }

  getOutputSnapshot = (values?: Record<string, any>) => {
    const formElem = this.querySelector("form") as HTMLFormElement | null;
    if (!formElem) {
      return {};
    }

    const mode = this.getBaseRenderMode();
    const fallbackValues = mode === "hybrid"
      ? (this.form?.getState().values || this.getInitialViewValues(formElem))
      : this.getInitialViewValues(formElem);
    const currentValues = values || fallbackValues;
    const snapshot: Record<string, { rendererType: string; mediaDisplayPolicy: TMediaDisplayPolicy; value: any }> = {};

    Array.from(formElem.elements).forEach((node) => {
      const fieldConfig = getFieldConfig(node);
      if (!fieldConfig?.name || fieldConfig.type === UNKNOWN_TYPE) {
        return;
      }

      const inputElement = this.querySelector(`#${fieldConfig.name}`) as HTMLElement | null;
      if (!inputElement) {
        return;
      }

      const { rendererType } = this.resolveOutputRendererForField(fieldConfig, inputElement);
      const mediaDisplayPolicy = this.resolveMediaDisplayPolicy(fieldConfig, inputElement, rendererType);
      snapshot[fieldConfig.name] = {
        rendererType,
        mediaDisplayPolicy,
        value: currentValues[fieldConfig.name],
      };
    });

    return snapshot;
  }

  emitOutputSnapshot = (values?: Record<string, any>) => {
    const snapshot = this.getOutputSnapshot(values);
    this.emitFormEvent("xpressui:output-snapshot", {
      values:
        values
        || this.form?.getState().values
        || {},
      formConfig: this.formConfig,
      submit: this.formConfig?.submit,
      result: snapshot,
    });
  }

  refreshOutputRendering = () => {
    if (!this.initialized) {
      return;
    }

    const formElem = this.querySelector("form") as HTMLFormElement | null;
    if (!formElem) {
      return;
    }

    const mode = this.getBaseRenderMode();
    if (mode === "view") {
      this.applyViewMode(formElem);
      this.emitOutputSnapshot(this.getInitialViewValues(formElem));
      return;
    }

    if (mode === "hybrid") {
      const values = this.form?.getState().values || this.getInitialViewValues(formElem);
      this.applyHybridMode(formElem, values);
      this.emitOutputSnapshot(values);
    }
  }

  readViewValuesAttribute = (): Record<string, any> => {
    return readConfiguredViewValuesAttribute(this);
  }

  collectDomFieldValues = (formElem: HTMLFormElement): Record<string, any> => {
    return collectDomFieldValues(formElem);
  }

  getInitialViewValues = (formElem: HTMLFormElement): Record<string, any> => {
    const domValues = this.collectDomFieldValues(formElem);
    const persistedValues = this.persistence.loadDraftValues();
    const attributeValues = this.readViewValuesAttribute();
    return {
      ...domValues,
      ...(persistedValues || {}),
      ...attributeValues,
      ...this.viewValues,
    };
  }

  getOutputRendererType = (fieldConfig: TFieldConfig): TOutputRendererType => {
    return getFieldOutputRendererType(fieldConfig);
  }

  isFieldViewMode = (fieldConfig: TFieldConfig, inputElement: HTMLElement | null): boolean => {
    return isConfiguredFieldViewMode(fieldConfig, inputElement);
  }

  readInputElementValue = (
    fieldConfig: TFieldConfig,
    inputElement: HTMLElement | null,
  ): any => {
    return readFieldInputElementValue(
      (nextFieldConfig) => this.isProductListField(nextFieldConfig),
      (nextFieldConfig) => this.isImageGalleryField(nextFieldConfig),
      (nextFieldConfig) => this.isQuizField(nextFieldConfig),
      (nextFieldConfig) => this.isChoiceListField(nextFieldConfig),
      fieldConfig,
      inputElement,
    );
  }

  resolveFieldViewValue = (
    fieldConfig: TFieldConfig,
    inputElement: HTMLElement | null,
    stateValue: any,
  ): any => {
    return resolveConfiguredFieldViewValue(
      this,
      fieldConfig,
      inputElement,
      stateValue,
      this.viewValues,
      (nextFieldConfig, nextInputElement) => this.readInputElementValue(nextFieldConfig, nextInputElement),
    );
  }

  applyFieldViewPresentation = (
    fieldConfig: TFieldConfig,
    inputElement: HTMLElement | null,
    selectionElement: HTMLElement | null,
    errorElement: HTMLElement | null,
    stateValue: any,
  ) => {
    if (!inputElement) {
      return;
    }

    const viewValue = this.resolveFieldViewValue(fieldConfig, inputElement, stateValue);
    this.renderViewField(
      fieldConfig,
      viewValue,
      inputElement,
      "view",
      this.form?.getState().values || {},
    );
    inputElement.style.display = "none";
    inputElement.setAttribute("aria-hidden", "true");
    if (
      inputElement instanceof HTMLInputElement
      || inputElement instanceof HTMLSelectElement
      || inputElement instanceof HTMLTextAreaElement
    ) {
      inputElement.disabled = true;
    }
    if (selectionElement) {
      selectionElement.style.display = "none";
      selectionElement.setAttribute("aria-hidden", "true");
    }
    if (errorElement) {
      errorElement.style.display = "none";
    }
  }

  renderViewField = (
    fieldConfig: TFieldConfig,
    value: any,
    inputElement: HTMLElement,
    modeOverride?: TFormRenderMode,
    valuesContext?: Record<string, any>,
  ) => {
    const viewFieldId = `${fieldConfig.name}_view`;
    let viewElement =
      (this.querySelector(`#${viewFieldId}`) as HTMLElement | null)
      || (this.querySelector(`[data-view-field="${fieldConfig.name}"]`) as HTMLElement | null);
    if (!viewElement) {
      viewElement = document.createElement("div");
      viewElement.setAttribute("data-view-field", fieldConfig.name);
      inputElement.insertAdjacentElement("afterend", viewElement);
    }
    viewElement.id = viewFieldId;

    const { rendererType, renderer } = this.resolveOutputRendererForField(fieldConfig, inputElement);
    viewElement.setAttribute("data-renderer-type", rendererType);
    const mediaDisplayPolicy = this.resolveMediaDisplayPolicy(fieldConfig, inputElement, rendererType);
    viewElement.setAttribute("data-media-display-policy", mediaDisplayPolicy);
    const viewBody = this.ensureSelectionChild(
      viewElement,
      `[data-view-field-body="${fieldConfig.name}"]`,
      "div",
      "",
      "data-view-field-body",
      fieldConfig.name,
    );
    const unsafeHtml = this.shouldRenderUnsafeHtml(inputElement);
    const resolvedValue = this.resolveViewTemplateValue(
      fieldConfig,
      inputElement,
      rendererType,
      value,
      valuesContext,
    );
    const rendered = renderer({
      fieldConfig,
      value: resolvedValue,
      mode: modeOverride || this.getRenderMode(),
      unsafeHtml,
      mediaDisplayPolicy,
    });
    viewBody.replaceChildren(rendered);
  }

  applyViewMode = (formElem: HTMLFormElement) => {
    const values = this.getInitialViewValues(formElem);
    Array.from(formElem.elements).forEach((node) => {
      const fieldConfig = getFieldConfig(node);
      if (!fieldConfig?.name || fieldConfig.type === UNKNOWN_TYPE) {
        return;
      }

      const inputElement = this.querySelector(`#${fieldConfig.name}`) as HTMLElement | null;
      if (!inputElement) {
        return;
      }

      this.renderViewField(fieldConfig, values[fieldConfig.name], inputElement, undefined, values);
      inputElement.style.display = "none";
      inputElement.setAttribute("aria-hidden", "true");
      if (
        inputElement instanceof HTMLInputElement ||
        inputElement instanceof HTMLSelectElement ||
        inputElement instanceof HTMLTextAreaElement
      ) {
        inputElement.disabled = true;
      }

      const errorElement = this.querySelector(`#${fieldConfig.name}_error`) as HTMLElement | null;
      if (errorElement) {
        errorElement.style.display = "none";
      }

      const selectionElement = this.querySelector(`#${fieldConfig.name}_selection`) as HTMLElement | null;
      if (selectionElement) {
        selectionElement.style.display = "none";
      }
    });

    Array.from(formElem.querySelectorAll('button[type="submit"], input[type="submit"]')).forEach((button) => {
      (button as HTMLElement).style.display = "none";
    });
  }

  applyHybridMode = (formElem: HTMLFormElement, values?: Record<string, any>) => {
    const modeValues = values || this.getInitialViewValues(formElem);
    Array.from(formElem.elements).forEach((node) => {
      const fieldConfig = getFieldConfig(node);
      if (!fieldConfig?.name || fieldConfig.type === UNKNOWN_TYPE) {
        return;
      }

      const inputElement = this.querySelector(`#${fieldConfig.name}`) as HTMLElement | null;
      if (!inputElement) {
        return;
      }

      this.renderViewField(fieldConfig, modeValues[fieldConfig.name], inputElement, undefined, modeValues);
    });
  }

  getFileValueList = getFileValueList

  isQrScanField = isQrScanField

  isDocumentScanField = isDocumentScanField

  getDocumentScanSlotCount = getDocumentScanSlotCount

  getDocumentScanInsight = (fieldConfig: TFieldConfig): TDocumentScanInsight =>
    getDocumentScanInsight(this.documentScanInsights, fieldConfig)

  buildDocumentNormalizedContract = createNormalizedDocumentContract

  resolveFileInputValue = async (fieldConfig: TFieldConfig, input: HTMLInputElement) => {
    const files = input.files?.[0]; // single file only

    if (this.isDocumentScanField(fieldConfig)) {
      const selectedFile = Array.isArray(files) ? files[0] : files;
      if (!selectedFile) {
        return undefined;
      }

      const currentFiles = this.getFileValueList(this.getFieldValue(fieldConfig.name));
      const slotCount = this.getDocumentScanSlotCount(fieldConfig);
      const activeSlot = Math.min(
        Math.max(this.activeDocumentScanSlot[fieldConfig.name] || 0, 0),
        slotCount - 1,
      );
      const nextFiles = Array.from(
        { length: slotCount },
        (_, index) => currentFiles[index] instanceof File ? currentFiles[index] : undefined,
      );
      const croppedFile = await this.cropDocumentScanFile(fieldConfig, selectedFile, activeSlot);
      nextFiles[activeSlot] = croppedFile;
      await this.analyzeDocumentScanFile(fieldConfig, croppedFile, activeSlot);
      return slotCount === 1 ? nextFiles[0] : nextFiles;
    }

    if (!this.isQrScanField(fieldConfig)) {
      return files;
    }

    const fileList = Array.isArray(files)
      ? files
      : files
        ? [files]
        : [];

    const fileValidationError = this.engine.validateFileField(fieldConfig.name, files);
    if (fileValidationError) {
      this.emitFileValidationErrorEvent(
        fieldConfig.name,
        {
          ...(this.form?.getState().values || {}),
          [fieldConfig.name]: files,
        },
        fileValidationError as TValidationError,
      );
      return undefined;
    }

    if (!fileList.length) {
      return undefined;
    }

    const qrValue = await this.decodeQrScanFile(fieldConfig, fileList[0]);
    if (qrValue !== null) {
      return qrValue;
    }

    return undefined;
  }

  getBarcodeDetector = () => this.documentQrRuntime.getBarcodeDetector()

  getTextDetector = () => this.documentQrRuntime.getTextDetector()

  getMrzCharValue = (char: string) => this.documentQrRuntime.getMrzCharValue(char)

  computeMrzChecksum = (input: string) => this.documentQrRuntime.computeMrzChecksum(input)

  validateMrzChecksum = (source: string, checkDigit?: string) =>
    this.documentQrRuntime.validateMrzChecksum(source, checkDigit)

  computeMrzValidity = (checksums?: TDocumentMrzResult["checksums"]) =>
    this.documentQrRuntime.computeMrzValidity(checksums)

  parseMrz = (text: string): TDocumentMrzResult | null => this.documentQrRuntime.parseMrz(text)

  getDocumentCropBounds = (width: number, height: number) =>
    this.documentQrRuntime.getDocumentCropBounds(width, height)

  getDocumentPerspectiveCorners = (bounds: ReturnType<HydratedFormHost["getDocumentCropBounds"]>) =>
    this.documentQrRuntime.getDocumentPerspectiveCorners(bounds)

  drawPerspectiveCorrectedDocument = (
    context: CanvasRenderingContext2D,
    imageBitmap: ImageBitmap,
    bounds: ReturnType<HydratedFormHost["getDocumentCropBounds"]>,
    corners: TDocumentPerspectiveCorners,
  ) => this.documentQrRuntime.drawPerspectiveCorrectedDocument(context, imageBitmap, bounds, corners)

  cropDocumentScanFile = async (fieldConfig: TFieldConfig, file: File, slotIndex: number) =>
    this.documentQrRuntime.cropDocumentScanFile(fieldConfig, file, slotIndex)

  analyzeDocumentScanFile = async (fieldConfig: TFieldConfig, file: File, slotIndex: number) =>
    this.documentQrRuntime.analyzeDocumentScanFile(fieldConfig, file, slotIndex)

  assignQrVideoStream = (fieldName: string) => this.documentQrRuntime.assignQrVideoStream(fieldName)

  clearQrScanTimer = (fieldName: string) => this.documentQrRuntime.clearQrScanTimer(fieldName)

  stopQrCamera = (fieldName: string, rerender: boolean = true) =>
    this.documentQrRuntime.stopQrCamera(fieldName, rerender)

  stopAllQrCameras = () => this.documentQrRuntime.stopAllQrCameras()

  scheduleContinuousQrScan = (fieldConfig: TFieldConfig) =>
    this.documentQrRuntime.scheduleContinuousQrScan(fieldConfig)

  startQrCamera = async (fieldConfig: TFieldConfig) => this.documentQrRuntime.startQrCamera(fieldConfig)

  scanQrFromLiveVideo = async (fieldConfig: TFieldConfig, silent: boolean = false) =>
    this.documentQrRuntime.scanQrFromLiveVideo(fieldConfig, silent)

  decodeQrScanFile = async (fieldConfig: TFieldConfig, file: File) =>
    this.documentQrRuntime.decodeQrScanFile(fieldConfig, file)

  clearFilePreviewUrls = (fieldName: string) => {
    const urls = this.filePreviewUrls[fieldName] || [];
    if (typeof URL !== "undefined" && typeof URL.revokeObjectURL === "function") {
      urls.forEach((url) => {
        URL.revokeObjectURL(url);
      });
    }
    this.filePreviewUrls[fieldName] = [];
  }

  clearAllFilePreviewUrls = () => {
    Object.keys(this.filePreviewUrls).forEach((fieldName) => {
      this.clearFilePreviewUrls(fieldName);
    });
  }

  shouldShowImagePreview = (fieldConfig: TFieldConfig, file: File) => {
    const accept = String(fieldConfig.accept || "").toLowerCase();
    return accept.includes("image/*") && String(file.type || "").startsWith("image/");
  }

  isProductListField = (fieldConfig: TFieldConfig) => {
    return fieldConfig.type === PRODUCT_LIST_TYPE;
  }

  isImageGalleryField = (fieldConfig: TFieldConfig) => {
    return fieldConfig.type === IMAGE_GALLERY_TYPE || fieldConfig.type === SELECT_PRODUCT_TYPE;
  }

  isQuizField = (fieldConfig: TFieldConfig) => {
    return fieldConfig.type === QUIZ_TYPE;
  }

  isChoiceListField = (fieldConfig: TFieldConfig) => {
    return fieldConfig.type === RADIO_BUTTONS_TYPE || fieldConfig.type === CHECKBOXES_TYPE;
  }

  isOpenQuizField = (fieldConfig: TFieldConfig) => {
    return isConfiguredOpenQuizField(fieldConfig);
  }

  isSettingField = (fieldConfig: TFieldConfig) => {
    return fieldConfig.type === SETTING_TYPE;
  }

  getSettingInitialValue = (inputElement: HTMLElement | null): any => {
    if (!inputElement) {
      return "";
    }
    const rawValue =
      inputElement.getAttribute("data-setting-value")
      || (inputElement instanceof HTMLInputElement ? inputElement.value : "");
    if (!rawValue) {
      return "";
    }
    try {
      return JSON.parse(rawValue);
    } catch {
      return rawValue;
    }
  }

  getProductListCatalog = (fieldConfig: TFieldConfig): TProductListItem[] => {
    return getProductListCatalogItems(fieldConfig);
  }

  getImageGalleryCatalog = (fieldConfig: TFieldConfig): TImageGalleryItem[] => {
    return getImageGalleryCatalogItems(fieldConfig);
  }

  getImageGallerySelectionLimit = (fieldConfig: TFieldConfig) => {
    if (fieldConfig.type === SELECT_PRODUCT_TYPE) {
      return 1;
    }

    const catalogSize = this.getImageGalleryCatalog(fieldConfig).length;
    const requestedMax = Number(
      fieldConfig.maxNumOfChoices
      ?? (
        Array.isArray(fieldConfig.choices) && fieldConfig.choices.length > 0
          ? fieldConfig.choices[0]?.maxNumOfChoices
          : undefined
      ),
    );
    if (!Number.isFinite(requestedMax) || requestedMax <= 0) {
      return catalogSize || 0;
    }

    const normalizedMax = Math.round(requestedMax);
    return catalogSize > 0 ? Math.min(normalizedMax, catalogSize) : normalizedMax;
  }

  getProductCartItems = (value: any): TProductCartItem[] => {
    return getNormalizedProductCartItems(value);
  }

  getImageGallerySelectionItems = (value: any): TImageGalleryItem[] => {
    return getImageGallerySelectionItems(value);
  }

  getQuizCatalog = (fieldConfig: TFieldConfig) => {
    return this.quizRuntime.getQuizCatalog(fieldConfig);
  }

  getQuizSelectionItems = (value: any) => {
    return this.quizRuntime.getQuizSelectionItems(value);
  }

  getChoiceSelectionItems = (fieldConfig: TFieldConfig, value: any): string[] => {
    if (fieldConfig.type === RADIO_BUTTONS_TYPE) {
      return typeof value === "string" && value ? [value] : [];
    }
    return Array.isArray(value) ? value.map((entry) => String(entry)) : [];
  }

  getQuizSelectionLimit = (fieldConfig: TFieldConfig) => {
    return this.quizRuntime.getQuizSelectionLimit(fieldConfig);
  }

  getNextQuizSelectionItems = (
    fieldConfig: TFieldConfig,
    currentValue: any,
    answerId: string,
  ) => {
    return this.quizRuntime.getNextQuizSelectionItems(fieldConfig, currentValue, answerId);
  }

  getNextChoiceSelectionValue = (
    fieldConfig: TFieldConfig,
    currentValue: any,
    choiceValue: string,
  ) => {
    if (fieldConfig.type === RADIO_BUTTONS_TYPE) {
      return currentValue === choiceValue ? "" : choiceValue;
    }

    const currentValues = Array.isArray(currentValue)
      ? currentValue.map((entry) => String(entry))
      : [];
    if (currentValues.includes(choiceValue)) {
      return currentValues.filter((entry) => entry !== choiceValue);
    }
    const maxChoices = typeof fieldConfig.maxNumOfChoices === "number" ? fieldConfig.maxNumOfChoices : null;
    if (maxChoices !== null && maxChoices > 0 && currentValues.length >= maxChoices) {
      return currentValues;
    }
    return [...currentValues, choiceValue];
  }

  getProductCartTotal = (cartItems: TProductCartItem[]): number => {
    return getNormalizedProductCartTotal(cartItems);
  }

  createCartGlyph = () => {
    return this.overlayRuntime.createCartGlyph();
  }

  createCartCountBadge = (value: string) => {
    return this.overlayRuntime.createCartCountBadge(value);
  }

  updateProductListInlineTotal = (fieldConfig: TFieldConfig, value: any) => {
    return this.commerceRuntime.updateProductListInlineTotal(fieldConfig, value);
  }

  getProductCartEntries = (): Array<{ fieldName: string; item: TProductCartItem }> => {
    return this.commerceRuntime.getProductCartEntries();
  }

  ensureProductCartTrigger = (): HTMLElement | null => {
    return this.overlayRuntime.ensureProductCartTrigger();
  }

  ensureProductListGlobalCart = (): HTMLElement | null => {
    return this.overlayRuntime.ensureProductListGlobalCart();
  }

  openProductCartModal = () => {
    return this.overlayRuntime.openProductCartModal();
  }

  closeProductCartModal = () => {
    return this.overlayRuntime.closeProductCartModal();
  }

  acquirePageScrollLock = () => {
    return this.overlayRuntime.acquirePageScrollLock();
  }

  releasePageScrollLock = () => {
    return this.overlayRuntime.releasePageScrollLock();
  }

  getFocusableElements = (container: HTMLElement): HTMLElement[] => {
    return this.overlayRuntime.getFocusableElements(container);
  }

  applyHostAriaHiddenForOverlay = () => {
    return this.overlayRuntime.applyHostAriaHiddenForOverlay();
  }

  restoreHostAriaHiddenAfterOverlay = () => {
    return this.overlayRuntime.restoreHostAriaHiddenAfterOverlay();
  }

  setupOverlayAccessibility = (
    overlay: HTMLElement,
    dialog: HTMLElement,
    onEscape: () => void,
    preferredFocusElement?: HTMLElement | null,
  ) => {
    return this.overlayRuntime.setupOverlayAccessibility(
      overlay,
      dialog,
      onEscape,
      preferredFocusElement,
    );
  }

  teardownOverlayAccessibility = (restoreFocus: boolean = true) => {
    return this.overlayRuntime.teardownOverlayAccessibility(restoreFocus);
  }

  renderProductListGlobalCart = () => {
    return this.commerceRuntime.renderProductListGlobalCart();
  }

  bindProductListGlobalCartEvents = () => {
    return this.commerceRuntime.bindProductListGlobalCartEvents();
  }

  openMediaGallery = (name: string, photos: string[]) => {
    return this.commerceRuntime.openMediaGallery(name, photos);
  }

  openProductListGallery = (product: TProductListItem) => {
    return this.commerceRuntime.openProductListGallery(product);
  }

  openImageGalleryItem = (item: TImageGalleryItem) => {
    return this.commerceRuntime.openImageGalleryItem(item);
  }

  getNextProductCartItems = (
    fieldConfig: TFieldConfig,
    currentValue: any,
    action: "add" | "inc" | "dec" | "remove",
    productId: string,
  ): TProductCartItem[] => {
    return this.commerceRuntime.getNextProductCartItems(fieldConfig, currentValue, action, productId);
  }

  getNextImageGallerySelectionItems = (
    fieldConfig: TFieldConfig,
    currentValue: any,
    action: "toggle" | "remove",
    imageId: string,
  ): TImageGalleryItem[] => {
    return this.commerceRuntime.getNextImageGallerySelectionItems(fieldConfig, currentValue, action, imageId);
  }

  renderProductListSelection = (
    fieldConfig: TFieldConfig,
    value: any,
    selectionElement: HTMLElement | null,
  ) => this.commerceRuntime.renderProductListSelection(fieldConfig, value, selectionElement)

  renderImageGallerySelection = (
    fieldConfig: TFieldConfig,
    value: any,
    selectionElement: HTMLElement | null,
  ) => this.commerceRuntime.renderImageGallerySelection(fieldConfig, value, selectionElement)

  private resolveChoiceLayout(
    fieldConfig: TFieldConfig,
    container: HTMLElement | null,
    fallbackLayout: "horizontal" | "vertical",
  ): "horizontal" | "vertical" {
    const explicitLayout = fieldConfig.layout;
    if (explicitLayout === "horizontal" || explicitLayout === "vertical") {
      return explicitLayout;
    }

    const shellLayout = container?.getAttribute("data-choice-layout");
    if (shellLayout === "horizontal" || shellLayout === "vertical") {
      return shellLayout;
    }

    return fallbackLayout;
  }

  private applyChoiceLayout(
    container: HTMLElement,
    layout: "horizontal" | "vertical",
    minWidth = "220px",
  ) {
    container.style.display = "grid";
    container.style.gap = "8px";
    container.setAttribute("data-choice-layout", layout);
    container.style.gridTemplateColumns =
      layout === "vertical" ? "1fr" : `repeat(auto-fit, minmax(${minWidth}, 1fr))`;
  }

  private getChoiceDisplayLabel(choice: Record<string, any>, fallback = "") {
    return String(choice.label ?? choice.title ?? choice.name ?? choice.value ?? choice.id ?? fallback);
  }

  private isCompactChoiceCard(label: string, desc?: string, previewSrc?: string | null) {
    return !previewSrc && !desc && label.trim().length <= 20;
  }

  renderQuizSelection = (
    fieldConfig: TFieldConfig,
    value: any,
    selectionElement: HTMLElement | null,
  ) => this.quizRuntime.renderQuizSelection(fieldConfig, value, selectionElement)

  renderChoiceListSelection = (
    fieldConfig: TFieldConfig,
    value: any,
    selectionElement: HTMLElement | null,
  ) => {
    if (!selectionElement) {
      return;
    }

    const choices = fieldConfig.choices || [];
    const selectedValues = this.getChoiceSelectionItems(fieldConfig, value);
    const selectedMap = selectedValues.reduce((accumulator, item) => {
      accumulator[item] = true;
      return accumulator;
    }, {} as Record<string, boolean>);
    const selectionLimit =
      fieldConfig.type === CHECKBOXES_TYPE && typeof fieldConfig.maxNumOfChoices === "number"
        ? fieldConfig.maxNumOfChoices
        : 1;
    const limitReached =
      fieldConfig.type === CHECKBOXES_TYPE && selectionLimit > 0 && selectedValues.length >= selectionLimit;

    const grid =
      (selectionElement.querySelector(
        `[data-choice-list-grid="${fieldConfig.name}"]`,
      ) as HTMLDivElement | null)
      ?? (selectionElement as HTMLDivElement);
    if (grid === selectionElement) {
      grid.setAttribute("data-choice-list-grid", fieldConfig.name);
    }
    const choiceLayout = this.resolveChoiceLayout(fieldConfig, grid, "vertical");
    this.applyChoiceLayout(grid, choiceLayout);

    choices.forEach((choice) => {
      const optionValue = String(choice.value ?? choice.id ?? choice.label ?? "");
      const selected = Boolean(selectedMap[optionValue]);
      const disabled = !selected && limitReached;

      let card = grid.querySelector(`[data-choice-option-value="${optionValue}"]`) as HTMLDivElement | null;
      if (!card) {
        card = document.createElement("div");
        grid.appendChild(card);
      }
      card.setAttribute("data-choice-option-action", "toggle");
      card.setAttribute("data-choice-option-value", optionValue);
      card.setAttribute("data-selected", selected ? "true" : "false");
      card.setAttribute("data-disabled", disabled ? "true" : "false");
      card.setAttribute("role", "button");
      card.setAttribute("tabindex", disabled ? "-1" : "0");
      card.setAttribute("aria-pressed", selected ? "true" : "false");
      card.className = "template-choice-card";
      card.style.cursor = disabled ? "not-allowed" : "pointer";
      card.style.opacity = disabled ? "0.55" : "1";
      card.style.borderColor = selected ? "rgba(15, 118, 110, 0.38)" : "";
      card.style.boxShadow = selected ? "0 0 0 2px rgba(15, 118, 110, 0.12)" : "";
      card.style.background = selected ? "rgba(240, 253, 250, 0.96)" : "rgba(255, 255, 255, 0.98)";

      let title = card.querySelector(`[data-choice-option-title="${optionValue}"]`) as HTMLDivElement | null;
      if (!title) {
        title = document.createElement("div");
        title.setAttribute("data-choice-option-title", optionValue);
        card.appendChild(title);
      }
      title.className = "template-choice-title";
      title.style.overflowWrap = "anywhere";
      title.textContent = String(choice.label ?? optionValue);

      let description = card.querySelector(
        `[data-choice-option-description="${optionValue}"]`,
      ) as HTMLDivElement | null;
      if (choice.desc) {
        if (!description) {
          description = document.createElement("div");
          description.setAttribute("data-choice-option-description", optionValue);
          card.appendChild(description);
        }
        description.className = "template-field-help";
        description.style.overflowWrap = "anywhere";
        description.textContent = choice.desc;
      } else {
        description?.remove();
      }

      let footer = card.querySelector(`[data-choice-option-footer="${optionValue}"]`) as HTMLDivElement | null;
      if (!footer) {
        footer = document.createElement("div");
        footer.setAttribute("data-choice-option-footer", optionValue);
        card.appendChild(footer);
      }
      footer.className = "template-choice-footer";
      footer.hidden = !disabled;
      footer.textContent = disabled ? "Limit reached" : "";
    });
    Array.from(grid.querySelectorAll("[data-choice-option-value]")).forEach((node) => {
      const optionValue = (node as HTMLElement).getAttribute("data-choice-option-value");
      if (optionValue && !choices.some((choice) => String(choice.value ?? choice.id ?? choice.label ?? "") === optionValue)) {
        node.remove();
      }
    });
  }

  renderDocumentScanSelection = (
    fieldConfig: TFieldConfig,
    selectedFiles: any[],
    selectionElement: HTMLElement,
  ) => this.documentQrRuntime.renderDocumentScanSelection(fieldConfig, selectedFiles, selectionElement)

  renderQrSelection = (fieldConfig: TFieldConfig, value: any, selectionElement: HTMLElement) =>
    this.documentQrRuntime.renderQrSelection(fieldConfig, value, selectionElement)

  renderFileSelection = (
    fieldConfig: TFieldConfig,
    value: any,
    selectionElement: HTMLElement | null,
  ) => {
    if (!selectionElement) {
      return;
    }

    const selectedFiles = this.getFileValueList(value);
    const isDragActive = Boolean(this.fileDragActive[fieldConfig.name]);
    const uploadState = this.fileUploadState[fieldConfig.name];
    const isDocumentScan = this.isDocumentScanField(fieldConfig);
    const isQrScan = this.isQrScanField(fieldConfig);
    const titleElement = this.ensureUploadSelectionTextNode(
      selectionElement,
      fieldConfig.name,
      "data-upload-selection-title",
      "div",
      "text-sm font-medium",
    );
    const messageElement = this.ensureUploadSelectionTextNode(
      selectionElement,
      fieldConfig.name,
      "data-upload-selection-message",
      "div",
      "mt-2 text-xs opacity-70",
    );
    const bodyElement = this.ensureUploadSelectionBody(selectionElement, fieldConfig.name);
    const fileCountLabel = selectedFiles.length === 1 ? "1 file selected" : `${selectedFiles.length} files selected`;
    const qrValue = isQrScan && typeof value === "string" && value.length ? value : "";

    this.clearFilePreviewUrls(fieldConfig.name);
    this.querySelectorAll(`[data-file-drop-zone="${fieldConfig.name}"]`).forEach((node) => {
      if (node instanceof HTMLElement) {
        node.dataset.fileDropState = isDragActive ? "drag" : selectedFiles.length ? "selected" : "idle";
        node.dataset.fileDragActive = isDragActive ? "true" : "false";
      }
    });

    selectionElement.classList.toggle("border-primary", isDragActive);
    selectionElement.classList.toggle("bg-base-200", isDragActive);
    selectionElement.dataset.uploadSelectionState =
      uploadState?.status || (selectedFiles.length ? "selected" : isDragActive ? "drag" : "idle");
    selectionElement.style.display =
      !isDocumentScan && !isQrScan && !uploadState && !isDragActive && !selectedFiles.length ? "none" : "";
    titleElement.textContent = this.getUploadSelectionTitle(fieldConfig, selectedFiles, qrValue, fileCountLabel);
    messageElement.textContent = this.getUploadSelectionMessage(fieldConfig, selectedFiles, qrValue);
    messageElement.style.display = messageElement.textContent ? "" : "none";

    let status = bodyElement.querySelector(`[data-upload-status="${fieldConfig.name}"]`) as HTMLDivElement | null;
    if (uploadState) {
      if (!status) {
        status = document.createElement("div");
        status.setAttribute("data-upload-status", fieldConfig.name);
        bodyElement.appendChild(status);
      }
      status.className = "";
      status.dataset.state = uploadState.status;
      status.textContent =
        uploadState.status === "uploading"
          ? `Uploading... ${uploadState.progress}%`
          : uploadState.status === "complete"
            ? "Uploaded"
            : "Upload failed";
      bodyElement.appendChild(status);
      messageElement.textContent =
        uploadState.status === "uploading"
          ? `Upload in progress (${uploadState.progress}%).`
          : uploadState.status === "complete"
            ? "Upload completed."
            : "Upload failed. Please try again.";
    } else {
      status?.remove();
    }

    if (isDocumentScan) {
      this.renderDocumentScanSelection(fieldConfig, selectedFiles, bodyElement);
      return;
    }

    if (isQrScan) {
      this.renderQrSelection(fieldConfig, value, bodyElement);
      if (!selectedFiles.length) {
        return;
      }
    }

    if (!selectedFiles.length) {
      bodyElement.querySelector(`[data-upload-file-list="${fieldConfig.name}"]`)?.remove();
      return;
    }

    const list = this.ensureSelectionChild(
      bodyElement,
      `[data-upload-file-list="${fieldConfig.name}"]`,
      "div",
      "space-y-2",
      "data-upload-file-list",
      fieldConfig.name,
    );
    list.className = "space-y-2";

    selectedFiles.forEach((file, index) => {
      const rowRef = `${fieldConfig.name}:${index}`;
      const row = this.ensureSelectionChild(
        list,
        `[data-upload-file-row="${rowRef}"]`,
        "div",
        "flex items-start justify-between gap-3 rounded border border-base-300 px-3 py-2",
        "data-upload-file-row",
        rowRef,
      );
      row.className = "flex items-start justify-between gap-3 rounded border border-base-300 px-3 py-2";

      const details = this.ensureSelectionChild(
        row,
        `[data-upload-file-details="${rowRef}"]`,
        "div",
        "min-w-0 flex-1",
        "data-upload-file-details",
        rowRef,
      );
      details.className = "min-w-0 flex-1";

      let name = details.querySelector(`[data-upload-file-name="${rowRef}"]`) as HTMLDivElement | null;
      if (!name) {
        name = document.createElement("div");
        name.setAttribute("data-upload-file-name", rowRef);
        details.appendChild(name);
      }
      name.className = "text-sm";
      name.textContent = file?.name || "";

      let meta = details.querySelector(`[data-upload-file-size="${rowRef}"]`) as HTMLDivElement | null;
      if (typeof file?.size === "number") {
        if (!meta) {
          meta = document.createElement("div");
          meta.setAttribute("data-upload-file-size", rowRef);
          details.appendChild(meta);
        }
        meta.className = "text-xs opacity-70";
        meta.textContent = `${Math.max(1, Math.round(file.size / 1024))} KB`;
      } else {
        meta?.remove();
      }

      if (
        file instanceof File &&
        this.shouldShowImagePreview(fieldConfig, file) &&
        typeof URL !== "undefined" &&
        typeof URL.createObjectURL === "function"
      ) {
        const previewUrl = URL.createObjectURL(file);
        this.filePreviewUrls[fieldConfig.name] = [
          ...(this.filePreviewUrls[fieldConfig.name] || []),
          previewUrl,
        ];
        let image = details.querySelector(`[data-upload-file-preview="${rowRef}"]`) as HTMLImageElement | null;
        if (!image) {
          image = document.createElement("img");
          image.setAttribute("data-upload-file-preview", rowRef);
          details.appendChild(image);
        }
        image.src = previewUrl;
        image.alt = file.name;
        image.className = "mt-2 h-20 w-20 rounded object-cover";
        image.style.display = "block";
        image.style.width = "72px";
        image.style.height = "72px";
        image.style.marginTop = "8px";
        image.style.borderRadius = "8px";
        image.style.objectFit = "cover";
        image.style.flexShrink = "0";
      } else {
        details.querySelector(`[data-upload-file-preview="${rowRef}"]`)?.remove();
      }

      let removeButton = row.querySelector(`[data-remove-file-index="${index}"]`) as HTMLButtonElement | null;
      if (!removeButton) {
        removeButton = document.createElement("button");
        removeButton.type = "button";
        row.appendChild(removeButton);
      }
      removeButton.className = "btn btn-xs btn-ghost";
      removeButton.textContent = "×";
      removeButton.setAttribute("aria-label", `Remove ${file?.name || "file"}`);
      removeButton.setAttribute("data-remove-file-index", String(index));
    });
    Array.from(list.querySelectorAll("[data-upload-file-row]")).forEach((node) => {
      const rowRef = (node as HTMLElement).getAttribute("data-upload-file-row");
      if (rowRef && !selectedFiles.some((_, index) => `${fieldConfig.name}:${index}` === rowRef)) {
        node.remove();
      }
    });
  }

  ensureUploadSelectionTextNode = (
    selectionElement: HTMLElement,
    fieldName: string,
    attributeName: string,
    tagName: "div" | "span",
    className: string,
  ) => {
    let element = selectionElement.querySelector(
      `[${attributeName}="${fieldName}"]`,
    ) as HTMLElement | null;
    if (element) {
      return element;
    }

    element = document.createElement(tagName);
    element.className = className;
    element.setAttribute(attributeName, fieldName);
    selectionElement.appendChild(element);
    return element;
  }

  ensureUploadSelectionBody = (selectionElement: HTMLElement, fieldName: string) => {
    if (selectionElement.getAttribute("data-upload-selection-body") === fieldName) {
      return selectionElement as HTMLDivElement;
    }

    let bodyElement = selectionElement.querySelector(
      `[data-upload-selection-body="${fieldName}"]`,
    ) as HTMLDivElement | null;
    if (bodyElement) {
      return bodyElement;
    }

    bodyElement = document.createElement("div");
    bodyElement.className = "mt-3 grid gap-2";
    bodyElement.setAttribute("data-upload-selection-body", fieldName);
    selectionElement.appendChild(bodyElement);
    return bodyElement;
  }

  ensureSelectionChild = (
    selectionElement: HTMLElement,
    selector: string,
    tagName: keyof HTMLElementTagNameMap,
    className: string,
    attributeName: string,
    fieldName: string,
  ) => {
    let element = selectionElement.querySelector(selector) as HTMLElement | null;
    if (element) {
      return element;
    }

    element = document.createElement(tagName);
    element.className = className;
    element.setAttribute(attributeName, fieldName);
    selectionElement.appendChild(element);
    return element;
  }

  getUploadSelectionTitle = (
    fieldConfig: TFieldConfig,
    selectedFiles: any[],
    qrValue: string,
    fileCountLabel: string,
  ) => {
    if (this.isDocumentScanField(fieldConfig)) {
      const slotCount = this.getDocumentScanSlotCount(fieldConfig);
      const capturedCount = selectedFiles.filter((file) => file instanceof File).length;
      return capturedCount ? `${capturedCount}/${slotCount} scans captured` : "Awaiting document scan";
    }

    if (this.isQrScanField(fieldConfig)) {
      const qrState = this.qrScannerState[fieldConfig.name] || { status: "idle" as const };
      if (qrValue) {
        return "QR code scanned";
      }
      if (qrState.status === "live") {
        return "Camera live";
      }
      if (qrState.status === "starting") {
        return "Starting camera";
      }
      return "Awaiting QR scan";
    }

    return !selectedFiles.length
      ? "Awaiting file"
      : selectedFiles.length === 1
        ? "Selected file"
        : fileCountLabel;
  }

  getUploadSelectionMessage = (fieldConfig: TFieldConfig, selectedFiles: any[], qrValue: string) => {
    if (this.isDocumentScanField(fieldConfig)) {
      const slotCount = this.getDocumentScanSlotCount(fieldConfig);
      const capturedCount = selectedFiles.filter((file) => file instanceof File).length;
      return capturedCount
        ? `${capturedCount} of ${slotCount} document side${slotCount > 1 ? "s" : ""} captured.`
        : this.getUploadSelectionIdleMessage(fieldConfig);
    }

    if (this.isQrScanField(fieldConfig)) {
      return qrValue ? `Latest result: ${qrValue}` : this.getUploadSelectionIdleMessage(fieldConfig);
    }

    return !selectedFiles.length
      ? this.getUploadSelectionIdleMessage(fieldConfig)
      : "";
  }

  getUploadSelectionIdleMessage = (fieldConfig: TFieldConfig) =>
    this.isDocumentScanField(fieldConfig)
      ? "Capture or upload the front and back of your document."
      : this.isQrScanField(fieldConfig)
        ? "Use the camera or upload an image containing a QR code."
        : fieldConfig.type === "upload-image"
      ? "Drop an image here or use the file picker."
      : "Drop files here or use the file picker."

  setFileDragState = (fieldName: string, active: boolean) => {
    this.fileDragActive[fieldName] = active;
    const fieldConfig = this.engine.getField(fieldName);
    if (!fieldConfig) {
      return;
    }

    this.querySelectorAll(`[data-file-drop-zone="${fieldName}"]`).forEach((node) => {
      if (node instanceof HTMLElement) {
        node.dataset.fileDragActive = active ? "true" : "false";
      }
    });

    const selectionElement = this.querySelector(`#${fieldName}_selection`) as HTMLElement | null;
    this.renderFileSelection(fieldConfig, this.getFieldValue(fieldName), selectionElement);
  }

  applyDroppedFiles = async (fieldName: string, files: File[]) => {
    if (!this.form || !files.length) {
      return;
    }

    const fieldConfig = this.engine.getField(fieldName);
    if (!fieldConfig) {
      return;
    }

    const currentFiles = this.getFileValueList(this.getFieldValue(fieldName));
    const nextValue = files[0]; // single file only
    const fileValidationError = this.engine.validateFileField(fieldName, nextValue);
    if (fileValidationError) {
      this.emitFileValidationErrorEvent(
        fieldName,
        {
          ...(this.form.getState().values || {}),
          [fieldName]: nextValue,
        },
        fileValidationError as TValidationError,
      );
      return;
    }

    if (this.isQrScanField(fieldConfig)) {
      const qrValue = files[0]
        ? await this.decodeQrScanFile(fieldConfig, files[0])
        : undefined;
      this.form.change(fieldName, qrValue);
      this.scheduleDraftSave();
      this.updateConditionalFields();
      void this.refreshRemoteOptions(fieldName);
      return;
    }

    if (this.isDocumentScanField(fieldConfig)) {
      const slotCount = this.getDocumentScanSlotCount(fieldConfig);
      const currentFiles = this.getFileValueList(this.getFieldValue(fieldName));
      const nextFiles = Array.from(
        { length: slotCount },
        (_, index) => currentFiles[index] instanceof File ? currentFiles[index] : undefined,
      );
      const targetSlot = Math.min(this.activeDocumentScanSlot[fieldName] || 0, slotCount - 1);
      const croppedFile = await this.cropDocumentScanFile(fieldConfig, files[0], targetSlot);
      nextFiles[targetSlot] = croppedFile;
      await this.analyzeDocumentScanFile(fieldConfig, croppedFile, targetSlot);
      const nextDocumentValue = slotCount === 1 ? nextFiles[0] : nextFiles;
      this.form.change(fieldName, nextDocumentValue);
      this.scheduleDraftSave();
      this.updateConditionalFields();
      void this.refreshRemoteOptions(fieldName);
      return;
    }

    this.form.change(fieldName, nextValue);
    this.scheduleDraftSave();
    this.updateConditionalFields();
    void this.refreshRemoteOptions(fieldName);
  }

  removeSelectedFile = (fieldName: string, fileIndex: number) => {
    const currentValue = this.getFieldValue(fieldName);
    const currentFiles = this.getFileValueList(currentValue);
    if (!currentFiles.length || fileIndex < 0 || fileIndex >= currentFiles.length) {
      return;
    }

    const nextFiles = currentFiles.filter((_, index) => index !== fileIndex);
    const fieldElement = this.getFieldElement(fieldName);
    if (fieldElement instanceof HTMLInputElement && fieldElement.type === "file") {
      fieldElement.value = "";
    }

    if (!this.form) {
      return;
    }

    const fieldConfig = this.engine.getField(fieldName);
    const nextValue = fieldConfig?.multiple ? nextFiles : nextFiles[0];
    this.form.change(fieldName, nextValue);
    this.scheduleDraftSave();
    this.updateConditionalFields();
    void this.refreshRemoteOptions(fieldName);
  }

  initialize = () => {
    let formElem: HTMLFormElement | null = null;
    const hydrationConfig = (this as any).__xpressuiHydrationConfig as TFormConfig | null | undefined;
    const useHydration = this.hasAttribute("hydrate-existing");

    if (useHydration) {
      formElem = this.querySelector("form") as HTMLFormElement | null;
    } else if ("content" in document.createElement("template")) {
      const name = this.getAttribute("name");
      if (name) {
        const root = this.getRootNode();
        const scopedRoot = this.parentElement || (root instanceof Document ? root : null);
        const template = scopedRoot?.querySelector(`#${name}`) as HTMLTemplateElement | null
          || document.querySelector(`#${name}`) as HTMLTemplateElement | null;
        if (template) {
          this.appendChild(template?.content.cloneNode(true));
          formElem = this.querySelector(`#${name}_form`) as HTMLFormElement;
        }
      }
    }

    if (!formElem) {
      this.initialized = false;
      return;
    }

    this.initialized = true;

    if (formElem) {
      this.formConfig = hydrationConfig
        ? validatePublicFormConfig(hydrationConfig)
        : validatePublicFormConfig(getFormConfig(formElem) as unknown as Record<string, any>);
      this.engine.setFormConfig(this.formConfig);
      this.steps.setFormConfig(this.formConfig);
      this.persistence.setFormConfig(this.formConfig);
      this.stepNames = this.isMultiStepMode() ? this.steps.getStepNames() : [];
      this.currentStepIndex = this.isMultiStepMode() ? this.steps.getCurrentStepIndex() : 0;
      const draftValues = this.persistence.loadDraftValues();
      const savedStepIndex = this.persistence.loadCurrentStepIndex();
      if (
        this.isMultiStepMode() &&
        Object.keys(draftValues).length > 0 &&
        typeof savedStepIndex === "number" &&
        savedStepIndex >= 0 &&
        savedStepIndex < this.stepNames.length
      ) {
        this.steps.setCurrentStepIndex(savedStepIndex);
        this.currentStepIndex = this.steps.getCurrentStepIndex();
      }
      const renderMode = this.getBaseRenderMode();
      const hybridInitialValues =
        renderMode === "hybrid"
          ? this.getInitialViewValues(formElem)
          : null;
      const hydrationInitialValues =
        useHydration
          ? this.getInitialViewValues(formElem)
          : draftValues;

      if (renderMode === "view") {
        this.applyViewMode(formElem);
        this.emitOutputSnapshot(this.getInitialViewValues(formElem));
        return;
      }

      this.form = createForm({
        onSubmit: this.onSubmit,
        initialValues: hybridInitialValues || hydrationInitialValues,
        validate: (values: Record<string, any>) => this.validateForm(values),

      });


      formElem.addEventListener("submit", (event) => {
        event.preventDefault();
        if (this.workflowState === "submitting") {
          return;
        }
        if (this.isMultiStepMode() && !this.isLastStep()) {
          this.nextStep();
          return;
        }
        const submitValues = this.form?.getState().values || {};
        const submitErrors = this.validateForm(submitValues);
        const blockingFields = Object.keys(submitErrors || {});
        if (blockingFields.length) {
          blockingFields.forEach((fieldName) => {
            this.form?.blur(fieldName);
          });
          // Final Form's internal errors may be stale when dynamicRequiredOverrides
          // are updated in onAfterChange (after Final Form already ran validation on
          // the triggering change). Force-render errors from the fresh submitErrors.
          blockingFields.forEach((fieldName) => {
            const inputElement = this.getFieldElement(fieldName);
            const errorElement = (this.querySelector(`#${fieldName}_error`) || this.querySelector(`[data-field-error="${fieldName}"]`)) as HTMLElement | null;
            const fieldState = this.form?.getFieldState(fieldName);
            this.renderFieldErrorState(
              fieldName,
              inputElement,
              errorElement,
              true,
              submitErrors[fieldName],
              Boolean(fieldState?.submitFailed) || this.stepSubmitFailed,
              this.getCurrentStepIndex(),
            );
          });
          this.emitFormEvent("xpressui:validation-blocked-submit", {
            values: this.engine.normalizeValues(submitValues),
            formConfig: this.formConfig,
            submit: this.formConfig?.submit,
            error: submitErrors,
            result: {
              fieldNames: blockingFields,
            },
          });
          return;
        }
        this.form?.submit();
      });
      formElem.addEventListener("click", (event) => {
        if (!this.isMultiStepMode()) {
          return;
        }

        const actionButton = (event.target as HTMLElement | null)?.closest("[data-step-action]") as HTMLButtonElement | null;
        if (!actionButton || !formElem.contains(actionButton)) {
          return;
        }

        const action = actionButton.getAttribute("data-step-action");
        if (action === "back") {
          event.preventDefault();
          this.previousStep();
        } else if (action === "next") {
          event.preventDefault();
          this.nextStep();
        }
      });

    if (useHydration && this.formConfig) {
      Object.values(this.engine.getFields()).forEach((fieldConfig) => {
        const input = this.getFieldElement(fieldConfig.name);
        const selectionElement = this.querySelector(`#${fieldConfig.name}_selection`) as HTMLElement | null;
        if (input || selectionElement) {
          this.registerField(fieldConfig, input);
        }
      });
    } else {
      Array.from(formElem.elements).forEach(input => {
        const fieldConfig = getFieldConfig(input);
        if (fieldConfig.type !== UNKNOWN_TYPE) {
          this.registerField(fieldConfig, input);
        }
      });
    }
      this.bindProductListGlobalCartEvents();
      this.resetFieldErrorDisplays();
      this.ensureStepControls(formElem);

      this.dynamic.updateConditionalFields();
      void this.dynamic.refreshRemoteOptions();
      this.syncStepVisibility();
      this.syncStepControls();
      this.emitWorkflowSnapshotEvent({
        values: this.form?.getState().values || {},
        formConfig: this.formConfig,
        submit: this.formConfig?.submit,
      });
      this.emitStepChange();

      if (renderMode === "hybrid") {
        this.applyHybridMode(formElem, hybridInitialValues || undefined);
        this.emitOutputSnapshot(hybridInitialValues || this.form?.getState().values || {});
      }

      this.persistence.connect();

      void this.persistence.hydrateStorage().then((result) => {
        if (!result) {
          return;
        }

        const restoredDraft = getRestorableStorageValues(result.snapshot.draft);
        const initialDraftJson = JSON.stringify(draftValues);
        const restoredDraftJson = JSON.stringify(restoredDraft);
        if (
          this.form &&
          Object.keys(restoredDraft).length &&
          restoredDraftJson !== initialDraftJson
        ) {
          this.form.initialize(restoredDraft);
          this.persistence.emitDraftRestored(restoredDraft);
        }

        this.persistence.emitQueueState();
      });

      if (Object.keys(draftValues).length) {
        this.persistence.emitDraftRestored(draftValues);
      }

      this.persistence.emitQueueState();
    }
  }

  saveDraft = (values?: Record<string, any>) => {
    this.persistence.saveDraft(values);
  }

  scheduleDraftSave = () => {
    this.persistence.scheduleDraftSave();
  }

  clearDraft = () => {
    this.persistence.clearDraft();
  }

  shouldUseQueue = () => {
    return this.persistence.shouldUseQueue();
  }

  enqueueSubmission = (values: Record<string, any>) => {
    this.persistence.enqueueSubmission(values);
  }

  getQueueState = (): TFormQueueState => {
    return this.persistence.getQueueState();
  }

  emitQueueState = () => {
    this.persistence.emitQueueState();
  }

  getStorageSnapshot = (): TFormStorageSnapshot => {
    return this.persistence.getStorageSnapshot();
  }

  getStorageHealth = (): TFormStorageHealth => {
    return this.persistence.getStorageHealth();
  }

  getResumeStatusSummary = (): TResumeStatusSummary => {
    return this.persistence.getResumeStatusSummary();
  }

  getOperationalSummary = () => {
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

  getIncidentSummary = (limit = 5) => {
    return buildLocalFormIncidentSummary({
      snapshot: this.getStorageSnapshot(),
      resumeTokens: this.listResumeTokens(),
    }, limit);
  }

  setValidationI18n = (i18n?: TFormValidationI18nConfig | null): void => {
    if (!this.formConfig) {
      return;
    }
    const nextValidation = {
      ...(this.formConfig.validation || {}),
      ...(i18n ? { i18n } : {}),
    };
    if (!i18n) {
      delete (nextValidation as any).i18n;
    }
    this.formConfig = {
      ...this.formConfig,
      validation: Object.keys(nextValidation).length ? nextValidation : undefined,
    };
    this.engine.setFormConfig(this.formConfig);
    this.persistence.setFormConfig(this.formConfig);
    this.emitFormEvent("xpressui:validation-i18n-updated", {
      values: this.engine.normalizeValues(this.form?.getState().values || {}),
      formConfig: this.formConfig,
      submit: this.formConfig?.submit,
      result: {
        locale: this.formConfig.validation?.i18n?.locale || null,
      },
    });
  }

  createResumeToken = (): string | null => {
    return this.persistence.createResumeToken();
  }

  createResumeTokenAsync = (): Promise<string | null> => {
    return this.persistence.createResumeTokenAsync();
  }

  createResumeShareCode = (token: string): Promise<string | null> => {
    return this.persistence.createResumeShareCode(token);
  }

  createResumeShareCodeDetail = (token: string): Promise<TResumeShareCodeInfo | null> => {
    return this.persistence.createResumeShareCodeDetail(token);
  }

  listResumeTokens = (): TResumeTokenInfo[] => {
    return this.persistence.listResumeTokens();
  }

  deleteResumeToken = (token: string): boolean => {
    return this.persistence.deleteResumeToken(token);
  }

  invalidateResumeToken = (token: string): Promise<boolean> => {
    return this.persistence.invalidateResumeToken(token);
  }

  restoreFromResumeToken = (token: string): Record<string, any> | null => {
    const restoredValues = this.persistence.restoreFromResumeToken(token);
    if (!restoredValues || !this.form) {
      return restoredValues;
    }

    Object.entries(restoredValues).forEach(([fieldName, fieldValue]) => {
      this.form?.change(fieldName, fieldValue);
    });
    this.persistence.emitDraftRestored(restoredValues);
    this.updateConditionalFields();
    void this.refreshRemoteOptions();
    return restoredValues;
  }

  lookupResumeToken = (token: string): Promise<TResumeLookupResult | null> => {
    return this.persistence.lookupResumeToken(token);
  }

  claimResumeShareCode = (code: string): Promise<TResumeLookupResult | null> => {
    return this.persistence.claimResumeShareCode(code);
  }

  claimResumeShareCodeDetail = (code: string): Promise<TResumeShareCodeClaimDetail | null> => {
    return this.persistence.claimResumeShareCodeDetail(code);
  }

  restoreFromShareCodeDetailAsync = async (code: string): Promise<TResumeShareCodeRestoreDetail | null> => {
    const detail = await this.persistence.restoreFromShareCodeDetailAsync(code);
    const restoredValues = detail?.restoredValues || null;
    if (!restoredValues || !this.form) {
      return detail;
    }

    Object.entries(restoredValues).forEach(([fieldName, fieldValue]) => {
      this.form?.change(fieldName, fieldValue);
    });
    this.persistence.emitDraftRestored(restoredValues);
    this.updateConditionalFields();
    await this.refreshRemoteOptions();
    return detail;
  }

  restoreFromResumeTokenAsync = async (token: string): Promise<Record<string, any> | null> => {
    const restoredValues = await this.persistence.restoreFromResumeTokenAsync(token);
    if (!restoredValues || !this.form) {
      return restoredValues;
    }

    Object.entries(restoredValues).forEach(([fieldName, fieldValue]) => {
      this.form?.change(fieldName, fieldValue);
    });
    this.persistence.emitDraftRestored(restoredValues);
    this.updateConditionalFields();
    await this.refreshRemoteOptions();
    return restoredValues;
  }

  restoreFromShareCodeAsync = async (code: string): Promise<Record<string, any> | null> => {
    const detail = await this.restoreFromShareCodeDetailAsync(code);
    return detail?.restoredValues || null;
  }

  clearDeadLetterQueue = () => {
    this.persistence.clearDeadLetterQueue();
  }

  requeueDeadLetterEntry = (entryId: string) => {
    return this.persistence.requeueDeadLetterEntry(entryId);
  }

  replayDeadLetterEntry = async (entryId: string) => {
    return this.persistence.replayDeadLetterEntry(entryId);
  }

  flushSubmissionQueue = async () => {
    await this.persistence.flushSubmissionQueue();
  }

  getActiveTemplateWarnings = (): TFormActiveTemplateWarning[] => {
    return this.dynamic.getActiveTemplateWarnings();
  }

  clearActiveTemplateWarnings = () => {
    this.dynamic.clearActiveTemplateWarnings();
  }

  getRecentAppliedRules = () => {
    return this.dynamic.getRecentAppliedRules();
  }

  clearRecentAppliedRules = () => {
    this.dynamic.clearRecentAppliedRules();
  }

  getDocumentData = (fieldName: string) => {
    return this.engine.getDocumentData(fieldName);
  }

  getDocumentDataView = (
    fieldName: string,
    mode: "full" | "summary" | "fields-only" | "mrz-only" | "none" = "summary",
    options: boolean | TDocumentDataViewOptions = true,
  ) => {
    return this.engine.getDocumentDataView(fieldName, mode, options);
  }

  getAllDocumentData = () => {
    return this.engine.getAllDocumentData();
  }

  getAllDocumentDataView = (
    mode: "full" | "summary" | "fields-only" | "mrz-only" | "none" = "summary",
    options: boolean | TDocumentDataViewOptions = true,
  ) => {
    return this.engine.getAllDocumentDataView(mode, options);
  }

  getApprovalState = (): TFormApprovalState | null => {
    return this.approvalState;
  }

  getWorkflowState = (): TFormWorkflowState => {
    return this.workflowState;
  }

  getStepNames = (): string[] => {
    return this.isMultiStepMode() ? this.steps.getStepNames() : [];
  }

  getCurrentStepIndex = (): number => {
    return this.isMultiStepMode() ? this.steps.getCurrentStepIndex() : 0;
  }

  getCurrentStepName = (): string | null => {
    return this.isMultiStepMode() ? this.steps.getCurrentStepName() : null;
  }

  getStepProgress = () => {
    if (!this.isMultiStepMode()) {
      return {
        stepIndex: 0,
        stepNumber: 1,
        stepCount: 1,
        percent: 100,
      };
    }
    return this.steps.getStepProgress();
  }

  getStepButtonLabels = (): { previous: string; next: string } => {
    return getConfiguredStepButtonLabels(this.formConfig);
  }

  getStepUiConfig = () => {
    return getConfiguredStepUiConfig(this.formConfig);
  }

  getWorkflowSnapshot = () => {
    const values = this.form?.getState().values || {};
    return this.steps.getWorkflowSnapshot(values);
  }

  getWorkflowContext = () => {
    return {
      workflowState: this.getWorkflowState(),
      approvalState: this.approvalState,
      snapshot: this.getWorkflowSnapshot(),
    };
  }

  forceRevalidation = () => {
    if (this.form) {
      this.form.setConfig("validate", (values) => this.validateForm(values));
    }
  }

  emitWorkflowSnapshotEvent = (
    detail: Omit<THydratedFormSubmitDetail, "result">,
    response?: Response,
  ) => {
    const workflowDetail = {
      ...detail,
      ...(response ? { response } : {}),
      result: this.getWorkflowSnapshot(),
    };
    this.emitFormEvent("xpressui:workflow-step", workflowDetail);
    this.emitFormEvent("xpressui:workflow-snapshot", workflowDetail);
  }

  goToWorkflowStep = (state?: string): boolean => {
    if (!this.isMultiStepMode()) {
      return false;
    }
    if (!this.steps.goToWorkflowStep(state)) {
      return false;
    }

    this.currentStepIndex = this.steps.getCurrentStepIndex();
    this.persistence.saveCurrentStepIndex(this.currentStepIndex);
    this.suppressActiveStepErrors();
    this.forceRevalidation();
    this.syncStepVisibility();
    this.syncStepControls();
    this.emitFormEvent("xpressui:step-jumped", {
      values: this.form?.getState().values || {},
      formConfig: this.formConfig,
      submit: this.formConfig?.submit,
      result: {
        state: state || this.workflowState,
        stepIndex: this.currentStepIndex,
        stepName: this.getCurrentStepName(),
      },
    });
    this.emitWorkflowSnapshotEvent({
      values: this.form?.getState().values || {},
      formConfig: this.formConfig,
      submit: this.formConfig?.submit,
    });
    this.emitStepChange();
    return true;
  }

  isLastStep = (): boolean => {
    if (!this.isMultiStepMode()) {
      return true;
    }
    return this.steps.isLastStep();
  }

  getCurrentStepConfig = (): TFieldConfig | null => {
    const currentStepName = this.getCurrentStepName();
    if (!currentStepName) {
      return null;
    }

    return (
      this.steps.getCurrentStepConfig()
    );
  }

  getStepSummary = (): Array<{ field: string; label: string; value: any }> => {
    const currentStepConfig = this.getCurrentStepConfig();
    if (!currentStepConfig?.stepSummary) {
      return [];
    }

    const values = this.form?.getState().values || {};
    return this.steps.getStepSummary(values, this.engine.getFields());
  }

  shouldValidateCurrentStepForWorkflow = (): boolean => {
    return this.steps.shouldValidateCurrentStepForWorkflow();
  }

  isCurrentStepSkippable = (): boolean => {
    return this.steps.isCurrentStepSkippable();
  }

  getConditionalNextStepName = (): string | null => {
    if (!this.form) {
      return null;
    }

    return this.steps.getConditionalNextStepName(this.form.getState().values || {});
  }

  setCurrentStepIndex = (index: number): boolean => {
    if (!this.isMultiStepMode()) {
      return false;
    }
    if (!this.steps.setCurrentStepIndex(index)) {
      return false;
    }

    this.currentStepIndex = this.steps.getCurrentStepIndex();
    this.suppressActiveStepErrors();
    this.forceRevalidation();
    this.syncStepVisibility();
    this.syncStepControls();
    this.emitStepChange();
    return true;
  }

  getCurrentStepFieldElements = (): Array<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement> => {
    return getConfiguredCurrentStepFieldElements(this, this.getCurrentStepName());
  }

  getStepElements = (sectionName: string): HTMLElement[] => {
    return getConfiguredStepElements(this, sectionName);
  }

  suppressActiveStepErrors = () => {
    if (!this.isMultiStepMode()) {
      return;
    }

    const activeStepName = this.getCurrentStepName();
    if (!activeStepName) {
      return;
    }

    this.suppressedStepErrorFields.clear();
    const activeStepFields = this.formConfig?.sections?.[activeStepName] || [];
    activeStepFields.forEach((fieldConfig) => {
      if (!fieldConfig.name) {
        return;
      }
      this.suppressedStepErrorFields.add(fieldConfig.name);
      this.syncFieldErrorDisplay(fieldConfig.name);
    });
  }

  markFieldAsInteracted = (fieldName: string) => {
    if (!this.suppressedStepErrorFields.has(fieldName)) {
      return;
    }
    this.suppressedStepErrorFields.delete(fieldName);
    this.syncFieldErrorDisplay(fieldName);
  }

  validateCurrentStep = (): boolean => {
    if (!this.form || !this.isMultiStepMode() || this.stepNames.length <= 1) {
      return true;
    }

    if (!this.shouldValidateCurrentStepForWorkflow()) {
      return true;
    }

    const currentStepName = this.getCurrentStepName();
    if (!currentStepName) {
      return true;
    }

    const currentStepConfigFields = this.formConfig?.sections?.[currentStepName] || [];
    if (!currentStepConfigFields.length) {
      return true;
    }

    const currentStepFields = this.getCurrentStepFieldElements();
    const values = {
      ...(this.form.getState().values || {}),
    } as Record<string, any>;

    currentStepFields.forEach((fieldElement) => {
      const fieldName = fieldElement.name;
      const fieldConfig = this.engine.getFields()[fieldName] || getFieldConfig(fieldElement);
      if (fieldName && fieldConfig?.type && fieldConfig.type !== UNKNOWN_TYPE) {
          if (!isFileFieldType(fieldConfig.type)) {
            const domValue = this.readInputElementValue(fieldConfig, fieldElement);
            if (domValue !== undefined) {
              values[fieldName] = domValue;
              this.form?.change(fieldName, domValue);
            }
          }
      }
    });

    const currentStepIndex = this.getCurrentStepIndex();
    const errors = this.engine.validateValues(values, currentStepIndex);

    currentStepFields.forEach((fieldElement) => {
      fieldElement.dispatchEvent(new FocusEvent("blur"));
    });

    currentStepConfigFields.forEach((fieldConfig) => {
      if (fieldConfig.name) {
        this.form?.blur(fieldConfig.name);
      }
    });

    const firstInvalidFieldConfig = currentStepConfigFields.find((fieldConfig) => Boolean(errors[fieldConfig.name]));
    if (firstInvalidFieldConfig) {
        const firstInvalidFieldElement = currentStepFields.find((fieldElement) => fieldElement.name === firstInvalidFieldConfig.name);
        if (firstInvalidFieldElement && typeof firstInvalidFieldElement.focus === "function") {
          firstInvalidFieldElement.focus();
        }
    }
      return !currentStepConfigFields.some((fieldConfig) => Boolean(errors[fieldConfig.name]));
  }

  ensureStepControls = (formElem: HTMLFormElement) => {
    if (!this.isMultiStepMode()) {
      this.stepProgressContainer?.remove();
      this.stepActionsContainer?.remove();
      this.stepProgressContainer = null;
      this.stepActionsContainer = null;
      this.stepProgressElement = null;
      this.stepProgressBar = null;
      this.stepSummaryElement = null;
      this.stepBackButton = null;
      this.stepNextButton = null;
      return;
    }
    const controls = ensureConfiguredStepControls({
      formElem,
      stepCount: this.stepNames.length,
      buttonLabels: this.getStepButtonLabels(),
      stepUi: this.getStepUiConfig(),
      allowCreate: !this.hasAttribute("hydrate-existing"),
      onPrevious: () => {
        this.previousStep();
      },
      onNext: () => {
        this.nextStep();
      },
    });
    this.stepProgressContainer = controls.progressContainer;
    this.stepActionsContainer = controls.actionsContainer;
    this.stepProgressElement = controls.progress;
    this.stepProgressBar = controls.progressBar;
    this.stepSummaryElement = controls.summary;
    this.stepBackButton = controls.backButton;
    this.stepNextButton = controls.nextButton;
  }

  syncStepVisibility = () => {
    if (!this.isMultiStepMode()) {
      this.steps.getStepNames().forEach((sectionName) => {
        this.getStepElements(sectionName).forEach((element) => {
          if (element.getAttribute("data-step-hidden") === "true") {
            element.removeAttribute("data-step-hidden");
            element.style.display = "";
          }
        });
      });
      return;
    }
    syncConfiguredStepVisibility({
      stepNames: this.stepNames,
      currentStepIndex: this.currentStepIndex,
      getStepElements: (sectionName) => this.getStepElements(sectionName),
    });
  }

  syncStepControls = () => {
    const formElement = this.querySelector("form");
    if (!this.isMultiStepMode()) {
      syncConfiguredStepControls({
        formElement,
        stepCount: 1,
        currentStepIndex: 0,
        isLastStep: true,
        progress: {
          stepIndex: 0,
          stepNumber: 1,
          stepCount: 1,
          percent: 100,
        },
        isCurrentStepSkippable: false,
        summary: [],
        submitLockedByRules: this.submitLockedByRules,
        submitLockMessage: this.submitLockMessage,
        isSubmitting: this.workflowState === "submitting",
        stepUi: this.getStepUiConfig(),
        controls: {
          progressContainer: this.stepProgressContainer,
          progress: this.stepProgressElement,
          progressBar: this.stepProgressBar,
          summary: this.stepSummaryElement,
          actionsContainer: this.stepActionsContainer,
          backButton: this.stepBackButton,
          nextButton: this.stepNextButton,
        },
      });
      return;
    }
    syncConfiguredStepControls({
      formElement,
      stepCount: this.stepNames.length,
      currentStepIndex: this.currentStepIndex,
      isLastStep: this.isLastStep(),
      progress: this.getStepProgress(),
      isCurrentStepSkippable: this.isCurrentStepSkippable(),
      summary: this.getStepSummary(),
      submitLockedByRules: this.submitLockedByRules,
      submitLockMessage: this.submitLockMessage,
      isSubmitting: this.workflowState === "submitting",
      stepUi: this.getStepUiConfig(),
      controls: {
        progressContainer: this.stepProgressContainer,
        progress: this.stepProgressElement,
        progressBar: this.stepProgressBar,
        summary: this.stepSummaryElement,
        actionsContainer: this.stepActionsContainer,
        backButton: this.stepBackButton,
        nextButton: this.stepNextButton,
      },
    });
  }

  emitStepChange = () => {
    if (!this.isMultiStepMode() || this.stepNames.length <= 1) {
      return;
    }

    const progress = this.getStepProgress();
    const conditionalNextStepName = this.getConditionalNextStepName();

    this.emitFormEvent("xpressui:step-change", {
      values: this.form?.getState().values || {},
      formConfig: this.formConfig,
      submit: this.formConfig?.submit,
      result: {
        stepIndex: this.currentStepIndex,
        stepName: this.getCurrentStepName(),
        stepCount: this.stepNames.length,
        progress,
        skippable: this.isCurrentStepSkippable(),
        summary: this.getStepSummary(),
        nextStepTarget: conditionalNextStepName,
      },
    });
  }

  goToStep = (index: number): boolean => {
    if (!this.isMultiStepMode()) {
      return false;
    }
    if (!this.steps.canGoToStep(index)) {
      return false;
    }

    if (!this.steps.goToStep(index)) {
      return false;
    }
    this.stepSubmitFailed = false;
    this.currentStepIndex = this.steps.getCurrentStepIndex();
    this.persistence.saveCurrentStepIndex(this.currentStepIndex);
    this.suppressActiveStepErrors();
    this.forceRevalidation();
    this.syncStepVisibility();
    this.syncStepControls();
    this.emitStepChange();
    return true;
  }

  nextStep = (): boolean => {
    if (!this.isMultiStepMode()) {
      return false;
    }
    const values = this.form?.getState().values || {};
    const wasSkippable = this.steps.isCurrentStepSkippable();
    const conditionalTarget = this.steps.getConditionalNextStepName(values);
    const isStepValid = this.validateCurrentStep();
    if (!wasSkippable && !isStepValid) {
      this.stepSubmitFailed = true;
      this.syncAllFieldErrors();
      this.emitFormEvent("xpressui:step-blocked", {
        values,
        formConfig: this.formConfig,
        submit: this.formConfig?.submit,
        result: {
          stepIndex: this.currentStepIndex,
          stepName: this.getCurrentStepName(),
        },
      });
    }
    if (!this.steps.nextStep(values, isStepValid)) {
      return false;
    }
    this.stepSubmitFailed = false;
    const jumped = Boolean(conditionalTarget);
    if (jumped) {
      this.emitFormEvent("xpressui:step-jumped", {
        values,
        formConfig: this.formConfig,
        submit: this.formConfig?.submit,
        result: {
          stepIndex: this.steps.getCurrentStepIndex(),
          stepName: this.steps.getCurrentStepName(),
        },
      });
    } else if (wasSkippable) {
      this.emitFormEvent("xpressui:step-skipped", {
        values,
        formConfig: this.formConfig,
        submit: this.formConfig?.submit,
        result: {
          stepIndex: this.currentStepIndex,
          stepName: this.getCurrentStepName(),
        },
      });
    }
    this.currentStepIndex = this.steps.getCurrentStepIndex();
    this.persistence.saveCurrentStepIndex(this.currentStepIndex);
    this.suppressActiveStepErrors();
    this.forceRevalidation();
    this.syncStepVisibility();
    this.syncStepControls();
    this.emitWorkflowSnapshotEvent({
      values,
      formConfig: this.formConfig,
      submit: this.formConfig?.submit,
    });
    this.emitStepChange();
    return true;
  }

  previousStep = (): boolean => {
    if (!this.isMultiStepMode()) {
      return false;
    }
    const stepUi = this.getStepUiConfig();
    if (stepUi.backBehavior !== "always") {
      return false;
    }
    if (!this.steps.previousStep()) {
      return false;
    }
    this.stepSubmitFailed = false;
    this.currentStepIndex = this.steps.getCurrentStepIndex();
    this.persistence.saveCurrentStepIndex(this.currentStepIndex);
    this.suppressActiveStepErrors();
    this.forceRevalidation();
    this.syncStepVisibility();
    this.syncStepControls();
    this.emitWorkflowSnapshotEvent({
      values: this.form?.getState().values || {},
      formConfig: this.formConfig,
      submit: this.formConfig?.submit,
    });
    this.emitStepChange();
    return true;
  }

  syncApprovalStateFields = () => {
    if (!this.form) {
      return;
    }

    Object.values(this.engine.getFields()).forEach((fieldConfig) => {
      if (fieldConfig.type === APPROVAL_STATE_TYPE) {
        this.form?.change(fieldConfig.name, this.approvalState?.status || "");
      }
    });
  }

  setWorkflowState = (
    nextState: TFormWorkflowState,
    detail: THydratedFormSubmitDetail,
    response?: Response,
    result?: any,
  ): TWorkflowRouteResult => {
    const workflowTarget = this.steps.getWorkflowStepTarget(nextState);
    if (this.workflowState === nextState) {
      const moved = this.goToWorkflowStep(nextState);
      const routed = moved || (workflowTarget !== null && workflowTarget === this.getCurrentStepName());
      const routeResult = {
        routed,
        stepIndex: this.getCurrentStepIndex(),
        stepName: this.getCurrentStepName(),
      };
      if (routed) {
        this.emitWorkflowSnapshotEvent(detail, response);
      }
      return routeResult;
    }

    this.workflowState = nextState;
    this.steps.setWorkflowState(nextState);
    // Immediately sync controls so spinner / disabled state reflects the new state
    // (e.g. shows spinner as soon as "submitting" begins, removes it on "submitted"/"error")
    this.syncStepControls();
    const moved = this.goToWorkflowStep(nextState);
    const routed = moved || (workflowTarget !== null && workflowTarget === this.getCurrentStepName());
    this.emitFormEvent("xpressui:workflow-state", {
      ...detail,
      response,
      result: {
        state: nextState,
        approvalState: this.approvalState,
        snapshot: this.getWorkflowSnapshot(),
      },
    });
    this.emitWorkflowSnapshotEvent(detail, response);
    // Bug 3: hide form fields and surface the success message after submission
    if (nextState === "submitted" && !routed) {
      this.applySubmittedLayout();
    }
    return {
      routed,
      stepIndex: this.getCurrentStepIndex(),
      stepName: this.getCurrentStepName(),
    };
  }

  /**
   * Called when the form transitions to "submitted" and there is no dedicated
   * workflow step to route to.  Hides all field containers so only the
   * confirmation / success message remains visible.
   *
   * The host page can mark a confirmation element with
   *   data-form-submitted="true"   (shown after submission)
   * and any element it wants hidden can carry
   *   data-form-hide-on-submit="true"
   *
   * Without those attributes the method falls back to hiding every label
   * that wraps an input / select / textarea inside the <form>.
   */
  applySubmittedLayout = (): void => {
    const formElem = this.querySelector("form") as HTMLFormElement | null;
    if (!formElem) {
      return;
    }

    // 1. Mark the host element so external CSS can target it
    this.setAttribute("data-workflow-state", "submitted");
    formElem.setAttribute("data-workflow-state", "submitted");

    // 2. Show elements explicitly tagged as success / confirmation content
    Array.from(
      this.querySelectorAll<HTMLElement>('[data-form-submitted="true"], [data-form-success]'),
    ).forEach((el) => {
      el.style.display = "";
      el.removeAttribute("hidden");
    });

    // 3. Always hide step UI controls (progress bar, navigation buttons)
    Array.from(
      formElem.querySelectorAll<HTMLElement>(
        "[data-form-step-progress-container], [data-form-step-actions]",
      ),
    ).forEach((el) => {
      el.style.display = "none";
    });

    // 4. Hide elements explicitly tagged for hiding on submit
    const explicitHide = Array.from(
      formElem.querySelectorAll<HTMLElement>('[data-form-hide-on-submit="true"]'),
    );

    // 5. Fall back: collect field containers (labels wrapping inputs, and
    //    section containers) when no explicit hide targets are present
    const fieldLabels = Array.from(
      formElem.querySelectorAll<HTMLElement>("label"),
    ).filter(
      (label) =>
        label.querySelector("input, select, textarea") !== null &&
        !label.hasAttribute("data-form-submitted") &&
        !label.hasAttribute("data-form-success"),
    );

    const sectionContainers = Array.from(
      formElem.querySelectorAll<HTMLElement>('[data-type="section"]'),
    );

    const submitButtons = Array.from(
      formElem.querySelectorAll<HTMLElement>('button[type="submit"], input[type="submit"]'),
    );

    const toHide = explicitHide.length > 0
      ? explicitHide
      : [...fieldLabels, ...sectionContainers, ...submitButtons];

    toHide.forEach((el) => {
      el.style.display = "none";
    });
  }

  resolveProviderStepTargetIndex = (target: string | number): number | null => {
    if (typeof target === "number" && Number.isInteger(target)) {
      return target;
    }

    if (typeof target === "string") {
      const stepNames = this.getStepNames();
      const stepIndex = stepNames.indexOf(target);
      return stepIndex >= 0 ? stepIndex : null;
    }

    return null;
  }

  getProviderRoutingPolicy = (): NonNullable<TFormSubmitRequest["providerRoutingPolicy"]> => {
    const policy = this.formConfig?.submit?.providerRoutingPolicy;
    if (
      policy === "workflow-first" ||
      policy === "step-first" ||
      policy === "workflow-only" ||
      policy === "step-only"
    ) {
      return policy;
    }

    return "auto";
  }

  applySingleProviderTransition = (
    transition: TFormProviderTransition,
    detail: THydratedFormSubmitDetail,
    response: Response | undefined,
    result: any,
  ): TProviderTransitionRouteResult | null => {
    if (transition.type === "workflow") {
      const workflowRoute = this.setWorkflowState(
        transition.state as TFormWorkflowState,
        detail,
        response,
        result,
      );
      if (workflowRoute.routed) {
        this.emitFormEvent("xpressui:provider-step-routed", {
          ...detail,
          response,
          result: {
            transition,
            stepIndex: workflowRoute.stepIndex,
            stepName: workflowRoute.stepName,
            workflow: this.getWorkflowSnapshot(),
          },
        });
      }
      return {
        routed: workflowRoute.routed,
        stepIndex: workflowRoute.stepIndex,
        stepName: workflowRoute.stepName,
      };
    }

    const stepIndex = this.resolveProviderStepTargetIndex(transition.target);
    if (stepIndex === null || !this.goToStep(stepIndex)) {
      return null;
    }

    this.emitFormEvent("xpressui:step-jumped", {
      ...detail,
      response,
      result: {
        transition,
        stepIndex: this.getCurrentStepIndex(),
        stepName: this.getCurrentStepName(),
      },
    });
    this.emitWorkflowSnapshotEvent(detail, response);
    return {
      routed: true,
      stepIndex: this.getCurrentStepIndex(),
      stepName: this.getCurrentStepName(),
    };
  }

  applyProviderTransition = (
    detail: THydratedFormSubmitDetail,
    response: Response | undefined,
    result: any,
    providerResult?: TNormalizedProviderResult,
  ) => {
    const policy = this.getProviderRoutingPolicy();
    const transitions = buildConfiguredProviderTransitionCandidates(policy, result, providerResult);
    if (!transitions.length) {
      return false;
    }

    for (const transition of transitions) {
      const routeResult = this.applySingleProviderTransition(transition, detail, response, result);
      if (!routeResult) {
        continue;
      }

      this.emitFormEvent("xpressui:provider-transition", {
        ...detail,
        response,
        result: {
          transition,
          routed: routeResult.routed,
          stepIndex: routeResult.stepIndex,
          stepName: routeResult.stepName,
          policy,
          workflow: this.getWorkflowSnapshot(),
        },
      });
      return true;
    }

    return false;
  }

  emitFileValidationErrorEvent = (
    fieldName: string,
    values: Record<string, any>,
    validationError: TValidationError,
  ) => {
    this.emitFormEvent("xpressui:file-validation-error", {
      values: this.engine.normalizeValues(values),
      formConfig: this.formConfig,
      submit: this.formConfig?.submit,
      error: validationError,
      result: {
        field: fieldName,
        code: validationError?.errorData?.type,
      },
    });
  }

  validateForm = (values: Record<string, any>) => {
    const currentStepIndex = this.getCurrentStepIndex();
    const errors = runValidationHooks({
      formConfig: this.formConfig,
      values,
      validateValues: (nextValues) => this.engine.validateValues(nextValues, currentStepIndex),
    });

    Object.entries(errors).forEach(([fieldName, errorValue]) => {
      const validationError = errorValue as TValidationError;
      const errorType = validationError?.errorData?.type;
      if (
        errorType === "file-accept" ||
        errorType === "file-size" ||
        errorType === "file-count" ||
        errorType === "file-min-count" ||
        errorType === "file-total-size"
      ) {
        this.emitFileValidationErrorEvent(fieldName, values, validationError);
      }
    });

    return errors;
  }

  emitFormEvent = (
    eventName: string,
    detail: THydratedFormSubmitDetail,
    cancelable: boolean = false,
  ) => {
    const event = new CustomEvent<THydratedFormSubmitDetail>(eventName, {
      detail,
      bubbles: true,
      cancelable,
    });

    return this.dispatchEvent(event);
  }

  submitToApi = async (
    formValues: Record<string, any>,
    submitConfig: TFormSubmitRequest,
  ) => {
    return this.upload.submit(formValues, submitConfig, this.engine.getFields());
  }

  emitApprovalStateEvents = (
    detail: THydratedFormSubmitDetail,
    result: any,
    providerResult?: TNormalizedProviderResult,
    response?: Response,
  ) => {
    const approvalUpdate = resolveApprovalStateUpdate({
      action: this.formConfig?.submit?.action,
      result,
      providerResult,
      currentApprovalId: this.approvalState?.approvalId,
      detail,
      response,
    });
    if (!approvalUpdate.approvalState) {
      return;
    }

    this.approvalState = approvalUpdate.approvalState;
    approvalUpdate.events.forEach((event) => {
      this.emitFormEvent(event.eventName, event.detail);
    });
    this.syncApprovalStateFields();
  }

  emitProviderMessages = (
    detail: THydratedFormSubmitDetail,
    providerResult?: TNormalizedProviderResult,
    response?: Response,
    source: "success" | "error" = "success",
  ) => {
    const result = buildProviderMessagesResult(providerResult, source);
    if (!result) {
      return;
    }

    this.emitFormEvent("xpressui:provider-messages", {
      ...detail,
      response,
      result,
    });
  }

  enforceProviderResponseContract = (
    result: any,
    submitConfig: TFormSubmitRequest,
  ) => {
    const warning = getProviderContractWarning(result, submitConfig);
    if (warning) {
      this.emitFormEvent("xpressui:provider-contract-warning", {
        values: this.engine.normalizeValues(this.form?.getState().values || {}),
        formConfig: this.formConfig,
        submit: submitConfig,
        result: warning,
      });
    }

    assertProviderResponseContract(result, submitConfig);
  }

  emitSubmitHookError = (
    stage: TFormSubmitLifecycleStage,
    detail: THydratedFormSubmitDetail,
    hookError: unknown,
  ) => {
    this.emitFormEvent("xpressui:submit-hook-error", {
      ...detail,
      error: hookError,
      result: buildSubmitHookErrorResult(stage, hookError),
    });
  }

  onSubmit = async (values: Record<string, any>) => {
    let formValues = this.engine.buildSubmissionValues(
      values,
      Boolean(this.formConfig?.submit?.includeDocumentData),
      this.formConfig?.submit?.documentDataMode || "full",
      this.formConfig?.submit?.documentFieldPaths,
    );
    if (this.submitLockedByRules) {
      this.emitFormEvent("xpressui:submit-locked", {
        values: formValues,
        formConfig: this.formConfig,
        submit: this.formConfig?.submit,
        result: {
          message: this.submitLockMessage,
        },
      });
      return;
    }
    const detail: THydratedFormSubmitDetail = {
      values: formValues,
      formConfig: this.formConfig,
      submit: this.formConfig?.submit,
    };
    try {
      const preSubmitResult = await runConfiguredSubmitLifecycleStage(this.formConfig?.submit, "preSubmit", detail);
      if (preSubmitResult.canceled) {
        this.emitFormEvent("xpressui:submit-canceled", {
          ...detail,
          result: {
            reason: "pre-submit-canceled",
          },
        });
        return;
      }
      formValues = preSubmitResult.values;
    } catch (hookError) {
      const hookDetail = {
        ...detail,
        values: formValues,
        error: hookError,
      };
      this.emitFormEvent("xpressui:submit-error", hookDetail);
      this.emitSubmitHookError("preSubmit", hookDetail, hookError);
      this.setWorkflowState("error", hookDetail, undefined, hookError);
      throw hookError;
    }
    detail.values = formValues;
    const shouldContinue = this.emitFormEvent("xpressui:submit", detail, true);

    if (!shouldContinue) {
      return;
    }
    this.setWorkflowState("submitting", detail);

    const customTransport = this.formConfig?.submit?.transport;
    const hasEndpoint = Boolean(this.formConfig?.submit?.endpoint);

    if (!hasEndpoint && !customTransport) {
      this.clearDraft();
      this.setWorkflowState("submitted", detail);
      this.emitFormEvent("xpressui:submit-success", detail);
      try {
        await runConfiguredSubmitLifecycleStage(this.formConfig?.submit, "postSuccess", detail);
      } catch (hookError) {
        this.emitSubmitHookError("postSuccess", detail, hookError);
      }
      return;
    }

    try {
      const submitConfig = this.formConfig?.submit as TFormSubmitRequest;
      const transportResult = customTransport
        ? await resolveSubmitTransportResult(
          await customTransport(formValues, {
            formConfig: this.formConfig,
            submit: submitConfig,
            fields: this.engine.getFields(),
          }),
        )
        : await this.submitToApi(formValues, submitConfig);
      const response = transportResult?.response;
      const result = transportResult?.result;
      this.enforceProviderResponseContract(result, submitConfig);
      const providerResult = normalizeProviderResult(
        submitConfig.action,
        result,
        submitConfig,
      );
      const successDetail = {
        ...detail,
        response,
        result,
        providerResult,
      };
      this.emitFormEvent("xpressui:submit-success", successDetail);
      this.emitProviderMessages(successDetail, providerResult, response, "success");
      this.emitApprovalStateEvents(successDetail, result, providerResult, response);
      const appliedProviderTransition = this.applyProviderTransition(successDetail, response, result, providerResult);
      if (!appliedProviderTransition && this.formConfig?.submit?.action !== "approval-request" && this.formConfig?.submit?.action !== "approval-decision") {
        this.setWorkflowState("submitted", successDetail, response, result);
      }
      this.clearDraft();
      const providerSuccessEvent = getProviderSuccessEventName(this.formConfig?.submit?.action);
      if (providerSuccessEvent) {
        this.emitFormEvent(providerSuccessEvent, successDetail);
      }
      try {
        await runConfiguredSubmitLifecycleStage(this.formConfig?.submit, "postSuccess", successDetail);
      } catch (hookError) {
        this.emitSubmitHookError("postSuccess", successDetail, hookError);
      }
    } catch (error: any) {
      const isNetworkError = !error?.response;
      const storageMode = this.formConfig?.storage?.mode;
      const queueConfigured = storageMode === "queue" || storageMode === "draft-and-queue";
      if (isNetworkError && hasFileValues(formValues) && queueConfigured) {
        const queueError = new Error(
          "Offline queue is disabled for file uploads. Retry while online after re-selecting files.",
        );
        this.emitFormEvent("xpressui:queue-disabled-for-files", {
          ...detail,
          error: queueError,
        });
        this.emitFormEvent("xpressui:submit-error", {
          ...detail,
          error: queueError,
        });
        this.setWorkflowState("error", detail, undefined, queueError);
        return;
      }

      if (isNetworkError && this.shouldUseQueue()) {
        this.enqueueSubmission(formValues);
        this.clearDraft();
        return;
      }

      const providerResult =
        error?.result && this.formConfig?.submit
          ? normalizeProviderResult(this.formConfig.submit.action, error.result, this.formConfig.submit)
          : undefined;
      const errorDetail = {
        ...detail,
        response: error?.response,
        result: error?.result,
        providerResult,
        error,
      };
      this.emitFormEvent("xpressui:submit-error", errorDetail);
      this.emitProviderMessages(errorDetail, providerResult, error?.response, "error");
      this.setWorkflowState("error", errorDetail, error?.response, error?.result);
      const providerErrorEvent = getProviderErrorEventName(this.formConfig?.submit?.action);
      if (providerErrorEvent) {
        this.emitFormEvent(providerErrorEvent, errorDetail);
      }
      try {
        await runConfiguredSubmitLifecycleStage(this.formConfig?.submit, "postFailure", errorDetail);
      } catch (hookError) {
        this.emitSubmitHookError("postFailure", errorDetail, hookError);
      }
      throw error;
    }
  }

  getFieldElement = (fieldName: string) => {
    return getConfiguredFieldElement(this, fieldName);
  }

  resetFieldErrorDisplays = () => {
    Array.from(this.querySelectorAll<HTMLElement>('[data-field-error], [id$="_error"]')).forEach((errorElement) => {
      errorElement.textContent = "";
      errorElement.style.display = "none";
      errorElement.setAttribute("aria-hidden", "true");
    });

    Object.keys(this.engine.getFields()).forEach((fieldName) => {
      const inputElement = this.getFieldElement(fieldName);
      if (!inputElement) {
        return;
      }

      const errorClass = getErrorClass(inputElement);
      inputElement.classList.remove(errorClass);
      inputElement.removeAttribute("aria-invalid");
      this.errors[fieldName] = false;
    });
  }

  renderFieldErrorState = (
    fieldName: string,
    inputElement:
      | HTMLInputElement
      | HTMLSelectElement
      | HTMLTextAreaElement
      | null,
    errorElement: HTMLElement | null,
    touched?: boolean,
    error?: unknown,
    submitFailed?: boolean,
    currentStepIndex?: number,
  ) => {
    if (this.isMultiStepMode()) {
      const activeStepIndex = currentStepIndex ?? this.getCurrentStepIndex();
      const activeStepName = this.stepNames[activeStepIndex];
      if (activeStepName) {
        const stepFields = this.formConfig?.sections?.[activeStepName] || [];
        if (!stepFields.some((f) => f.name === fieldName)) {
          return;
        }
      }
    }

    const suppressErrorDisplay =
      this.suppressedStepErrorFields.has(fieldName)
      && !submitFailed
      && !this.ruleFieldErrors[fieldName];
    const resolvedTouched = suppressErrorDisplay ? false : touched;
    const resolvedSubmitFailed = suppressErrorDisplay ? false : submitFailed;

    renderConfiguredFieldErrorState({
      fieldName,
      inputElement,
      errorElement,
      touched: resolvedTouched,
      submitFailed: resolvedSubmitFailed,
      error,
      errors: this.errors,
      ruleFieldErrors: this.ruleFieldErrors,
      currentStepIndex,
    });

    const hasError = (resolvedTouched || resolvedSubmitFailed) && error;
    const ruleError = this.ruleFieldErrors[fieldName];
    const isInvalid = Boolean(hasError || ruleError);

    const selectionElement = this.querySelector(`#${fieldName}_selection`) as HTMLElement | null;
    if (selectionElement) {
      if (isInvalid) {
        selectionElement.classList.add("border-error");
        selectionElement.classList.remove("border-base-300");
      } else {
        selectionElement.classList.remove("border-error");
        selectionElement.classList.add("border-base-300");
      }
    }
  }

  syncAllFieldErrors = () => {
    Object.keys(this.engine.getFields()).forEach((fieldName) => {
      this.syncFieldErrorDisplay(fieldName);
    });
  }

  syncFieldErrorDisplay = (fieldName: string) => {
    const inputElement = this.getFieldElement(fieldName);
    const errorElement = (this.querySelector(`#${fieldName}_error`) || this.querySelector(`[data-field-error="${fieldName}"]`)) as HTMLElement | null;
    const fieldState = this.form?.getFieldState(fieldName);
    this.renderFieldErrorState(
      fieldName,
      inputElement,
      errorElement,
      Boolean(fieldState?.touched),
      fieldState?.error,
      Boolean(fieldState?.submitFailed) || this.stepSubmitFailed,
      this.getCurrentStepIndex(),
    );
  }

  getFieldContainer = (fieldName: string) => {
    return getConfiguredFieldContainer(this, fieldName);
  }

  getFieldValue = (fieldName: string) => {
    const values = this.form?.getState().values || {};
    return values[fieldName];
  }

  updateConditionalFields = () => {
    this.dynamic.updateConditionalFields();
  }

  refreshRemoteOptions = async (sourceFieldName?: string) => {
    await this.dynamic.refreshRemoteOptions(sourceFieldName);
  }

  registerField = (fieldConfig: TFieldConfig, input: any) => {
    const {
      name
    } = fieldConfig;

    this.form?.registerField(
      name,
      (fieldState) => {
        const { blur, change, error, focus, touched, value, submitFailed } = fieldState;
        const errorElement = (this.querySelector(`#${name}_error`) || this.querySelector(`[data-field-error="${name}"]`)) as HTMLElement | null;
        const selectionElement = this.querySelector(`#${name}_selection`) as HTMLElement | null;
        const inputElement = this.querySelector(`#${name}`) as HTMLElement | null;
        const fieldViewOnly = this.isFieldViewMode(fieldConfig, inputElement);
        const settingField = this.isSettingField(fieldConfig);


        if (!this.registered[name]) {
          // first time, register event listeners
          if (!fieldViewOnly && !settingField) {
            bindConfiguredSimpleFieldEvents({
              input,
              fieldConfig,
              onBlur: () => {
                this.markFieldAsInteracted(name);
                blur();
              },
              onFocus: () => {
                this.markFieldAsInteracted(name);
                focus();
              },
              onChangeValue: (nextValue) => {
                this.markFieldAsInteracted(name);
                change(nextValue);
              },
              onAfterChange: () => {
                this.scheduleDraftSave();
                this.updateConditionalFields();
                void this.refreshRemoteOptions(name);
              },
              resolveFileInputValue: async (nextFieldConfig, nextInput) =>
                this.resolveFileInputValue(nextFieldConfig as TFieldConfig, nextInput),
            });
          }
          if (selectionElement && !fieldViewOnly && !settingField) {
            const dropZoneElement = this.querySelector(
              `[data-file-drop-zone="${name}"]`,
            ) as HTMLElement | null;
            bindConfiguredSelectionFieldEvents({
              selectionElement,
              dropZoneElement,
              input,
              fieldConfig,
              isFileField: isFileFieldType(fieldConfig.type),
              isProductListField: this.isProductListField(fieldConfig),
              isImageGalleryField: this.isImageGalleryField(fieldConfig),
              isQuizField: this.isQuizField(fieldConfig),
              isChoiceListField: this.isChoiceListField(fieldConfig),
              isOpenQuizField: this.isOpenQuizField(fieldConfig),
              getCurrentValue: () => this.getFieldValue(name),
              onChangeValue: (nextValue) => {
                this.markFieldAsInteracted(name);
                change(nextValue);
              },
              onAfterChange: () => {
                this.scheduleDraftSave();
                this.updateConditionalFields();
                void this.refreshRemoteOptions(name);
              },
              getNextProductCartItems: (action, productId) =>
                this.getNextProductCartItems(fieldConfig, this.getFieldValue(name), action, productId),
              getNextImageGallerySelectionItems: (action, imageId) =>
                this.getNextImageGallerySelectionItems(fieldConfig, this.getFieldValue(name), action, imageId),
              getNextQuizSelectionItems: (answerId) =>
                this.getNextQuizSelectionItems(fieldConfig, this.getFieldValue(name), answerId),
              getNextChoiceSelectionValue: (choiceValue) =>
                this.getNextChoiceSelectionValue(fieldConfig, this.getFieldValue(name), choiceValue),
              openProductGallery: (productId) => {
                const product = this.getProductListCatalog(fieldConfig).find((entry) => entry.id === productId);
                if (product) {
                  this.openProductListGallery(product);
                }
              },
              openImageGallery: (imageId) => {
                const imageItem = this.getImageGalleryCatalog(fieldConfig).find((entry) => entry.id === imageId);
                if (imageItem) {
                  this.openImageGalleryItem(imageItem);
                }
              },
              startQrCamera: () => {
                void this.startQrCamera(fieldConfig);
              },
              scanQrCamera: () => {
                void this.scanQrFromLiveVideo(fieldConfig);
              },
              stopQrCamera: () => {
                this.stopQrCamera(name);
              },
              setActiveDocumentScanSlot: (slotIndex) => {
                this.activeDocumentScanSlot[name] = slotIndex;
              },
              refreshSelection: () => {
                this.renderFileSelection(fieldConfig, this.getFieldValue(name), selectionElement);
              },
              removeSelectedFile: (fileIndex) => {
                this.removeSelectedFile(name, fileIndex);
              },
              setFileDragState: (active) => {
                this.setFileDragState(name, active);
              },
              applyDroppedFiles: (files) => {
                void this.applyDroppedFiles(name, files);
              },
            });
          }
          if (fieldViewOnly) {
            const configuredViewValue = this.resolveFieldViewValue(fieldConfig, inputElement, value);
            if (configuredViewValue !== undefined && JSON.stringify(configuredViewValue) !== JSON.stringify(value)) {
              change(configuredViewValue);
            }
          }
          if (settingField) {
            const settingValue = this.getSettingInitialValue(inputElement);
            if (JSON.stringify(settingValue) !== JSON.stringify(value)) {
              change(settingValue);
            }
            if (inputElement) {
              inputElement.style.display = "none";
              inputElement.setAttribute("aria-hidden", "true");
            }
          }
          this.registered[name] = true;
          this.engine.setField(name, fieldConfig);
        }

        // update value
        applyConfiguredFieldValuePresentation({
          input,
          inputElement,
          selectionElement,
          errorElement,
          fieldConfig,
          value,
          settingField,
          fieldViewOnly,
          isQrScanField: this.isQrScanField(fieldConfig),
          isProductListField: this.isProductListField(fieldConfig),
          isImageGalleryField: this.isImageGalleryField(fieldConfig),
          isQuizField: this.isQuizField(fieldConfig),
          isChoiceListField: this.isChoiceListField(fieldConfig),
          isOpenQuizField: this.isOpenQuizField(fieldConfig),
          applyFieldViewPresentation: () => {
            this.applyFieldViewPresentation(fieldConfig, inputElement, selectionElement, errorElement, value);
          },
          renderFileSelection: () => {
            this.renderFileSelection(fieldConfig, value, selectionElement);
          },
          renderProductListSelection: () => {
            this.renderProductListSelection(fieldConfig, value, selectionElement);
          },
          renderImageGallerySelection: () => {
            this.renderImageGallerySelection(fieldConfig, value, selectionElement);
          },
          renderQuizSelection: () => {
            this.renderQuizSelection(fieldConfig, value, selectionElement);
          },
          renderChoiceListSelection: () => {
            this.renderChoiceListSelection(fieldConfig, value, selectionElement);
          },
          getProductCartItems: () => this.getProductCartItems(value),
          getImageGallerySelectionItems: () => this.getImageGallerySelectionItems(value),
          getQuizSelectionItems: () => this.getQuizSelectionItems(value),
          isHybridMode: this.getBaseRenderMode() === "hybrid",
          renderHybridView: () => {
            if (!inputElement) {
              return;
            }
            this.renderViewField(
              fieldConfig,
              value,
              inputElement,
              undefined,
              this.form?.getState().values || {},
            );
            this.emitOutputSnapshot(this.form?.getState().values || {});
          },
        });

        // show/hide errors
        this.renderFieldErrorState(
          name,
          (inputElement || input) as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement | null,
          errorElement,
          touched,
          error,
          submitFailed || this.stepSubmitFailed,
          this.getCurrentStepIndex(),
        );
      },
      {
        value: true,
        error: true,
        touched: true,
        submitFailed: true,
      }
    )
  }
}

if (typeof window !== "undefined") {
  if (!window.customElements.get('form-ui')) {
    window.customElements.define('form-ui', HydratedFormHost);
  }
}
