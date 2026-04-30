<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><main class="form-frame" data-template-zone="form_frame">
  <div class="template-submit-overlay" data-submit-overlay role="status" aria-live="polite" aria-label="Submitting form">
    <div class="template-submit-overlay-spinner"></div>
    <span class="template-submit-overlay-label">Submitting…</span>
  </div>
<?php xpressui_bridge_template_include_template('runtime-mount.php', $xpressui_ctx); ?>
  <button type="button" class="xpui-cart-trigger" data-product-cart-trigger aria-label="Open cart" aria-haspopup="dialog" aria-expanded="false" hidden>
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="20" r="1"></circle><circle cx="18" cy="20" r="1"></circle><path d="M5 6h16l-1.5 9h-11z"></path><path d="M5 6 4 3H2"></path></svg>
  </button>
  <div class="xpui-cart-overlay" data-product-cart-overlay data-state="closed" aria-hidden="true">
    <aside class="xpui-cart-panel" data-product-list-global-cart data-product-cart-panel aria-label="Mini cart"></aside>
  </div>
  <dialog class="xpui-capture-dialog" data-mobile-capture-modal>
    <div class="xpui-capture-panel">
      <div class="xpui-capture-title" data-mobile-capture-modal-title>Scan to capture on your phone</div>
      <img class="xpui-capture-qr" data-mobile-capture-modal-qr alt="QR code" />
      <div class="xpui-capture-status" data-mobile-capture-modal-status>Waiting for capture…</div>
      <button type="button" class="template-field-pill" data-mobile-capture-modal-close>Cancel</button>
    </div>
  </dialog>
</main>

