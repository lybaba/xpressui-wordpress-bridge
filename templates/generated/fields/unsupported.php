<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Generated from export/_partials/fields/unsupported.j2. Do not edit manually.
if (!isset($__ctx) || !is_array($__ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><div class="template-field" data-template-zone="field" data-field-name="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'name')); ?>" data-field-type="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type')); ?>">
  <div class="template-field-label-row">
    <div class="template-field-label"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'label')); ?></div>
    <span class="template-field-help"><?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'field'), 'type')); ?></span>
  </div>
  <div class="template-quiz-wrap">
    <div class="template-field-help">
      <?php echo xui_jinja_escape(__("This field is rendered by the XPressUI runtime. The server-rendered HTML shell keeps its slot reserved here.", 'xpressui-bridge')); ?>
    </div>
  </div>
</div>
