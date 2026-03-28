<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Generated from export/_partials/runtime-mount.j2. Do not edit manually.
if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><div
  id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'mount_node_id'))); ?>"
  data-project-id="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'id'))); ?>"
  data-project-slug="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'slug'))); ?>"
  data-theme-preset="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'preset_id'))); ?>"
  data-template-zone="runtime_mount"
>
<?php xpressui_bridge_template_include_template('rendered-form.php', $xpressui_ctx); ?>
</div>

