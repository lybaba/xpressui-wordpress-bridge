<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Generated from export/_partials/field-meta.j2. Do not edit manually.
if (!isset($__ctx) || !is_array($__ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><?php $__ctx['hide_single_choice_limits'] = xui_jinja_and(xui_jinja_eq(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'min_choices'), 1), xui_jinja_eq(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'max_choices'), 1)); ?><?php $__ctx['has_meta'] = xui_jinja_or(xui_jinja_or(xui_jinja_or(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'help_text'), xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'pattern')), xui_jinja_and(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'min_choices'), (!xui_jinja_truthy(xui_jinja_context_get($__ctx, 'hide_single_choice_limits'))))), xui_jinja_and(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'max_choices'), (!xui_jinja_truthy(xui_jinja_context_get($__ctx, 'hide_single_choice_limits'))))); ?><?php $__ctx['has_messages'] = xui_jinja_or(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'error_message'), xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'success_message')); ?><?php if (xui_jinja_truthy(xui_jinja_context_get($__ctx, 'has_meta'))): ?>  <div class="template-field-meta">
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'help_text'))): ?>      <span class="template-field-pill"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'help_text')); ?></span>
<?php endif; ?><?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'pattern'))): ?>      <span class="template-field-pill"><?php echo xui_jinja_escape(__("Pattern:", 'xpressui-bridge')); ?> <?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'pattern')); ?></span>
<?php endif; ?><?php if (xui_jinja_truthy(xui_jinja_and(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'min_choices'), (!xui_jinja_truthy(xui_jinja_context_get($__ctx, 'hide_single_choice_limits')))))): ?>      <span class="template-field-pill"><?php echo xui_jinja_escape(__("Min choices:", 'xpressui-bridge')); ?> <?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'min_choices')); ?></span>
<?php endif; ?><?php if (xui_jinja_truthy(xui_jinja_and(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'max_choices'), (!xui_jinja_truthy(xui_jinja_context_get($__ctx, 'hide_single_choice_limits')))))): ?>      <span class="template-field-pill"><?php echo xui_jinja_escape(__("Max choices:", 'xpressui-bridge')); ?> <?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'max_choices')); ?></span>
<?php endif; ?>  </div>
<?php endif; ?><?php if (xui_jinja_truthy(xui_jinja_context_get($__ctx, 'has_messages'))): ?>  <div class="template-field-messages" data-field-feedback="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>">
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'error_message'))): ?>      <div id="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>_error" class="template-field-message is-error" data-field-error="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>" data-default-message="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'error_message')); ?>" role="alert" aria-live="polite" style="display:none;"></div>
<?php else: ?>      <div id="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>_error" class="template-field-message is-error" data-field-error="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>" role="alert" aria-live="polite" style="display:none;"></div>
<?php endif; ?><?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'success_message'))): ?>      <div class="template-field-message is-success" data-field-success="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'success_message')); ?></div>
<?php else: ?>      <div class="template-field-message is-success" data-field-success="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>" style="display:none;"></div>
<?php endif; ?>  </div>
<?php else: ?>  <div class="template-field-messages" data-field-feedback="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>">
    <div id="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>_error" class="template-field-message is-error" data-field-error="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>" role="alert" aria-live="polite" style="display:none;"></div>
    <div class="template-field-message is-success" data-field-success="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>" style="display:none;"></div>
  </div>
<?php endif; ?>
