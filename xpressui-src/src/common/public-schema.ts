import { ErrorObject } from "ajv";
import TFormConfig from "./TFormConfig";
import { ajv } from "./frontend";

export const PUBLIC_FORM_SCHEMA_VERSION = 1;

const PUBLIC_FORM_SCHEMA = {
  type: "object",
  required: ["version", "id", "uid", "type", "name", "title", "sections"],
  additionalProperties: true,
  properties: {
    version: { type: "integer", minimum: 1 },
    id: { type: "string", minLength: 1 },
    uid: { type: "string", minLength: 1 },
    type: { type: "string", minLength: 1 },
    name: { type: "string", minLength: 1 },
    title: { type: "string", minLength: 1 },
    timestamp: { type: "integer" },
    mode: {
      type: "string",
      enum: ["form", "view", "hybrid", "form-multi-step", "view-multi-step"],
    },
    submit: {
      type: "object",
      additionalProperties: true,
      properties: {
        includeSettingFields: { type: "boolean" },
        endpoint: { type: "string", minLength: 1 },
        baseUrl: { type: "string", minLength: 1 },
        providerResponseContract: { type: "string", enum: ["compat", "warn-v2", "strict-v2"] },
        method: { type: "string", enum: ["GET", "POST", "PUT", "PATCH", "DELETE"] },
        mode: { type: "string", enum: ["json", "form-data"] },
        action: { type: "string", minLength: 1 },
        presignEndpoint: { type: "string", minLength: 1 },
        presignMethod: { type: "string", enum: ["POST", "PUT", "PATCH"] },
        presignUploadUrlKey: { type: "string", minLength: 1 },
        presignFileUrlKey: { type: "string", minLength: 1 },
        presignResumeUrlKey: { type: "string", minLength: 1 },
        presignSessionIdKey: { type: "string", minLength: 1 },
        uploadRetryMaxAttempts: { type: "integer", minimum: 1 },
        uploadRetryBaseDelayMs: { type: "integer", minimum: 0 },
        uploadRetryMaxDelayMs: { type: "integer", minimum: 0 },
        uploadRetryJitter: { type: "boolean" },
        uploadChunkMethod: { type: "string", enum: ["PUT", "PATCH", "POST"] },
        uploadChunkSizeMb: { type: "number", exclusiveMinimum: 0 },
        uploadResumeKey: { type: "string", minLength: 1 },
        uploadResumeEnabled: { type: "boolean" },
        settingFieldAllowlist: {
          type: "array",
          items: { type: "string", minLength: 1 },
        },
      },
    },
    storage: {
      type: "object",
      additionalProperties: true,
      properties: {
        mode: { type: "string", enum: ["none", "draft", "queue", "draft-and-queue"] },
        adapter: { type: "string", enum: ["local-storage", "indexeddb"] },
        key: { type: "string", minLength: 1 },
        autoSaveMs: { type: "integer", minimum: 0 },
        resumeEndpoint: { type: "string", minLength: 1 },
        shareCodeEndpoint: { type: "string", minLength: 1 },
        resumeTokenTtlDays: { type: "number", minimum: 0 },
        retentionDays: { type: "number", minimum: 0 },
        retentionDraftDays: { type: "number", minimum: 0 },
        retentionQueueDays: { type: "number", minimum: 0 },
        retentionDeadLetterDays: { type: "number", minimum: 0 },
        shareCodeClaimThrottleMs: { type: "number", minimum: 0 },
        shareCodeClaimMaxAttempts: { type: "number", minimum: 1 },
        shareCodeClaimWindowMs: { type: "number", minimum: 0 },
        shareCodeClaimBlockMs: { type: "number", minimum: 0 },
        resumeTokenSignatureVersion: { type: "string", minLength: 1 },
      },
    },
    stepUi: {
      type: "object",
      additionalProperties: false,
      properties: {
        progressPlacement: { type: "string", enum: ["top", "bottom", "hidden"] },
        navigationPlacement: { type: "string", enum: ["top", "bottom"] },
        backBehavior: { type: "string", enum: ["always", "never", "hide-after-advance"] },
      },
    },
    rules: {
      type: "array",
      items: {
        type: "object",
        required: ["conditions", "actions"],
        additionalProperties: true,
        properties: {
          id: { type: "string", minLength: 1 },
          logic: { type: "string", enum: ["AND", "OR"] },
          conditions: {
            type: "array",
            minItems: 1,
            items: {
              type: "object",
              required: ["field"],
              additionalProperties: true,
              properties: {
                field: { type: "string", minLength: 1 },
                operator: {
                  type: "string",
                  enum: ["equals", "not_equals", "contains", "in", "gt", "lt", "exists", "empty"],
                },
              },
            },
          },
          actions: {
            type: "array",
            minItems: 1,
            items: {
              type: "object",
              required: ["type"],
              additionalProperties: true,
              properties: {
                type: {
                  type: "string",
                  enum: [
                    "show",
                    "hide",
                    "enable",
                    "disable",
                    "clear-value",
                    "set-value",
                    "fetch-options",
                    "set-error",
                    "lock-submit",
                    "emit-event",
                    "show-section",
                    "hide-section",
                    "set-required",
                    "clear-required",
                  ],
                },
                field: { type: "string" },
                message: { type: "string" },
                sourceField: { type: "string", minLength: 1 },
                template: { type: "string" },
                transform: {
                  type: "string",
                  enum: ["copy", "trim", "lowercase", "uppercase", "slugify"],
                },
              },
            },
          },
        },
      },
    },
    validation: {
      type: "object",
      additionalProperties: true,
      properties: {
        i18n: {
          type: "object",
          additionalProperties: true,
          properties: {
            locale: { type: "string", minLength: 1 },
            fallbackLocale: { type: "string", minLength: 1 },
            messages: {
              type: "object",
              additionalProperties: {
                type: "object",
                additionalProperties: {
                  type: "string",
                },
              },
            },
          },
        },
      },
    },
    lockedFields: {
      type: "array",
      items: { type: "string", minLength: 1 },
    },
    sections: {
      type: "object",
      minProperties: 1,
      additionalProperties: {
        type: "array",
        items: {
          type: "object",
          required: ["type", "name", "label"],
          additionalProperties: true,
          properties: {
            type: { type: "string", minLength: 1 },
            name: { type: "string", minLength: 1 },
            label: { type: "string", minLength: 1 },
            includeInSubmit: { type: "boolean" },
            documentExcludeFromSubmit: { type: "boolean" },
            documentExcludeFromDebug: { type: "boolean" },
            documentMaskPaths: {
              type: "array",
              items: { type: "string", minLength: 1 },
            },
            documentDebugMaskPaths: {
              type: "array",
              items: { type: "string", minLength: 1 },
            },
          },
        },
      },
    },
  },
} as const;

const validateSchema = ajv.compile(PUBLIC_FORM_SCHEMA);

function cloneObject<T>(value: T): T {
  if (Array.isArray(value)) {
    return value.map((entry) => cloneObject(entry)) as unknown as T;
  }

  if (value && typeof value === "object") {
    const prototype = Object.getPrototypeOf(value);
    if (prototype === Object.prototype || prototype === null) {
      const result: Record<string, unknown> = {};
      Object.entries(value as Record<string, unknown>).forEach(([key, entry]) => {
        result[key] = cloneObject(entry);
      });
      return result as T;
    }
  }

  return value;
}

export function migratePublicFormConfig(input: Record<string, any>): TFormConfig {
  const config = cloneObject(input) as Record<string, any>;

  const normalizeFieldType = (fieldType: unknown) => {
    if (fieldType === "image-gallery" || fieldType === "imagegallery" || fieldType === "selectimage") {
      return "select-image";
    }
    if (fieldType === "selectproduct") {
      return "select-product";
    }

    return fieldType;
  };

  const normalizeSections = (sections: unknown) => {
    if (!sections || typeof sections !== "object") {
      return sections;
    }

    return Object.fromEntries(
      Object.entries(sections as Record<string, any>).map(([sectionName, sectionValue]) => {
        if (sectionName === "custom" || !Array.isArray(sectionValue)) {
          return [sectionName, sectionValue];
        }

        return [
          sectionName,
          sectionValue.map((entry) => (
            entry && typeof entry === "object" && "type" in entry
              ? { ...entry, type: normalizeFieldType((entry as Record<string, any>).type) }
              : entry
          )),
        ];
      }),
    );
  };

  if (!config.version) {
    config.version = PUBLIC_FORM_SCHEMA_VERSION;
  }

  if (!config.title && typeof config.label === "string" && config.label) {
    config.title = config.label;
  }

  if (config.sections) {
    config.sections = normalizeSections(config.sections);
  }

  if (Array.isArray(config.lockedFields) && config.lockedFields.length > 0) {
    const allowed = new Set<string>(config.lockedFields as string[]);
    const filtered: Record<string, unknown[]> = {};
    for (const [sectionKey, sectionFields] of Object.entries(
      config.sections as Record<string, unknown[]>,
    )) {
      if (sectionKey === 'custom' || sectionKey === 'btngroup') {
        filtered[sectionKey] = sectionFields;
      } else {
        filtered[sectionKey] = (sectionFields as Array<Record<string, unknown>>).filter(
          (field) => typeof field.name !== 'string' || allowed.has(field.name),
        );
      }
    }
    config.sections = filtered;
  }

  return config as TFormConfig;
}

export function getPublicFormSchemaErrors(): ErrorObject[] | null | undefined {
  return validateSchema.errors;
}

export function validatePublicFormConfig(input: Record<string, any>): TFormConfig {
  const migrated = migratePublicFormConfig(input);
  const valid = validateSchema(migrated);

  if (!valid) {
    const details = (validateSchema.errors || [])
      .map((error) => `${error.instancePath || "/"} ${error.message}`)
      .join("; ");
    throw new Error(`Invalid public form config: ${details}`);
  }

  return migrated;
}
