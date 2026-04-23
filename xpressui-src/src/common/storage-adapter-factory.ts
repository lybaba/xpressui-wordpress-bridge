import TFormConfig from "./TFormConfig";
import { TFormStorageAdapter } from "./form-storage";
import {
  LocalStorageAdapter,
  getDraftKey,
  getQueueKey,
  getDeadLetterKey,
  retentionDaysToMs,
} from "./storage-adapters";
import { IndexedDbStorageAdapter } from "./storage-adapter-indexeddb";

export function createStorageAdapter(formConfig: TFormConfig | null): TFormStorageAdapter | null {
  if (!formConfig?.storage || typeof window === "undefined") return null;
  const storage = formConfig.storage;
  const shouldUseStorage = storage.mode === "draft" || storage.mode === "queue" || storage.mode === "draft-and-queue";
  if (!shouldUseStorage) return null;

  const adapter = storage.adapter || "local-storage";
  const fallbackMs = retentionDaysToMs(storage.retentionDays);
  const draftMs = retentionDaysToMs(storage.retentionDraftDays) ?? fallbackMs;
  const queueMs = retentionDaysToMs(storage.retentionQueueDays) ?? fallbackMs;
  const deadMs = retentionDaysToMs(storage.retentionDeadLetterDays) ?? fallbackMs;

  if (adapter === "local-storage") {
    return new LocalStorageAdapter(getDraftKey(formConfig, storage), getQueueKey(formConfig, storage), getDeadLetterKey(formConfig, storage), storage.encryptionKey || null, draftMs, queueMs, deadMs);
  }
  if (adapter === "indexeddb") {
    return new IndexedDbStorageAdapter(getDraftKey(formConfig, storage), getQueueKey(formConfig, storage), getDeadLetterKey(formConfig, storage), storage.key || `xpressui:idb:${formConfig.name}`, storage.encryptionKey || null, draftMs, queueMs, deadMs);
  }
  return null;
}
