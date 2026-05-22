<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! isset( $xpressui_ctx ) || ! is_array( $xpressui_ctx ) ) {
    throw new RuntimeException( 'Missing template context array.' );
}

$_theme    = $xpressui_ctx['theme']    ?? [];
$_colors   = $_theme['colors']         ?? [];
$_radius   = $_theme['radius']         ?? [];
$_project  = $xpressui_ctx['project'] ?? [];
$_bg_url   = $_project['background_image_url'] ?? '';
$_bg_style = $_theme['background_style']        ?? 'none';
$_font     = ! empty( $_theme['font_family'] ) ? $_theme['font_family'] : 'Inter, system-ui, sans-serif';

$_data_bg_style = ( $_bg_url && 'none' !== $_bg_style ) ? $_bg_style : 'none';

$_vars = implode( '; ', [
    '--template-font-family:'     . esc_attr( $_font ),
    '--template-page-background:' . esc_attr( $_colors['page_background'] ?? '' ),
    '--template-surface:'         . esc_attr( $_colors['surface']         ?? '' ),
    '--template-text:'            . esc_attr( $_colors['text']            ?? '' ),
    '--template-muted-text:color-mix(in srgb, var(--template-text) 65%, transparent)',
    '--template-primary:'         . esc_attr( $_colors['primary']         ?? '' ),
    '--template-border:'          . esc_attr( $_colors['border']          ?? '' ),
    '--template-card-radius:'     . esc_attr( (string) ( $_radius['card']   ?? 0 ) ) . 'px',
    '--template-input-radius:'    . esc_attr( (string) ( $_radius['input']  ?? 0 ) ) . 'px',
    '--template-button-radius:'   . esc_attr( (string) ( $_radius['button'] ?? 0 ) ) . 'px',
] );
?><!doctype html>
<html lang="en" style="<?php echo esc_attr( $_vars ); ?>; --template-background-image:<?php echo $_bg_url ? 'url(' . esc_url( $_bg_url ) . ')' : 'none'; ?>">
<?php xpressui_bridge_template_include_template( 'head.php', $xpressui_ctx ); ?>
<body data-bg-style="<?php echo esc_attr( $_data_bg_style ); ?>">
  <div id="xpressui-root" class="page-shell" data-template-zone="page_shell">
<?php xpressui_bridge_template_include_template( 'header.php', $xpressui_ctx ); ?>
<?php xpressui_bridge_template_include_template( 'form-frame.php', $xpressui_ctx ); ?>
<?php xpressui_bridge_template_include_template( 'footer.php', $xpressui_ctx ); ?>
  </div>
  <script id="xpressui-custom-config" type="application/json">
<?php echo wp_json_encode( json_decode( xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'form_config_json')), true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode escapes HTML special chars and produces a safe JSON string ?>  </script>
</body>
</html>
