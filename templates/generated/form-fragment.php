<?php
// Inline fragment template for WordPress shortcode rendering.
// Outputs CSS styles and form HTML only — no doctype/html/head/body/script tags.
// Processed from the compiled head.php + form partials; do not edit manually.
if (!isset($__ctx) || !is_array($__ctx)) {
    throw new RuntimeException('Missing template context array.');
}

// Capture head.php output and extract only the <style> blocks.
$_head_html = xui_jinja_render_template('head.php', $__ctx);
preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/i', $_head_html, $_style_blocks);
$_inline_css = implode("\n", $_style_blocks[1] ?? []);
unset($_head_html, $_style_blocks);

$_mount_id = isset($__ctx['_mount_node_id'])
    ? htmlspecialchars((string) $__ctx['_mount_node_id'], ENT_QUOTES, 'UTF-8')
    : 'xpressui-root';
?>
<style>
<?php echo $_inline_css; ?>
/* WordPress inline embed — reset standalone-page layout rules */
.xpressui-inline-form.page-shell { min-height: 0 !important; height: auto !important; overflow: visible !important; padding: 16px 0 !important; display: block !important; }
</style>
<div id="<?php echo $_mount_id; ?>" class="page-shell xpressui-inline-form" data-template-zone="page_shell">
<?php xui_jinja_include('header.php', $__ctx); ?>
<?php xui_jinja_include('form-frame.php', $__ctx); ?>
<?php xui_jinja_include('footer.php', $__ctx); ?>
</div>
