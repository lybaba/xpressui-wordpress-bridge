import { TFormProviderRequest, TFormSubmitRequest } from "./TFormConfig";
import {
  createNormalizedProviderResult,
  TFormProviderTransition,
  TNormalizedProviderError,
  TNormalizedProviderNextAction,
  TNormalizedProviderResult,
  TProviderResponseEnvelopeV2,
  PROVIDER_RESPONSE_CONTRACT_VERSION,
} from "./provider-contract";

export type TFormProviderDefinition = {
  createSubmitRequest?(
    provider: TFormProviderRequest,
  ): TFormSubmitRequest;
  buildPayload(
    values: Record<string, any>,
    submitConfig: TFormSubmitRequest,
  ): Record<string, any>;
  resolveTransition?(
    result: any,
    submitConfig: TFormSubmitRequest,
  ): TFormProviderTransition | null;
  providerConfigSchema?: TFormProviderConfigSchema;
  successEventName?: string;
  errorEventName?: string;
};

export type {
  TProviderResponseContractVersion,
  TFormProviderTransition,
  TNormalizedProviderNextAction,
  TNormalizedProviderResult,
  TProviderResponseEnvelopeV2,
} from "./provider-contract";
export {
  createNormalizedProviderResult,
  isNormalizedProviderResult,
  PROVIDER_RESPONSE_CONTRACT_VERSION,
} from "./provider-contract";

type TProviderConfigFieldSchema = {
  type: "string" | "number" | "boolean" | "array" | "object";
  minLength?: number;
  minimum?: number;
  enum?: readonly any[];
};

export type TFormProviderConfigSchema = {
  required?: string[];
  properties: Record<string, TProviderConfigFieldSchema>;
  additionalProperties?: boolean;
};

const providerRegistry = new Map<string, TFormProviderDefinition>();

const providerConfigSchemas = new Map<string, TFormProviderConfigSchema>([
  [
    "payment-stripe",
    {
      properties: {
        publishableKey: { type: "string", minLength: 1 },
        accountId: { type: "string", minLength: 1 },
      },
      additionalProperties: true,
    },
  ],
  [
    "booking-availability",
    {
      properties: {
        slotDurationMinutes: { type: "number", minimum: 1 },
        timezone: { type: "string", minLength: 1 },
      },
      additionalProperties: true,
    },
  ],
  [
    "identity-verification-stripe",
    {
      properties: {
        verificationFlow: {
          type: "string",
          enum: ["document", "id_number", "selfie"],
        },
      },
      additionalProperties: true,
    },
  ],
]);

export function registerProvider(
  action: string,
  definition: TFormProviderDefinition,
): void {
  providerRegistry.set(action, definition);
}

export function getProviderDefinition(
  action?: string,
): TFormProviderDefinition | null {
  if (!action) {
    return null;
  }

  return providerRegistry.get(action) || null;
}

export function buildProviderPayload(
  values: Record<string, any>,
  submitConfig: TFormSubmitRequest,
): Record<string, any> {
  const definition = getProviderDefinition(submitConfig.action);
  return definition ? definition.buildPayload(values, submitConfig) : values;
}

function getProviderConfigSchema(
  action: string,
  definition?: TFormProviderDefinition | null,
): TFormProviderConfigSchema | null {
  if (definition?.providerConfigSchema) {
    return definition.providerConfigSchema;
  }

  return providerConfigSchemas.get(action) || null;
}

function validateConfigField(
  providerType: string,
  fieldName: string,
  fieldValue: unknown,
  fieldSchema: TProviderConfigFieldSchema,
): void {
  const valueType =
    Array.isArray(fieldValue) ? "array" : fieldValue === null ? "null" : typeof fieldValue;

  if (valueType !== fieldSchema.type) {
    throw new Error(
      `Invalid provider config for "${providerType}": config.${fieldName} must be ${fieldSchema.type}.`,
    );
  }

  if (
    fieldSchema.type === "string" &&
    typeof fieldSchema.minLength === "number" &&
    typeof fieldValue === "string" &&
    fieldValue.length < fieldSchema.minLength
  ) {
    throw new Error(
      `Invalid provider config for "${providerType}": config.${fieldName} must contain at least ${fieldSchema.minLength} characters.`,
    );
  }

  if (
    fieldSchema.type === "number" &&
    typeof fieldSchema.minimum === "number" &&
    typeof fieldValue === "number" &&
    fieldValue < fieldSchema.minimum
  ) {
    throw new Error(
      `Invalid provider config for "${providerType}": config.${fieldName} must be >= ${fieldSchema.minimum}.`,
    );
  }

  if (fieldSchema.enum && !fieldSchema.enum.includes(fieldValue)) {
    throw new Error(
      `Invalid provider config for "${providerType}": config.${fieldName} is not an allowed value.`,
    );
  }
}

export function validateProviderRequest(
  provider: TFormProviderRequest,
  definition?: TFormProviderDefinition | null,
): void {
  if (!provider || typeof provider !== "object") {
    throw new Error("Invalid provider config: provider must be an object.");
  }

  if (typeof provider.type !== "string" || provider.type.length === 0) {
    throw new Error("Invalid provider config: provider.type must be a non-empty string.");
  }

  if (typeof provider.endpoint !== "string" || provider.endpoint.length === 0) {
    throw new Error(
      `Invalid provider config for "${provider.type}": provider.endpoint must be a non-empty string.`,
    );
  }

  if (provider.headers !== undefined) {
    if (!provider.headers || typeof provider.headers !== "object" || Array.isArray(provider.headers)) {
      throw new Error(
        `Invalid provider config for "${provider.type}": provider.headers must be an object.`,
      );
    }
    for (const [headerName, headerValue] of Object.entries(provider.headers)) {
      if (typeof headerValue !== "string") {
        throw new Error(
          `Invalid provider config for "${provider.type}": provider.headers.${headerName} must be a string.`,
        );
      }
    }
  }

  const schema = getProviderConfigSchema(provider.type, definition);
  if (!schema) {
    return;
  }

  if (provider.config === undefined) {
    if (schema.required?.length) {
      throw new Error(
        `Invalid provider config for "${provider.type}": provider.config is required.`,
      );
    }
    return;
  }

  if (!provider.config || typeof provider.config !== "object" || Array.isArray(provider.config)) {
    throw new Error(
      `Invalid provider config for "${provider.type}": provider.config must be an object.`,
    );
  }

  const config = provider.config as Record<string, unknown>;

  if (schema.required?.length) {
    for (const requiredKey of schema.required) {
      if (config[requiredKey] === undefined) {
        throw new Error(
          `Invalid provider config for "${provider.type}": missing required config.${requiredKey}.`,
        );
      }
    }
  }

  for (const [fieldName, fieldValue] of Object.entries(config)) {
    const fieldSchema = schema.properties[fieldName];
    if (!fieldSchema) {
      if (schema.additionalProperties === false) {
        throw new Error(
          `Invalid provider config for "${provider.type}": unsupported config.${fieldName}.`,
        );
      }
      continue;
    }
    validateConfigField(provider.type, fieldName, fieldValue, fieldSchema);
  }
}

export function createSubmitRequestFromProvider(
  provider: TFormProviderRequest,
): TFormSubmitRequest {
  const definition = getProviderDefinition(provider.type);
  validateProviderRequest(provider, definition);
  if (definition?.createSubmitRequest) {
    return definition.createSubmitRequest(provider);
  }

  return {
    endpoint: provider.endpoint,
    method: provider.method || "POST",
    headers: provider.headers,
    action: provider.type,
  };
}

export function getProviderSuccessEventName(action?: string): string | null {
  return getProviderDefinition(action)?.successEventName || null;
}

export function getProviderErrorEventName(action?: string): string | null {
  return getProviderDefinition(action)?.errorEventName || null;
}

function normalizeProviderTransition(result: any): TFormProviderTransition | null {
  if (!result || typeof result !== "object" || !result.transition || typeof result.transition !== "object") {
    return null;
  }

  const transition = result.transition as Record<string, any>;
  if (
    transition.type === "step" &&
    (typeof transition.target === "string" || Number.isFinite(transition.target))
  ) {
    return {
      type: "step",
      target: transition.target as string | number,
    };
  }

  if (transition.type === "workflow" && typeof transition.state === "string" && transition.state) {
    return {
      type: "workflow",
      state: transition.state,
    };
  }

  return null;
}

const WORKFLOW_STATUS_TRANSITIONS = new Set([
  "draft",
  "submitting",
  "submitted",
  "pending_approval",
  "approved",
  "completed",
  "rejected",
  "error",
]);

const PAYMENT_PROVIDER_ACTIONS = new Set(["payment", "payment-stripe"]);
const APPROVAL_PROVIDER_ACTIONS = new Set([
  "approval-request",
  "approval-decision",
  "approval-comment",
]);
const IDENTITY_PROVIDER_ACTIONS = new Set([
  "identity-verification",
  "identity-verification-stripe",
  "identity-verification-webhook",
]);

function normalizeProviderStatus(result: any): string | null {
  if (!result || typeof result !== "object") {
    return null;
  }

  if (typeof result.status === "string" && result.status) {
    return result.status;
  }

  if (
    result.data &&
    typeof result.data === "object" &&
    typeof result.data.status === "string" &&
    result.data.status
  ) {
    return result.data.status;
  }

  return null;
}

function normalizeProviderStatusTransition(result: any): TFormProviderTransition | null {
  const status = normalizeProviderStatus(result);
  if (!status || !WORKFLOW_STATUS_TRANSITIONS.has(status)) {
    return null;
  }

  return {
    type: "workflow",
    state: status,
  };
}

function normalizeProviderMessages(result: any): string[] {
  if (!result || typeof result !== "object") {
    return [];
  }

  if (Array.isArray(result.messages)) {
    return result.messages.filter(
      (entry: unknown): entry is string =>
        typeof entry === "string" && entry.length > 0,
    );
  }

  if (typeof result.message === "string" && result.message) {
    return [result.message];
  }

  return [];
}

function isNonEmptyString(value: unknown): value is string {
  return typeof value === "string" && value.length > 0;
}

function isRecord(value: unknown): value is Record<string, any> {
  return Boolean(value) && typeof value === "object" && !Array.isArray(value);
}

function toProviderErrorEntry(
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

  if (!isRecord(value)) {
    return null;
  }

  const message =
    typeof value.message === "string"
      ? value.message
      : typeof value.error === "string"
        ? value.error
        : typeof value.detail === "string"
          ? value.detail
          : typeof value.reason === "string"
            ? value.reason
            : undefined;
  const code =
    typeof value.code === "string"
      ? value.code
      : typeof value.type === "string"
        ? value.type
        : fallback.code;
  const field =
    typeof value.field === "string"
      ? value.field
      : typeof value.path === "string"
        ? value.path
        : fallback.field;

  return {
    source: action || "provider",
    ...(code ? { code } : {}),
    ...(field ? { field } : {}),
    ...(message ? { message } : {}),
    raw: value,
  };
}

function normalizeProviderErrors(
  action: string | undefined,
  result: any,
): TNormalizedProviderError[] {
  if (!result || typeof result !== "object") {
    return [];
  }

  const normalizedErrors: TNormalizedProviderError[] = [];
  const appendError = (value: unknown, fallback: { code?: string; field?: string } = {}) => {
    const entry = toProviderErrorEntry(action, value, fallback);
    if (entry) {
      normalizedErrors.push(entry);
    }
  };

  if (Array.isArray(result.errors)) {
    result.errors.forEach((entry: unknown) => appendError(entry));
  } else if (result.errors && typeof result.errors === "object") {
    Object.entries(result.errors).forEach(([field, value]) => {
      if (Array.isArray(value)) {
        value.forEach((entry) => appendError(entry, { code: "field_error", field }));
        return;
      }
      appendError(value, { code: "field_error", field });
    });
  }

  if (result.error !== undefined) {
    appendError(result.error, { code: "provider_error" });
  }

  if (typeof result.code === "string") {
    appendError(
      {
        code: result.code,
        message: typeof result.message === "string" ? result.message : undefined,
      },
      { code: result.code },
    );
  }

  if (PAYMENT_PROVIDER_ACTIONS.has(action || "")) {
    if (typeof result.declineCode === "string") {
      appendError({ code: result.declineCode, message: result.message }, { code: result.declineCode });
    }
    if (typeof result.decline_code === "string") {
      appendError({ code: result.decline_code, message: result.message }, { code: result.decline_code });
    }
  } else if (APPROVAL_PROVIDER_ACTIONS.has(action || "")) {
    if (typeof result.reason === "string") {
      appendError({ code: "approval_error", message: result.reason }, { code: "approval_error" });
    }
  } else if (IDENTITY_PROVIDER_ACTIONS.has(action || "")) {
    if (Array.isArray(result.verificationErrors)) {
      result.verificationErrors.forEach((entry: unknown) => appendError(entry, { code: "verification_error" }));
    }
    if (isRecord(result.documentErrors)) {
      Object.entries(result.documentErrors).forEach(([field, value]) => {
        appendError(value, { code: "document_error", field });
      });
    }
  }

  const seen = new Set<string>();
  return normalizedErrors.filter((entry) => {
    const key = JSON.stringify({
      source: entry.source,
      code: entry.code,
      field: entry.field,
      message: entry.message,
    });
    if (seen.has(key)) {
      return false;
    }
    seen.add(key);
    return true;
  });
}

function normalizeProviderMessagesFromErrors(errors: any[]): string[] {
  const messages: string[] = [];
  errors.forEach((entry: unknown) => {
    if (isRecord(entry) && typeof entry.message === "string" && entry.message.length > 0) {
      messages.push(entry.message);
    }
  });

  return Array.from(new Set(messages));
}

function normalizeProviderNextActions(result: any): TNormalizedProviderNextAction[] | undefined {
  const normalizeEntries = (entries: unknown[]): TNormalizedProviderNextAction[] => entries.flatMap((entry) => {
    if (typeof entry === "string" && entry) {
      return [{ type: entry }];
    }
    if (entry && typeof entry === "object" && !Array.isArray(entry) && typeof (entry as any).type === "string") {
      return [entry as TNormalizedProviderNextAction];
    }
    return [];
  });

  if (!result || typeof result !== "object") {
    return undefined;
  }

  const explicitNextActions = (result as Record<string, any>).nextActions;
  if (Array.isArray(explicitNextActions)) {
    return normalizeEntries(explicitNextActions);
  }

  const nestedData = (result as Record<string, any>).data;
  if (!nestedData || typeof nestedData !== "object") {
    return undefined;
  }
  const nestedNextActions = (nestedData as Record<string, any>).nextActions;
  if (Array.isArray(nestedNextActions)) {
    return normalizeEntries(nestedNextActions);
  }

  return undefined;
}

function normalizeProviderData(result: any): any {
  if (!result || typeof result !== "object") {
    return result;
  }

  if (result.data !== undefined) {
    return result.data;
  }

  const {
    status,
    transition,
    message,
    messages,
    error,
    errors,
    nextActions,
    ...rest
  } = result as Record<string, any>;
  void status;
  void transition;
  void message;
  void messages;
  void error;
  void errors;
  void nextActions;

  if (Object.keys(rest).length > 0) {
    return rest;
  }

  return result;
}

export function validateProviderResponseEnvelopeV2(result: any): string[] {
  if (!result || typeof result !== "object") {
    return ["provider response must be an object"];
  }

  const errors: string[] = [];
  if ("status" in result && result.status !== null && typeof result.status !== "string") {
    errors.push("status must be a string");
  }
  if ("messages" in result && !Array.isArray(result.messages)) {
    errors.push("messages must be an array");
  }
  if (Array.isArray(result.messages) && result.messages.some((entry: unknown) => !isNonEmptyString(entry))) {
    errors.push("messages entries must be non-empty strings");
  }
  if ("errors" in result && !Array.isArray(result.errors)) {
    errors.push("errors must be an array");
  }
  if (
    Array.isArray(result.errors) &&
    result.errors.some((entry: unknown) => {
      if (typeof entry === "string") {
        return entry.length === 0;
      }
      if (!entry || typeof entry !== "object" || Array.isArray(entry)) {
        return true;
      }
      return "source" in (entry as Record<string, any>) && typeof (entry as Record<string, any>).source !== "string";
    })
  ) {
    errors.push("errors entries must be strings or provider error objects");
  }
  if ("nextActions" in result && !Array.isArray(result.nextActions)) {
    errors.push("nextActions must be an array");
  }
  if (
    Array.isArray(result.nextActions) &&
    result.nextActions.some((entry: unknown) => !entry || typeof entry !== "object" || Array.isArray(entry) || typeof (entry as Record<string, any>).type !== "string" || !(entry as Record<string, any>).type)
  ) {
    errors.push("nextActions entries must be objects with a non-empty type");
  }
  if ("transition" in result && result.transition !== null && typeof result.transition !== "object") {
    errors.push("transition must be an object");
  }
  if ("transition" in result && result.transition && !normalizeProviderTransition(result)) {
    errors.push("transition must match {type:'step'|'workflow'} contract");
  }
  if ("data" in result && result.data === undefined) {
    errors.push("data cannot be undefined");
  }

  return errors;
}

export function isProviderResponseEnvelopeV2(result: any): result is TProviderResponseEnvelopeV2 {
  return validateProviderResponseEnvelopeV2(result).length === 0;
}

export function resolveProviderTransition(
  action: string | undefined,
  result: any,
  submitConfig: TFormSubmitRequest,
): TFormProviderTransition | null {
  const definition = getProviderDefinition(action);
  const explicitTransition = normalizeProviderTransition(result);
  if (explicitTransition) {
    return explicitTransition;
  }

  const providerTransition = definition?.resolveTransition?.(result, submitConfig);
  if (providerTransition) {
    return providerTransition;
  }

  return normalizeProviderStatusTransition(result);
}

export function normalizeProviderResult(
  action: string | undefined,
  result: any,
  submitConfig: TFormSubmitRequest,
): TNormalizedProviderResult {
  const nextActions = normalizeProviderNextActions(result);
  const normalizedErrors = normalizeProviderErrors(action, result);
  const explicitMessages = normalizeProviderMessages(result);
  const normalizedMessages = Array.from(
    new Set([
      ...explicitMessages,
      ...normalizeProviderMessagesFromErrors(normalizedErrors),
    ]),
  );
  return createNormalizedProviderResult({
    status: normalizeProviderStatus(result),
    transition: resolveProviderTransition(action, result, submitConfig),
    messages: normalizedMessages,
    errors: normalizedErrors,
    ...(nextActions ? { nextActions } : {}),
    data: normalizeProviderData(result),
  });
}

registerProvider("reservation", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "reservation",
    };
  },
  buildPayload(values) {
    return {
      action: "reservation",
      reservation: values,
    };
  },
  successEventName: "xpressui:reservation-success",
});

registerProvider("payment", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "payment",
    };
  },
  buildPayload(values) {
    return {
      action: "payment",
      payment: values,
    };
  },
  successEventName: "xpressui:payment-success",
  errorEventName: "xpressui:payment-error",
});

registerProvider("payment-stripe", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "payment-stripe",
    };
  },
  buildPayload(values) {
    return {
      action: "payment-stripe",
      payment: values,
    };
  },
  successEventName: "xpressui:payment-stripe-success",
  errorEventName: "xpressui:payment-stripe-error",
});

registerProvider("webhook", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "webhook",
    };
  },
  buildPayload(values) {
    return {
      action: "webhook",
      data: values,
    };
  },
  successEventName: "xpressui:webhook-success",
  errorEventName: "xpressui:webhook-error",
});

registerProvider("booking-availability", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "booking-availability",
    };
  },
  buildPayload(values) {
    return {
      action: "booking-availability",
      availability: values,
    };
  },
  successEventName: "xpressui:booking-availability-success",
  errorEventName: "xpressui:booking-availability-error",
});

registerProvider("calendar-booking", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "calendar-booking",
    };
  },
  buildPayload(values) {
    return {
      action: "calendar-booking",
      booking: values,
    };
  },
  successEventName: "xpressui:calendar-booking-success",
  errorEventName: "xpressui:calendar-booking-error",
});

registerProvider("calendar-cancel", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "calendar-cancel",
    };
  },
  buildPayload(values) {
    return {
      action: "calendar-cancel",
      cancellation: values,
    };
  },
  successEventName: "xpressui:calendar-cancel-success",
  errorEventName: "xpressui:calendar-cancel-error",
});

registerProvider("calendar-reschedule", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "calendar-reschedule",
    };
  },
  buildPayload(values) {
    return {
      action: "calendar-reschedule",
      reschedule: values,
    };
  },
  successEventName: "xpressui:calendar-reschedule-success",
  errorEventName: "xpressui:calendar-reschedule-error",
});

registerProvider("approval-request", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "approval-request",
    };
  },
  buildPayload(values) {
    return {
      action: "approval-request",
      approval: values,
    };
  },
  resolveTransition(result) {
    if (!result || typeof result !== "object") {
      return null;
    }

    const status = typeof result.status === "string" ? result.status : "";
    if (
      status === "pending_approval" ||
      status === "approved" ||
      status === "completed" ||
      status === "rejected"
    ) {
      return {
        type: "workflow",
        state: status,
      };
    }

    return null;
  },
  successEventName: "xpressui:approval-request-success",
  errorEventName: "xpressui:approval-request-error",
});

registerProvider("approval-decision", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "approval-decision",
    };
  },
  buildPayload(values) {
    return {
      action: "approval-decision",
      decision: values,
    };
  },
  resolveTransition(result) {
    if (!result || typeof result !== "object") {
      return null;
    }

    const status = typeof result.status === "string" ? result.status : "";
    if (status === "approved" || status === "completed" || status === "rejected") {
      return {
        type: "workflow",
        state: status,
      };
    }

    return null;
  },
  successEventName: "xpressui:approval-decision-success",
  errorEventName: "xpressui:approval-decision-error",
});

registerProvider("approval-comment", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "approval-comment",
    };
  },
  buildPayload(values) {
    return {
      action: "approval-comment",
      comment: values,
    };
  },
  successEventName: "xpressui:approval-comment-success",
  errorEventName: "xpressui:approval-comment-error",
});

registerProvider("email", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "email",
    };
  },
  buildPayload(values) {
    return {
      action: "email",
      email: values,
    };
  },
  successEventName: "xpressui:email-success",
  errorEventName: "xpressui:email-error",
});

registerProvider("crm", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "crm",
    };
  },
  buildPayload(values) {
    return {
      action: "crm",
      contact: values,
    };
  },
  successEventName: "xpressui:crm-success",
  errorEventName: "xpressui:crm-error",
});

registerProvider("identity-verification", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "identity-verification",
    };
  },
  buildPayload(values) {
    return {
      action: "identity-verification",
      identity: values,
    };
  },
  successEventName: "xpressui:identity-verification-success",
  errorEventName: "xpressui:identity-verification-error",
});

registerProvider("identity-verification-stripe", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "identity-verification-stripe",
    };
  },
  buildPayload(values) {
    return {
      action: "identity-verification-stripe",
      identity: values,
    };
  },
  successEventName: "xpressui:identity-verification-stripe-success",
  errorEventName: "xpressui:identity-verification-stripe-error",
});

registerProvider("identity-verification-webhook", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "identity-verification-webhook",
    };
  },
  buildPayload(values) {
    return {
      action: "identity-verification-webhook",
      identity: values,
    };
  },
  successEventName: "xpressui:identity-verification-webhook-success",
  errorEventName: "xpressui:identity-verification-webhook-error",
});

registerProvider("calendar-availability-hold", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "calendar-availability-hold",
    };
  },
  buildPayload(values) {
    return {
      action: "calendar-availability-hold",
      hold: values,
    };
  },
  resolveTransition(result) {
    if (!result || typeof result !== "object") {
      return null;
    }

    const status =
      typeof result.status === "string"
        ? result.status
        : typeof result.holdStatus === "string"
          ? result.holdStatus
          : "";
    if (
      status === "hold_pending" ||
      status === "hold_confirmed" ||
      status === "hold_expired"
    ) {
      return {
        type: "workflow",
        state: status,
      };
    }

    return null;
  },
  successEventName: "xpressui:calendar-availability-hold-success",
  errorEventName: "xpressui:calendar-availability-hold-error",
});

registerProvider("payment-capture", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "payment-capture",
    };
  },
  buildPayload(values) {
    return {
      action: "payment-capture",
      capture: values,
    };
  },
  resolveTransition(result) {
    if (!result || typeof result !== "object") {
      return null;
    }

    const status =
      typeof result.status === "string"
        ? result.status
        : typeof result.captureStatus === "string"
          ? result.captureStatus
          : "";
    if (status === "captured" || status === "succeeded" || status === "completed") {
      return {
        type: "workflow",
        state: "completed",
      };
    }

    if (status === "failed" || status === "declined" || status === "rejected") {
      return {
        type: "workflow",
        state: "error",
      };
    }

    return null;
  },
  successEventName: "xpressui:payment-capture-success",
  errorEventName: "xpressui:payment-capture-error",
});

registerProvider("identity-review", {
  createSubmitRequest(provider) {
    return {
      endpoint: provider.endpoint,
      method: provider.method || "POST",
      headers: provider.headers,
      action: "identity-review",
    };
  },
  buildPayload(values) {
    return {
      action: "identity-review",
      review: values,
    };
  },
  resolveTransition(result) {
    if (!result || typeof result !== "object") {
      return null;
    }

    const status =
      typeof result.status === "string"
        ? result.status
        : typeof result.reviewStatus === "string"
          ? result.reviewStatus
          : "";
    if (
      status === "pending_approval" ||
      status === "approved" ||
      status === "completed" ||
      status === "rejected"
    ) {
      return {
        type: "workflow",
        state: status,
      };
    }

    return null;
  },
  successEventName: "xpressui:identity-review-success",
  errorEventName: "xpressui:identity-review-error",
});
