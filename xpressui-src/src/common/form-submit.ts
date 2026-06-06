import TFieldConfig from "./TFieldConfig";
import { TFormSubmitRequest } from "./TFormConfig";
import { SETTING_TYPE, isFileLikeValue } from "./field";
import { buildProviderPayload } from "./provider-registry";

function buildSubmitMetadataValue(value: any): any {
  if (value !== "$submissionId") {
    return value;
  }

  if (typeof globalThis !== "undefined" && globalThis.crypto?.randomUUID) {
    return `submission_${globalThis.crypto.randomUUID()}`;
  }

  return `submission_${Date.now()}`;
}

function buildSubmitMetadata(
  submitConfig: TFormSubmitRequest,
): Record<string, any> {
  const metadata = submitConfig.metadata;
  if (!metadata || typeof metadata !== "object" || Array.isArray(metadata)) {
    return {};
  }

  return Object.fromEntries(
    Object.entries(metadata).map(([key, value]) => [key, buildSubmitMetadataValue(value)]),
  );
}

function isAbsoluteUrl(value: string): boolean {
  return /^[a-zA-Z][a-zA-Z\d+\-.]*:\/\//.test(value) || value.startsWith("//");
}

export function resolveSubmitRequestUrl(
  endpoint: string,
  baseUrl?: string,
): string {
  const normalizedEndpoint = String(endpoint || "").trim();
  if (!normalizedEndpoint || isAbsoluteUrl(normalizedEndpoint) || !baseUrl) {
    return normalizedEndpoint;
  }

  const normalizedBaseUrl = String(baseUrl).trim();
  if (!normalizedBaseUrl) {
    return normalizedEndpoint;
  }

  if (normalizedEndpoint.startsWith("?") || normalizedEndpoint.startsWith("#")) {
    return `${normalizedBaseUrl.replace(/\/+$/, "")}${normalizedEndpoint}`;
  }

  return `${normalizedBaseUrl.replace(/\/+$/, "")}/${normalizedEndpoint.replace(/^\/+/, "")}`;
}

export function resolveFormDataKey(
  key: string,
  fieldMap?: Record<string, TFieldConfig>,
): string {
  return fieldMap?.[key]?.formDataFieldName || key;
}

function appendFormDataValue(
  formData: FormData,
  key: string,
  value: any,
  arrayMode: "brackets" | "repeat",
  fieldMap?: Record<string, TFieldConfig>,
): void {
  if (value === undefined) {
    return;
  }

  if (isFileLikeValue(value)) {
    formData.append(key, value as File | Blob);
    return;
  }

  if (Array.isArray(value)) {
    value.forEach((entry) => {
      appendFormDataValue(
        formData,
        arrayMode === "brackets" ? `${key}[]` : key,
        entry,
        arrayMode,
        fieldMap,
      );
    });
    return;
  }

  if (value && typeof value === "object") {
    Object.entries(value).forEach(([childKey, childValue]) => {
      const resolvedChildKey = resolveFormDataKey(childKey, fieldMap);
      appendFormDataValue(
        formData,
        `${key}[${resolvedChildKey}]`,
        childValue,
        arrayMode,
        fieldMap,
      );
    });
    return;
  }

  formData.append(key, value === null ? "" : String(value));
}

export function buildSubmitPayload(
  values: Record<string, any>,
  submitConfig: TFormSubmitRequest,
  fieldMap?: Record<string, TFieldConfig>,
): Record<string, any> {
  const includeSettings = submitConfig.includeSettingFields === true;
  const allowlist = new Set(submitConfig.settingFieldAllowlist || []);

  const filteredValues = includeSettings || !fieldMap
    ? values
    : Object.fromEntries(
        Object.entries(values).filter(([fieldName]) => {
          const fieldConfig = fieldMap[fieldName];
          if (!fieldConfig || fieldConfig.type !== SETTING_TYPE) {
            return true;
          }
          if (fieldConfig.includeInSubmit) {
            return true;
          }
          return allowlist.has(fieldName);
        }),
      );

  return {
    ...buildProviderPayload(filteredValues, submitConfig),
    ...buildSubmitMetadata(submitConfig),
  };
}

export function buildFormDataBody(
  values: Record<string, any>,
  submitConfig: TFormSubmitRequest,
  fieldMap?: Record<string, TFieldConfig>,
): FormData {
  const formDataArrayMode = submitConfig.formDataArrayMode || "brackets";
  const payload = buildSubmitPayload(values, submitConfig, fieldMap);
  const body = new FormData();
  Object.entries(payload).forEach(([key, value]) => {
    const resolvedKey = resolveFormDataKey(key, fieldMap);
    appendFormDataValue(body, resolvedKey, value, formDataArrayMode, fieldMap);
  });
  return body;
}

export async function submitFormValues(
  values: Record<string, any>,
  submitConfig: TFormSubmitRequest,
  fieldMap?: Record<string, TFieldConfig>,
): Promise<{ response: Response; result: any }> {
  const method = submitConfig.method || "POST";
  const mode = submitConfig.mode || "json";
  const formDataArrayMode = submitConfig.formDataArrayMode || "brackets";
  const headers = { ...(submitConfig.headers || {}) };
  let url = resolveSubmitRequestUrl(submitConfig.endpoint, submitConfig.baseUrl);
  const init: RequestInit = { method, headers };
  const payload = buildSubmitPayload(values, submitConfig, fieldMap);

  if (method === "GET") {
    const searchParams = new URLSearchParams();
    Object.entries(payload).forEach(([key, value]) => {
      searchParams.set(key, String(value));
    });
    const query = searchParams.toString();
    if (query) {
      url += (url.includes("?") ? "&" : "?") + query;
    }
  } else if (mode === "form-data") {
    const body = buildFormDataBody(values, submitConfig, fieldMap);
    init.body = body;
  } else {
    headers["Content-Type"] = headers["Content-Type"] || "application/json";
    init.body = JSON.stringify(payload);
  }

  const response = await fetch(url, init);
  const contentType = response.headers.get("content-type") || "";
  let result: any = null;

  if (contentType.includes("application/json")) {
    result = await response.json();
  } else if (contentType.startsWith("text/")) {
    result = await response.text();
  }

  if (!response.ok) {
    throw { response, result };
  }

  return { response, result };
}
