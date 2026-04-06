type TQuizRuntimeHost = any;

function createUnsupportedRuntimeMessage(fieldName: string): string {
  return `The quiz field "${fieldName}" is not supported by the XPressUI light runtime.`;
}

export function createQuizRuntime(host: TQuizRuntimeHost) {
  const emitUnsupported = (fieldConfig: { name: string }) => {
    const error = new Error(createUnsupportedRuntimeMessage(fieldConfig.name));
    host.emitFormEvent("xpressui:runtime-unsupported", {
      values: host.engine.normalizeValues(host.form?.getState().values || {}),
      formConfig: host.formConfig,
      submit: host.formConfig?.submit,
      error,
      result: {
        field: fieldConfig.name,
        capability: "quiz",
        runtimeTier: "light",
      },
    });
    return error;
  };

  return {
    getQuizCatalog: (_fieldConfig: any) => [],
    getQuizSelectionItems: (_value: any) => [],
    getQuizSelectionLimit: (_fieldConfig: any) => 0,
    getNextQuizSelectionItems: (fieldConfig: any, currentValue: any, _answerId: string) => {
      emitUnsupported(fieldConfig);
      return currentValue;
    },
    renderQuizSelection(fieldConfig: any, _value: any, selectionElement: HTMLElement | null) {
      if (!selectionElement) {
        return;
      }

      const message = host.ensureSelectionChild(
        selectionElement,
        `[data-quiz-unsupported="${fieldConfig.name}"]`,
        "div",
        "rounded border border-warning/40 bg-warning/10 px-3 py-2 text-xs",
        "data-quiz-unsupported",
        fieldConfig.name,
      );
      message.textContent = createUnsupportedRuntimeMessage(fieldConfig.name);
    },
  };
}
