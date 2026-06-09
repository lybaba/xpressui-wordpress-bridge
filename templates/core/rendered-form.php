<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><?php $xpressui_ctx['is_step_timeline'] = xpressui_bridge_template_and_value(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'step_layout'), "timeline"), xpressui_bridge_template_attr(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'step_status'), 'enabled')); ?>
<form
  class="template-runtime-shell"
  data-template-zone="rendered_form"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), 'product_items')))): ?>
data-product-form-gated="true" hidden<?php endif; ?>
  data-field-columns="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'field_columns'))); ?>"
  data-label-position="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'label_position'))); ?>"
  data-step-layout="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'step_layout'), "default"))); ?>"
  method="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'submit_method'))); ?>"
  action="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'submit_endpoint'))); ?>"
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'submit_enctype'))): ?>
enctype="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'submit_enctype'))); ?>"<?php endif; ?>
>
  <input type="hidden" name="projectId" value="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'id'))); ?>" />
  <input type="hidden" name="projectSlug" value="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'slug'))); ?>" />
  <input type="hidden" name="submissionId" value="" data-submission-id>
  <input type="hidden" name="xpressui_resume_entry_id" value="" data-resume-entry-id>
  <input type="text" name="xpressui_confirm_email" tabindex="-1" autocomplete="off" aria-hidden="true" style="opacity:0;position:absolute;top:0;left:0;height:0;width:0;z-index:-1;pointer-events:none;">
  <input type="hidden" name="xpressui_resume_token" data-resume-token disabled>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), 'product_items')))): ?>
  <input type="hidden" name="xpressuiProductCart" value="[]" />
  <input type="hidden" name="xpressuiProductTotal" value="0" />
  <input type="hidden" name="xpressuiProductCurrency" value="" />
  <input type="hidden" name="xpressuiProductCount" value="0" />
<?php endif; ?>
  <div class="xpressui-resume-banner" data-resume-banner style="display:none;"><span data-resume-banner-note></span></div>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'product_catalog'), 'product_items')))): ?>
  <button type="button" class="template-product-selection-back" data-product-selection-back hidden style="display:none"></button>
<?php endif; ?>
  <header class="template-form-header" data-template-zone="form_header">
    <h1 class="template-form-title"<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'show_title'))))): ?> style="display:none"<?php endif; ?>><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'title'))); ?></h1>
    <p class="template-form-subtitle"<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_or_value((!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'show_subtitle'))), (!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'subtitle')))))): ?> style="display:none"<?php endif; ?>><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'subtitle'))); ?></p>
  </header>

<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'is_step_timeline'))): ?>
  <div class="template-step-layout" data-step-layout-grid>
    <div class="template-step-timeline">
<?php
$xpressui_loop_parent_ctx_2 = $xpressui_ctx;
$xpressui_loop_items_1 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'step_descriptors'));
foreach ($xpressui_loop_items_1 as $xpressui_loop_index_3 => $xpressui_loop_value_4):
    $xpressui_ctx = $xpressui_loop_parent_ctx_2;
    $xpressui_ctx['step'] = $xpressui_loop_value_4;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_3 + 1,
        'index0' => $xpressui_loop_index_3,
        'first'  => $xpressui_loop_index_3 === 0,
        'last'   => ($xpressui_loop_index_3 + 1) === count($xpressui_loop_items_1),
    ];
?>
      <button
        type="button"
        class="template-step-timeline-item<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'loop'), 'first'))): ?> is-active<?php endif; ?>"
        data-step-nav-item="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'step'), 'index'))); ?>"
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'loop'), 'first'))))): ?>
disabled<?php endif; ?>
        aria-current="<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'loop'), 'first'))): ?>step<?php else: ?>false<?php endif; ?>"
      >
        <span class="template-step-timeline-index"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_add(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'step'), 'index'), 1))); ?></span>
        <span class="template-step-timeline-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'step'), 'label'))); ?></span>
      </button>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_2; ?>
    </div>
    <div class="template-step-layout-main">
<?php endif; ?>
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
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'has_sections'))): ?>
<?php
$xpressui_loop_parent_ctx_6 = $xpressui_ctx;
$xpressui_loop_items_5 = xpressui_bridge_template_iterable(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'sections'));
foreach ($xpressui_loop_items_5 as $xpressui_loop_index_7 => $xpressui_loop_value_8):
    $xpressui_ctx = $xpressui_loop_parent_ctx_6;
    $xpressui_ctx['section'] = $xpressui_loop_value_8;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_7 + 1,
        'index0' => $xpressui_loop_index_7,
        'first'  => $xpressui_loop_index_7 === 0,
        'last'   => ($xpressui_loop_index_7 + 1) === count($xpressui_loop_items_5),
    ];
?>
<?php $xpressui_ctx['is_initial_form_section'] = xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'loop'), 'first'); ?>
<?php xpressui_bridge_template_include_template('section.php', $xpressui_ctx); ?>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_6; ?>
<?php else: ?>
    <section class="template-section" data-template-zone="empty_form">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'show_section_headers'))): ?>
      <header class="template-section-header">
        <h2 class="template-section-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Form content", 'xpressui-bridge'))); ?></h2>
        <p class="template-section-desc"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("No sections are configured yet.", 'xpressui-bridge'))); ?></p>
      </header>
<?php endif; ?>
    </section>
<?php endif; ?>
<?php xpressui_bridge_template_include_template('actions.php', $xpressui_ctx); ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_context_get($xpressui_ctx, 'is_step_timeline'))): ?>
    </div>
  </div>
<?php endif; ?>
</form>
