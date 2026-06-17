<?php
/**
 * Workflow overlay admin UI — registers the IntakeFlow Console redirection submenu.
 *
 * @package XPressUI_Bridge_Pro
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Menu registration
// ---------------------------------------------------------------------------

add_action( 'admin_menu', 'xpressui_pro_register_console_link', 20 );

function xpressui_pro_register_console_link(): void {
	add_submenu_page(
		'edit.php?post_type=xpressui_submission',
		__( 'IntakeFlow Console', 'xpressui-bridge' ),
		__( 'Console', 'xpressui-bridge' ),
		'manage_options',
		'xpressui-console',
		'xpressui_render_embedded_console'
	);
}

function xpressui_render_embedded_console(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Insufficient permissions.', 'xpressui-bridge' ) );
	}

	$conn = xpressui_get_console_connection();
	$console_url = trailingslashit( $conn['apiUrl'] ?? 'https://app.intakeflow.dev' ) . '?embed=wordpress';
	$api_token = $conn['apiToken'] ?? '';

	// SSO the embedded console: resolve a short-lived ticket for the stored API token and
	// pass it in the URL fragment (#ticket=…). The SPA exchanges it for a session on load,
	// so the operator lands authenticated instead of on the logged-out landing page. The
	// fragment is not sent to the server or as a referer, so the ticket is not logged.
	$embed_ticket = xpressui_fetch_console_embed_ticket( (string) ( $conn['apiUrl'] ?? '' ), (string) $api_token );
	if ( '' !== $embed_ticket ) {
		$console_url .= '#ticket=' . $embed_ticket;
	}

	?>
	<div class="wrap xpressui-console-wrap" style="margin: 0; height: calc(100vh - 32px); position: relative;">
		<div id="xpressui-console-loader" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; background: #f0f0f1; z-index: 10;">
			<div style="text-align: center;">
				<span class="spinner is-active" style="float: none; margin: 0 0 10px 0;"></span>
				<p style="font-size: 14px; color: #646970;"><?php esc_html_e( 'Loading IntakeFlow Console...', 'xpressui-bridge' ); ?></p>
			</div>
		</div>
		<iframe id="xpressui-console-iframe" src="<?php echo esc_url( $console_url ); ?>" style="width: 100%; height: 100%; border: none; display: none;" allow="clipboard-write"></iframe>
	</div>
	
	<style>
		/* Remove WP admin padding for a full-screen experience */
		#wpbody-content {
			padding-bottom: 0 !important;
		}
		.xpressui-console-wrap {
			max-width: 100% !important;
		}
	</style>

	<?php
}
