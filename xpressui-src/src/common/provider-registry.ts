import { TFormProviderRequest, TFormSubmitRequest } from "./TFormConfig";
import { TFormProviderTransition, TNormalizedProviderResult } from "./provider-contract";
import {
  normalizeProviderTransition,
  normalizeProviderStatusTransition,
  buildNormalizedProviderResult,
  validateProviderResponseEnvelopeV2,
  isProviderResponseEnvelopeV2,
} from "./provider-normalization";
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
export { validateProviderResponseEnvelopeV2, isProviderResponseEnvelopeV2 };

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
  return buildNormalizedProviderResult(
    action,
    result,
    resolveProviderTransition(action, result, submitConfig),
  );
}
