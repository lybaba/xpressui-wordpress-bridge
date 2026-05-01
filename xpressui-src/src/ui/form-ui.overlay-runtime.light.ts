type TOverlayRuntimeHost = any;

export function createOverlayRuntime(_host: TOverlayRuntimeHost) {
  return {
    ensureProductCartTrigger: () => null,
    ensureProductListGlobalCart: () => null,
    openProductCartModal: () => undefined,
    closeProductCartModal: () => undefined,
    acquirePageScrollLock: () => undefined,
    releasePageScrollLock: () => undefined,
    getFocusableElements: (_container: HTMLElement) => [],
    applyHostAriaHiddenForOverlay: () => undefined,
    restoreHostAriaHiddenAfterOverlay: () => undefined,
    setupOverlayAccessibility: (
      _overlay: HTMLElement,
      _dialog: HTMLElement,
      _onEscape: () => void,
      _preferredFocusElement?: HTMLElement | null,
    ) => undefined,
    teardownOverlayAccessibility: (_restoreFocus: boolean = true) => undefined,
  };
}
