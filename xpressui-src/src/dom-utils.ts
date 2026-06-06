import { TEXT_TYPE, TEXTAREA_TYPE, UNKNOWN_TYPE } from "./common/field"
import TFieldConfig from "./common/TFieldConfig"
import TFormConfig, { DEFAULT_FORM_CONFIG } from "./common/TFormConfig"
import { CUSTOM_SECTION } from "./common/Constants"
import { generateRuntimeId } from "./common/id"

export const HTML_ATTR_PREFIX = "data-"

const ATTR_TYPE = "type"
const ATTR_LABEL = "label"
const ATTR_ADMIN_LABEL = "adminLabel"
const ATTR_NAME = "name"
const ATTR_SUB_TYPE = "subType"
const ATTR_REF_TYPE = "refType"
const ATTR_DESC = "desc"
const ATTR_CAN_EDIT = "canEdit"
const ATTR_REQUIRED = "required"
const ATTR_MIN_LEN = "minLen"
const ATTR_MAX_LEN = "maxLen"
const ATTR_PLACEHOLDER = "placeholder"
const ATTR_ACCEPT = "accept"
const ATTR_CAPTURE = "capture"
const ATTR_MULTIPLE = "multiple"
const ATTR_DOCUMENT_SCAN_MODE = "documentScanMode"
const ATTR_ENABLE_DOCUMENT_OCR = "enableDocumentOcr"
const ATTR_REQUIRE_VALID_DOCUMENT_MRZ = "requireValidDocumentMrz"
const ATTR_DOCUMENT_TEXT_TARGET_FIELD = "documentTextTargetField"
const ATTR_DOCUMENT_MRZ_TARGET_FIELD = "documentMrzTargetField"
const ATTR_DOCUMENT_FIRST_NAME_TARGET_FIELD = "documentFirstNameTargetField"
const ATTR_DOCUMENT_LAST_NAME_TARGET_FIELD = "documentLastNameTargetField"
const ATTR_DOCUMENT_NUMBER_TARGET_FIELD = "documentNumberTargetField"
const ATTR_DOCUMENT_NATIONALITY_TARGET_FIELD = "documentNationalityTargetField"
const ATTR_DOCUMENT_BIRTH_DATE_TARGET_FIELD = "documentBirthDateTargetField"
const ATTR_DOCUMENT_EXPIRY_DATE_TARGET_FIELD = "documentExpiryDateTargetField"
const ATTR_DOCUMENT_SEX_TARGET_FIELD = "documentSexTargetField"
const ATTR_DOCUMENT_EXCLUDE_FROM_SUBMIT = "documentExcludeFromSubmit"
const ATTR_DOCUMENT_MASK_PATHS = "documentMaskPaths"
const ATTR_DOCUMENT_EXCLUDE_FROM_DEBUG = "documentExcludeFromDebug"
const ATTR_DOCUMENT_DEBUG_MASK_PATHS = "documentDebugMaskPaths"
const ATTR_FILE_DROP_MODE = "fileDropMode"
const ATTR_MIN_FILES = "minFiles"
const ATTR_MAX_FILES = "maxFiles"
const ATTR_MAX_FILE_SIZE_MB = "maxFileSizeMb"
const ATTR_MAX_TOTAL_FILE_SIZE_MB = "maxTotalFileSizeMb"
const ATTR_FORM_DATA_FIELD_NAME = "formDataFieldName"
const ATTR_FILE_TYPE_ERROR_MSG = "fileTypeErrorMsg"
const ATTR_FILE_SIZE_ERROR_MSG = "fileSizeErrorMsg"
const ATTR_PATTERN = "pattern"
const ATTR_MEDIA_ID = "mediaId"
const ATTR_MIN_VALUE = "min"
const ATTR_MAX_VALUE = "max"
const ATTR_STEP_VALUE = "step"
const ATTR_DEFAULT_VALUE = "defaultValue"
const ATTR_INCLUDE_IN_SUBMIT = "includeInSubmit"
const ATTR_MIN_NUM_OF_CHOICES = "minNumOfChoices"
const ATTR_MAX_NUM_OF_CHOICES = "maxNumOfChoices"
const ATTR_HELP_TEXT = "helpText"
const ATTR_ERROR_MSG = "errorMsg"
const ATTR_SUCCESS_MSG = "successMsg"
const ATTR_NEXT_BTN_LABEL = "nextBtnLabel"
const ATTR_VIEW_TEMPLATE = "viewTemplate"
const ATTR_VIEW_TEMPLATE_UNSAFE = "viewTemplateUnsafe"
const ATTR_CHOICE_GROUP_ID = "choiceGroupId"
const ATTR_CHOICES = "choices"
const ATTR_MEDIA_INFO = "mediaInfo"
const ATTR_LINK_TYPE = "linkType"
const ATTR_LINK_PATH = "linkPath"
const ATTR_ID = "id"
const ATTR_UID = "uid"
const ATTR_TIMESTAMP = "timestamp"
const ATTR_SECTIONS = "sections"
const ATTR_RENDERING_MODE = "RenderingMode"
const ATTR_SUBMIT_ENDPOINT = "submitEndpoint"
const ATTR_SUBMIT_BASE_URL = "submitBaseUrl"
const ATTR_SUBMIT_INCLUDE_SETTING_FIELDS = "submitIncludeSettingFields"
const ATTR_SUBMIT_SETTING_FIELD_ALLOWLIST = "submitSettingFieldAllowlist"
const ATTR_SUBMIT_PROVIDER_ROUTING_POLICY = "submitProviderRoutingPolicy"
const ATTR_SUBMIT_PROVIDER_RESPONSE_CONTRACT = "submitProviderResponseContract"
const ATTR_SUBMIT_METHOD = "submitMethod"
const ATTR_SUBMIT_MODE = "submitMode"
const ATTR_SUBMIT_INCLUDE_DOCUMENT_DATA = "submitIncludeDocumentData"
const ATTR_SUBMIT_DOCUMENT_DATA_MODE = "submitDocumentDataMode"
const ATTR_SUBMIT_DOCUMENT_FIELD_PATHS = "submitDocumentFieldPaths"
const ATTR_SUBMIT_FORM_DATA_ARRAY_MODE = "submitFormDataArrayMode"
const ATTR_SUBMIT_UPLOAD_STRATEGY = "submitUploadStrategy"
const ATTR_SUBMIT_PRESIGN_ENDPOINT = "submitPresignEndpoint"
const ATTR_SUBMIT_PRESIGN_METHOD = "submitPresignMethod"
const ATTR_SUBMIT_PRESIGN_UPLOAD_URL_KEY = "submitPresignUploadUrlKey"
const ATTR_SUBMIT_PRESIGN_FILE_URL_KEY = "submitPresignFileUrlKey"
const ATTR_SUBMIT_UPLOAD_METHOD = "submitUploadMethod"
const ATTR_SUBMIT_UPLOAD_CHUNK_METHOD = "submitUploadChunkMethod"
const ATTR_SUBMIT_UPLOAD_CHUNK_SIZE_MB = "submitUploadChunkSizeMb"
const ATTR_SUBMIT_UPLOAD_RESUME_ENABLED = "submitUploadResumeEnabled"
const ATTR_SUBMIT_UPLOAD_RESUME_KEY = "submitUploadResumeKey"
const ATTR_SUBMIT_UPLOAD_RETRY_MAX_ATTEMPTS = "submitUploadRetryMaxAttempts"
const ATTR_SUBMIT_UPLOAD_RETRY_BASE_DELAY_MS = "submitUploadRetryBaseDelayMs"
const ATTR_SUBMIT_UPLOAD_RETRY_MAX_DELAY_MS = "submitUploadRetryMaxDelayMs"
const ATTR_SUBMIT_UPLOAD_RETRY_JITTER = "submitUploadRetryJitter"
const ATTR_SUBMIT_ACTION = "submitAction"
const ATTR_VISIBLE_WHEN_FIELD = "visibleWhenField"
const ATTR_VISIBLE_WHEN_EQUALS = "visibleWhenEquals"
const ATTR_OPTIONS_ENDPOINT = "optionsEndpoint"
const ATTR_OPTIONS_DEPENDS_ON = "optionsDependsOn"
const ATTR_OPTIONS_LABEL_KEY = "optionsLabelKey"
const ATTR_OPTIONS_VALUE_KEY = "optionsValueKey"
const ATTR_STEP_SKIPPABLE = "stepSkippable"
const ATTR_STEP_VALIDATE_WHEN_WORKFLOW_STATES = "stepValidateWhenWorkflowStates"
const ATTR_STEP_SUMMARY = "stepSummary"
const ATTR_NEXT_STEP_WHEN_FIELD = "nextStepWhenField"
const ATTR_NEXT_STEP_WHEN_EQUALS = "nextStepWhenEquals"
const ATTR_NEXT_STEP_WHEN_NOT_EQUALS = "nextStepWhenNotEquals"
const ATTR_NEXT_STEP_TARGET = "nextStepTarget"
const ATTR_STEP_TRANSITIONS = "stepTransitions"
const ATTR_STORAGE_MODE = "storageMode"
const ATTR_STORAGE_ADAPTER = "storageAdapter"
const ATTR_STORAGE_KEY = "storageKey"
const ATTR_STORAGE_AUTOSAVE_MS = "storageAutoSaveMs"
const ATTR_STORAGE_RESUME_ENDPOINT = "storageResumeEndpoint"
const ATTR_STORAGE_SHARE_CODE_ENDPOINT = "storageShareCodeEndpoint"
const ATTR_STORAGE_RESUME_TOKEN_TTL_DAYS = "storageResumeTokenTtlDays"
const ATTR_STORAGE_RESUME_TOKEN_SIGNATURE_VERSION = "storageResumeTokenSignatureVersion"
const ATTR_STORAGE_ENCRYPTION_KEY = "storageEncryptionKey"
const ATTR_STORAGE_RETENTION_DAYS = "storageRetentionDays"
const ATTR_STORAGE_RETENTION_DRAFT_DAYS = "storageRetentionDraftDays"
const ATTR_STORAGE_RETENTION_QUEUE_DAYS = "storageRetentionQueueDays"
const ATTR_STORAGE_RETENTION_DEAD_LETTER_DAYS = "storageRetentionDeadLetterDays"
const ATTR_STORAGE_SHARE_CODE_CLAIM_THROTTLE_MS = "storageShareCodeClaimThrottleMs"
const ATTR_STORAGE_SHARE_CODE_CLAIM_MAX_ATTEMPTS = "storageShareCodeClaimMaxAttempts"
const ATTR_STORAGE_SHARE_CODE_CLAIM_WINDOW_MS = "storageShareCodeClaimWindowMs"
const ATTR_STORAGE_SHARE_CODE_CLAIM_BLOCK_MS = "storageShareCodeClaimBlockMs"
const ATTR_STEP_PREVIOUS_LABEL = "stepPreviousLabel"
const ATTR_STEP_NEXT_LABEL = "stepNextLabel"
const ATTR_STEP_PROGRESS_PLACEMENT = "stepProgressPlacement"
const ATTR_STEP_NAVIGATION_PLACEMENT = "stepNavigationPlacement"
const ATTR_STEP_BACK_BEHAVIOR = "stepBackBehavior"
const ATTR_WORKFLOW_STEP_TARGETS = "workflowStepTargets"
const ATTR_VERSION = "version"
const ATTR_RULES = "rules"


export const HTML_ATTR_TYPE = `${HTML_ATTR_PREFIX}type`
export const HTML_ATTR_LABEL = `${HTML_ATTR_PREFIX}label`
export const HTML_ATTR_ADMIN_LABEL = `${HTML_ATTR_PREFIX}admin-label`
export const HTML_ATTR_NAME = `${HTML_ATTR_PREFIX}name`
export const HTML_ATTR_SUB_TYPE = `${HTML_ATTR_PREFIX}sub-type`
export const HTML_ATTR_REF_TYPE = `${HTML_ATTR_PREFIX}ref-type`
export const HTML_ATTR_DESC = `${HTML_ATTR_PREFIX}desc`
export const HTML_ATTR_CAN_EDIT = `${HTML_ATTR_PREFIX}can-edit`
export const HTML_ATTR_REQUIRED = `${HTML_ATTR_PREFIX}required`
export const HTML_ATTR_MIN_LEN = `${HTML_ATTR_PREFIX}min-len`
export const HTML_ATTR_MAX_LEN = `${HTML_ATTR_PREFIX}max-len`
export const HTML_ATTR_PLACEHOLDER = `${HTML_ATTR_PREFIX}placeholder`
export const HTML_ATTR_ACCEPT = `${HTML_ATTR_PREFIX}accept`
export const HTML_ATTR_CAPTURE = `${HTML_ATTR_PREFIX}capture`
export const HTML_ATTR_MULTIPLE = `${HTML_ATTR_PREFIX}multiple`
export const HTML_ATTR_DOCUMENT_SCAN_MODE = `${HTML_ATTR_PREFIX}document-scan-mode`
export const HTML_ATTR_ENABLE_DOCUMENT_OCR = `${HTML_ATTR_PREFIX}enable-document-ocr`
export const HTML_ATTR_REQUIRE_VALID_DOCUMENT_MRZ = `${HTML_ATTR_PREFIX}require-valid-document-mrz`
export const HTML_ATTR_DOCUMENT_TEXT_TARGET_FIELD = `${HTML_ATTR_PREFIX}document-text-target-field`
export const HTML_ATTR_DOCUMENT_MRZ_TARGET_FIELD = `${HTML_ATTR_PREFIX}document-mrz-target-field`
export const HTML_ATTR_DOCUMENT_FIRST_NAME_TARGET_FIELD = `${HTML_ATTR_PREFIX}document-first-name-target-field`
export const HTML_ATTR_DOCUMENT_LAST_NAME_TARGET_FIELD = `${HTML_ATTR_PREFIX}document-last-name-target-field`
export const HTML_ATTR_DOCUMENT_NUMBER_TARGET_FIELD = `${HTML_ATTR_PREFIX}document-number-target-field`
export const HTML_ATTR_DOCUMENT_NATIONALITY_TARGET_FIELD = `${HTML_ATTR_PREFIX}document-nationality-target-field`
export const HTML_ATTR_DOCUMENT_BIRTH_DATE_TARGET_FIELD = `${HTML_ATTR_PREFIX}document-birth-date-target-field`
export const HTML_ATTR_DOCUMENT_EXPIRY_DATE_TARGET_FIELD = `${HTML_ATTR_PREFIX}document-expiry-date-target-field`
export const HTML_ATTR_DOCUMENT_SEX_TARGET_FIELD = `${HTML_ATTR_PREFIX}document-sex-target-field`
export const HTML_ATTR_DOCUMENT_EXCLUDE_FROM_SUBMIT = `${HTML_ATTR_PREFIX}document-exclude-from-submit`
export const HTML_ATTR_DOCUMENT_MASK_PATHS = `${HTML_ATTR_PREFIX}document-mask-paths`
export const HTML_ATTR_DOCUMENT_EXCLUDE_FROM_DEBUG = `${HTML_ATTR_PREFIX}document-exclude-from-debug`
export const HTML_ATTR_DOCUMENT_DEBUG_MASK_PATHS = `${HTML_ATTR_PREFIX}document-debug-mask-paths`
export const HTML_ATTR_FILE_DROP_MODE = `${HTML_ATTR_PREFIX}file-drop-mode`
export const HTML_ATTR_MIN_FILES = `${HTML_ATTR_PREFIX}min-files`
export const HTML_ATTR_MAX_FILES = `${HTML_ATTR_PREFIX}max-files`
export const HTML_ATTR_MAX_FILE_SIZE_MB = `${HTML_ATTR_PREFIX}max-file-size-mb`
export const HTML_ATTR_MAX_TOTAL_FILE_SIZE_MB = `${HTML_ATTR_PREFIX}max-total-file-size-mb`
export const HTML_ATTR_FORM_DATA_FIELD_NAME = `${HTML_ATTR_PREFIX}form-data-field-name`
export const HTML_ATTR_FILE_TYPE_ERROR_MSG = `${HTML_ATTR_PREFIX}file-type-error-msg`
export const HTML_ATTR_FILE_SIZE_ERROR_MSG = `${HTML_ATTR_PREFIX}file-size-error-msg`
export const HTML_ATTR_PATTERN = `${HTML_ATTR_PREFIX}pattern`
export const HTML_ATTR_MEDIA_ID = `${HTML_ATTR_PREFIX}media-id`
export const HTML_ATTR_MIN_VALUE = `${HTML_ATTR_PREFIX}min`
export const HTML_ATTR_MAX_VALUE = `${HTML_ATTR_PREFIX}max`
export const HTML_ATTR_STEP_VALUE = `${HTML_ATTR_PREFIX}step`
export const HTML_ATTR_DEFAULT_VALUE = `${HTML_ATTR_PREFIX}default-value`
export const HTML_ATTR_INCLUDE_IN_SUBMIT = `${HTML_ATTR_PREFIX}include-in-submit`
export const HTML_ATTR_MIN_NUM_OF_CHOICES = `${HTML_ATTR_PREFIX}min-num-of-choices`
export const HTML_ATTR_MAX_NUM_OF_CHOICES = `${HTML_ATTR_PREFIX}max-num-of-choices`
export const HTML_ATTR_HELP_TEXT = `${HTML_ATTR_PREFIX}help-text`
export const HTML_ATTR_ERROR_MSG = `${HTML_ATTR_PREFIX}error-msg`
export const HTML_ATTR_SUCCESS_MSG = `${HTML_ATTR_PREFIX}success-msg`
export const HTML_ATTR_NEXT_BTN_LABEL = `${HTML_ATTR_PREFIX}next-btn-label`
export const HTML_ATTR_VIEW_TEMPLATE = `${HTML_ATTR_PREFIX}view-template`
export const HTML_ATTR_VIEW_TEMPLATE_UNSAFE = `${HTML_ATTR_PREFIX}view-template-unsafe`
export const HTML_ATTR_CHOICE_GROUP_ID = `${HTML_ATTR_PREFIX}choice-group-id`
export const HTML_ATTR_CHOICES = `${HTML_ATTR_PREFIX}choices`
export const HTML_ATTR_MEDIA_INFO = `${HTML_ATTR_PREFIX}media-info`
export const HTML_ATTR_LINK_TYPE = `${HTML_ATTR_PREFIX}link-type`
export const HTML_ATTR_LINK_PATH = `${HTML_ATTR_PREFIX}link-path`
export const HTML_ATTR_ID = `${HTML_ATTR_PREFIX}id`
export const HTML_ATTR_UID = `${HTML_ATTR_PREFIX}uid`
export const HTML_ATTR_TIMESTAMP = `${HTML_ATTR_PREFIX}timestamp`
export const HTML_ATTR_SECTIONS = `${HTML_ATTR_PREFIX}sections`
export const HTML_ATTR_RENDERING_MODE = `${HTML_ATTR_PREFIX}rendering-mode`
export const HTML_ATTR_SUBMIT_ENDPOINT = `${HTML_ATTR_PREFIX}submit-endpoint`
export const HTML_ATTR_SUBMIT_BASE_URL = `${HTML_ATTR_PREFIX}submit-base-url`
export const HTML_ATTR_SUBMIT_INCLUDE_SETTING_FIELDS = `${HTML_ATTR_PREFIX}submit-include-setting-fields`
export const HTML_ATTR_SUBMIT_SETTING_FIELD_ALLOWLIST = `${HTML_ATTR_PREFIX}submit-setting-field-allowlist`
export const HTML_ATTR_SUBMIT_PROVIDER_ROUTING_POLICY = `${HTML_ATTR_PREFIX}submit-provider-routing-policy`
export const HTML_ATTR_SUBMIT_PROVIDER_RESPONSE_CONTRACT = `${HTML_ATTR_PREFIX}submit-provider-response-contract`
export const HTML_ATTR_SUBMIT_METHOD = `${HTML_ATTR_PREFIX}submit-method`
export const HTML_ATTR_SUBMIT_MODE = `${HTML_ATTR_PREFIX}submit-mode`
export const HTML_ATTR_SUBMIT_INCLUDE_DOCUMENT_DATA = `${HTML_ATTR_PREFIX}submit-include-document-data`
export const HTML_ATTR_SUBMIT_DOCUMENT_DATA_MODE = `${HTML_ATTR_PREFIX}submit-document-data-mode`
export const HTML_ATTR_SUBMIT_DOCUMENT_FIELD_PATHS = `${HTML_ATTR_PREFIX}submit-document-field-paths`
export const HTML_ATTR_SUBMIT_FORM_DATA_ARRAY_MODE = `${HTML_ATTR_PREFIX}submit-form-data-array-mode`
export const HTML_ATTR_SUBMIT_UPLOAD_STRATEGY = `${HTML_ATTR_PREFIX}submit-upload-strategy`
export const HTML_ATTR_SUBMIT_PRESIGN_ENDPOINT = `${HTML_ATTR_PREFIX}submit-presign-endpoint`
export const HTML_ATTR_SUBMIT_PRESIGN_METHOD = `${HTML_ATTR_PREFIX}submit-presign-method`
export const HTML_ATTR_SUBMIT_PRESIGN_UPLOAD_URL_KEY = `${HTML_ATTR_PREFIX}submit-presign-upload-url-key`
export const HTML_ATTR_SUBMIT_PRESIGN_FILE_URL_KEY = `${HTML_ATTR_PREFIX}submit-presign-file-url-key`
export const HTML_ATTR_SUBMIT_UPLOAD_METHOD = `${HTML_ATTR_PREFIX}submit-upload-method`
export const HTML_ATTR_SUBMIT_UPLOAD_CHUNK_METHOD = `${HTML_ATTR_PREFIX}submit-upload-chunk-method`
export const HTML_ATTR_SUBMIT_UPLOAD_CHUNK_SIZE_MB = `${HTML_ATTR_PREFIX}submit-upload-chunk-size-mb`
export const HTML_ATTR_SUBMIT_UPLOAD_RESUME_ENABLED = `${HTML_ATTR_PREFIX}submit-upload-resume-enabled`
export const HTML_ATTR_SUBMIT_UPLOAD_RESUME_KEY = `${HTML_ATTR_PREFIX}submit-upload-resume-key`
export const HTML_ATTR_SUBMIT_UPLOAD_RETRY_MAX_ATTEMPTS = `${HTML_ATTR_PREFIX}submit-upload-retry-max-attempts`
export const HTML_ATTR_SUBMIT_UPLOAD_RETRY_BASE_DELAY_MS = `${HTML_ATTR_PREFIX}submit-upload-retry-base-delay-ms`
export const HTML_ATTR_SUBMIT_UPLOAD_RETRY_MAX_DELAY_MS = `${HTML_ATTR_PREFIX}submit-upload-retry-max-delay-ms`
export const HTML_ATTR_SUBMIT_UPLOAD_RETRY_JITTER = `${HTML_ATTR_PREFIX}submit-upload-retry-jitter`
export const HTML_ATTR_SUBMIT_ACTION = `${HTML_ATTR_PREFIX}submit-action`
export const HTML_ATTR_VISIBLE_WHEN_FIELD = `${HTML_ATTR_PREFIX}visible-when-field`
export const HTML_ATTR_VISIBLE_WHEN_EQUALS = `${HTML_ATTR_PREFIX}visible-when-equals`
export const HTML_ATTR_OPTIONS_ENDPOINT = `${HTML_ATTR_PREFIX}options-endpoint`
export const HTML_ATTR_OPTIONS_DEPENDS_ON = `${HTML_ATTR_PREFIX}options-depends-on`
export const HTML_ATTR_OPTIONS_LABEL_KEY = `${HTML_ATTR_PREFIX}options-label-key`
export const HTML_ATTR_OPTIONS_VALUE_KEY = `${HTML_ATTR_PREFIX}options-value-key`
export const HTML_ATTR_STEP_SKIPPABLE = `${HTML_ATTR_PREFIX}step-skippable`
export const HTML_ATTR_STEP_VALIDATE_WHEN_WORKFLOW_STATES = `${HTML_ATTR_PREFIX}step-validate-when-workflow-states`
export const HTML_ATTR_STEP_SUMMARY = `${HTML_ATTR_PREFIX}step-summary`
export const HTML_ATTR_NEXT_STEP_WHEN_FIELD = `${HTML_ATTR_PREFIX}next-step-when-field`
export const HTML_ATTR_NEXT_STEP_WHEN_EQUALS = `${HTML_ATTR_PREFIX}next-step-when-equals`
export const HTML_ATTR_NEXT_STEP_WHEN_NOT_EQUALS = `${HTML_ATTR_PREFIX}next-step-when-not-equals`
export const HTML_ATTR_NEXT_STEP_TARGET = `${HTML_ATTR_PREFIX}next-step-target`
export const HTML_ATTR_STEP_TRANSITIONS = `${HTML_ATTR_PREFIX}step-transitions`
export const HTML_ATTR_STORAGE_MODE = `${HTML_ATTR_PREFIX}storage-mode`
export const HTML_ATTR_STORAGE_ADAPTER = `${HTML_ATTR_PREFIX}storage-adapter`
export const HTML_ATTR_STORAGE_KEY = `${HTML_ATTR_PREFIX}storage-key`
export const HTML_ATTR_STORAGE_AUTOSAVE_MS = `${HTML_ATTR_PREFIX}storage-autosave-ms`
export const HTML_ATTR_STORAGE_RESUME_ENDPOINT = `${HTML_ATTR_PREFIX}storage-resume-endpoint`
export const HTML_ATTR_STORAGE_SHARE_CODE_ENDPOINT = `${HTML_ATTR_PREFIX}storage-share-code-endpoint`
export const HTML_ATTR_STORAGE_RESUME_TOKEN_TTL_DAYS = `${HTML_ATTR_PREFIX}storage-resume-token-ttl-days`
export const HTML_ATTR_STORAGE_RESUME_TOKEN_SIGNATURE_VERSION = `${HTML_ATTR_PREFIX}storage-resume-token-signature-version`
export const HTML_ATTR_STORAGE_ENCRYPTION_KEY = `${HTML_ATTR_PREFIX}storage-encryption-key`
export const HTML_ATTR_STORAGE_RETENTION_DAYS = `${HTML_ATTR_PREFIX}storage-retention-days`
export const HTML_ATTR_STORAGE_RETENTION_DRAFT_DAYS = `${HTML_ATTR_PREFIX}storage-retention-draft-days`
export const HTML_ATTR_STORAGE_RETENTION_QUEUE_DAYS = `${HTML_ATTR_PREFIX}storage-retention-queue-days`
export const HTML_ATTR_STORAGE_RETENTION_DEAD_LETTER_DAYS = `${HTML_ATTR_PREFIX}storage-retention-dead-letter-days`
export const HTML_ATTR_STORAGE_SHARE_CODE_CLAIM_THROTTLE_MS = `${HTML_ATTR_PREFIX}storage-share-code-claim-throttle-ms`
export const HTML_ATTR_STORAGE_SHARE_CODE_CLAIM_MAX_ATTEMPTS = `${HTML_ATTR_PREFIX}storage-share-code-claim-max-attempts`
export const HTML_ATTR_STORAGE_SHARE_CODE_CLAIM_WINDOW_MS = `${HTML_ATTR_PREFIX}storage-share-code-claim-window-ms`
export const HTML_ATTR_STORAGE_SHARE_CODE_CLAIM_BLOCK_MS = `${HTML_ATTR_PREFIX}storage-share-code-claim-block-ms`
export const HTML_ATTR_STEP_PREVIOUS_LABEL = `${HTML_ATTR_PREFIX}step-previous-label`
export const HTML_ATTR_STEP_NEXT_LABEL = `${HTML_ATTR_PREFIX}step-next-label`
export const HTML_ATTR_STEP_PROGRESS_PLACEMENT = `${HTML_ATTR_PREFIX}step-progress-placement`
export const HTML_ATTR_STEP_NAVIGATION_PLACEMENT = `${HTML_ATTR_PREFIX}step-navigation-placement`
export const HTML_ATTR_STEP_BACK_BEHAVIOR = `${HTML_ATTR_PREFIX}step-back-behavior`
export const HTML_ATTR_WORKFLOW_STEP_TARGETS = `${HTML_ATTR_PREFIX}workflow-step-targets`
export const HTML_ATTR_VERSION = `${HTML_ATTR_PREFIX}version`
export const HTML_ATTR_RULES = `${HTML_ATTR_PREFIX}rules`

const ATTR_MAP = {
    [HTML_ATTR_TYPE]: ATTR_TYPE,
    [HTML_ATTR_LABEL]: ATTR_LABEL,
    [HTML_ATTR_ADMIN_LABEL]: ATTR_ADMIN_LABEL,
    [HTML_ATTR_NAME]: ATTR_NAME,
    [HTML_ATTR_SUB_TYPE]: ATTR_SUB_TYPE,
    [HTML_ATTR_REF_TYPE]: ATTR_REF_TYPE,
    [HTML_ATTR_DESC]: ATTR_DESC,
    [HTML_ATTR_CAN_EDIT]: ATTR_CAN_EDIT,
    [HTML_ATTR_REQUIRED]: ATTR_REQUIRED,
    [HTML_ATTR_MIN_LEN]: ATTR_MIN_LEN,
    [HTML_ATTR_MAX_LEN]: ATTR_MAX_LEN,
    [HTML_ATTR_PLACEHOLDER]: ATTR_PLACEHOLDER,
    [HTML_ATTR_ACCEPT]: ATTR_ACCEPT,
    [HTML_ATTR_CAPTURE]: ATTR_CAPTURE,
    [HTML_ATTR_MULTIPLE]: ATTR_MULTIPLE,
    [HTML_ATTR_DOCUMENT_SCAN_MODE]: ATTR_DOCUMENT_SCAN_MODE,
    [HTML_ATTR_ENABLE_DOCUMENT_OCR]: ATTR_ENABLE_DOCUMENT_OCR,
    [HTML_ATTR_REQUIRE_VALID_DOCUMENT_MRZ]: ATTR_REQUIRE_VALID_DOCUMENT_MRZ,
    [HTML_ATTR_DOCUMENT_TEXT_TARGET_FIELD]: ATTR_DOCUMENT_TEXT_TARGET_FIELD,
    [HTML_ATTR_DOCUMENT_MRZ_TARGET_FIELD]: ATTR_DOCUMENT_MRZ_TARGET_FIELD,
    [HTML_ATTR_DOCUMENT_FIRST_NAME_TARGET_FIELD]: ATTR_DOCUMENT_FIRST_NAME_TARGET_FIELD,
    [HTML_ATTR_DOCUMENT_LAST_NAME_TARGET_FIELD]: ATTR_DOCUMENT_LAST_NAME_TARGET_FIELD,
    [HTML_ATTR_DOCUMENT_NUMBER_TARGET_FIELD]: ATTR_DOCUMENT_NUMBER_TARGET_FIELD,
    [HTML_ATTR_DOCUMENT_NATIONALITY_TARGET_FIELD]: ATTR_DOCUMENT_NATIONALITY_TARGET_FIELD,
    [HTML_ATTR_DOCUMENT_BIRTH_DATE_TARGET_FIELD]: ATTR_DOCUMENT_BIRTH_DATE_TARGET_FIELD,
    [HTML_ATTR_DOCUMENT_EXPIRY_DATE_TARGET_FIELD]: ATTR_DOCUMENT_EXPIRY_DATE_TARGET_FIELD,
    [HTML_ATTR_DOCUMENT_SEX_TARGET_FIELD]: ATTR_DOCUMENT_SEX_TARGET_FIELD,
    [HTML_ATTR_DOCUMENT_EXCLUDE_FROM_SUBMIT]: ATTR_DOCUMENT_EXCLUDE_FROM_SUBMIT,
    [HTML_ATTR_DOCUMENT_MASK_PATHS]: ATTR_DOCUMENT_MASK_PATHS,
    [HTML_ATTR_DOCUMENT_EXCLUDE_FROM_DEBUG]: ATTR_DOCUMENT_EXCLUDE_FROM_DEBUG,
    [HTML_ATTR_DOCUMENT_DEBUG_MASK_PATHS]: ATTR_DOCUMENT_DEBUG_MASK_PATHS,
    [HTML_ATTR_FILE_DROP_MODE]: ATTR_FILE_DROP_MODE,
    [HTML_ATTR_MIN_FILES]: ATTR_MIN_FILES,
    [HTML_ATTR_MAX_FILES]: ATTR_MAX_FILES,
    [HTML_ATTR_MAX_FILE_SIZE_MB]: ATTR_MAX_FILE_SIZE_MB,
    [HTML_ATTR_MAX_TOTAL_FILE_SIZE_MB]: ATTR_MAX_TOTAL_FILE_SIZE_MB,
    [HTML_ATTR_FORM_DATA_FIELD_NAME]: ATTR_FORM_DATA_FIELD_NAME,
    [HTML_ATTR_FILE_TYPE_ERROR_MSG]: ATTR_FILE_TYPE_ERROR_MSG,
    [HTML_ATTR_FILE_SIZE_ERROR_MSG]: ATTR_FILE_SIZE_ERROR_MSG,
    [HTML_ATTR_PATTERN]: ATTR_PATTERN,
    [HTML_ATTR_MEDIA_ID]: ATTR_MEDIA_ID,
    [HTML_ATTR_MIN_VALUE]: ATTR_MIN_VALUE,
    [HTML_ATTR_MAX_VALUE]: ATTR_MAX_VALUE,
    [HTML_ATTR_STEP_VALUE]: ATTR_STEP_VALUE,
    [HTML_ATTR_DEFAULT_VALUE]: ATTR_DEFAULT_VALUE,
    [HTML_ATTR_INCLUDE_IN_SUBMIT]: ATTR_INCLUDE_IN_SUBMIT,
    [HTML_ATTR_MIN_NUM_OF_CHOICES]: ATTR_MIN_NUM_OF_CHOICES,
    [HTML_ATTR_MAX_NUM_OF_CHOICES]: ATTR_MAX_NUM_OF_CHOICES,
    [HTML_ATTR_HELP_TEXT]: ATTR_HELP_TEXT,
    [HTML_ATTR_ERROR_MSG]: ATTR_ERROR_MSG,
    [HTML_ATTR_SUCCESS_MSG]: ATTR_SUCCESS_MSG,
    [HTML_ATTR_NEXT_BTN_LABEL]: ATTR_NEXT_BTN_LABEL,
    [HTML_ATTR_VIEW_TEMPLATE]: ATTR_VIEW_TEMPLATE,
    [HTML_ATTR_VIEW_TEMPLATE_UNSAFE]: ATTR_VIEW_TEMPLATE_UNSAFE,
    [HTML_ATTR_CHOICE_GROUP_ID]: ATTR_CHOICE_GROUP_ID,
    [HTML_ATTR_CHOICES]: ATTR_CHOICES,
    [HTML_ATTR_MEDIA_INFO]: ATTR_MEDIA_INFO,
    [HTML_ATTR_LINK_TYPE]: ATTR_LINK_TYPE,
    [HTML_ATTR_LINK_PATH]: ATTR_LINK_PATH,
    [HTML_ATTR_ID]: ATTR_ID,
    [HTML_ATTR_UID]: ATTR_UID,
    [HTML_ATTR_TIMESTAMP]: ATTR_TIMESTAMP,
    [HTML_ATTR_SECTIONS]: ATTR_SECTIONS,
    [HTML_ATTR_RENDERING_MODE]: ATTR_RENDERING_MODE,
    [HTML_ATTR_SUBMIT_ENDPOINT]: ATTR_SUBMIT_ENDPOINT,
    [HTML_ATTR_SUBMIT_BASE_URL]: ATTR_SUBMIT_BASE_URL,
    [HTML_ATTR_SUBMIT_INCLUDE_SETTING_FIELDS]: ATTR_SUBMIT_INCLUDE_SETTING_FIELDS,
    [HTML_ATTR_SUBMIT_SETTING_FIELD_ALLOWLIST]: ATTR_SUBMIT_SETTING_FIELD_ALLOWLIST,
    [HTML_ATTR_SUBMIT_PROVIDER_ROUTING_POLICY]: ATTR_SUBMIT_PROVIDER_ROUTING_POLICY,
    [HTML_ATTR_SUBMIT_PROVIDER_RESPONSE_CONTRACT]: ATTR_SUBMIT_PROVIDER_RESPONSE_CONTRACT,
    [HTML_ATTR_SUBMIT_METHOD]: ATTR_SUBMIT_METHOD,
    [HTML_ATTR_SUBMIT_MODE]: ATTR_SUBMIT_MODE,
    [HTML_ATTR_SUBMIT_INCLUDE_DOCUMENT_DATA]: ATTR_SUBMIT_INCLUDE_DOCUMENT_DATA,
    [HTML_ATTR_SUBMIT_DOCUMENT_DATA_MODE]: ATTR_SUBMIT_DOCUMENT_DATA_MODE,
    [HTML_ATTR_SUBMIT_DOCUMENT_FIELD_PATHS]: ATTR_SUBMIT_DOCUMENT_FIELD_PATHS,
    [HTML_ATTR_SUBMIT_FORM_DATA_ARRAY_MODE]: ATTR_SUBMIT_FORM_DATA_ARRAY_MODE,
    [HTML_ATTR_SUBMIT_UPLOAD_STRATEGY]: ATTR_SUBMIT_UPLOAD_STRATEGY,
    [HTML_ATTR_SUBMIT_PRESIGN_ENDPOINT]: ATTR_SUBMIT_PRESIGN_ENDPOINT,
    [HTML_ATTR_SUBMIT_PRESIGN_METHOD]: ATTR_SUBMIT_PRESIGN_METHOD,
    [HTML_ATTR_SUBMIT_PRESIGN_UPLOAD_URL_KEY]: ATTR_SUBMIT_PRESIGN_UPLOAD_URL_KEY,
    [HTML_ATTR_SUBMIT_PRESIGN_FILE_URL_KEY]: ATTR_SUBMIT_PRESIGN_FILE_URL_KEY,
    [HTML_ATTR_SUBMIT_UPLOAD_METHOD]: ATTR_SUBMIT_UPLOAD_METHOD,
    [HTML_ATTR_SUBMIT_UPLOAD_CHUNK_METHOD]: ATTR_SUBMIT_UPLOAD_CHUNK_METHOD,
    [HTML_ATTR_SUBMIT_UPLOAD_CHUNK_SIZE_MB]: ATTR_SUBMIT_UPLOAD_CHUNK_SIZE_MB,
    [HTML_ATTR_SUBMIT_UPLOAD_RESUME_ENABLED]: ATTR_SUBMIT_UPLOAD_RESUME_ENABLED,
    [HTML_ATTR_SUBMIT_UPLOAD_RESUME_KEY]: ATTR_SUBMIT_UPLOAD_RESUME_KEY,
    [HTML_ATTR_SUBMIT_UPLOAD_RETRY_MAX_ATTEMPTS]: ATTR_SUBMIT_UPLOAD_RETRY_MAX_ATTEMPTS,
    [HTML_ATTR_SUBMIT_UPLOAD_RETRY_BASE_DELAY_MS]: ATTR_SUBMIT_UPLOAD_RETRY_BASE_DELAY_MS,
    [HTML_ATTR_SUBMIT_UPLOAD_RETRY_MAX_DELAY_MS]: ATTR_SUBMIT_UPLOAD_RETRY_MAX_DELAY_MS,
    [HTML_ATTR_SUBMIT_UPLOAD_RETRY_JITTER]: ATTR_SUBMIT_UPLOAD_RETRY_JITTER,
    [HTML_ATTR_SUBMIT_ACTION]: ATTR_SUBMIT_ACTION,
    [HTML_ATTR_VISIBLE_WHEN_FIELD]: ATTR_VISIBLE_WHEN_FIELD,
    [HTML_ATTR_VISIBLE_WHEN_EQUALS]: ATTR_VISIBLE_WHEN_EQUALS,
    [HTML_ATTR_OPTIONS_ENDPOINT]: ATTR_OPTIONS_ENDPOINT,
    [HTML_ATTR_OPTIONS_DEPENDS_ON]: ATTR_OPTIONS_DEPENDS_ON,
    [HTML_ATTR_OPTIONS_LABEL_KEY]: ATTR_OPTIONS_LABEL_KEY,
    [HTML_ATTR_OPTIONS_VALUE_KEY]: ATTR_OPTIONS_VALUE_KEY,
    [HTML_ATTR_STEP_SKIPPABLE]: ATTR_STEP_SKIPPABLE,
    [HTML_ATTR_STEP_VALIDATE_WHEN_WORKFLOW_STATES]: ATTR_STEP_VALIDATE_WHEN_WORKFLOW_STATES,
    [HTML_ATTR_STEP_SUMMARY]: ATTR_STEP_SUMMARY,
    [HTML_ATTR_NEXT_STEP_WHEN_FIELD]: ATTR_NEXT_STEP_WHEN_FIELD,
    [HTML_ATTR_NEXT_STEP_WHEN_EQUALS]: ATTR_NEXT_STEP_WHEN_EQUALS,
    [HTML_ATTR_NEXT_STEP_WHEN_NOT_EQUALS]: ATTR_NEXT_STEP_WHEN_NOT_EQUALS,
    [HTML_ATTR_NEXT_STEP_TARGET]: ATTR_NEXT_STEP_TARGET,
    [HTML_ATTR_STEP_TRANSITIONS]: ATTR_STEP_TRANSITIONS,
    [HTML_ATTR_STORAGE_MODE]: ATTR_STORAGE_MODE,
    [HTML_ATTR_STORAGE_ADAPTER]: ATTR_STORAGE_ADAPTER,
    [HTML_ATTR_STORAGE_KEY]: ATTR_STORAGE_KEY,
    [HTML_ATTR_STORAGE_AUTOSAVE_MS]: ATTR_STORAGE_AUTOSAVE_MS,
    [HTML_ATTR_STORAGE_RESUME_ENDPOINT]: ATTR_STORAGE_RESUME_ENDPOINT,
    [HTML_ATTR_STORAGE_SHARE_CODE_ENDPOINT]: ATTR_STORAGE_SHARE_CODE_ENDPOINT,
    [HTML_ATTR_STORAGE_RESUME_TOKEN_TTL_DAYS]: ATTR_STORAGE_RESUME_TOKEN_TTL_DAYS,
    [HTML_ATTR_STORAGE_RESUME_TOKEN_SIGNATURE_VERSION]: ATTR_STORAGE_RESUME_TOKEN_SIGNATURE_VERSION,
    [HTML_ATTR_STORAGE_ENCRYPTION_KEY]: ATTR_STORAGE_ENCRYPTION_KEY,
    [HTML_ATTR_STORAGE_RETENTION_DAYS]: ATTR_STORAGE_RETENTION_DAYS,
    [HTML_ATTR_STORAGE_RETENTION_DRAFT_DAYS]: ATTR_STORAGE_RETENTION_DRAFT_DAYS,
    [HTML_ATTR_STORAGE_RETENTION_QUEUE_DAYS]: ATTR_STORAGE_RETENTION_QUEUE_DAYS,
    [HTML_ATTR_STORAGE_RETENTION_DEAD_LETTER_DAYS]: ATTR_STORAGE_RETENTION_DEAD_LETTER_DAYS,
    [HTML_ATTR_STORAGE_SHARE_CODE_CLAIM_THROTTLE_MS]: ATTR_STORAGE_SHARE_CODE_CLAIM_THROTTLE_MS,
    [HTML_ATTR_STORAGE_SHARE_CODE_CLAIM_MAX_ATTEMPTS]: ATTR_STORAGE_SHARE_CODE_CLAIM_MAX_ATTEMPTS,
    [HTML_ATTR_STORAGE_SHARE_CODE_CLAIM_WINDOW_MS]: ATTR_STORAGE_SHARE_CODE_CLAIM_WINDOW_MS,
    [HTML_ATTR_STORAGE_SHARE_CODE_CLAIM_BLOCK_MS]: ATTR_STORAGE_SHARE_CODE_CLAIM_BLOCK_MS,
    [HTML_ATTR_STEP_PREVIOUS_LABEL]: ATTR_STEP_PREVIOUS_LABEL,
    [HTML_ATTR_STEP_NEXT_LABEL]: ATTR_STEP_NEXT_LABEL,
    [HTML_ATTR_STEP_PROGRESS_PLACEMENT]: ATTR_STEP_PROGRESS_PLACEMENT,
    [HTML_ATTR_STEP_NAVIGATION_PLACEMENT]: ATTR_STEP_NAVIGATION_PLACEMENT,
    [HTML_ATTR_STEP_BACK_BEHAVIOR]: ATTR_STEP_BACK_BEHAVIOR,
    [HTML_ATTR_WORKFLOW_STEP_TARGETS]: ATTR_WORKFLOW_STEP_TARGETS,
    [HTML_ATTR_VERSION]: ATTR_VERSION,
    [HTML_ATTR_RULES]: ATTR_RULES,
}




function getFieldConfigList(nodes: NodeListOf<Element>): TFieldConfig[] {
    const res: TFieldConfig[] = [];

    nodes.forEach((node) => {
        const fieldConfig: TFieldConfig = getFieldConfig(node);
        res.push(fieldConfig)
    })

    return res;
}

export function getFieldConfig(node: Element): TFieldConfig {
    const randomId = generateRuntimeId();
    const fieldConfig: TFieldConfig = { type: UNKNOWN_TYPE, name: randomId, label: randomId }
    for (const [dashKey, camelKey] of Object.entries(ATTR_MAP)) {
        const attrValue = node.getAttribute(dashKey)
        if (attrValue) {
            (fieldConfig as any)[camelKey] = attrValue;
        }
    }

    const acceptValue = node.getAttribute("accept");
    if (acceptValue) {
        fieldConfig.accept = acceptValue;
    }

    const captureValue = node.getAttribute("capture") || node.getAttribute(HTML_ATTR_CAPTURE);
    if (captureValue === "user" || captureValue === "environment") {
        fieldConfig.capture = captureValue;
    }

    if (node.hasAttribute("multiple")) {
        fieldConfig.multiple = true;
    }

    if (node.hasAttribute(HTML_ATTR_ENABLE_DOCUMENT_OCR)) {
        fieldConfig.enableDocumentOcr = true;
    }

    if (node.hasAttribute(HTML_ATTR_REQUIRE_VALID_DOCUMENT_MRZ)) {
        fieldConfig.requireValidDocumentMrz = true;
    }

    if (node.hasAttribute(HTML_ATTR_VIEW_TEMPLATE_UNSAFE)) {
        fieldConfig.viewTemplateUnsafe = node.getAttribute(HTML_ATTR_VIEW_TEMPLATE_UNSAFE) === "true";
    }

    const includeInSubmitAttr = node.getAttribute(HTML_ATTR_INCLUDE_IN_SUBMIT);
    if (includeInSubmitAttr !== null) {
        fieldConfig.includeInSubmit = includeInSubmitAttr === "true";
    }

    const documentExcludeFromSubmitAttr = node.getAttribute(HTML_ATTR_DOCUMENT_EXCLUDE_FROM_SUBMIT);
    if (documentExcludeFromSubmitAttr !== null) {
        fieldConfig.documentExcludeFromSubmit = documentExcludeFromSubmitAttr === "true";
    }

    const documentExcludeFromDebugAttr = node.getAttribute(HTML_ATTR_DOCUMENT_EXCLUDE_FROM_DEBUG);
    if (documentExcludeFromDebugAttr !== null) {
        fieldConfig.documentExcludeFromDebug = documentExcludeFromDebugAttr === "true";
    }

    const documentMaskPaths = node.getAttribute(HTML_ATTR_DOCUMENT_MASK_PATHS);
    if (documentMaskPaths) {
        try {
            const parsed = JSON.parse(documentMaskPaths);
            if (Array.isArray(parsed)) {
                fieldConfig.documentMaskPaths = parsed.map((entry) => String(entry));
            }
        } catch {
            // Ignore invalid JSON.
        }
    }

    const documentDebugMaskPaths = node.getAttribute(HTML_ATTR_DOCUMENT_DEBUG_MASK_PATHS);
    if (documentDebugMaskPaths) {
        try {
            const parsed = JSON.parse(documentDebugMaskPaths);
            if (Array.isArray(parsed)) {
                fieldConfig.documentDebugMaskPaths = parsed.map((entry) => String(entry));
            }
        } catch {
            // Ignore invalid JSON.
        }
    }

    if (node.hasAttribute(HTML_ATTR_STEP_SKIPPABLE)) {
        fieldConfig.stepSkippable = true;
    }

    if (node.hasAttribute(HTML_ATTR_STEP_SUMMARY)) {
        fieldConfig.stepSummary = true;
    }

    const stepValidateWhenWorkflowStates = node.getAttribute(HTML_ATTR_STEP_VALIDATE_WHEN_WORKFLOW_STATES);
    if (stepValidateWhenWorkflowStates) {
        try {
            const parsed = JSON.parse(stepValidateWhenWorkflowStates);
            if (Array.isArray(parsed)) {
                fieldConfig.stepValidateWhenWorkflowStates = parsed.filter((entry) => typeof entry === "string");
            }
        } catch {
            // Ignore invalid JSON.
        }
    }

    const nextStepWhenEquals = node.getAttribute(HTML_ATTR_NEXT_STEP_WHEN_EQUALS);
    if (nextStepWhenEquals) {
        try {
            const parsed = JSON.parse(nextStepWhenEquals);
            if (Array.isArray(parsed)) {
                fieldConfig.nextStepWhenEquals = parsed.map((entry) => String(entry));
            }
        } catch {
            // Keep attribute string as-is via ATTR_MAP mapping.
        }
    }

    const nextStepWhenNotEquals = node.getAttribute(HTML_ATTR_NEXT_STEP_WHEN_NOT_EQUALS);
    if (nextStepWhenNotEquals) {
        try {
            const parsed = JSON.parse(nextStepWhenNotEquals);
            if (Array.isArray(parsed)) {
                fieldConfig.nextStepWhenNotEquals = parsed.map((entry) => String(entry));
            }
        } catch {
            // Keep attribute string as-is via ATTR_MAP mapping.
        }
    }

    const stepTransitions = node.getAttribute(HTML_ATTR_STEP_TRANSITIONS);
    if (stepTransitions) {
        try {
            const parsed = JSON.parse(stepTransitions);
            if (Array.isArray(parsed)) {
                fieldConfig.stepTransitions = parsed.filter((entry) => entry && typeof entry === "object");
            }
        } catch {
            // Ignore invalid JSON.
        }
    }

    const minFiles = node.getAttribute(HTML_ATTR_MIN_FILES);
    if (minFiles) {
        fieldConfig.minFiles = Number(minFiles);
    }

    const maxFiles = node.getAttribute(HTML_ATTR_MAX_FILES);
    if (maxFiles) {
        fieldConfig.maxFiles = Number(maxFiles);
    }

    const maxFileSizeMb = node.getAttribute(HTML_ATTR_MAX_FILE_SIZE_MB);
    if (maxFileSizeMb) {
        fieldConfig.maxFileSizeMb = Number(maxFileSizeMb);
    }

    const maxTotalFileSizeMb = node.getAttribute(HTML_ATTR_MAX_TOTAL_FILE_SIZE_MB);
    if (maxTotalFileSizeMb) {
        fieldConfig.maxTotalFileSizeMb = Number(maxTotalFileSizeMb);
    }

    const minNumOfChoices = node.getAttribute(HTML_ATTR_MIN_NUM_OF_CHOICES);
    if (minNumOfChoices) {
        fieldConfig.minNumOfChoices = Number(minNumOfChoices);
    }

    const maxNumOfChoices = node.getAttribute(HTML_ATTR_MAX_NUM_OF_CHOICES);
    if (maxNumOfChoices) {
        fieldConfig.maxNumOfChoices = Number(maxNumOfChoices);
    }

    const choices = node.getAttribute(HTML_ATTR_CHOICES);
    if (choices) {
        try {
            const parsed = JSON.parse(choices);
            if (Array.isArray(parsed)) {
                fieldConfig.choices = parsed.filter((entry) => entry && typeof entry === "object");
            }
        } catch {
            // Keep original attribute mapping when JSON is invalid.
        }
    }

    return fieldConfig;
}


export default function getFormConfig(node: Element): TFormConfig {
    const formConfig: TFormConfig = {
        ...DEFAULT_FORM_CONFIG,
        sections: {
            ...DEFAULT_FORM_CONFIG.sections,
            [CUSTOM_SECTION]: [...(DEFAULT_FORM_CONFIG.sections[CUSTOM_SECTION] || [])],
        },
    };

    for (const [dashKey, camelKey] of Object.entries(ATTR_MAP)) {
        const attrValue = node.getAttribute(dashKey)
        if (attrValue) {
            (formConfig as any)[camelKey] = attrValue;
        }
    }

    if ((formConfig as any).label && formConfig.title === DEFAULT_FORM_CONFIG.title) {
        formConfig.title = (formConfig as any).label;
    }

    if ((formConfig as any).submitEndpoint) {
        (formConfig as any).submit = {
            endpoint: (formConfig as any).submitEndpoint,
            baseUrl: (formConfig as any).submitBaseUrl,
            includeSettingFields: (formConfig as any).submitIncludeSettingFields === 'true',
            settingFieldAllowlist: (formConfig as any).submitSettingFieldAllowlist
              ? JSON.parse((formConfig as any).submitSettingFieldAllowlist)
              : undefined,
            providerRoutingPolicy: (formConfig as any).submitProviderRoutingPolicy,
            providerResponseContract: (formConfig as any).submitProviderResponseContract,
            method: (formConfig as any).submitMethod,
            mode: (formConfig as any).submitMode,
            includeDocumentData: (formConfig as any).submitIncludeDocumentData === 'true',
            documentDataMode: (formConfig as any).submitDocumentDataMode,
            documentFieldPaths: (formConfig as any).submitDocumentFieldPaths
              ? JSON.parse((formConfig as any).submitDocumentFieldPaths)
              : undefined,
            formDataArrayMode: (formConfig as any).submitFormDataArrayMode,
            uploadStrategy: (formConfig as any).submitUploadStrategy,
            presignEndpoint: (formConfig as any).submitPresignEndpoint,
            presignMethod: (formConfig as any).submitPresignMethod,
            presignUploadUrlKey: (formConfig as any).submitPresignUploadUrlKey,
            presignFileUrlKey: (formConfig as any).submitPresignFileUrlKey,
            presignResumeUrlKey: (formConfig as any).submitPresignResumeUrlKey,
            presignSessionIdKey: (formConfig as any).submitPresignSessionIdKey,
            uploadMethod: (formConfig as any).submitUploadMethod,
            uploadChunkMethod: (formConfig as any).submitUploadChunkMethod,
            uploadChunkSizeMb: (formConfig as any).submitUploadChunkSizeMb
              ? Number((formConfig as any).submitUploadChunkSizeMb)
              : undefined,
            uploadResumeEnabled: (formConfig as any).submitUploadResumeEnabled === 'true',
            uploadResumeKey: (formConfig as any).submitUploadResumeKey,
            uploadRetryMaxAttempts: (formConfig as any).submitUploadRetryMaxAttempts
              ? Number((formConfig as any).submitUploadRetryMaxAttempts)
              : undefined,
            uploadRetryBaseDelayMs: (formConfig as any).submitUploadRetryBaseDelayMs
              ? Number((formConfig as any).submitUploadRetryBaseDelayMs)
              : undefined,
            uploadRetryMaxDelayMs: (formConfig as any).submitUploadRetryMaxDelayMs
              ? Number((formConfig as any).submitUploadRetryMaxDelayMs)
              : undefined,
            uploadRetryJitter: (formConfig as any).submitUploadRetryJitter === 'true',
            action: (formConfig as any).submitAction,
        };
        delete (formConfig as any).submitEndpoint;
        delete (formConfig as any).submitBaseUrl;
        delete (formConfig as any).submitIncludeSettingFields;
        delete (formConfig as any).submitSettingFieldAllowlist;
        delete (formConfig as any).submitProviderRoutingPolicy;
        delete (formConfig as any).submitProviderResponseContract;
        delete (formConfig as any).submitMethod;
        delete (formConfig as any).submitMode;
        delete (formConfig as any).submitIncludeDocumentData;
        delete (formConfig as any).submitDocumentDataMode;
        delete (formConfig as any).submitDocumentFieldPaths;
        delete (formConfig as any).submitFormDataArrayMode;
        delete (formConfig as any).submitUploadStrategy;
        delete (formConfig as any).submitPresignEndpoint;
        delete (formConfig as any).submitPresignMethod;
        delete (formConfig as any).submitPresignUploadUrlKey;
        delete (formConfig as any).submitPresignFileUrlKey;
        delete (formConfig as any).submitPresignResumeUrlKey;
        delete (formConfig as any).submitPresignSessionIdKey;
        delete (formConfig as any).submitUploadMethod;
        delete (formConfig as any).submitUploadChunkMethod;
        delete (formConfig as any).submitUploadChunkSizeMb;
        delete (formConfig as any).submitUploadResumeEnabled;
        delete (formConfig as any).submitUploadResumeKey;
        delete (formConfig as any).submitUploadRetryMaxAttempts;
        delete (formConfig as any).submitUploadRetryBaseDelayMs;
        delete (formConfig as any).submitUploadRetryMaxDelayMs;
        delete (formConfig as any).submitUploadRetryJitter;
        delete (formConfig as any).submitAction;
    }

    if ((formConfig as any).storageMode) {
        (formConfig as any).storage = {
            mode: (formConfig as any).storageMode,
            adapter: (formConfig as any).storageAdapter,
            key: (formConfig as any).storageKey,
            autoSaveMs: (formConfig as any).storageAutoSaveMs
                ? Number((formConfig as any).storageAutoSaveMs)
                : undefined,
            resumeEndpoint: (formConfig as any).storageResumeEndpoint,
            shareCodeEndpoint: (formConfig as any).storageShareCodeEndpoint,
            resumeTokenTtlDays: (formConfig as any).storageResumeTokenTtlDays
                ? Number((formConfig as any).storageResumeTokenTtlDays)
                : undefined,
            resumeTokenSignatureVersion: (formConfig as any).storageResumeTokenSignatureVersion,
            encryptionKey: (formConfig as any).storageEncryptionKey,
            retentionDays: (formConfig as any).storageRetentionDays
                ? Number((formConfig as any).storageRetentionDays)
                : undefined,
            retentionDraftDays: (formConfig as any).storageRetentionDraftDays
                ? Number((formConfig as any).storageRetentionDraftDays)
                : undefined,
            retentionQueueDays: (formConfig as any).storageRetentionQueueDays
                ? Number((formConfig as any).storageRetentionQueueDays)
                : undefined,
            retentionDeadLetterDays: (formConfig as any).storageRetentionDeadLetterDays
                ? Number((formConfig as any).storageRetentionDeadLetterDays)
                : undefined,
            shareCodeClaimThrottleMs: (formConfig as any).storageShareCodeClaimThrottleMs
                ? Number((formConfig as any).storageShareCodeClaimThrottleMs)
                : undefined,
            shareCodeClaimMaxAttempts: (formConfig as any).storageShareCodeClaimMaxAttempts
                ? Number((formConfig as any).storageShareCodeClaimMaxAttempts)
                : undefined,
            shareCodeClaimWindowMs: (formConfig as any).storageShareCodeClaimWindowMs
                ? Number((formConfig as any).storageShareCodeClaimWindowMs)
                : undefined,
            shareCodeClaimBlockMs: (formConfig as any).storageShareCodeClaimBlockMs
                ? Number((formConfig as any).storageShareCodeClaimBlockMs)
                : undefined,
        };
        delete (formConfig as any).storageMode;
        delete (formConfig as any).storageAdapter;
        delete (formConfig as any).storageKey;
        delete (formConfig as any).storageAutoSaveMs;
        delete (formConfig as any).storageResumeEndpoint;
        delete (formConfig as any).storageShareCodeEndpoint;
        delete (formConfig as any).storageResumeTokenTtlDays;
        delete (formConfig as any).storageResumeTokenSignatureVersion;
        delete (formConfig as any).storageEncryptionKey;
        delete (formConfig as any).storageRetentionDays;
        delete (formConfig as any).storageRetentionDraftDays;
        delete (formConfig as any).storageRetentionQueueDays;
        delete (formConfig as any).storageRetentionDeadLetterDays;
        delete (formConfig as any).storageShareCodeClaimThrottleMs;
        delete (formConfig as any).storageShareCodeClaimMaxAttempts;
        delete (formConfig as any).storageShareCodeClaimWindowMs;
        delete (formConfig as any).storageShareCodeClaimBlockMs;
    }

    if ((formConfig as any).version) {
        (formConfig as any).version = Number((formConfig as any).version);
    }

    if ((formConfig as any).stepPreviousLabel || (formConfig as any).stepNextLabel) {
        (formConfig as any).navigationLabels = {
            prevLabel: (formConfig as any).stepPreviousLabel,
            nextLabel: (formConfig as any).stepNextLabel,
        };
        delete (formConfig as any).stepPreviousLabel;
        delete (formConfig as any).stepNextLabel;
    }

    if (
        (formConfig as any).stepProgressPlacement
        || (formConfig as any).stepNavigationPlacement
        || (formConfig as any).stepBackBehavior
    ) {
        (formConfig as any).stepUi = {
            progressPlacement: (formConfig as any).stepProgressPlacement,
            navigationPlacement: (formConfig as any).stepNavigationPlacement,
            backBehavior: (formConfig as any).stepBackBehavior,
        };
        delete (formConfig as any).stepProgressPlacement;
        delete (formConfig as any).stepNavigationPlacement;
        delete (formConfig as any).stepBackBehavior;
    }

    if ((formConfig as any).workflowStepTargets) {
        try {
            (formConfig as any).workflowStepTargets = JSON.parse((formConfig as any).workflowStepTargets);
        } catch {
            delete (formConfig as any).workflowStepTargets;
        }
    }

    if ((formConfig as any).rules) {
        try {
            (formConfig as any).rules = JSON.parse((formConfig as any).rules);
        } catch {
            delete (formConfig as any).rules;
        }
    }

    const sectionNodes = node.querySelectorAll('[data-type="section"]');
    const sectionList = getFieldConfigList(sectionNodes);
    formConfig.sections[CUSTOM_SECTION] = sectionList;
    (formConfig as any).stepSections = sectionList;

    sectionList.forEach((sectionConfig: TFieldConfig) => {
        const fieldNodes = node.querySelectorAll(`[data-section-name="${sectionConfig.name}"]`);
        const fieldList = getFieldConfigList(fieldNodes);
        formConfig.sections[sectionConfig.name] = fieldList;
    })


    return formConfig;
}


export function getErrorClass(input: any): string {
    if (!input) return 'input-error';
    const type = input.type || (typeof input.getAttribute === 'function' ? input.getAttribute('type') : null);
    switch (type) {
        case 'textarea':
            return 'textarea-error'

        case 'select-one':
        case 'select-multiple':
            return 'select-error'
            
        case 'checkbox':
            return 'checkbox-error'
            
        case 'radio':
            return 'radio-error'
            
        case 'file':
            return 'file-input-error'

        default:
            return 'input-error'
    }
}
