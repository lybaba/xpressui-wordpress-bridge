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
// the surrounding WordPress page. Replace :root and body with the mount ID.
$xpressui_scope = '#' . $xpressui_mount_id;
$xpressui_inline_css = str_replace(':root', $xpressui_scope, $xpressui_inline_css);
$xpressui_inline_css = str_replace(
    ['body::', 'body {', 'body,', 'body '],
    [$xpressui_scope . '::', $xpressui_scope . ' {', $xpressui_scope . ',', $xpressui_scope . ' '],
    $xpressui_inline_css
);
unset($xpressui_scope);
?>
<style>
<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- compiled inline CSS is generated from trusted templates. ?>
<?php echo $xpressui_inline_css; ?>
/* WordPress inline embed — reset standalone-page layout */
#<?php echo esc_attr($xpressui_mount_id); ?>.page-shell { min-height: 0 !important; height: auto !important; overflow: visible !important; padding: 0 !important; display: block !important; background: transparent !important; }
</style>
<div id="<?php echo esc_attr($xpressui_mount_id); ?>" class="page-shell xpressui-inline-form" data-template-zone="page_shell">
<?php xpressui_bridge_template_include_template('header.php', $xpressui_ctx); ?>
<?php xpressui_bridge_template_include_template('form-frame.php', $xpressui_ctx); ?>
<?php xpressui_bridge_template_include_template('footer.php', $xpressui_ctx); ?>
</div>
