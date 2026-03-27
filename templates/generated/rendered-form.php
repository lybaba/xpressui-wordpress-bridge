<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Generated from export/_partials/rendered-form.j2. Do not edit manually.
if (!isset($__ctx) || !is_array($__ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><form
  class="template-runtime-shell"
  data-template-zone="rendered_form"
  method="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'runtime'), 'submit_method')); ?>"
  action="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'runtime'), 'submit_endpoint')); ?>"
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'runtime'), 'submit_enctype'))): ?>enctype="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'runtime'), 'submit_enctype')); ?>"<?php endif; ?>>
  <input type="hidden" name="projectId" value="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'project'), 'id')); ?>" />
  <input type="hidden" name="projectSlug" value="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'project'), 'slug')); ?>" />
  <header class="template-form-header" data-template-zone="form_header">
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'show_title'))): ?>    <h1 class="template-form-title"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'title')); ?></h1>
<?php endif; ?><?php if (xui_jinja_truthy(xui_jinja_and(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'show_subtitle'), xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'subtitle')))): ?>      <p class="template-form-subtitle"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'subtitle')); ?></p>
<?php endif; ?>  </header>

<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'step_status'), 'enabled'))): ?>    <section
      class="template-step-status"
      data-template-zone="step_status"
      data-form-step-progress-container="true"
    >
      <div class="template-step-status-title" data-form-step-progress>Step <?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'step_status'), 'current_index')); ?> of <?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'step_status'), 'total')); ?></div>
      <div class="template-step-progress-track" data-form-step-progress-track>
        <div
          class="template-step-progress-bar"
          data-form-step-progress-bar
          style="width: <?php echo xui_jinja_escape((xui_jinja_truthy(xui_jinja_attr(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'step_status'), 'total')) ? xui_jinja_filter_round(((xui_jinja_attr(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'step_status'), 'current_index') / xui_jinja_attr(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'step_status'), 'total')) * 100), 0, "floor") : 0)); ?>%;"
        ></div>
      </div>
      <div class="template-step-status-message" data-form-step-summary><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'step_status'), 'idle_message')); ?></div>
    </section>
<?php endif; ?><?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'has_sections'))): ?><?php $__loop_parent_ctx_2 = $__ctx; $__loop_items_1 = xui_jinja_iterable(xui_jinja_attr(xui_jinja_context_get($__ctx, 'rendered_form'), 'sections')); foreach ($__loop_items_1 as $__loop_index_3 => $__loop_value_4): $__ctx = $__loop_parent_ctx_2; $__ctx['section'] = $__loop_value_4; $__ctx['loop'] = ['index' => $__loop_index_3 + 1, 'index0' => $__loop_index_3, 'first' => $__loop_index_3 === 0, 'last' => ($__loop_index_3 + 1) === count($__loop_items_1)]; ?><?php xui_jinja_include('section.php', $__ctx); ?><?php endforeach; $__ctx = $__loop_parent_ctx_2; ?><?php else: ?>    <section class="template-section" data-template-zone="empty_form">
      <header class="template-section-header">
        <h2 class="template-section-label"><?php echo xui_jinja_escape(__("Form content", 'xpressui-bridge')); ?></h2>
        <p class="template-section-desc"><?php echo xui_jinja_escape(__("No sections are configured yet.", 'xpressui-bridge')); ?></p>
      </header>
    </section>
<?php endif; ?><?php xui_jinja_include('actions.php', $__ctx); ?></form>
