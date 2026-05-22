type RepeaterValidationResult = {
  valid: boolean;
  message: string;
  focusNode: HTMLElement | HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement | null;
};

function getRepeaterRows(repeaterNode: HTMLElement): HTMLElement[] {
  return Array.from(repeaterNode.querySelectorAll("[data-repeater-row]")).filter(
    (row): row is HTMLElement => row instanceof HTMLElement,
  );
}

function getRepeaterLimit(repeaterNode: HTMLElement, key: "repeaterMin" | "repeaterMax", fallback: number): number {
  const rawValue = repeaterNode.dataset[key] || "";
  const parsedValue = Number.parseInt(rawValue, 10);
  return Number.isFinite(parsedValue) ? parsedValue : fallback;
}

function dispatchRepeaterValueChange(hiddenInput: HTMLInputElement): void {
  hiddenInput.dispatchEvent(new Event("input", { bubbles: true }));
  hiddenInput.dispatchEvent(new Event("change", { bubbles: true }));
}

function replaceRepeaterIndexPlaceholders(root: HTMLElement | null, index: number): void {
  if (!root) {
    return;
  }

  const indexText = String(index);
  const numberText = String(index + 1);
  const nodes = [root, ...Array.from(root.querySelectorAll("*"))];
  nodes.forEach((node) => {
    Array.from(node.attributes || []).forEach((attribute) => {
      if (!attribute.value.includes("__INDEX__") && !attribute.value.includes("__NUMBER__")) {
        return;
      }
      node.setAttribute(
        attribute.name,
        attribute.value.replaceAll("__INDEX__", indexText).replaceAll("__NUMBER__", numberText),
      );
    });
    if (node instanceof HTMLElement && Object.prototype.hasOwnProperty.call(node.dataset, "repeaterRowNumber")) {
      node.textContent = numberText;
    }
  });
}

function serializeRepeater(repeaterNode: HTMLElement): void {
  const hiddenInput = repeaterNode.querySelector("[data-repeater-value]");
  if (!(hiddenInput instanceof HTMLInputElement)) {
    return;
  }

  const rowValues = getRepeaterRows(repeaterNode).map((row) => {
    const value: Record<string, any> = {};
    const controls = Array.from(row.querySelectorAll("[data-repeater-control]")).filter(
      (control): control is HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement =>
        control instanceof HTMLInputElement
        || control instanceof HTMLTextAreaElement
        || control instanceof HTMLSelectElement,
    );

    controls.forEach((control) => {
      const controlName = control.dataset.repeaterControlName;
      const controlType = control.dataset.repeaterControlType || "text";
      if (!controlName) {
        return;
      }

      if (control instanceof HTMLInputElement && controlType === "checkboxes") {
        if (!Array.isArray(value[controlName])) {
          value[controlName] = [];
        }
        if (control.checked) {
          value[controlName].push(control.value);
        }
        return;
      }

      if (control instanceof HTMLInputElement && controlType === "radio-buttons") {
        if (control.checked) {
          value[controlName] = control.value;
        } else if (value[controlName] === undefined) {
          value[controlName] = "";
        }
        return;
      }

      if (control instanceof HTMLInputElement && controlType === "checkbox") {
        value[controlName] = Boolean(control.checked);
        return;
      }

      value[controlName] = control.value || "";
    });

    return value;
  });

  hiddenInput.value = JSON.stringify(rowValues);
  dispatchRepeaterValueChange(hiddenInput);
}

function setRepeaterValidity(repeaterNode: HTMLElement, message: string): void {
  const hiddenInput = repeaterNode.querySelector("[data-repeater-value]");
  const fieldName = repeaterNode.dataset.repeaterField || "";
  const errorNode = fieldName
    ? repeaterNode.ownerDocument.querySelector(`[data-field-error="${fieldName}"]`)
    : null;

  if (hiddenInput instanceof HTMLInputElement) {
    hiddenInput.setAttribute("aria-invalid", message ? "true" : "false");
  }
  if (errorNode instanceof HTMLElement) {
    errorNode.textContent = message;
    errorNode.style.display = message ? "" : "none";
    errorNode.setAttribute("aria-hidden", message ? "false" : "true");
  }
  repeaterNode.classList.toggle("is-invalid", Boolean(message));
}

function clearRepeaterRowValidation(rowNode: HTMLElement): void {
  rowNode.classList.remove("is-invalid");
  rowNode.querySelectorAll(".is-invalid").forEach((node) => node.classList.remove("is-invalid"));
  rowNode.querySelectorAll("[data-repeater-control]").forEach((control) => {
    if (
      control instanceof HTMLInputElement
      || control instanceof HTMLTextAreaElement
      || control instanceof HTMLSelectElement
    ) {
      control.setAttribute("aria-invalid", "false");
    }
  });
}

function controlHasRepeaterValue(control: HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement): boolean {
  const controlType = control.dataset.repeaterControlType || "text";
  if (control instanceof HTMLInputElement && controlType === "checkbox") {
    return control.checked;
  }
  if (control instanceof HTMLInputElement && (controlType === "checkboxes" || controlType === "radio-buttons")) {
    const row = control.closest("[data-repeater-row]");
    const controlName = control.dataset.repeaterControlName || "";
    return Boolean(row?.querySelector(`[data-repeater-control-name="${controlName}"]:checked`));
  }
  return String(control.value || "").trim() !== "";
}

function validateRepeaterRow(rowNode: HTMLElement, repeaterLabel: string): RepeaterValidationResult {
  clearRepeaterRowValidation(rowNode);
  const requiredSubfields = Array.from(rowNode.querySelectorAll('[data-repeater-subfield-required="true"]')).filter(
    (node): node is HTMLElement => node instanceof HTMLElement,
  );

  for (const subfieldNode of requiredSubfields) {
    const controls = Array.from(subfieldNode.querySelectorAll("[data-repeater-control]")).filter(
      (control): control is HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement =>
        control instanceof HTMLInputElement
        || control instanceof HTMLTextAreaElement
        || control instanceof HTMLSelectElement,
    );
    if (controls.some((control) => controlHasRepeaterValue(control))) {
      continue;
    }

    const subfieldLabel = subfieldNode.dataset.repeaterSubfieldLabel || "This field";
    const message = `${repeaterLabel}: ${subfieldLabel} is required.`;
    subfieldNode.classList.add("is-invalid");
    rowNode.classList.add("is-invalid");
    controls.forEach((control) => control.setAttribute("aria-invalid", "true"));

    return {
      valid: false,
      message,
      focusNode: controls[0] || subfieldNode,
    };
  }

  return { valid: true, message: "", focusNode: null };
}

function validateRepeater(repeaterNode: HTMLElement): RepeaterValidationResult {
  serializeRepeater(repeaterNode);
  setRepeaterValidity(repeaterNode, "");

  let rows = getRepeaterRows(repeaterNode);
  const minRows = Math.max(1, getRepeaterLimit(repeaterNode, "repeaterMin", 0));
  const maxRows = getRepeaterLimit(repeaterNode, "repeaterMax", 50);
  const label = repeaterNode.dataset.repeaterLabel || "Repeater";
  const minimumRows = minRows;

  if (rows.length === 0) {
    appendRepeaterRow(repeaterNode);
    rows = getRepeaterRows(repeaterNode);
  }

  if (rows.length < minimumRows) {
    const message = minimumRows === 1
      ? `${label}: add at least one row.`
      : `${label}: add at least ${minimumRows} rows.`;
    setRepeaterValidity(repeaterNode, message);
    return {
      valid: false,
      message,
      focusNode: repeaterNode.querySelector("[data-repeater-add]") as HTMLElement | null || repeaterNode,
    };
  }

  if (rows.length > maxRows) {
    const message = `${label}: keep ${maxRows} rows or fewer.`;
    setRepeaterValidity(repeaterNode, message);
    return { valid: false, message, focusNode: repeaterNode };
  }

  for (const row of rows) {
    const result = validateRepeaterRow(row, label);
    if (!result.valid) {
      setRepeaterValidity(repeaterNode, result.message);
      return result;
    }
  }

  return { valid: true, message: "", focusNode: null };
}

function focusRepeaterValidationTarget(focusNode: HTMLElement | null): void {
  if (!focusNode) {
    return;
  }
  focusNode.focus({ preventScroll: true });
  focusNode.scrollIntoView({ block: "center", behavior: "smooth" });
}

function updateRepeaterControls(repeaterNode: HTMLElement): void {
  const minRows = Math.max(1, getRepeaterLimit(repeaterNode, "repeaterMin", 0));
  const maxRows = getRepeaterLimit(repeaterNode, "repeaterMax", 50);
  const rows = getRepeaterRows(repeaterNode);
  if (rows.length === 0) {
    appendRepeaterRow(repeaterNode);
    return;
  }
  const singleRow = maxRows === 1;

  rows.forEach((row, index) => {
    replaceRepeaterIndexPlaceholders(row, index);
    const removeButton = row.querySelector("[data-repeater-remove]");
    if (removeButton instanceof HTMLButtonElement) {
      removeButton.hidden = singleRow;
      removeButton.disabled = singleRow || rows.length <= minRows;
    }
  });

  const addButton = repeaterNode.querySelector("[data-repeater-add]");
  if (addButton instanceof HTMLButtonElement) {
    addButton.hidden = singleRow;
    addButton.disabled = singleRow || rows.length >= maxRows;
  }
}

function appendRepeaterRow(repeaterNode: HTMLElement): void {
  const rowsNode = repeaterNode.querySelector("[data-repeater-rows]");
  const templateNode = repeaterNode.querySelector("template[data-repeater-template]");
  if (!(rowsNode instanceof HTMLElement) || !(templateNode instanceof HTMLTemplateElement)) {
    return;
  }

  const currentRows = rowsNode.querySelectorAll("[data-repeater-row]").length;
  const maxRows = getRepeaterLimit(repeaterNode, "repeaterMax", 50);
  if (currentRows >= maxRows) {
    return;
  }

  const fragment = templateNode.content.cloneNode(true);
  if (!(fragment instanceof DocumentFragment)) {
    return;
  }
  const rowTemplate = fragment.querySelector("[data-repeater-row]");
  replaceRepeaterIndexPlaceholders(rowTemplate instanceof HTMLElement ? rowTemplate : null, currentRows);
  rowsNode.appendChild(fragment);
  updateRepeaterControls(repeaterNode);
  serializeRepeater(repeaterNode);
}

function removeRepeaterRow(repeaterNode: HTMLElement, rowNode: HTMLElement): void {
  const minRows = Math.max(1, getRepeaterLimit(repeaterNode, "repeaterMin", 0));
  const rows = getRepeaterRows(repeaterNode);
  if (rows.length <= minRows) {
    return;
  }
  rowNode.remove();
  updateRepeaterControls(repeaterNode);
  serializeRepeater(repeaterNode);
}

function syncRepeaterRadioGroup(control: HTMLInputElement): void {
  const row = control.closest("[data-repeater-row]");
  const controlName = control.dataset.repeaterControlName;
  if (!(row instanceof HTMLElement) || !controlName || !control.checked) {
    return;
  }
  row.querySelectorAll(`[data-repeater-control-name="${controlName}"]`).forEach((candidate) => {
    if (candidate !== control && candidate instanceof HTMLInputElement) {
      candidate.checked = false;
    }
  });
}

export function createRepeaterRuntime(host: HTMLElement) {
  let attached = false;

  const sync = (root: ParentNode = host): void => {
    root.querySelectorAll("[data-repeater-field]").forEach((repeaterNode) => {
      if (!(repeaterNode instanceof HTMLElement)) {
        return;
      }
      updateRepeaterControls(repeaterNode);
      serializeRepeater(repeaterNode);
    });
  };

  const validateAll = (
    root: ParentNode = host,
    options: { includeHidden?: boolean } = {},
  ): { valid: boolean; focusNode: HTMLElement | null } => {
    const includeHidden = options.includeHidden !== false;
    const repeaters = Array.from(root.querySelectorAll("[data-repeater-field]")).filter(
      (node): node is HTMLElement => node instanceof HTMLElement,
    );
    for (const repeaterNode of repeaters) {
      if (!includeHidden && repeaterNode.closest('[data-step-hidden="true"]')) {
        continue;
      }
      const result = validateRepeater(repeaterNode);
      if (!result.valid) {
        return {
          valid: false,
          focusNode: result.focusNode instanceof HTMLElement ? result.focusNode : repeaterNode,
        };
      }
    }
    return { valid: true, focusNode: null };
  };

  const attach = (root: HTMLElement = host): void => {
    sync(root);
    if (attached) {
      return;
    }
    attached = true;

    root.addEventListener("click", (event) => {
      const target = event.target instanceof Element ? event.target : null;
      const nextButton = target?.closest('[data-step-action="next"]');
      if (!(nextButton instanceof HTMLElement) || !root.contains(nextButton)) {
        return;
      }
      const validationRoot = nextButton.closest("form") || root;
      const result = validateAll(validationRoot, { includeHidden: false });
      if (!result.valid) {
        event.preventDefault();
        event.stopImmediatePropagation();
        focusRepeaterValidationTarget(result.focusNode);
      }
    }, true);

    root.addEventListener("click", (event) => {
      const target = event.target instanceof Element ? event.target : null;
      if (!target) {
        return;
      }

      const addButton = target.closest("[data-repeater-add]");
      if (addButton) {
        const repeaterNode = addButton.closest("[data-repeater-field]");
        if (repeaterNode instanceof HTMLElement && root.contains(repeaterNode)) {
          event.preventDefault();
          event.stopPropagation();
          appendRepeaterRow(repeaterNode);
        }
        return;
      }

      const removeButton = target.closest("[data-repeater-remove]");
      if (!removeButton) {
        return;
      }
      const repeaterNode = removeButton.closest("[data-repeater-field]");
      const rowNode = removeButton.closest("[data-repeater-row]");
      if (repeaterNode instanceof HTMLElement && rowNode instanceof HTMLElement && root.contains(repeaterNode)) {
        event.preventDefault();
        event.stopPropagation();
        removeRepeaterRow(repeaterNode, rowNode);
      }
    });

    root.addEventListener("input", (event) => {
      if (!(event.target instanceof Element) || !event.target.matches("[data-repeater-control]")) {
        return;
      }
      const repeaterNode = event.target.closest("[data-repeater-field]");
      if (repeaterNode instanceof HTMLElement && root.contains(repeaterNode)) {
        serializeRepeater(repeaterNode);
      }
    });

    root.addEventListener("change", (event) => {
      if (!(event.target instanceof Element) || !event.target.matches("[data-repeater-control]")) {
        return;
      }
      const repeaterNode = event.target.closest("[data-repeater-field]");
      if (!(repeaterNode instanceof HTMLElement) || !root.contains(repeaterNode)) {
        return;
      }
      if (event.target instanceof HTMLInputElement && event.target.dataset.repeaterControlType === "radio-buttons") {
        syncRepeaterRadioGroup(event.target);
      }
      serializeRepeater(repeaterNode);
    });
  };

  return {
    attach,
    sync,
    validateAll,
  };
}
