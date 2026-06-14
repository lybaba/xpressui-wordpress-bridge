<?php
/**
 * Handles the logic for the IntakeFlow Pro license activation and validation.
 * Redefined for SaaS compliance: Pro status is active if connected to the Console.
 *
 * @package XPressUI-Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checks if the Pro features are active.
 * Pro is active as long as the console connection is configured.
 *
 * @return bool
 */
function xpressui_pro_is_license_active() {
	$stored = get_option( 'xpressui_console_connection', [] );
	return is_array( $stored ) && ! empty( $stored['apiToken'] );
}
