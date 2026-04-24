<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><section
  class="template-section"
  data-template-zone="section"
  data-afile-slot
  style="display:none;"
>
  <div class="template-fields">
    <div
      class="template-field"
      data-template-zone="field"
      data-field-name="xpressui_afile"
      data-field-type="file"
    >
      <div class="template-field-label-row">
        <div class="template-field-label" data-afile-label><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Additional document", 'xpressui-bridge'))); ?></div>
        <div class="template-field-meta-inline">
          <span class="template-required" aria-hidden="true" style="display:none;">*</span>
        </div>
      </div>

      <div class="xpressui-afile-ref-block" data-afile-ref-block style="display:none;">
        <a class="xpressui-afile-ref-link" data-afile-ref-link href="" target="_blank" rel="noopener noreferrer"></a>
        <p class="xpressui-afile-ref-hint" data-afile-ref-hint></p>
      </div>

      <div class="template-upload-box" data-file-drop-zone="xpressui_afile" data-file-drag-active="false">
        <span class="template-upload-icon">&#8593;</span>
        <div class="template-field-label"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Upload file", 'xpressui-bridge'))); ?></div>
        <div class="template-field-help"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Drag a file here or browse from your device.", 'xpressui-bridge'))); ?></div>
        <input
          id="xpressui_afile"
          class="template-input"
          type="file"
          name="xpressui_afile"
          data-name="xpressui_afile"
          data-type="file"
        />
      </div>

      <div
        id="xpressui_afile_selection"
        class="template-upload-selection"
        data-upload-selection-zone="xpressui_afile"
      >
        <div class="template-upload-selection-row">
          <span class="template-upload-selection-title" data-upload-selection-title="xpressui_afile"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Awaiting file", 'xpressui-bridge'))); ?></span>
        </div>
        <div class="template-field-help" data-upload-selection-message="xpressui_afile"></div>
        <div data-upload-selection-body="xpressui_afile"></div>
      </div>
    </div>
  </div>
</section>
