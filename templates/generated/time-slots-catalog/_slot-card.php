<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><article
  class="template-choice-card template-time-slot-card"
  data-choice-option-action="toggle"
  data-choice-option-value="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'value'))); ?>"
  data-slot-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'label'))); ?>"
  data-selected="false"
  data-disabled="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'slot_disabled')) ? "true" : "false"))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'starts_at'))): ?>
data-slot-starts-at="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'starts_at'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'ends_at'))): ?>
data-slot-ends-at="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'ends_at'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_test_none(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'capacity')))))): ?>
data-slot-capacity="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'capacity'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_test_none(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'booked_count')))))): ?>
data-slot-booked-count="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'booked_count'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_test_none(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'price')))))): ?>
data-slot-price="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'price'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'currency'))): ?>
data-slot-currency="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'currency'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'pricing_mode'))): ?>
data-slot-pricing-mode="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'pricing_mode'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_key'))): ?>
data-slot-resource-key="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_key'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_label'))): ?>
data-slot-resource-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_label'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'product_sku'))): ?>
data-slot-product-sku="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'product_sku'))); ?>"<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_video_url'))): ?>
data-slot-resource-video-url="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_video_url'))); ?>"<?php endif; ?>
  role="button"
  tabindex="<?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'slot_disabled')) ? "-1" : "0"))); ?>"
  aria-pressed="false"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'slot_disabled'))): ?>
aria-disabled="true"<?php endif; ?>
>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_image_url'))): ?>
    <div class="template-choice-media template-time-slot-resource-media">
      <img src="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_image_url'))); ?>" alt="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_label'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'label')))); ?>" loading="lazy" />
    </div>
<?php endif; ?>
  <div class="template-time-slot-time">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'slot_start_time'))): ?>
<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'slot_start_time'))); ?><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'slot_end_time'))): ?> -> <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'slot_end_time'))); ?><?php endif; ?>
<?php else: ?>
<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'label'))); ?><?php endif; ?>
  </div>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'slot_date'))): ?>
    <div class="template-time-slot-date"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'slot_date'))); ?></div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_label'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'location')))): ?>
    <div class="template-time-slot-location"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_label'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'location')))); ?></div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_description'))): ?>
    <div class="template-field-help"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'resource_description'))); ?></div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'price_display'))): ?>
    <div class="template-time-slot-price"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'price_display'))); ?></div>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_test_none(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'capacity')))))): ?>
    <div class="template-time-slot-capacity<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'slot_full'))): ?> template-time-slot-capacity--full<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'slot_low'))): ?> template-time-slot-capacity--low<?php endif; ?>">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'slot_full'))): ?>
<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Full", 'xpressui-bridge'))); ?>
<?php else: ?>
<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'slot_available'))); ?> <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("spots left", 'xpressui-bridge'))); ?><?php endif; ?>
    </div>
<?php endif; ?>
  <div class="template-time-slot-select-indicator">
    <span class="template-time-slot-select-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Select", 'xpressui-bridge'))); ?></span>
    <span class="template-time-slot-selected-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Selected", 'xpressui-bridge'))); ?></span>
  </div>
  <div class="template-choice-footer" data-choice-option-footer="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'choice'), 'value'))); ?>" hidden></div>
</article>
