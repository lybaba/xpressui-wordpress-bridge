<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><div class="template-field" data-template-zone="field" data-field-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>" data-field-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'))); ?>">
  <div class="template-field-label-row">
    <label class="template-field-label" for="field-<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>">
      <span><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'label'))); ?></span>
      <span class="template-required" aria-hidden="true"<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'required'))))): ?> style="display:none"<?php endif; ?>>*</span>
    </label>
  </div>
  <input
    id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
    class="template-input"
    type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'input_type'))); ?>"
    name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
    placeholder="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'placeholder'))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'required'))): ?>
required aria-required="true"<?php endif; ?>
    data-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'name'))); ?>"
    data-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'label'))); ?>"
    data-type="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'))); ?>"
    data-section-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'section'), 'name'))); ?>"
  />
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'desc'))): ?>
    <div class="template-field-help"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'desc'))); ?></div>
<?php endif; ?>
<?php xpressui_bridge_template_include_template('field-meta.php', $xpressui_ctx); ?>
</div>
