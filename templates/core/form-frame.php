<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><main
  class="form-frame<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), 'product_items')))): ?> form-frame--commerce-landing<?php endif; ?>"
  data-template-zone="form_frame"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), 'product_items')))): ?>
data-commerce-experience-frame="true"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), 'product_items')))): ?>
data-product-landing-enabled="true"<?php endif; ?>
>
  <div class="template-submit-overlay" data-submit-overlay role="status" aria-live="polite" aria-label="Submitting form">
    <div class="template-submit-overlay-spinner"></div>
    <span class="template-submit-overlay-label">Submitting…</span>
  </div>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), 'product_items')))): ?>
<?php xpressui_bridge_template_include_template('workflow-product-catalog.php', $xpressui_ctx); ?>
<?php endif; ?>
<?php xpressui_bridge_template_include_template('runtime-mount.php', $xpressui_ctx); ?>
  <dialog class="xpui-gallery-dialog" data-product-gallery-modal aria-label="Product gallery" style="display:none;">
    <div class="xpui-gallery-panel">
      <button type="button" class="btn xpui-gallery-close" data-product-gallery-close aria-label="Close gallery">×</button>
      <div class="xpui-gallery-title" data-product-gallery-title></div>
      <div class="xpui-gallery-meta" data-product-gallery-meta></div>
      <img class="xpui-gallery-main-image" data-product-gallery-main alt="" />
      <div class="xpui-gallery-thumbs" data-product-gallery-thumbs></div>
      <div class="xpui-product-view-details">
        <div class="xpui-product-view-title" data-product-gallery-detail-title></div>
        <div class="xpui-product-view-description" data-product-gallery-description></div>
        <div class="xpui-product-view-price-row">
          <span class="xpui-product-view-price" data-product-gallery-price></span>
          <span class="xpui-product-view-regular-price" data-product-gallery-unit></span>
        </div>
        <div class="xpui-product-view-controls">
          <button type="button" class="xpui-product-view-step" data-product-gallery-decrease>-</button>
          <span class="xpui-product-view-quantity">
            <strong data-product-gallery-quantity>0</strong>
            <span>Quantity</span>
          </span>
          <button type="button" class="xpui-product-view-step xpui-product-view-step--add" data-product-gallery-increase>+</button>
        </div>
        <div class="xpui-product-view-subtotal" data-product-gallery-subtotal></div>
      </div>
    </div>
  </dialog>
  <dialog class="xpui-capture-dialog" data-mobile-capture-modal style="display:none;">
    <div class="xpui-capture-panel">
      <div class="xpui-capture-title" data-mobile-capture-modal-title>Scan to capture on your phone</div>
      <img class="xpui-capture-qr" data-mobile-capture-modal-qr alt="QR code" hidden />
      <div class="xpui-capture-status" data-mobile-capture-modal-status>Waiting for capture…</div>
      <button type="button" class="template-field-pill" data-mobile-capture-modal-close>Cancel</button>
    </div>
  </dialog>
</main>

