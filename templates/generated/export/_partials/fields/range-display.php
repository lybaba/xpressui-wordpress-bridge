<?php
// Generated from export/_partials/fields/range-display.j2. Do not edit manually.
if (!isset($__ctx) || !is_array($__ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><div class="template-field" data-template-zone="field" data-field-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>" data-field-type="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type')); ?>">
  <div class="template-field-label-row">
    <label class="template-field-label" for="field-<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'label')); ?></label>
    <span class="template-field-help"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type')); ?></span>
  </div>
  <input
    id="field-<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
    class="template-input"
    type="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'input_type')); ?>"
    name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
<?php if (xui_jinja_truthy((!xui_jinja_truthy(xui_jinja_test_none(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'min_value')))))): ?>min="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'min_value')); ?>"<?php endif; ?><?php if (xui_jinja_truthy((!xui_jinja_truthy(xui_jinja_test_none(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'max_value')))))): ?>max="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'max_value')); ?>"<?php endif; ?><?php if (xui_jinja_truthy((!xui_jinja_truthy(xui_jinja_test_none(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'step_value')))))): ?>step="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'step_value')); ?>"<?php endif; ?><?php if (xui_jinja_truthy((!xui_jinja_truthy(xui_jinja_test_none(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'default_value')))))): ?>value="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'default_value')); ?>"<?php endif; ?>  />
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'desc'))): ?>    <div class="template-field-help"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'desc')); ?></div>
<?php endif; ?><?php xui_jinja_include('export/_partials/field-meta.php', $__ctx); ?></div>
