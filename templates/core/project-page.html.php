<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><!doctype html>
<html lang="en">
<?php xpressui_bridge_template_include_template('head.php', $xpressui_ctx); ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress"))): ?>
<style>
  /* WordPress plugin-shell overrides — scoped to #xpressui-root so they don't bleed to the surrounding page */
  #xpressui-root form-ui { display: block !important; }
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_or_value((!xpressui_bridge_template_truthy(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'project'), 'background_image_url'))), xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'theme'), 'background_style'), "none")))): ?>
  #xpressui-root.page-shell { background: transparent !important; padding: 2px !important; min-height: 0 !important; height: auto !important; overflow: visible !important; align-items: flex-start !important; width: 100% !important; }
  #xpressui-root.page-shell::before, #xpressui-root.page-shell::after { display: none !important; }
  #xpressui-root .form-frame { background: transparent !important; box-shadow: none !important; border: none !important; margin: 0 auto !important; padding: 0 !important; max-width: 100% !important; width: 100% !important; }
<?php endif; ?>
</style>
<?php endif; ?>
<body>
  <div id="xpressui-root" class="page-shell" data-template-zone="page_shell">
<?php xpressui_bridge_template_include_template('header.php', $xpressui_ctx); ?>
<?php xpressui_bridge_template_include_template('form-frame.php', $xpressui_ctx); ?>
<?php xpressui_bridge_template_include_template('footer.php', $xpressui_ctx); ?>
  </div>
  <script id="xpressui-custom-config" type="application/json">
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress"))): ?>
<?php echo wp_json_encode( json_decode( xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'form_config_json')), true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode escapes HTML special chars and produces a safe JSON string ?>
<?php else: ?>
<?php echo xpressui_bridge_template_stringify(xpressui_bridge_template_mark_safe(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'form_config_json'))); ?><?php endif; ?>
  </script>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "standalone"))): ?>
  <script src="./<?php echo esc_attr(xpressui_bridge_template_stringify(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime_js_filename'))); ?>"></script>
<?php endif; ?>
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress")))): ?>
  <script src="./init.js"></script>
<?php endif; ?>
</body>
</html>
