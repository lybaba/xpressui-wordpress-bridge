<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Generated from export/_partials/runtime-mount.j2. Do not edit manually.
if (!isset($__ctx) || !is_array($__ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><div
  id="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'runtime'), 'mount_node_id')); ?>"
  data-project-id="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'project'), 'id')); ?>"
  data-project-slug="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'project'), 'slug')); ?>"
  data-theme-preset="<?php echo xui_jinja_escape(xui_jinja_attr(xui_jinja_context_get($__ctx, 'theme'), 'preset_id')); ?>"
  data-template-zone="runtime_mount"
>
<?php xui_jinja_include('rendered-form.php', $__ctx); ?></div>

