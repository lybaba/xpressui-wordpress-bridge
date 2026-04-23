import TFieldConfig from "./TFieldConfig";
import TFormConfig from "./TFormConfig";
import { CUSTOM_SECTION } from "./Constants";
import validate, { getValidators, TValidator } from "./Validator";
import { REQUIRED_FIELD_MSG } from "./validation-messages";
import { isFileFieldType, normalizeFormValues } from "./field";
import {
  type TStoredDocumentData,
  type TDocumentDataReadMode,
  type TDocumentDataPrivacyScope,
  type TDocumentDataViewOptions,
  redactDocumentData,
  filterDocumentDataByPaths,
  maskDocumentDataByPaths,
  getFileList,
  matchesAccept,
} from "./form-engine-document";

export type {
  TStoredDocumentData,
  TDocumentDataReadMode,
  TDocumentDataPrivacyScope,
  TDocumentDataViewOptions,
};

export class FormEngineRuntime {
  formConfig: TFormConfig | null;
  validators: TValidator[];
  inputFields: Record<string, TFieldConfig>;
  documentData: Record<string, TStoredDocumentData>;
  /** Tracks required-state overrides applied at runtime (via setField) that differ from
   *  the compiled AJV schema. Keyed by field name, value is the override (true = required,
   *  false = not-required). Cleared on setFormConfig. */
  dynamicRequiredOverrides: Record<string, boolean>;

  constructor() {
    this.formConfig = null;
    this.validators = [];
    this.inputFields = {};
    this.documentData = {};
    this.dynamicRequiredOverrides = {};
  }

  setFormConfig(formConfig: TFormConfig | null): void {
    this.formConfig = formConfig;
    this.validators = formConfig ? getValidators(formConfig) : [];
    this.inputFields = {};
    this.dynamicRequiredOverrides = {};
    if (formConfig?.sections) {
      Object.entries(formConfig.sections).forEach(([sectionName, fields]) => {
        if (sectionName === CUSTOM_SECTION) {
          return;
        }

        fields.forEach((field) => {
          this.inputFields[field.name] = field;
        });
      });
    }
  }

  setField(fieldName: string, fieldConfig: TFieldConfig): void {
    this.inputFields[fieldName] = fieldConfig;
  }

  /** Called exclusively by the setFieldRequired option — bypasses setField so that
   *  DOM re-registration (which calls setField with the original config) cannot
   *  silently clear a rule-driven required override. */
  setRequiredOverride(fieldName: string, required: boolean): void {
    this.dynamicRequiredOverrides[fieldName] = required;
  }

  getField(fieldName: string): TFieldConfig | undefined {
    return this.inputFields[fieldName];
  }

  getFields(): Record<string, TFieldConfig> {
    return this.inputFields;
  }

  setDocumentData(fieldName: string, data: TStoredDocumentData): void {
    this.documentData[fieldName] = data;
  }

  getDocumentData(fieldName: string): TStoredDocumentData | null {
    return this.documentData[fieldName] || null;
  }

  getAllDocumentData(): Record<string, TStoredDocumentData> {
    return { ...this.documentData };
  }

  getDocumentDataView(
    fieldName: string,
    mode: TDocumentDataReadMode = "summary",
    options: boolean | TDocumentDataViewOptions = true,
  ): Record<string, any> | null {
    const data = this.documentData[fieldName];
    if (!data) {
      return null;
    }

    const fieldConfig = this.inputFields[fieldName];
    const resolvedOptions =
      typeof options === "boolean"
        ? { applyFieldPrivacy: options, privacyScope: "submit" as TDocumentDataPrivacyScope }
        : {
            applyFieldPrivacy: options.applyFieldPrivacy !== false,
            privacyScope: options.privacyScope || "submit",
          };
    if (
      resolvedOptions.applyFieldPrivacy &&
      (
        (resolvedOptions.privacyScope === "submit" && fieldConfig?.documentExcludeFromSubmit)
        || (
          resolvedOptions.privacyScope === "debug" &&
          (fieldConfig?.documentExcludeFromSubmit || fieldConfig?.documentExcludeFromDebug)
        )
      )
    ) {
      return null;
    }

    const redacted = redactDocumentData(data, mode);
    if (!redacted) {
      return null;
    }

    return resolvedOptions.applyFieldPrivacy
      ? maskDocumentDataByPaths(
          redacted,
          resolvedOptions.privacyScope === "debug"
            ? [
                ...(fieldConfig?.documentMaskPaths || []),
                ...(fieldConfig?.documentDebugMaskPaths || []),
              ]
            : fieldConfig?.documentMaskPaths,
        )
      : redacted;
  }

  getAllDocumentDataView(
    mode: TDocumentDataReadMode = "summary",
    options: boolean | TDocumentDataViewOptions = true,
  ): Record<string, Record<string, any>> {
    const result: Record<string, Record<string, any>> = {};
    Object.keys(this.documentData).forEach((fieldName) => {
      const view = this.getDocumentDataView(fieldName, mode, options);
      if (view) {
        result[fieldName] = view;
      }
    });
    return result;
  }

  normalizeValues(values: Record<string, any>): Record<string, any> {
    return normalizeFormValues(this.inputFields, values);
  }

  buildSubmissionValues(
    values: Record<string, any>,
    includeDocumentData: boolean = false,
    documentDataMode: "full" | "summary" | "fields-only" | "mrz-only" | "none" = "full",
    documentFieldPaths?: string[],
  ): Record<string, any> {
    const normalizedValues = this.normalizeValues(values);
    if (!includeDocumentData) {
      return normalizedValues;
    }

    const entries = Object.entries(this.documentData);
    if (!entries.length) {
      return normalizedValues;
    }

    if (entries.length === 1) {
      const [fieldName, data] = entries[0];
      const fieldConfig = this.inputFields[fieldName];
      if (fieldConfig?.documentExcludeFromSubmit) {
        return normalizedValues;
      }
      const redactedData = redactDocumentData(data, documentDataMode);
      const maskedData = redactedData
        ? maskDocumentDataByPaths(redactedData, fieldConfig?.documentMaskPaths)
        : null;
      const filteredData = maskedData
        ? filterDocumentDataByPaths(maskedData, documentFieldPaths)
        : null;
      if (!filteredData) {
        return normalizedValues;
      }
      return {
        ...normalizedValues,
        document: {
          field: fieldName,
          ...filteredData,
        },
      };
    }

    const redactedEntries = Object.fromEntries(
      entries
        .map(([fieldName, data]) => {
          const fieldConfig = this.inputFields[fieldName];
          if (fieldConfig?.documentExcludeFromSubmit) {
            return [fieldName, null] as const;
          }
          const redactedData = redactDocumentData(data, documentDataMode);
          const maskedData = redactedData
            ? maskDocumentDataByPaths(redactedData, fieldConfig?.documentMaskPaths)
            : null;
          return [
            fieldName,
            maskedData
              ? filterDocumentDataByPaths(maskedData, documentFieldPaths)
              : null,
          ] as const;
        })
        .filter(([, data]) => data),
    );
    if (!Object.keys(redactedEntries).length) {
      return normalizedValues;
    }

    return {
      ...normalizedValues,
      document: {
        byField: redactedEntries,
      },
    };
  }

  validateFileField(
    fieldName: string,
    value: any,
  ): { errorMessage: string; errorData?: any } | null {
    const fieldConfig = this.inputFields[fieldName];
    if (!fieldConfig || !isFileFieldType(fieldConfig.type)) {
      return null;
    }

    const files = getFileList(value);

    if (
      typeof fieldConfig.minFiles === "number" &&
      fieldConfig.minFiles > 0 &&
      files.length < fieldConfig.minFiles
    ) {
      return {
        errorMessage:
          fieldConfig.errorMsg ||
          `Not enough files: minimum ${fieldConfig.minFiles} required`,
        errorData: {
          type: "file-min-count",
          minFiles: fieldConfig.minFiles,
          fileCount: files.length,
        },
      };
    }

    if (!files.length) {
      return null;
    }

    if (
      typeof fieldConfig.maxFiles === "number" &&
      fieldConfig.maxFiles > 0 &&
      files.length > fieldConfig.maxFiles
    ) {
      return {
        errorMessage:
          fieldConfig.errorMsg ||
          `Too many files: maximum ${fieldConfig.maxFiles} allowed`,
        errorData: {
          type: "file-count",
          maxFiles: fieldConfig.maxFiles,
          fileCount: files.length,
        },
      };
    }

    const invalidFile = files.find((file) => !matchesAccept(file, fieldConfig.accept));
    if (invalidFile) {
      return {
        errorMessage:
          fieldConfig.fileTypeErrorMsg ||
          fieldConfig.errorMsg ||
          `File type not allowed: ${invalidFile.name}`,
        errorData: {
          type: "file-accept",
          fileName: invalidFile.name,
          accept: fieldConfig.accept,
        },
      };
    }

    if (typeof fieldConfig.maxFileSizeMb === "number" && fieldConfig.maxFileSizeMb > 0) {
      const maxBytes = fieldConfig.maxFileSizeMb * 1024 * 1024;
      const oversizedFile = files.find((file) => file.size > maxBytes);
      if (oversizedFile) {
        return {
          errorMessage:
            fieldConfig.fileSizeErrorMsg ||
            fieldConfig.errorMsg ||
            `File too large: ${oversizedFile.name} exceeds ${fieldConfig.maxFileSizeMb} MB`,
          errorData: {
            type: "file-size",
            fileName: oversizedFile.name,
            maxFileSizeMb: fieldConfig.maxFileSizeMb,
          },
        };
      }
    }

    if (
      typeof fieldConfig.maxTotalFileSizeMb === "number" &&
      fieldConfig.maxTotalFileSizeMb > 0
    ) {
      const totalBytes = files.reduce((sum, file) => sum + file.size, 0);
      const maxTotalBytes = fieldConfig.maxTotalFileSizeMb * 1024 * 1024;
      if (totalBytes > maxTotalBytes) {
        return {
          errorMessage:
            fieldConfig.errorMsg ||
            `Files too large together: total exceeds ${fieldConfig.maxTotalFileSizeMb} MB`,
          errorData: {
            type: "file-total-size",
            maxTotalFileSizeMb: fieldConfig.maxTotalFileSizeMb,
            totalBytes,
          },
        };
      }
    }

    return null;
  }

  validateValues(values: Record<string, any>, stepIndex: number = 0): Record<string, any> {
    const formValues = this.normalizeValues(values);
    const validator = this.validators[stepIndex] || this.validators[0];
    const validationErrors = validator
      ? validate(validator, formValues, this.formConfig?.validation?.i18n)
      : {};

    // Enforce dynamically-overridden required state (set-required / clear-required rules).
    // The AJV schema is compiled once at init and won't reflect runtime changes.
    Object.entries(this.dynamicRequiredOverrides).forEach(([fieldName, required]) => {
      if (validationErrors[fieldName]) return; // already has an error
      const value = formValues[fieldName];
      const isEmpty = value === undefined || value === null || value === "";
      if (required && isEmpty) {
        validationErrors[fieldName] = { errorMessage: REQUIRED_FIELD_MSG };
      }
    });

    Object.values(this.inputFields).forEach((fieldConfig) => {
      if (!isFileFieldType(fieldConfig.type)) {
        return;
      }

      const fieldName = fieldConfig.name;
      const fileError = this.validateFileField(fieldName, formValues[fieldName]);
      if (fileError) {
        validationErrors[fieldName] = fileError;
        return;
      }

      if (fieldConfig.requireValidDocumentMrz) {
        const documentData = this.documentData[fieldName];
        const mrzValid = documentData?.mrz && typeof documentData.mrz.valid === "boolean"
          ? documentData.mrz.valid
          : undefined;
        if (mrzValid === false) {
          validationErrors[fieldName] = {
            errorMessage:
              fieldConfig.errorMsg || "Document scan failed MRZ validation.",
            errorData: {
              type: "document-mrz-invalid",
            },
          };
        }
      }
    });

    return validationErrors;
  }
}
