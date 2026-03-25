<?php
// Generated from export/_partials/fields/setting.j2. Do not edit manually.
if (!isset($__ctx) || !is_array($__ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><input
  id="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
  type="hidden"
  name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
  value="<?php echo xui_jinja_escape((xui_jinja_truthy((!xui_jinja_truthy(xui_jinja_test_none(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'value'))))) ? xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'value') : "")); ?>"
  data-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
  data-label="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'label')); ?>"
  data-type="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type')); ?>"
  data-section-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'section'), 'name')); ?>"
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'include_in_submit'))): ?>data-include-in-submit="true"<?php endif; ?>/>
