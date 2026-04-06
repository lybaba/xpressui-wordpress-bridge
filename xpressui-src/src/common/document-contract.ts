export const DOCUMENT_NORMALIZED_CONTRACT_VERSION = "ocr-mrz-v2" as const;

export type TDocumentNormalizedContractVersion = typeof DOCUMENT_NORMALIZED_CONTRACT_VERSION;

export type TDocumentMrzResult = {
  format: "TD1" | "TD2" | "TD3";
  lines: string[];
  documentCode: string;
  issuingCountry: string;
  documentNumber?: string;
  nationality?: string;
  birthDate?: string;
  expiryDate?: string;
  sex?: string;
  surnames?: string[];
  givenNames?: string[];
  checksums?: {
    documentNumber?: boolean;
    birthDate?: boolean;
    expiryDate?: boolean;
    composite?: boolean;
  };
  valid?: boolean;
};

export type TDocumentNormalizedStatus = "text_only" | "mrz_detected" | "mrz_invalid";

export type TDocumentNormalizedQuality = {
  textLength: number;
  estimatedConfidence: number;
  hasMrz: boolean;
  hasFields: boolean;
  mrzChecksumValid?: boolean;
};

export type TDocumentNormalizedReview = {
  recommendedAction: "allow" | "review" | "reject";
  reasons: string[];
};

export type TDocumentNormalizedFields = {
  firstName?: string;
  lastName?: string;
  documentNumber?: string;
  nationality?: string;
  birthDate?: string;
  expiryDate?: string;
  sex?: string;
} & Record<string, any>;

export type TDocumentNormalizedContractV2 = {
  contractVersion: TDocumentNormalizedContractVersion;
  status: TDocumentNormalizedStatus;
  quality: TDocumentNormalizedQuality;
  review: TDocumentNormalizedReview;
  mrz?: TDocumentMrzResult | null;
  fields?: TDocumentNormalizedFields | null;
};

export type TDocumentScanInsight = {
  textBySlot: Array<string | null>;
  mrzBySlot: Array<TDocumentMrzResult | null>;
  normalizedBySlot: Array<TDocumentNormalizedContractV2 | null>;
};

export function createNormalizedDocumentContract(
  detectedText: string,
  mrz: TDocumentMrzResult | null,
  fields: TDocumentNormalizedFields | null,
): TDocumentNormalizedContractV2 {
  const estimatedConfidence = Math.max(0, Math.min(1, Number((detectedText.length / 64).toFixed(2))));
  const status: TDocumentNormalizedContractV2["status"] = !mrz
    ? "text_only"
    : mrz.valid === false
      ? "mrz_invalid"
      : "mrz_detected";
  const reasons: string[] = [];

  if (!mrz) {
    reasons.push("no_mrz");
  } else if (mrz.valid === false) {
    reasons.push("mrz_checksum_failed");
  }

  if (estimatedConfidence < 0.2) {
    reasons.push("low_text_confidence");
  }

  if (!fields || !Object.keys(fields).length) {
    reasons.push("no_normalized_fields");
  }

  const recommendedAction: TDocumentNormalizedReview["recommendedAction"] = mrz?.valid === false
    ? "reject"
    : reasons.length
      ? "review"
      : "allow";

  return {
    contractVersion: DOCUMENT_NORMALIZED_CONTRACT_VERSION,
    status,
    quality: {
      textLength: detectedText.length,
      estimatedConfidence,
      hasMrz: Boolean(mrz),
      hasFields: Boolean(fields && Object.keys(fields).length),
      ...(typeof mrz?.valid === "boolean" ? { mrzChecksumValid: mrz.valid } : {}),
    },
    review: {
      recommendedAction,
      reasons,
    },
    mrz: mrz || null,
    fields: fields || null,
  };
}

export function isDocumentNormalizedContractV2(
  value: unknown,
): value is TDocumentNormalizedContractV2 {
  if (!value || typeof value !== "object") {
    return false;
  }

  const contract = value as Record<string, any>;
  if (contract.contractVersion !== DOCUMENT_NORMALIZED_CONTRACT_VERSION) {
    return false;
  }

  if (!["text_only", "mrz_detected", "mrz_invalid"].includes(contract.status)) {
    return false;
  }

  if (!contract.quality || typeof contract.quality !== "object") {
    return false;
  }

  return (
    typeof contract.quality.textLength === "number" &&
    typeof contract.quality.estimatedConfidence === "number" &&
    typeof contract.quality.hasMrz === "boolean" &&
    typeof contract.quality.hasFields === "boolean" &&
    contract.review &&
    typeof contract.review === "object" &&
    ["allow", "review", "reject"].includes((contract.review as Record<string, any>).recommendedAction) &&
    Array.isArray((contract.review as Record<string, any>).reasons)
  );
}

export function summarizeNormalizedDocumentContract(
  contract: TDocumentNormalizedContractV2 | null | undefined,
): Pick<TDocumentNormalizedContractV2, "contractVersion" | "status" | "quality" | "review"> | null {
  if (!contract) {
    return null;
  }

  return {
    contractVersion: contract.contractVersion,
    status: contract.status,
    quality: contract.quality,
    review: contract.review,
  };
}
