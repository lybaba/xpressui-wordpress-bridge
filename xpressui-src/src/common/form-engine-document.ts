import type { TDocumentMrzResult, TDocumentNormalizedContractV2 } from "./document-contract";
import { isFileLikeValue } from "./field";

// ---------------------------------------------------------------------------
// Public types
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// Document redaction / filtering utilities
// ---------------------------------------------------------------------------

export function redactDocumentData(
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

export function filterDocumentDataByPaths(
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

export function maskDocumentDataByPaths(
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

export function cloneDocumentValue<T>(value: T): T {
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

export function getFileList(value: any): File[] {
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

export function matchesAccept(file: File, accept?: string): boolean {
  if (!accept) {
    return true;
  }

  return accept
    .split(",")
    .some((token) => matchesAcceptToken(file, token));
}
