import type TFieldConfig from "../common/TFieldConfig";
import type {
  TDocumentScanInsight,
} from "../common/document-contract";
import { DOCUMENT_SCAN_TYPE, QR_SCAN_TYPE, isFileLikeValue } from "../common/field";

export function hasFileValues(value: any): boolean {
  if (isFileLikeValue(value)) {
    return true;
  }

  if (Array.isArray(value)) {
    return value.some((entry) => hasFileValues(entry));
  }

  if (value && typeof value === "object") {
    return Object.values(value).some((entry) => hasFileValues(entry));
  }

  return false;
}

export function getFileValueList(value: any): any[] {
  return Array.isArray(value)
    ? value
    : value && typeof value === "object"
      ? [value]
      : [];
}

export function isQrScanField(fieldConfig: TFieldConfig): boolean {
  return fieldConfig.type === QR_SCAN_TYPE;
}

export function isDocumentScanField(fieldConfig: TFieldConfig): boolean {
  return fieldConfig.type === DOCUMENT_SCAN_TYPE;
}

export function getDocumentScanSlotCount(fieldConfig: TFieldConfig): number {
  return fieldConfig.documentScanMode === "single" ? 1 : 2;
}

export function getDocumentScanInsight(
  documentScanInsights: Record<string, TDocumentScanInsight>,
  fieldConfig: TFieldConfig,
): TDocumentScanInsight {
  const slotCount = getDocumentScanSlotCount(fieldConfig);
  if (!documentScanInsights[fieldConfig.name]) {
    documentScanInsights[fieldConfig.name] = {
      textBySlot: Array.from({ length: slotCount }, () => null),
      mrzBySlot: Array.from({ length: slotCount }, () => null),
      normalizedBySlot: Array.from({ length: slotCount }, () => null),
    };
  }

  return documentScanInsights[fieldConfig.name];
}
