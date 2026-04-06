type TDocumentQrRuntimeHost = any;

function createUnsupportedRuntimeMessage(fieldName: string, capability: string): string {
  return `The ${capability} field "${fieldName}" is not supported by the XPressUI light runtime.`;
}

export function createDocumentQrRuntime(host: TDocumentQrRuntimeHost) {
  const emitUnsupported = (fieldConfig: { name: string }, reason: string) => {
    const error = new Error(createUnsupportedRuntimeMessage(fieldConfig.name, reason));
    host.emitFormEvent("xpressui:runtime-unsupported", {
      values: host.engine.normalizeValues(host.form?.getState().values || {}),
      formConfig: host.formConfig,
      submit: host.formConfig?.submit,
      error,
      result: {
        field: fieldConfig.name,
        capability: reason,
        runtimeTier: "light",
      },
    });
    return error;
  };

  return {
    getBarcodeDetector: () => null,
    getTextDetector: () => null,
    getMrzCharValue: (_char: string) => 0,
    computeMrzChecksum: (_input: string) => 0,
    validateMrzChecksum: (_source: string, _checkDigit?: string) => undefined,
    computeMrzValidity: (_checksums?: Record<string, boolean | undefined>) => undefined,
    parseMrz: (_text: string) => null,
    getDocumentCropBounds: (_width: number, _height: number) => ({ x: 0, y: 0, width: 1, height: 1 }),
    getDocumentPerspectiveCorners: (_bounds: { x: number; y: number; width: number; height: number }) => ({
      topLeft: { x: 0, y: 0 },
      topRight: { x: 0, y: 0 },
      bottomRight: { x: 0, y: 0 },
      bottomLeft: { x: 0, y: 0 },
    }),
    drawPerspectiveCorrectedDocument: () => undefined,
    cropDocumentScanFile: async (_fieldConfig: { name: string }, file: File) => file,
    analyzeDocumentScanFile: async (fieldConfig: { name: string }) => {
      emitUnsupported(fieldConfig, "document-scan");
    },
    assignQrVideoStream: (_fieldName: string) => undefined,
    clearQrScanTimer(fieldName: string) {
      const timerId = host.qrScannerTimers[fieldName];
      if (typeof timerId === "number") {
        window.clearTimeout(timerId);
      }
      host.qrScannerTimers[fieldName] = null;
    },
    stopQrCamera(fieldName: string, rerender: boolean = true) {
      host.clearQrScanTimer(fieldName);
      host.qrScannerRunning[fieldName] = false;
      const stream = host.qrScannerStreams[fieldName];
      stream?.getTracks?.().forEach((track: MediaStreamTrack) => track.stop());
      host.qrScannerStreams[fieldName] = null;
      host.qrScannerState[fieldName] = { status: "idle" };
      if (rerender) {
        const fieldConfig = host.engine.getField(fieldName);
        const selectionElement = host.querySelector(`#${fieldName}_selection`) as HTMLElement | null;
        if (fieldConfig) {
          host.renderFileSelection(fieldConfig, host.getFieldValue(fieldName), selectionElement);
        }
      }
    },
    stopAllQrCameras() {
      Object.keys(host.qrScannerStreams).forEach((fieldName) => {
        host.stopQrCamera(fieldName, false);
      });
    },
    scheduleContinuousQrScan: (_fieldConfig: { name: string }) => undefined,
    async startQrCamera(fieldConfig: { name: string }) {
      host.qrScannerState[fieldConfig.name] = {
        status: "error",
        message: createUnsupportedRuntimeMessage(fieldConfig.name, "qr-scan"),
      };
      emitUnsupported(fieldConfig, "qr-scan");
      const selectionElement = host.querySelector(`#${fieldConfig.name}_selection`) as HTMLElement | null;
      host.renderFileSelection(fieldConfig, host.getFieldValue(fieldConfig.name), selectionElement);
    },
    async scanQrFromLiveVideo(fieldConfig: { name: string }) {
      emitUnsupported(fieldConfig, "qr-scan");
    },
    async decodeQrScanFile(fieldConfig: { name: string }, _file: File) {
      emitUnsupported(fieldConfig, "qr-scan");
      return null;
    },
    renderDocumentScanSelection(fieldConfig: { name: string }, _selectedFiles: any[], selectionElement: HTMLElement) {
      const message = host.ensureSelectionChild(
        selectionElement,
        `[data-document-scan-unsupported="${fieldConfig.name}"]`,
        "div",
        "rounded border border-warning/40 bg-warning/10 px-3 py-2 text-xs",
        "data-document-scan-unsupported",
        fieldConfig.name,
      );
      message.textContent = createUnsupportedRuntimeMessage(fieldConfig.name, "document-scan");
    },
    renderQrSelection(fieldConfig: { name: string }, _value: any, selectionElement: HTMLElement) {
      const message = host.ensureSelectionChild(
        selectionElement,
        `[data-qr-unsupported="${fieldConfig.name}"]`,
        "div",
        "rounded border border-warning/40 bg-warning/10 px-3 py-2 text-xs",
        "data-qr-unsupported",
        fieldConfig.name,
      );
      message.textContent = createUnsupportedRuntimeMessage(fieldConfig.name, "qr-scan");
    },
  };
}
