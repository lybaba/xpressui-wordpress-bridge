type TOverlayRuntimeHost = any;

export function createOverlayRuntime(host: TOverlayRuntimeHost) {
  return {
    ensureProductCartTrigger() {
      return host.querySelector("[data-product-cart-trigger]") as HTMLButtonElement | null;
    },

    ensureProductListGlobalCart() {
      if (host.productCartOverlay && host.contains(host.productCartOverlay)) {
        return host.productCartOverlay.querySelector("[data-product-list-global-cart]") as HTMLElement | null;
      }
      const overlay = host.querySelector("[data-product-cart-overlay]") as HTMLElement | null;
      if (overlay) {
        host.productCartOverlay = overlay;
        return overlay.querySelector("[data-product-list-global-cart]") as HTMLElement | null;
      }
      return null;
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
      const overlay = host.productCartOverlay;
      const panel = overlay.querySelector("[data-product-cart-panel]") as HTMLElement | null;
      const closeButton = overlay.querySelector("[data-product-cart-close]") as HTMLElement | null;
      overlay.setAttribute("data-state", "open");
      overlay.setAttribute("aria-hidden", "false");
      if (panel) {
        host.setupOverlayAccessibility(overlay, panel, () => host.closeProductCartModal(), closeButton);
      }
      host.querySelector("[data-product-cart-trigger]")?.setAttribute("aria-expanded", "true");
      host.acquirePageScrollLock();
    },

    closeProductCartModal() {
      if (!host.productCartOverlay) {
        return;
      }
      const overlay = host.productCartOverlay;
      overlay.setAttribute("data-state", "closed");
      if (host.productCartCloseTimer !== null && typeof window !== "undefined") {
        window.clearTimeout(host.productCartCloseTimer);
      }
      if (typeof window !== "undefined") {
        host.productCartCloseTimer = window.setTimeout(() => {
          if (!host.productCartOverlay) return;
          host.productCartOverlay.setAttribute("aria-hidden", "true");
          host.teardownOverlayAccessibility(true);
          host.querySelector("[data-product-cart-trigger]")?.setAttribute("aria-expanded", "false");
          host.releasePageScrollLock();
          host.productCartCloseTimer = null;
        }, 180);
      } else {
        overlay.setAttribute("aria-hidden", "true");
        host.teardownOverlayAccessibility(true);
        host.querySelector("[data-product-cart-trigger]")?.setAttribute("aria-expanded", "false");
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
