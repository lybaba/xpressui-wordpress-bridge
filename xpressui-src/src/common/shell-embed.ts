/**
 * Shell embed utilities — helpers for XPressUI forms embedded inside a host page.
 */

/**
 * Observe document.body size changes and post the height to the parent window,
 * allowing an embedding host to resize its container to fit the form content.
 *
 * Measuring from inside the embedded context (rather than relying on a host-side
 * observer) avoids race conditions caused by iOS Safari workarounds that reset
 * the container height before measuring.
 *
 * No-op when not inside an embedded context or when ResizeObserver is unavailable.
 */
export function attachEmbedResizeReporter(): void {
  if (typeof window === 'undefined' || window.parent === window || !window.ResizeObserver) return;
  new ResizeObserver(() => {
    const h = Math.max(
      document.body.scrollHeight ?? 0,
      document.body.offsetHeight ?? 0,
      document.documentElement?.scrollHeight ?? 0,
      document.documentElement?.offsetHeight ?? 0,
    );
    if (h > 0) {
      try {
        window.parent.postMessage({ type: 'xpressui:resize', height: h }, '*');
      } catch {
        // cross-origin parent may block postMessage — silently ignore
      }
    }
  }).observe(document.body);
}
