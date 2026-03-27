<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Generated from export/_partials/fields/choice-list.j2. Do not edit manually.
if (!isset($__ctx) || !is_array($__ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><?php $__ctx['choice_layout'] = (xui_jinja_truthy(xui_jinja_in(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'layout'), ["horizontal", "vertical"])) ? xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'layout') : "auto"); ?><div class="template-field" data-template-zone="field" data-field-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>" data-field-type="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type')); ?>">
  <div class="template-field-label-row">
    <div class="template-field-label">
      <span><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'label')); ?></span>
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'required'))): ?>        <span class="template-required" aria-hidden="true">*</span>
<?php endif; ?>    </div>
  </div>
  <input
    type="hidden"
    id="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
    name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
    data-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
    data-label="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'label')); ?>"
    data-type="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type')); ?>"
    data-section-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'section'), 'name')); ?>"
    data-choices='<?php echo xui_jinja_escape(xui_jinja_mark_safe(xui_jinja_filter_tojson(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'choices')))); ?>'
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'max_choices'))): ?>data-max-num-of-choices="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'max_choices')); ?>"<?php endif; ?><?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'min_choices'))): ?>data-min-num-of-choices="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'min_choices')); ?>"<?php endif; ?>  />
  <div class="template-choice-grid template-choice-grid--<?php echo xui_jinja_escape(xui_jinja_context_get($__ctx, 'choice_layout')); ?>" id="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>_selection" data-choice-list-grid="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>" data-choice-layout="<?php echo xui_jinja_escape(xui_jinja_context_get($__ctx, 'choice_layout')); ?>">
<?php $__loop_parent_ctx_2 = $__ctx; $__loop_items_1 = xui_jinja_iterable(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'choices')); foreach ($__loop_items_1 as $__loop_index_3 => $__loop_value_4): $__ctx = $__loop_parent_ctx_2; $__ctx['choice'] = $__loop_value_4; $__ctx['loop'] = ['index' => $__loop_index_3 + 1, 'index0' => $__loop_index_3, 'first' => $__loop_index_3 === 0, 'last' => ($__loop_index_3 + 1) === count($__loop_items_1)]; ?>      <article
        class="template-choice-card"
        data-choice-option-action="toggle"
        data-choice-option-value="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'choice'), 'value')); ?>"
        data-selected="false"
        data-disabled="false"
        role="button"
        tabindex="0"
        aria-pressed="false"
      >
        <div class="template-choice-title" data-choice-option-title="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'choice'), 'value')); ?>"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'choice'), 'label')); ?></div>
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'choice'), 'desc'))): ?>          <div class="template-field-help" data-choice-option-description="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'choice'), 'value')); ?>"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'choice'), 'desc')); ?></div>
<?php endif; ?>        <div class="template-choice-footer" data-choice-option-footer="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'choice'), 'value')); ?>" hidden></div>
      </article>
<?php endforeach; $__ctx = $__loop_parent_ctx_2; ?>  </div>

<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'desc'))): ?>    <div class="template-field-help"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'desc')); ?></div>
<?php endif; ?><?php xui_jinja_include('field-meta.php', $__ctx); ?></div>
