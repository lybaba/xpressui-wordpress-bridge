function isRecord(value: unknown): value is Record<string, any> {
  return Boolean(value) && typeof value === "object" && !Array.isArray(value);
}

function readString(value: unknown): string | undefined {
  return typeof value === "string" && value ? value : undefined;
}

function readNumber(value: unknown): number | undefined {
  return typeof value === "number" && Number.isFinite(value) ? value : undefined;
}

function readBoolean(value: unknown): boolean | undefined {
  return typeof value === "boolean" ? value : undefined;
}

function readHeaders(value: unknown): Record<string, string> | undefined {
  if (!isRecord(value)) {
    return undefined;
  }

  const headers = Object.fromEntries(
    Object.entries(value)
      .filter(([, entry]) => typeof entry === "string" && entry.length > 0)
      .map(([key, entry]) => [key, entry as string]),
  );

  return Object.keys(headers).length ? headers : undefined;
}

export type TPresignedUploadDescriptor = {
  uploadUrl?: string;
  fileUrl?: string;
  resumeUrl?: string;
  sessionId?: string;
  headers?: Record<string, string>;
  nextChunkIndex?: number;
  nextByteOffset?: number;
  completed?: boolean;
};

export function normalizePresignedUploadDescriptor(
  value: unknown,
  options: {
    uploadUrlKey?: string;
    fileUrlKey?: string;
    resumeUrlKey?: string;
    sessionIdKey?: string;
  } = {},
): TPresignedUploadDescriptor {
  if (!isRecord(value)) {
    return {};
  }

  const uploadUrlKey = options.uploadUrlKey || "uploadUrl";
  const fileUrlKey = options.fileUrlKey || "fileUrl";
  const resumeUrlKey = options.resumeUrlKey || "resumeUrl";
  const sessionIdKey = options.sessionIdKey || "sessionId";
  const session = isRecord(value.uploadSession) ? value.uploadSession : null;

  return {
    uploadUrl: readString(value[uploadUrlKey]) || readString(session?.[uploadUrlKey]),
    fileUrl: readString(value[fileUrlKey]) || readString(session?.[fileUrlKey]),
    resumeUrl: readString(value[resumeUrlKey]) || readString(session?.[resumeUrlKey]),
    sessionId: readString(value[sessionIdKey]) || readString(session?.[sessionIdKey]),
    headers: readHeaders(value.headers) || readHeaders(session?.headers),
    nextChunkIndex: readNumber(value.nextChunkIndex) ?? readNumber(session?.nextChunkIndex),
    nextByteOffset: readNumber(value.nextByteOffset) ?? readNumber(session?.nextByteOffset),
    completed: readBoolean(value.completed) ?? readBoolean(session?.completed),
  };
}
