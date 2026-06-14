<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}

$xpressui_pmt_field   = xpressui_bridge_template_context_get($xpressui_ctx, 'field');
$xpressui_pmt_project = xpressui_bridge_template_context_get($xpressui_ctx, 'project');
$xpressui_pmt_fn      = xpressui_bridge_template_stringify(xpressui_bridge_template_attr($xpressui_pmt_field, 'name'));
$xpressui_pmt_amount  = xpressui_bridge_template_attr($xpressui_pmt_field, 'amount');
$xpressui_pmt_amount  = ($xpressui_pmt_amount !== null && $xpressui_pmt_amount !== '') ? (int) $xpressui_pmt_amount : 0;
$xpressui_pmt_currency = xpressui_bridge_template_attr($xpressui_pmt_field, 'currency');
$xpressui_pmt_currency = ($xpressui_pmt_currency !== null && $xpressui_pmt_currency !== '') ? (string) $xpressui_pmt_currency : 'usd';

?><div class="template-field" data-template-zone="field" data-field-name="<?php echo esc_attr($xpressui_pmt_fn); ?>" data-field-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr($xpressui_pmt_field, 'type'))); ?>">
  <div class="template-field-label-row">
    <label class="template-field-label">
      <span><?php echo esc_html(xpressui_bridge_template_stringify(xpressui_bridge_template_attr($xpressui_pmt_field, 'label'))); ?></span>
      <span class="template-required" aria-hidden="true"<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_attr($xpressui_pmt_field, 'required'))))): ?> style="display:none"<?php endif; ?>>*</span>
    </label>
  </div>
  <div
    class="xpressui-payment-wrap"
    data-stripe-payment-field="<?php echo esc_attr($xpressui_pmt_fn); ?>"
    data-stripe-amount="<?php echo esc_attr((string) $xpressui_pmt_amount); ?>"
    data-stripe-currency="<?php echo esc_attr($xpressui_pmt_currency); ?>"
    data-stripe-project-slug="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr($xpressui_pmt_project, 'slug'))); ?>"
    style="display:flex;flex-direction:column;gap:10px;"
  >
    <div
      data-stripe-card-element="<?php echo esc_attr($xpressui_pmt_fn); ?>"
      style="border:1px solid #d1d5db;border-radius:6px;padding:14px 16px;background:#ffffff;min-height:44px;"
    ></div>
    <div class="template-field-help" data-stripe-error="<?php echo esc_attr($xpressui_pmt_fn); ?>" style="color:#ef4444;min-height:1em;"></div>
    <div style="font-size:12px;color:#64748b;display:flex;align-items:center;gap:6px;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
      Secured by Stripe
    </div>
  </div>
  <input
    type="hidden"
    class="template-input"
    name="<?php echo esc_attr($xpressui_pmt_fn); ?>"
    data-name="<?php echo esc_attr($xpressui_pmt_fn); ?>"
    data-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr($xpressui_pmt_field, 'label'))); ?>"
    data-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr($xpressui_pmt_field, 'type'))); ?>"
    data-section-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'section'), 'name'))); ?>"
    <?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr($xpressui_pmt_field, 'required'))): ?>data-required="true"<?php endif; ?>
    value=""
  />
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr($xpressui_pmt_field, 'desc'))): ?>
  <div class="template-field-help"><?php echo esc_html(xpressui_bridge_template_stringify(xpressui_bridge_template_attr($xpressui_pmt_field, 'desc'))); ?></div>
<?php endif; ?>
<?php xpressui_bridge_template_include_template('field-meta.php', $xpressui_ctx); ?>
</div>
