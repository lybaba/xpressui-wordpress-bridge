import TFieldConfig from "./TFieldConfig";
import {
  TFormSubmitRequest,
  TFormUploadPolicyHook,
  TFormUploadPolicyResult,
  TFormUploadPolicyStage,
} from "./TFormConfig";
import { isFileLikeValue } from "./field";
import { buildFormDataBody, resolveSubmitRequestUrl, submitFormValues } from "./form-submit";
import { normalizePresignedUploadDescriptor } from "./upload-contract";
import {
  TUploadRetryStage,
  TUploadRetryFailureMeta,
  sleep,
  normalizeRetryPolicy,
  shouldRetryUploadError,
  getUploadErrorReason,
  toUploadRetryFailureMeta,
  getRetryDelayMs,
} from "./upload-retry";
import {
  TUploadResumeState,
  getChunkSizeBytes,
  loadUploadResumeState,
  saveUploadResumeState,
  clearUploadResumeState,
  getResumeChunkIndexFromDescriptor,
} from "./upload-resume";

export type TFormUploadState = {
  phase: "upload";
  status: "uploading" | "complete" | "error";
  progress: number;
  fieldNames: string[];
};

type TXhrUploadResponse = {
  response: Response;
  result: any;
};

type TFormUploadRuntimeOptions = {
  emitEvent?: (eventName: string, detail: Record<string, any>) => boolean;
};

function noopEmitEvent(): boolean {
  return true;
}

function getFileFieldNames(
  values: Record<string, any>,
  fieldMap: Record<string, TFieldConfig>,
): string[] {
  return Object.entries(fieldMap)
    .filter(([fieldName]) => {
      if (!values[fieldName]) return false;
      if (isFileLikeValue(values[fieldName])) return true;
      return Array.isArray(values[fieldName]) && values[fieldName].some((entry) => isFileLikeValue(entry));
    })
    .map(([fieldName]) => fieldName);
}

function createUploadState(
  fieldNames: string[],
  status: TFormUploadState["status"],
  progress: number,
): TFormUploadState {
  return { phase: "upload", status, progress, fieldNames };
}

function parseXhrResult(xhr: XMLHttpRequest): any {
  const contentType = xhr.getResponseHeader("content-type") || "";
  if (contentType.includes("application/json")) {
    return xhr.responseText ? JSON.parse(xhr.responseText) : null;
  }
  return xhr.responseText || null;
}

function uploadWithProgress(
  url: string,
  method: string,
  headers: Record<string, string>,
  body: FormData | Blob,
  onProgress: (progress: number) => void,
): Promise<TXhrUploadResponse> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    Object.entries(headers).forEach(([key, value]) => { xhr.setRequestHeader(key, value); });
    xhr.upload.onprogress = (event) => {
      if (!event.lengthComputable || event.total === 0) return;
      onProgress(Math.round((event.loaded / event.total) * 100));
    };
    xhr.onerror = () => { reject(new Error("upload_network_error")); };
    xhr.onload = () => {
      const response = new Response(xhr.responseText, {
        status: xhr.status,
        headers: { "content-type": xhr.getResponseHeader("content-type") || "text/plain" },
      });
      const result = parseXhrResult(xhr);
      if (xhr.status >= 200 && xhr.status < 300) {
        resolve({ response, result });
      } else {
        reject({ response, result });
      }
    };
    xhr.send(body);
  });
}

function buildPresignPayload(fieldName: string, file: File): Record<string, any> {
  return { fieldName, fileName: file.name, contentType: file.type, size: file.size };
}

function isUploadPolicyAllowed(result: TFormUploadPolicyResult): { allowed: boolean; reason?: string } {
  if (typeof result === "boolean") return { allowed: result };
  if (typeof result === "string") return { allowed: false, reason: result };
  if (result && typeof result === "object") return { allowed: result.allowed !== false, reason: result.reason };
  return { allowed: true };
}

export class FormUploadRuntime {
  emitEvent: NonNullable<TFormUploadRuntimeOptions["emitEvent"]>;

  constructor(options: TFormUploadRuntimeOptions = {}) {
    this.emitEvent = options.emitEvent || noopEmitEvent;
  }

  emitUploadEvent(
    eventName: string,
    values: Record<string, any>,
    submitConfig: TFormSubmitRequest,
    state: TFormUploadState,
    extra?: Record<string, any>,
  ): void {
    this.emitEvent(eventName, { values, submit: submitConfig, result: { ...state, ...extra } });
  }

  async submit(
    values: Record<string, any>,
    submitConfig: TFormSubmitRequest,
    fieldMap: Record<string, TFieldConfig>,
  ): Promise<{ response: Response; result: any }> {
    const fileFieldNames = getFileFieldNames(values, fieldMap);
    const hasFileValues = fileFieldNames.length > 0;
    await this.runUploadPolicies(values, submitConfig, fieldMap, fileFieldNames);

    if (!hasFileValues || submitConfig.mode !== "form-data") {
      return submitFormValues(values, submitConfig, fieldMap);
    }

    if (submitConfig.uploadStrategy === "presigned" && submitConfig.presignEndpoint) {
      return this.submitWithPresignedUploads(values, submitConfig, fieldMap, fileFieldNames);
    }

    return this.submitMultipart(values, submitConfig, fieldMap, fileFieldNames);
  }

  async runSingleUploadPolicy(
    stage: TFormUploadPolicyStage,
    hook: TFormUploadPolicyHook | undefined,
    values: Record<string, any>,
    submitConfig: TFormSubmitRequest,
    fieldMap: Record<string, TFieldConfig>,
    fileFieldNames: string[],
  ): Promise<void> {
    if (!hook || !fileFieldNames.length) return;

    for (const fieldName of fileFieldNames) {
      const fieldValue = values[fieldName];
      const files = Array.isArray(fieldValue) ? fieldValue : [fieldValue];
      const uploadFiles = files.filter((entry) => isFileLikeValue(entry)) as File[];
      for (const file of uploadFiles) {
        const result = await hook({
          stage, fieldName, file, values, submit: submitConfig, formConfig: null, field: fieldMap[fieldName],
        });
        const decision = isUploadPolicyAllowed(result);
        if (!decision.allowed) {
          const reason = decision.reason || `${stage} policy rejected file "${file.name}".`;
          this.emitEvent("xpressui:file-policy-rejected", {
            values, submit: submitConfig,
            result: { stage, fieldName, fileName: file.name, reason },
          });
          throw new Error(reason);
        }
      }
    }
  }

  async runUploadPolicies(
    values: Record<string, any>,
    submitConfig: TFormSubmitRequest,
    fieldMap: Record<string, TFieldConfig>,
    fileFieldNames: string[],
  ): Promise<void> {
    if (!fileFieldNames.length) return;
    await this.runSingleUploadPolicy("file-acceptance", submitConfig.fileAcceptancePolicy, values, submitConfig, fieldMap, fileFieldNames);
    await this.runSingleUploadPolicy("content-moderation", submitConfig.contentModerationPolicy, values, submitConfig, fieldMap, fileFieldNames);
    await this.runSingleUploadPolicy("virus-scan", submitConfig.virusScanPolicy, values, submitConfig, fieldMap, fileFieldNames);
  }

  async submitMultipart(
    values: Record<string, any>,
    submitConfig: TFormSubmitRequest,
    fieldMap: Record<string, TFieldConfig>,
    fileFieldNames: string[],
  ): Promise<{ response: Response; result: any }> {
    const body = buildFormDataBody(values, submitConfig, fieldMap);
    const headers = { ...(submitConfig.headers || {}) };
    const method = submitConfig.method || "POST";
    const endpoint = resolveSubmitRequestUrl(submitConfig.endpoint, submitConfig.baseUrl);

    this.emitUploadEvent("xpressui:upload-start", values, submitConfig, createUploadState(fileFieldNames, "uploading", 0));

    const result = await uploadWithProgress(endpoint, method, headers, body, (progress) => {
      this.emitUploadEvent("xpressui:upload-progress", values, submitConfig, createUploadState(fileFieldNames, "uploading", progress));
    }).catch((error) => {
      this.emitUploadEvent("xpressui:upload-error", values, submitConfig, createUploadState(fileFieldNames, "error", 0));
      throw error;
    });

    this.emitUploadEvent("xpressui:upload-complete", values, submitConfig, createUploadState(fileFieldNames, "complete", 100));
    return result;
  }

  async submitWithPresignedUploads(
    values: Record<string, any>,
    submitConfig: TFormSubmitRequest,
    fieldMap: Record<string, TFieldConfig>,
    fileFieldNames: string[],
  ): Promise<{ response: Response; result: any }> {
    const transformedValues = { ...values };
    const allFiles = fileFieldNames.flatMap((fieldName) => {
      const fieldValue = values[fieldName];
      const files = Array.isArray(fieldValue) ? fieldValue : [fieldValue];
      return files.filter((entry) => isFileLikeValue(entry)).map((file) => ({ fieldName, file: file as File }));
    });

    let completed = 0;
    const total = Math.max(1, allFiles.length);
    const retryPolicy = normalizeRetryPolicy(submitConfig);

    this.emitUploadEvent("xpressui:upload-start", values, submitConfig, createUploadState(fileFieldNames, "uploading", 0), { strategy: "presigned" });

    for (const { fieldName, file } of allFiles) {
      const presignUrl = resolveSubmitRequestUrl(submitConfig.presignEndpoint as string, submitConfig.baseUrl);

      const executeWithRetry = async <T>(stage: TUploadRetryStage, operation: () => Promise<T>): Promise<T> => {
        let lastError: any = null;
        let attemptsPerformed = 0;
        for (let attempt = 1; attempt <= retryPolicy.maxAttempts; attempt += 1) {
          attemptsPerformed = attempt;
          try {
            return await operation();
          } catch (error: any) {
            lastError = error;
            const retryable = shouldRetryUploadError(error);
            if (!retryable || attempt >= retryPolicy.maxAttempts) {
              if (lastError && typeof lastError === "object") {
                (lastError as any).__uploadRetry = toUploadRetryFailureMeta(stage, attempt, retryPolicy.maxAttempts, retryable, error);
              }
              break;
            }
            const delayMs = getRetryDelayMs(retryPolicy, attempt);
            const nextRetryAt = Date.now() + delayMs;
            this.emitUploadEvent(
              "xpressui:upload-retry", values, submitConfig,
              createUploadState(fileFieldNames, "uploading", Math.round((completed / total) * 100)),
              {
                strategy: "presigned", stage, fieldName, fileName: file.name,
                attempt, maxAttempts: retryPolicy.maxAttempts, nextRetryAt, delayMs,
                reason: getUploadErrorReason(error), status: error?.response?.status,
              },
            );
            await sleep(delayMs);
          }
        }

        if (lastError && typeof lastError === "object" && !(lastError as any).__uploadRetry) {
          (lastError as any).__uploadRetry = toUploadRetryFailureMeta(
            stage, Math.max(1, attemptsPerformed), retryPolicy.maxAttempts, shouldRetryUploadError(lastError), lastError,
          );
        }
        throw lastError;
      };

      const presignResult = await executeWithRetry("presign", async () => {
        const presignResponse = await fetch(presignUrl, {
          method: submitConfig.presignMethod || "POST",
          headers: { "Content-Type": "application/json", ...(submitConfig.presignHeaders || {}) },
          body: JSON.stringify(buildPresignPayload(fieldName, file)),
        });
        const contentType = presignResponse.headers.get("content-type") || "";
        const result = contentType.includes("application/json") ? await presignResponse.json() : await presignResponse.text();
        if (!presignResponse.ok) throw { response: presignResponse, result };
        return result;
      }).catch((error) => {
        const retryMeta = (error as any)?.__uploadRetry as TUploadRetryFailureMeta | undefined;
        this.emitUploadEvent(
          "xpressui:upload-error", values, submitConfig,
          createUploadState(fileFieldNames, "error", Math.round((completed / total) * 100)),
          {
            strategy: "presigned", stage: "presign", fieldName, fileName: file.name,
            attempt: retryMeta?.attempt, maxAttempts: retryMeta?.maxAttempts, retryable: retryMeta?.retryable,
            reason: retryMeta?.reason || getUploadErrorReason(error), status: retryMeta?.status ?? error?.response?.status,
          },
        );
        throw error;
      });

      const uploadDescriptor = normalizePresignedUploadDescriptor(presignResult, {
        uploadUrlKey: submitConfig.presignUploadUrlKey,
        fileUrlKey: submitConfig.presignFileUrlKey,
        resumeUrlKey: submitConfig.presignResumeUrlKey,
        sessionIdKey: submitConfig.presignSessionIdKey,
      });
      const uploadUrl = uploadDescriptor.uploadUrl;
      const fileUrl = uploadDescriptor.fileUrl;
      if (!uploadUrl || !fileUrl) throw new Error("upload_invalid_presign_response");

      const chunkSizeBytes = getChunkSizeBytes(submitConfig);
      const uploadMethod = submitConfig.uploadMethod || "PUT";
      const persistedResumeState = loadUploadResumeState(submitConfig, fieldName, file);

      const uploadFileInChunks = async (): Promise<void> => {
        if (chunkSizeBytes <= 0 || file.size <= chunkSizeBytes) {
          await uploadWithProgress(uploadUrl, uploadMethod, uploadDescriptor.headers || {}, file, (progress) => {
            const aggregateProgress = Math.round(((completed + progress / 100) / total) * 100);
            this.emitUploadEvent("xpressui:upload-progress", values, submitConfig, createUploadState(fileFieldNames, "uploading", aggregateProgress), { strategy: "presigned", fieldName });
          });
          return;
        }

        const chunkMethod = submitConfig.uploadChunkMethod || uploadMethod;
        const totalChunks = Math.ceil(file.size / chunkSizeBytes);
        let resumeState: TUploadResumeState = {
          version: 1,
          nextChunkIndex: Math.max(0, persistedResumeState?.nextChunkIndex || 0),
          updatedAt: Date.now(),
          ...(persistedResumeState?.sessionId || uploadDescriptor.sessionId ? { sessionId: persistedResumeState?.sessionId || uploadDescriptor.sessionId } : {}),
          ...(persistedResumeState?.resumeUrl || uploadDescriptor.resumeUrl ? { resumeUrl: persistedResumeState?.resumeUrl || uploadDescriptor.resumeUrl } : {}),
          uploadUrl, fileUrl,
          ...(persistedResumeState?.headers || uploadDescriptor.headers ? { headers: persistedResumeState?.headers || uploadDescriptor.headers } : {}),
        };

        if (resumeState.resumeUrl) {
          try {
            const resumeResponse = await fetch(resumeState.resumeUrl, { method: "GET", headers: { Accept: "application/json" } });
            const resumeContentType = resumeResponse.headers.get("content-type") || "";
            const resumeResult = resumeContentType.includes("application/json") ? await resumeResponse.json() : await resumeResponse.text();
            if (resumeResponse.ok) {
              const remoteDescriptor = normalizePresignedUploadDescriptor(resumeResult, {
                uploadUrlKey: submitConfig.presignUploadUrlKey,
                fileUrlKey: submitConfig.presignFileUrlKey,
                resumeUrlKey: submitConfig.presignResumeUrlKey,
                sessionIdKey: submitConfig.presignSessionIdKey,
              });
              const remoteChunkIndex = getResumeChunkIndexFromDescriptor(remoteDescriptor, chunkSizeBytes);
              if (typeof remoteChunkIndex === "number") resumeState.nextChunkIndex = remoteChunkIndex;
              if (remoteDescriptor.sessionId) resumeState.sessionId = remoteDescriptor.sessionId;
              if (remoteDescriptor.resumeUrl) resumeState.resumeUrl = remoteDescriptor.resumeUrl;
              if (remoteDescriptor.uploadUrl) resumeState.uploadUrl = remoteDescriptor.uploadUrl;
              if (remoteDescriptor.fileUrl) resumeState.fileUrl = remoteDescriptor.fileUrl;
              if (remoteDescriptor.headers) resumeState.headers = remoteDescriptor.headers;
              resumeState.updatedAt = Date.now();
              this.emitUploadEvent(
                "xpressui:upload-resume-state", values, submitConfig,
                createUploadState(fileFieldNames, "uploading", Math.round(((completed + Math.min(resumeState.nextChunkIndex, totalChunks) / totalChunks) / total) * 100)),
                {
                  strategy: "presigned", source: "remote", fieldName, fileName: file.name,
                  sessionId: resumeState.sessionId, resumeUrl: resumeState.resumeUrl,
                  resumeChunkIndex: resumeState.nextChunkIndex, chunkCount: totalChunks,
                  completed: remoteDescriptor.completed === true,
                },
              );
              if (remoteDescriptor.completed === true) {
                clearUploadResumeState(submitConfig, fieldName, file);
                return;
              }
            }
          } catch {
            this.emitUploadEvent(
              "xpressui:upload-resume-state", values, submitConfig,
              createUploadState(fileFieldNames, "uploading", Math.round((completed / total) * 100)),
              {
                strategy: "presigned", source: "remote", fieldName, fileName: file.name,
                sessionId: resumeState.sessionId, resumeUrl: resumeState.resumeUrl,
                resumeChunkIndex: resumeState.nextChunkIndex, degradedToLocal: true,
              },
            );
          }
        }

        if (!resumeState.resumeUrl && resumeState.nextChunkIndex > 0) {
          this.emitUploadEvent(
            "xpressui:upload-resume-state", values, submitConfig,
            createUploadState(fileFieldNames, "uploading", Math.round(((completed + Math.min(resumeState.nextChunkIndex, totalChunks) / totalChunks) / total) * 100)),
            {
              strategy: "presigned", source: "local", fieldName, fileName: file.name,
              sessionId: resumeState.sessionId, resumeUrl: resumeState.resumeUrl,
              resumeChunkIndex: resumeState.nextChunkIndex, chunkCount: totalChunks,
            },
          );
        }

        const resumeChunkIndex = resumeState.nextChunkIndex >= totalChunks ? 0 : resumeState.nextChunkIndex;
        if (resumeChunkIndex > 0) {
          this.emitUploadEvent(
            "xpressui:upload-progress", values, submitConfig,
            createUploadState(fileFieldNames, "uploading", Math.round(((completed + resumeChunkIndex / totalChunks) / total) * 100)),
            { strategy: "presigned", fieldName, resumed: true, resumeChunkIndex, chunkCount: totalChunks },
          );
        }

        for (let chunkIndex = resumeChunkIndex; chunkIndex < totalChunks; chunkIndex += 1) {
          const start = chunkIndex * chunkSizeBytes;
          const end = Math.min(file.size, start + chunkSizeBytes);
          const chunk = file.slice(start, end);
          await uploadWithProgress(
            resumeState.uploadUrl || uploadUrl, chunkMethod,
            { ...(resumeState.headers || {}), "Content-Type": file.type || "application/octet-stream", "Content-Range": `bytes ${start}-${end - 1}/${file.size}` },
            chunk,
            (progress) => {
              const chunkProgress = (chunkIndex + progress / 100) / totalChunks;
              const aggregateProgress = Math.round(((completed + chunkProgress) / total) * 100);
              this.emitUploadEvent(
                "xpressui:upload-progress", values, submitConfig,
                createUploadState(fileFieldNames, "uploading", aggregateProgress),
                { strategy: "presigned", fieldName, chunkIndex, chunkCount: totalChunks, sessionId: resumeState.sessionId },
              );
            },
          );
          resumeState = { ...resumeState, nextChunkIndex: chunkIndex + 1, updatedAt: Date.now() };
          saveUploadResumeState(submitConfig, fieldName, file, resumeState);
        }
        clearUploadResumeState(submitConfig, fieldName, file);
      };

      await executeWithRetry("upload", uploadFileInChunks).catch((error) => {
        const retryMeta = (error as any)?.__uploadRetry as TUploadRetryFailureMeta | undefined;
        this.emitUploadEvent(
          "xpressui:upload-error", values, submitConfig,
          createUploadState(fileFieldNames, "error", Math.round((completed / total) * 100)),
          {
            strategy: "presigned", stage: "upload", fieldName, fileName: file.name,
            attempt: retryMeta?.attempt, maxAttempts: retryMeta?.maxAttempts, retryable: retryMeta?.retryable,
            reason: retryMeta?.reason || getUploadErrorReason(error), status: retryMeta?.status ?? error?.response?.status,
          },
        );
        throw error;
      });

      completed += 1;
      const currentValue = transformedValues[fieldName];
      if (Array.isArray(currentValue)) {
        transformedValues[fieldName] = (currentValue as any[]).map((entry) => (entry === file ? fileUrl : entry));
      } else {
        transformedValues[fieldName] = fileUrl;
      }
    }

    this.emitUploadEvent("xpressui:upload-complete", transformedValues, submitConfig, createUploadState(fileFieldNames, "complete", 100), { strategy: "presigned" });
    return submitFormValues(transformedValues, submitConfig, fieldMap);
  }
}
