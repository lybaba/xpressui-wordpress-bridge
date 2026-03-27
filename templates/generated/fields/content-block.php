<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Generated from export/_partials/fields/content-block.j2. Do not edit manually.
if (!isset($__ctx) || !is_array($__ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><div class="template-field" data-template-zone="field" data-field-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>" data-field-type="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type')); ?>">
  <div class="template-field-label-row">
    <div class="template-field-label"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'label')); ?></div>
    <span class="template-field-help"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type')); ?></span>
  </div>
  <div class="template-html-block">
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'desc'))): ?>      <?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'desc')); ?>
<?php elseif (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'default_value'))): ?>      <?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'default_value')); ?>
<?php else: ?>      <?php echo xui_jinja_escape(__("Content block reserved for runtime rendering.", 'xpressui-bridge')); ?>
<?php endif; ?>  </div>
<?php xui_jinja_include('field-meta.php', $__ctx); ?></div>
