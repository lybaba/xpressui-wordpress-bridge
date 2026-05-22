type TChoiceCatalogHost = any;

function createUnsupportedRuntimeMessage(fieldName: string, capability: string): string {
  return `The ${capability} field "${fieldName}" is not supported by the XPressUI light runtime.`;
}

export function createChoiceCatalogRuntime(host: TChoiceCatalogHost) {
  const emitUnsupported = (fieldConfig: { name: string }, capability: string) => {
    const error = new Error(createUnsupportedRuntimeMessage(fieldConfig.name, capability));
    host.emitFormEvent("xpressui:runtime-unsupported", {
      values: host.engine.normalizeValues(host.form?.getState().values || {}),
      formConfig: host.formConfig,
      submit: host.formConfig?.submit,
      error,
      result: {
        field: fieldConfig.name,
        capability,
        runtimeTier: "light",
      },
    });
  };

  return {
    isDateRange(fieldConfig: any): boolean {
      return (fieldConfig as any).choiceCatalogSnapshot?.kind === "date_range"
        || (fieldConfig as any).choiceCatalogRef?.catalogKind === "date_range"
        || (fieldConfig.choices || []).some((choice: any) => typeof choice?.date === "string");
    },

    isMonthRange(fieldConfig: any): boolean {
      return (fieldConfig as any).choiceCatalogSnapshot?.kind === "month_range"
        || (fieldConfig as any).choiceCatalogRef?.catalogKind === "month_range"
        || (fieldConfig.choices || []).some((choice: any) => typeof choice?.month === "string");
    },

    getMonthRangeBaselineAmountDisplay(_fieldConfig: any): string {
      return "";
    },

    updateMonthRangeTotal(_fieldConfig: any, _value: any): void {
      return;
    },

    renderDateRangeSelection(fieldConfig: any, _value: any, _selectionElement: HTMLElement | null): void {
      emitUnsupported(fieldConfig, "date-range");
    },

    renderMonthRangeSelection(fieldConfig: any, _value: any, _selectionElement: HTMLElement | null): void {
      emitUnsupported(fieldConfig, "month-range");
    },

    getNextChoiceMonthSelectionValue(_fieldConfig: any, currentValue: any, _monthKey: string): any {
      return currentValue;
    },

    getNextChoiceYearSelectionValue(_fieldConfig: any, currentValue: any, _year: string): any {
      return currentValue;
    },
  };
}
