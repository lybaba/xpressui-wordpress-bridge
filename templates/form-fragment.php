<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Inline fragment template for WordPress shortcode rendering.
// Outputs form HTML only — CSS and scripts are enqueued by xpressui_render_shortcode().
// form-fragment.php lives in templates/ (not generated/) as it is manually maintained.
if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}

$xpressui_mount_id = isset($xpressui_ctx['_mount_node_id'])
	? htmlspecialchars((string) $xpressui_ctx['_mount_node_id'], ENT_QUOTES, 'UTF-8')
	: 'xpressui-root';
?>
<div id="<?php echo esc_attr($xpressui_mount_id); ?>" class="page-shell xpressui-inline-form" data-template-zone="page_shell">
<?php xpressui_bridge_template_include_template('header.php', $xpressui_ctx); ?>
<?php xpressui_bridge_template_include_template('form-frame.php', $xpressui_ctx); ?>
<?php xpressui_bridge_template_include_template('footer.php', $xpressui_ctx); ?>
</div>
