import TFormConfig, { TFormStorageConfig } from "./TFormConfig";
import { isFileLikeValue } from "./field";
import {
  TQueuedSubmission,
  TStorageHealth,
  TStorageHydrationResult,
  TFormStorageAdapter,
  getSerializableStorageValues,
} from "./form-storage";
// ---------------------------------------------------------------------------
// Internal types
// ---------------------------------------------------------------------------

type TQueueState = {
  version: 1;
  items: TQueuedSubmission[];
};

type TDraftState = {
  version: 1;
  savedAt: number;
  values: Record<string, any>;
};

type TStorageLike = Pick<Storage, "getItem" | "setItem" | "removeItem" | "clear" | "key" | "length">;

// ---------------------------------------------------------------------------
// Private storage utilities
// ---------------------------------------------------------------------------

function toStorageLike(storage: Storage | Record<string, any> | null | undefined): TStorageLike | null {
  if (!storage || typeof storage !== "object") return null;
  if (
    typeof (storage as Storage).getItem === "function" &&
    typeof (storage as Storage).setItem === "function" &&
    typeof (storage as Storage).removeItem === "function" &&
    typeof (storage as Storage).clear === "function"
  ) {
    return storage as Storage;
  }

  const objectStorage = storage as Record<string, any>;
  const shim: TStorageLike = {
    get length() { return Object.keys(objectStorage).length; },
    clear() { Object.keys(objectStorage).forEach((key) => { delete objectStorage[key]; }); },
    getItem(key: string) { return Object.prototype.hasOwnProperty.call(objectStorage, key) ? String(objectStorage[key]) : null; },
    key(index: number) { return Object.keys(objectStorage)[index] || null; },
    removeItem(key: string) { delete objectStorage[key]; },
    setItem(key: string, value: string) { objectStorage[key] = String(value); },
  };

  const descriptors: Partial<Record<keyof TStorageLike, PropertyDescriptor>> = {
    getItem: { value: shim.getItem, configurable: true },
    setItem: { value: shim.setItem, configurable: true },
    removeItem: { value: shim.removeItem, configurable: true },
    clear: { value: shim.clear, configurable: true },
    key: { value: shim.key, configurable: true },
    length: { get: () => Object.keys(objectStorage).length, configurable: true },
  };
  try {
    Object.entries(descriptors).forEach(([key, descriptor]) => {
      if (descriptor) Object.defineProperty(objectStorage, key, descriptor);
    });
    return objectStorage as unknown as TStorageLike;
  } catch {
    return shim;
  }
}

function getWindowStorage(): TStorageLike | null {
  if (typeof window === "undefined") return null;
  try { return toStorageLike(window.localStorage as Storage | Record<string, any> | undefined); } catch { return null; }
}

void getWindowStorage();

function toStoredFileMetadata(value: File | Blob) {
  return {
    __type: "file-metadata" as const,
    name: "name" in value && typeof value.name === "string" ? value.name : "blob",
    size: value.size,
    mimeType: value.type || "application/octet-stream",
    lastModified: "lastModified" in value && typeof value.lastModified === "number" ? value.lastModified : undefined,
  };
}

function sanitizeStoredValue(value: any): any {
  if (isFileLikeValue(value)) return toStoredFileMetadata(value as File | Blob);
  if (Array.isArray(value)) return value.map((entry) => sanitizeStoredValue(entry));
  if (value && typeof value === "object") {
    const next: Record<string, any> = {};
    Object.entries(value).forEach(([k, v]) => { next[k] = sanitizeStoredValue(v); });
    return next;
  }
  return value;
}

function stripStoredFileMetadata(value: any): any {
  if (Array.isArray(value)) return value.map((e) => stripStoredFileMetadata(e)).filter((e) => e !== undefined);
  if (value && typeof value === "object" && (value as any).__type === "file-metadata") return undefined;
  if (value && typeof value === "object") {
    const next: Record<string, any> = {};
    Object.entries(value).forEach(([k, v]) => {
      const s = stripStoredFileMetadata(v);
      if (s !== undefined) next[k] = s;
    });
    return next;
  }
  return value;
}

export function getDraftKey(formConfig: TFormConfig, storage: TFormStorageConfig): string {
  return storage.key || `xpressui:draft:${formConfig.name}`;
}

export function getQueueKey(formConfig: TFormConfig, storage: TFormStorageConfig): string {
  const base = storage.key || `xpressui:queue:${formConfig.name}`;
  return `${base}:queue`;
}

export function getDeadLetterKey(formConfig: TFormConfig, storage: TFormStorageConfig): string {
  const base = storage.key || `xpressui:queue:${formConfig.name}`;
  return `${base}:dead-letter`;
}

export function retentionDaysToMs(retentionDays?: number): number | null {
  return typeof retentionDays === "number" && retentionDays > 0
    ? retentionDays * 24 * 60 * 60 * 1000
    : null;
}

// ---------------------------------------------------------------------------
// LocalStorageAdapter
// ---------------------------------------------------------------------------

export class LocalStorageAdapter implements TFormStorageAdapter {
  storageKey: string;
  queueKey: string;
  deadLetterKey: string;
  encryptionKey: string | null;
  draftRetentionMs: number | null;
  queueRetentionMs: number | null;
  deadLetterRetentionMs: number | null;

  constructor(
    storageKey: string,
    queueKey: string,
    deadLetterKey: string,
    encryptionKey: string | null = null,
    draftRetentionMs: number | null = null,
    queueRetentionMs: number | null = null,
    deadLetterRetentionMs: number | null = null,
  ) {
    this.storageKey = storageKey;
    this.queueKey = queueKey;
    this.deadLetterKey = deadLetterKey;
    this.encryptionKey = encryptionKey || null;
    this.draftRetentionMs = draftRetentionMs;
    this.queueRetentionMs = queueRetentionMs;
    this.deadLetterRetentionMs = deadLetterRetentionMs;
  }

  createQueueEntry(values: Record<string, any>): TQueuedSubmission {
    const now = Date.now();
    return {
      id: `queue_${now}_${Math.random().toString(36).slice(2, 10)}`,
      values,
      attempts: 0,
      createdAt: now,
      updatedAt: now,
      nextAttemptAt: now,
    };
  }

  encodePayload(raw: string): string {
    if (!this.encryptionKey) return raw;
    try {
      const key = this.encryptionKey;
      let encoded = "";
      for (let i = 0; i < raw.length; i++) {
        encoded += String.fromCharCode(raw.charCodeAt(i) ^ key.charCodeAt(i % key.length));
      }
      return `enc:v1:${window.btoa(encoded)}`;
    } catch { return raw; }
  }

  decodePayload(raw: string | null): string | null {
    if (!raw) return null;
    if (!raw.startsWith("enc:v1:")) return raw;
    if (!this.encryptionKey) return null;
    try {
      const decoded = window.atob(raw.slice("enc:v1:".length));
      const key = this.encryptionKey;
      let next = "";
      for (let i = 0; i < decoded.length; i++) {
        next += String.fromCharCode(decoded.charCodeAt(i) ^ key.charCodeAt(i % key.length));
      }
      return next;
    } catch { return null; }
  }

  serializeDraftState(values: Record<string, any>): string {
    const state: TDraftState = { version: 1, savedAt: Date.now(), values: getSerializableStorageValues(values) };
    return this.encodePayload(JSON.stringify(state));
  }

  serializeQueueState(values: TQueuedSubmission[]): string {
    const state: TQueueState = { version: 1, items: values };
    return this.encodePayload(JSON.stringify(state));
  }

  getStorageValue(key: string): string | null {
    const storage = getWindowStorage();
    if (!storage) return null;
    try { return storage.getItem(key); } catch { return null; }
  }

  setStorageValue(key: string, value: string): void {
    const storage = getWindowStorage();
    if (!storage) return;
    try { storage.setItem(key, value); } catch { /* quota/private mode */ }
  }

  removeStorageValue(key: string): void {
    const storage = getWindowStorage();
    if (!storage) return;
    try { storage.removeItem(key); } catch { /* ignore */ }
  }

  parseDraftRaw(raw: string | null): { value: Record<string, any> | null; changed: boolean } {
    if (!raw) return { value: null, changed: false };
    const decoded = this.decodePayload(raw);
    if (!decoded) return { value: null, changed: true };
    try {
      const parsed = JSON.parse(decoded);
      if (parsed && typeof parsed === "object" && (parsed as Partial<TDraftState>).version === 1 &&
        typeof (parsed as Partial<TDraftState>).savedAt === "number" &&
        (parsed as Partial<TDraftState>).values && typeof (parsed as Partial<TDraftState>).values === "object") {
        const s = parsed as TDraftState;
        if (this.isExpiredTimestamp(s.savedAt, this.draftRetentionMs)) return { value: null, changed: true };
        return { value: s.values, changed: false };
      }
      return { value: parsed && typeof parsed === "object" ? parsed : null, changed: raw.startsWith("enc:v1:") };
    } catch { return { value: null, changed: true }; }
  }

  parseQueueRaw(raw: string | null): { value: TQueuedSubmission[]; changed: boolean } {
    if (!raw) return { value: [], changed: false };
    const decoded = this.decodePayload(raw);
    if (!decoded) return { value: [], changed: true };
    try {
      const parsed = JSON.parse(decoded);
      if (Array.isArray(parsed)) {
        return { value: parsed.filter((i) => i && typeof i === "object").map((i) => this.createQueueEntry(i as Record<string, any>)), changed: true };
      }
      const state = parsed as Partial<TQueueState>;
      const items = Array.isArray(state?.items) ? state.items : [];
      const filtered = items.filter((i) => !this.isExpiredTimestamp(i.updatedAt || i.createdAt, this.queueRetentionMs));
      return { value: filtered, changed: filtered.length !== items.length };
    } catch { return { value: [], changed: true }; }
  }

  parseDeadLetterRaw(raw: string | null): { value: TQueuedSubmission[]; changed: boolean } {
    if (!raw) return { value: [], changed: false };
    const decoded = this.decodePayload(raw);
    if (!decoded) return { value: [], changed: true };
    try {
      const parsed = JSON.parse(decoded);
      if (Array.isArray(parsed)) {
        const filtered = (parsed as TQueuedSubmission[]).filter((i) => !this.isExpiredTimestamp(i.updatedAt || i.createdAt, this.deadLetterRetentionMs));
        return { value: filtered, changed: filtered.length !== parsed.length };
      }
      const state = parsed as Partial<TQueueState>;
      const items = Array.isArray(state?.items) ? state.items : [];
      const filtered = items.filter((i) => !this.isExpiredTimestamp(i.updatedAt || i.createdAt, this.deadLetterRetentionMs));
      return { value: filtered, changed: filtered.length !== items.length };
    } catch { return { value: [], changed: true }; }
  }

  isExpiredTimestamp(timestamp?: number, retentionMs: number | null = null): boolean {
    if (!retentionMs || typeof timestamp !== "number") return false;
    return Date.now() - timestamp > retentionMs;
  }

  loadDraft(): Record<string, any> | null {
    const parsed = this.parseDraftRaw(this.getStorageValue(this.storageKey));
    if (parsed.changed) { parsed.value ? this.saveDraft(parsed.value) : this.clearDraft(); }
    return parsed.value;
  }

  saveDraft(values: Record<string, any>): void { this.setStorageValue(this.storageKey, this.serializeDraftState(values)); }
  clearDraft(): void { this.removeStorageValue(this.storageKey); }

  loadQueue(): TQueuedSubmission[] {
    const parsed = this.parseQueueRaw(this.getStorageValue(this.queueKey));
    if (parsed.changed) { parsed.value.length ? this.saveQueue(parsed.value) : this.clearQueue(); }
    return parsed.value;
  }

  saveQueue(values: TQueuedSubmission[]): void { this.setStorageValue(this.queueKey, this.serializeQueueState(values)); }
  clearQueue(): void { this.removeStorageValue(this.queueKey); }

  enqueueSubmission(values: Record<string, any>): TQueuedSubmission[] {
    const queue = this.loadQueue();
    queue.push(this.createQueueEntry(getSerializableStorageValues(values)));
    this.saveQueue(queue);
    return queue;
  }

  dequeueSubmission(): TQueuedSubmission | null {
    const queue = this.loadQueue();
    if (!queue.length) return null;
    const next = queue.shift() || null;
    this.saveQueue(queue);
    return next;
  }

  updateQueueEntry(entry: TQueuedSubmission): void {
    this.saveQueue(this.loadQueue().map((i) => (i.id === entry.id ? entry : i)));
  }

  loadDeadLetterQueue(): TQueuedSubmission[] {
    const parsed = this.parseDeadLetterRaw(this.getStorageValue(this.deadLetterKey));
    if (parsed.changed) { parsed.value.length ? this.saveDeadLetterQueue(parsed.value) : this.clearDeadLetterQueue(); }
    return parsed.value;
  }

  saveDeadLetterQueue(values: TQueuedSubmission[]): void { this.setStorageValue(this.deadLetterKey, this.serializeQueueState(values)); }

  enqueueDeadLetter(entry: TQueuedSubmission): TQueuedSubmission[] {
    const queue = this.loadDeadLetterQueue();
    queue.push(entry);
    this.saveDeadLetterQueue(queue);
    return queue;
  }

  removeDeadLetterEntry(entryId: string): TQueuedSubmission | null {
    const queue = this.loadDeadLetterQueue();
    const entry = queue.find((i) => i.id === entryId) || null;
    if (!entry) return null;
    this.saveDeadLetterQueue(queue.filter((i) => i.id !== entryId));
    return entry;
  }

  clearDeadLetterQueue(): void { this.removeStorageValue(this.deadLetterKey); }

  getHealth(): TStorageHealth {
    const queue = this.loadQueue();
    const deadLetter = this.loadDeadLetterQueue();
    return {
      adapter: "local-storage",
      encryptionEnabled: Boolean(this.encryptionKey),
      hasDraft: Boolean(this.loadDraft()),
      queueLength: queue.length,
      deadLetterLength: deadLetter.length,
      totalEntries: queue.length + deadLetter.length,
      retentionMs: { draft: this.draftRetentionMs, queue: this.queueRetentionMs, deadLetter: this.deadLetterRetentionMs },
    };
  }

  async hydrate(): Promise<TStorageHydrationResult> {
    return {
      snapshot: { draft: this.loadDraft(), queue: this.loadQueue(), deadLetter: this.loadDeadLetterQueue() },
      source: "local-storage",
      migratedFromLocalStorage: false,
    };
  }
}

