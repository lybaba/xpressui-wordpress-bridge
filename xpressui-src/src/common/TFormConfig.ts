import TFieldConfig from "./TFieldConfig";
import { CUSTOM_SECTION } from "./Constants";
import { generateRuntimeId } from "./id";
import type { TFormRenderMode } from "../ui/form-ui.types";



export const CONTACTFORM_TYPE = 'contactform';
export const ADVANCEDFORM_TYPE = 'advancedform';
export const PRODUCTFORM_TYPE = 'productform';
export const WEBAPP_TYPE = 'webapp';
export const SIGNUPFORM_TYPE = 'signupform';
export const MULTI_STEP_FORM_TYPE = 'multistepform';
export const FORM_MULTI_STEP_MODE = 'form-multi-step';


export type TFormSettings = {
    title: string;
};

export enum RenderingMode {
    CREATE_ENTRY,
    MODIFY_ENTRY,
    VIEW_ENTRY
}

export type TFormSubmitRequest = {
    endpoint: string;
    metadata?: Record<string, any>;
    baseUrl?: string;
    includeSettingFields?: boolean;
    settingFieldAllowlist?: string[];
    providerRoutingPolicy?: 'auto' | 'workflow-first' | 'step-first' | 'workflow-only' | 'step-only';
    providerResponseContract?: 'compat' | 'warn-v2' | 'strict-v2';
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    headers?: Record<string, string>;
    mode?: 'json' | 'form-data';
    includeDocumentData?: boolean;
    documentDataMode?: 'full' | 'summary' | 'fields-only' | 'mrz-only' | 'none';
    documentFieldPaths?: string[];
    formDataArrayMode?: 'brackets' | 'repeat';
    uploadStrategy?: 'standard' | 'presigned';
    presignEndpoint?: string;
    presignMethod?: 'POST' | 'PUT' | 'PATCH';
    presignHeaders?: Record<string, string>;
    presignUploadUrlKey?: string;
    presignFileUrlKey?: string;
    presignResumeUrlKey?: string;
    presignSessionIdKey?: string;
    uploadMethod?: 'POST' | 'PUT';
    uploadChunkMethod?: 'PUT' | 'PATCH' | 'POST';
    uploadChunkSizeMb?: number;
    uploadResumeKey?: string;
    uploadResumeEnabled?: boolean;
    uploadRetryMaxAttempts?: number;
    uploadRetryBaseDelayMs?: number;
    uploadRetryMaxDelayMs?: number;
    uploadRetryJitter?: boolean;
    fileAcceptancePolicy?: TFormUploadPolicyHook;
    contentModerationPolicy?: TFormUploadPolicyHook;
    virusScanPolicy?: TFormUploadPolicyHook;
    action?: string;
    lifecycle?: TFormSubmitLifecycle;
    transport?: TFormSubmitTransport;
};

export type TFormSubmitLifecycleStage = 'preSubmit' | 'postSuccess' | 'postFailure';

export type TFormSubmitLifecycleContext = {
    stage: TFormSubmitLifecycleStage;
    values: Record<string, any>;
    formConfig: TFormConfig | null;
    submit?: TFormSubmitRequest;
    response?: Response;
    result?: any;
    providerResult?: any;
    error?: unknown;
};

export type TFormSubmitLifecycleHookResult = void | boolean | Record<string, any>;

export type TFormSubmitLifecycleHook = (
    context: TFormSubmitLifecycleContext,
) => TFormSubmitLifecycleHookResult | Promise<TFormSubmitLifecycleHookResult>;

export type TFormSubmitLifecycle = {
    preSubmit?: TFormSubmitLifecycleHook | TFormSubmitLifecycleHook[];
    postSuccess?: TFormSubmitLifecycleHook | TFormSubmitLifecycleHook[];
    postFailure?: TFormSubmitLifecycleHook | TFormSubmitLifecycleHook[];
};

export type TFormSubmitTransportContext = {
    formConfig: TFormConfig | null;
    submit?: TFormSubmitRequest;
    fields: Record<string, TFieldConfig>;
};

export type TFormSubmitTransportResult =
  | Response
  | {
      response?: Response;
      result?: any;
    }
  | any;

export type TFormSubmitTransport = (
    values: Record<string, any>,
    context: TFormSubmitTransportContext,
) => TFormSubmitTransportResult | Promise<TFormSubmitTransportResult>;

export type TFormUploadPolicyStage = 'file-acceptance' | 'content-moderation' | 'virus-scan';

export type TFormUploadPolicyContext = {
    stage: TFormUploadPolicyStage;
    fieldName: string;
    file: File;
    values: Record<string, any>;
    submit: TFormSubmitRequest;
    formConfig: TFormConfig | null;
    field?: TFieldConfig;
};

export type TFormUploadPolicyResult =
    | boolean
    | string
    | {
        allowed?: boolean;
        reason?: string;
      };

export type TFormUploadPolicyHook = (
    context: TFormUploadPolicyContext,
) => TFormUploadPolicyResult | Promise<TFormUploadPolicyResult>;

export type TFormValidationContext = {
    formConfig: TFormConfig | null;
};

export type TFormValidationHook = (
    values: Record<string, any>,
    context: TFormValidationContext,
) => Record<string, any> | void;

export type TFormValidationErrorsHook = (
    values: Record<string, any>,
    errors: Record<string, any>,
    context: TFormValidationContext,
) => Record<string, any> | void;

export type TFormValidationI18nCatalog = Record<string, string>;

export type TFormValidationI18nMessages = Record<string, TFormValidationI18nCatalog>;

export type TFormValidationMessageResolverContext = {
    key: string;
    locale: string;
    fallbackLocale: string;
    defaultMessage: string;
    values: Record<string, any>;
    fieldName?: string | null;
    error?: any;
};

export type TFormValidationMessageResolver = (
    context: TFormValidationMessageResolverContext,
) => string | null | undefined;

export type TFormValidationI18nConfig = {
    locale?: string;
    fallbackLocale?: string;
    messages?: TFormValidationI18nMessages;
    resolveMessage?: TFormValidationMessageResolver;
};

export type TFormValidationConfig = {
    preValidate?: TFormValidationHook | TFormValidationHook[];
    customValidate?: TFormValidationHook | TFormValidationHook[];
    postValidate?: TFormValidationErrorsHook | TFormValidationErrorsHook[];
    i18n?: TFormValidationI18nConfig;
};

export type TFormProviderRequest = {
    type: string;
    endpoint: string;
    method?: 'POST' | 'PUT' | 'PATCH';
    headers?: Record<string, string>;
    config?: Record<string, any>;
};

export type TFormStorageConfig = {
    mode: 'none' | 'draft' | 'queue' | 'draft-and-queue';
    adapter?: 'local-storage' | 'indexeddb';
    key?: string;
    autoSaveMs?: number;
    resumeEndpoint?: string;
    shareCodeEndpoint?: string;
    resumeTokenTtlDays?: number;
    encryptionKey?: string;
    retentionDays?: number;
    retentionDraftDays?: number;
    retentionQueueDays?: number;
    retentionDeadLetterDays?: number;
    shareCodeClaimThrottleMs?: number;
    shareCodeClaimMaxAttempts?: number;
    shareCodeClaimWindowMs?: number;
    shareCodeClaimBlockMs?: number;
    resumeTokenSignatureVersion?: string;
    signResumeToken?: (payload: {
        token: string;
        formName?: string;
        savedAt: number;
        issuedAt: number;
        expiresAt?: number;
        snapshot: Record<string, any>;
        resumeEndpoint?: string;
        remote?: boolean;
    }) => string | null;
    verifyResumeToken?: (payload: {
        token: string;
        formName?: string;
        savedAt: number;
        issuedAt: number;
        expiresAt?: number;
        snapshot: Record<string, any>;
        resumeEndpoint?: string;
        remote?: boolean;
        signature?: string;
        signatureVersion?: string;
    }) => boolean;
};

export type TFormStepSection = TFieldConfig;
export type TFormWorkflowStepTargets = Record<string, string>;

export type TFormRuleCondition = {
    field: string;
    operator?: 'equals' | 'not_equals' | 'contains' | 'in' | 'gt' | 'lt' | 'exists' | 'empty';
    value?: any;
};

export type TFormRuleAction = {
    type: 'show' | 'hide' | 'enable' | 'disable' | 'clear-value' | 'set-value' | 'fetch-options' | 'set-error' | 'lock-submit' | 'emit-event';
    field: string;
    value?: any;
    message?: string;
    sourceField?: string;
    template?: string;
    transform?: 'copy' | 'trim' | 'lowercase' | 'uppercase' | 'slugify';
};

export type TFormRule = {
    id?: string;
    logic?: 'AND' | 'OR';
    conditions: TFormRuleCondition[];
    actions: TFormRuleAction[];
};

export type TFormNavigationLabels = {
    submitLabel?: string;
    nextLabel?: string;
    prevLabel?: string;
};

export type TFormStepUiConfig = {
    progressPlacement?: 'top' | 'bottom' | 'hidden';
    navigationPlacement?: 'top' | 'bottom';
    backBehavior?: 'always' | 'never' | 'hide-after-advance';
};

export type TWorkflowConfig = {
    errorMessage?: string;
    providerMode?: 'wordpress-bridge' | string;
    resumeSupport?: 'disabled' | 'enabled' | string;
    submissionMode?: 'multi-step-submit' | 'single-step-submit' | string;
    successMessage?: string;
    documentHandling?: 'none' | string;
    submissionEndpoint?: string;
};

export type TThemeConfig = {
    presetId?: string;
    layout?: string;
    density?: string;
    labelPosition?: string;
    submitWidth?: string;
    backgroundStyle?: string;
    fieldColumns?: number;
    colors?: {
        pageBackground?: string;
        surface?: string;
        text?: string;
        mutedText?: string;
        primary?: string;
        border?: string;
    };
    radius?: {
        card?: number;
        input?: number;
        button?: number;
    };
};

type TFormConfig = {
    version: number;
    id: string;
    uid: string;
    type: string;
    name: string;
    title: string;
    timestamp?: number;
    lockedFields?: string[];
    sections: Record<string, TFieldConfig[]>;
    stepSections?: TFormStepSection[];
    workflowStepTargets?: TFormWorkflowStepTargets;
    renderingMode?: RenderingMode;
    mode?: TFormRenderMode;
    submit?: TFormSubmitRequest;
    provider?: TFormProviderRequest;
    storage?: TFormStorageConfig;
    rules?: TFormRule[];
    validation?: TFormValidationConfig;
    navigationLabels?: TFormNavigationLabels;
    stepUi?: TFormStepUiConfig;
    workflowConfig?: TWorkflowConfig;
    themeConfig?: TThemeConfig;
}

export const DEFAULT_FORM_CONFIG: TFormConfig = {
    version: 1,
    id: generateRuntimeId(),
    uid: generateRuntimeId(),
    timestamp: Math.floor(Date.now() / 1000),
    type: CONTACTFORM_TYPE,
    name: 'demo',
    title: 'demo',
    sections: {[CUSTOM_SECTION]: []},
}

export default TFormConfig;
