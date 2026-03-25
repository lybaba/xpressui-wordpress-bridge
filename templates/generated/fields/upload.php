<?php
// Generated from export/_partials/fields/upload.j2. Do not edit manually.
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
    <span class="template-upload-icon">↑</span>
    <div class="template-field-label">
<?php if (xui_jinja_truthy(xui_jinja_eq(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type'), "upload-image"))): ?>        <?php echo xui_jinja_escape(__("Upload image", 'xpressui-bridge')); ?>
<?php elseif (xui_jinja_truthy(xui_jinja_eq(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type'), "camera-photo"))): ?>        <?php echo xui_jinja_escape(__("Take photo", 'xpressui-bridge')); ?>
<?php else: ?>        <?php echo xui_jinja_escape(__("Upload file", 'xpressui-bridge')); ?>
<?php endif; ?>    </div>
    <div class="template-field-help">
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'placeholder'))): ?>        <?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'placeholder')); ?>
<?php elseif (xui_jinja_truthy(xui_jinja_eq(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type'), "upload-image"))): ?>        <?php echo xui_jinja_escape(__("Drag an image here or browse from your device.", 'xpressui-bridge')); ?>
<?php else: ?>        <?php echo xui_jinja_escape(__("Drag a file here or browse from your device.", 'xpressui-bridge')); ?>
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
      <span class="template-upload-selection-title" data-upload-selection-title="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>">
<?php if (xui_jinja_truthy(xui_jinja_eq(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type'), "upload-image"))): ?>          <?php echo xui_jinja_escape(__("Awaiting image", 'xpressui-bridge')); ?>
<?php elseif (xui_jinja_truthy(xui_jinja_eq(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type'), "camera-photo"))): ?>          <?php echo xui_jinja_escape(__("Awaiting camera photo", 'xpressui-bridge')); ?>
<?php else: ?>          <?php echo xui_jinja_escape(__("Awaiting file", 'xpressui-bridge')); ?>
<?php endif; ?>      </span>
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'upload_type_label'))): ?>        <span class="template-field-pill" data-upload-selection-kind="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'upload_type_label')); ?></span>
<?php endif; ?>    </div>
    <div class="template-field-help" data-upload-selection-message="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>">
      <?php echo xui_jinja_escape(__("The runtime can keep this server-rendered selection shell and update file state in place.", 'xpressui-bridge')); ?>
    </div>
    <div data-upload-selection-body="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>"></div>
  </div>
<?php if (xui_jinja_truthy(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'desc'))): ?>    <div class="template-field-help"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'desc')); ?></div>
<?php endif; ?><?php xui_jinja_include('field-meta.php', $__ctx); ?></div>
