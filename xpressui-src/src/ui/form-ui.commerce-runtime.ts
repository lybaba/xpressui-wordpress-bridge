import { IMAGE_GALLERY_TYPE, SELECT_PRODUCT_TYPE } from "../common/field";

type TCommerceRuntimeHost = any;

const CURRENCY_SYMBOLS: Record<string, string> = {
  EUR: "€",
  USD: "$",
  GBP: "£",
  XOF: "F CFA",
  XAF: "F CFA",
};

function getProductCurrency(fieldConfig: any): string {
  return String(fieldConfig?.productCurrency || fieldConfig?.paymentCurrency || "EUR").trim().toUpperCase() || "EUR";
}

function getProductAmountFormat(fieldConfig: any): string {
  return String(fieldConfig?.productAmountFormat || "amount-code").trim() || "amount-code";
}

function formatProductAmount(amount: number): string {
  if (!Number.isFinite(amount)) {
    return "0";
  }

  const isWholeAmount = Number.isInteger(amount);
  return new Intl.NumberFormat("fr-FR", {
    minimumFractionDigits: isWholeAmount ? 0 : 2,
    maximumFractionDigits: isWholeAmount ? 0 : 2,
  }).format(amount).replace(/[\u00a0\u202f]/g, " ");
}

function formatProductMoney(amount: number, fieldConfig: any): string {
  const currency = getProductCurrency(fieldConfig);
  const symbol = CURRENCY_SYMBOLS[currency] || currency;
  const formattedAmount = formatProductAmount(amount);

  switch (getProductAmountFormat(fieldConfig)) {
    case "code-amount":
      return `${currency} ${formattedAmount}`;
    case "amount-symbol":
      return `${formattedAmount} ${symbol}`;
    case "symbol-amount":
      return `${symbol} ${formattedAmount}`;
    case "amount-code":
    default:
      return `${formattedAmount} ${currency}`;
  }
}

function getCommerceUnitPrice(item: any): number | null {
  const rawPrice = item.discount_price ?? item.sale_price;
  const price = Number(rawPrice);
  return Number.isFinite(price) ? price : null;
}

function formatProductSummaryLabel(label: unknown, quantity: number): string {
  const configuredLabel = String(label || "articles").trim() || "articles";
  if (quantity === 1 && configuredLabel.endsWith("s")) {
    return configuredLabel.slice(0, -1);
  }
  return configuredLabel;
}

function resolveCatalogChoiceLayout(fieldConfig: any, fallbackLayout: "auto" | "horizontal" | "vertical" = "vertical") {
  const layout = fieldConfig?.layout;
  return layout === "auto" || layout === "horizontal" || layout === "vertical" ? layout : fallbackLayout;
}

function applyCatalogChoiceLayout(container: HTMLElement, layout: "auto" | "horizontal" | "vertical", minWidth = "220px") {
  container.setAttribute("data-choice-layout", layout);
  container.style.display = "grid";
  container.style.gap = container.style.gap || "8px";
  container.style.gridTemplateColumns =
    layout === "vertical" ? "1fr" : `repeat(auto-fit, minmax(${minWidth}, 1fr))`;
}

export function createCommerceRuntime(host: TCommerceRuntimeHost) {
  return {
    updateProductListInlineTotal(fieldConfig: any, value: any) {
      const totalNode = host.querySelector(`[data-product-list-total="${fieldConfig.name}"]`) as HTMLElement | null;
      if (!totalNode) {
        return;
      }

      const cartItems = host.getProductCartItems(value);
      const totalAmount = host.getProductCartTotal(cartItems);
      const totalQuantity = cartItems.reduce((sum: number, item: any) => sum + (Number(item.quantity) || 0), 0);
      const amount = totalNode.querySelector("[data-product-list-total-amount]") as HTMLSpanElement | null;

      if (totalQuantity > 0) {
        totalNode.removeAttribute("hidden");
        if (!totalNode.style.display || totalNode.style.display === "none") {
          totalNode.style.display = "inline-flex";
        }
        if (amount) {
          const summaryLabel = formatProductSummaryLabel(fieldConfig.productSummaryLabel, totalQuantity);
          amount.textContent = `${totalQuantity} ${summaryLabel} ${formatProductMoney(totalAmount, fieldConfig)}`;
        }
      } else {
        totalNode.toggleAttribute("hidden", true);
        if (amount) {
          amount.textContent = "";
        }
      }
    },

    getProductCartEntries() {
      return Object.values(host.engine.getFields())
        .filter((fieldConfig: any) => host.isProductListField(fieldConfig))
        .flatMap((fieldConfig: any) => host.getProductCartItems(host.getFieldValue(fieldConfig.name)).map((item: any) => ({
          fieldName: fieldConfig.name,
          item,
        })));
    },

    getProductCheckoutSummaryGroups() {
      return Object.values(host.engine.getFields())
        .filter((fieldConfig: any) => host.isProductListField(fieldConfig) || fieldConfig.type === SELECT_PRODUCT_TYPE)
        .map((fieldConfig: any) => {
          const fieldName = fieldConfig.name;
          const fieldLabel = String(fieldConfig.label || fieldConfig.title || fieldName || "Products");
          const value = host.getFieldValue(fieldName);
          const sourceItems = host.isProductListField(fieldConfig)
            ? host.getProductCartItems(value)
            : host.getImageGallerySelectionItems(value).map((item: any) => ({ ...item, quantity: 1 }));

          const items = sourceItems.map((item: any) => {
            const quantity = Math.max(1, Number(item.quantity || 1));
            const unitPrice = getCommerceUnitPrice(item);
            const subtotal = unitPrice === null ? null : unitPrice * quantity;
            return {
              id: String(item.id || item.value || item.name),
              name: String(item.name || item.label || item.id || "Product"),
              quantity,
              unitPrice,
              subtotal,
            };
          });

          const total = items.reduce((sum: number, item: any) => (
            item.subtotal === null ? sum : sum + item.subtotal
          ), 0);

          return {
            fieldName,
            fieldLabel,
            fieldConfig,
            items,
            total,
          };
        })
        .filter((group: any) => group.items.length > 0);
    },

    renderProductCheckoutSummary() {
      const summary = host.querySelector("[data-product-checkout-summary]") as HTMLElement | null;
      if (!summary || typeof document === "undefined") {
        return;
      }

      const productFields = Object.values(host.engine.getFields())
        .filter((fieldConfig: any) => host.isProductListField(fieldConfig) || fieldConfig.type === SELECT_PRODUCT_TYPE);
      const isLastStep = !host.isMultiStepMode?.() || host.isLastStep?.();
      const shouldShow = productFields.length > 0 && isLastStep;

      summary.toggleAttribute("hidden", !shouldShow);
      summary.style.display = shouldShow ? "" : "none";
      if (!shouldShow) {
        return;
      }

      const groups = this.getProductCheckoutSummaryGroups();
      const total = groups.reduce((sum: number, group: any) => sum + group.total, 0);
      const totalFieldConfig = groups[0]?.fieldConfig || productFields[0] || {};
      const empty = summary.querySelector("[data-product-checkout-summary-empty]") as HTMLElement | null;
      const groupsContainer = summary.querySelector("[data-product-checkout-summary-groups]") as HTMLElement | null;
      const totalNode = summary.querySelector("[data-product-checkout-summary-total]") as HTMLElement | null;
      const totalPill = summary.querySelector("[data-product-checkout-summary-total-pill]") as HTMLElement | null;
      const body = summary.querySelector("[data-product-checkout-summary-body]") as HTMLElement | null;
      const toggle = summary.querySelector("[data-product-checkout-summary-toggle]") as HTMLButtonElement | null;

      if (summary.dataset.checkoutToggleReady !== "true") {
        summary.dataset.checkoutToggleReady = "true";
        summary.dataset.collapsed = summary.dataset.collapsed === "false" ? "false" : "true";
        body?.toggleAttribute("hidden", summary.dataset.collapsed !== "false");
        toggle?.setAttribute("aria-expanded", String(summary.dataset.collapsed === "false"));
        toggle?.addEventListener("click", () => {
          const nextCollapsed = summary.dataset.collapsed === "false";
          summary.dataset.collapsed = nextCollapsed ? "true" : "false";
          body?.toggleAttribute("hidden", nextCollapsed);
          toggle.setAttribute("aria-expanded", String(!nextCollapsed));
        });
      }
      const isCollapsed = summary.dataset.collapsed !== "false";
      body?.toggleAttribute("hidden", isCollapsed);
      toggle?.setAttribute("aria-expanded", String(!isCollapsed));

      if (totalNode) {
        totalNode.textContent = formatProductMoney(total, totalFieldConfig);
      }
      if (totalPill) {
        totalPill.toggleAttribute("hidden", groups.length === 0);
      }
      if (empty) {
        empty.style.display = groups.length === 0 ? "" : "none";
      }
      if (!groupsContainer) {
        return;
      }

      groupsContainer.replaceChildren();
      groups.forEach((group: any) => {
        const groupElement = document.createElement("section");
        groupElement.className = "template-checkout-summary-group";
        groupElement.setAttribute("data-product-checkout-summary-group", group.fieldName);

        const title = document.createElement("h3");
        title.className = "template-checkout-summary-group-title";
        title.textContent = group.fieldLabel;
        groupElement.appendChild(title);

        const itemsElement = document.createElement("div");
        itemsElement.className = "template-checkout-summary-items";
        group.items.forEach((item: any) => {
          const row = document.createElement("div");
          row.className = "template-checkout-summary-item";
          row.setAttribute("data-product-checkout-summary-item", item.id);

          const main = document.createElement("div");
          main.className = "template-checkout-summary-item-main";
          const name = document.createElement("div");
          name.className = "template-checkout-summary-item-name";
          name.textContent = item.name;
          const meta = document.createElement("div");
          meta.className = "template-checkout-summary-item-meta";
          meta.textContent = item.unitPrice === null
            ? `Quantity ${item.quantity} · Price on request`
            : `Quantity ${item.quantity} × ${formatProductMoney(item.unitPrice, group.fieldConfig)}`;
          main.appendChild(name);
          main.appendChild(meta);

          const subtotal = document.createElement("div");
          subtotal.className = "template-checkout-summary-item-subtotal";
          subtotal.textContent = item.subtotal === null ? "Price on request" : formatProductMoney(item.subtotal, group.fieldConfig);

          row.appendChild(main);
          row.appendChild(subtotal);
          itemsElement.appendChild(row);
        });
        groupElement.appendChild(itemsElement);
        groupsContainer.appendChild(groupElement);
      });

      const totalElement = document.createElement("div");
      totalElement.className = "template-checkout-summary-grand-total";
      const totalLabel = document.createElement("span");
      totalLabel.textContent = "Total to pay";
      const totalAmount = document.createElement("strong");
      totalAmount.textContent = formatProductMoney(total, totalFieldConfig);
      totalElement.appendChild(totalLabel);
      totalElement.appendChild(totalAmount);
      groupsContainer.appendChild(totalElement);
    },

    renderProductListGlobalCart() {
      Object.values(host.engine.getFields())
        .filter((fieldConfig: any) => host.isProductListField(fieldConfig))
        .forEach((fieldConfig: any) => {
          host.updateProductListInlineTotal(fieldConfig, host.getFieldValue(fieldConfig.name));
        });
      host.renderProductCheckoutSummary?.();
    },

    bindProductListGlobalCartEvents() {
      const hasProductListFields = Object.values(host.engine.getFields())
        .some((fieldConfig: any) => host.isProductListField(fieldConfig));
      if (!hasProductListFields) return;

      const trigger = host.ensureProductCartTrigger();
      if (!trigger) return;

      trigger.removeAttribute('hidden');
      trigger.addEventListener('click', () => host.openProductCartModal());
    },

    openMediaGallery(name: string, photos: string[], options: { product?: any; fieldConfig?: any } = {}) {
      const isProductView = Boolean(options.product && options.fieldConfig);
      if ((!photos.length && !isProductView) || typeof document === "undefined") {
        return;
      }

      const dialog = (
        host.closest?.('[data-template-zone="form_frame"]')?.querySelector('[data-product-gallery-modal]') ??
        host.querySelector('[data-product-gallery-modal]') ??
        document.querySelector('[data-product-gallery-modal]')
      ) as HTMLDialogElement | null;
      if (!dialog) {
        return;
      }

      const titleEl = dialog.querySelector("[data-product-gallery-title]") as HTMLElement | null;
      const metaEl = dialog.querySelector("[data-product-gallery-meta]") as HTMLElement | null;
      const mainImage = dialog.querySelector("[data-product-gallery-main]") as HTMLImageElement | null;
      const thumbsContainer = dialog.querySelector("[data-product-gallery-thumbs]") as HTMLElement | null;
      const closeButton = dialog.querySelector("[data-product-gallery-close]") as HTMLButtonElement | null;
      const panel = dialog.querySelector(".xpui-gallery-panel") as HTMLElement | null;
      let detailsPanel = dialog.querySelector("[data-product-gallery-details]") as HTMLElement | null;

      dialog.dataset.productView = String(isProductView);
      dialog.dataset.hasPhotos = String(photos.length > 0);
      dialog.setAttribute("aria-label", isProductView ? `Product details for ${name}` : "Product gallery");

      if (!detailsPanel && panel) {
        detailsPanel = document.createElement("div");
        detailsPanel.setAttribute("data-product-gallery-details", "true");
        detailsPanel.className = "xpui-product-view-details";
        panel.appendChild(detailsPanel);
      }

      if (titleEl) titleEl.textContent = name;
      if (mainImage) {
        mainImage.hidden = photos.length === 0;
        mainImage.src = photos[0] || "";
        mainImage.alt = name;
      }
      if (detailsPanel) {
        detailsPanel.hidden = !isProductView;
        detailsPanel.replaceChildren();
      }

      const setActivePhoto = (photo: string) => {
        const photoIndex = photos.findIndex((entry) => entry === photo);
        if (mainImage) {
          mainImage.src = photo;
        }
        if (metaEl) {
          metaEl.textContent = photoIndex >= 0 ? `${photoIndex + 1} of ${photos.length}` : `${photos.length} photos`;
        }
        if (thumbsContainer) {
          Array.from(thumbsContainer.querySelectorAll("[data-product-gallery-thumb]")).forEach((thumb) => {
            (thumb as HTMLElement).dataset.active = String(thumb.getAttribute("data-product-gallery-thumb") === photo);
          });
        }
      };

      if (thumbsContainer) {
        thumbsContainer.replaceChildren();
        thumbsContainer.hidden = photos.length <= 1;
        photos.forEach((photo) => {
          const thumb = document.createElement("img");
          thumb.className = "xpui-gallery-thumb";
          thumb.setAttribute("data-product-gallery-thumb", photo);
          thumb.src = photo;
          thumb.alt = `${name} preview`;
          thumb.addEventListener("click", () => setActivePhoto(photo));
          thumbsContainer.appendChild(thumb);
        });
      }

      if (photos.length > 0) {
        setActivePhoto(photos[0]);
      } else if (metaEl) {
        metaEl.textContent = "";
      }

      if (isProductView && detailsPanel && options.product && options.fieldConfig) {
        const product = options.product;
        const fieldConfig = options.fieldConfig;
        const productId = String(product.id || product.value || "");
        const fieldName = String(fieldConfig.name || "");
        const unitPrice = getCommerceUnitPrice(product);
        const regularPrice = Number(product.sale_price);
        const hasRegularPrice = Number.isFinite(regularPrice) && unitPrice !== null && regularPrice > unitPrice;
        const maxQuantity = typeof product.maxNumOfChoices === "number" ? product.maxNumOfChoices : undefined;

        const getCurrentQuantity = () => {
          const currentItems = host.getProductCartItems(host.getFieldValue(fieldName));
          const currentItem = currentItems.find((entry: any) => entry.id === productId);
          return Math.max(0, Math.floor(Number(currentItem?.quantity) || 0));
        };

        const applyQuantity = (nextQuantity: number) => {
          const nextValue = host.getNextProductCartItems(
            fieldConfig,
            host.getFieldValue(fieldName),
            "set",
            productId,
            nextQuantity,
          );
          host.markFieldAsInteracted?.(fieldName);
          host.form?.change(fieldName, nextValue);
          host.queuePostChangeEffects?.(fieldName);
          updateProductDetails();
        };

        const title = document.createElement("div");
        title.className = "xpui-product-view-title";
        title.textContent = host.getChoiceDisplayLabel?.(product, productId) || name;
        detailsPanel.appendChild(title);

        if (product.desc) {
          const description = document.createElement("div");
          description.className = "xpui-product-view-description";
          description.textContent = String(product.desc);
          detailsPanel.appendChild(description);
        }

        const priceRow = document.createElement("div");
        priceRow.className = "xpui-product-view-price-row";
        const price = document.createElement("strong");
        price.className = "xpui-product-view-price";
        price.textContent = unitPrice === null ? "Price on request" : formatProductMoney(unitPrice, fieldConfig);
        priceRow.appendChild(price);
        if (hasRegularPrice) {
          const compareAt = document.createElement("span");
          compareAt.className = "xpui-product-view-regular-price";
          compareAt.textContent = formatProductMoney(regularPrice, fieldConfig);
          priceRow.appendChild(compareAt);
        }
        detailsPanel.appendChild(priceRow);

        const controls = document.createElement("div");
        controls.className = "xpui-product-view-controls";
        const decButton = document.createElement("button");
        decButton.type = "button";
        decButton.className = "xpui-product-view-step";
        decButton.textContent = "−";
        decButton.setAttribute("aria-label", `Decrease quantity for ${name}`);
        const quantityWrap = document.createElement("label");
        quantityWrap.className = "xpui-product-view-quantity";
        const quantityLabel = document.createElement("span");
        quantityLabel.textContent = "Quantity";
        const quantityInput = document.createElement("input");
        quantityInput.type = "number";
        quantityInput.min = "0";
        quantityInput.step = "1";
        quantityInput.inputMode = "numeric";
        quantityInput.setAttribute("aria-label", `Quantity for ${name}`);
        if (typeof maxQuantity === "number") {
          quantityInput.max = String(maxQuantity);
        }
        quantityWrap.appendChild(quantityInput);
        quantityWrap.appendChild(quantityLabel);
        const incButton = document.createElement("button");
        incButton.type = "button";
        incButton.className = "xpui-product-view-step xpui-product-view-step--add";
        incButton.textContent = "+";
        incButton.setAttribute("aria-label", `Increase quantity for ${name}`);
        controls.appendChild(decButton);
        controls.appendChild(quantityWrap);
        controls.appendChild(incButton);
        detailsPanel.appendChild(controls);

        const subtotal = document.createElement("div");
        subtotal.className = "xpui-product-view-subtotal";
        detailsPanel.appendChild(subtotal);

        const addButton = document.createElement("button");
        addButton.type = "button";
        addButton.className = "xpui-product-view-add";
        detailsPanel.appendChild(addButton);

        const updateProductDetails = () => {
          const quantity = getCurrentQuantity();
          const maxReached = typeof maxQuantity === "number" && quantity >= maxQuantity;
          quantityInput.value = String(quantity);
          decButton.disabled = quantity <= 0;
          incButton.disabled = maxReached;
          addButton.textContent = quantity > 0 ? "Update quantity" : "Add to cart";
          subtotal.textContent = quantity > 0 && unitPrice !== null
            ? `Subtotal ${formatProductMoney(unitPrice * quantity, fieldConfig)}`
            : unitPrice === null
              ? "Price on request"
              : "Choose a quantity to add this item.";
        };

        decButton.addEventListener("click", () => applyQuantity(getCurrentQuantity() - 1));
        incButton.addEventListener("click", () => applyQuantity(getCurrentQuantity() + 1));
        quantityInput.addEventListener("change", () => applyQuantity(Number(quantityInput.value)));
        quantityInput.addEventListener("keydown", (event) => {
          if (event.key === "Enter") {
            event.preventDefault();
            applyQuantity(Number(quantityInput.value));
          }
        });
        addButton.addEventListener("click", () => {
          const nextQuantity = Number(quantityInput.value) > 0 ? Number(quantityInput.value) : 1;
          applyQuantity(nextQuantity);
        });

        updateProductDetails();
      }

      let galleryClosed = false;
      const cleanupGallery = () => {
        if (galleryClosed) {
          return;
        }
        galleryClosed = true;
        dialog.style.display = "none";
        dialog.setAttribute("aria-hidden", "true");
        host.teardownOverlayAccessibility(true);
        host.releasePageScrollLock();
        closeButton?.removeEventListener("click", closeGallery);
        dialog.removeEventListener("click", backdropClose);
      };
      const closeGallery = () => {
        if (typeof dialog.close === "function") {
          dialog.close();
        } else {
          dialog.removeAttribute("open");
        }
        cleanupGallery();
      };
      const backdropClose = (event: MouseEvent) => {
        if (event.target === dialog) {
          closeGallery();
        }
      };

      closeButton?.addEventListener("click", closeGallery);
      dialog.addEventListener("click", backdropClose);
      dialog.addEventListener("close", cleanupGallery, { once: true });

      dialog.style.display = "";
      dialog.setAttribute("aria-hidden", "false");
      if (typeof dialog.showModal === "function") {
        try {
          dialog.showModal();
        } catch {
          dialog.setAttribute("open", "");
        }
      } else {
        dialog.setAttribute("open", "");
      }
      host.acquirePageScrollLock();
    },

    openProductListGallery(product: any, _fieldConfig?: any) {
      const fullPhotos = Array.isArray(product.photos_full) ? product.photos_full : [];
      const photos = fullPhotos.length
        ? fullPhotos
        : [product.image_medium || product.image_thumbnail].filter(Boolean);
      if (!photos.length) {
        return;
      }
      host.openMediaGallery(product.name, photos);
    },

    openImageGalleryItem(item: any) {
      const fullPhotos = Array.isArray(item.photos_full) ? item.photos_full : [];
      const photos = fullPhotos.length
        ? fullPhotos
        : [item.image_medium || item.image_thumbnail].filter(Boolean);
      host.openMediaGallery(item.name, photos);
    },

    getNextProductCartItems(fieldConfig: any, currentValue: any, action: "add" | "inc" | "dec" | "remove" | "set", productId: string, quantity?: number) {
      const catalog = host.getProductListCatalog(fieldConfig);
      const product = catalog.find((entry: any) => entry.id === productId);
      const currentItems = host.getProductCartItems(currentValue);
      const existingIndex = currentItems.findIndex((entry: any) => entry.id === productId);
      const nextItems = [...currentItems];

      if (action === "set") {
        if (!product && existingIndex < 0) {
          return nextItems;
        }
        const productSource = product || nextItems[existingIndex];
        const maxQuantity = productSource.maxNumOfChoices;
        const normalizedQuantity = Math.max(0, Math.floor(Number(quantity) || 0));
        const nextQuantity = typeof maxQuantity === "number"
          ? Math.min(normalizedQuantity, maxQuantity)
          : normalizedQuantity;
        if (nextQuantity <= 0) {
          return nextItems.filter((entry: any) => entry.id !== productId);
        }
        if (existingIndex >= 0) {
          nextItems[existingIndex] = {
            ...nextItems[existingIndex],
            quantity: nextQuantity,
          };
          return nextItems;
        }
        return [{ ...productSource, quantity: nextQuantity }, ...nextItems];
      }

      if (action === "add") {
        if (!product) {
          return nextItems;
        }
        if (existingIndex >= 0) {
          const maxQuantity = product.maxNumOfChoices;
          if (typeof maxQuantity === "number" && nextItems[existingIndex].quantity >= maxQuantity) {
            return nextItems;
          }
          nextItems[existingIndex] = {
            ...nextItems[existingIndex],
            quantity: nextItems[existingIndex].quantity + 1,
          };
          return nextItems;
        }
        return [...nextItems, { ...product, quantity: 1 }];
      }

      if (existingIndex < 0) {
        return nextItems;
      }

      if (action === "inc") {
        const maxQuantity = nextItems[existingIndex].maxNumOfChoices;
        if (typeof maxQuantity === "number" && nextItems[existingIndex].quantity >= maxQuantity) {
          return nextItems;
        }
        nextItems[existingIndex] = {
          ...nextItems[existingIndex],
          quantity: nextItems[existingIndex].quantity + 1,
        };
        return nextItems;
      }

      if (action === "dec") {
        const nextQuantity = nextItems[existingIndex].quantity - 1;
        if (nextQuantity <= 0) {
          return nextItems.filter((entry: any) => entry.id !== productId);
        }
        nextItems[existingIndex] = {
          ...nextItems[existingIndex],
          quantity: nextQuantity,
        };
        return nextItems;
      }

      return nextItems.filter((entry: any) => entry.id !== productId);
    },

    getNextImageGallerySelectionItems(fieldConfig: any, currentValue: any, action: "toggle" | "remove", imageId: string) {
      const catalog = host.getImageGalleryCatalog(fieldConfig);
      const image = catalog.find((entry: any) => entry.id === imageId);
      const currentItems = host.getImageGallerySelectionItems(currentValue);
      const existingIndex = currentItems.findIndex((entry: any) => entry.id === imageId);
      const nextItems = [...currentItems];
      const selectionLimit = host.getImageGallerySelectionLimit(fieldConfig);

      if (action === "toggle") {
        if (existingIndex >= 0) {
          if (selectionLimit === 1) {
            return nextItems;
          }
          return nextItems.filter((entry: any) => entry.id !== imageId);
        }
        if (!image) {
          return nextItems;
        }
        if (selectionLimit === 1) {
          return [image];
        }
        if (selectionLimit > 0 && nextItems.length >= selectionLimit) {
          return nextItems;
        }
        return [...nextItems, image];
      }

      return nextItems.filter((entry: any) => entry.id !== imageId);
    },

    renderProductListSelection(fieldConfig: any, value: any, selectionElement: HTMLElement | null) {
      if (!selectionElement) {
        return;
      }
      const products = host.getProductListCatalog(fieldConfig);
      const cartItems = host.getProductCartItems(value);
      const cartMap = cartItems.reduce((accumulator: Record<string, number>, item: any) => {
        accumulator[item.id] = item.quantity;
        return accumulator;
      }, {});

      const productList =
        (selectionElement.querySelector(
          `[data-product-list-catalog="${fieldConfig.name}"]`,
        ) as HTMLDivElement | null)
        ?? (selectionElement.querySelector(`.template-product-grid`) as HTMLDivElement | null)
        ?? (selectionElement as HTMLDivElement);

      if (productList !== selectionElement && !productList.hasAttribute("data-product-list-catalog")) {
        productList.setAttribute("data-product-list-catalog", fieldConfig.name);
      }

      products.forEach((product: any) => {
        const currentQuantity = cartMap[product.id] || 0;
        const maxReached = typeof product.maxNumOfChoices === "number" && currentQuantity >= product.maxNumOfChoices;
        const unitPrice = product.discount_price ?? product.sale_price ?? 0;
        const subtotalAmount = unitPrice * currentQuantity;

        const card = productList.querySelector(`[data-product-card="${product.id}"]`) as HTMLElement | null;
        if (!card) return;

        card.dataset.inCart = String(currentQuantity > 0);

        const overlay = card.querySelector(`[data-product-overlay="${product.id}"]`) as HTMLElement | null;
        if (overlay) {
          overlay.toggleAttribute("hidden", currentQuantity <= 0);
          if (currentQuantity > 0) {
            const qtyValue = overlay.querySelector(`[data-product-quantity-pill-value="${product.id}"]`) as HTMLElement | null;
            if (qtyValue) qtyValue.textContent = String(currentQuantity);
            const subtotalPill = overlay.querySelector(`[data-product-subtotal-pill="${product.id}"]`) as HTMLElement | null;
            if (subtotalPill) subtotalPill.textContent = formatProductMoney(subtotalAmount, fieldConfig);
          }
        }

        const title = card.querySelector(`[data-product-title="${product.id}"]`) as HTMLElement | null;
        if (title) {
          title.textContent = host.getChoiceDisplayLabel(product, product.id);
        }

        const primaryPrice = card.querySelector(`[data-product-price="${product.id}"]`) as HTMLElement | null;
        if (primaryPrice) {
          primaryPrice.textContent =
            product.discount_price !== null
              ? formatProductMoney(product.discount_price, fieldConfig)
              : product.sale_price !== null
                ? formatProductMoney(product.sale_price, fieldConfig)
                : "Price on request";
        }

        const decButton = card.querySelector(`[data-product-action-slot="dec"][data-product-id="${product.id}"]`) as HTMLButtonElement | null;
        if (decButton) {
          decButton.disabled = currentQuantity <= 0;
        }

        const incButton = card.querySelector(`[data-product-action-slot="inc"][data-product-id="${product.id}"]`) as HTMLButtonElement | null;
        if (incButton) {
          incButton.disabled = maxReached;
        }

        const quantityLabel = card.querySelector(`[data-product-quantity-label="${product.id}"]`) as HTMLElement | null;
        if (quantityLabel && !(quantityLabel instanceof HTMLInputElement)) {
          quantityLabel.textContent = String(currentQuantity);
        }
        const quantityInput = card.querySelector(`[data-product-quantity-input="${product.id}"]`) as HTMLInputElement | null;
        if (quantityInput && quantityInput.value !== String(currentQuantity)) {
          quantityInput.value = String(currentQuantity);
        }
      });

      host.renderProductListGlobalCart();
      host.renderProductCheckoutSummary?.();
    },

    renderImageGallerySelection(fieldConfig: any, value: any, selectionElement: HTMLElement | null) {
      if (!selectionElement) {
        return;
      }

      const images = host.getImageGalleryCatalog(fieldConfig);
      const selectedItems = host.getImageGallerySelectionItems(value);
      const selectedMap = selectedItems.reduce((accumulator: Record<string, boolean>, item: any) => {
        accumulator[item.id] = true;
        return accumulator;
      }, {});
      const selectionLimit = host.getImageGallerySelectionLimit(fieldConfig);
      const isSingleSelect = selectionLimit === 1;
      const limitReached = selectionLimit > 0 && selectedItems.length >= selectionLimit;
      const isSelectProductField = fieldConfig.type === SELECT_PRODUCT_TYPE;
      const isSelectImageField = fieldConfig.type === IMAGE_GALLERY_TYPE;
      const hasChoiceMedia = images.some((item: any) => item.image_thumbnail || item.image_medium);
      const useChoiceStyle =
        isSelectProductField
        || isSelectImageField;
      const choiceLayout = resolveCatalogChoiceLayout(fieldConfig);

      const gallery =
        (selectionElement.querySelector(
          `[data-image-gallery-catalog="${fieldConfig.name}"]`,
        ) as HTMLDivElement | null)
        ?? (selectionElement as HTMLDivElement);
      applyCatalogChoiceLayout(gallery, choiceLayout);
      if (useChoiceStyle) {
        selectionElement.setAttribute("data-image-choice-mode", "choice-list");
        gallery.setAttribute("data-image-choice-mode", "choice-list");
        if (hasChoiceMedia) {
          gallery.setAttribute("data-choice-media", "true");
        } else {
          gallery.removeAttribute("data-choice-media");
        }
        gallery.classList.remove("template-gallery-grid");
        gallery.classList.remove("template-choice-grid--auto", "template-choice-grid--horizontal", "template-choice-grid--vertical", "template-choice-grid--media");
        gallery.classList.add("template-choice-grid", `template-choice-grid--${choiceLayout}`);
        if (hasChoiceMedia) {
          gallery.classList.add("template-choice-grid--media");
        }
      }

      images.forEach((imageItem: any) => {
        const selected = Boolean(selectedMap[imageItem.id]);
        const disabled = !selected && limitReached && !isSingleSelect;

        const card = gallery.querySelector(`[data-image-card="${imageItem.id}"]`) as HTMLElement | null;
        if (!card) return;

        if (useChoiceStyle) {
          card.removeAttribute("data-image-open-gallery");
          const metaRow = card.querySelector(`[data-image-meta-row="${imageItem.id}"]`) as HTMLElement | null;
          if (metaRow) {
            metaRow.hidden = !isSelectProductField;
          }
          const badge = card.querySelector(`[data-image-gallery-badge="${imageItem.id}"]`) as HTMLElement | null;
          if (badge && !isSelectProductField) {
            badge.hidden = true;
          }
        }

        card.dataset.selected = String(selected);
        if (isSingleSelect) {
          card.setAttribute("data-image-gallery-action", "toggle");
          card.setAttribute("data-image-id", imageItem.id);
        } else {
          card.removeAttribute("data-image-gallery-action");
          card.removeAttribute("data-image-id");
        }

        const stateBadge = card.querySelector(`[data-image-gallery-state="${imageItem.id}"]`) as HTMLElement | null;
        if (stateBadge) {
          if (useChoiceStyle) {
            stateBadge.textContent = "";
            stateBadge.style.display = "none";
            stateBadge.hidden = true;
          } else if (isSelectProductField) {
            stateBadge.textContent = "";
            stateBadge.style.display = "none";
            stateBadge.hidden = true;
          } else if (selected) {
            stateBadge.textContent = "Selected";
          } else if (disabled) {
            stateBadge.textContent = "";
            stateBadge.style.display = "none";
            stateBadge.hidden = true;
          } else {
            stateBadge.textContent = "Available";
            stateBadge.style.display = "";
            stateBadge.hidden = false;
          }
        }

        const controls = card.querySelector(`[data-image-controls="${imageItem.id}"]`) as HTMLElement | null;
        if (controls) {
          if (isSingleSelect) {
            controls.className = "hidden";
          } else {
            controls.className = "mt-2 flex items-center justify-center";
            const toggleButton = controls.querySelector(`[data-image-gallery-action="toggle"]`) as HTMLButtonElement | null;
            if (toggleButton) {
              toggleButton.textContent = selected ? "×" : "+";
              toggleButton.setAttribute("aria-label", selected ? `Remove ${imageItem.name}` : `Select ${imageItem.name}`);
              toggleButton.disabled = disabled;
            }
          }
        }
      });

      if (isSelectProductField || useChoiceStyle) {
        const existingSelectedPanel = selectionElement.querySelector(
          `[data-image-gallery-selection="${fieldConfig.name}"]`,
        ) as HTMLDivElement | null;
        if (existingSelectedPanel) {
          existingSelectedPanel.style.display = "none";
        }
        if (isSelectProductField) {
          host.renderProductCheckoutSummary?.();
        }
        return;
      }

      const heading = selectionElement.querySelector(
        `[data-image-gallery-heading="${fieldConfig.name}"]`,
      ) as HTMLElement | null;
      if (heading) {
        heading.textContent = `Selected Images (${selectedItems.length}${selectionLimit ? `/${selectionLimit}` : ""})`;
      }

      const emptyState = selectionElement.querySelector(
        `[data-image-gallery-empty="${fieldConfig.name}"]`,
      ) as HTMLElement | null;
      const list = selectionElement.querySelector(
        `[data-image-gallery-list="${fieldConfig.name}"]`,
      ) as HTMLElement | null;

      if (!selectedItems.length) {
        if (emptyState) emptyState.style.display = "";
        if (list) {
          list.style.display = "none";
          Array.from(list.querySelectorAll("[data-image-gallery-item]")).forEach((node) => node.remove());
        }
        return;
      }

      if (emptyState) emptyState.style.display = "none";
      if (list) {
        list.style.display = "grid";
        selectedItems.forEach((item: any) => {
          let row = list.querySelector(`[data-image-gallery-item="${item.id}"]`) as HTMLDivElement | null;
          if (!row) {
            row = document.createElement("div");
            row.setAttribute("data-image-gallery-item", item.id);
            row.className = "flex items-center justify-between gap-2 rounded border border-base-300 px-2 py-2";

            const nameWrap = document.createElement("div");
            nameWrap.setAttribute("data-image-gallery-name-wrap", item.id);
            nameWrap.className = "flex min-w-0 items-center gap-2";
            row.appendChild(nameWrap);

            if (item.image_thumbnail || item.image_medium) {
              const thumb = document.createElement("img");
              thumb.setAttribute("data-image-gallery-thumb", item.id);
              nameWrap.appendChild(thumb);
            }

            const name = document.createElement("div");
            name.setAttribute("data-image-gallery-name", item.id);
            name.className = "text-sm";
            nameWrap.appendChild(name);

            const unitPrice = item.discount_price ?? item.sale_price;
            if (isSelectProductField && unitPrice !== undefined && unitPrice !== null) {
              const price = document.createElement("div");
              price.setAttribute("data-image-gallery-price", item.id);
              price.className = "text-xs font-semibold";
              row.appendChild(price);
            }

            const removeBtn = document.createElement("button");
            removeBtn.setAttribute("data-image-gallery-action", "remove");
            removeBtn.setAttribute("data-image-id", item.id);
            removeBtn.type = "button";
            removeBtn.className = "btn";
            removeBtn.textContent = "×";
            row.appendChild(removeBtn);

            list.appendChild(row);
          }

          const thumb = row.querySelector(`[data-image-gallery-thumb="${item.id}"]`) as HTMLImageElement | null;
          if (thumb) {
            thumb.src = item.image_thumbnail || item.image_medium || "";
            thumb.alt = item.name;
          }

          const name = row.querySelector(`[data-image-gallery-name="${item.id}"]`) as HTMLElement | null;
          if (name) name.textContent = item.name;

          const price = row.querySelector(`[data-image-gallery-price="${item.id}"]`) as HTMLElement | null;
          if (price) {
            const unitPrice = item.discount_price ?? item.sale_price;
            if (isSelectProductField && unitPrice !== undefined && unitPrice !== null) {
              price.textContent = formatProductMoney(Number(unitPrice), fieldConfig);
            }
          }

          const removeBtn = row.querySelector(`[data-image-gallery-action="remove"]`) as HTMLButtonElement | null;
          if (removeBtn) removeBtn.setAttribute("aria-label", `Remove ${item.name}`);
        });

        Array.from(list.querySelectorAll("[data-image-gallery-item]")).forEach((node) => {
          const imageId = (node as HTMLElement).getAttribute("data-image-gallery-item");
          if (imageId && !selectedItems.some((item: any) => item.id === imageId)) {
            node.remove();
          }
        });
      }
    },
  };
}
