import type { TDocumentMrzResult, TDocumentNormalizedContractV2 } from "./document-contract";
import TFieldConfig from "./TFieldConfig";
import TFormConfig from "./TFormConfig";
import { CUSTOM_SECTION } from "./Constants";
import validate, { getValidators, TValidator } from "./Validator";
import { isFileFieldType, isFileLikeValue, normalizeFormValues } from "./field";

export type TStoredDocumentData = {
  text?: string | null;
  mrz?: TDocumentMrzResult | null;
  fields?: Record<string, any> | null;
  normalized?: TDocumentNormalizedContractV2 | null;
};

export type TDocumentDataReadMode = "full" | "summary" | "fields-only" | "mrz-only" | "none";
export type TDocumentDataPrivacyScope = "submit" | "debug";
export type TDocumentDataViewOptions = {
  applyFieldPrivacy?: boolean;
  privacyScope?: TDocumentDataPrivacyScope;
};

function redactDocumentData(
  data: TStoredDocumentData,
  mode: "full" | "summary" | "fields-only" | "mrz-only" | "none",
): Record<string, any> | null {
  switch (mode) {
    case "none":
      return null;

    case "fields-only":
      return {
        fields: data.fields || null,
      };

    case "mrz-only":
      return {
        mrz: data.mrz || null,
      };

    case "summary":
      return {
        ...(data.normalized
          ? {
              normalized: {
                contractVersion: data.normalized.contractVersion,
                status: data.normalized.status,
                quality: data.normalized.quality,
              },
            }
          : {}),
        mrz: data.mrz
          ? {
              format: data.mrz.format,
              documentCode: data.mrz.documentCode,
              issuingCountry: data.mrz.issuingCountry,
              documentNumber: data.mrz.documentNumber,
              nationality: data.mrz.nationality,
              birthDate: data.mrz.birthDate,
              expiryDate: data.mrz.expiryDate,
              sex: data.mrz.sex,
              valid: data.mrz.valid,
            }
          : null,
        fields: data.fields || null,
      };

    case "full":
    default:
      const fullData = { ...data } as Record<string, any>;
      if (!fullData.normalized) {
        delete fullData.normalized;
      }
      return fullData;
  }
}

function hasObjectContent(value: Record<string, any> | null): boolean {
  return Boolean(value && Object.keys(value).length);
}

function setNestedValue(target: Record<string, any>, path: string[], value: any): void {
  let cursor = target;
  path.forEach((segment, index) => {
    if (index === path.length - 1) {
      cursor[segment] = value;
      return;
    }

    if (!cursor[segment] || typeof cursor[segment] !== "object") {
      cursor[segment] = {};
    }
    cursor = cursor[segment];
  });
}

function getNestedValue(source: Record<string, any>, path: string[]): any {
  let cursor: any = source;
  for (const segment of path) {
    if (!cursor || typeof cursor !== "object" || !(segment in cursor)) {
      return undefined;
    }
    cursor = cursor[segment];
  }

  return cursor;
}

function filterDocumentDataByPaths(
  data: Record<string, any>,
  fieldPaths?: string[],
): Record<string, any> | null {
  if (!fieldPaths?.length) {
    return data;
  }

  const filtered: Record<string, any> = {};

  fieldPaths.forEach((path) => {
    const normalizedPath = path.trim();
    if (!normalizedPath) {
      return;
    }

    const segments = normalizedPath.split(".").filter(Boolean);
    if (!segments.length) {
      return;
    }

    const value = getNestedValue(data, segments);
    if (typeof value === "undefined") {
      return;
    }

    setNestedValue(filtered, segments, value);
  });

  return hasObjectContent(filtered) ? filtered : null;
}

function maskDocumentDataByPaths(
  data: Record<string, any>,
  fieldPaths?: string[],
  maskValue: string = "***",
): Record<string, any> {
  if (!fieldPaths?.length) {
    return data;
  }

  const masked = cloneDocumentValue(data);
  fieldPaths.forEach((path) => {
    const normalizedPath = path.trim();
    if (!normalizedPath) {
      return;
    }

    const segments = normalizedPath.split(".").filter(Boolean);
    if (!segments.length) {
      return;
    }

    const value = getNestedValue(masked, segments);
    if (typeof value === "undefined") {
      return;
    }
    setNestedValue(masked, segments, maskValue);
  });

  return masked;
}

function cloneDocumentValue<T>(value: T): T {
  if (Array.isArray(value)) {
    return value.map((entry) => cloneDocumentValue(entry)) as unknown as T;
  }

  if (value && typeof value === "object") {
    const result: Record<string, any> = {};
    Object.entries(value as Record<string, any>).forEach(([key, entry]) => {
      result[key] = cloneDocumentValue(entry);
    });
    return result as T;
  }

  return value;
}

function getFileList(value: any): File[] {
  if (!value) {
    return [];
  }

  if (Array.isArray(value)) {
    return value.filter((entry) => isFileLikeValue(entry)) as File[];
  }

  return isFileLikeValue(value) ? [value as File] : [];
}

function matchesAcceptToken(file: File, token: string): boolean {
  const normalizedToken = token.trim().toLowerCase();
  if (!normalizedToken) {
    return true;
  }

  const fileName = file.name.toLowerCase();
  const mimeType = (file.type || "").toLowerCase();

  if (normalizedToken.startsWith(".")) {
    return fileName.endsWith(normalizedToken);
  }

  if (normalizedToken.endsWith("/*")) {
    const category = normalizedToken.slice(0, -1);
    return mimeType.startsWith(category);
  }

  return mimeType === normalizedToken;
}

function matchesAccept(file: File, accept?: string): boolean {
  if (!accept) {
    return true;
  }

  return accept
    .split(",")
    .some((token) => matchesAcceptToken(file, token));
}

export class FormEngineRuntime {
  formConfig: TFormConfig | null;
  validators: TValidator[];
  inputFields: Record<string, TFieldConfig>;
  documentData: Record<string, TStoredDocumentData>;

  constructor() {
    this.formConfig = null;
    this.validators = [];
    this.inputFields = {};
    this.documentData = {};
  }

  setFormConfig(formConfig: TFormConfig | null): void {
    this.formConfig = formConfig;
    this.validators = formConfig ? getValidators(formConfig) : [];
    this.inputFields = {};
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

  rebuildValidators(): void {
    this.validators = this.formConfig ? getValidators(this.formConfig) : [];
  }

  setField(fieldName: string, fieldConfig: TFieldConfig): void {
    this.inputFields[fieldName] = fieldConfig;
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
