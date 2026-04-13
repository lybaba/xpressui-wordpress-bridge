type TOverlayRuntimeHost = any;

export function createOverlayRuntime(host: TOverlayRuntimeHost) {
  return {
    createCartGlyph() {
      const icon = document.createElement("span");
      icon.setAttribute("aria-hidden", "true");
      icon.style.display = "inline-flex";
      icon.style.alignItems = "center";

      const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
      svg.setAttribute("width", "12");
      svg.setAttribute("height", "12");
      svg.setAttribute("viewBox", "0 0 24 24");
      svg.setAttribute("fill", "none");
      svg.setAttribute("stroke", "currentColor");
      svg.setAttribute("stroke-width", "2");
      svg.setAttribute("stroke-linecap", "round");
      svg.setAttribute("stroke-linejoin", "round");

      const circleLeft = document.createElementNS("http://www.w3.org/2000/svg", "circle");
      circleLeft.setAttribute("cx", "9");
      circleLeft.setAttribute("cy", "20");
      circleLeft.setAttribute("r", "1");
      svg.appendChild(circleLeft);

      const circleRight = document.createElementNS("http://www.w3.org/2000/svg", "circle");
      circleRight.setAttribute("cx", "18");
      circleRight.setAttribute("cy", "20");
      circleRight.setAttribute("r", "1");
      svg.appendChild(circleRight);

      const basket = document.createElementNS("http://www.w3.org/2000/svg", "path");
      basket.setAttribute("d", "M5 6h16l-1.5 9h-11z");
      svg.appendChild(basket);

      const handle = document.createElementNS("http://www.w3.org/2000/svg", "path");
      handle.setAttribute("d", "M5 6 4 3H2");
      svg.appendChild(handle);

      icon.appendChild(svg);
      return icon;
    },

    createCartCountBadge(value: string) {
      const badge = document.createElement("span");
      badge.className = "text-xs opacity-70";
      badge.style.lineHeight = "1.2";
      badge.style.display = "inline-flex";
      badge.style.alignItems = "center";
      badge.style.gap = "4px";

      badge.appendChild(host.createCartGlyph());

      const text = document.createElement("span");
      text.textContent = value;
      badge.appendChild(text);

      return badge;
    },

    ensureProductCartTrigger() {
      const formElement = host.querySelector("form");
      if (!formElement) {
        return null;
      }

      let trigger = host.querySelector("[data-product-cart-trigger]") as HTMLButtonElement | null;
      if (!trigger) {
        trigger = document.createElement("button");
        trigger.type = "button";
        trigger.className = "btn btn-primary";
        trigger.setAttribute("data-product-cart-trigger", "true");
        trigger.style.position = "fixed";
        trigger.style.right = "20px";
        trigger.style.bottom = "20px";
        trigger.style.zIndex = "10001";
        trigger.style.display = "inline-flex";
        trigger.style.alignItems = "center";
        trigger.style.justifyContent = "center";
        trigger.style.width = "50px";
        trigger.style.height = "50px";
        trigger.style.borderRadius = "999px";
        trigger.style.padding = "0";
        trigger.style.fontSize = "20px";
        trigger.style.lineHeight = "1";
        trigger.setAttribute("aria-label", "Open cart");
        trigger.setAttribute("aria-haspopup", "dialog");
        trigger.setAttribute("aria-expanded", "false");
        host.appendChild(trigger);
      }

      return trigger;
    },

    ensureProductListGlobalCart() {
      const formElement = host.querySelector("form");
      if (!formElement) {
        return null;
      }

      if (host.productCartOverlay && host.contains(host.productCartOverlay)) {
        return host.productCartOverlay.querySelector("[data-product-list-global-cart]") as HTMLElement | null;
      }

      const overlay = document.createElement("div");
      overlay.setAttribute("data-product-cart-overlay", "true");
      overlay.setAttribute("data-state", "closed");
      overlay.setAttribute("aria-hidden", "true");
      overlay.style.position = "fixed";
      overlay.style.inset = "0";
      overlay.style.background = "rgba(15, 23, 42, 0.26)";
      overlay.style.zIndex = "10002";
      overlay.style.display = "none";
      overlay.style.justifyContent = "flex-end";
      overlay.style.opacity = "0";
      overlay.style.visibility = "hidden";
      overlay.style.transition = "opacity 180ms ease";

      const panel = document.createElement("aside");
      panel.setAttribute("data-product-list-global-cart", "true");
      panel.setAttribute("data-product-cart-panel", "true");
      panel.id = `${host.getAttribute("name") || "form"}_product_cart_panel`;
      panel.setAttribute("aria-label", "Mini cart");
      panel.style.width = "min(340px, 88vw)";
      panel.style.height = "100%";
      panel.style.background = "rgba(255, 255, 255, 0.98)";
      panel.style.borderLeft = "1px solid rgba(15, 23, 42, 0.08)";
      panel.style.padding = "14px";
      panel.style.overflowY = "auto";
      panel.style.display = "flex";
      panel.style.flexDirection = "column";
      panel.style.gap = "10px";
      panel.style.transform = "translateX(100%)";
      panel.style.transition = "transform 180ms ease";
      panel.style.boxShadow = "-24px 0 48px -36px rgba(15, 23, 42, 0.28)";

      overlay.appendChild(panel);
      host.appendChild(overlay);
      host.productCartOverlay = overlay;

      return panel;
    },

    openProductCartModal() {
      if (!host.productCartOverlay) {
        host.ensureProductListGlobalCart();
      }
      if (!host.productCartOverlay) {
        return;
      }
      if (host.productCartCloseTimer !== null && typeof window !== "undefined") {
        window.clearTimeout(host.productCartCloseTimer);
        host.productCartCloseTimer = null;
      }
      host.productCartOverlay.setAttribute("data-state", "open");
      host.productCartOverlay.style.display = "flex";
      host.productCartOverlay.style.visibility = "visible";
      const panel = host.productCartOverlay.querySelector("[data-product-cart-panel]") as HTMLElement | null;
      const closeButton = host.productCartOverlay.querySelector("[data-product-cart-close]") as HTMLElement | null;
      if (panel) {
        host.setupOverlayAccessibility(
          host.productCartOverlay,
          panel,
          () => host.closeProductCartModal(),
          closeButton,
        );
      }
      const trigger = host.querySelector("[data-product-cart-trigger]") as HTMLElement | null;
      if (trigger) {
        trigger.setAttribute("aria-expanded", "true");
      }
      if (typeof window !== "undefined" && typeof window.requestAnimationFrame === "function") {
        window.requestAnimationFrame(() => {
          host.productCartOverlay!.style.opacity = "1";
          if (panel) {
            panel.style.transform = "translateX(0)";
          }
        });
      } else {
        host.productCartOverlay.style.opacity = "1";
        if (panel) {
          panel.style.transform = "translateX(0)";
        }
      }
      host.acquirePageScrollLock();
    },

    closeProductCartModal() {
      if (!host.productCartOverlay) {
        return;
      }
      host.productCartOverlay.setAttribute("data-state", "closing");
      host.productCartOverlay.style.opacity = "0";
      const panel = host.productCartOverlay.querySelector("[data-product-cart-panel]") as HTMLElement | null;
      if (panel) {
        panel.style.transform = "translateX(100%)";
      }
      if (host.productCartCloseTimer !== null && typeof window !== "undefined") {
        window.clearTimeout(host.productCartCloseTimer);
      }
      if (typeof window !== "undefined") {
        host.productCartCloseTimer = window.setTimeout(() => {
          if (!host.productCartOverlay) {
            return;
          }
          host.productCartOverlay.style.display = "none";
          host.productCartOverlay.style.visibility = "hidden";
          host.productCartOverlay.setAttribute("data-state", "closed");
          host.productCartOverlay.setAttribute("aria-hidden", "true");
          host.teardownOverlayAccessibility(true);
          const trigger = host.querySelector("[data-product-cart-trigger]") as HTMLElement | null;
          if (trigger) {
            trigger.setAttribute("aria-expanded", "false");
          }
          host.releasePageScrollLock();
          host.productCartCloseTimer = null;
        }, 180);
      } else {
        host.productCartOverlay.style.display = "none";
        host.productCartOverlay.style.visibility = "hidden";
        host.productCartOverlay.setAttribute("data-state", "closed");
        host.productCartOverlay.setAttribute("aria-hidden", "true");
        host.teardownOverlayAccessibility(true);
        const trigger = host.querySelector("[data-product-cart-trigger]") as HTMLElement | null;
        if (trigger) {
          trigger.setAttribute("aria-expanded", "false");
        }
        host.releasePageScrollLock();
      }
    },

    acquirePageScrollLock() {
      if (typeof document === "undefined") {
        return;
      }
      const body = document.body;
      if (!body) {
        return;
      }
      if (host.pageScrollLockCount === 0) {
        host.pageScrollPreviousOverflow = body.style.overflow || "";
        body.style.overflow = "hidden";
      }
      host.pageScrollLockCount += 1;
    },

    releasePageScrollLock() {
      if (typeof document === "undefined") {
        return;
      }
      const body = document.body;
      if (!body || host.pageScrollLockCount <= 0) {
        return;
      }
      host.pageScrollLockCount -= 1;
      if (host.pageScrollLockCount === 0) {
        body.style.overflow = host.pageScrollPreviousOverflow || "";
        host.pageScrollPreviousOverflow = null;
      }
    },

    getFocusableElements(container: HTMLElement): HTMLElement[] {
      const selectors = [
        "button:not([disabled])",
        "a[href]",
        "input:not([disabled])",
        "select:not([disabled])",
        "textarea:not([disabled])",
        "[tabindex]:not([tabindex='-1'])",
      ];
      const candidates = Array.from(container.querySelectorAll(selectors.join(", "))) as HTMLElement[];
      return candidates.filter((element) => {
        if (element.getAttribute("aria-hidden") === "true") {
          return false;
        }
        const computed = typeof window !== "undefined" ? window.getComputedStyle(element) : null;
        if (computed && (computed.display === "none" || computed.visibility === "hidden")) {
          return false;
        }
        return true;
      });
    },

    applyHostAriaHiddenForOverlay() {
      const formElement = host.querySelector("form");
      if (!formElement) {
        return;
      }
      host.hostAriaHiddenBeforeOverlay = formElement.getAttribute("aria-hidden");
      formElement.setAttribute("aria-hidden", "true");
    },

    restoreHostAriaHiddenAfterOverlay() {
      const formElement = host.querySelector("form");
      if (!formElement) {
        return;
      }
      if (host.hostAriaHiddenBeforeOverlay === null) {
        formElement.removeAttribute("aria-hidden");
      } else {
        formElement.setAttribute("aria-hidden", host.hostAriaHiddenBeforeOverlay);
      }
      host.hostAriaHiddenBeforeOverlay = null;
    },

    setupOverlayAccessibility(
      overlay: HTMLElement,
      dialog: HTMLElement,
      onEscape: () => void,
      preferredFocusElement?: HTMLElement | null,
    ) {
      host.teardownOverlayAccessibility(false);
      host.overlayReturnFocusElement = document.activeElement instanceof HTMLElement
        ? document.activeElement
        : null;
      host.applyHostAriaHiddenForOverlay();

      overlay.setAttribute("aria-hidden", "false");
      dialog.setAttribute("role", "dialog");
      dialog.setAttribute("aria-modal", "true");
      if (!dialog.hasAttribute("tabindex")) {
        dialog.setAttribute("tabindex", "-1");
      }

      const focusTarget = preferredFocusElement || host.getFocusableElements(dialog)[0] || dialog;
      if (typeof focusTarget.focus === "function") {
        focusTarget.focus();
      }

      const handleKeyDown = (event: KeyboardEvent) => {
        if (event.key === "Escape") {
          event.preventDefault();
          event.stopPropagation();
          onEscape();
          return;
        }
        if (event.key !== "Tab") {
          return;
        }

        const focusable = host.getFocusableElements(dialog);
        if (!focusable.length) {
          event.preventDefault();
          dialog.focus();
          return;
        }
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        const active = document.activeElement as HTMLElement | null;

        if (event.shiftKey) {
          if (active === first || !active || !dialog.contains(active)) {
            event.preventDefault();
            last.focus();
          }
          return;
        }

        if (active === last || !active || !dialog.contains(active)) {
          event.preventDefault();
          first.focus();
        }
      };

      const handleFocusIn = (event: FocusEvent) => {
        const target = event.target as Node | null;
        if (!target || dialog.contains(target)) {
          return;
        }
        const fallback = host.getFocusableElements(dialog)[0] || dialog;
        fallback.focus();
      };

      document.addEventListener("keydown", handleKeyDown, true);
      document.addEventListener("focusin", handleFocusIn, true);

      host.overlayCleanup = () => {
        document.removeEventListener("keydown", handleKeyDown, true);
        document.removeEventListener("focusin", handleFocusIn, true);
      };
    },

    teardownOverlayAccessibility(restoreFocus: boolean = true) {
      if (host.overlayCleanup) {
        host.overlayCleanup();
        host.overlayCleanup = null;
      }
      host.restoreHostAriaHiddenAfterOverlay();
      if (restoreFocus && host.overlayReturnFocusElement && typeof host.overlayReturnFocusElement.focus === "function") {
        host.overlayReturnFocusElement.focus();
      }
      host.overlayReturnFocusElement = null;
    },
  };
}
