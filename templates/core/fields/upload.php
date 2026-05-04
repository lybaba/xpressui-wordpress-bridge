<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><div class="template-field" data-template-zone="field" data-field-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" data-field-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'))); ?>">
<?php $xpressui_ctx['is_image_upload'] = xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), ["upload-image", "payment-proof"]); ?>
  <div class="template-field-label-row">
    <div class="template-field-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'label'))); ?></div>
    <div class="template-field-meta-inline">
      <span class="template-required"<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'required'))))): ?> style="display:none"<?php endif; ?>>*</span>
    </div>
  </div>
  <div class="xpressui-ref-file-block" data-ref-file-block style="display:none;">
    <a class="xpressui-ref-file-link" data-ref-file-link href="" target="_blank" rel="noopener noreferrer"></a>
    <p class="xpressui-ref-file-hint" data-ref-file-hint></p>
  </div>
  <div
    class="template-upload-box"
    data-file-drop-zone="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
    data-file-drag-active="false"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"))): ?>
data-payment-proof="true"<?php endif; ?>
  >
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"))): ?>
      <div class="template-payment-proof-summary" data-payment-proof-summary="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>">
        <div class="template-payment-proof-summary-row">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_provider_label'))): ?>
            <span class="template-payment-provider-badge" data-payment-provider-badge="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_provider'), "manual"))); ?>">
              <span class="template-payment-provider-logo" aria-hidden="true">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_provider'), "wave"))): ?>
                  W
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_provider'), "orange-money"))): ?>
                  O
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_provider'), "free-money"))): ?>
                  F
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_provider'), "bank-transfer"))): ?>
                  BT
<?php else: ?>
                  M
<?php endif; ?>
              </span>
              <span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_provider_label'))); ?></span>
            </span>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount'), xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_source'), "cart")))): ?>
            <span
              class="template-field-pill"
              data-payment-proof-amount-pill="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_source'), "cart"), (!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount')))))): ?>
style="display:none"<?php endif; ?>
            >
              <span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Expected amount:", 'xpressui-bridge'))); ?></span>
              <span data-payment-proof-amount="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_display'))): ?>
<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_display'))); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount'))): ?>
<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount'))); ?><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_currency'))): ?> <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_currency'))); ?><?php endif; ?><?php endif; ?>
              </span>
            </span>
<?php endif; ?>
        </div>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_name'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_phone')))): ?>
          <div class="template-payment-proof-summary-grid">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_name'))): ?>
              <div class="template-payment-proof-summary-item">
                <span class="template-payment-proof-summary-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Merchant", 'xpressui-bridge'))); ?></span>
                <strong><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_name'))); ?></strong>
              </div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_phone'))): ?>
              <div class="template-payment-proof-summary-item">
                <span class="template-payment-proof-summary-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Phone", 'xpressui-bridge'))); ?></span>
                <strong>
                  <a
                    class="template-payment-proof-phone"
                    href="tel:<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_phone_href'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_phone')))); ?>"
                  ><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_phone_display'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_phone')))); ?></a>
                </strong>
              </div>
<?php endif; ?>
          </div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_qr_code'))): ?>
          <div class="template-payment-proof-qr">
            <img
              class="template-payment-proof-qr-img"
              src="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_qr_code'))); ?>"
              alt="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Scan to pay", 'xpressui-bridge'))); ?>"
              loading="lazy"
            />
            <span class="template-payment-proof-qr-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Scan to pay", 'xpressui-bridge'))); ?></span>
          </div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_instructions'))): ?>
          <div class="template-payment-proof-summary-note"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_instructions'))); ?></div>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_source'), "cart"))): ?>
          <div class="template-payment-proof-summary-note"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("This amount updates from the selected cart items.", 'xpressui-bridge'))); ?></div>
<?php endif; ?>
        <ul class="template-payment-proof-checklist">
          <li><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Keep the confirmation screen visible.", 'xpressui-bridge'))); ?></li>
          <li><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Make sure the amount can be read clearly.", 'xpressui-bridge'))); ?></li>
        </ul>
      </div>
<?php endif; ?>
    <span class="template-upload-icon">↑</span>
    <div class="template-field-label">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'is_image_upload'))): ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Upload image", 'xpressui-bridge'))); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "camera-photo"))): ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Take photo", 'xpressui-bridge'))); ?>
<?php else: ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Upload file", 'xpressui-bridge'))); ?>
<?php endif; ?>
    </div>
    <div class="template-field-help">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'placeholder'))): ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'placeholder'))); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_instructions')))): ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_instructions'))); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'is_image_upload'))): ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Drag an image here or browse from your device.", 'xpressui-bridge'))); ?>
<?php else: ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Drag a file here or browse from your device.", 'xpressui-bridge'))); ?>
<?php endif; ?>
    </div>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'upload_accept_label'))): ?>
      <div class="template-upload-pills">
        <span class="template-field-pill"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'upload_accept_label'))); ?></span>
      </div>
<?php endif; ?>
    <input
      id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
      class="template-input"
      type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'input_type'))); ?>"
      name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
      data-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
      data-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'label'))); ?>"
      data-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'))); ?>"
      data-section-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'section'), 'name'))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_provider')))): ?>
data-payment-provider="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_provider'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_provider_label')))): ?>
data-payment-provider-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_provider_label'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_name')))): ?>
data-merchant-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_name'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_phone')))): ?>
data-merchant-phone="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_phone'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount')))): ?>
data-payment-amount="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_source')))): ?>
data-payment-amount-source="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_source'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_field')))): ?>
data-payment-amount-field="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_field'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_currency')))): ?>
data-payment-currency="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_currency'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_instructions')))): ?>
data-payment-instructions="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_instructions'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_qr_code')))): ?>
data-merchant-qr-code="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_merchant_qr_code'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'accept'))): ?>
accept="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'accept'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'capture'))): ?>
capture="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'capture'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'required'))): ?>
aria-required="true" data-required="true"<?php endif; ?>
    />
  </div>
  <div
    id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>_selection"
    class="template-upload-selection"
    data-upload-selection-zone="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
    style="display:none;"
  >
    <div class="template-upload-selection-row">
      <span class="template-upload-selection-title" data-upload-selection-title="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"></span>
    </div>
    <div class="template-field-help" data-upload-selection-message="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"></div>
    <div data-upload-selection-body="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"></div>
  </div>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'desc'))): ?>
    <div class="template-field-help"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'desc'))); ?></div>
<?php endif; ?>
<?php xpressui_bridge_template_include_template('field-meta.php', $xpressui_ctx); ?>
</div>
