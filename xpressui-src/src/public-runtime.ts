// Core runtime types exposed by the main package entrypoint.
export type {
  THydratedFormApprovalState,
  THydratedFormDocumentState,
  THydratedFormProviderTransition,
  THydratedFormSubmitDetail,
  THydratedFormWorkflowState,
  TFormApprovalState,
  TFormWorkflowState,
} from "./ui/form-ui.types";

// Integration-oriented helpers that remain useful to the current export and
// host-delivery story.
export {
  DOCUMENT_NORMALIZED_CONTRACT_VERSION,
  createNormalizedDocumentContract,
  isDocumentNormalizedContractV2,
  summarizeNormalizedDocumentContract,
} from "./common/document-contract";
export { createFormPreset, fieldFactory, stepFactory } from "./common/form-presets";
export { FormRuntime } from "./common/form-runtime";
export { FormUploadRuntime } from "./common/form-upload";
export {
  assertRuntimeCompatibility,
  getRuntimeCompatibilityIssues,
} from "./common/runtime-compatibility";
export {
  PUBLIC_FORM_SCHEMA_VERSION,
  getPublicFormSchemaErrors,
  migratePublicFormConfig,
  validatePublicFormConfig,
} from "./common/public-schema";
export type { TCreateFormPresetOptions, TFormPresetName } from "./common/form-presets";
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
export {
  syncShellDomWithConfig,
  setShellActionButtonsDisabled,
  attachShellSubmitOverlayHandlers,
  syncShellPostSubmitUi,
  setShellFeedbackState,
  resolveShellSubmitErrorMessage,
  handleShellSuccessRedirect,
  attachShellFeedbackHandlers,
} from "./common/shell-dom-sync";
export type {
  TShellFeedbackState,
  TShellI18nResolver,
  TShellSubmitFeedbackConfig,
  TShellFormConfig,
  TAttachShellFeedbackOptions,
  TAttachShellSubmitOverlayOptions,
} from "./common/shell-dom-sync";
export { attachEmbedResizeReporter } from "./common/shell-embed";
export {
  WORDPRESS_REST_SUBMIT_ROUTE,
  WORDPRESS_REST_SUBMIT_ENDPOINT_PLACEHOLDER,
  getDefaultWordPressSubmitEndpoints,
  isWordPressBridgeProviderMode,
  isDefaultWordPressSubmitEndpoint,
  getWordPressIntegrationEndpoint,
  resolveExportSubmissionEndpoint,
  resolveHydrationSubmissionEndpoint,
} from "./common/export-runtime";
export {
  createExportHydrationRuntimeConfig,
  normalizeExportHydrationRules,
} from "./common/export-hydration";
export type {
  TCreateExportHydrationRuntimeConfigOptions,
} from "./common/export-hydration";
export { createExportManifest } from "./common/export-manifest";
export type { TCreateExportManifestOptions } from "./common/export-manifest";
export { createExportStaticHtmlSnippet } from "./common/export-snippets";
export type { TCreateExportStaticHtmlSnippetOptions } from "./common/export-snippets";

export type { TFormUploadState } from "./common/form-upload";
export type { TXPressUIRuntimeTier } from "./common/runtime-compatibility";
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
