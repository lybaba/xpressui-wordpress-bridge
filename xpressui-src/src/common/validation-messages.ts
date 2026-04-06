import type { ErrorObject } from 'ajv';
import type { TFormValidationI18nConfig } from './TFormConfig';

// ─── Exported constants (also re-exported from parse-errors for back-compat) ─

export const REQUIRED_FIELD_MSG = "This field is required.";
export const FIELD_VALUE_TOO_LONG = "Too many characters (maximum: %s).";
export const FIELD_VALUE_TOO_SHORT = "Not enough characters (minimum: %s).";

// ─── Default message catalog ──────────────────────────────────────────────────

const DEFAULT_VALIDATION_MESSAGES: Record<string, Record<string, string>> = {
    en: {
        required: REQUIRED_FIELD_MSG,
        minLength: "Not enough characters (minimum: {limit}).",
        maxLength: "Too many characters (maximum: {limit}).",
        minItems: "Not enough selections (minimum: {limit}).",
        maxItems: "Too many selections (maximum: {limit}).",
        enum: "Please select a valid option.",
        minimum: "Value must be greater than or equal to {limit}.",
        maximum: "Value must be less than or equal to {limit}.",
        "format.email": "Please enter a valid email address.",
        "format.date": "Please enter a valid date (YYYY-MM-DD).",
        "format.time": "Please enter a valid time (HH:mm).",
        "format.date-time": "Please enter a valid date and time.",
        "format.uri": "Please enter a valid URL.",
        "format.url": "Please enter a valid URL.",
        "invalid.format": "Invalid format.",
        "type.number": "Please enter a valid number.",
        "type.integer": "Please enter a valid number.",
        "type.boolean": "Please choose a valid yes/no value.",
        "type.array": "Please provide a valid list value.",
        "type.string": "Please enter a valid text value.",
        "invalid.type": "Invalid value type.",
        "invalid.value": "Invalid value.",
    },
    fr: {
        required: "Ce champ est obligatoire.",
        minLength: "Pas assez de caracteres (minimum : {limit}).",
        maxLength: "Trop de caracteres (maximum : {limit}).",
        minItems: "Pas assez de selections (minimum : {limit}).",
        maxItems: "Trop de selections (maximum : {limit}).",
        enum: "Veuillez selectionner une option valide.",
        minimum: "La valeur doit etre superieure ou egale a {limit}.",
        maximum: "La valeur doit etre inferieure ou egale a {limit}.",
        "format.email": "Veuillez saisir une adresse e-mail valide.",
        "format.date": "Veuillez saisir une date valide (YYYY-MM-DD).",
        "format.time": "Veuillez saisir une heure valide (HH:mm).",
        "format.date-time": "Veuillez saisir une date et une heure valides.",
        "format.uri": "Veuillez saisir une URL valide.",
        "format.url": "Veuillez saisir une URL valide.",
        "invalid.format": "Format invalide.",
        "type.number": "Veuillez saisir un nombre valide.",
        "type.integer": "Veuillez saisir un nombre entier valide.",
        "type.boolean": "Veuillez choisir une valeur oui/non valide.",
        "type.array": "Veuillez fournir une liste valide.",
        "type.string": "Veuillez saisir un texte valide.",
        "invalid.type": "Type de valeur invalide.",
        "invalid.value": "Valeur invalide.",
    },
};

// ─── Message resolution helpers ───────────────────────────────────────────────

function formatErrorTemplate(template: string, limit: number = 0): string {
    if (!template.includes("%s")) {
        return template;
    }
    return template.replace("%s", String(limit));
}

function interpolateMessage(template: string, values: Record<string, any>): string {
    const withNamedTokens = template.replace(/\{(\w+)\}/g, (_, token: string) => {
        if (!Object.prototype.hasOwnProperty.call(values, token)) {
            return '';
        }
        const value = values[token];
        return value === undefined || value === null ? '' : String(value);
    });

    if (!withNamedTokens.includes("%s")) {
        return withNamedTokens;
    }

    const limit = Number(values.limit ?? 0);
    return formatErrorTemplate(withNamedTokens, Number.isFinite(limit) ? limit : 0);
}

export function normalizeLocale(locale?: string): string {
    return String(locale || "en").trim().toLowerCase() || "en";
}

function resolveLocaleCandidates(locale: string): string[] {
    const normalized = normalizeLocale(locale);
    const baseLocale = normalized.includes("-") ? normalized.split("-")[0] : normalized;
    return normalized === baseLocale ? [normalized] : [normalized, baseLocale];
}

function getMessageFromCatalog(
    messages: Record<string, Record<string, string>> | undefined,
    locale: string,
    key: string,
): string | undefined {
    if (!messages) {
        return undefined;
    }
    const localeCandidates = resolveLocaleCandidates(locale);
    for (const candidate of localeCandidates) {
        const catalog = messages[candidate];
        if (catalog && Object.prototype.hasOwnProperty.call(catalog, key)) {
            return catalog[key];
        }
    }
    return undefined;
}

/**
 * Resolves a validation message key to a localized, interpolated string.
 * Respects custom message catalogs and a custom resolver hook from `i18n`.
 */
export function resolveValidationMessage(
    key: string,
    fallbackMessage: string,
    values: Record<string, any>,
    i18n?: TFormValidationI18nConfig,
    fieldName?: string | null,
    error?: ErrorObject,
): string {
    const locale = normalizeLocale(i18n?.locale);
    const fallbackLocale = normalizeLocale(i18n?.fallbackLocale || "en");

    const defaultTemplate =
        getMessageFromCatalog(DEFAULT_VALIDATION_MESSAGES, locale, key)
        || getMessageFromCatalog(DEFAULT_VALIDATION_MESSAGES, fallbackLocale, key)
        || getMessageFromCatalog(DEFAULT_VALIDATION_MESSAGES, "en", key)
        || fallbackMessage;

    const customTemplate =
        getMessageFromCatalog(i18n?.messages, locale, key)
        || getMessageFromCatalog(i18n?.messages, fallbackLocale, key);

    const candidateTemplate = customTemplate || defaultTemplate;
    const defaultMessage = interpolateMessage(candidateTemplate, values);

    const resolvedMessage = i18n?.resolveMessage?.({
        key,
        locale,
        fallbackLocale,
        defaultMessage,
        values,
        fieldName,
        error,
    });

    if (typeof resolvedMessage === "string" && resolvedMessage.trim()) {
        return resolvedMessage;
    }

    return defaultMessage;
}
