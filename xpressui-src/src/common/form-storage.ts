import TFormConfig, { TFormStorageConfig } from "./TFormConfig";
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

function toStorageLike(storage: Storage | Record<string, any> | null | undefined): TStorageLike | null {
  if (!storage || typeof storage !== "object") {
    return null;
  }

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
    get length() {
      return Object.keys(objectStorage).length;
    },
    clear() {
      Object.keys(objectStorage).forEach((key) => {
        delete objectStorage[key];
      });
    },
    getItem(key: string) {
      return Object.prototype.hasOwnProperty.call(objectStorage, key)
        ? String(objectStorage[key])
        : null;
    },
    key(index: number) {
      return Object.keys(objectStorage)[index] || null;
    },
    removeItem(key: string) {
      delete objectStorage[key];
    },
    setItem(key: string, value: string) {
      objectStorage[key] = String(value);
    },
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
      if (descriptor) {
        Object.defineProperty(objectStorage, key, descriptor);
      }
    });
    return objectStorage as unknown as TStorageLike;
  } catch {
    return shim;
  }
}

function getWindowStorage(): TStorageLike | null {
  if (typeof window === "undefined") {
    return null;
  }

  try {
    return toStorageLike(window.localStorage as Storage | Record<string, any> | undefined);
  } catch {
    return null;
  }
}

// Ensure Storage-like helpers exist in non-browser test environments.
void getWindowStorage();

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
    const nextValue = value
      .map((entry) => stripStoredFileMetadata(entry))
      .filter((entry) => entry !== undefined);
    return nextValue;
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

type TIndexedDbRequest<T = any> = {
  onsuccess: ((this: TIndexedDbRequest<T>, event: Event) => any) | null;
  onerror: ((this: TIndexedDbRequest<T>, event: Event) => any) | null;
  onupgradeneeded?: ((this: TIndexedDbRequest<T>, event: Event) => any) | null;
  result: T;
  error?: unknown;
};

type TIndexedDbDatabase = {
  objectStoreNames?: { contains(name: string): boolean };
  createObjectStore(name: string): unknown;
  transaction(
    storeNames: string | string[],
    mode?: "readonly" | "readwrite",
  ): {
    objectStore(name: string): {
      get(key: string): TIndexedDbRequest<any>;
      put(value: any, key: string): TIndexedDbRequest<any>;
      delete(key: string): TIndexedDbRequest<any>;
    };
  };
};

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

function getDraftKey(formConfig: TFormConfig, storage: TFormStorageConfig): string {
  return storage.key || `xpressui:draft:${formConfig.name}`;
}

function getQueueKey(formConfig: TFormConfig, storage: TFormStorageConfig): string {
  const baseKey = storage.key || `xpressui:queue:${formConfig.name}`;
  return `${baseKey}:queue`;
}

function getDeadLetterKey(formConfig: TFormConfig, storage: TFormStorageConfig): string {
  const baseKey = storage.key || `xpressui:queue:${formConfig.name}`;
  return `${baseKey}:dead-letter`;
}

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
    if (!this.encryptionKey) {
      return raw;
    }

    try {
      const key = this.encryptionKey;
      let encoded = "";
      for (let index = 0; index < raw.length; index += 1) {
        encoded += String.fromCharCode(
          raw.charCodeAt(index) ^ key.charCodeAt(index % key.length),
        );
      }

      return `enc:v1:${window.btoa(encoded)}`;
    } catch {
      return raw;
    }
  }

  decodePayload(raw: string | null): string | null {
    if (!raw) {
      return null;
    }

    if (!raw.startsWith("enc:v1:")) {
      return raw;
    }

    if (!this.encryptionKey) {
      return null;
    }

    try {
      const decoded = window.atob(raw.slice("enc:v1:".length));
      const key = this.encryptionKey;
      let nextValue = "";
      for (let index = 0; index < decoded.length; index += 1) {
        nextValue += String.fromCharCode(
          decoded.charCodeAt(index) ^ key.charCodeAt(index % key.length),
        );
      }

      return nextValue;
    } catch {
      return null;
    }
  }

  serializeDraftState(values: Record<string, any>): string {
    const state: TDraftState = {
      version: 1,
      savedAt: Date.now(),
      values: getSerializableStorageValues(values),
    };

    return this.encodePayload(JSON.stringify(state));
  }

  serializeQueueState(values: TQueuedSubmission[]): string {
    const state: TQueueState = {
      version: 1,
      items: values,
    };

    return this.encodePayload(JSON.stringify(state));
  }

  getStorageValue(key: string): string | null {
    const storage = getWindowStorage();
    if (!storage) {
      return null;
    }
    try {
      return storage.getItem(key);
    } catch {
      return null;
    }
  }

  setStorageValue(key: string, value: string): void {
    const storage = getWindowStorage();
    if (!storage) {
      return;
    }
    try {
      storage.setItem(key, value);
    } catch {
      // Ignore storage write failures (quota/private mode).
    }
  }

  removeStorageValue(key: string): void {
    const storage = getWindowStorage();
    if (!storage) {
      return;
    }
    try {
      storage.removeItem(key);
    } catch {
      // Ignore storage clear failures.
    }
  }

  parseDraftRaw(raw: string | null): { value: Record<string, any> | null; changed: boolean } {
    if (!raw) {
      return { value: null, changed: false };
    }

    const decoded = this.decodePayload(raw);
    if (!decoded) {
      return { value: null, changed: true };
    }

    try {
      const parsed = JSON.parse(decoded);
      if (
        parsed &&
        typeof parsed === "object" &&
        (parsed as Partial<TDraftState>).version === 1 &&
        typeof (parsed as Partial<TDraftState>).savedAt === "number" &&
        (parsed as Partial<TDraftState>).values &&
        typeof (parsed as Partial<TDraftState>).values === "object"
      ) {
        const draftState = parsed as TDraftState;
        if (this.isExpiredTimestamp(draftState.savedAt, this.draftRetentionMs)) {
          return { value: null, changed: true };
        }

        return { value: draftState.values, changed: false };
      }

      return {
        value: parsed && typeof parsed === "object" ? parsed : null,
        changed: raw.startsWith("enc:v1:"),
      };
    } catch {
      return { value: null, changed: true };
    }
  }

  parseQueueRaw(raw: string | null): { value: TQueuedSubmission[]; changed: boolean } {
    if (!raw) {
      return { value: [], changed: false };
    }

    const decoded = this.decodePayload(raw);
    if (!decoded) {
      return { value: [], changed: true };
    }

    try {
      const parsed = JSON.parse(decoded);
      if (Array.isArray(parsed)) {
        return {
          value: parsed
            .filter((item) => item && typeof item === "object")
            .map((item) => this.createQueueEntry(item as Record<string, any>)),
          changed: true,
        };
      }

      const state = parsed as Partial<TQueueState>;
      const items = Array.isArray(state?.items) ? state.items : [];
      const filtered = items.filter(
        (item) => !this.isExpiredTimestamp(item.updatedAt || item.createdAt, this.queueRetentionMs),
      );
      return {
        value: filtered,
        changed: filtered.length !== items.length,
      };
    } catch {
      return { value: [], changed: true };
    }
  }

  parseDeadLetterRaw(raw: string | null): { value: TQueuedSubmission[]; changed: boolean } {
    if (!raw) {
      return { value: [], changed: false };
    }

    const decoded = this.decodePayload(raw);
    if (!decoded) {
      return { value: [], changed: true };
    }

    try {
      const parsed = JSON.parse(decoded);
      if (Array.isArray(parsed)) {
        const filtered = (parsed as TQueuedSubmission[]).filter(
          (item) =>
            !this.isExpiredTimestamp(item.updatedAt || item.createdAt, this.deadLetterRetentionMs),
        );
        return {
          value: filtered,
          changed: filtered.length !== parsed.length,
        };
      }

      const state = parsed as Partial<TQueueState>;
      const items = Array.isArray(state?.items) ? state.items : [];
      const filtered = items.filter(
        (item) =>
          !this.isExpiredTimestamp(item.updatedAt || item.createdAt, this.deadLetterRetentionMs),
      );
      return {
        value: filtered,
        changed: filtered.length !== items.length,
      };
    } catch {
      return { value: [], changed: true };
    }
  }

  isExpiredTimestamp(timestamp?: number, retentionMs: number | null = null): boolean {
    if (!retentionMs || typeof timestamp !== "number") {
      return false;
    }

    return Date.now() - timestamp > retentionMs;
  }

  loadDraft(): Record<string, any> | null {
    const raw = this.getStorageValue(this.storageKey);
    const parsed = this.parseDraftRaw(raw);
    if (parsed.changed) {
      if (parsed.value) {
        this.saveDraft(parsed.value);
      } else {
        this.clearDraft();
      }
    }

    return parsed.value;
  }

  saveDraft(values: Record<string, any>): void {
    this.setStorageValue(this.storageKey, this.serializeDraftState(values));
  }

  clearDraft(): void {
    this.removeStorageValue(this.storageKey);
  }

  loadQueue(): TQueuedSubmission[] {
    const raw = this.getStorageValue(this.queueKey);
    const parsed = this.parseQueueRaw(raw);
    if (parsed.changed) {
      if (parsed.value.length) {
        this.saveQueue(parsed.value);
      } else {
        this.clearQueue();
      }
    }

    return parsed.value;
  }

  saveQueue(values: TQueuedSubmission[]): void {
    this.setStorageValue(this.queueKey, this.serializeQueueState(values));
  }

  clearQueue(): void {
    this.removeStorageValue(this.queueKey);
  }

  enqueueSubmission(values: Record<string, any>): TQueuedSubmission[] {
    const queue = this.loadQueue();
    queue.push(this.createQueueEntry(getSerializableStorageValues(values)));
    this.saveQueue(queue);
    return queue;
  }

  dequeueSubmission(): TQueuedSubmission | null {
    const queue = this.loadQueue();
    if (!queue.length) {
      return null;
    }

    const next = queue.shift() || null;
    this.saveQueue(queue);
    return next;
  }

  updateQueueEntry(entry: TQueuedSubmission): void {
    const queue = this.loadQueue();
    const nextQueue = queue.map((item) => (item.id === entry.id ? entry : item));
    this.saveQueue(nextQueue);
  }

  loadDeadLetterQueue(): TQueuedSubmission[] {
    const raw = this.getStorageValue(this.deadLetterKey);
    const parsed = this.parseDeadLetterRaw(raw);
    if (parsed.changed) {
      if (parsed.value.length) {
        this.saveDeadLetterQueue(parsed.value);
      } else {
        this.clearDeadLetterQueue();
      }
    }

    return parsed.value;
  }

  saveDeadLetterQueue(values: TQueuedSubmission[]): void {
    this.setStorageValue(this.deadLetterKey, this.serializeQueueState(values));
  }

  enqueueDeadLetter(entry: TQueuedSubmission): TQueuedSubmission[] {
    const queue = this.loadDeadLetterQueue();
    queue.push(entry);
    this.saveDeadLetterQueue(queue);
    return queue;
  }

  removeDeadLetterEntry(entryId: string): TQueuedSubmission | null {
    const queue = this.loadDeadLetterQueue();
    const entry = queue.find((item) => item.id === entryId) || null;
    if (!entry) {
      return null;
    }

    this.saveDeadLetterQueue(queue.filter((item) => item.id !== entryId));
    return entry;
  }

  clearDeadLetterQueue(): void {
    this.removeStorageValue(this.deadLetterKey);
  }

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
      retentionMs: {
        draft: this.draftRetentionMs,
        queue: this.queueRetentionMs,
        deadLetter: this.deadLetterRetentionMs,
      },
    };
  }

  async hydrate(): Promise<TStorageHydrationResult> {
    return {
      snapshot: {
        draft: this.loadDraft(),
        queue: this.loadQueue(),
        deadLetter: this.loadDeadLetterQueue(),
      },
      source: "local-storage",
      migratedFromLocalStorage: false,
    };
  }
}

export class IndexedDbStorageAdapter extends LocalStorageAdapter {
  dbName: string;
  storeName: string;

  constructor(
    storageKey: string,
    queueKey: string,
    deadLetterKey: string,
    dbName: string,
    encryptionKey: string | null = null,
    draftRetentionMs: number | null = null,
    queueRetentionMs: number | null = null,
    deadLetterRetentionMs: number | null = null,
    storeName: string = "form-state",
  ) {
    super(
      storageKey,
      queueKey,
      deadLetterKey,
      encryptionKey,
      draftRetentionMs,
      queueRetentionMs,
      deadLetterRetentionMs,
    );
    this.dbName = dbName;
    this.storeName = storeName;
    void this.hydrateFromIndexedDb();
  }

  getIndexedDb(): IDBFactory | null {
    if (typeof window === "undefined" || !("indexedDB" in window) || !window.indexedDB) {
      return null;
    }

    return window.indexedDB;
  }

  async openDb(): Promise<TIndexedDbDatabase | null> {
    const indexedDb = this.getIndexedDb();
    if (!indexedDb) {
      return null;
    }

    return new Promise((resolve) => {
      try {
        const request = indexedDb.open(this.dbName, 1) as unknown as TIndexedDbRequest<TIndexedDbDatabase>;
        request.onupgradeneeded = () => {
          const db = request.result;
          if (!db.objectStoreNames?.contains?.(this.storeName)) {
            db.createObjectStore(this.storeName);
          }
        };
        request.onsuccess = () => resolve(request.result || null);
        request.onerror = () => resolve(null);
      } catch {
        resolve(null);
      }
    });
  }

  async readKey<T = any>(key: string): Promise<T | null> {
    const db = await this.openDb();
    if (!db) {
      return null;
    }

    return new Promise((resolve) => {
      try {
        const request = db
          .transaction(this.storeName, "readonly")
          .objectStore(this.storeName)
          .get(key) as TIndexedDbRequest<T>;
        request.onsuccess = () => resolve((request.result as T) || null);
        request.onerror = () => resolve(null);
      } catch {
        resolve(null);
      }
    });
  }

  async writeKey(key: string, value: string): Promise<void> {
    const db = await this.openDb();
    if (!db) {
      return;
    }

    try {
      db.transaction(this.storeName, "readwrite").objectStore(this.storeName).put(value, key);
    } catch {
      // Ignore IndexedDB write failures.
    }
  }

  async deleteKey(key: string): Promise<void> {
    const db = await this.openDb();
    if (!db) {
      return;
    }

    try {
      db.transaction(this.storeName, "readwrite").objectStore(this.storeName).delete(key);
    } catch {
      // Ignore IndexedDB delete failures.
    }
  }

  async hydrateFromIndexedDb(): Promise<void> {
    const keys = [this.storageKey, this.queueKey, this.deadLetterKey];
    await Promise.all(
      keys.map(async (key) => {
        const value = await this.readKey<string>(key);
        if (typeof value !== "string") {
          return;
        }

        try {
          this.setStorageValue(key, value);
        } catch {
          // Ignore cache warm-up failures.
        }
      }),
    );
  }

  getLocalSnapshot(): TStorageSnapshot {
    return {
      draft: this.loadDraft(),
      queue: this.loadQueue(),
      deadLetter: this.loadDeadLetterQueue(),
    };
  }

  async readRawSnapshotFromIndexedDb(): Promise<{
    draftRaw: string | null;
    queueRaw: string | null;
    deadLetterRaw: string | null;
  }> {
    const [draftRaw, queueRaw, deadLetterRaw] = await Promise.all([
      this.readKey<string>(this.storageKey),
      this.readKey<string>(this.queueKey),
      this.readKey<string>(this.deadLetterKey),
    ]);

    return {
      draftRaw: typeof draftRaw === "string" ? draftRaw : null,
      queueRaw: typeof queueRaw === "string" ? queueRaw : null,
      deadLetterRaw: typeof deadLetterRaw === "string" ? deadLetterRaw : null,
    };
  }

  async hydrate(): Promise<TStorageHydrationResult> {
    const indexedDb = this.getIndexedDb();
    if (!indexedDb) {
      return {
        snapshot: this.getLocalSnapshot(),
        source: "local-storage",
        migratedFromLocalStorage: false,
      };
    }

    const indexedDbState = await this.readRawSnapshotFromIndexedDb();
    const hasIndexedDbState = Boolean(
      indexedDbState.draftRaw || indexedDbState.queueRaw || indexedDbState.deadLetterRaw,
    );

    if (!hasIndexedDbState) {
      const localDraftRaw = this.getStorageValue(this.storageKey);
      const localQueueRaw = this.getStorageValue(this.queueKey);
      const localDeadLetterRaw = this.getStorageValue(this.deadLetterKey);
      const hasLocalState = Boolean(localDraftRaw || localQueueRaw || localDeadLetterRaw);

      if (hasLocalState) {
        await Promise.all([
          localDraftRaw ? this.writeKey(this.storageKey, localDraftRaw) : Promise.resolve(),
          localQueueRaw ? this.writeKey(this.queueKey, localQueueRaw) : Promise.resolve(),
          localDeadLetterRaw ? this.writeKey(this.deadLetterKey, localDeadLetterRaw) : Promise.resolve(),
        ]);

        return {
          snapshot: {
            draft: this.parseDraftRaw(localDraftRaw).value,
            queue: this.parseQueueRaw(localQueueRaw).value,
            deadLetter: this.parseDeadLetterRaw(localDeadLetterRaw).value,
          },
          source: "indexeddb",
          migratedFromLocalStorage: true,
        };
      }
    }

    if (indexedDbState.draftRaw !== null) {
      this.setStorageValue(this.storageKey, indexedDbState.draftRaw);
    } else {
      this.removeStorageValue(this.storageKey);
    }
    if (indexedDbState.queueRaw !== null) {
      this.setStorageValue(this.queueKey, indexedDbState.queueRaw);
    } else {
      this.removeStorageValue(this.queueKey);
    }
    if (indexedDbState.deadLetterRaw !== null) {
      this.setStorageValue(this.deadLetterKey, indexedDbState.deadLetterRaw);
    } else {
      this.removeStorageValue(this.deadLetterKey);
    }

    return {
      snapshot: {
        draft: this.parseDraftRaw(indexedDbState.draftRaw).value,
        queue: this.parseQueueRaw(indexedDbState.queueRaw).value,
        deadLetter: this.parseDeadLetterRaw(indexedDbState.deadLetterRaw).value,
      },
      source: "indexeddb",
      migratedFromLocalStorage: false,
    };
  }

  override saveDraft(values: Record<string, any>): void {
    super.saveDraft(values);
    const serialized = this.getStorageValue(this.storageKey);
    if (serialized !== null) {
      void this.writeKey(this.storageKey, serialized);
    }
  }

  override clearDraft(): void {
    super.clearDraft();
    void this.deleteKey(this.storageKey);
  }

  override saveQueue(values: TQueuedSubmission[]): void {
    super.saveQueue(values);
    const serialized = this.getStorageValue(this.queueKey);
    if (serialized !== null) {
      void this.writeKey(this.queueKey, serialized);
    }
  }

  override clearQueue(): void {
    super.clearQueue();
    void this.deleteKey(this.queueKey);
  }

  override saveDeadLetterQueue(values: TQueuedSubmission[]): void {
    super.saveDeadLetterQueue(values);
    const serialized = this.getStorageValue(this.deadLetterKey);
    if (serialized !== null) {
      void this.writeKey(this.deadLetterKey, serialized);
    }
  }

  override clearDeadLetterQueue(): void {
    super.clearDeadLetterQueue();
    void this.deleteKey(this.deadLetterKey);
  }

  override getHealth(): TStorageHealth {
    return {
      ...super.getHealth(),
      adapter: "indexeddb",
    };
  }
}

function retentionDaysToMs(retentionDays?: number): number | null {
  return typeof retentionDays === "number" && retentionDays > 0
    ? retentionDays * 24 * 60 * 60 * 1000
    : null;
}

export function createStorageAdapter(
  formConfig: TFormConfig | null,
): TFormStorageAdapter | null {
  if (!formConfig?.storage || typeof window === "undefined") {
    return null;
  }

  const storage = formConfig.storage;
  const shouldUseStorage =
    storage.mode === "draft" ||
    storage.mode === "queue" ||
    storage.mode === "draft-and-queue";
  if (!shouldUseStorage) {
    return null;
  }

  const adapter = storage.adapter || "local-storage";
  const fallbackRetentionMs = retentionDaysToMs(storage.retentionDays);
  const draftRetentionMs = retentionDaysToMs(storage.retentionDraftDays) ?? fallbackRetentionMs;
  const queueRetentionMs = retentionDaysToMs(storage.retentionQueueDays) ?? fallbackRetentionMs;
  const deadLetterRetentionMs =
    retentionDaysToMs(storage.retentionDeadLetterDays) ?? fallbackRetentionMs;
  if (adapter === "local-storage") {
    return new LocalStorageAdapter(
      getDraftKey(formConfig, storage),
      getQueueKey(formConfig, storage),
      getDeadLetterKey(formConfig, storage),
      storage.encryptionKey || null,
      draftRetentionMs,
      queueRetentionMs,
      deadLetterRetentionMs,
    );
  }

  if (adapter === "indexeddb") {
    return new IndexedDbStorageAdapter(
      getDraftKey(formConfig, storage),
      getQueueKey(formConfig, storage),
      getDeadLetterKey(formConfig, storage),
      storage.key || `xpressui:idb:${formConfig.name}`,
      storage.encryptionKey || null,
      draftRetentionMs,
      queueRetentionMs,
      deadLetterRetentionMs,
    );
  }

  return null;
}
