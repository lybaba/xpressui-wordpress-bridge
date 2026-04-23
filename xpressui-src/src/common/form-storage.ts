import { isFileLikeValue } from "./field";

export type TStoredFileMetadata = {
  __type: "file-metadata";
  name: string;
  size: number;
  mimeType: string;
  lastModified?: number;
};

export type TQueuedSubmission = {
  id: string;
  values: Record<string, any>;
  attempts: number;
  createdAt: number;
  updatedAt: number;
  nextAttemptAt: number;
  lastError?: string;
};

export type TStorageSnapshot = {
  draft: Record<string, any> | null;
  queue: TQueuedSubmission[];
  deadLetter: TQueuedSubmission[];
};

export type TStorageHydrationResult = {
  snapshot: TStorageSnapshot;
  source: "local-storage" | "indexeddb";
  migratedFromLocalStorage: boolean;
};

export type TStorageHealth = {
  adapter: "local-storage" | "indexeddb";
  encryptionEnabled: boolean;
  hasDraft: boolean;
  queueLength: number;
  deadLetterLength: number;
  totalEntries: number;
  retentionMs: {
    draft: number | null;
    queue: number | null;
    deadLetter: number | null;
  };
};

export interface TFormStorageAdapter {
  loadDraft(): Record<string, any> | null;
  saveDraft(values: Record<string, any>): void;
  clearDraft(): void;
  loadQueue(): TQueuedSubmission[];
  saveQueue(values: TQueuedSubmission[]): void;
  clearQueue(): void;
  enqueueSubmission(values: Record<string, any>): TQueuedSubmission[];
  dequeueSubmission(): TQueuedSubmission | null;
  updateQueueEntry(entry: TQueuedSubmission): void;
  loadDeadLetterQueue(): TQueuedSubmission[];
  saveDeadLetterQueue(values: TQueuedSubmission[]): void;
  enqueueDeadLetter(entry: TQueuedSubmission): TQueuedSubmission[];
  removeDeadLetterEntry(entryId: string): TQueuedSubmission | null;
  clearDeadLetterQueue(): void;
  getHealth(): TStorageHealth;
  hydrate?(): Promise<TStorageHydrationResult>;
}

function toStoredFileMetadata(value: File | Blob): TStoredFileMetadata {
  return {
    __type: "file-metadata",
    name: "name" in value && typeof value.name === "string" ? value.name : "blob",
    size: value.size,
    mimeType: value.type || "application/octet-stream",
    lastModified:
      "lastModified" in value && typeof value.lastModified === "number"
        ? value.lastModified
        : undefined,
  };
}

function sanitizeStoredValue(value: any): any {
  if (isFileLikeValue(value)) {
    return toStoredFileMetadata(value as File | Blob);
  }

  if (Array.isArray(value)) {
    return value.map((entry) => sanitizeStoredValue(entry));
  }

  if (value && typeof value === "object") {
    const nextValue: Record<string, any> = {};
    Object.entries(value).forEach(([key, entry]) => {
      nextValue[key] = sanitizeStoredValue(entry);
    });
    return nextValue;
  }

  return value;
}

function stripStoredFileMetadata(value: any): any {
  if (Array.isArray(value)) {
    return value
      .map((entry) => stripStoredFileMetadata(entry))
      .filter((entry) => entry !== undefined);
  }

  if (
    value &&
    typeof value === "object" &&
    (value as TStoredFileMetadata).__type === "file-metadata"
  ) {
    return undefined;
  }

  if (value && typeof value === "object") {
    const nextValue: Record<string, any> = {};
    Object.entries(value).forEach(([key, entry]) => {
      const sanitizedEntry = stripStoredFileMetadata(entry);
      if (sanitizedEntry !== undefined) {
        nextValue[key] = sanitizedEntry;
      }
    });
    return nextValue;
  }

  return value;
}

export function getSerializableStorageValues(values: Record<string, any>): Record<string, any> {
  return sanitizeStoredValue(values) as Record<string, any>;
}

export function getRestorableStorageValues(values: Record<string, any> | null): Record<string, any> {
  if (!values) {
    return {};
  }

  const restored = stripStoredFileMetadata(values);
  return restored && typeof restored === "object" ? restored as Record<string, any> : {};
}
