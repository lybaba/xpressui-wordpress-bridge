import { SELECT_PRODUCT_TYPE } from "../common/field";

type TCommerceRuntimeHost = any;

export function createCommerceRuntime(host: TCommerceRuntimeHost) {
  return {
    updateProductListInlineTotal(fieldConfig: any, value: any) {
      const totalNode = host.querySelector(`[data-product-list-total="${fieldConfig.name}"]`) as HTMLElement | null;
      if (!totalNode) {
        return;
      }

      const cartItems = host.getProductCartItems(value);
      const totalAmount = host.getProductCartTotal(cartItems);
      let icon = totalNode.querySelector("[data-product-list-total-icon]") as HTMLSpanElement | null;
      let amount = totalNode.querySelector("[data-product-list-total-amount]") as HTMLSpanElement | null;
      if (totalAmount > 0) {
        totalNode.style.display = "inline-flex";
        totalNode.style.alignItems = "center";
        totalNode.style.gap = "8px";
        totalNode.style.padding = "7px 12px";
        totalNode.style.borderRadius = "999px";
        totalNode.style.background = "rgba(15, 23, 42, 0.08)";
        totalNode.style.border = "1px solid rgba(15, 23, 42, 0.14)";
        totalNode.style.boxShadow = "inset 0 1px 0 rgba(255,255,255,0.45)";
        totalNode.style.color = "#0f172a";
        totalNode.style.fontSize = "12px";
        totalNode.style.fontWeight = "700";
        totalNode.style.lineHeight = "1";
        if (!icon) {
          icon = document.createElement("span");
          icon.setAttribute("data-product-list-total-icon", fieldConfig.name);
          icon.setAttribute("aria-hidden", "true");
          icon.style.display = "inline-flex";
          icon.style.alignItems = "center";
          icon.style.color = "#1d4ed8";
          icon.textContent = "🛒";
          totalNode.appendChild(icon);
        }
        if (!amount) {
          amount = document.createElement("span");
          amount.setAttribute("data-product-list-total-amount", fieldConfig.name);
          totalNode.appendChild(amount);
        }
        amount.style.fontVariantNumeric = "tabular-nums";
        amount.style.letterSpacing = "0.01em";
        amount.textContent = `${totalAmount.toFixed(2)}€`;
        return;
      }

      icon?.remove();
      amount?.remove();
      totalNode.style.display = "none";
    },

    getProductCartEntries() {
      return Object.values(host.engine.getFields())
        .filter((fieldConfig: any) => host.isProductListField(fieldConfig))
        .flatMap((fieldConfig: any) => host.getProductCartItems(host.getFieldValue(fieldConfig.name)).map((item: any) => ({
          fieldName: fieldConfig.name,
          item,
        })));
    },

    renderProductListGlobalCart() {
      Object.values(host.engine.getFields())
        .filter((fieldConfig: any) => host.isProductListField(fieldConfig))
        .forEach((fieldConfig: any) => {
          host.updateProductListInlineTotal(fieldConfig, host.getFieldValue(fieldConfig.name));
        });

      host.querySelector("[data-product-cart-trigger]")?.remove();
      host.querySelector("[data-product-cart-overlay]")?.remove();
    },

    bindProductListGlobalCartEvents() {
      return;
    },

    openMediaGallery(name: string, photos: string[]) {
      if (!photos.length || typeof document === "undefined") {
        return;
      }

      if (host.productGalleryOverlay) {
        host.productGalleryOverlay.remove();
        host.productGalleryOverlay = null;
        host.teardownOverlayAccessibility(false);
        host.releasePageScrollLock();
      }

      const overlay = document.createElement("div");
      overlay.setAttribute("data-product-gallery-overlay", "true");
      overlay.setAttribute("aria-hidden", "true");
      overlay.style.position = "fixed";
      overlay.style.inset = "0";
      overlay.style.background = "rgba(15, 23, 42, 0.8)";
      overlay.style.zIndex = "10000";
      overlay.style.display = "flex";
      overlay.style.alignItems = "center";
      overlay.style.justifyContent = "center";
      overlay.style.padding = "24px";

      const modal = document.createElement("div");
      modal.setAttribute("data-product-gallery-modal", "true");
      modal.setAttribute("aria-label", `${name} gallery`);
      modal.style.width = "min(960px, 100%)";
      modal.style.maxHeight = "90vh";
      modal.style.overflow = "auto";
      modal.style.background = "#ffffff";
      modal.style.borderRadius = "14px";
      modal.style.padding = "14px";

      const closeButton = document.createElement("button");
      closeButton.type = "button";
      closeButton.className = "btn";
      closeButton.textContent = "×";
      closeButton.setAttribute("data-product-gallery-close", "true");
      closeButton.style.float = "right";
      closeButton.style.width = "36px";
      closeButton.style.minWidth = "36px";
      closeButton.style.height = "36px";
      closeButton.style.padding = "0";
      closeButton.style.display = "inline-flex";
      closeButton.style.alignItems = "center";
      closeButton.style.justifyContent = "center";
      closeButton.style.borderRadius = "999px";
      closeButton.style.border = "1px solid rgba(148, 163, 184, 0.4)";
      closeButton.style.background = "#ffffff";
      closeButton.style.color = "#0f172a";
      closeButton.style.boxShadow = "none";
      closeButton.setAttribute("aria-label", "Close gallery");
      const closeGallery = () => {
        overlay.remove();
        host.productGalleryOverlay = null;
        host.teardownOverlayAccessibility(true);
        host.releasePageScrollLock();
      };
      closeButton.addEventListener("click", closeGallery);
      modal.appendChild(closeButton);

      const title = document.createElement("div");
      title.className = "mb-2 text-sm font-semibold";
      title.textContent = name;
      modal.appendChild(title);

      const meta = document.createElement("div");
      meta.className = "mb-3 text-xs opacity-70";
      modal.appendChild(meta);

      const mainImage = document.createElement("img");
      mainImage.setAttribute("data-product-gallery-main", "true");
      mainImage.src = photos[0];
      mainImage.alt = name;
      mainImage.style.width = "100%";
      mainImage.style.maxHeight = "60vh";
      mainImage.style.objectFit = "contain";
      mainImage.style.borderRadius = "10px";
      modal.appendChild(mainImage);

      const thumbs = document.createElement("div");
      thumbs.setAttribute("data-product-gallery-thumbs", "true");
      thumbs.style.display = "flex";
      thumbs.style.gap = "8px";
      thumbs.style.marginTop = "10px";
      thumbs.style.overflowX = "auto";
      const thumbButtons: HTMLImageElement[] = [];
      const setActivePhoto = (photo: string) => {
        const photoIndex = photos.findIndex((entry) => entry === photo);
        mainImage.src = photo;
        meta.textContent = photoIndex >= 0 ? `${photoIndex + 1} of ${photos.length}` : `${photos.length} photos`;
        thumbButtons.forEach((thumb) => {
          const selected = thumb.getAttribute("data-product-gallery-thumb") === photo;
          thumb.style.outline = selected ? "2px solid rgb(59 130 246)" : "1px solid rgba(148, 163, 184, 0.35)";
          thumb.style.outlineOffset = "1px";
          thumb.style.opacity = selected ? "1" : "0.78";
        });
      };

      photos.forEach((photo) => {
        const thumb = document.createElement("img");
        thumb.setAttribute("data-product-gallery-thumb", photo);
        thumb.src = photo;
        thumb.alt = `${name} preview`;
        thumb.style.width = "72px";
        thumb.style.height = "72px";
        thumb.style.objectFit = "cover";
        thumb.style.borderRadius = "8px";
        thumb.style.cursor = "pointer";
        thumb.addEventListener("click", () => {
          setActivePhoto(photo);
        });
        thumbButtons.push(thumb);
        thumbs.appendChild(thumb);
      });
      modal.appendChild(thumbs);
      setActivePhoto(photos[0]);

      overlay.appendChild(modal);
      overlay.addEventListener("click", (event) => {
        if (event.target === overlay) {
          closeGallery();
        }
      });
      document.body.appendChild(overlay);
      host.productGalleryOverlay = overlay;
      host.setupOverlayAccessibility(overlay, modal, closeGallery, closeButton);
      host.acquirePageScrollLock();
    },

    openProductListGallery(product: any) {
      const photos = product.photos_full.length
        ? product.photos_full
        : [product.image_medium || product.image_thumbnail].filter(Boolean);
      host.openMediaGallery(product.name, photos);
    },

    openImageGalleryItem(item: any) {
      const photos = item.photos_full.length
        ? item.photos_full
        : [item.image_medium || item.image_thumbnail].filter(Boolean);
      host.openMediaGallery(item.name, photos);
    },

    getNextProductCartItems(fieldConfig: any, currentValue: any, action: "add" | "inc" | "dec" | "remove", productId: string) {
      const catalog = host.getProductListCatalog(fieldConfig);
      const product = catalog.find((entry: any) => entry.id === productId);
      const currentItems = host.getProductCartItems(currentValue);
      const existingIndex = currentItems.findIndex((entry: any) => entry.id === productId);
      const nextItems = [...currentItems];

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
        ?? (selectionElement as HTMLDivElement);

      if (productList === selectionElement) {
        productList.setAttribute("data-product-list-catalog", fieldConfig.name);
      }

      productList.style.display = "grid";
      productList.style.gridTemplateColumns = "repeat(auto-fit, minmax(180px, 1fr))";
      productList.style.gap = "10px";
      productList.style.marginBottom = "14px";
      productList.style.alignItems = "start";

      const styleProductActionButton = (
        button: HTMLButtonElement,
        options: { emphasized?: boolean; ghost?: boolean } = {},
      ) => {
        const { emphasized = false, ghost = false } = options;
        button.style.width = "30px";
        button.style.minWidth = "30px";
        button.style.height = "30px";
        button.style.padding = "0";
        button.style.display = "inline-flex";
        button.style.alignItems = "center";
        button.style.justifyContent = "center";
        button.style.borderRadius = "999px";
        button.style.fontSize = "13px";
        button.style.fontWeight = "700";
        button.style.lineHeight = "1";
        button.style.boxShadow = "none";
        button.style.border = ghost ? "1px solid transparent" : "1px solid rgba(148, 163, 184, 0.4)";
        button.style.background = emphasized ? "#0f172a" : (ghost ? "transparent" : "#f8fafc");
        button.style.color = emphasized ? "#ffffff" : "#0f172a";
      };

      products.forEach((product: any) => {
        const currentQuantity = cartMap[product.id] || 0;
        const maxReached = typeof product.maxNumOfChoices === "number" && currentQuantity >= product.maxNumOfChoices;
        const unitPrice = product.discount_price ?? product.sale_price ?? 0;
        const subtotalAmount = unitPrice * currentQuantity;

        let card = productList.querySelector(`[data-product-card="${product.id}"]`) as HTMLDivElement | null;
        if (!card) {
          card = document.createElement("div");
          card.setAttribute("data-product-card", product.id);
          productList.appendChild(card);
        }

        card.className = "rounded border border-base-300 p-2";
        card.style.cursor = "default";
        card.style.borderColor = currentQuantity > 0 ? "rgb(59 130 246)" : "";
        card.style.boxShadow = currentQuantity > 0 ? "0 0 0 2px rgba(59, 130, 246, 0.12)" : "";
        card.style.background = currentQuantity > 0 ? "rgba(59, 130, 246, 0.05)" : "rgba(248, 250, 252, 0.84)";
        card.style.display = "grid";
        card.style.gridTemplateRows = "auto auto auto";
        card.style.padding = "16px";
        card.style.justifyItems = "center";
        card.style.borderRadius = "16px";

        const previewSource = product.image_medium || product.image_thumbnail;
        let thumbFrame = card.querySelector("[data-product-media]") as HTMLDivElement | null;
        if (!thumbFrame) {
          thumbFrame = card.querySelector(".template-product-media") as HTMLDivElement | null;
        }
        if (!thumbFrame && previewSource) {
          thumbFrame = document.createElement("div");
          thumbFrame.setAttribute("data-product-media", product.id);
          card.appendChild(thumbFrame);
        }
        if (thumbFrame) {
          thumbFrame.setAttribute("data-product-open-gallery", product.id);
          thumbFrame.style.cursor = "pointer";
          thumbFrame.style.width = "100%";
          thumbFrame.style.aspectRatio = "4 / 3";
          thumbFrame.style.maxHeight = "164px";
          thumbFrame.style.position = "relative";
          thumbFrame.style.display = "flex";
          thumbFrame.style.alignItems = "center";
          thumbFrame.style.justifyContent = "center";
          thumbFrame.style.borderRadius = "12px";
          thumbFrame.style.background = "rgba(255,255,255,0.72)";
          thumbFrame.style.overflow = "hidden";

          let thumb = thumbFrame.querySelector("img") as HTMLImageElement | null;
          if (!thumb && previewSource) {
            thumb = document.createElement("img");
            thumb.setAttribute("data-product-image", product.id);
            thumbFrame.appendChild(thumb);
          }
          if (thumb) {
            thumb.src = previewSource || "";
            thumb.alt = product.name;
            thumb.style.width = "100%";
            thumb.style.height = "100%";
            thumb.style.objectFit = "cover";
            thumb.style.objectPosition = "center center";
            thumb.style.display = previewSource ? "block" : "none";
          }

          let imageOverlay = thumbFrame.querySelector("[data-product-overlay]") as HTMLDivElement | null;
          if (!imageOverlay && currentQuantity > 0) {
            imageOverlay = document.createElement("div");
            imageOverlay.setAttribute("data-product-overlay", product.id);
            thumbFrame.appendChild(imageOverlay);
          }
          if (imageOverlay) {
            if (currentQuantity > 0) {
              imageOverlay.style.position = "absolute";
              imageOverlay.style.left = "0";
              imageOverlay.style.right = "0";
              imageOverlay.style.bottom = "0";
              imageOverlay.style.display = "flex";
              imageOverlay.style.justifyContent = "space-between";
              imageOverlay.style.alignItems = "center";
              imageOverlay.style.gap = "8px";
              imageOverlay.style.padding = "10px";
              imageOverlay.style.background = "linear-gradient(180deg, rgba(15,23,42,0) 0%, rgba(15,23,42,0.74) 100%)";

              let quantityPill = imageOverlay.querySelector("[data-product-quantity-pill]") as HTMLSpanElement | null;
              if (!quantityPill) {
                quantityPill = document.createElement("span");
                quantityPill.setAttribute("data-product-quantity-pill", product.id);
                imageOverlay.appendChild(quantityPill);
              }
              quantityPill.style.display = "inline-flex";
              quantityPill.style.alignItems = "center";
              quantityPill.style.gap = "4px";
              quantityPill.style.padding = "4px 8px";
              quantityPill.style.borderRadius = "999px";
              quantityPill.style.background = "rgba(255,255,255,0.16)";
              quantityPill.style.color = "#ffffff";
              quantityPill.style.fontSize = "11px";
              quantityPill.style.fontWeight = "700";
              let quantityIcon = quantityPill.querySelector("[data-product-quantity-pill-icon]") as HTMLSpanElement | null;
              if (!quantityIcon) {
                quantityIcon = document.createElement("span");
                quantityIcon.setAttribute("data-product-quantity-pill-icon", product.id);
                quantityIcon.setAttribute("aria-hidden", "true");
                quantityPill.appendChild(quantityIcon);
              }
              quantityIcon.textContent = "🛒";

              let quantityValue = quantityPill.querySelector("[data-product-quantity-pill-value]") as HTMLSpanElement | null;
              if (!quantityValue) {
                quantityValue = document.createElement("span");
                quantityValue.setAttribute("data-product-quantity-pill-value", product.id);
                quantityPill.appendChild(quantityValue);
              }
              quantityValue.textContent = String(currentQuantity);

              let subtotalPill = imageOverlay.querySelector("[data-product-subtotal-pill]") as HTMLSpanElement | null;
              if (!subtotalPill) {
                subtotalPill = document.createElement("span");
                subtotalPill.setAttribute("data-product-subtotal-pill", product.id);
                imageOverlay.appendChild(subtotalPill);
              }
              subtotalPill.style.display = "inline-flex";
              subtotalPill.style.alignItems = "center";
              subtotalPill.style.padding = "4px 8px";
              subtotalPill.style.borderRadius = "999px";
              subtotalPill.style.background = "rgba(15,23,42,0.42)";
              subtotalPill.style.color = "#ffffff";
              subtotalPill.style.fontSize = "11px";
              subtotalPill.style.fontWeight = "700";
              subtotalPill.textContent = `${subtotalAmount.toFixed(2)}€`;
            } else {
              imageOverlay.remove();
            }
          }
        }

        let title = card.querySelector("[data-product-title]") as HTMLDivElement | null;
        if (!title) {
          title = document.createElement("div");
          title.setAttribute("data-product-title", product.id);
          card.appendChild(title);
        }
        title.setAttribute("data-product-open-gallery", product.id);
        title.className = "mt-2 text-sm font-semibold";
        title.style.cursor = "pointer";
        title.style.width = "100%";
        title.style.overflow = "hidden";
        title.style.textOverflow = "ellipsis";
        title.style.whiteSpace = "nowrap";
        title.style.lineHeight = "1.2";
        title.style.textAlign = "center";
        title.textContent = host.getChoiceDisplayLabel(product, product.id);

        let pricing = card.querySelector("[data-product-pricing]") as HTMLDivElement | null;
        if (!pricing) {
          pricing =
            (card.querySelector(".template-product-meta") as HTMLDivElement | null)
            || (card.querySelector("[data-product-meta-row]") as HTMLDivElement | null);
        }
        if (!pricing) {
          pricing = document.createElement("div");
          pricing.setAttribute("data-product-pricing", product.id);
          card.appendChild(pricing);
        }
        pricing.className = "mt-1 text-xs";
        pricing.style.width = "100%";
        pricing.style.display = "flex";
        pricing.style.alignItems = "baseline";
        pricing.style.justifyContent = "center";
        pricing.style.gap = "4px";
        pricing.style.textAlign = "center";

        let primaryPrice = pricing.querySelector("[data-product-price]") as HTMLSpanElement | null;
        if (!primaryPrice) {
          primaryPrice = document.createElement("span");
          primaryPrice.setAttribute("data-product-price", product.id);
          pricing.appendChild(primaryPrice);
        }
        primaryPrice.className = "font-semibold";
        primaryPrice.style.fontSize = "13px";
        primaryPrice.style.overflowWrap = "anywhere";
        primaryPrice.textContent =
          product.discount_price !== null
            ? `${product.discount_price.toFixed(2)}€`
            : product.sale_price !== null
              ? `${product.sale_price.toFixed(2)}€`
              : "Price on request";

        let existingControls = card.querySelector("[data-product-controls]") as HTMLDivElement | null;
        if (!existingControls) {
          existingControls = document.createElement("div");
          existingControls.setAttribute("data-product-controls", product.id);
          card.appendChild(existingControls);
        }
        existingControls.style.width = "100%";
        existingControls.style.display = "grid";
        existingControls.style.placeItems = "center";

        const controls = existingControls;
        controls.setAttribute("data-product-control-row", product.id);
        controls.className = "flex items-center gap-1";
        controls.style.display = "flex";
        controls.style.alignItems = "center";
        controls.style.justifyContent = "center";
        controls.style.flexShrink = "0";
        controls.style.columnGap = "8px";
        controls.style.margin = "0 auto";
        controls.style.padding = "5px 8px";
        controls.style.borderRadius = "999px";
        controls.style.background = "#eef2f7";

        const buildAction = (
          action: "add" | "inc" | "dec" | "remove",
          label: string,
          disabled = false,
        ) => {
          const selector = `[data-product-action-slot="${action}"][data-product-id="${product.id}"]`;
          let button = controls!.querySelector(selector) as HTMLButtonElement | null;
          if (!button) {
            button = document.createElement("button");
            button.type = "button";
            button.className = "btn";
            button.setAttribute("data-product-action-slot", action);
          }
          button.textContent = label;
          button.setAttribute("data-product-action", action);
          button.setAttribute("data-product-id", product.id);
          button.setAttribute("aria-label", action === "add" ? `Add ${product.name}` : `${action} ${product.name}`);
          button.disabled = disabled;
          return button;
        };

        const decButton = buildAction("dec", "−", currentQuantity <= 0);
        styleProductActionButton(decButton);
        controls.appendChild(decButton);

        const incButton = buildAction("inc", "+", maxReached);
        incButton.setAttribute("data-product-action", "add");
        incButton.setAttribute("aria-label", `Add ${product.name}`);
        styleProductActionButton(incButton, { emphasized: true });

        let quantityLabel = controls.querySelector(`[data-product-quantity-label="${product.id}"]`) as HTMLSpanElement | null;
        if (!quantityLabel) {
          quantityLabel = document.createElement("span");
          quantityLabel.setAttribute("data-product-quantity-label", product.id);
          controls.appendChild(quantityLabel);
        }
        quantityLabel.style.minWidth = "18px";
        quantityLabel.style.textAlign = "center";
        quantityLabel.style.fontSize = "13px";
        quantityLabel.style.fontWeight = "700";
        quantityLabel.style.fontVariantNumeric = "tabular-nums";
        quantityLabel.textContent = String(currentQuantity);
        controls.appendChild(quantityLabel);
        controls.appendChild(incButton);

        Array.from(controls.querySelectorAll("[data-product-action-slot]")).forEach((node) => {
          const action = (node as HTMLElement).getAttribute("data-product-action-slot");
          if (!action || !["dec", "inc"].includes(action)) {
            node.remove();
          }
        });
      });

      Array.from(productList.querySelectorAll("[data-product-card]")).forEach((node) => {
        const productId = (node as HTMLElement).getAttribute("data-product-card");
        if (productId && !products.some((product: any) => product.id === productId)) {
          node.remove();
        }
      });
      host.renderProductListGlobalCart();
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

      const gallery =
        (selectionElement.querySelector(
          `[data-image-gallery-catalog="${fieldConfig.name}"]`,
        ) as HTMLDivElement | null)
        ?? (selectionElement as HTMLDivElement);

      if (gallery === selectionElement) {
        gallery.setAttribute("data-image-gallery-catalog", fieldConfig.name);
      }

      gallery.style.display = "grid";
      gallery.style.gridTemplateColumns = "repeat(auto-fit, minmax(180px, 1fr))";
      gallery.style.gap = "10px";
      gallery.style.marginBottom = "14px";

      images.forEach((imageItem: any) => {
        const selected = Boolean(selectedMap[imageItem.id]);
        const disabled = !selected && limitReached && !isSingleSelect;

        let card = gallery!.querySelector(`[data-image-card="${imageItem.id}"]`) as HTMLDivElement | null;
        if (!card) {
          card = document.createElement("div");
          card.setAttribute("data-image-card", imageItem.id);
          gallery!.appendChild(card);
        }
        card.setAttribute("data-image-open-gallery", imageItem.id);
        if (isSingleSelect) {
          card.setAttribute("data-image-gallery-action", "toggle");
          card.setAttribute("data-image-id", imageItem.id);
        } else {
          card.removeAttribute("data-image-gallery-action");
          card.removeAttribute("data-image-id");
        }
        card.className = "rounded border border-base-300 p-2 transition-all";
        card.style.cursor = "pointer";
        card.style.opacity = "1";
        card.style.borderColor = selected ? "rgb(59 130 246)" : "";
        card.style.boxShadow = selected ? "0 0 0 2px rgba(59, 130, 246, 0.15)" : "";
        card.style.background = selected ? "rgba(59, 130, 246, 0.06)" : "rgba(248, 250, 252, 0.82)";
        if (isSelectProductField) {
          card.style.padding = "10px";
          card.style.borderRadius = "18px";
        }

        const previewSrc = imageItem.image_medium || imageItem.image_thumbnail;
        let preview = card.querySelector("img") as HTMLImageElement | null;
        if (!preview && previewSrc) {
          preview = document.createElement("img");
          preview.setAttribute("data-image-preview", imageItem.id);
          card.appendChild(preview);
        }
        if (preview) {
          preview.src = previewSrc || "";
          preview.alt = host.getChoiceDisplayLabel(imageItem, imageItem.id);
          preview.style.width = "100%";
          preview.style.height = isSelectProductField ? "220px" : "140px";
          preview.style.objectFit = "cover";
          preview.style.borderRadius = "8px";
        }

        let title = card.querySelector("[data-image-title]") as HTMLDivElement | null;
        if (!title) {
          title = document.createElement("div");
          title.setAttribute("data-image-title", imageItem.id);
          card.appendChild(title);
        }
        title.className = "mt-2 text-sm font-semibold";
        title.style.overflowWrap = "anywhere";
        title.style.wordBreak = "break-word";
        title.textContent = host.getChoiceDisplayLabel(imageItem, imageItem.id);
        if (isSelectProductField) {
          title.style.fontSize = "18px";
          title.style.lineHeight = "1.3";
          title.style.marginTop = "12px";
        }

        const photoCount = imageItem.photos_full.length;
        let stateRow = card.querySelector("[data-image-meta-row]") as HTMLDivElement | null;
        if (!stateRow) {
          stateRow = document.createElement("div");
          stateRow.setAttribute("data-image-meta-row", imageItem.id);
          card.appendChild(stateRow);
        }
        stateRow.className = "mt-2 flex items-center justify-between gap-2";
        if (isSelectProductField) {
          stateRow.style.marginTop = "6px";
        }

        let galleryBadge = stateRow.querySelector("[data-image-gallery-badge]") as HTMLSpanElement | null;
        if (!galleryBadge) {
          galleryBadge = document.createElement("span");
          galleryBadge.setAttribute("data-image-gallery-badge", imageItem.id);
          stateRow.appendChild(galleryBadge);
        }
        galleryBadge.className = "text-[11px] font-semibold uppercase tracking-[0.12em]";
        galleryBadge.style.opacity = "0.68";
        galleryBadge.style.overflowWrap = "anywhere";
        if (isSelectProductField) {
          galleryBadge.className = "text-sm font-semibold";
          galleryBadge.style.opacity = "1";
          const unitPrice = imageItem.discount_price ?? imageItem.sale_price;
          galleryBadge.textContent = unitPrice !== undefined && unitPrice !== null
            ? `${Number(unitPrice).toFixed(2)}€`
            : "";
        } else {
          galleryBadge.textContent = photoCount ? `${photoCount} photos` : "single image";
        }

        let selectedBadge = stateRow.querySelector("[data-image-gallery-state]") as HTMLSpanElement | null;
        if (!selectedBadge) {
          selectedBadge = document.createElement("span");
          selectedBadge.setAttribute("data-image-gallery-state", imageItem.id);
          stateRow.appendChild(selectedBadge);
        }
        if (selected) {
          selectedBadge.className = "text-[11px] font-semibold";
          selectedBadge.style.display = "inline-flex";
          selectedBadge.style.padding = "4px 8px";
          selectedBadge.style.borderRadius = "999px";
          selectedBadge.style.background = "rgba(59, 130, 246, 0.12)";
          selectedBadge.style.color = "rgb(29, 78, 216)";
          selectedBadge.style.whiteSpace = "nowrap";
          selectedBadge.textContent = "Selected";
        } else if (disabled) {
          selectedBadge.className = "text-[11px] font-semibold";
          selectedBadge.style.display = "inline-flex";
          selectedBadge.style.padding = "4px 8px";
          selectedBadge.style.borderRadius = "999px";
          selectedBadge.style.background = "rgba(148, 163, 184, 0.12)";
          selectedBadge.style.color = "#475569";
          selectedBadge.style.whiteSpace = "nowrap";
          selectedBadge.textContent = "Limit reached";
        } else {
          selectedBadge.className = "text-[11px] opacity-70";
          selectedBadge.style.display = "inline-flex";
          selectedBadge.style.padding = "0";
          selectedBadge.style.background = "transparent";
          selectedBadge.style.color = "";
          selectedBadge.style.whiteSpace = "nowrap";
          selectedBadge.textContent = "Available";
        }
        if (isSelectProductField) {
          if (selected) {
            selectedBadge.className = "text-[11px] font-semibold";
            selectedBadge.style.display = "inline-flex";
            selectedBadge.style.padding = "4px 8px";
            selectedBadge.style.borderRadius = "999px";
            selectedBadge.style.background = "rgba(59, 130, 246, 0.12)";
            selectedBadge.style.color = "rgb(29, 78, 216)";
            selectedBadge.style.whiteSpace = "nowrap";
            selectedBadge.textContent = "Selected";
          } else {
            selectedBadge.textContent = "";
            selectedBadge.style.display = "none";
            selectedBadge.style.padding = "0";
            selectedBadge.style.background = "transparent";
            selectedBadge.style.color = "";
          }
        }

        let controls = card.querySelector("[data-image-controls]") as HTMLDivElement | null;
        if (!controls) {
          controls = document.createElement("div");
          controls.setAttribute("data-image-controls", imageItem.id);
          card.appendChild(controls);
        }
        controls.setAttribute("data-image-gallery-control-row", imageItem.id);
        if (isSingleSelect) {
          controls.className = "hidden";
          Array.from(controls.querySelectorAll('[data-image-gallery-action="toggle"]')).forEach((node) => node.remove());
        } else {
          const toggleButton = document.createElement("button");
          toggleButton.type = "button";
          toggleButton.className = "btn";
          toggleButton.textContent = selected ? "×" : "+";
          toggleButton.setAttribute("data-image-gallery-action", "toggle");
          toggleButton.setAttribute("data-image-id", imageItem.id);
          toggleButton.setAttribute("aria-label", selected ? `Remove ${imageItem.name}` : `Select ${imageItem.name}`);
          toggleButton.disabled = disabled;
          toggleButton.style.width = "36px";
          toggleButton.style.minWidth = "36px";
          toggleButton.style.height = "36px";
          toggleButton.style.padding = "0";
          toggleButton.style.display = "inline-flex";
          toggleButton.style.alignItems = "center";
          toggleButton.style.justifyContent = "center";
          toggleButton.style.borderRadius = "999px";
          toggleButton.style.fontSize = "14px";
          toggleButton.style.fontWeight = "700";
          toggleButton.style.boxShadow = "none";
          toggleButton.style.border = selected ? "1px solid transparent" : "1px solid rgba(148, 163, 184, 0.4)";
          toggleButton.style.background = selected ? "transparent" : "#0f172a";
          toggleButton.style.color = selected ? "#0f172a" : "#ffffff";
          controls.className = "mt-2 flex items-center justify-center";
          const existingToggle = controls.querySelector(
            '[data-image-gallery-action="toggle"]',
          ) as HTMLButtonElement | null;
          if (existingToggle && existingToggle !== toggleButton) {
            existingToggle.remove();
          }
          controls.appendChild(toggleButton);
        }
      });

      Array.from(gallery.querySelectorAll("[data-image-card]")).forEach((node) => {
        const imageId = (node as HTMLElement).getAttribute("data-image-card");
        if (imageId && !images.some((image: any) => image.id === imageId)) {
          node.remove();
        }
      });

      if (isSelectProductField) {
        const existingSelectedPanel = selectionElement.querySelector(
          `[data-image-gallery-selection="${fieldConfig.name}"]`,
        ) as HTMLDivElement | null;
        if (existingSelectedPanel) {
          existingSelectedPanel.style.display = "none";
        }
        return;
      }

      const selectedPanel = host.ensureSelectionChild(
        selectionElement,
        `[data-image-gallery-selection="${fieldConfig.name}"]`,
        "div",
        "",
        "data-image-gallery-selection",
        fieldConfig.name,
      ) as HTMLDivElement;
      selectedPanel.className = "rounded border border-base-300 p-3";

      const heading = host.ensureSelectionChild(
        selectedPanel,
        `[data-image-gallery-heading="${fieldConfig.name}"]`,
        "div",
        "mb-2 text-sm font-semibold",
        "data-image-gallery-heading",
        fieldConfig.name,
      );
      heading.className = "mb-2 text-sm font-semibold";
      heading.textContent = `${isSelectProductField ? "Selected Products" : "Selected Images"} (${selectedItems.length}${selectionLimit ? `/${selectionLimit}` : ""})`;
      const selectedBody = host.ensureSelectionChild(
        selectedPanel,
        `[data-image-gallery-selection-body="${fieldConfig.name}"]`,
        "div",
        "",
        "data-image-gallery-selection-body",
        fieldConfig.name,
      ) as HTMLDivElement;
      const emptyState = host.ensureSelectionChild(
        selectedBody,
        `[data-image-gallery-empty="${fieldConfig.name}"]`,
        "div",
        "grid gap-1 rounded border border-base-300 px-3 py-3 text-xs",
        "data-image-gallery-empty",
        fieldConfig.name,
      ) as HTMLDivElement;
      emptyState.style.background = "rgba(248, 250, 252, 0.82)";
      const emptyTitle = host.ensureSelectionChild(
        emptyState,
        `[data-image-gallery-empty-title="${fieldConfig.name}"]`,
        "div",
        "font-semibold",
        "data-image-gallery-empty-title",
        fieldConfig.name,
      );
      emptyTitle.textContent = isSelectProductField ? "No product selected" : "No image selected";
      const emptyHint = host.ensureSelectionChild(
        emptyState,
        `[data-image-gallery-empty-hint="${fieldConfig.name}"]`,
        "div",
        "",
        "data-image-gallery-empty-hint",
        fieldConfig.name,
      );
      emptyHint.style.opacity = "0.72";
      emptyHint.textContent = isSelectProductField
        ? "Use the product cards above to build the selection."
        : "Use the gallery cards above to build the selection.";
      const list = host.ensureSelectionChild(
        selectedBody,
        `[data-image-gallery-list="${fieldConfig.name}"]`,
        "div",
        "",
        "data-image-gallery-list",
        fieldConfig.name,
      ) as HTMLDivElement;
      list.style.display = "grid";
      list.style.gap = "8px";

      if (!selectedItems.length) {
        emptyState.style.display = "";
        list.style.display = "none";
        Array.from(list.querySelectorAll("[data-image-gallery-item]")).forEach((node) => node.remove());
        return;
      }

      emptyState.style.display = "none";
      list.style.display = "grid";
      selectedItems.forEach((item: any) => {
        let row = list.querySelector(`[data-image-gallery-item="${item.id}"]`) as HTMLDivElement | null;
        if (!row) {
          row = document.createElement("div");
          row.setAttribute("data-image-gallery-item", item.id);
          list.appendChild(row);
        }
        row.className = "flex items-center justify-between gap-2 rounded border border-base-300 px-2 py-2";
        row.style.background = "rgba(248, 250, 252, 0.82)";

        let nameWrap = row.querySelector("[data-image-gallery-name-wrap]") as HTMLDivElement | null;
        if (!nameWrap) {
          nameWrap = document.createElement("div");
          nameWrap.setAttribute("data-image-gallery-name-wrap", item.id);
          row.appendChild(nameWrap);
        }
        nameWrap.className = "flex min-w-0 items-center gap-2";

        let thumb = nameWrap.querySelector("[data-image-gallery-thumb]") as HTMLImageElement | null;
        if (!thumb && (item.image_thumbnail || item.image_medium)) {
          thumb = document.createElement("img");
          thumb.setAttribute("data-image-gallery-thumb", item.id);
          nameWrap.appendChild(thumb);
        }
        if (thumb) {
          if (item.image_thumbnail || item.image_medium) {
            thumb.src = item.image_thumbnail || item.image_medium || "";
            thumb.alt = item.name;
            thumb.style.display = "";
          } else {
            thumb.remove();
            thumb = null;
          }
        }
        if (thumb) {
          thumb.style.width = "40px";
          thumb.style.height = "40px";
          thumb.style.objectFit = "cover";
          thumb.style.borderRadius = "8px";
          thumb.style.flexShrink = "0";
        }

        let name = nameWrap.querySelector("[data-image-gallery-name]") as HTMLDivElement | null;
        if (!name) {
          name = document.createElement("div");
          name.setAttribute("data-image-gallery-name", item.id);
          nameWrap.appendChild(name);
        }
        name.className = "text-sm";
        name.style.overflowWrap = "anywhere";
        name.style.wordBreak = "break-word";
        name.textContent = item.name;

        let price = row.querySelector("[data-image-gallery-price]") as HTMLDivElement | null;
        const unitPrice = item.discount_price ?? item.sale_price;
        if (!price && isSelectProductField && unitPrice !== undefined && unitPrice !== null) {
          price = document.createElement("div");
          price.setAttribute("data-image-gallery-price", item.id);
          row.appendChild(price);
        }
        if (price) {
          if (isSelectProductField && unitPrice !== undefined && unitPrice !== null) {
            price.className = "text-xs font-semibold";
            price.style.marginLeft = "auto";
            price.style.whiteSpace = "nowrap";
            price.textContent = `${Number(unitPrice).toFixed(2)}€`;
          } else {
            price.remove();
            price = null;
          }
        }

        let remove = row.querySelector('[data-image-gallery-action="remove"]') as HTMLButtonElement | null;
        if (!remove) {
          remove = document.createElement("button");
          remove.setAttribute("data-image-gallery-action", "remove");
          row.appendChild(remove);
        }
        remove.type = "button";
        remove.className = "btn";
        remove.textContent = "×";
        remove.setAttribute("data-image-id", item.id);
        remove.setAttribute("aria-label", `Remove ${item.name}`);
        remove.style.width = "32px";
        remove.style.minWidth = "32px";
        remove.style.height = "32px";
        remove.style.padding = "0";
        remove.style.display = "inline-flex";
        remove.style.alignItems = "center";
        remove.style.justifyContent = "center";
        remove.style.borderRadius = "999px";
        remove.style.fontSize = "16px";
        remove.style.boxShadow = "none";
        remove.style.border = "1px solid transparent";
        remove.style.background = "transparent";
        remove.style.color = "#0f172a";
      });
      Array.from(list.querySelectorAll("[data-image-gallery-item]")).forEach((node) => {
        const imageId = (node as HTMLElement).getAttribute("data-image-gallery-item");
        if (imageId && !selectedItems.some((item: any) => item.id === imageId)) {
          node.remove();
        }
      });
    },
  };
}
