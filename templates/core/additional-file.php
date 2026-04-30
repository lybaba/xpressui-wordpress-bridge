<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><?php
$xpressui_loop_parent_ctx_2 = $xpressui_ctx;
$xpressui_loop_items_1 = xpressui_bridge_template_iterable(xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'rendered_form'), 'additional_file_slots'), [["id" => "xpressui_afile", "label" => ""]]));
foreach ($xpressui_loop_items_1 as $xpressui_loop_index_3 => $xpressui_loop_value_4):
    $xpressui_ctx = $xpressui_loop_parent_ctx_2;
    $xpressui_ctx['slot'] = $xpressui_loop_value_4;
    $xpressui_ctx['loop'] = [
        'index'  => $xpressui_loop_index_3 + 1,
        'index0' => $xpressui_loop_index_3,
        'first'  => $xpressui_loop_index_3 === 0,
        'last'   => ($xpressui_loop_index_3 + 1) === count($xpressui_loop_items_1),
    ];
?>
<section
  class="template-section"
  data-template-zone="section"
  data-afile-slot="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'id'))); ?>"
  style="display:none;"
>
  <div class="template-fields">
    <div
      class="template-field"
      data-template-zone="field"
      data-field-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'id'))); ?>"
      data-field-type="file"
    >
      <div class="template-field-label-row">
        <div class="template-field-label" data-afile-label><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'label'))): ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'label'))); ?><?php else: ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Additional document", 'xpressui-bridge'))); ?><?php endif; ?></div>
        <div class="template-field-meta-inline">
          <span class="template-required" aria-hidden="true" style="display:none;">*</span>
        </div>
      </div>

      <div class="xpressui-afile-ref-block" data-afile-ref-block style="display:none;">
        <a class="xpressui-afile-ref-link" data-afile-ref-link href="" target="_blank" rel="noopener noreferrer"></a>
        <p class="xpressui-afile-ref-hint" data-afile-ref-hint></p>
      </div>

      <div class="template-upload-box" data-file-drop-zone="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'id'))); ?>" data-file-drag-active="false">
        <span class="template-upload-icon">&#8593;</span>
        <div class="template-field-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Upload file", 'xpressui-bridge'))); ?></div>
        <div class="template-field-help"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Drag a file here or browse from your device.", 'xpressui-bridge'))); ?></div>
        <input
          id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'id'))); ?>"
          class="template-input"
          type="file"
          name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'id'))); ?>"
          data-name="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'id'))); ?>"
          data-label="<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'label'))): ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'label'))); ?><?php else: ?><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Additional document", 'xpressui-bridge'))); ?><?php endif; ?>"
          data-type="file"
          data-section-name="xpressui_resume_additional_documents"
        />
      </div>

      <div
        id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'id'))); ?>_selection"
        class="template-upload-selection"
        data-upload-selection-zone="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'id'))); ?>"
        style="display:none;"
      >
        <div class="template-upload-selection-row">
          <span class="template-upload-selection-title" data-upload-selection-title="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'id'))); ?>"></span>
        </div>
        <div class="template-field-help" data-upload-selection-message="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'id'))); ?>"></div>
        <div data-upload-selection-body="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'slot'), 'id'))); ?>"></div>
      </div>
    </div>
  </div>
</section>
<?php endforeach; $xpressui_ctx = $xpressui_loop_parent_ctx_2; ?>

