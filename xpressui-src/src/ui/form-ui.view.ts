import getFormConfig, { getFieldConfig } from "../dom-utils";
import TFieldConfig from "../common/TFieldConfig";
import {
  CHECKBOXES_TYPE,
  CAMERA_PHOTO_TYPE,
  DOCUMENT_SCAN_TYPE,
  HTML_TYPE,
  IMAGE_GALLERY_TYPE,
  IMAGE_TYPE,
  LINK_TYPE,
  MEDIA_TYPE,
  OUTPUT_TYPE,
  QUIZ_TYPE,
  RICH_EDITOR_TYPE,
  SETTING_TYPE,
  TEXTAREA_TYPE,
  TEXT_TYPE,
  UNKNOWN_TYPE,
  UPLOAD_FILE_TYPE,
  UPLOAD_IMAGE_TYPE,
  URL_TYPE,
  PRODUCT_LIST_TYPE,
  RADIO_BUTTONS_TYPE,
} from "../common/field";
import type {
  TFormRenderMode,
  TOutputRendererType,
} from "./form-ui.types";

export function readViewValuesAttribute(host: HTMLElement): Record<string, any> {
  const rawAttribute = host.getAttribute("view-values") || host.getAttribute("data-view-values");
  if (!rawAttribute) {
    return {};
  }

  try {
    const parsed = JSON.parse(rawAttribute);
    return parsed && typeof parsed === "object" ? parsed : {};
  } catch {
    return {};
  }
}

export function collectDomFieldValues(formElem: HTMLFormElement): Record<string, any> {
  const domValues: Record<string, any> = {};
  Array.from(formElem.elements).forEach((node) => {
    const fieldConfig = getFieldConfig(node);
    if (!fieldConfig?.name || fieldConfig.type === UNKNOWN_TYPE) {
      return;
    }

    if (node instanceof HTMLInputElement) {
      if (node.type === "checkbox") {
        domValues[fieldConfig.name] = node.checked;
      } else if (
        node.type === "hidden"
        && (
          fieldConfig.type === PRODUCT_LIST_TYPE
          || fieldConfig.type === IMAGE_GALLERY_TYPE
          || fieldConfig.type === QUIZ_TYPE
          || fieldConfig.type === RADIO_BUTTONS_TYPE
          || fieldConfig.type === CHECKBOXES_TYPE
        )
      ) {
        if (!node.value) {
          domValues[fieldConfig.name] =
            fieldConfig.type === QUIZ_TYPE && !fieldConfig.choices?.length
              ? ""
              : fieldConfig.type === RADIO_BUTTONS_TYPE
                ? ""
                : [];
        } else if (fieldConfig.type === QUIZ_TYPE && !fieldConfig.choices?.length) {
          domValues[fieldConfig.name] = node.value;
        } else if (fieldConfig.type === RADIO_BUTTONS_TYPE) {
          domValues[fieldConfig.name] = node.value;
        } else {
          try {
            const parsed = JSON.parse(node.value);
            domValues[fieldConfig.name] = Array.isArray(parsed) ? parsed : [];
          } catch {
            domValues[fieldConfig.name] = [];
          }
        }
      } else if (node.type === "radio") {
        if (node.checked) {
          domValues[fieldConfig.name] = node.value;
        }
      } else {
        domValues[fieldConfig.name] = node.value;
      }
      return;
    }

    if (node instanceof HTMLSelectElement) {
      domValues[fieldConfig.name] = node.multiple
        ? Array.from(node.selectedOptions).map((option) => option.value)
        : node.value;
      return;
    }

    if (node instanceof HTMLTextAreaElement) {
      domValues[fieldConfig.name] = node.value;
    }
  });
  return domValues;
}

export function getOutputRendererType(fieldConfig: TFieldConfig): TOutputRendererType {
  const subType = String(fieldConfig.subType || fieldConfig.refType || "").toLowerCase();
  const accept = String(fieldConfig.accept || "").toLowerCase();

  if (fieldConfig.type === OUTPUT_TYPE || fieldConfig.type === MEDIA_TYPE) {
    if (subType.includes("html")) {
      return "html";
    }
    if (subType.includes("image")) {
      return "image";
    }
    if (subType.includes("video")) {
      return "video";
    }
    if (subType.includes("audio")) {
      return "audio";
    }
    if (subType.includes("map")) {
      return "map";
    }
    if (
      subType.includes("document")
      || subType.includes("pdf")
      || subType.includes("viewer")
      || subType.includes("embed")
    ) {
      return "document";
    }
    if (subType.includes("file") || subType.includes("document")) {
      return "file";
    }
    if (subType.includes("link") || subType.includes("url")) {
      return "link";
    }
  }

  if (fieldConfig.type === HTML_TYPE || fieldConfig.type === RICH_EDITOR_TYPE) {
    return "html";
  }

  if (
    fieldConfig.type === IMAGE_TYPE ||
    fieldConfig.type === UPLOAD_IMAGE_TYPE ||
    fieldConfig.type === CAMERA_PHOTO_TYPE
  ) {
    return "image";
  }

  if (fieldConfig.type === UPLOAD_FILE_TYPE || fieldConfig.type === DOCUMENT_SCAN_TYPE) {
    if (accept.includes("video/")) {
      return "video";
    }
    if (accept.includes("audio/")) {
      return "audio";
    }
    if (accept.includes("application/pdf")) {
      return "document";
    }
    return "file";
  }

  if (fieldConfig.type === LINK_TYPE || fieldConfig.type === URL_TYPE) {
    if (subType.includes("map")) {
      return "map";
    }
    return "link";
  }

  if (fieldConfig.type === PRODUCT_LIST_TYPE) {
    return "text";
  }

  if (fieldConfig.type === IMAGE_GALLERY_TYPE) {
    return "image";
  }

  if (fieldConfig.type === QUIZ_TYPE) {
    return "text";
  }

  if (fieldConfig.type === SETTING_TYPE) {
    return "text";
  }

  if (fieldConfig.type === "video" || accept.includes("video/")) {
    return "video";
  }

  if (fieldConfig.type === "audio" || accept.includes("audio/")) {
    return "audio";
  }

  if (fieldConfig.type === TEXT_TYPE || fieldConfig.type === TEXTAREA_TYPE) {
    return "text";
  }

  return "text";
}

export function isFieldViewMode(
  fieldConfig: TFieldConfig,
  inputElement: HTMLElement | null,
): boolean {
  const configMode = String((fieldConfig as any).viewMode || "").toLowerCase();
  const attrMode = String(
    inputElement?.getAttribute("data-field-render-mode")
      || inputElement?.getAttribute("data-view-mode")
      || "",
  ).toLowerCase();
  return configMode === "view" || attrMode === "view";
}

export function readInputElementValue(
  isProductListField: (fieldConfig: TFieldConfig) => boolean,
  isImageGalleryField: (fieldConfig: TFieldConfig) => boolean,
  isQuizField: (fieldConfig: TFieldConfig) => boolean,
  isChoiceListField: (fieldConfig: TFieldConfig) => boolean,
  fieldConfig: TFieldConfig,
  inputElement: HTMLElement | null,
): any {
  if (!inputElement) {
    return undefined;
  }

  if (inputElement instanceof HTMLInputElement) {
    if (inputElement.type === "checkbox") {
      return inputElement.checked;
    }

    if (
      inputElement.type === "hidden"
      && (isProductListField(fieldConfig) || isImageGalleryField(fieldConfig) || isQuizField(fieldConfig) || isChoiceListField(fieldConfig))
    ) {
      if (!inputElement.value) {
        if (isQuizField(fieldConfig) && !fieldConfig.choices?.length) {
          return "";
        }
        if (fieldConfig.type === RADIO_BUTTONS_TYPE) {
          return "";
        }
        return [];
      }
      if (isQuizField(fieldConfig) && !fieldConfig.choices?.length) {
        return inputElement.value;
      }
      if (fieldConfig.type === RADIO_BUTTONS_TYPE) {
        return inputElement.value;
      }
      try {
        const parsed = JSON.parse(inputElement.value);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }

    return inputElement.value;
  }

  if (inputElement instanceof HTMLSelectElement) {
    return inputElement.multiple
      ? Array.from(inputElement.selectedOptions).map((option) => option.value)
      : inputElement.value;
  }

  if (inputElement instanceof HTMLTextAreaElement) {
    return inputElement.value;
  }

  return undefined;
}

export function resolveFieldViewValue(
  host: HTMLElement,
  fieldConfig: TFieldConfig,
  inputElement: HTMLElement | null,
  stateValue: any,
  viewValues: Record<string, any>,
  readInputValue: (fieldConfig: TFieldConfig, inputElement: HTMLElement | null) => any,
): any {
  if (stateValue !== undefined) {
    return stateValue;
  }

  const overrideValues = {
    ...readViewValuesAttribute(host),
    ...viewValues,
  };
  if (Object.prototype.hasOwnProperty.call(overrideValues, fieldConfig.name)) {
    return overrideValues[fieldConfig.name];
  }

  const attrViewValue = inputElement?.getAttribute("data-view-value");
  if (attrViewValue) {
    try {
      return JSON.parse(attrViewValue);
    } catch {
      return attrViewValue;
    }
  }

  const inputValue = readInputValue(fieldConfig, inputElement);
  if (inputValue !== undefined && inputValue !== "") {
    return inputValue;
  }

  if ((fieldConfig as any).value !== undefined) {
    return (fieldConfig as any).value;
  }

  if (fieldConfig.defaultValue !== undefined) {
    return fieldConfig.defaultValue;
  }

  return "";
}
