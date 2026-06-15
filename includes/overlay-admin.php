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
add_action( 'admin_footer', 'xpressui_pro_patch_console_menu_link' );

function xpressui_pro_register_console_link(): void {
	add_submenu_page(
		'edit.php?post_type=xpressui_submission',
		__( 'IntakeFlow Console', 'xpressui-bridge' ),
		__( '↗ Console', 'xpressui-bridge' ),
		'manage_options',
		'xpressui-console-redirect',
		'xpressui_pro_redirect_to_console'
	);
}

function xpressui_pro_patch_console_menu_link(): void {
	$console_url = (string) wp_json_encode( xpressui_pro_get_console_url() );
	wp_print_inline_script_tag(
		"(function () {
			document.querySelectorAll( '#adminmenu a[href*=\"xpressui-console-redirect\"]' ).forEach( function ( link ) {
				link.href = {$console_url};
				link.target = '_blank';
				link.rel = 'noopener noreferrer';
			} );
		}());"
	);
}

function xpressui_pro_get_console_url(): string {
	return 'https://app.intakeflow.dev';
}

function xpressui_pro_redirect_to_console(): void {
	$console_url = (string) wp_json_encode( xpressui_pro_get_console_url() );
	$back_url    = (string) wp_json_encode( admin_url( 'edit.php?post_type=xpressui_submission' ) );
	wp_print_inline_script_tag(
		"(function () {
			window.open( {$console_url}, '_blank', 'noopener,noreferrer' );
			window.location.href = {$back_url};
		}());"
	);
}
