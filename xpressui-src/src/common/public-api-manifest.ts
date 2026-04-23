export type TPublicApiManifest = {
  schemaVersion: number;
  core: string[];
  integrations: string[];
  advanced: string[];
};

const CORE_PUBLIC_API = [
  "hydrateForm",
  "createFormConfig",
  "createFormPreset",
  "fieldFactory",
  "stepFactory",
  "FormRuntime",
  "FormUploadRuntime",
  "PUBLIC_FORM_SCHEMA_VERSION",
  "getPublicFormSchemaErrors",
  "migratePublicFormConfig",
  "validatePublicFormConfig",
  "assertRuntimeCompatibility",
  "getRuntimeCompatibilityIssues",
];

const INTEGRATION_PUBLIC_API = [
  "DOCUMENT_NORMALIZED_CONTRACT_VERSION",
  "createNormalizedDocumentContract",
  "isDocumentNormalizedContractV2",
  "summarizeNormalizedDocumentContract",
  "WORDPRESS_REST_SUBMIT_ROUTE",
  "WORDPRESS_REST_SUBMIT_ENDPOINT_PLACEHOLDER",
  "getDefaultWordPressSubmitEndpoints",
  "isWordPressBridgeProviderMode",
  "isDefaultWordPressSubmitEndpoint",
  "getWordPressIntegrationEndpoint",
  "resolveExportSubmissionEndpoint",
  "resolveHydrationSubmissionEndpoint",
  "createExportHydrationRuntimeConfig",
  "normalizeExportHydrationRules",
  "createExportManifest",
  "createExportStaticHtmlSnippet",
  "syncShellDomWithConfig",
  "setShellActionButtonsDisabled",
  "attachShellSubmitOverlayHandlers",
  "syncShellPostSubmitUi",
  "setShellFeedbackState",
  "resolveShellSubmitErrorMessage",
  "handleShellSuccessRedirect",
  "attachShellFeedbackHandlers",
  "attachEmbedResizeReporter",
];

const ADVANCED_PUBLIC_API = [
  "createLocalFormAdmin",
  "PROVIDER_RESPONSE_CONTRACT_VERSION",
  "validateProviderResponseEnvelopeV2",
  "isProviderResponseEnvelopeV2",
  "REMOTE_RESUME_CONTRACT_VERSION",
  "isRemoteResumePolicy",
  "getRemoteResumePolicy",
  "getResumeShareCodeClaimPresentation",
  "FormEngineRuntime",
  "FormDynamicRuntime",
  "FormPersistenceRuntime",
  "FormStepRuntime",
  "createFormDebugPanel",
  "createFormAdminPanel",
  "createFormOpsPanel",
  "createResumeStatusPanel",
  "attachFormDebugObserver",
  "registerProvider",
  "getProviderDefinition",
  "createSubmitRequestFromProvider",
  "resolveProviderTransition",
  "createNormalizedProviderResult",
  "isNormalizedProviderResult",
  "normalizeProviderResult",
  "validateProviderRequest",
  "getProviderSuccessEventName",
  "getProviderErrorEventName",
];

export function getPublicApiManifest(): TPublicApiManifest {
  return {
    schemaVersion: 1,
    core: [...CORE_PUBLIC_API],
    integrations: [...INTEGRATION_PUBLIC_API],
    advanced: [...ADVANCED_PUBLIC_API],
  };
}
