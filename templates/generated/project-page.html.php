<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Generated from export/project-page.html.j2. Do not edit manually.
if (!isset($__ctx) || !is_array($__ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><!doctype html>
<html lang="en">
<?php xui_jinja_include('head.php', $__ctx); ?><?php if (xui_jinja_truthy(xui_jinja_eq(xui_jinja_context_get($__ctx, 'target'), "wordpress"))): ?><style>
  /* WordPress iframe integration overrides */
  html, body { background: transparent !important; height: auto !important; min-height: 0 !important; overflow-x: hidden !important; overflow-y: visible !important; margin: 0 !important; padding: 0 !important; width: 100% !important; }
  * { box-sizing: border-box !important; }
  .page-shell { background: transparent !important; padding: 2px !important; min-height: 0 !important; height: auto !important; overflow: visible !important; align-items: flex-start !important; width: 100% !important; }
  body::before, body::after, .page-shell::before, .page-shell::after { display: none !important; }
  .form-frame { background: transparent !important; box-shadow: none !important; border: none !important; margin: 0 auto !important; padding: 0 !important; max-width: 100% !important; width: 100% !important; }
  form-ui { display: block !important; }
</style>
<?php endif; ?><body>
  <div id="xpressui-root" class="page-shell" data-template-zone="page_shell">
<?php xui_jinja_include('header.php', $__ctx); ?><?php xui_jinja_include('form-frame.php', $__ctx); ?><?php xui_jinja_include('footer.php', $__ctx); ?>  </div>
  <script id="xpressui-custom-config" type="application/json">
<?php echo xui_jinja_escape(xui_jinja_mark_safe(xui_jinja_attr(xui_jinja_context_get($__ctx, 'runtime'), 'form_config_json'))); ?>
  </script>
  <script src="./wordpress/runtime/xpressui-light-1.0.0.umd.js"></script>
  <script src="./init.js"></script>
</body>
</html>
