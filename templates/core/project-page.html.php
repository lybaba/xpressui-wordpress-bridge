<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! isset( $xpressui_ctx ) || ! is_array( $xpressui_ctx ) ) {
    throw new RuntimeException( 'Missing template context array.' );
}

$xpressui_bridge_theme    = $xpressui_ctx['theme']    ?? [];
$xpressui_bridge_colors   = $xpressui_bridge_theme['colors']         ?? [];
$xpressui_bridge_radius   = $xpressui_bridge_theme['radius']         ?? [];
$xpressui_bridge_project  = $xpressui_ctx['project'] ?? [];
$xpressui_bridge_bg_url   = $xpressui_bridge_project['background_image_url'] ?? '';
$xpressui_bridge_bg_style = $xpressui_bridge_theme['background_style']        ?? 'none';
$xpressui_bridge_font     = ! empty( $xpressui_bridge_theme['font_family'] ) ? $xpressui_bridge_theme['font_family'] : 'Inter, system-ui, sans-serif';

$xpressui_bridge_data_bg_style = ( $xpressui_bridge_bg_url && 'none' !== $xpressui_bridge_bg_style ) ? $xpressui_bridge_bg_style : 'none';

$xpressui_bridge_vars = implode( '; ', [
    '--template-font-family:'     . esc_attr( $xpressui_bridge_font ),
    '--template-page-background:' . esc_attr( $xpressui_bridge_colors['page_background'] ?? '' ),
    '--template-surface:'         . esc_attr( $xpressui_bridge_colors['surface']         ?? '' ),
    '--template-text:'            . esc_attr( $xpressui_bridge_colors['text']            ?? '' ),
    '--template-muted-text:color-mix(in srgb, var(--template-text) 65%, transparent)',
    '--template-primary:'         . esc_attr( $xpressui_bridge_colors['primary']         ?? '' ),
    '--template-border:'          . esc_attr( $xpressui_bridge_colors['border']          ?? '' ),
    '--template-card-radius:'     . esc_attr( (string) ( $xpressui_bridge_radius['card']   ?? 0 ) ) . 'px',
    '--template-input-radius:'    . esc_attr( (string) ( $xpressui_bridge_radius['input']  ?? 0 ) ) . 'px',
    '--template-button-radius:'   . esc_attr( (string) ( $xpressui_bridge_radius['button'] ?? 0 ) ) . 'px',
] );
?><!doctype html>
<html lang="en" style="<?php echo esc_attr( $xpressui_bridge_vars ); ?>; --template-background-image:<?php echo $xpressui_bridge_bg_url ? 'url(' . esc_url( $xpressui_bridge_bg_url ) . ')' : 'none'; ?>">
<?php xpressui_bridge_template_include_template( 'head.php', $xpressui_ctx ); ?>
<body data-bg-style="<?php echo esc_attr( $xpressui_bridge_data_bg_style ); ?>">
  <div id="xpressui-root" class="page-shell" data-template-zone="page_shell">
<?php xpressui_bridge_template_include_template( 'header.php', $xpressui_ctx ); ?>
<?php xpressui_bridge_template_include_template( 'form-frame.php', $xpressui_ctx ); ?>
<?php xpressui_bridge_template_include_template( 'footer.php', $xpressui_ctx ); ?>
  </div>
  <script id="xpressui-custom-config" type="application/json">
<?php echo wp_json_encode( json_decode( xpressui_bridge_template_stringify(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'runtime'), 'form_config_json')), true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode escapes HTML special chars and produces a safe JSON string ?>  </script>
</body>
</html>
