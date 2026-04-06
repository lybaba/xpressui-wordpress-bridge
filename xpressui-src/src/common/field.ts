
import TFieldConfig from './TFieldConfig';

import slugify from 'slugify';
import { isEmpty, lowerCase } from 'lodash';
import TFieldType from './TFieldType';
import { TValidator } from './Validator';
import { FieldConfig } from 'final-form';

export const SECTION_TYPE = 'section';


export const MEDIA_TYPE = 'media';
export const CHECKBOX_TYPE = 'checkbox';
export const DATE_TYPE = 'date';
export const TIME_TYPE = 'time';
export const DATETIME_TYPE = 'datetime';
export const EMAIL_TYPE = 'email';
export const UPLOAD_FILE_TYPE = 'file';
export const UPLOAD_IMAGE_TYPE = 'upload-image';
export const CAMERA_PHOTO_TYPE = 'camera-photo';
export const QR_SCAN_TYPE = 'qr-scan';
export const DOCUMENT_SCAN_TYPE = 'document-scan';
export const IMAGE_TYPE = 'image';
export const NUMBER_TYPE = 'number';
export const POSITIVE_INTEGER_TYPE = 'integer';
export const PRICE_TYPE = 'price';
export const PASSWORD_TYPE = 'password';
export const SELECT_ONE_TYPE = 'select-one';
export const RADIO_BUTTONS_TYPE = 'radio-buttons';
export const BTNGROUP_TYPE = 'btngroup';
export const CALL2ACTION_GROUP_TYPE = 'call2action-group';
export const CALL2ACTION_TYPE = 'call2action';

export const ACTION_TYPE_TYPE = 'action-type';
export const ACTION_TARGET_TYPE = 'action-target';

export const SELECT_MULTIPLE_TYPE = 'select-multiple';
export const CHECKBOXES_TYPE = 'checkboxes';
export const PRODUCT_LIST_TYPE = 'product-list';
export const SELECT_PRODUCT_TYPE = 'select-product';
export const SELECT_IMAGE_TYPE = 'select-image';
export const IMAGE_GALLERY_TYPE = SELECT_IMAGE_TYPE;
export const QUIZ_TYPE = 'quiz';
export const SETTING_TYPE = 'setting';

export const SUBMIT_TYPE = 'submit';
export const BTN_TYPE = 'btn';
export const TEXT_TYPE = 'text';
export const SLUG_TYPE = 'slug';
export const TEXTAREA_TYPE = 'textarea';
export const TEL_TYPE = 'tel';
export const OUTPUT_TYPE = 'output';
export const APPROVAL_STATE_TYPE = 'approval-state';
export const RICH_EDITOR_TYPE = 'rich-editor';
export const FIELDGROUP_SELECT_TYPE = 'section-select';
export const URL_TYPE = 'url';
export const TAX_TYPE = 'tax';
export const SWITCH_TYPE = 'switch';

export const LOGO_TYPE = 'logo';
export const HERO_TYPE = 'hero';
export const FOOTER_TYPE = 'footer';
export const HEADER_NAV_TYPE = 'header-nav';
export const TITLE_TYPE = 'title';
export const HTML_TYPE = 'html';
export const FORM_TYPE = 'form';
export const HEADER_TYPE = 'header';

export const LINK_TYPE = 'link';
export const UNKNOWN_TYPE = 'unknown';

export const FORM_SECTION_LABEL = 'Custom Section';
export const LOGO_LABEL = 'Logo';
export const BODY_LABEL = 'Body';
export const HEADER_LABEL = 'Header';
export const HERO_LABEL = 'Hero Section';
export const FOOTER_LABEL = 'Footer';
export const HEADER_NAV_LABEL = 'Header Menu';
export const BTNGROUP_TYPE_LABEL = 'Form Buttons';
export const LINK_TYPE_LABEL = 'Website/URL';
export const CALL2ACTION_GROUP_TYPE_LABEL = 'Hero Actions';
export const CALL2ACTION_TYPE_LABEL = 'Call-To-Action Button';

export const FIELDGROUP_TYPE_FIELD: TFieldType = { type: SECTION_TYPE, subType: SECTION_TYPE, name: FORM_SECTION_LABEL };
export const BTNGROUP_TYPE_FIELD: TFieldType = { type: BTNGROUP_TYPE, name: BTNGROUP_TYPE_LABEL };

export const HERO_TYPE_FIELD: TFieldType = { type: SECTION_TYPE, subType: HERO_TYPE, name: HERO_LABEL, adminLabel: HERO_LABEL };


export const CALL2ACTION_GROUP_TYPE_FIELD: TFieldType = { type: SECTION_TYPE, subType: CALL2ACTION_GROUP_TYPE, name: CALL2ACTION_GROUP_TYPE_LABEL, adminLabel: CALL2ACTION_GROUP_TYPE_LABEL };
export const CALL2ACTION_TYPE_FIELD: TFieldType = { type: CALL2ACTION_TYPE, name: CALL2ACTION_TYPE_LABEL };

export const HEADER_NAV_TYPE_FIELD: TFieldType = { type: SECTION_TYPE, subType: HEADER_NAV_TYPE, name: HEADER_NAV_LABEL, adminLabel: HEADER_NAV_LABEL };
export const HEADER_TITLE_TYPE_FIELD: TFieldType = { type: SECTION_TYPE, subType: HEADER_TYPE, name: HEADER_LABEL, adminLabel: HEADER_LABEL };
export const FOOTER_TYPE_FIELD: TFieldType = { type: SECTION_TYPE, subType: FOOTER_TYPE, name: FOOTER_LABEL, adminLabel: FOOTER_LABEL };

export const TEXT_TYPE_FIELD: TFieldType = { type: TEXT_TYPE, name: 'Text Field' };
export const TEXTAREA_TYPE_FIELD: TFieldType = { type: TEXTAREA_TYPE, name: 'Text Area' };
export const NUMBER_TYPE_FIELD: TFieldType = { type: NUMBER_TYPE, name: 'Number' };
export const PRICE_TYPE_FIELD: TFieldType = { type: PRICE_TYPE, name: 'Price' };
export const EMAIL_TYPE_FIELD: TFieldType = { type: EMAIL_TYPE, name: 'Email' };
export const PASSWORD_TYPE_FIELD: TFieldType = { type: PASSWORD_TYPE, name: 'Password' };
export const TEL_TYPE_FIELD: TFieldType = { type: TEL_TYPE, name: 'Phone Number' };
export const SUBMIT_TYPE_FIELD: TFieldType = { type: SUBMIT_TYPE, name: 'Submit button' };
export const CHECKBOX_TYPE_FIELD: TFieldType = { type: CHECKBOX_TYPE, name: 'Checkbox' };
export const MULTI_SELECT_TYPE_FIELD: TFieldType = { type: SELECT_MULTIPLE_TYPE, name: 'Multi-select List' };
export const CHECKBOXES_TYPE_FIELD: TFieldType = { type: CHECKBOXES_TYPE, name: 'Checkboxes' };
export const SINGLE_SELECT_TYPE_FIELD: TFieldType = { type: SELECT_ONE_TYPE, name: 'Single-select List' };
export const RADIO_BUTTONS_TYPE_FIELD: TFieldType = { type: RADIO_BUTTONS_TYPE, name: 'Radio Buttons' };
export const URL_TYPE_FIELD: TFieldType = { type: URL_TYPE, name: 'URL' };
export const IMAGE_TYPE_FIELD: TFieldType = { type: IMAGE_TYPE, name: 'Image' };
export const UPLOAD_IMAGE_TYPE_FIELD: TFieldType = { type: UPLOAD_IMAGE_TYPE, name: 'Upload Image' };
export const UPLOAD_FILE_TYPE_FIELD: TFieldType = { type: UPLOAD_FILE_TYPE, name: 'Upload File' };
export const DATETIME_TYPE_FIELD: TFieldType = { type: DATETIME_TYPE, name: 'Date / Time' };
export const DATE_TYPE_FIELD: TFieldType = { type: DATE_TYPE, name: 'Date' };
export const TIME_TYPE_FIELD: TFieldType = { type: TIME_TYPE, name: 'Time' };
export const TAX_TYPE_FIELD: TFieldType = { type: TAX_TYPE, name: 'Tax' };
export const SWITCH_TYPE_FIELD: TFieldType = { type: SWITCH_TYPE, name: 'Switch' };
export const HTML_TYPE_FIELD: TFieldType = { type: HTML_TYPE, name: 'Html Content' };
export const FORM_TYPE_FIELD: TFieldType = { type: FORM_TYPE, name: 'Form Content' };

export const BTN_TYPE_FIELD: TFieldType = { type: BTN_TYPE, name: 'Button' };


export const NAV_MENU_ITEM_FIELD: TFieldType = { type: LINK_TYPE, name: LINK_TYPE_LABEL };


export const REQUEST_FORM_FIELD_TYPES: Array<TFieldType> = [
    TEXT_TYPE_FIELD,
    TEXTAREA_TYPE_FIELD,
    NUMBER_TYPE_FIELD,
    DATETIME_TYPE_FIELD,
    CHECKBOX_TYPE_FIELD,
    SINGLE_SELECT_TYPE_FIELD,
    MULTI_SELECT_TYPE_FIELD,
    IMAGE_TYPE_FIELD,
    UPLOAD_IMAGE_TYPE_FIELD,
    UPLOAD_FILE_TYPE_FIELD,
    { type: PRODUCT_LIST_TYPE, name: 'Product Catalog' },
    { type: SELECT_PRODUCT_TYPE, name: 'Select Product' },
    { type: IMAGE_GALLERY_TYPE, name: 'Select Image' },
    { type: QUIZ_TYPE, name: 'Quiz' },
    { type: SETTING_TYPE, name: 'Setting' },
];


export const PRICING_DECIMAL_VALUES = [
    { value: "0", label: "0" },
    { value: "1", label: "1" },
    { value: "2", label: "2" },
    { value: "3", label: "3" },
    { value: "4", label: "4" },
    { value: "5", label: "5" },
    { value: "6", label: "6" },
    { value: "7", label: "7" },
    { value: "8", label: "8" },
];



export const getIsChoiceField = (type: string): boolean => {
    return type === SELECT_MULTIPLE_TYPE ||
        type === SELECT_ONE_TYPE
}

export const isFileFieldType = (type: string): boolean => {
    return type === UPLOAD_FILE_TYPE ||
        type === UPLOAD_IMAGE_TYPE ||
        type === CAMERA_PHOTO_TYPE ||
        type === QR_SCAN_TYPE ||
        type === DOCUMENT_SCAN_TYPE;
}

export const isFileLikeValue = (value: any): boolean => {
    if (!value) {
        return false;
    }

    if (typeof File !== 'undefined' && value instanceof File) {
        return true;
    }

    if (typeof Blob !== 'undefined' && value instanceof Blob) {
        return true;
    }

    return Boolean(
        typeof value === 'object' &&
        typeof value.name === 'string' &&
        typeof value.size === 'number' &&
        typeof value.type === 'string' &&
        typeof value.arrayBuffer === 'function'
    );
}

export const buildSlug = (name: string) => {
    return slugify(lowerCase(name), { replacement: '-' });
}

export const normalizeFormName = (name: string) => {
    return slugify(lowerCase(name), { replacement: '-' });
}

export const normalizeFieldName = (name: string) => {
    return slugify(lowerCase(name), { replacement: '_' });
}

export const getFieldName = (fieldIndex: number, fieldConfig: TFieldConfig): string => {
    if (fieldConfig.name) return normalizeFieldName(fieldConfig.name);

    return `field${fieldIndex}`;
}

export const getBooleanValue = (value: any): boolean => {
    if (typeof value === 'boolean')
        return value;

    if (typeof value === 'string')
        return value === 'true' ? true : false;

    if (typeof value === 'number')
        return value === 0 ? false : true;

    return false;
}


export const doNormalizeFieldValue = (fieldConfig: TFieldConfig, value: any): any => {
    switch (fieldConfig.type) {
        case CHECKBOX_TYPE:
        case SWITCH_TYPE:
            return getBooleanValue(value);

        case DATETIME_TYPE:
            return value ? value.replace('T', ' ') : value

        case NUMBER_TYPE:
        case PRICE_TYPE:
        case TAX_TYPE:
            if (Number.isNaN(value) || value === 'NaN')
                return 0;

            return parseFloat(value)

        case POSITIVE_INTEGER_TYPE:
            if (Number.isNaN(value) || value === 'NaN')
                return 0;

            return parseInt(value)

        default:
            return value;

    }
};

export function normalizeFormValues(fieldMap: Record<string, TFieldConfig>, formValues: Record<string, any>) : Record<string, any> {
    const res: Record<string, any> = {}

    for (const [fieldName, fieldVal] of Object.entries(formValues)) {
        const fieldConfig = fieldMap[fieldName];
        
        let hasValue = false;
        if (fieldVal !== undefined && fieldVal !== null) {
            if (typeof fieldVal === 'number' || typeof fieldVal === 'boolean' || fieldVal instanceof Date) {
                hasValue = true;
            } else if (isFileLikeValue(fieldVal)) {
                hasValue = true;
            } else if (Array.isArray(fieldVal)) {
                hasValue = fieldVal.length > 0;
            } else if (typeof fieldVal === 'string') {
                hasValue = fieldVal.trim().length > 0;
            } else {
                hasValue = !isEmpty(fieldVal);
            }
        }
        
        if (hasValue && fieldConfig) {
            res[fieldName] = doNormalizeFieldValue(fieldConfig, fieldVal);
        }
    }

    return res;
}


export const getHtmlInputType = (fieldType: string): string => {
    switch (fieldType) {
        case TEXT_TYPE:
            return "text";

        case EMAIL_TYPE:
            return "email";

        case PASSWORD_TYPE:
            return "password"

        case NUMBER_TYPE:
        case PRICE_TYPE:
        case TAX_TYPE:
        case POSITIVE_INTEGER_TYPE:
            return "number";

        case DATETIME_TYPE:
            return "datetime-local";

        case DATE_TYPE:
            return "date";

        case PRODUCT_LIST_TYPE:
        case IMAGE_GALLERY_TYPE:
        case QUIZ_TYPE:
        case SETTING_TYPE:
            return "text";

        case TIME_TYPE:
            return "time";

        case APPROVAL_STATE_TYPE:
            return "text";

        case TEL_TYPE:
            return "tel";

        case UPLOAD_FILE_TYPE:
        case UPLOAD_IMAGE_TYPE:
        case CAMERA_PHOTO_TYPE:
        case QR_SCAN_TYPE:
        case DOCUMENT_SCAN_TYPE:
            return "file";

        default:
            return fieldType
    }
}


export const getHtmlInputProps = (fieldConfig: TFieldConfig): Record<string, any> => {
    const minNumber = Number.isFinite(Number(fieldConfig.min)) ? Number(fieldConfig.min) : undefined;
    const maxNumber = Number.isFinite(Number(fieldConfig.max)) ? Number(fieldConfig.max) : undefined;
    const minString = fieldConfig.min ? String(fieldConfig.min) : undefined;
    const maxString = fieldConfig.max ? String(fieldConfig.max) : undefined;

    switch (fieldConfig.type) {
        case NUMBER_TYPE:
            return {
                ...(typeof minNumber === "number" ? { min: minNumber } : {}),
                ...(typeof maxNumber === "number" ? { max: maxNumber } : {}),
                ...(fieldConfig.step ? { step: Number(fieldConfig.step) } : {}),
            };

        case PRICE_TYPE:
            return {
                min: typeof minNumber === "number" ? minNumber : 0,
                ...(typeof maxNumber === "number" ? { max: maxNumber } : {}),
                ...(fieldConfig.step ? { step: Number(fieldConfig.step) } : {}),
            };

        case TAX_TYPE:
            return {
                min: typeof minNumber === "number" ? Math.max(0, minNumber) : 0,
                max: typeof maxNumber === "number" ? Math.min(1, maxNumber) : 1,
                ...(fieldConfig.step ? { step: Number(fieldConfig.step) } : {}),
            }

        case POSITIVE_INTEGER_TYPE:
            return {
                min: typeof minNumber === "number" ? minNumber : 0,
                ...(typeof maxNumber === "number" ? { max: maxNumber } : {}),
                step: 1
            }

        case DATE_TYPE:
            {
                const minProp = minString ? { min: minString } : {};
                const maxProp = maxString ? { max: maxString } : {};
                const stepProp = fieldConfig.step ? { step: fieldConfig.step } : {};

                return {
                    ...minProp,
                    ...maxProp,
                    ...stepProp
                }
            }

        case DATETIME_TYPE:
        case TIME_TYPE:
            {
                const minProp = minString ? { min: minString } : {};
                const maxProp = maxString ? { max: maxString } : {};
                const stepProp = fieldConfig.step ? { step: fieldConfig.step } : {};

                return {
                    ...minProp,
                    ...maxProp,
                    ...stepProp
                }
            }


        default:
            return {}
    }
}


export function getSizes(width: string) : Record<string, number> {
    const tab = width.split(' ');
    const sizes : Record<string, number> = {};
    tab.forEach((token) => {
        const tab2 = token.split(':');
       
        if (tab2.length == 2) {
            const sizeName = tab2[0];
            const sizeVal = Number(tab2[1]);
            if (['xl', 'md', 'sm', 'xs'].includes(sizeName)) {
                sizes[sizeName] = sizeVal;
            }
        }
    });

    return sizes;
}
