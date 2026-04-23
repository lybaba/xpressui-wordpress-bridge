import TChoice from "./TChoice";
import TFieldConfig from "./TFieldConfig";

export function normalizeRemoteChoices(payload: any, fieldConfig: TFieldConfig): TChoice[] {
  const optionList = Array.isArray(payload)
    ? payload
    : Array.isArray(payload?.items)
      ? payload.items
      : Array.isArray(payload?.options)
        ? payload.options
        : [];
  const labelKey = fieldConfig.optionsLabelKey || "label";
  const valueKey = fieldConfig.optionsValueKey || "value";

  return optionList
    .map((item: any) => {
      if (typeof item === "string") {
        return { value: item, label: item };
      }

      return {
        value: String(item?.[valueKey] ?? item?.value ?? ""),
        label: String(item?.[labelKey] ?? item?.label ?? item?.[valueKey] ?? ""),
      };
    })
    .filter((choice: TChoice) => Boolean(choice.value));
}

export function syncSelectOptionChildren(
  fieldElement: HTMLSelectElement,
  options: TChoice[],
): void {
  const desiredOptions = fieldElement.multiple
    ? options
    : [{ value: "", label: "" }, ...options];
  const desiredValues = new Set(desiredOptions.map((choice) => choice.value));

  desiredOptions.forEach((choice, index) => {
    let option =
      Array.from(fieldElement.options).find((candidate) => candidate.value === choice.value) ||
      null;
    if (!option) {
      option = document.createElement("option");
    }

    option.value = choice.value;
    option.textContent = choice.label;
    const currentOptionAtIndex = fieldElement.options[index] || null;
    if (currentOptionAtIndex !== option) {
      fieldElement.insertBefore(option, currentOptionAtIndex);
    }
  });

  Array.from(fieldElement.options).forEach((option) => {
    if (!desiredValues.has(option.value)) {
      option.remove();
    }
  });
}
