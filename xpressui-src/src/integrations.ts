export {
  DOCUMENT_NORMALIZED_CONTRACT_VERSION,
  createNormalizedDocumentContract,
  isDocumentNormalizedContractV2,
  summarizeNormalizedDocumentContract,
} from "./common/document-contract";
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
export type { TCreateExportHydrationRuntimeConfigOptions } from "./common/export-hydration";

export { createExportManifest } from "./common/export-manifest";
export type { TCreateExportManifestOptions } from "./common/export-manifest";

export { createExportStaticHtmlSnippet } from "./common/export-snippets";
export type { TCreateExportStaticHtmlSnippetOptions } from "./common/export-snippets";

