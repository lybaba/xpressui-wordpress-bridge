<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><main class="form-frame" data-template-zone="form_frame">
  <div class="template-submit-overlay" data-submit-overlay role="status" aria-live="polite" aria-label="Submitting form">
    <div class="template-submit-overlay-spinner"></div>
    <span class="template-submit-overlay-label">Submitting…</span>
  </div>
<?php xpressui_bridge_template_include_template('runtime-mount.php', $xpressui_ctx); ?>
</main>

