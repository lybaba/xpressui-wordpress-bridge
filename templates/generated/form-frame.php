<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Generated from export/_partials/form-frame.j2. Do not edit manually.
if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><main class="form-frame" data-template-zone="form_frame">
<?php xpressui_bridge_template_include_template('runtime-mount.php', $xpressui_ctx); ?></main>

