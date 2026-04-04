<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Generated from export/project-page.html.j2. Do not edit manually.
if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><!doctype html>
<html lang="en">
<?php xpressui_bridge_template_include_template('head.php', $xpressui_ctx); ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress"))): ?>
<style>
  /* WordPress plugin-shell overrides */
  html, body { background: transparent !important; height: auto !important; min-height: 0 !important; overflow-x: hidden !important; overflow-y: visible !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
  * { box-sizing: border-box !important; }
  .page-shell { background: transparent !important; padding: 2px !important; min-height: 0 !important; height: auto !important; overflow: visible !important; align-items: flex-start !important; width: 100% !important; }
  body::before, body::after, .page-shell::before, .page-shell::after { display: none !important; }
  .form-frame { background: transparent !important; box-shadow: none !important; border: none !important; margin: 0 auto !important; padding: 0 !important; max-width: 100% !important; width: 100% !important; }
  form-ui { display: block !important; }
</style>
<?php endif; ?><body>
  <div id="xpressui-root" class="page-shell" data-template-zone="page_shell">
<?php xpressui_bridge_template_include_template('header.php', $xpressui_ctx); ?>
<?php xpressui_bridge_template_include_template('form-frame.php', $xpressui_ctx); ?>
<?php xpressui_bridge_template_include_template('footer.php', $xpressui_ctx); ?>
  </div>
  <script id="xpressui-custom-config" type="application/json">
<?php echo xpressui_bridge_template_stringify(xpressui_bridge_template_mark_safe(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'form_config_json'))); ?><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress"))): ?><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safe JSON string produced by template_mark_safe(), emitted inside <script type="application/json"> ?><?php endif; ?>  </script>
<?php if (xpressui_bridge_template_truthy((!xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress")))): ?>
  <script src="./init.js"></script><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'target'), "wordpress"))): ?><?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- standalone HTML page, not a WordPress template ?><?php endif; ?><?php endif; ?></body>
</html>
