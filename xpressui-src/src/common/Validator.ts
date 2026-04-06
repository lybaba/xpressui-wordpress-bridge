import { buildSchema } from "./schema-builder";
import { getCustomSectionList } from "./post";
import TFormConfig, { FORM_MULTI_STEP_MODE, TFormValidationI18nConfig } from "./TFormConfig";
import { ajv } from './frontend';
import { ValidateFunction } from "ajv";
import parseErrors from "./parse-errors";
import TFieldConfig from "./TFieldConfig";

export type TValidator = {
    validate: ValidateFunction<unknown>;
    fieldMap: Record<string, TFieldConfig>;
}

export function getValidators(formConfig: TFormConfig): TValidator[] {    
    const res: TValidator[] = [];
    if (formConfig?.mode !== FORM_MULTI_STEP_MODE) {
        const {
            ajvSchema,
            fieldMap
        } = buildSchema(formConfig);
        const validate = ajv.compile(ajvSchema);
        res.push({
            validate,
            fieldMap
        });
    } else {
        const nbSteps = getCustomSectionList(formConfig).length;
        for (let sectionIndex = 0; sectionIndex < nbSteps; sectionIndex++) {
            const {
                ajvSchema,
                fieldMap
            } = buildSchema(formConfig, sectionIndex);
            const validate = ajv.compile(ajvSchema);
            res.push({
                validate,
                fieldMap
            });
        }
    }

    return res;
}


export default function validate(
    validator: TValidator,
    formValues: Record<string, any>,
    i18n?: TFormValidationI18nConfig,
) {
    validator.validate(formValues);

    const errors = parseErrors(validator.validate.errors, validator.fieldMap, i18n);

    return errors;
}
