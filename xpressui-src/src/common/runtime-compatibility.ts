import TFormConfig from "./TFormConfig";

export type TXPressUIRuntimeTier = "standard" | "light";

export const LIGHT_UNSUPPORTED_COMPONENTS = new Set([
  "product-list",
  "select-product",
  "select-image",
  "select-multiple",
  "quiz",
  "section-select",
  "qr-scan",
  "document-scan",
]);

function getNormalizedFieldType(fieldType: unknown): string {
  return typeof fieldType === "string" ? fieldType.trim().toLowerCase() : "";
}

export function getRuntimeCompatibilityIssues(
  config: TFormConfig,
  runtimeTier: TXPressUIRuntimeTier,
): string[] {
  if (runtimeTier !== "light") {
    return [];
  }

  const unsupported = new Set<string>();
  const sections = config.sections || {};
  Object.values(sections).forEach((sectionFields) => {
    if (!Array.isArray(sectionFields)) {
      return;
    }

    sectionFields.forEach((fieldConfig) => {
      const normalizedType = getNormalizedFieldType((fieldConfig as Record<string, unknown>).type);
      if (normalizedType && LIGHT_UNSUPPORTED_COMPONENTS.has(normalizedType)) {
        unsupported.add(normalizedType);
      }
    });
  });

  return Array.from(unsupported).sort();
}

export function assertRuntimeCompatibility(
  config: TFormConfig,
  runtimeTier: TXPressUIRuntimeTier,
): TFormConfig {
  const issues = getRuntimeCompatibilityIssues(config, runtimeTier);
  if (!issues.length) {
    return config;
  }

  throw new Error(
    `Unsupported XPressUI ${runtimeTier} runtime components: ${issues.join(", ")}`,
  );
}
