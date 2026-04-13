import { getErrorClass } from "../dom-utils";

function escapeSelectorValue(value: string): string {
  if (typeof CSS !== "undefined" && typeof CSS.escape === "function") {
    return CSS.escape(value);
  }

  return value.replace(/["\\#.:()[\]=>+~*^$|,\s]/g, "\\$&");
}

export function getFieldElement(
  host: ParentNode,
  fieldName: string,
): HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement | null {
  const escapedFieldName = escapeSelectorValue(fieldName);
  const directId = host.querySelector(`#${escapedFieldName}`) as
    | HTMLInputElement
    | HTMLSelectElement
    | HTMLTextAreaElement
    | null;
  if (directId) {
    return directId;
  }

  const prefixedId = host.querySelector(`#${escapeSelectorValue(`field-${fieldName}`)}`) as
    | HTMLInputElement
    | HTMLSelectElement
    | HTMLTextAreaElement
    | null;
  if (prefixedId) {
    return prefixedId;
  }

  return host.querySelector(`[name="${escapedFieldName}"]`) as
    | HTMLInputElement
    | HTMLSelectElement
    | HTMLTextAreaElement
    | null;
}

export function getFieldContainer(host: ParentNode, fieldName: string): HTMLElement | null {
  const fieldElement = getFieldElement(host, fieldName);
  if (!fieldElement) {
    return null;
  }

  const closestLabel = fieldElement.closest("label");
  if (closestLabel) {
    return closestLabel as HTMLElement;
  }

  return fieldElement.parentElement as HTMLElement | null;
}

export function renderFieldErrorState(options: {
  fieldName: string;
  inputElement: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement | null;
  errorElement: HTMLElement | null;
  touched?: boolean;
  submitFailed?: boolean;
  error?: unknown;
  errors: Record<string, boolean>;
  ruleFieldErrors: Record<string, string>;
  currentStepIndex?: number;
}): void {
  const { fieldName, inputElement, errorElement } = options;
  const ruleError = options.ruleFieldErrors[fieldName];
  const displayedError = ruleError || ((options.touched || options.submitFailed) ? options.error : undefined);
  if (displayedError) {
    const errorClass = getErrorClass(inputElement);
    const errorMessage =
      typeof displayedError === "object" && displayedError && "errorMessage" in (displayedError as Record<string, any>)
        ? String((displayedError as Record<string, any>).errorMessage || "")
        : String(displayedError);
    if (errorElement) {
      const defaultMessage = errorElement.getAttribute("data-default-message") || "";
      errorElement.textContent = ruleError ? ruleError : (defaultMessage || errorMessage);
      errorElement.style.display = "block";
      errorElement.setAttribute("aria-hidden", "false");
    }
    if (inputElement) {
      inputElement.classList.add(errorClass);
      inputElement.setAttribute("aria-invalid", "true");
    }
    options.errors[fieldName] = true;
    return;
  }

  if (errorElement) {
    errorElement.textContent = "";
    errorElement.style.display = "none";
    errorElement.setAttribute("aria-hidden", "true");
  }
  if (inputElement) {
    const errorClass = getErrorClass(inputElement);
    inputElement.classList.remove(errorClass);
    inputElement.removeAttribute("aria-invalid");
  }
  options.errors[fieldName] = false;
}

export function bindSimpleFieldEvents(options: {
  input: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
  fieldConfig: { name: string };
  onBlur: () => void;
  onFocus: () => void;
  onChangeValue: (value: any) => void | Promise<void>;
  onAfterChange: () => void;
  resolveFileInputValue: (
    fieldConfig: { name: string },
    input: HTMLInputElement,
  ) => Promise<any>;
}): void {
  const { input, fieldConfig } = options;

  input.addEventListener("blur", () => {
    if (input instanceof HTMLInputElement && input.type === "file") {
      return;
    }
    options.onBlur();
  });
  input.addEventListener("input", (event: Event) => {
    if (input instanceof HTMLInputElement && input.type === "file") {
      return;
    }

    const nextValue =
      input instanceof HTMLInputElement && input.type === "checkbox"
        ? (event.target as HTMLInputElement | null)?.checked
        : input instanceof HTMLSelectElement && input.multiple
          ? Array.from((event.target as HTMLSelectElement | null)?.selectedOptions || []).map(
              (option) => option.value,
            )
          : (event.target as HTMLInputElement | HTMLTextAreaElement | null)?.value;
    void options.onChangeValue(nextValue);
    options.onAfterChange();
  });
  input.addEventListener("change", async () => {
    if (input instanceof HTMLInputElement && input.type === "file") {
      const nextValue = await options.resolveFileInputValue(fieldConfig, input);
      await options.onChangeValue(nextValue);
      options.onBlur();
    }
    options.onAfterChange();
  });
  input.addEventListener("focus", () => options.onFocus());

  if (
    input instanceof HTMLInputElement &&
    (input.type === "date" || input.type === "time" || input.type === "datetime-local")
  ) {
    input.addEventListener("click", () => {
      try {
        (input as HTMLInputElement & { showPicker?: () => void }).showPicker?.();
      } catch {
        // showPicker not supported or not triggered by user gesture — ignore
      }
    });
    input.addEventListener("wheel", (e) => e.preventDefault(), { passive: false });
  }
}

export function bindSelectionFieldEvents(options: {
  selectionElement: HTMLElement;
  dropZoneElement?: HTMLElement | null;
  input: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
  fieldConfig: { name: string; type?: string };
  isFileField: boolean;
  isProductListField: boolean;
  isImageGalleryField: boolean;
  isQuizField: boolean;
  isChoiceListField: boolean;
  isOpenQuizField: boolean;
  getCurrentValue: () => any;
  onChangeValue: (value: any) => void;
  onAfterChange: () => void;
  getNextProductCartItems: (action: "add" | "inc" | "dec" | "remove", productId: string) => any;
  getNextImageGallerySelectionItems: (action: "toggle" | "remove", imageId: string) => any;
  getNextQuizSelectionItems: (answerId: string) => any;
  getNextChoiceSelectionValue: (choiceValue: string) => any;
  openProductGallery: (productId: string) => void;
  openImageGallery: (imageId: string) => void;
  startQrCamera: () => void;
  scanQrCamera: () => void;
  stopQrCamera: () => void;
  setActiveDocumentScanSlot: (slotIndex: number) => void;
  refreshSelection: () => void;
  removeSelectedFile: (fileIndex: number) => void;
  setFileDragState: (active: boolean) => void;
  applyDroppedFiles: (files: File[]) => void;
}): void {
  const {
    selectionElement,
    input,
  } = options;
  const fileDropTarget = options.dropZoneElement || selectionElement;

  selectionElement.addEventListener("click", (event) => {
    const target = event.target as HTMLElement | null;

    const quizAnswerCard = target?.closest("[data-quiz-answer-action]") as HTMLElement | null;
    if (options.isQuizField && quizAnswerCard) {
      event.preventDefault();
      event.stopPropagation();
      const answerId = quizAnswerCard.getAttribute("data-quiz-answer-id");
      const disabled =
        quizAnswerCard.getAttribute("data-disabled") === "true"
        || quizAnswerCard.getAttribute("tabindex") === "-1";
      if (answerId && !disabled) {
        options.onChangeValue(options.getNextQuizSelectionItems(answerId));
        options.onAfterChange();
      }
      return;
    }

    const choiceCard = target?.closest("[data-choice-option-action]") as HTMLElement | null;
    if (options.isChoiceListField && choiceCard) {
      event.preventDefault();
      event.stopPropagation();
      const choiceValue = choiceCard.getAttribute("data-choice-option-value");
      const disabled =
        choiceCard.getAttribute("data-disabled") === "true"
        || choiceCard.getAttribute("tabindex") === "-1";
      if (choiceValue && !disabled) {
        options.onChangeValue(options.getNextChoiceSelectionValue(choiceValue));
        options.onAfterChange();
      }
      return;
    }

    const productActionButton = target?.closest("[data-product-action]") as HTMLElement | null;
    if (options.isProductListField && productActionButton) {
      event.preventDefault();
      event.stopPropagation();
      const action = productActionButton.getAttribute("data-product-action");
      const productId = productActionButton.getAttribute("data-product-id");
      if ((action === "add" || action === "inc" || action === "dec" || action === "remove") && productId) {
        options.onChangeValue(options.getNextProductCartItems(action, productId));
        options.onAfterChange();
      }
      return;
    }

    const imageGalleryActionButton = target?.closest("[data-image-gallery-action]") as HTMLElement | null;
    if (options.isImageGalleryField && imageGalleryActionButton) {
      event.preventDefault();
      event.stopPropagation();
      const action = imageGalleryActionButton.getAttribute("data-image-gallery-action");
      const imageId = imageGalleryActionButton.getAttribute("data-image-id");
      if ((action === "toggle" || action === "remove") && imageId) {
        options.onChangeValue(options.getNextImageGallerySelectionItems(action, imageId));
        options.onAfterChange();
      }
      return;
    }

    if (options.isProductListField) {
      const productCard = target?.closest("[data-product-open-gallery]") as HTMLElement | null;
      if (productCard) {
        const productId = productCard.getAttribute("data-product-open-gallery");
        if (productId) {
          event.preventDefault();
          event.stopPropagation();
          options.openProductGallery(productId);
        }
        return;
      }
    }

    if (options.isImageGalleryField) {
      const imageCard = target?.closest("[data-image-open-gallery]") as HTMLElement | null;
      if (imageCard) {
        const imageId = imageCard.getAttribute("data-image-open-gallery");
        if (imageId) {
          event.preventDefault();
          event.stopPropagation();
          options.openImageGallery(imageId);
        }
        return;
      }
    }

    const qrAction = target?.closest("[data-qr-action]") as HTMLElement | null;
    if (qrAction) {
      event.preventDefault();
      event.stopPropagation();
      const action = qrAction.getAttribute("data-qr-action");
      if (action === "start") {
        options.startQrCamera();
      } else if (action === "scan") {
        options.scanQrCamera();
      } else if (action === "stop") {
        options.stopQrCamera();
      }
      return;
    }

    const documentScanSlotButton = target?.closest("[data-document-scan-slot]") as HTMLElement | null;
    if (documentScanSlotButton) {
      event.preventDefault();
      event.stopPropagation();
      const slotIndex = Number(documentScanSlotButton.getAttribute("data-document-scan-slot"));
      if (!Number.isNaN(slotIndex)) {
        options.setActiveDocumentScanSlot(slotIndex);
        options.refreshSelection();
        if (input instanceof HTMLInputElement && input.type === "file") {
          input.click();
        }
      }
      return;
    }

    const removeButton = target?.closest("[data-remove-file-index]") as HTMLElement | null;
    if (!removeButton) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    const fileIndex = Number(removeButton.getAttribute("data-remove-file-index"));
    if (Number.isNaN(fileIndex)) {
      return;
    }

    options.removeSelectedFile(fileIndex);
  });

  if (options.isQuizField && options.isOpenQuizField) {
    selectionElement.addEventListener("input", (event) => {
      const target = event.target as HTMLElement | null;
      const answerInput = target?.closest("[data-quiz-open-answer]") as HTMLTextAreaElement | null;
      if (!answerInput) {
        return;
      }

      options.onChangeValue(answerInput.value);
      options.onAfterChange();
    });
  }

  if (options.isQuizField && !options.isOpenQuizField) {
    selectionElement.addEventListener("keydown", (event) => {
      const target = event.target as HTMLElement | null;
      const answerCard = target?.closest("[data-quiz-answer-action]") as HTMLElement | null;
      if (!answerCard || (event.key !== "Enter" && event.key !== " ")) {
        return;
      }

      event.preventDefault();
      answerCard.click();
    });
  }

  if (!options.isFileField) {
    return;
  }

  fileDropTarget.addEventListener("dragenter", (event) => {
    event.preventDefault();
    options.setFileDragState(true);
  });
  fileDropTarget.addEventListener("dragover", (event) => {
    event.preventDefault();
    options.setFileDragState(true);
  });
  fileDropTarget.addEventListener("dragleave", (event) => {
    const relatedTarget = event.relatedTarget as Node | null;
    if (relatedTarget && fileDropTarget.contains(relatedTarget)) {
      return;
    }
    options.setFileDragState(false);
  });
  fileDropTarget.addEventListener("drop", (event) => {
    event.preventDefault();
    options.setFileDragState(false);
    const droppedFiles = Array.from(event.dataTransfer?.files || []);
    options.applyDroppedFiles(droppedFiles);
  });
}

export function applyFieldValuePresentation(options: {
  input: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement | null;
  inputElement: HTMLElement | null;
  selectionElement: HTMLElement | null;
  errorElement: HTMLElement | null;
  fieldConfig: { name: string; multiple?: boolean; accept?: string; capture?: string };
  value: any;
  settingField: boolean;
  fieldViewOnly: boolean;
  isQrScanField: boolean;
  isProductListField: boolean;
  isImageGalleryField: boolean;
  isQuizField: boolean;
  isChoiceListField: boolean;
  isOpenQuizField: boolean;
  applyFieldViewPresentation: () => void;
  renderFileSelection: () => void;
  renderProductListSelection: () => void;
  renderImageGallerySelection: () => void;
  renderQuizSelection: () => void;
  renderChoiceListSelection: () => void;
  getProductCartItems: () => any[];
  getImageGallerySelectionItems: () => any[];
  getQuizSelectionItems: () => any[];
  isHybridMode: boolean;
  renderHybridView: () => void;
}): void {
  const {
    input,
    inputElement,
    value,
  } = options;

  const syncNativeFileInput = (fileInput: HTMLInputElement, nextValue: unknown) => {
    const nextFiles = Array.isArray(nextValue)
      ? nextValue.filter((entry): entry is File => entry instanceof File)
      : nextValue instanceof File
        ? [nextValue]
        : [];

    if (!nextFiles.length || typeof DataTransfer === "undefined") {
      fileInput.value = "";
      return;
    }

    try {
      const transfer = new DataTransfer();
      (fileInput.multiple ? nextFiles : [nextFiles[0]]).forEach((file) => {
        transfer.items.add(file);
      });
      fileInput.files = transfer.files;
    } catch {
      // Some browsers block programmatic FileList assignment.
      fileInput.value = "";
    }
  };

  if (options.settingField) {
    if (inputElement instanceof HTMLInputElement) {
      inputElement.value = value === undefined || value === null
        ? ""
        : typeof value === "string"
          ? value
          : JSON.stringify(value);
    }
  } else if (options.fieldViewOnly) {
    options.applyFieldViewPresentation();
  } else if (input instanceof HTMLInputElement && input.type === "checkbox") {
    input.checked = value;
  } else if (input instanceof HTMLInputElement && input.type === "file") {
    input.multiple = false; // single file only
    input.accept = typeof options.fieldConfig.accept === "string" ? options.fieldConfig.accept : "";
    if (typeof options.fieldConfig.capture === "string" && options.fieldConfig.capture) {
      input.setAttribute("capture", options.fieldConfig.capture);
    } else {
      input.removeAttribute("capture");
    }
    options.renderFileSelection();
    if (!value || (Array.isArray(value) && !value.length) || (typeof value === "string" && options.isQrScanField)) {
      input.value = "";
    } else {
      syncNativeFileInput(input, value);
    }
  } else if (options.isProductListField) {
    options.renderProductListSelection();
    if (input) {
      input.value = JSON.stringify(options.getProductCartItems());
    }
  } else if (options.isImageGalleryField) {
    options.renderImageGallerySelection();
    if (input) {
      input.value = JSON.stringify(options.getImageGallerySelectionItems());
    }
  } else if (options.isQuizField) {
    options.renderQuizSelection();
    if (input) {
      input.value = options.isOpenQuizField
        ? (typeof value === "string" ? value : "")
        : JSON.stringify(options.getQuizSelectionItems());
    }
  } else if (options.isChoiceListField) {
    options.renderChoiceListSelection();
    if (input) {
      input.value = Array.isArray(value) ? JSON.stringify(value) : String(value ?? "");
    }
  } else if (input instanceof HTMLSelectElement && input.multiple) {
    const selectedValues = Array.isArray(value)
      ? value.map((entry) => String(entry))
      : [];
    Array.from(input.options).forEach((option) => {
      option.selected = selectedValues.includes(option.value);
    });
  } else {
    if (input) {
      input.value = value === undefined ? "" : value;
    }
  }

  if (options.isHybridMode && inputElement) {
    options.renderHybridView();
  }
}
