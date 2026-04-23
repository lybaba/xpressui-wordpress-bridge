import {
  TQueuedSubmission,
  TStorageHealth,
  TStorageHydrationResult,
  TStorageSnapshot,
} from "./form-storage";
import { LocalStorageAdapter } from "./storage-adapters";

// ---------------------------------------------------------------------------
// IndexedDB-specific internal types
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// IndexedDbStorageAdapter
// ---------------------------------------------------------------------------

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
    super(storageKey, queueKey, deadLetterKey, encryptionKey, draftRetentionMs, queueRetentionMs, deadLetterRetentionMs);
    this.dbName = dbName;
    this.storeName = storeName;
    void this.hydrateFromIndexedDb();
  }

  getIndexedDb(): IDBFactory | null {
    if (typeof window === "undefined" || !("indexedDB" in window) || !window.indexedDB) return null;
    return window.indexedDB;
  }

  async openDb(): Promise<TIndexedDbDatabase | null> {
    const indexedDb = this.getIndexedDb();
    if (!indexedDb) return null;
    return new Promise((resolve) => {
      try {
        const request = indexedDb.open(this.dbName, 1) as unknown as TIndexedDbRequest<TIndexedDbDatabase>;
        request.onupgradeneeded = () => {
          const db = request.result;
          if (!db.objectStoreNames?.contains?.(this.storeName)) db.createObjectStore(this.storeName);
        };
        request.onsuccess = () => resolve(request.result || null);
        request.onerror = () => resolve(null);
      } catch { resolve(null); }
    });
  }

  async readKey<T = any>(key: string): Promise<T | null> {
    const db = await this.openDb();
    if (!db) return null;
    return new Promise((resolve) => {
      try {
        const req = db.transaction(this.storeName, "readonly").objectStore(this.storeName).get(key) as TIndexedDbRequest<T>;
        req.onsuccess = () => resolve((req.result as T) || null);
        req.onerror = () => resolve(null);
      } catch { resolve(null); }
    });
  }

  async writeKey(key: string, value: string): Promise<void> {
    const db = await this.openDb();
    if (!db) return;
    try { db.transaction(this.storeName, "readwrite").objectStore(this.storeName).put(value, key); } catch { /* ignore */ }
  }

  async deleteKey(key: string): Promise<void> {
    const db = await this.openDb();
    if (!db) return;
    try { db.transaction(this.storeName, "readwrite").objectStore(this.storeName).delete(key); } catch { /* ignore */ }
  }

  async hydrateFromIndexedDb(): Promise<void> {
    await Promise.all([this.storageKey, this.queueKey, this.deadLetterKey].map(async (key) => {
      const value = await this.readKey<string>(key);
      if (typeof value !== "string") return;
      try { this.setStorageValue(key, value); } catch { /* ignore */ }
    }));
  }

  getLocalSnapshot(): TStorageSnapshot {
    return { draft: this.loadDraft(), queue: this.loadQueue(), deadLetter: this.loadDeadLetterQueue() };
  }

  async readRawSnapshotFromIndexedDb(): Promise<{ draftRaw: string | null; queueRaw: string | null; deadLetterRaw: string | null }> {
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
    if (!indexedDb) return { snapshot: this.getLocalSnapshot(), source: "local-storage", migratedFromLocalStorage: false };

    const idbState = await this.readRawSnapshotFromIndexedDb();
    const hasIdbState = Boolean(idbState.draftRaw || idbState.queueRaw || idbState.deadLetterRaw);

    if (!hasIdbState) {
      const localDraftRaw = this.getStorageValue(this.storageKey);
      const localQueueRaw = this.getStorageValue(this.queueKey);
      const localDeadLetterRaw = this.getStorageValue(this.deadLetterKey);
      if (Boolean(localDraftRaw || localQueueRaw || localDeadLetterRaw)) {
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

    if (idbState.draftRaw !== null) this.setStorageValue(this.storageKey, idbState.draftRaw); else this.removeStorageValue(this.storageKey);
    if (idbState.queueRaw !== null) this.setStorageValue(this.queueKey, idbState.queueRaw); else this.removeStorageValue(this.queueKey);
    if (idbState.deadLetterRaw !== null) this.setStorageValue(this.deadLetterKey, idbState.deadLetterRaw); else this.removeStorageValue(this.deadLetterKey);

    return {
      snapshot: {
        draft: this.parseDraftRaw(idbState.draftRaw).value,
        queue: this.parseQueueRaw(idbState.queueRaw).value,
        deadLetter: this.parseDeadLetterRaw(idbState.deadLetterRaw).value,
      },
      source: "indexeddb",
      migratedFromLocalStorage: false,
    };
  }

  override saveDraft(values: Record<string, any>): void {
    super.saveDraft(values);
    const s = this.getStorageValue(this.storageKey);
    if (s !== null) void this.writeKey(this.storageKey, s);
  }

  override clearDraft(): void { super.clearDraft(); void this.deleteKey(this.storageKey); }

  override saveQueue(values: TQueuedSubmission[]): void {
    super.saveQueue(values);
    const s = this.getStorageValue(this.queueKey);
    if (s !== null) void this.writeKey(this.queueKey, s);
  }

  override clearQueue(): void { super.clearQueue(); void this.deleteKey(this.queueKey); }

  override saveDeadLetterQueue(values: TQueuedSubmission[]): void {
    super.saveDeadLetterQueue(values);
    const s = this.getStorageValue(this.deadLetterKey);
    if (s !== null) void this.writeKey(this.deadLetterKey, s);
  }

  override clearDeadLetterQueue(): void { super.clearDeadLetterQueue(); void this.deleteKey(this.deadLetterKey); }

  override getHealth(): TStorageHealth { return { ...super.getHealth(), adapter: "indexeddb" }; }
}
