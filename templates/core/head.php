<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! isset( $xpressui_ctx ) || ! is_array( $xpressui_ctx ) ) {
    throw new RuntimeException( 'Missing template context array.' );
}

$xpressui_bridge_catalog_seo = $xpressui_ctx['catalog_seo'] ?? null;
$xpressui_bridge_project     = $xpressui_ctx['project']     ?? [];
?><head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
<?php if ( ! empty( $xpressui_bridge_catalog_seo ) ) : ?>
  <meta name="robots" content="index, follow" />
  <title><?php echo esc_html( $xpressui_bridge_catalog_seo['title'] ?? '' ); ?></title>
  <?php if ( ! empty( $xpressui_bridge_catalog_seo['description'] ) ) : ?><meta name="description" content="<?php echo esc_attr( $xpressui_bridge_catalog_seo['description'] ); ?>" /><?php endif; ?>
  <link rel="canonical" href="<?php echo esc_url( $xpressui_bridge_catalog_seo['canonical_url'] ?? '' ); ?>" />
  <meta property="og:type" content="website" />
  <meta property="og:title" content="<?php echo esc_attr( $xpressui_bridge_catalog_seo['title'] ?? '' ); ?>" />
  <?php if ( ! empty( $xpressui_bridge_catalog_seo['description'] ) ) : ?><meta property="og:description" content="<?php echo esc_attr( $xpressui_bridge_catalog_seo['description'] ); ?>" /><?php endif; ?>
  <meta property="og:url" content="<?php echo esc_url( $xpressui_bridge_catalog_seo['canonical_url'] ?? '' ); ?>" />
  <?php if ( ! empty( $xpressui_bridge_catalog_seo['og_image'] ) ) : ?><meta property="og:image" content="<?php echo esc_url( $xpressui_bridge_catalog_seo['og_image'] ); ?>" /><?php endif; ?>
  <meta name="twitter:card" content="<?php echo esc_attr( ! empty( $xpressui_bridge_catalog_seo['og_image'] ) ? 'summary_large_image' : 'summary' ); ?>" />
  <meta name="twitter:title" content="<?php echo esc_attr( $xpressui_bridge_catalog_seo['title'] ?? '' ); ?>" />
  <?php if ( ! empty( $xpressui_bridge_catalog_seo['description'] ) ) : ?><meta name="twitter:description" content="<?php echo esc_attr( $xpressui_bridge_catalog_seo['description'] ); ?>" /><?php endif; ?>
  <?php if ( ! empty( $xpressui_bridge_catalog_seo['og_image'] ) ) : ?><meta name="twitter:image" content="<?php echo esc_url( $xpressui_bridge_catalog_seo['og_image'] ); ?>" /><?php endif; ?>
<?php else : ?>
  <meta name="robots" content="noindex, nofollow" />
  <title><?php echo esc_html( $xpressui_bridge_project['name'] ?? '' ); ?></title>
<?php endif; ?>
  <?php
  wp_enqueue_style( 'xpressui-shell', XPRESSUI_BRIDGE_URL . 'assets/shell/xpressui-shell.css', [], XPRESSUI_BRIDGE_VERSION );
  wp_print_styles( 'xpressui-shell' );
  ?>
<?php if ( ! empty( $xpressui_ctx['workspace_public'] ) ) : ?>
<?php xpressui_bridge_template_include_template( 'workspace-public/_mairie_shell_styles.php', $xpressui_ctx ); ?>
<?php xpressui_bridge_template_include_template( 'workspace-public/_mairie_catalog_styles.php', $xpressui_ctx ); ?>
<?php endif; ?>
</head>
