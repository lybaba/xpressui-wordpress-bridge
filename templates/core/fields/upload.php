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
        <div class="template-payment-step-head"><span class="template-payment-step-num">1</span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Make your payment", 'xpressui-bridge'))); ?></div>

<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_providers'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'has_multiple_providers')))): ?>
          <div class="template-payment-proof-method">
            <span class="template-payment-proof-method-caption"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Payment method", 'xpressui-bridge'))); ?></span>
            <div class="template-payment-proof-pills" role="radiogroup" aria-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Payment method", 'xpressui-bridge'))); ?>">
<?php
$xpressui_loop_parent_ctx_2 = $xpressui_ctx;
$xpressui_loop_items_1 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_providers'));
foreach ($xpressui_loop_items_1 as $xpressui_loop_index_3 => $xpressui_loop_value_4):
    $xpressui_ctx = $xpressui_loop_parent_ctx_2;
    $xpressui_ctx['prov'] = $xpressui_loop_value_4;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_3 + 1,
        'index0' => $xpressui_loop_index_3,
        'first'  => $xpressui_loop_index_3 === 0,
        'last'   => ($xpressui_loop_index_3 + 1) === count($xpressui_loop_items_1),
    ];
?>
<?php $xpressui_ctx['_logo'] = ""; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'provider'), "wave"))): ?>
<?php $xpressui_ctx['_logo'] = "wave.svg"; ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'provider'), "orange-money"))): ?>
<?php $xpressui_ctx['_logo'] = "Orange_Money.svg"; ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'provider'), "bank-transfer"))): ?>
<?php $xpressui_ctx['_logo'] = "bank-transfer.svg"; ?>
<?php endif; ?>
              <button
                type="button"
                role="radio"
                class="template-payment-proof-pill<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'loop'), 'first'))): ?> is-active<?php endif; ?>"
                aria-checked="<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'loop'), 'first'))): ?>true<?php else: ?>false<?php endif; ?>"
                aria-pressed="<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'loop'), 'first'))): ?>true<?php else: ?>false<?php endif; ?>"
                data-provider-pill="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
                data-provider="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'provider'))); ?>"
                data-payment-provider="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'provider'))); ?>"
                data-payment-provider-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'provider_label'))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone'))): ?>
data-merchant-phone="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_name'))): ?>
data-merchant-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_name'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_amount'))): ?>
data-payment-amount="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_amount'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_qr_code'))): ?>
data-merchant-qr-code="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_qr_code'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_iban'))): ?>
data-payment-iban="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_iban'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_bic'))): ?>
data-payment-bic="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_bic'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_reference_prefix'))): ?>
data-payment-reference-prefix="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_reference_prefix'))); ?>"<?php endif; ?>
              >
                <span class="template-payment-proof-pill-media">
                  <span class="template-payment-proof-pill-icon" aria-hidden="true"><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'provider'), "cash"))): ?><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg><?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'provider'), "bank-transfer"))): ?><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" x2="21" y1="22" y2="22"/><line x1="6" x2="6" y1="18" y2="11"/><line x1="10" x2="10" y1="18" y2="11"/><line x1="14" x2="14" y1="18" y2="11"/><line x1="18" x2="18" y1="18" y2="11"/><polygon points="12 2 20 7 4 7"/></svg><?php else: ?><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="20" x="5" y="2" rx="2"/><path d="M12 18h.01"/></svg><?php endif; ?></span>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, '_logo'))): ?>
<img class="template-payment-proof-pill-logo" src="/images/payment/<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, '_logo'))); ?>" alt="" aria-hidden="true" loading="lazy" onerror="this.remove()" /><?php endif; ?>
                </span>
                <span class="template-payment-proof-pill-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'provider_label'))); ?></span>
              </button>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_2; ?>
            </div>
          </div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_providers'))): ?>
<?php
$xpressui_loop_parent_ctx_6 = $xpressui_ctx;
$xpressui_loop_items_5 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_providers'));
foreach ($xpressui_loop_items_5 as $xpressui_loop_index_7 => $xpressui_loop_value_8):
    $xpressui_ctx = $xpressui_loop_parent_ctx_6;
    $xpressui_ctx['prov'] = $xpressui_loop_value_8;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_7 + 1,
        'index0' => $xpressui_loop_index_7,
        'first'  => $xpressui_loop_index_7 === 0,
        'last'   => ($xpressui_loop_index_7 + 1) === count($xpressui_loop_items_5),
    ];
?>
            <div
              class="template-payment-proof-provider-block"
              data-provider-block="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
              data-provider="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'provider'))); ?>"
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'loop'), 'first'))))): ?>
style="display:none"<?php endif; ?>
            >
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'has_multiple_providers'))))): ?>
                <div class="template-payment-proof-summary-row">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'provider_label'))): ?>
                    <label class="template-payment-proof-method">
                      <span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Payment method", 'xpressui-bridge'))); ?></span>
                      <span class="template-payment-provider-badge">
                        <span class="template-payment-provider-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'provider_label'))); ?></span>
                      </span>
                    </label>
<?php endif; ?>
<?php $xpressui_ctx['expected_amount'] = xpressui_bridge_template_or_value(xpressui_bridge_template_or_value(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_amount_display'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_display')), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_amount')), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount')); ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_or_value(xpressui_bridge_template_context_get($xpressui_ctx, 'expected_amount'), xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_source'), ["cart", "cart_total", "operator_expected_amount"])))): ?>
                    <span
                      class="template-field-pill template-payment-proof-amount-pill"
                      data-payment-proof-amount-pill="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
                      data-payment-proof-amount-source="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_source'), "fixed"))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_source'), ["cart", "cart_total"]), (!xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'expected_amount')))))): ?>
style="display:none"<?php endif; ?>
                    >
                      <span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Amount to pay", 'xpressui-bridge'))); ?></span>
                      <span data-payment-proof-amount="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" data-payment-proof-original-amount="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'expected_amount'))); ?>"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_context_get($xpressui_ctx, 'expected_amount'), "—"))); ?></span>
                    </span>
<?php endif; ?>
                  <button
                    type="button"
                    class="template-payment-proof-info"
                    title="<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_name'))): ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Merchant", 'xpressui-bridge'))); ?>: <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_name'))); ?>. <?php endif; ?><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone_display'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone')))): ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Phone", 'xpressui-bridge'))); ?>: <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone_display'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone')))); ?>. <?php endif; ?><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_instructions'))): ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_instructions'))); ?> <?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_source'), "cart"))): ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("This amount updates from the selected cart items.", 'xpressui-bridge'))); ?> <?php endif; ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Keep the confirmation screen visible. Make sure the amount can be read clearly.", 'xpressui-bridge'))); ?>"
                    aria-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Payment instructions", 'xpressui-bridge'))); ?>"
                  >i</button>
                </div>
<?php else: ?>
                <div class="template-payment-proof-summary-row">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_amount_display'))): ?>
                    <span
                      class="template-field-pill template-payment-proof-amount-pill"
                      data-payment-proof-amount-pill="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
                      data-payment-proof-amount-source="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_source'), "fixed"))); ?>"
                    >
                      <span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Amount to pay", 'xpressui-bridge'))); ?></span>
                      <span data-payment-proof-amount="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" data-payment-proof-original-amount="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_amount_display'))); ?>"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_amount_display'))); ?></span>
                    </span>
<?php endif; ?>
                  <button
                    type="button"
                    class="template-payment-proof-info"
                    title="<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_name'))): ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Merchant", 'xpressui-bridge'))); ?>: <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_name'))); ?>. <?php endif; ?><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone_display'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone')))): ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Phone", 'xpressui-bridge'))); ?>: <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone_display'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone')))); ?>. <?php endif; ?><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_instructions'))): ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_instructions'))); ?> <?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_source'), "cart"))): ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("This amount updates from the selected cart items.", 'xpressui-bridge'))); ?> <?php endif; ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Keep the confirmation screen visible. Make sure the amount can be read clearly.", 'xpressui-bridge'))); ?>"
                    aria-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Payment instructions", 'xpressui-bridge'))); ?>"
                  >i</button>
                </div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_name'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone')))): ?>
                <div class="template-payment-proof-summary-grid">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_name'))): ?>
                    <div class="template-payment-proof-summary-item">
                      <span class="template-payment-proof-summary-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Merchant", 'xpressui-bridge'))); ?></span>
                      <strong><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_name'))); ?></strong>
                    </div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone'))): ?>
                    <div class="template-payment-proof-summary-item">
                      <span class="template-payment-proof-summary-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Phone", 'xpressui-bridge'))); ?></span>
                      <strong>
                        <a class="template-payment-proof-phone" href="tel:<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone_href'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone')))); ?>" data-phone="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone_href'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone')))); ?>"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone_display'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_phone')))); ?></a>
                      </strong>
                    </div>
<?php endif; ?>
                </div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'provider'), "bank-transfer"), xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_iban'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_bic'))))): ?>
                <div class="template-payment-proof-bank-block">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_iban'))): ?>
                    <div class="template-payment-proof-summary-item">
                      <span class="template-payment-proof-summary-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("IBAN", 'xpressui-bridge'))); ?></span>
                      <div class="template-payment-proof-copy-row">
                        <span class="template-payment-proof-bank-value"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_iban_display'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_iban')))); ?></span>
                        <button type="button" class="template-payment-proof-copy-btn" data-copy-value="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_iban'))); ?>" aria-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Copy IBAN", 'xpressui-bridge'))); ?>"><svg class="tpp-copy-ic" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg><svg class="tpp-check-ic" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></button>
                      </div>
                    </div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_bic'))): ?>
                    <div class="template-payment-proof-summary-item">
                      <span class="template-payment-proof-summary-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("BIC", 'xpressui-bridge'))); ?></span>
                      <div class="template-payment-proof-copy-row">
                        <span class="template-payment-proof-bank-value"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_bic'))); ?></span>
                        <button type="button" class="template-payment-proof-copy-btn" data-copy-value="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_bic'))); ?>" aria-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Copy BIC", 'xpressui-bridge'))); ?>"><svg class="tpp-copy-ic" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg><svg class="tpp-check-ic" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></button>
                      </div>
                    </div>
<?php endif; ?>
                  <div class="template-payment-proof-summary-item">
                    <span class="template-payment-proof-summary-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Payment reference", 'xpressui-bridge'))); ?></span>
                    <div class="template-payment-proof-copy-row">
                      <span class="template-payment-proof-bank-value template-payment-proof-reference-value" data-payment-proof-reference="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>">—</span>
                      <button type="button" class="template-payment-proof-copy-btn template-payment-proof-reference-copy" data-copy-reference="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" disabled aria-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Copy reference", 'xpressui-bridge'))); ?>"><svg class="tpp-copy-ic" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg><svg class="tpp-check-ic" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg></button>
                    </div>
                  </div>
                  <p class="template-payment-proof-summary-note">
                    <svg class="template-payment-note-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    <span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Important: add this reference to your bank transfer (in the \"Reference\" or \"Reason\" field) so we can confirm your payment.", 'xpressui-bridge'))); ?></span>
                  </p>
                </div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_qr_code'))): ?>
                <div class="template-payment-proof-qr" data-payment-proof-qr="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>">
                  <img class="template-payment-proof-qr-img" src="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'merchant_qr_code'))); ?>" alt="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Scan to pay", 'xpressui-bridge'))); ?>" loading="lazy" />
                  <span class="template-payment-proof-qr-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Scan to pay", 'xpressui-bridge'))); ?></span>
                </div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_instructions'))): ?>
<?php $xpressui_ctx['instruction_lines'] = xpressui_bridge_template_method_split(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'prov'), 'payment_instructions'), "\n"); ?>
<?php $xpressui_ctx['table_lines'] = xpressui_bridge_template_iterable(xpressui_bridge_template_filter_select(xpressui_bridge_template_context_get($xpressui_ctx, 'instruction_lines'), "string")); ?>
                <div class="template-payment-proof-context-table" role="table" aria-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Payment context", 'xpressui-bridge'))); ?>">
<?php
$xpressui_loop_parent_ctx_10 = $xpressui_ctx;
$xpressui_loop_items_9 = xpressui_bridge_template_iterable(xpressui_bridge_template_context_get($xpressui_ctx, 'table_lines'));
foreach ($xpressui_loop_items_9 as $xpressui_loop_index_11 => $xpressui_loop_value_12):
    $xpressui_ctx = $xpressui_loop_parent_ctx_10;
    $xpressui_ctx['line'] = $xpressui_loop_value_12;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_11 + 1,
        'index0' => $xpressui_loop_index_11,
        'first'  => $xpressui_loop_index_11 === 0,
        'last'   => ($xpressui_loop_index_11 + 1) === count($xpressui_loop_items_9),
    ];
?>
<?php $xpressui_ctx['parts'] = xpressui_bridge_template_method_split(xpressui_bridge_template_context_get($xpressui_ctx, 'line'), ":", 1); ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_filter_length(xpressui_bridge_template_context_get($xpressui_ctx, 'parts')), 2), xpressui_bridge_template_method_strip(xpressui_bridge_template_getitem(xpressui_bridge_template_context_get($xpressui_ctx, 'parts'), 0))))): ?>
                      <div class="template-payment-proof-context-row" role="row">
                        <div class="template-payment-proof-context-key" role="cell"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_method_strip(xpressui_bridge_template_getitem(xpressui_bridge_template_context_get($xpressui_ctx, 'parts'), 0)))); ?></div>
                        <div class="template-payment-proof-context-value" role="cell"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_method_strip(xpressui_bridge_template_getitem(xpressui_bridge_template_context_get($xpressui_ctx, 'parts'), 1)))); ?></div>
                      </div>
<?php else: ?>
                      <div class="template-payment-proof-context-row" role="row">
                        <div class="template-payment-proof-context-key" role="cell"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Notes", 'xpressui-bridge'))); ?></div>
                        <div class="template-payment-proof-context-value" role="cell"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'line'))); ?></div>
                      </div>
<?php endif; ?>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_10; ?>
                </div>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_amount_source'), ["cart", "cart_total"]), (!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'has_multiple_providers')))))): ?>
                <div class="template-payment-proof-summary-note"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("This amount updates from the selected cart items.", 'xpressui-bridge'))); ?></div>
<?php endif; ?>
            </div>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_6; ?>
<?php endif; ?>
        <ul class="template-payment-proof-checklist">
          <li><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Please ensure the transaction date and amount are clearly visible.", 'xpressui-bridge'))); ?></li>
          <li><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Accepted formats: PDF, JPG, PNG.", 'xpressui-bridge'))); ?></li>
        </ul>
      </div>
<?php endif; ?>
    <span class="template-upload-icon">↑</span>
    <div class="template-field-label">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"))): ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Upload your proof of payment", 'xpressui-bridge'))); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'is_image_upload'))): ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Upload image", 'xpressui-bridge'))); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "camera-photo"))): ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Take photo", 'xpressui-bridge'))); ?>
<?php else: ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Upload file", 'xpressui-bridge'))); ?>
<?php endif; ?>
    </div>
    <div class="template-field-help">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"))): ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Upload the payment screenshot or receipt.", 'xpressui-bridge'))); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'placeholder'))): ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'placeholder'))); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'is_image_upload'))): ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Drag an image here or browse from your device.", 'xpressui-bridge'))); ?>
<?php else: ?>
        <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Drag a file here or browse from your device.", 'xpressui-bridge'))); ?>
<?php endif; ?>
    </div>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'upload_type_label'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'upload_accept_label')))): ?>
      <div class="template-upload-pills">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'upload_type_label'))): ?>
<span class="template-field-pill"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'upload_type_label'))); ?></span><?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'upload_accept_label'))): ?>
<span class="template-field-pill"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'upload_accept_label'))); ?></span><?php endif; ?>
      </div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"))): ?>
      <div class="template-payment-step-head template-payment-step-head--upload"><span class="template-payment-step-num">2</span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Upload your proof of payment", 'xpressui-bridge'))); ?></div>
      <label class="template-upload-dropzone" for="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>">
        <svg class="template-upload-dropzone-icon" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M12 3v13"/><path d="m7 8 5-5 5 5"/></svg>
        <span class="template-upload-dropzone-title"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Drag your receipt here, or browse your files", 'xpressui-bridge'))); ?></span>
        <span class="template-upload-dropzone-formats"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Accepted formats: PDF, JPG, PNG. Make sure the date and amount are clearly visible.", 'xpressui-bridge'))); ?></span>
      </label>
<?php endif; ?>
    <input
      id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
      class="template-input"
      type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'input_type'))); ?>"
      name="<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'multiple'))): ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>[]<?php else: ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?><?php endif; ?>"
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
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_iban')))): ?>
data-payment-iban="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_iban'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_bic')))): ?>
data-payment-bic="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_bic'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "payment-proof"), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_reference_prefix')))): ?>
data-payment-reference-prefix="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'payment_reference_prefix'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'accept'))): ?>
accept="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'accept'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'capture'))): ?>
capture="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'capture'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'multiple'))): ?>
multiple<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'min_files'))): ?>
data-min-files="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'min_files'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'max_files'))): ?>
data-max-files="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'max_files'))); ?>"<?php endif; ?>
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
