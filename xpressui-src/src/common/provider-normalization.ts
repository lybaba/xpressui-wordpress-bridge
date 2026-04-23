import {
  createNormalizedProviderResult,
  TFormProviderTransition,
  TNormalizedProviderError,
  TNormalizedProviderNextAction,
  TNormalizedProviderResult,
  TProviderResponseEnvelopeV2,
  PROVIDER_RESPONSE_CONTRACT_VERSION,
} from "./provider-contract";

export type {
  TFormProviderTransition,
  TNormalizedProviderNextAction,
  TNormalizedProviderResult,
  TProviderResponseEnvelopeV2,
};
export { createNormalizedProviderResult, PROVIDER_RESPONSE_CONTRACT_VERSION };

export const WORKFLOW_STATUS_TRANSITIONS = new Set([
  "draft", "submitting", "submitted", "pending_approval",
  "approved", "completed", "rejected", "error",
]);

export const PAYMENT_PROVIDER_ACTIONS = new Set(["payment", "payment-stripe"]);
export const APPROVAL_PROVIDER_ACTIONS = new Set(["approval-request", "approval-decision", "approval-comment"]);
export const IDENTITY_PROVIDER_ACTIONS = new Set([
  "identity-verification", "identity-verification-stripe", "identity-verification-webhook",
]);

export function isNonEmptyString(value: unknown): value is string {
  return typeof value === "string" && value.length > 0;
}

export function isRecord(value: unknown): value is Record<string, any> {
  return Boolean(value) && typeof value === "object" && !Array.isArray(value);
}

export function normalizeProviderTransition(result: any): TFormProviderTransition | null {
  if (!result || typeof result !== "object" || !result.transition || typeof result.transition !== "object") return null;
  const t = result.transition as Record<string, any>;
  if (t.type === "step" && (typeof t.target === "string" || Number.isFinite(t.target))) {
    return { type: "step", target: t.target as string | number };
  }
  if (t.type === "workflow" && typeof t.state === "string" && t.state) {
    return { type: "workflow", state: t.state };
  }
  return null;
}

export function normalizeProviderStatus(result: any): string | null {
  if (!result || typeof result !== "object") return null;
  if (typeof result.status === "string" && result.status) return result.status;
  if (result.data && typeof result.data === "object" && typeof result.data.status === "string" && result.data.status) {
    return result.data.status;
  }
  return null;
}

export function normalizeProviderStatusTransition(result: any): TFormProviderTransition | null {
  const status = normalizeProviderStatus(result);
  if (!status || !WORKFLOW_STATUS_TRANSITIONS.has(status)) return null;
  return { type: "workflow", state: status };
}

export function normalizeProviderMessages(result: any): string[] {
  if (!result || typeof result !== "object") return [];
  if (Array.isArray(result.messages)) {
    return result.messages.filter((e: unknown): e is string => typeof e === "string" && e.length > 0);
  }
  if (typeof result.message === "string" && result.message) return [result.message];
  return [];
}

export function toProviderErrorEntry(
  action: string | undefined,
  value: unknown,
  fallback: { code?: string; field?: string } = {},
): TNormalizedProviderError | null {
  if (typeof value === "string") {
    return {
      source: action || "provider",
      ...(fallback.code ? { code: fallback.code } : {}),
      ...(fallback.field ? { field: fallback.field } : {}),
      message: value,
    };
  }
  if (!isRecord(value)) return null;
  const message =
    typeof value.message === "string" ? value.message :
    typeof value.error === "string" ? value.error :
    typeof value.detail === "string" ? value.detail :
    typeof value.reason === "string" ? value.reason : undefined;
  const code =
    typeof value.code === "string" ? value.code :
    typeof value.type === "string" ? value.type : fallback.code;
  const field =
    typeof value.field === "string" ? value.field :
    typeof value.path === "string" ? value.path : fallback.field;
  return {
    source: action || "provider",
    ...(code ? { code } : {}),
    ...(field ? { field } : {}),
    ...(message ? { message } : {}),
    raw: value,
  };
}

export function normalizeProviderErrors(action: string | undefined, result: any): TNormalizedProviderError[] {
  if (!result || typeof result !== "object") return [];
  const out: TNormalizedProviderError[] = [];
  const push = (value: unknown, fallback: { code?: string; field?: string } = {}) => {
    const e = toProviderErrorEntry(action, value, fallback);
    if (e) out.push(e);
  };

  if (Array.isArray(result.errors)) {
    result.errors.forEach((e: unknown) => push(e));
  } else if (result.errors && typeof result.errors === "object") {
    Object.entries(result.errors).forEach(([field, value]) => {
      if (Array.isArray(value)) { value.forEach((e) => push(e, { code: "field_error", field })); return; }
      push(value, { code: "field_error", field });
    });
  }
  if (result.error !== undefined) push(result.error, { code: "provider_error" });
  if (typeof result.code === "string") {
    push({ code: result.code, message: typeof result.message === "string" ? result.message : undefined }, { code: result.code });
  }
  if (PAYMENT_PROVIDER_ACTIONS.has(action || "")) {
    if (typeof result.declineCode === "string") push({ code: result.declineCode, message: result.message }, { code: result.declineCode });
    if (typeof result.decline_code === "string") push({ code: result.decline_code, message: result.message }, { code: result.decline_code });
  } else if (APPROVAL_PROVIDER_ACTIONS.has(action || "")) {
    if (typeof result.reason === "string") push({ code: "approval_error", message: result.reason }, { code: "approval_error" });
  } else if (IDENTITY_PROVIDER_ACTIONS.has(action || "")) {
    if (Array.isArray(result.verificationErrors)) result.verificationErrors.forEach((e: unknown) => push(e, { code: "verification_error" }));
    if (isRecord(result.documentErrors)) Object.entries(result.documentErrors).forEach(([field, value]) => push(value, { code: "document_error", field }));
  }

  const seen = new Set<string>();
  return out.filter((entry) => {
    const key = JSON.stringify({ source: entry.source, code: entry.code, field: entry.field, message: entry.message });
    if (seen.has(key)) return false;
    seen.add(key);
    return true;
  });
}

export function normalizeProviderMessagesFromErrors(errors: TNormalizedProviderError[]): string[] {
  const messages: string[] = [];
  errors.forEach((entry) => {
    if (typeof entry.message === "string" && entry.message.length > 0) messages.push(entry.message);
  });
  return Array.from(new Set(messages));
}

export function normalizeProviderNextActions(result: any): TNormalizedProviderNextAction[] | undefined {
  const normalizeEntries = (entries: unknown[]): TNormalizedProviderNextAction[] =>
    entries.flatMap((entry) => {
      if (typeof entry === "string" && entry) return [{ type: entry }];
      if (entry && typeof entry === "object" && !Array.isArray(entry) && typeof (entry as any).type === "string") return [entry as TNormalizedProviderNextAction];
      return [];
    });
  if (!result || typeof result !== "object") return undefined;
  const explicit = (result as Record<string, any>).nextActions;
  if (Array.isArray(explicit)) return normalizeEntries(explicit);
  const nested = (result as Record<string, any>).data;
  if (!nested || typeof nested !== "object") return undefined;
  const nestedNext = (nested as Record<string, any>).nextActions;
  if (Array.isArray(nestedNext)) return normalizeEntries(nestedNext);
  return undefined;
}

export function normalizeProviderData(result: any): any {
  if (!result || typeof result !== "object") return result;
  if (result.data !== undefined) return result.data;
  const { status, transition, message, messages, error, errors, nextActions, ...rest } = result as Record<string, any>;
  void status; void transition; void message; void messages; void error; void errors; void nextActions;
  return Object.keys(rest).length > 0 ? rest : result;
}

export function validateProviderResponseEnvelopeV2(result: any): string[] {
  if (!result || typeof result !== "object") return ["provider response must be an object"];
  const errors: string[] = [];
  if ("status" in result && result.status !== null && typeof result.status !== "string") errors.push("status must be a string");
  if ("messages" in result && !Array.isArray(result.messages)) errors.push("messages must be an array");
  if (Array.isArray(result.messages) && result.messages.some((e: unknown) => !isNonEmptyString(e))) errors.push("messages entries must be non-empty strings");
  if ("errors" in result && !Array.isArray(result.errors)) errors.push("errors must be an array");
  if (
    Array.isArray(result.errors) &&
    result.errors.some((e: unknown) => {
      if (typeof e === "string") return e.length === 0;
      if (!e || typeof e !== "object" || Array.isArray(e)) return true;
      return "source" in (e as Record<string, any>) && typeof (e as Record<string, any>).source !== "string";
    })
  ) errors.push("errors entries must be strings or provider error objects");
  if ("nextActions" in result && !Array.isArray(result.nextActions)) errors.push("nextActions must be an array");
  if (
    Array.isArray(result.nextActions) &&
    result.nextActions.some((e: unknown) => !e || typeof e !== "object" || Array.isArray(e) || typeof (e as Record<string, any>).type !== "string" || !(e as Record<string, any>).type)
  ) errors.push("nextActions entries must be objects with a non-empty type");
  if ("transition" in result && result.transition !== null && typeof result.transition !== "object") errors.push("transition must be an object");
  if ("transition" in result && result.transition && !normalizeProviderTransition(result)) errors.push("transition must match {type:'step'|'workflow'} contract");
  if ("data" in result && result.data === undefined) errors.push("data cannot be undefined");
  return errors;
}

export function isProviderResponseEnvelopeV2(result: any): result is TProviderResponseEnvelopeV2 {
  return validateProviderResponseEnvelopeV2(result).length === 0;
}

export function buildNormalizedProviderResult(
  action: string | undefined,
  result: any,
  transition: TFormProviderTransition | null,
): TNormalizedProviderResult {
  const nextActions = normalizeProviderNextActions(result);
  const normalizedErrors = normalizeProviderErrors(action, result);
  const normalizedMessages = Array.from(new Set([
    ...normalizeProviderMessages(result),
    ...normalizeProviderMessagesFromErrors(normalizedErrors),
  ]));
  return createNormalizedProviderResult({
    status: normalizeProviderStatus(result),
    transition,
    messages: normalizedMessages,
    errors: normalizedErrors,
    ...(nextActions ? { nextActions } : {}),
    data: normalizeProviderData(result),
  });
}
