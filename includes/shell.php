<?php
/**
 * Front-end iframe shell for installed workflows.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function xpressui_register_shell_query_var( $vars ) {
	$vars[] = 'xpressui_shell';
	return $vars;
}

function xpressui_maybe_render_shell_page() {
	$slug = get_query_var( 'xpressui_shell', '' );
	$slug = sanitize_title( (string) $slug );

	if ( '' === $slug ) {
		return;
	}

	$payload = xpressui_get_workflow_shell_payload( $slug );
	if ( empty( $payload ) ) {
		status_header( 404 );
		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
		echo '<!doctype html><html><head><meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '"><meta name="robots" content="noindex,nofollow"><title>' . esc_html__( 'Workflow not found', 'xpressui-bridge' ) . '</title></head><body><p>' . esc_html__( 'The requested workflow could not be loaded.', 'xpressui-bridge' ) . '</p></body></html>';
		exit;
	}

	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );

	$compiled_shell_html = xpressui_render_compiled_workflow_shell_html( $slug );
	if ( '' !== $compiled_shell_html ) {
		echo $compiled_shell_html;
		exit;
	}

	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="robots" content="noindex, nofollow" />
	<title><?php echo esc_html( $payload['title'] ); ?></title>
	<style>
		html, body {
			margin: 0;
			padding: 0;
			width: 100%;
			min-height: 0;
			background: transparent;
		}
		* {
			box-sizing: border-box;
		}
		#xpressui-shell-root {
			width: 100%;
		}
	</style>
</head>
<body>
	<div id="xpressui-shell-root" data-workflow-slug="<?php echo esc_attr( $payload['slug'] ); ?>"></div>
	<script id="xpressui-shell-payload" type="application/json"><?php echo wp_json_encode( $payload ); ?></script>
	<script>window.XPRESSUI_I18N = <?php echo wp_json_encode( xpressui_get_shell_translations() ); ?>;</script>
	<script src="<?php echo esc_url( XPRESSUI_BRIDGE_URL . 'assets/iframe-shell.js' ); ?>?ver=<?php echo esc_attr( XPRESSUI_BRIDGE_VERSION ); ?>"></script>
</body>
</html>
	<?php
	exit;
}
