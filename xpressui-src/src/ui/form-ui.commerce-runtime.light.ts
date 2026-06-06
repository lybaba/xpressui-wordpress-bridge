type TCommerceRuntimeHost = any;

function createUnsupportedRuntimeMessage(fieldName: string, capability: string): string {
  return `The ${capability} field "${fieldName}" is not supported by the XPressUI light runtime.`;
}

export function createCommerceRuntime(host: TCommerceRuntimeHost) {
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
    updateProductListInlineTotal: (_fieldConfig: any, _value: any) => undefined,
    getProductCartEntries: () => [],
    renderProductListGlobalCart() {
      host.querySelector("[data-product-cart-trigger]")?.remove();
      host.querySelector("[data-product-cart-overlay]")?.remove();
    },
    bindProductListGlobalCartEvents() {
      return;
    },
    openMediaGallery: (_name: string, _photos: string[]) => undefined,
    openProductListGallery(product: { name: string }) {
      emitUnsupported(product as any, "product-list");
    },
    openImageGalleryItem(item: { name: string }) {
      emitUnsupported(item as any, "select-image");
    },
    getNextProductCartItems(fieldConfig: any, currentValue: any, _action: string, _productId: string) {
      emitUnsupported(fieldConfig, "product-list");
      return currentValue;
    },
    getNextImageGallerySelectionItems(fieldConfig: any, currentValue: any, _action: string, _imageId: string) {
      emitUnsupported(fieldConfig, fieldConfig.type === "select-product" ? "select-product" : "select-image");
      return currentValue;
    },
    renderProductListSelection(fieldConfig: any, _value: any, selectionElement: HTMLElement | null) {
      if (!selectionElement) {
        return;
      }
      const message = host.ensureSelectionChild(
        selectionElement,
        `[data-product-list-unsupported="${fieldConfig.name}"]`,
        "div",
        "rounded border border-warning/40 bg-warning/10 px-3 py-2 text-xs",
        "data-product-list-unsupported",
        fieldConfig.name,
      );
      message.textContent = createUnsupportedRuntimeMessage(fieldConfig.name, "product-list");
    },
    renderImageGallerySelection(fieldConfig: any, _value: any, selectionElement: HTMLElement | null) {
      if (!selectionElement) {
        return;
      }
      const capability = fieldConfig.type === "select-product" ? "select-product" : "select-image";
      const message = host.ensureSelectionChild(
        selectionElement,
        `[data-image-gallery-unsupported="${fieldConfig.name}"]`,
        "div",
        "rounded border border-warning/40 bg-warning/10 px-3 py-2 text-xs",
        "data-image-gallery-unsupported",
        fieldConfig.name,
      );
      message.textContent = createUnsupportedRuntimeMessage(fieldConfig.name, capability);
    },
  };
}
