import { TFormSubmitRequest } from "./TFormConfig";
import { TPresignedUploadDescriptor } from "./upload-contract";

export type TUploadResumeState = {
  version: 1;
  nextChunkIndex: number;
  updatedAt: number;
  sessionId?: string;
  resumeUrl?: string;
  uploadUrl?: string;
  fileUrl?: string;
  headers?: Record<string, string>;
};

export function getChunkSizeBytes(submitConfig: TFormSubmitRequest): number {
  const chunkSizeMb = Number(submitConfig.uploadChunkSizeMb || 0);
  if (!Number.isFinite(chunkSizeMb) || chunkSizeMb <= 0) return 0;
  return Math.max(1, Math.floor(chunkSizeMb * 1024 * 1024));
}

function getUploadResumeStorage(): Storage | null {
  if (typeof window === "undefined") return null;
  try { return window.localStorage; } catch { return null; }
}

function shouldUseUploadResume(submitConfig: TFormSubmitRequest): boolean {
  return submitConfig.uploadResumeEnabled !== false;
}

function getUploadResumeKey(
  submitConfig: TFormSubmitRequest,
  fieldName: string,
  file: File,
): string {
  const namespace = submitConfig.uploadResumeKey
    || submitConfig.presignEndpoint
    || submitConfig.endpoint
    || "default";
  return `xpressui:upload-resume:${namespace}:${fieldName}:${file.name}:${file.size}:${file.lastModified}`;
}

export function loadUploadResumeState(
  submitConfig: TFormSubmitRequest,
  fieldName: string,
  file: File,
): TUploadResumeState | null {
  if (!shouldUseUploadResume(submitConfig)) return null;
  const storage = getUploadResumeStorage();
  if (!storage) return null;
  const raw = storage.getItem(getUploadResumeKey(submitConfig, fieldName, file));
  if (!raw) return null;

  const parsedNumber = Number(raw);
  if (Number.isInteger(parsedNumber) && parsedNumber >= 0) {
    return { version: 1, nextChunkIndex: parsedNumber, updatedAt: 0 };
  }

  try {
    const parsed = JSON.parse(raw) as Record<string, any>;
    if (parsed.version !== 1 || !Number.isInteger(parsed.nextChunkIndex) || parsed.nextChunkIndex < 0) {
      return null;
    }
    return {
      version: 1,
      nextChunkIndex: parsed.nextChunkIndex,
      updatedAt: typeof parsed.updatedAt === "number" ? parsed.updatedAt : Date.now(),
      ...(typeof parsed.sessionId === "string" ? { sessionId: parsed.sessionId } : {}),
      ...(typeof parsed.resumeUrl === "string" ? { resumeUrl: parsed.resumeUrl } : {}),
      ...(typeof parsed.uploadUrl === "string" ? { uploadUrl: parsed.uploadUrl } : {}),
      ...(typeof parsed.fileUrl === "string" ? { fileUrl: parsed.fileUrl } : {}),
      ...(parsed.headers && typeof parsed.headers === "object" && !Array.isArray(parsed.headers)
        ? { headers: parsed.headers as Record<string, string> }
        : {}),
    };
  } catch {
    return null;
  }
}

export function saveUploadResumeState(
  submitConfig: TFormSubmitRequest,
  fieldName: string,
  file: File,
  state: TUploadResumeState,
): void {
  if (!shouldUseUploadResume(submitConfig)) return;
  const storage = getUploadResumeStorage();
  if (!storage) return;
  storage.setItem(
    getUploadResumeKey(submitConfig, fieldName, file),
    JSON.stringify({
      version: 1,
      nextChunkIndex: Math.max(0, state.nextChunkIndex),
      updatedAt: state.updatedAt,
      ...(state.sessionId ? { sessionId: state.sessionId } : {}),
      ...(state.resumeUrl ? { resumeUrl: state.resumeUrl } : {}),
      ...(state.uploadUrl ? { uploadUrl: state.uploadUrl } : {}),
      ...(state.fileUrl ? { fileUrl: state.fileUrl } : {}),
      ...(state.headers ? { headers: state.headers } : {}),
    }),
  );
}

export function clearUploadResumeState(
  submitConfig: TFormSubmitRequest,
  fieldName: string,
  file: File,
): void {
  if (!shouldUseUploadResume(submitConfig)) return;
  const storage = getUploadResumeStorage();
  if (!storage) return;
  storage.removeItem(getUploadResumeKey(submitConfig, fieldName, file));
}

export function getResumeChunkIndexFromDescriptor(
  descriptor: TPresignedUploadDescriptor,
  chunkSizeBytes: number,
): number | null {
  if (typeof descriptor.nextChunkIndex === "number" && descriptor.nextChunkIndex >= 0) {
    return Math.floor(descriptor.nextChunkIndex);
  }
  if (
    typeof descriptor.nextByteOffset === "number"
    && descriptor.nextByteOffset >= 0
    && chunkSizeBytes > 0
  ) {
    return Math.floor(descriptor.nextByteOffset / chunkSizeBytes);
  }
  return null;
}
