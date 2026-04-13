type TDocumentQrRuntimeHost = any;

function createUnsupportedRuntimeMessage(fieldName: string, capability: string): string {
  return `The ${capability} field "${fieldName}" is not supported by the XPressUI light runtime.`;
}

export function createDocumentQrRuntime(host: TDocumentQrRuntimeHost) {
  return {
    getBarcodeDetector() {
      const barcodeDetector = (globalThis as any).BarcodeDetector;
      if (!barcodeDetector) {
        return null;
      }

      try {
        return new barcodeDetector({ formats: ["qr_code"] });
      } catch {
        try {
          return new barcodeDetector();
        } catch {
          return null;
        }
      }
    },

    getTextDetector() {
      const textDetector = (globalThis as any).TextDetector;
      if (!textDetector) {
        return null;
      }

      try {
        return new textDetector();
      } catch {
        return null;
      }
    },

    getMrzCharValue(char: string) {
      if (char >= "0" && char <= "9") {
        return Number(char);
      }

      if (char >= "A" && char <= "Z") {
        return char.charCodeAt(0) - 55;
      }

      return 0;
    },

    computeMrzChecksum(input: string) {
      const weights = [7, 3, 1];
      return input
        .split("")
        .reduce((sum, char, index) => sum + host.getMrzCharValue(char) * weights[index % 3], 0) % 10;
    },

    validateMrzChecksum(source: string, checkDigit?: string) {
      if (!checkDigit || checkDigit === "<") {
        return undefined;
      }

      if (!/^\d$/.test(checkDigit)) {
        return false;
      }

      return host.computeMrzChecksum(source) === Number(checkDigit);
    },

    computeMrzValidity(checksums?: Record<string, boolean | undefined>) {
      if (!checksums) {
        return undefined;
      }

      const values = Object.values(checksums).filter((entry) => entry !== undefined);
      if (!values.length) {
        return undefined;
      }

      return values.every((entry) => entry === true);
    },

    parseMrz(text: string) {
      const lines = text
        .split(/\r?\n/)
        .map((line) => line.trim().toUpperCase())
        .filter((line) => /^[A-Z0-9<]{20,}$/.test(line));

      if (lines.length >= 3) {
        const [line1, line2, line3] = lines.slice(-3);
        const nameBlock = line3.split("<<");
        const documentNumberSource = line1.slice(5, 14);
        const birthDateSource = line2.slice(0, 6);
        const expiryDateSource = line2.slice(8, 14);
        const compositeSource = `${line1.slice(0, 30)}${line2.slice(0, 7)}${line2.slice(8, 15)}${line2.slice(18, 29)}`;
        const checksums = {
          documentNumber: host.validateMrzChecksum(documentNumberSource, line1.slice(14, 15)),
          birthDate: host.validateMrzChecksum(birthDateSource, line2.slice(6, 7)),
          expiryDate: host.validateMrzChecksum(expiryDateSource, line2.slice(14, 15)),
          composite: host.validateMrzChecksum(compositeSource, line2.slice(29, 30)),
        };
        return {
          format: "TD1" as const,
          lines: [line1, line2, line3],
          documentCode: line1.slice(0, 2).replace(/</g, ""),
          issuingCountry: line1.slice(2, 5).replace(/</g, ""),
          documentNumber: documentNumberSource.replace(/</g, ""),
          birthDate: birthDateSource.replace(/</g, ""),
          sex: line2.slice(7, 8).replace(/</g, ""),
          expiryDate: expiryDateSource.replace(/</g, ""),
          nationality: line2.slice(15, 18).replace(/</g, ""),
          surnames: (nameBlock[0] || "").split("<").filter(Boolean),
          givenNames: (nameBlock.slice(1).join("<<") || "").split("<").filter(Boolean),
          checksums,
          valid: host.computeMrzValidity(checksums),
        };
      }

      if (lines.length < 2) {
        return null;
      }

      const [line1, line2] = lines.slice(-2);
      const nameBlock = line1.slice(5).split("<<");
      const surnames = (nameBlock[0] || "")
        .split("<")
        .filter(Boolean);
      const givenNames = (nameBlock.slice(1).join("<<") || "")
        .split("<")
        .filter(Boolean);
      const format = (line1.length >= 40 || line2.length >= 40 ? "TD3" : "TD2") as "TD2" | "TD3";
      const isTd3 = format === "TD3";
      const documentNumberSource = line2.slice(0, 9);
      const birthDateSource = isTd3 ? line2.slice(13, 19) : line2.slice(0, 6);
      const expiryDateSource = isTd3 ? line2.slice(21, 27) : line2.slice(8, 14);
      const checksums = {
        documentNumber: host.validateMrzChecksum(documentNumberSource, line2.slice(9, 10)),
        birthDate: host.validateMrzChecksum(
          birthDateSource,
          isTd3 ? line2.slice(19, 20) : line2.slice(6, 7),
        ),
        expiryDate: host.validateMrzChecksum(
          expiryDateSource,
          isTd3 ? line2.slice(27, 28) : line2.slice(14, 15),
        ),
        composite: host.validateMrzChecksum(
          isTd3
            ? `${line2.slice(0, 10)}${line2.slice(13, 20)}${line2.slice(21, 43)}`
            : `${line2.slice(0, 18)}`,
          isTd3 ? line2.slice(43, 44) : line2.slice(18, 19),
        ),
      };
      return {
        format,
        lines: [line1, line2],
        documentCode: line1.slice(0, 2).replace(/</g, ""),
        issuingCountry: line1.slice(2, 5).replace(/</g, ""),
        documentNumber: documentNumberSource.replace(/</g, ""),
        nationality: (isTd3 ? line2.slice(10, 13) : line2.slice(15, 18)).replace(/</g, ""),
        birthDate: birthDateSource.replace(/</g, ""),
        sex: (isTd3 ? line2.slice(20, 21) : line2.slice(7, 8)).replace(/</g, ""),
        expiryDate: expiryDateSource.replace(/</g, ""),
        surnames,
        givenNames,
        checksums,
        valid: host.computeMrzValidity(checksums),
      };
    },

    getDocumentCropBounds(width: number, height: number) {
      const targetRatio = 1.586;
      const insetX = Math.round(width * 0.06);
      const insetY = Math.round(height * 0.06);
      const safeWidth = Math.max(1, width - insetX * 2);
      const safeHeight = Math.max(1, height - insetY * 2);
      let cropWidth = safeWidth;
      let cropHeight = Math.round(cropWidth / targetRatio);

      if (cropHeight > safeHeight) {
        cropHeight = safeHeight;
        cropWidth = Math.round(cropHeight * targetRatio);
      }

      const x = insetX + Math.max(0, Math.round((safeWidth - cropWidth) / 2));
      const y = insetY + Math.max(0, Math.round((safeHeight - cropHeight) / 2));

      return {
        x,
        y,
        width: Math.max(1, cropWidth),
        height: Math.max(1, cropHeight),
      };
    },

    getDocumentPerspectiveCorners(bounds: { x: number; y: number; width: number; height: number }) {
      const topInset = Math.max(2, Math.round(bounds.width * 0.04));
      const bottomInset = Math.max(1, Math.round(bounds.width * 0.01));

      return {
        topLeft: { x: bounds.x + topInset, y: bounds.y },
        topRight: { x: bounds.x + bounds.width - topInset, y: bounds.y },
        bottomRight: { x: bounds.x + bounds.width - bottomInset, y: bounds.y + bounds.height },
        bottomLeft: { x: bounds.x + bottomInset, y: bounds.y + bounds.height },
      };
    },

    drawPerspectiveCorrectedDocument(
      context: CanvasRenderingContext2D,
      imageBitmap: ImageBitmap,
      bounds: { width: number; height: number },
      corners: {
        topLeft: { x: number; y: number };
        topRight: { x: number; y: number };
        bottomRight: { x: number; y: number };
        bottomLeft: { x: number; y: number };
      },
    ) {
      const destinationWidth = bounds.width;
      const destinationHeight = bounds.height;

      for (let y = 0; y < destinationHeight; y += 1) {
        const ratio = destinationHeight <= 1 ? 0 : y / (destinationHeight - 1);
        const sourceLeftX = corners.topLeft.x + (corners.bottomLeft.x - corners.topLeft.x) * ratio;
        const sourceRightX = corners.topRight.x + (corners.bottomRight.x - corners.topRight.x) * ratio;
        const sourceY = corners.topLeft.y + (corners.bottomLeft.y - corners.topLeft.y) * ratio;
        const sourceWidth = Math.max(1, sourceRightX - sourceLeftX);

        context.drawImage(
          imageBitmap,
          sourceLeftX,
          sourceY,
          sourceWidth,
          1,
          0,
          y,
          destinationWidth,
          1,
        );
      }
    },

    async cropDocumentScanFile(fieldConfig: { name: string }, file: File, slotIndex: number) {
      if (
        typeof document === "undefined" ||
        typeof createImageBitmap !== "function"
      ) {
        return file;
      }

      let imageBitmap: ImageBitmap | null = null;

      try {
        imageBitmap = await createImageBitmap(file);
        const bounds = host.getDocumentCropBounds(imageBitmap.width, imageBitmap.height);
        const corners = host.getDocumentPerspectiveCorners(bounds);

        const canvas = document.createElement("canvas");
        canvas.width = bounds.width;
        canvas.height = bounds.height;
        const context = canvas.getContext("2d");
        if (!context) {
          return file;
        }

        host.drawPerspectiveCorrectedDocument(context, imageBitmap, bounds, corners);

        const blob = await new Promise<Blob | null>((resolve) => {
          canvas.toBlob((result) => resolve(result), file.type || "image/jpeg");
        });

        if (!blob) {
          return file;
        }

        const croppedFile = new File(
          [blob],
          file.name,
          {
            type: blob.type || file.type,
            lastModified: file.lastModified,
          },
        );

        host.emitFormEvent("xpressui:document-scan-cropped", {
          values: host.engine.normalizeValues(host.form?.getState().values || {}),
          formConfig: host.formConfig,
          submit: host.formConfig?.submit,
          result: {
            field: fieldConfig.name,
            slot: slotIndex,
            fileName: croppedFile.name,
            bounds,
            corners,
          },
        });

        host.emitFormEvent("xpressui:document-scan-bounds-detected", {
          values: host.engine.normalizeValues(host.form?.getState().values || {}),
          formConfig: host.formConfig,
          submit: host.formConfig?.submit,
          result: {
            field: fieldConfig.name,
            slot: slotIndex,
            bounds,
            corners,
          },
        });

        return croppedFile;
      } catch {
        return file;
      } finally {
        if (imageBitmap && typeof imageBitmap.close === "function") {
          imageBitmap.close();
        }
      }
    },

    async analyzeDocumentScanFile(fieldConfig: any, file: File, slotIndex: number) {
      if (fieldConfig.enableDocumentOcr === false) {
        return;
      }

      const detector = host.getTextDetector();
      if (!detector || typeof detector.detect !== "function") {
        return;
      }

      let scanSource: any = file;
      let imageBitmap: ImageBitmap | null = null;

      if (typeof createImageBitmap === "function") {
        try {
          imageBitmap = await createImageBitmap(file);
          scanSource = imageBitmap;
        } catch {
          scanSource = file;
        }
      }

      try {
        const textBlocks = await detector.detect(scanSource);
        const detectedText = (textBlocks || [])
          .map((entry: { rawValue?: string }) => String(entry?.rawValue || "").trim())
          .filter(Boolean)
          .join("\n");

        if (!detectedText) {
          return;
        }

        const insight = host.getDocumentScanInsight(fieldConfig);
        insight.textBySlot[slotIndex] = detectedText;

        host.emitFormEvent("xpressui:document-text-detected", {
          values: host.engine.normalizeValues(host.form?.getState().values || {}),
          formConfig: host.formConfig,
          submit: host.formConfig?.submit,
          result: {
            field: fieldConfig.name,
            slot: slotIndex,
            text: detectedText,
          },
        });

        if (fieldConfig.documentTextTargetField && host.form) {
          host.form.change(fieldConfig.documentTextTargetField, detectedText);
        }

        const mrz = host.parseMrz(detectedText);
        let normalizedFields: Record<string, any> | null = null;
        if (mrz) {
          insight.mrzBySlot[slotIndex] = mrz;
          normalizedFields = {
            firstName: mrz.givenNames?.join(" ") || "",
            lastName: mrz.surnames?.join(" ") || "",
            documentNumber: mrz.documentNumber || "",
            nationality: mrz.nationality || "",
            birthDate: mrz.birthDate || "",
            expiryDate: mrz.expiryDate || "",
            sex: mrz.sex || "",
          };
          if (fieldConfig.documentMrzTargetField && host.form) {
            host.form.change(fieldConfig.documentMrzTargetField, mrz);
          }
          if (host.form) {
            if (fieldConfig.documentFirstNameTargetField) {
              host.form.change(fieldConfig.documentFirstNameTargetField, normalizedFields.firstName);
            }
            if (fieldConfig.documentLastNameTargetField) {
              host.form.change(fieldConfig.documentLastNameTargetField, normalizedFields.lastName);
            }
            if (fieldConfig.documentNumberTargetField) {
              host.form.change(fieldConfig.documentNumberTargetField, normalizedFields.documentNumber);
            }
            if (fieldConfig.documentNationalityTargetField) {
              host.form.change(fieldConfig.documentNationalityTargetField, normalizedFields.nationality);
            }
            if (fieldConfig.documentBirthDateTargetField) {
              host.form.change(fieldConfig.documentBirthDateTargetField, normalizedFields.birthDate);
            }
            if (fieldConfig.documentExpiryDateTargetField) {
              host.form.change(fieldConfig.documentExpiryDateTargetField, normalizedFields.expiryDate);
            }
            if (fieldConfig.documentSexTargetField) {
              host.form.change(fieldConfig.documentSexTargetField, normalizedFields.sex);
            }
          }
          host.emitFormEvent("xpressui:document-mrz-detected", {
            values: host.engine.normalizeValues(host.form?.getState().values || {}),
            formConfig: host.formConfig,
            submit: host.formConfig?.submit,
            result: {
              field: fieldConfig.name,
              slot: slotIndex,
              mrz,
              normalized: host.buildDocumentNormalizedContract(
                detectedText,
                mrz || null,
                normalizedFields,
              ),
            },
          });
        }

        const normalizedContract = host.buildDocumentNormalizedContract(
          detectedText,
          mrz || null,
          normalizedFields,
        );
        insight.normalizedBySlot[slotIndex] = normalizedContract;

        host.engine.setDocumentData(fieldConfig.name, {
          text: detectedText,
          mrz,
          fields: normalizedFields,
          normalized: normalizedContract,
        });

        host.emitFormEvent("xpressui:document-data", {
          values: host.engine.normalizeValues(host.form?.getState().values || {}),
          formConfig: host.formConfig,
          submit: host.formConfig?.submit,
          result: {
            field: fieldConfig.name,
            slot: slotIndex,
            text: detectedText,
            mrz,
            normalized: normalizedContract,
          },
        });

        if (mrz) {
          host.emitFormEvent("xpressui:document-fields-populated", {
            values: host.engine.normalizeValues(host.form?.getState().values || {}),
            formConfig: host.formConfig,
            submit: host.formConfig?.submit,
            result: {
              field: fieldConfig.name,
              slot: slotIndex,
              fields: {
                firstName: mrz.givenNames?.join(" ") || "",
                lastName: mrz.surnames?.join(" ") || "",
                documentNumber: mrz.documentNumber || "",
                nationality: mrz.nationality || "",
                birthDate: mrz.birthDate || "",
                expiryDate: mrz.expiryDate || "",
                sex: mrz.sex || "",
              },
            },
          });
        }
      } catch {
        return;
      } finally {
        if (imageBitmap && typeof imageBitmap.close === "function") {
          imageBitmap.close();
        }
      }
    },

    assignQrVideoStream(fieldName: string) {
      const video = host.querySelector(`[data-qr-video="${fieldName}"]`) as HTMLVideoElement | null;
      const stream = host.qrScannerStreams[fieldName];
      if (!video || !stream) {
        return;
      }

      try {
        (video as any).srcObject = stream;
      } catch {
        return;
      }

      if (typeof video.play === "function") {
        void video.play().catch(() => undefined);
      }
    },

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

    scheduleContinuousQrScan(fieldConfig: { name: string }) {
      const fieldName = fieldConfig.name;
      host.clearQrScanTimer(fieldName);

      if (!host.qrScannerStreams[fieldName]) {
        return;
      }

      host.qrScannerTimers[fieldName] = window.setTimeout(async () => {
        if (!host.qrScannerStreams[fieldName] || host.qrScannerRunning[fieldName]) {
          host.scheduleContinuousQrScan(fieldConfig);
          return;
        }

        host.qrScannerRunning[fieldName] = true;
        try {
          await host.scanQrFromLiveVideo(fieldConfig, true);
        } finally {
          host.qrScannerRunning[fieldName] = false;
        }

        if (host.qrScannerStreams[fieldName]) {
          host.scheduleContinuousQrScan(fieldConfig);
        }
      }, 250);
    },

    async startQrCamera(fieldConfig: any) {
      const mediaDevices = navigator?.mediaDevices;
      if (!mediaDevices?.getUserMedia) {
        host.qrScannerState[fieldConfig.name] = {
          status: "error",
          message: "Camera is not available in this browser.",
        };
        const selectionElement = host.querySelector(`#${fieldConfig.name}_selection`) as HTMLElement | null;
        host.renderFileSelection(fieldConfig, host.getFieldValue(fieldConfig.name), selectionElement);
        return;
      }

      host.qrScannerState[fieldConfig.name] = { status: "starting" };
      host.renderFileSelection(
        fieldConfig,
        host.getFieldValue(fieldConfig.name),
        host.querySelector(`#${fieldConfig.name}_selection`) as HTMLElement | null,
      );

      try {
        const stream = await mediaDevices.getUserMedia({
          video: {
            facingMode: fieldConfig.capture || "environment",
          },
        });
        host.qrScannerStreams[fieldConfig.name] = stream;
        host.qrScannerState[fieldConfig.name] = { status: "live" };
        const selectionElement = host.querySelector(`#${fieldConfig.name}_selection`) as HTMLElement | null;
        host.renderFileSelection(fieldConfig, host.getFieldValue(fieldConfig.name), selectionElement);
        host.scheduleContinuousQrScan(fieldConfig);
      } catch {
        host.qrScannerState[fieldConfig.name] = {
          status: "error",
          message: "Unable to start the camera.",
        };
        const selectionElement = host.querySelector(`#${fieldConfig.name}_selection`) as HTMLElement | null;
        host.renderFileSelection(fieldConfig, host.getFieldValue(fieldConfig.name), selectionElement);
      }
    },

    async scanQrFromLiveVideo(fieldConfig: any, silent: boolean = false) {
      const detector = host.getBarcodeDetector();
      if (!detector || typeof detector.detect !== "function") {
        if (!silent) {
          host.emitFormEvent("xpressui:qr-scan-error", {
            values: host.engine.normalizeValues(host.form?.getState().values || {}),
            formConfig: host.formConfig,
            submit: host.formConfig?.submit,
            error: new Error("QR scan is not available in this browser."),
            result: {
              field: fieldConfig.name,
              reason: "barcode-detector-unavailable",
            },
          });
        }
        return;
      }

      const video = host.querySelector(`[data-qr-video="${fieldConfig.name}"]`) as HTMLVideoElement | null;
      if (!video) {
        return;
      }

      try {
        const results = await detector.detect(video);
        const qrResult = (results as Array<{ rawValue?: string }>).find(
          (entry) => typeof entry?.rawValue === "string" && entry.rawValue.trim().length,
        );
        if (!qrResult?.rawValue) {
          throw new Error("No QR code detected.");
        }

        host.form?.change(fieldConfig.name, qrResult.rawValue);
        host.stopQrCamera(fieldConfig.name, false);
        host.emitFormEvent("xpressui:qr-scan-success", {
          values: host.engine.normalizeValues({
            ...(host.form?.getState().values || {}),
            [fieldConfig.name]: qrResult.rawValue,
          }),
          formConfig: host.formConfig,
          submit: host.formConfig?.submit,
          result: {
            field: fieldConfig.name,
            code: qrResult.rawValue,
            source: "camera",
          },
        });
        host.scheduleDraftSave();
        host.updateConditionalFields();
        void host.refreshRemoteOptions(fieldConfig.name);
        host.renderFileSelection(
          fieldConfig,
          host.getFieldValue(fieldConfig.name),
          host.querySelector(`#${fieldConfig.name}_selection`) as HTMLElement | null,
        );
      } catch (error) {
        if (!silent) {
          host.emitFormEvent("xpressui:qr-scan-error", {
            values: host.engine.normalizeValues(host.form?.getState().values || {}),
            formConfig: host.formConfig,
            submit: host.formConfig?.submit,
            error,
            result: {
              field: fieldConfig.name,
              reason: "decode-failed",
            },
          });
        }
      }
    },

    async decodeQrScanFile(fieldConfig: any, file: File) {
      const detector = host.getBarcodeDetector();
      if (!detector || typeof detector.detect !== "function") {
        const error = new Error("QR scan is not available in this browser.");
        host.emitFormEvent("xpressui:qr-scan-error", {
          values: host.engine.normalizeValues(host.form?.getState().values || {}),
          formConfig: host.formConfig,
          submit: host.formConfig?.submit,
          error,
          result: {
            field: fieldConfig.name,
            reason: "barcode-detector-unavailable",
          },
        });
        return null;
      }

      let scanSource: any = file;
      let imageBitmap: ImageBitmap | null = null;

      if (typeof createImageBitmap === "function") {
        try {
          imageBitmap = await createImageBitmap(file);
          scanSource = imageBitmap;
        } catch {
          scanSource = file;
        }
      }

      try {
        const results = await detector.detect(scanSource);
        const qrResult = (results as Array<{ rawValue?: string }>).find(
          (entry) => typeof entry?.rawValue === "string" && entry.rawValue.trim().length,
        );
        if (!qrResult?.rawValue) {
          throw new Error("No QR code detected.");
        }

        host.stopQrCamera(fieldConfig.name, false);
        host.emitFormEvent("xpressui:qr-scan-success", {
          values: host.engine.normalizeValues({
            ...(host.form?.getState().values || {}),
            [fieldConfig.name]: qrResult.rawValue,
          }),
          formConfig: host.formConfig,
          submit: host.formConfig?.submit,
          result: {
            field: fieldConfig.name,
            code: qrResult.rawValue,
          },
        });

        return qrResult.rawValue;
      } catch (error) {
        host.emitFormEvent("xpressui:qr-scan-error", {
          values: host.engine.normalizeValues(host.form?.getState().values || {}),
          formConfig: host.formConfig,
          submit: host.formConfig?.submit,
          error,
          result: {
            field: fieldConfig.name,
            reason: "decode-failed",
          },
        });
        return null;
      } finally {
        if (imageBitmap && typeof imageBitmap.close === "function") {
          imageBitmap.close();
        }
      }
    },

    renderDocumentScanSelection(fieldConfig: any, selectedFiles: any[], selectionElement: HTMLElement) {
      const slotCount = host.getDocumentScanSlotCount(fieldConfig);
      const activeSlot = host.activeDocumentScanSlot[fieldConfig.name] || 0;
      const insight = host.getDocumentScanInsight(fieldConfig);
      const controls = host.ensureSelectionChild(
        selectionElement,
        `[data-document-scan-controls="${fieldConfig.name}"]`,
        "div",
        "mb-3 flex flex-wrap gap-2",
        "data-document-scan-controls",
        fieldConfig.name,
      );

      Array.from({ length: slotCount }, (_, index) => index).forEach((slotIndex) => {
        let button = controls.querySelector(`[data-document-scan-slot="${slotIndex}"]`) as HTMLButtonElement | null;
        if (!button) {
          button = document.createElement("button");
          button.type = "button";
          button.setAttribute("data-document-scan-slot", String(slotIndex));
          controls.appendChild(button);
        }
        button.className = "btn btn-xs btn-outline";
        button.textContent = slotIndex === 0 ? "Capture Front" : "Capture Back";
        button.classList.toggle("btn-primary", activeSlot === slotIndex);
        button.classList.toggle("btn-outline", activeSlot !== slotIndex);
      });
      Array.from<Element>(controls.querySelectorAll("[data-document-scan-slot]")).forEach((node) => {
        const slotIndex = Number((node as HTMLElement).getAttribute("data-document-scan-slot"));
        if (!Number.isFinite(slotIndex) || slotIndex < 0 || slotIndex >= slotCount) {
          node.remove();
        }
      });

      const slotGrid =
        (selectionElement.querySelector(
          `[data-document-scan-grid="${fieldConfig.name}"]`,
        ) as HTMLDivElement | null)
        ?? (selectionElement.getAttribute("data-document-scan-grid") === fieldConfig.name
          ? selectionElement as HTMLDivElement
          : host.ensureSelectionChild(
              selectionElement,
              `[data-document-scan-grid="${fieldConfig.name}"]`,
              "div",
              "grid gap-3 md:grid-cols-2",
              "data-document-scan-grid",
              fieldConfig.name,
            ) as HTMLDivElement);

      Array.from({ length: slotCount }, (_, index) => index).forEach((slotIndex) => {
        const file = selectedFiles[slotIndex];
        const slotRef = `${fieldConfig.name}:${slotIndex}`;
        const card = host.ensureSelectionChild(
          slotGrid,
          `[data-document-scan-slot-card="${slotRef}"]`,
          "div",
          "rounded border border-base-300 p-3",
          "data-document-scan-slot-card",
          slotRef,
        );
        const previewFrame = host.ensureSelectionChild(
          card,
          `[data-document-scan-preview="${slotRef}"]`,
          "div",
          "flex h-28 w-full items-center justify-center overflow-hidden rounded border border-base-300 bg-base-200",
          "data-document-scan-preview",
          slotRef,
        );
        (previewFrame as HTMLElement).style.aspectRatio = "1.586 / 1";
        let previewImage = previewFrame.querySelector("[data-document-scan-preview-image]") as HTMLImageElement | null;
        let placeholder = previewFrame.querySelector("[data-document-scan-placeholder]") as HTMLDivElement | null;

        if (
          file instanceof File &&
          host.shouldShowImagePreview(fieldConfig, file) &&
          typeof URL !== "undefined" &&
          typeof URL.createObjectURL === "function"
        ) {
          const previewUrl = URL.createObjectURL(file);
          host.filePreviewUrls[fieldConfig.name] = [
            ...(host.filePreviewUrls[fieldConfig.name] || []),
            previewUrl,
          ];
          if (!previewImage) {
            previewImage = document.createElement("img");
            previewImage.setAttribute("data-document-scan-preview-image", slotRef);
            previewFrame.appendChild(previewImage);
          }
          previewImage.src = previewUrl;
          previewImage.alt = file.name;
          previewImage.className = "h-full w-full object-cover";
          previewImage.style.display = "block";
          previewImage.style.width = "100%";
          previewImage.style.height = "100%";
          previewImage.style.objectFit = "cover";
          if (placeholder) {
            placeholder.style.display = "none";
          }
        } else {
          if (!placeholder) {
            placeholder = host.ensureSelectionChild(
              previewFrame,
              `[data-document-scan-placeholder="${slotRef}"]`,
              "div",
              "px-2 text-center text-xs opacity-70",
              "data-document-scan-placeholder",
              slotRef,
            ) as HTMLDivElement;
          }
          placeholder.textContent = file?.name || "No scan yet";
          placeholder.style.display = "";
          if (previewImage) {
            previewImage.remove();
          }
        }

        const name = host.ensureSelectionChild(
          card,
          `[data-document-scan-file-name="${slotRef}"]`,
          "div",
          "mt-2 text-xs",
          "data-document-scan-file-name",
          slotRef,
        );
        name.textContent = file?.name || "";
        name.style.display = file?.name ? "" : "none";

        const textInsight = insight.textBySlot[slotIndex];
        const textBlock = host.ensureSelectionChild(
          card,
          `[data-document-scan-ocr="${slotRef}"]`,
          "div",
          "mt-2 text-[11px] opacity-80",
          "data-document-scan-ocr",
          slotRef,
        );
        textBlock.textContent = textInsight ? `OCR: ${textInsight.slice(0, 80)}` : "";
        textBlock.style.display = textBlock.textContent ? "" : "none";

        const mrzInsight = insight.mrzBySlot[slotIndex];
        const mrzBlock = host.ensureSelectionChild(
          card,
          `[data-document-scan-mrz="${slotRef}"]`,
          "div",
          "mt-1 text-[11px] font-medium",
          "data-document-scan-mrz",
          slotRef,
        );
        mrzBlock.textContent = mrzInsight ? `MRZ: ${mrzInsight.documentCode} ${mrzInsight.issuingCountry}` : "";
        mrzBlock.style.display = mrzBlock.textContent ? "" : "none";
      });

      const helper = host.ensureSelectionChild(
        selectionElement,
        `[data-document-scan-helper="${fieldConfig.name}"]`,
        "div",
        "mt-2 text-xs opacity-70",
        "data-document-scan-helper",
        fieldConfig.name,
      );
      helper.textContent = "Scans are center-cropped to an ID card frame before submit.";
    },

    renderQrSelection(fieldConfig: any, value: any, selectionElement: HTMLElement) {
      const qrState = host.qrScannerState[fieldConfig.name] || { status: "idle" as const };
      const result = host.ensureSelectionChild(
        selectionElement,
        `[data-qr-result="${fieldConfig.name}"]`,
        "div",
        "text-sm",
        "data-qr-result",
        fieldConfig.name,
      );
      result.textContent = typeof value === "string" && value.length ? `Scanned code: ${value}` : "";
      result.style.display = result.textContent ? "" : "none";

      const controls = host.ensureSelectionChild(
        selectionElement,
        `[data-qr-controls="${fieldConfig.name}"]`,
        "div",
        "mt-2 flex flex-wrap gap-2",
        "data-qr-controls",
        fieldConfig.name,
      );
      let startButton = controls.querySelector('[data-qr-action="start"]') as HTMLButtonElement | null;
      if (!startButton) {
        startButton = document.createElement("button");
        startButton.type = "button";
        startButton.setAttribute("data-qr-action", "start");
        controls.appendChild(startButton);
      }
      startButton.className = "btn btn-xs btn-outline";
      startButton.textContent =
        qrState.status === "starting" ? "Starting..." : qrState.status === "live" ? "Camera Live" : "Start Camera";
      startButton.disabled = qrState.status === "starting" || qrState.status === "live";

      if (qrState.status === "live") {
        let scanButton = controls.querySelector('[data-qr-action="scan"]') as HTMLButtonElement | null;
        if (!scanButton) {
          scanButton = document.createElement("button");
          scanButton.type = "button";
          scanButton.setAttribute("data-qr-action", "scan");
          controls.appendChild(scanButton);
        }
        scanButton.className = "btn btn-xs btn-primary";
        scanButton.textContent = "Scan Now";

        let stopButton = controls.querySelector('[data-qr-action="stop"]') as HTMLButtonElement | null;
        if (!stopButton) {
          stopButton = document.createElement("button");
          stopButton.type = "button";
          stopButton.setAttribute("data-qr-action", "stop");
          controls.appendChild(stopButton);
        }
        stopButton.className = "btn btn-xs btn-ghost";
        stopButton.textContent = "Stop";
      } else {
        controls.querySelector('[data-qr-action="scan"]')?.remove();
        controls.querySelector('[data-qr-action="stop"]')?.remove();
      }

      const message = host.ensureSelectionChild(
        selectionElement,
        `[data-qr-status-message="${fieldConfig.name}"]`,
        "div",
        "mt-2 text-xs",
        "data-qr-status-message",
        fieldConfig.name,
      );
      message.textContent = qrState.message || "";
      message.style.display = qrState.message ? "" : "none";

      if (qrState.status === "live") {
        const video = host.ensureSelectionChild(
          selectionElement,
          `[data-qr-video="${fieldConfig.name}"]`,
          "video",
          "mt-3 h-40 w-full rounded border border-base-300 bg-black object-cover",
          "data-qr-video",
          fieldConfig.name,
        ) as HTMLVideoElement;
        video.setAttribute("playsinline", "true");
        video.setAttribute("muted", "true");
        video.setAttribute("autoplay", "true");
        video.style.display = "";
        host.assignQrVideoStream(fieldConfig.name);
      } else {
        const existingVideo = selectionElement.querySelector(
          `[data-qr-video="${fieldConfig.name}"]`,
        ) as HTMLVideoElement | null;
        if (existingVideo) {
          existingVideo.style.display = "none";
        }
      }

      const hint = host.ensureSelectionChild(
        selectionElement,
        `[data-qr-hint="${fieldConfig.name}"]`,
        "div",
        "mt-2 text-xs opacity-70",
        "data-qr-hint",
        fieldConfig.name,
      );
      hint.textContent = "You can also upload or capture an image with the file picker.";
    },
  };
}

export function createLightDocumentQrRuntime(host: TDocumentQrRuntimeHost) {
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
