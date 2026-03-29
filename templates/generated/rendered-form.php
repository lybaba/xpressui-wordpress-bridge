<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Generated from export/_partials/rendered-form.j2. Do not edit manually.
if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><form
  class="template-runtime-shell"
  data-template-zone="rendered_form"
  method="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'submit_method'))); ?>"
  action="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'submit_endpoint'))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'submit_enctype'))): ?>
enctype="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'submit_enctype'))); ?>"<?php endif; ?>>
  <input type="hidden" name="projectId" value="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'id'))); ?>" />
  <input type="hidden" name="projectSlug" value="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'slug'))); ?>" />
  <header class="template-form-header" data-template-zone="form_header">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'show_title'))): ?>
    <h1 class="template-form-title"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'title'))); ?></h1>
<?php endif; ?><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'show_subtitle'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'subtitle')))): ?>
      <p class="template-form-subtitle"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'subtitle'))); ?></p>
<?php endif; ?>  </header>

<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'step_status'), 'enabled'))): ?>
    <section
      class="template-step-status"
      data-template-zone="step_status"
      data-form-step-progress-container="true"
    >
      <div class="template-step-status-title" data-form-step-progress>Step <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'step_status'), 'current_index'))); ?> of <?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'step_status'), 'total'))); ?></div>
      <div class="template-step-progress-track" data-form-step-progress-track>
        <div
          class="template-step-progress-bar"
          data-form-step-progress-bar
          style="width: <?php echo esc_attr(xpressui_bridge_template_stringify((xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'step_status'), 'total')) ? xpressui_bridge_template_filter_round(((xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'step_status'), 'current_index') / xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'step_status'), 'total')) * 100), 0, "floor") : 0))); ?>%;"
        ></div>
      </div>
      <div class="template-step-status-message" data-form-step-summary><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'step_status'), 'idle_message'))); ?></div>
    </section>
<?php endif; ?><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'has_sections'))): ?>
<?php
$xpressui_loop_parent_ctx_2 = $xpressui_ctx;
$xpressui_loop_items_1 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'sections'));
foreach ($xpressui_loop_items_1 as $xpressui_loop_index_3 => $xpressui_loop_value_4):
    $xpressui_ctx = $xpressui_loop_parent_ctx_2;
    $xpressui_ctx['section'] = $xpressui_loop_value_4;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_3 + 1,
        'index0' => $xpressui_loop_index_3,
        'first'  => $xpressui_loop_index_3 === 0,
        'last'   => ($xpressui_loop_index_3 + 1) === count($xpressui_loop_items_1),
    ];
?>
<?php xpressui_bridge_template_include_template('section.php', $xpressui_ctx); ?>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_2; ?>
<?php else: ?>
    <section class="template-section" data-template-zone="empty_form">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'show_section_headers'))): ?>
      <header class="template-section-header">
        <h2 class="template-section-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Form content", 'xpressui-bridge'))); ?></h2>
        <p class="template-section-desc"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("No sections are configured yet.", 'xpressui-bridge'))); ?></p>
      </header>
<?php endif; ?>
    </section>
<?php endif; ?><?php xpressui_bridge_template_include_template('actions.php', $xpressui_ctx); ?>
</form>
