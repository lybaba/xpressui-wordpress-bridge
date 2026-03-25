<?php
// Generated from export/_partials/fields/choice-select.j2. Do not edit manually.
if (!isset($__ctx) || !is_array($__ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><div class="template-field" data-template-zone="field" data-field-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>" data-field-type="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type')); ?>">
  <div class="template-field-label-row">
    <label class="template-field-label" for="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>">
      <span><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'label')); ?></span>
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'required'))): ?>        <span class="template-required" aria-hidden="true">*</span>
<?php endif; ?>    </label>
  </div>

  <select
    id="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
    class="template-input"
    name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
    data-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
    data-label="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'label')); ?>"
    data-type="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type')); ?>"
    data-section-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'section'), 'name')); ?>"
<?php if (xui_jinja_truthy(xui_jinja_eq(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type'), "select-multiple"))): ?>multiple<?php endif; ?><?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'required'))): ?>required aria-required="true"<?php endif; ?>  >
<?php if (xui_jinja_truthy(xui_jinja_eq(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type'), "select-one"))): ?>      <option value=""></option>
<?php endif; ?><?php $__loop_parent_ctx_2 = $__ctx; $__loop_items_1 = xui_jinja_iterable(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'choices')); foreach ($__loop_items_1 as $__loop_index_3 => $__loop_value_4): $__ctx = $__loop_parent_ctx_2; $__ctx['choice'] = $__loop_value_4; $__ctx['loop'] = ['index' => $__loop_index_3 + 1, 'index0' => $__loop_index_3, 'first' => $__loop_index_3 === 0, 'last' => ($__loop_index_3 + 1) === count($__loop_items_1)]; ?>      <option value="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'choice'), 'value')); ?>"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'choice'), 'label')); ?></option>
<?php endforeach; $__ctx = $__loop_parent_ctx_2; ?>  </select>

<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'desc'))): ?>    <div class="template-field-help"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'desc')); ?></div>
<?php endif; ?><?php xui_jinja_include('export/_partials/field-meta.php', $__ctx); ?></div>
