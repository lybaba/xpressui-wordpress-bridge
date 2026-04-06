import {
    CHECKBOX_TYPE,
    CHECKBOXES_TYPE,
    DATETIME_TYPE,
    DATE_TYPE,
    EMAIL_TYPE,
    SELECT_MULTIPLE_TYPE,
    NUMBER_TYPE,
    POSITIVE_INTEGER_TYPE,
    PRICE_TYPE,
    PRODUCT_LIST_TYPE,
    IMAGE_GALLERY_TYPE,
    QUIZ_TYPE,
    SETTING_TYPE,
    SELECT_ONE_TYPE,
    SLUG_TYPE,
    SWITCH_TYPE,
    TAX_TYPE,
    TIME_TYPE,
    RADIO_BUTTONS_TYPE,
    URL_TYPE,
    isFileFieldType,
} from "./field";
import type TFieldConfig from "./TFieldConfig";
import type TFormConfig from "./TFormConfig";
import { FORM_MULTI_STEP_MODE } from "./TFormConfig";
import type TChoice from "./TChoice";
import type TSchema from "./TSchema";
import { shouldRenderField, getCustomSectionList, getSectionByIndex } from "./post";
import { isEmpty } from 'lodash';

// ─── Private helpers ──────────────────────────────────────────────────────────

const getFiniteNumber = (value: unknown): number | undefined => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : undefined;
};

const getNonEmptyString = (value: unknown): string | undefined => {
    const normalized = String(value ?? "").trim();
    return normalized ? normalized : undefined;
};

// ─── Field → AJV schema ───────────────────────────────────────────────────────

/**
 * Converts a single field config to its AJV JSON-schema fragment.
 * Returns null for fields that should not produce a schema constraint.
 */
function toAjvFieldType(fieldConfig: TFieldConfig): object | null {
    const res: any = {};
    const minNumber = getFiniteNumber(fieldConfig.min);
    const maxNumber = getFiniteNumber(fieldConfig.max);
    const minString = getNonEmptyString(fieldConfig.min);
    const maxString = getNonEmptyString(fieldConfig.max);

    if (isFileFieldType(fieldConfig.type)) {
        res.anyOf = [
            { type: "object" },
            { type: "array", ...(fieldConfig.required ? { minItems: 1 } : {}) },
            { type: "string", ...(fieldConfig.required ? { minLength: 1 } : {}) },
        ];
        return res;
    }

    switch (fieldConfig.type) {
        case NUMBER_TYPE:
            res.type = "number";
            if (typeof minNumber === "number") res.minimum = minNumber;
            if (typeof maxNumber === "number") res.maximum = maxNumber;
            break;

        case PRICE_TYPE:
            res.type = "number";
            res.minimum = typeof minNumber === "number" ? minNumber : 0;
            if (typeof maxNumber === "number") res.maximum = maxNumber;
            break;

        case TAX_TYPE:
            res.type = "number";
            res.minimum = typeof minNumber === "number" ? Math.max(0, minNumber) : 0;
            res.maximum = typeof maxNumber === "number" ? Math.min(1, maxNumber) : 1;
            break;

        case POSITIVE_INTEGER_TYPE:
            res.type = "integer";
            res.minimum = typeof minNumber === "number" ? minNumber : 0;
            if (typeof maxNumber === "number") res.maximum = maxNumber;
            break;

        case CHECKBOX_TYPE:
        case SWITCH_TYPE:
            res.type = "boolean";
            break;

        case CHECKBOXES_TYPE:
        case SELECT_MULTIPLE_TYPE: {
            const catalogSize = fieldConfig.choices?.length || 0;
            const requestedMaxItems =
                Number.isFinite(Number(fieldConfig.maxNumOfChoices)) && Number(fieldConfig.maxNumOfChoices) > 0
                    ? Math.round(Number(fieldConfig.maxNumOfChoices))
                    : undefined;
            const effectiveMaxCap = catalogSize > 0 ? catalogSize : requestedMaxItems;
            const maxItems =
                typeof requestedMaxItems === "number" && typeof effectiveMaxCap === "number"
                    ? Math.min(requestedMaxItems, effectiveMaxCap)
                    : undefined;
            const requestedMinItems =
                Number.isFinite(Number(fieldConfig.minNumOfChoices)) && Number(fieldConfig.minNumOfChoices) > 0
                    ? Math.round(Number(fieldConfig.minNumOfChoices))
                    : undefined;
            const effectiveMinCap =
                typeof maxItems === "number" ? maxItems : (catalogSize > 0 ? catalogSize : requestedMinItems);
            const minItems =
                typeof requestedMinItems === "number" && typeof effectiveMinCap === "number"
                    ? Math.min(requestedMinItems, effectiveMinCap)
                    : undefined;
            res.type = "array";
            res.items = fieldConfig.choices?.length
                ? { type: "string", enum: fieldConfig.choices.map((opt: TChoice) => opt.value) }
                : { type: "string" };
            if (typeof maxItems === "number" && maxItems > 0) res.maxItems = maxItems;
            if (typeof minItems === "number" && minItems > 0) res.minItems = minItems;
            else if (fieldConfig.required) res.minItems = 1;
            break;
        }

        case RADIO_BUTTONS_TYPE:
            res.type = "string";
            if (fieldConfig.choices?.length) {
                res.enum = fieldConfig.choices.map((opt: TChoice) => opt.value);
            }
            break;

        case PRODUCT_LIST_TYPE:
        case IMAGE_GALLERY_TYPE: {
            const catalogSize = fieldConfig.choices?.length || 0;
            const requestedMaxItems =
                Number.isFinite(Number(fieldConfig.maxNumOfChoices)) && Number(fieldConfig.maxNumOfChoices) > 0
                    ? Math.round(Number(fieldConfig.maxNumOfChoices))
                    : undefined;
            const effectiveMaxCap = catalogSize > 0 ? catalogSize : requestedMaxItems;
            const maxItems =
                typeof requestedMaxItems === "number" && typeof effectiveMaxCap === "number"
                    ? Math.min(requestedMaxItems, effectiveMaxCap)
                    : undefined;
            const requestedMinItems =
                Number.isFinite(Number(fieldConfig.minNumOfChoices)) && Number(fieldConfig.minNumOfChoices) > 0
                    ? Math.round(Number(fieldConfig.minNumOfChoices))
                    : undefined;
            const effectiveMinCap =
                typeof maxItems === "number" ? maxItems : (catalogSize > 0 ? catalogSize : requestedMinItems);
            const minItems =
                typeof requestedMinItems === "number" && typeof effectiveMinCap === "number"
                    ? Math.min(requestedMinItems, effectiveMinCap)
                    : undefined;
            res.type = "array";
            res.items = { type: "object", additionalProperties: true };
            if (typeof maxItems === "number" && maxItems > 0) res.maxItems = maxItems;
            if (typeof minItems === "number" && minItems > 0) res.minItems = minItems;
            else if (fieldConfig.required) res.minItems = 1;
            break;
        }

        case QUIZ_TYPE:
            if (fieldConfig.choices?.length) {
                const catalogSize = fieldConfig.choices.length;
                const maxItems = fieldConfig.multiple
                    ? (Number.isFinite(Number(fieldConfig.maxNumOfChoices)) && Number(fieldConfig.maxNumOfChoices) > 0
                        ? Math.min(Math.round(Number(fieldConfig.maxNumOfChoices)), catalogSize)
                        : catalogSize)
                    : 1;
                const minItems =
                    Number.isFinite(Number(fieldConfig.minNumOfChoices)) && Number(fieldConfig.minNumOfChoices) > 0
                        ? Math.min(Math.round(Number(fieldConfig.minNumOfChoices)), maxItems)
                        : undefined;
                res.type = "array";
                res.items = { type: "object", additionalProperties: true };
                res.maxItems = maxItems;
                if (typeof minItems === "number" && minItems > 0) res.minItems = minItems;
                else if (fieldConfig.required && fieldConfig.multiple) res.minItems = 1;
            } else {
                res.type = "string";
            }
            break;

        case SETTING_TYPE:
            res.anyOf = [
                { type: "string" },
                { type: "number" },
                { type: "boolean" },
                { type: "object" },
                { type: "array" },
            ];
            break;

        case SELECT_ONE_TYPE:
            res.type = "string";
            if (fieldConfig.choices) {
                res.enum = fieldConfig.choices.map((opt: TChoice) => opt.value);
            }
            break;

        case SLUG_TYPE:
            res.type = "string";
            res.pattern = "^[a-z0-9][a-z0-9_-]*$";
            break;

        case URL_TYPE:
            res.type = "string";
            res.format = "uri";
            break;

        case EMAIL_TYPE:
            res.type = "string";
            res.format = "email";
            break;

        case DATETIME_TYPE:
            res.type = "string";
            res.format = "date-time";
            if (minString) res.formatMinimum = minString;
            if (maxString) res.formatMaximum = maxString;
            break;

        case DATE_TYPE:
            res.type = "string";
            res.format = "date";
            if (minString) res.formatMinimum = minString;
            if (maxString) res.formatMaximum = maxString;
            break;

        case TIME_TYPE:
            res.type = "string";
            res.format = "time";
            if (minString) res.formatMinimum = minString;
            if (maxString) res.formatMaximum = maxString;
            break;

        default:
            res.type = "string";
            break;
    }

    if (fieldConfig.pattern) res.pattern = fieldConfig.pattern;
    if (fieldConfig.minLen) res.minLength = Number(fieldConfig.minLen);
    if (fieldConfig.maxLen) res.maxLength = Number(fieldConfig.maxLen);

    return res;
}

// ─── Schema builder ───────────────────────────────────────────────────────────

/**
 * Builds a complete AJV JSON schema for the given form config.
 * In multi-step mode, only the fields of the given step index are included.
 */
export function buildSchema(formConfig: TFormConfig, sectionIndex: number = 0): TSchema {
    let fields: TFieldConfig[] = [];

    if (formConfig?.mode === FORM_MULTI_STEP_MODE) {
        const currentSection = getSectionByIndex(formConfig, sectionIndex);
        fields = currentSection && formConfig.sections.hasOwnProperty(currentSection.name)
            ? formConfig.sections[currentSection.name]
            : [];
    } else {
        const sectionList = getCustomSectionList(formConfig);
        sectionList.forEach((sectionConfig: TFieldConfig) => {
            const currentFields = formConfig.sections.hasOwnProperty(sectionConfig.name)
                ? formConfig.sections[sectionConfig.name]
                : [];
            fields.push(...currentFields);
        });
    }

    const required: string[] = [];
    const errorMessage: Record<string, string> = {};
    const properties: Record<string, any> = {};
    const fieldMap: Record<string, TFieldConfig> = {};

    for (const fieldConfig of fields) {
        if (shouldRenderField(formConfig, fieldConfig)) {
            const ajvType = toAjvFieldType(fieldConfig);
            if (!isEmpty(ajvType)) {
                fieldMap[fieldConfig.name] = fieldConfig;
                properties[fieldConfig.name] = ajvType;
                if (fieldConfig.required) required.push(fieldConfig.name);
                if (fieldConfig.errorMsg) errorMessage[fieldConfig.name] = fieldConfig.errorMsg;
            }
        }
    }

    const ajvSchema = {
        type: "object",
        properties,
        ...(required.length ? { required } : {}),
        additionalProperties: true,
        ...(!isEmpty(errorMessage) ? { errorMessage: { properties: errorMessage } } : {}),
    };

    return { ajvSchema, fieldMap };
}
