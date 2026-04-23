import type {
  TFormStorageSnapshot,
  TRemoteResumeCreateResponse,
  TRemoteResumeInvalidateResponse,
  TRemoteResumeLookupResponse,
  TRemoteResumeShareCodeClaimResponse,
  TRemoteResumeShareCodeCreateResponse,
} from "./form-persistence.types";

function parseJsonLike(result: unknown): unknown {
  if (typeof result !== "string") {
    return result;
  }

  try {
    return JSON.parse(result) as unknown;
  } catch {
    return null;
  }
}

export function normalizeResumeSnapshot(input: unknown): TFormStorageSnapshot | null {
  if (!input || typeof input !== "object") {
    return null;
  }

  const snapshot = input as Record<string, any>;
  return {
    draft:
      snapshot.draft && typeof snapshot.draft === "object"
        ? snapshot.draft as Record<string, any>
        : null,
    queue: Array.isArray(snapshot.queue) ? snapshot.queue : [],
    deadLetter: Array.isArray(snapshot.deadLetter) ? snapshot.deadLetter : [],
    ...(typeof snapshot.currentStepIndex === "number" && Number.isInteger(snapshot.currentStepIndex)
      ? { currentStepIndex: snapshot.currentStepIndex }
      : {}),
  };
}

export function parseRemoteCreateResponse(result: unknown): TRemoteResumeCreateResponse | null {
  const normalizedResult = parseJsonLike(result);
  if (!normalizedResult || typeof normalizedResult !== "object") {
    return null;
  }

  const response = normalizedResult as Record<string, any>;
  if (typeof response.token !== "string" || !response.token) {
    return null;
  }

  return {
    operation: "create",
    token: response.token,
    savedAt: typeof response.savedAt === "number" ? response.savedAt : Date.now(),
    issuedAt: typeof response.issuedAt === "number" ? response.issuedAt : undefined,
    expiresAt: typeof response.expiresAt === "number" ? response.expiresAt : undefined,
    signature: typeof response.signature === "string" ? response.signature : undefined,
    signatureVersion:
      typeof response.signatureVersion === "string" ? response.signatureVersion : undefined,
  };
}

export function parseRemoteLookupResponse(
  fallbackToken: string,
  result: unknown,
): TRemoteResumeLookupResponse | null {
  const normalizedResult = parseJsonLike(result);
  if (!normalizedResult || typeof normalizedResult !== "object") {
    return null;
  }

  const response = normalizedResult as Record<string, any>;
  if (response.found === false) {
    return null;
  }

  return {
    operation: "lookup",
    token: typeof response.token === "string" && response.token ? response.token : fallbackToken,
    savedAt: typeof response.savedAt === "number" ? response.savedAt : Date.now(),
    issuedAt: typeof response.issuedAt === "number" ? response.issuedAt : undefined,
    expiresAt: typeof response.expiresAt === "number" ? response.expiresAt : undefined,
    signature: typeof response.signature === "string" ? response.signature : undefined,
    signatureVersion:
      typeof response.signatureVersion === "string" ? response.signatureVersion : undefined,
    snapshot: normalizeResumeSnapshot(response.snapshot),
  };
}

export function parseRemoteInvalidateResponse(
  token: string,
  response: Response,
  result: unknown,
): TRemoteResumeInvalidateResponse | null {
  if (!response.ok) {
    return null;
  }

  if (response.status === 204 || !result) {
    return {
      operation: "invalidate",
      token,
      invalidated: true,
    };
  }

  const normalizedResult = parseJsonLike(result);
  if (!normalizedResult || typeof normalizedResult !== "object") {
    return {
      operation: "invalidate",
      token,
      invalidated: true,
    };
  }

  const parsed = normalizedResult as Record<string, any>;
  if (parsed.invalidated === false) {
    return null;
  }

  return {
    operation: "invalidate",
    token: typeof parsed.token === "string" && parsed.token ? parsed.token : token,
    invalidated: true,
  };
}

export function parseRemoteShareCodeCreateResponse(
  result: unknown,
): TRemoteResumeShareCodeCreateResponse | null {
  const normalizedResult = parseJsonLike(result);
  if (!normalizedResult || typeof normalizedResult !== "object") {
    return null;
  }

  const response = normalizedResult as Record<string, any>;
  if (typeof response.code !== "string" || !response.code) {
    return null;
  }

  return {
    operation: "create-share-code",
    code: response.code,
    token: typeof response.token === "string" ? response.token : undefined,
    expiresAt: typeof response.expiresAt === "number" ? response.expiresAt : undefined,
  };
}

export function parseRemoteShareCodeClaimResponse(
  result: unknown,
): TRemoteResumeShareCodeClaimResponse | null {
  const normalizedResult = parseJsonLike(result);
  if (!normalizedResult || typeof normalizedResult !== "object") {
    return null;
  }

  const response = normalizedResult as Record<string, any>;
  if (typeof response.code !== "string" || !response.code) {
    return null;
  }
  if (typeof response.token !== "string" || !response.token) {
    return null;
  }

  return {
    operation: "claim-share-code",
    code: response.code,
    token: response.token,
    savedAt: typeof response.savedAt === "number" ? response.savedAt : Date.now(),
    issuedAt: typeof response.issuedAt === "number" ? response.issuedAt : undefined,
    expiresAt: typeof response.expiresAt === "number" ? response.expiresAt : undefined,
    signature: typeof response.signature === "string" ? response.signature : undefined,
    signatureVersion:
      typeof response.signatureVersion === "string" ? response.signatureVersion : undefined,
    snapshot: normalizeResumeSnapshot(response.snapshot),
  };
}
