<?php
// Generated from export/_partials/fields/camera-photo.j2. Do not edit manually.
if (!isset($__ctx) || !is_array($__ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><div class="template-field" data-template-zone="field" data-field-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>" data-field-type="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type')); ?>">
  <div class="template-field-label-row">
    <div class="template-field-label"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'label')); ?></div>
    <div class="template-field-meta-inline">
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'required'))): ?>        <span class="template-required">*</span>
<?php endif; ?>    </div>
  </div>
  <div class="template-upload-box" data-file-drop-zone="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>" data-file-drag-active="false">
    <span class="template-upload-icon">&#128247;</span>
    <div class="template-field-label"><?php echo xui_jinja_escape(__("Take photo", 'xpressui-bridge')); ?></div>
    <div class="template-field-help">
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'placeholder'))): ?>        <?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'placeholder')); ?>
<?php else: ?>        <?php echo xui_jinja_escape(__("Open the device camera or choose an existing photo from your gallery.", 'xpressui-bridge')); ?>
<?php endif; ?>    </div>
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'upload_accept_label'))): ?>      <div class="template-upload-pills">
        <span class="template-field-pill"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'upload_accept_label')); ?></span>
      </div>
<?php endif; ?>    <input
      id="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
      class="template-input"
      type="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'input_type')); ?>"
      name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
      data-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
      data-label="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'label')); ?>"
      data-type="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type')); ?>"
      data-section-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'section'), 'name')); ?>"
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'accept'))): ?>accept="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'accept')); ?>"<?php endif; ?><?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'capture'))): ?>capture="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'capture')); ?>"<?php endif; ?><?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'required'))): ?>required aria-required="true"<?php endif; ?>    />
  </div>
  <div
    id="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>_selection"
    class="template-upload-selection"
    data-upload-selection-zone="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"
  >
    <div class="template-upload-selection-row">
      <span class="template-upload-selection-title" data-upload-selection-title="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"><?php echo xui_jinja_escape(__("Awaiting camera photo", 'xpressui-bridge')); ?></span>
      <span class="template-field-pill" data-upload-selection-kind="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"><?php echo xui_jinja_escape(xui_jinja_or(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'upload_type_label'), __("Camera capture", 'xpressui-bridge'))); ?></span>
    </div>
    <div class="template-field-help" data-upload-selection-message="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>">
      <?php echo xui_jinja_escape(__("The runtime can keep this server-rendered camera shell and update the selected photo in place.", 'xpressui-bridge')); ?>
    </div>
    <div data-upload-selection-body="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"></div>
  </div>
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'desc'))): ?>    <div class="template-field-help"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'desc')); ?></div>
<?php endif; ?><?php xui_jinja_include('export/_partials/field-meta.php', $__ctx); ?></div>
