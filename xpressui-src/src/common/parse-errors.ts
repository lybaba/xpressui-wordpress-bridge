import type { ErrorObject } from 'ajv';
import type TFieldConfig from './TFieldConfig';
import type { TFormValidationI18nConfig } from './TFormConfig';

export type TValidationError = {
  errorMessage: string;
  errorData?: any;
};
import {
    REQUIRED_FIELD_MSG,
    FIELD_VALUE_TOO_LONG,
    FIELD_VALUE_TOO_SHORT,
    resolveValidationMessage,
} from './validation-messages';

// Re-export for backward compatibility
export { REQUIRED_FIELD_MSG, FIELD_VALUE_TOO_LONG, FIELD_VALUE_TOO_SHORT };

// ─── Server-error parsing (key:limit format) ─────────────────────────────────

function getErrorLabel(
    errorKey: string,
    errorValue: string = "",
    limit: number = 0,
    i18n?: TFormValidationI18nConfig,
): string {
    switch (errorKey) {
        case 'required':
            return resolveValidationMessage("required", REQUIRED_FIELD_MSG, { limit }, i18n);
        case 'minLength':
            return resolveValidationMessage("minLength", FIELD_VALUE_TOO_SHORT, { limit }, i18n);
        case 'maxLength':
            return resolveValidationMessage("maxLength", FIELD_VALUE_TOO_LONG, { limit }, i18n);
        default:
            return errorValue || errorKey;
    }
}

export function parseServerErrors(
    errors: Record<string, string>,
    i18n?: TFormValidationI18nConfig,
): Record<string, string> {
    const res: Record<string, string> = {};
    Object.entries(errors).forEach((value: [string, string]) => {
        const fieldName = value[0];
        const errorTab = value[1].split(':');
        const errorKey = errorTab[0];
        const limit = errorTab.length > 1 ? Number(errorTab[1]) : 0;
        res[fieldName] = getErrorLabel(errorKey, "", limit, i18n);
    });
    return res;
}

// ─── AJV error parsing ────────────────────────────────────────────────────────

export default function parseErrors(
    errors: null | undefined | ErrorObject[],
    fieldMap?: Record<string, TFieldConfig>,
    i18n?: TFormValidationI18nConfig,
): Record<string, TValidationError> {
    const res: Record<string, TValidationError> = {};

    if (!errors) {
        return res;
    }

    errors.forEach(error => {
        let fieldName: string | null = null;
        let errorMessage: string | null = null;

        const limit =
            typeof (error.params as any)?.limit === "number"
                ? Number((error.params as any).limit)
                : 0;

        const getFriendlyAjvMessage = (resolvedFieldName?: string | null): string => {
            switch (error.keyword) {
                case "required":
                    return resolveValidationMessage("required", REQUIRED_FIELD_MSG, { limit }, i18n, resolvedFieldName, error);
                case "minLength":
                    return resolveValidationMessage("minLength", FIELD_VALUE_TOO_SHORT, { limit }, i18n, resolvedFieldName, error);
                case "maxLength":
                    return resolveValidationMessage("maxLength", FIELD_VALUE_TOO_LONG, { limit }, i18n, resolvedFieldName, error);
                case "minItems":
                    return resolveValidationMessage("minItems", `Not enough selections (minimum: ${limit}).`, { limit }, i18n, resolvedFieldName, error);
                case "maxItems":
                    return resolveValidationMessage("maxItems", `Too many selections (maximum: ${limit}).`, { limit }, i18n, resolvedFieldName, error);
                case "enum":
                    return resolveValidationMessage("enum", "Please select a valid option.", { limit }, i18n, resolvedFieldName, error);
                case "minimum":
                    return resolveValidationMessage("minimum", `Value must be greater than or equal to ${limit}.`, { limit }, i18n, resolvedFieldName, error);
                case "maximum":
                    return resolveValidationMessage("maximum", `Value must be less than or equal to ${limit}.`, { limit }, i18n, resolvedFieldName, error);
                case "formatMinimum": {
                    const bound = String((error.params as any)?.limit || (error.params as any)?.comparison || "");
                    return resolveValidationMessage("formatMinimum", `Value must be on or after ${bound}.`, { limit: bound }, i18n, resolvedFieldName, error);
                }
                case "formatMaximum": {
                    const bound = String((error.params as any)?.limit || (error.params as any)?.comparison || "");
                    return resolveValidationMessage("formatMaximum", `Value must be on or before ${bound}.`, { limit: bound }, i18n, resolvedFieldName, error);
                }
                case "format": {
                    const format = String((error.params as any)?.format || "").toLowerCase();
                    switch (format) {
                        case "email":
                            return resolveValidationMessage("format.email", "Please enter a valid email address.", { format, limit }, i18n, resolvedFieldName, error);
                        case "date":
                            return resolveValidationMessage("format.date", "Please enter a valid date (YYYY-MM-DD).", { format, limit }, i18n, resolvedFieldName, error);
                        case "time":
                            return resolveValidationMessage("format.time", "Please enter a valid time (HH:mm).", { format, limit }, i18n, resolvedFieldName, error);
                        case "date-time":
                            return resolveValidationMessage("format.date-time", "Please enter a valid date and time.", { format, limit }, i18n, resolvedFieldName, error);
                        case "uri":
                        case "url":
                            return resolveValidationMessage(`format.${format}`, "Please enter a valid URL.", { format, limit }, i18n, resolvedFieldName, error);
                        default:
                            return resolveValidationMessage("invalid.format", error.message || "Invalid format.", { format, limit }, i18n, resolvedFieldName, error);
                    }
                }
                case "type": {
                    const expectedType = String((error.params as any)?.type || "").toLowerCase();
                    switch (expectedType) {
                        case "number":
                        case "integer":
                            return resolveValidationMessage(`type.${expectedType}`, "Please enter a valid number.", { type: expectedType, limit }, i18n, resolvedFieldName, error);
                        case "boolean":
                            return resolveValidationMessage("type.boolean", "Please choose a valid yes/no value.", { type: expectedType, limit }, i18n, resolvedFieldName, error);
                        case "array":
                            return resolveValidationMessage("type.array", "Please provide a valid list value.", { type: expectedType, limit }, i18n, resolvedFieldName, error);
                        case "string":
                            return resolveValidationMessage("type.string", "Please enter a valid text value.", { type: expectedType, limit }, i18n, resolvedFieldName, error);
                        default:
                            return resolveValidationMessage("invalid.type", error.message || "Invalid value type.", { type: expectedType, limit }, i18n, resolvedFieldName, error);
                    }
                }
                default:
                    return resolveValidationMessage("invalid.value", error.message || "Invalid value.", { limit }, i18n, resolvedFieldName, error);
            }
        };

        if (error.keyword === 'required') {
            fieldName = error.params.missingProperty;
            errorMessage = getFriendlyAjvMessage(fieldName);
        } else {
            fieldName = error.instancePath.slice(1).replace(/\//g, ".");
            errorMessage = getFriendlyAjvMessage(fieldName);
        }

        if (fieldName && errorMessage) {
            const fieldConfig = fieldMap && fieldMap.hasOwnProperty(fieldName) ? fieldMap[fieldName] : null;
            errorMessage = fieldConfig ? fieldConfig.errorMsg || errorMessage : errorMessage;
            res[fieldName] = { errorMessage, errorData: error };
        }
    });

    return res;
}
