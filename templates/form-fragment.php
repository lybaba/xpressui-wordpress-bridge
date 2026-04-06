<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Inline fragment template for WordPress shortcode rendering.
// Outputs CSS styles and form HTML only — no doctype/html/head/body/script tags.
// Processed from the compiled head.php + form partials; do not edit manually.
if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}

$xpressui_mount_id = isset($xpressui_ctx['_mount_node_id'])
	? htmlspecialchars((string) $xpressui_ctx['_mount_node_id'], ENT_QUOTES, 'UTF-8')
	: 'xpressui-root';

// Capture head.php output and extract only the <style> blocks.
$xpressui_head_html = xpressui_bridge_template_render_template('head.php', $xpressui_ctx);
preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/i', $xpressui_head_html, $xpressui_style_blocks);
$xpressui_inline_css = implode("\n", $xpressui_style_blocks[1] ?? []);
unset($xpressui_head_html, $xpressui_style_blocks);

// Scope global selectors to the form container so they don't bleed into
// the surrounding WordPress page. Replace :root, #xpressui-root and body with the mount ID.
$xpressui_scope = '#' . $xpressui_mount_id;
$xpressui_inline_css = preg_replace('/(?<![#\w-]):root\b/', $xpressui_scope, $xpressui_inline_css);
$xpressui_inline_css = preg_replace('/#xpressui-root(?![-\w])/', $xpressui_scope, $xpressui_inline_css);
$xpressui_inline_css = str_replace(
    ['body::', 'body {', 'body,', 'body '],
    [$xpressui_scope . '::', $xpressui_scope . ' {', $xpressui_scope . ',', $xpressui_scope . ' '],
    $xpressui_inline_css
);
unset($xpressui_scope);
$xpressui_has_bg = !empty($xpressui_ctx['project']['background_image_url'])
    && isset($xpressui_ctx['theme']['background_style'])
    && $xpressui_ctx['theme']['background_style'] !== 'none';
?>
<style>
<?php echo $xpressui_inline_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $xpressui_inline_css is CSS extracted from server-side compiled templates (head.php), scoped and processed within this request. No user input reaches this variable. ?>
/* WordPress inline embed — reset standalone-page layout */
<?php if ($xpressui_has_bg): ?>
#<?php echo esc_attr($xpressui_mount_id); ?>.page-shell { min-height: 0 !important; height: auto !important; overflow: hidden !important; padding: 48px max(5%, 24px) !important; display: grid !important; place-items: center !important; background: transparent !important; position: relative !important; border-radius: 24px !important; }
<?php else: ?>
#<?php echo esc_attr($xpressui_mount_id); ?>.page-shell { min-height: 0 !important; height: auto !important; overflow: visible !important; padding: 0 !important; display: block !important; background: transparent !important; }
<?php endif; ?>
/* WordPress embed tuning — keep the runtime readable inside typical WP page widths. */
#<?php echo esc_attr($xpressui_mount_id); ?> .form-frame { padding: 20px; box-shadow: <?php echo $xpressui_has_bg ? '0 28px 80px -38px rgba(0,0,0,0.42)' : '0 16px 44px rgba(15, 23, 42, 0.1)'; ?>; <?php echo $xpressui_has_bg ? 'max-width: 680px !important; width: 100% !important;' : ''; ?> }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-runtime-shell { gap: 16px; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-form-header { gap: 2px; padding-top: 0; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-form-title { font-size: clamp(22px, 2.8vw, 30px); line-height: 1.08; letter-spacing: -0.03em; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-section { gap: 18px; padding: 20px 18px; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-fields { gap: 12px; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-field { gap: 6px; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-field-label { font-size: 13px; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-field-help { font-size: 12px; line-height: 1.4; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-input,
#<?php echo esc_attr($xpressui_mount_id); ?> .template-textarea { font-size: 14px; line-height: 1.4; padding: 11px 13px; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-textarea { min-height: 124px; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-choice-card { padding: 9px 12px; gap: 3px; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-choice-title { font-size: 12px; line-height: 1.2; font-weight: 600; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-choice-footer { font-size: 11px; }
#<?php echo esc_attr($xpressui_mount_id); ?> select.template-input[multiple] { min-height: 120px; padding-top: 7px; padding-bottom: 7px; }
#<?php echo esc_attr($xpressui_mount_id); ?> select.template-input option { font-size: 14px; line-height: 1.35; }
/* Scoped select overrides — ensures overlay theme colors win over the WP theme */
#<?php echo esc_attr($xpressui_mount_id); ?> .template-runtime-shell select { background-color: color-mix(in srgb, var(--template-surface) 96%, white) !important; color: var(--template-text) !important; border-color: var(--template-border) !important; accent-color: var(--template-primary) !important; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-runtime-shell select:focus { border-color: var(--template-primary) !important; box-shadow: 0 0 0 3px color-mix(in srgb, var(--template-primary) 15%, transparent) !important; outline: none !important; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-step-progress-track { height: 7px; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-step-actions { margin-top: 14px; gap: 10px; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-step-actions [data-step-action] { font-size: 13px; padding: 11px 16px; min-width: 112px; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-submit-row { padding-top: 14px; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-submit-btn { cursor: pointer; font-size: 13px; line-height: 1.1; padding: 11px 16px; min-width: 112px; }
/* Step title — prominent, clearly separated from fields */
#<?php echo esc_attr($xpressui_mount_id); ?> .template-section-header { padding-bottom: 14px; border-bottom: 2px solid color-mix(in srgb, var(--template-primary, #2563eb) 18%, transparent); margin-bottom: 2px; }
#<?php echo esc_attr($xpressui_mount_id); ?> .template-section-label { font-size: 19px; font-weight: 800; letter-spacing: -0.02em; color: var(--template-text, #0f172a); }
/* Step transition animation */
@keyframes xpressui-step-in {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
#<?php echo esc_attr($xpressui_mount_id); ?> .template-section[data-template-zone="section"] { animation: xpressui-step-in 220ms cubic-bezier(0.22, 1, 0.36, 1) both; }
@media (max-width: 720px) {
  #<?php echo esc_attr($xpressui_mount_id); ?> .form-frame { padding: 16px; }
  #<?php echo esc_attr($xpressui_mount_id); ?> .template-form-title { font-size: clamp(20px, 7vw, 26px); }
  #<?php echo esc_attr($xpressui_mount_id); ?> .template-section { padding: 16px 14px; }
  #<?php echo esc_attr($xpressui_mount_id); ?> .template-input,
  #<?php echo esc_attr($xpressui_mount_id); ?> .template-textarea { font-size: 13px; }
}
</style>
<div id="<?php echo esc_attr($xpressui_mount_id); ?>" class="page-shell xpressui-inline-form" data-template-zone="page_shell">
<?php xpressui_bridge_template_include_template('header.php', $xpressui_ctx); ?>
<?php xpressui_bridge_template_include_template('form-frame.php', $xpressui_ctx); ?>
<?php xpressui_bridge_template_include_template('footer.php', $xpressui_ctx); ?>
</div>
