import { BTNGROUP_TYPE, HEADER_NAV_TYPE, SLUG_TYPE } from "./field";
import TFieldConfig from "./TFieldConfig";
import TFormConfig, { RenderingMode } from "./TFormConfig";
import { CUSTOM_SECTION } from './Constants';
import TMediaFile, { TMediaInfo, MediaSizeType } from './TMediaFile';
import { isObject } from 'lodash';

export const FORM_ID = "form";
export const SECTION_ID = 'attrgroup';
export const FIELD_ID = "field";
export const LABEL_ID = "label";
export const INPUT_ID = "input";

export const DATA_FORM_CONTROL_ID = 'data-form-control';

export const TOP_ALIGNED_LABELS = 'tal';
export const LEFT_ALIGNED_LABELS = 'lal';
export const RIGHT_ALIGNED_LABELS = 'ral';
export const BOTTOM_ALIGNED_LABELS = 'bal';
export const LABELS_WITHIN_INPUTS = 'lwi';



export type TFormType = {
    type: string,
    label: string,
    description: string,
    icon: string
}

export type TGetPostAssetsResult = {
    mediaFiles: TMediaFile[];
    mediaFilesMap: Record<string, TMediaFile>;
}


export const getLargeImageUrl = (mediaFile: TMediaInfo): string => {
    if (mediaFile.large)
        return mediaFile.large.publicUrl;


    return mediaFile.publicUrl ? mediaFile.publicUrl : '';
}

export const getSmallImageUrl = (mediaFile: TMediaInfo): string => {
    if (mediaFile.small)
        return mediaFile.small.publicUrl

    if (mediaFile.thumb)
        return mediaFile.thumb.publicUrl

    if (mediaFile.medium)
        return mediaFile.medium.publicUrl;

    if (mediaFile.large)
        return mediaFile.large.publicUrl;


    return mediaFile.publicUrl ? mediaFile.publicUrl : '';
}

export const getThumbImageUrl = (mediaFile: TMediaInfo): string => {
    if (mediaFile.thumb)
        return mediaFile.thumb.publicUrl

    if (mediaFile.medium)
        return mediaFile.medium.publicUrl;

    if (mediaFile.large)
        return mediaFile.large.publicUrl;

    return mediaFile.publicUrl ? mediaFile.publicUrl : '';
}

export const getMediumImageUrl = (mediaFile: TMediaInfo): string => {
    if (mediaFile.medium)
        return mediaFile.medium.publicUrl;

    if (mediaFile.large)
        return mediaFile.large.publicUrl;

    return mediaFile.publicUrl ? mediaFile.publicUrl : '';
}


export const getCustomSectionList = (formConfig: TFormConfig): Array<TFieldConfig> => {
    return formConfig.sections.hasOwnProperty(CUSTOM_SECTION) ? formConfig.sections[CUSTOM_SECTION] : []
}

export const getSectionFields = (formConfig: TFormConfig, sectionName: string): Array<TFieldConfig> => {
    return formConfig.sections.hasOwnProperty(sectionName) ? formConfig.sections[sectionName] : [];
}

export const getSectionHasFields = (formConfig: TFormConfig, sectionName: string): boolean => {
    const fields = getSectionFields(formConfig, sectionName);
    return fields.length != 0;
}


export const getSectionByIndex = (formConfig: TFormConfig, index: number): TFieldConfig | null => {
    const mainSection = CUSTOM_SECTION;

    if (!formConfig || index < 0 || index >= formConfig.sections[mainSection].length)
        return null;

    return formConfig.sections[mainSection][index];
}


export const getFieldConfigByIndex = (formConfig: TFormConfig, sectionIndex: number, fieldIndex: number): TFieldConfig | null => {
    const sectionConfig = getSectionByIndex(formConfig, sectionIndex);

    if (sectionConfig) {
        if (fieldIndex < 0 || fieldIndex >= formConfig.sections[sectionConfig.name].length)
            return null;

        return formConfig.sections[sectionConfig.name][fieldIndex];
    }

    return null;
}



export const getSectionByName = (formConfig: TFormConfig, sectionName: string) => {
    const groupIndex = getSectionIndex(formConfig, sectionName);
    const mainSection = CUSTOM_SECTION;
    return groupIndex >= 0 ? formConfig.sections[mainSection][groupIndex] : null;
}

export const getSectionIndex = (formConfig: TFormConfig, sectionName: string) => {
    let groupIndex = -1;
    const mainSection = CUSTOM_SECTION;

    if (formConfig.sections[mainSection]) {
        formConfig.sections[mainSection].every((tmp: TFieldConfig, index: number) => {

            if (tmp.name === sectionName) {
                groupIndex = index;
                return false;
            }

            return true;
        });
    }

    return groupIndex;
}


export function shouldRenderField(formConfig: TFormConfig, fieldConfig: TFieldConfig): boolean {
    const {
        renderingMode = RenderingMode.CREATE_ENTRY as RenderingMode
    } = formConfig;

    const {
        canEdit = true
    } = fieldConfig;

    if (renderingMode === RenderingMode.MODIFY_ENTRY && (!canEdit || fieldConfig.type === SLUG_TYPE))
        return false;


    return true;
}





export const getMediaUrlByMediaId = (fieldConfig: TFieldConfig, mediaSize: MediaSizeType = MediaSizeType.Small): string => {

    const mediaInfo: TMediaInfo = fieldConfig.mediaInfo ? fieldConfig.mediaInfo : { publicUrl: fieldConfig.mediaId };

    switch (mediaSize) {
        case MediaSizeType.Small:
            return getSmallImageUrl(mediaInfo);

        case MediaSizeType.Thumb:
            return getThumbImageUrl(mediaInfo);

        case MediaSizeType.Medium:
            return getMediumImageUrl(mediaInfo);

        case MediaSizeType.Large:
            return getLargeImageUrl(mediaInfo);

        default:
            return '';
    }
}

export function isJSON(str: string) {
    try {
        const obj = JSON.parse(str);
        return isObject(obj);
    } catch (e) {
        return false;
    }
}


// ======================================================

export function getFieldConfigByName(formConfig: TFormConfig, sectionName: string, fieldName: string) : TFieldConfig | null {
    if (formConfig.sections[sectionName]) {
        let fieldIndex = -1;
        const fields =  formConfig.sections[sectionName];
        fields.every((tmpFieldConfig: TFieldConfig, index: number) => {
            if (tmpFieldConfig.name === fieldName) {
                fieldIndex = index;
                return false;
            }
    
            return true;
        });
    
        return fieldIndex >=0 ? fields[fieldIndex] : null;
    }

    return null;
}


export function getFirstSectionIndexBySubType(formConfig: TFormConfig, subType: string): number {
    const mainSection = CUSTOM_SECTION;

    const sections = formConfig.sections.hasOwnProperty(mainSection) ? formConfig.sections[mainSection] : [];

    let fieldIndex = -1;
    sections.every((tmpFieldConfig: TFieldConfig, index: number) => {
        if (tmpFieldConfig.subType === subType) {
            fieldIndex = index;
            return false;
        }

        return true;
    });


    return fieldIndex;
}

export function getFormSubmitSectionIndex(formConfig: TFormConfig): number {
    return getFirstSectionIndexBySubType(formConfig, BTNGROUP_TYPE);
}

export function getFormNavSectionIndex(formConfig: TFormConfig): number {
    return getFirstSectionIndexBySubType(formConfig, HEADER_NAV_TYPE);
}
