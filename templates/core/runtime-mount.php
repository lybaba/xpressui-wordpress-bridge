<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
  <div class="xpressui-resume-loader" data-resume-loader style="display:none;" role="status" aria-live="polite" aria-label="<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Loading your correction form", 'xpressui-bridge'))); ?>">
    <div class="xpressui-resume-loader-card">
      <div class="xpressui-resume-loader-spinner" aria-hidden="true"></div>
      <div class="xpressui-resume-loader-title"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("Preparing your correction form", 'xpressui-bridge'))); ?></div>
      <p class="xpressui-resume-loader-text"><?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_wp_text("We are loading the requested fields and documents.", 'xpressui-bridge'))); ?></p>
    </div>
  </div>
<?php xpressui_bridge_template_include_template('rendered-form.php', $xpressui_ctx); ?>
</div>
