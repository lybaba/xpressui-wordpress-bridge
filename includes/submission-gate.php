<?php
/**
 * Pro license soft enforcement.
 *
 * IMPORTANT: submissions are NEVER blocked. Blocking a form submission would
 * make the site silently lose leads/data when a license is missing, expired, or
 * temporarily unverifiable (API downtime, site-URL mismatch). Instead we let the
 * submission through and nudge the site administrator to activate their license
 * through a persistent admin notice.
 *
 * @package XPressUI_Bridge_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const XPRESSUI_PRO_LICENSE_NAG_OPTION = 'xpressui_pro_license_nag';

/**
 * Soft gate hooked to the free plugin's `xpressui_submission_gate` filter.
 *
 * Records that a Pro-tier workflow was submitted without an active license so the
 * admin notice can surface, then ALWAYS lets the submission proceed by returning
 * the incoming value unchanged.
 *
 * @param mixed  $gate         Current gate value (WP_Error to block, otherwise null).
 * @param string $project_slug Workflow slug being submitted.
 * @param mixed  $payload      Submission payload (unused).
 * @param mixed  $request      REST request (unused).
 * @return mixed Always returns $gate unchanged (never a new WP_Error).
 */
function xpressui_pro_submission_license_gate( $gate, $project_slug, $payload = null, $request = null ) {
	// Never override a block another add-on may have set, but never add our own.
	if ( is_wp_error( $gate ) ) {
		return $gate;
	}

	$slug = sanitize_title( (string) $project_slug );
	if ( '' === $slug || ! function_exists( 'xpressui_get_workflow_manifest_meta' ) ) {
		return $gate;
	}

	$meta = xpressui_get_workflow_manifest_meta( $slug );
	$tier = is_array( $meta ) ? (string) ( $meta['runtimeTier'] ?? '' ) : '';

	// Only Pro-tier workflows are relevant to licensing.
	if ( 'pro' !== $tier ) {
		return $gate;
	}

	if ( function_exists( 'xpressui_pro_is_license_active' ) && xpressui_pro_is_license_active() ) {
		return $gate;
	}

	// Unlicensed Pro submission: remember it so the admin gets nudged, but DO NOT block.
	update_option(
		XPRESSUI_PRO_LICENSE_NAG_OPTION,
		array(
			'slug' => $slug,
			'time' => time(),
		),
		false
	);

	return $gate;
}
add_filter( 'xpressui_submission_gate', 'xpressui_pro_submission_license_gate', 10, 4 );

/**
 * Returns true when the site shows evidence of Pro usage (a Pro-tier workflow is
 * installed, or one was recently submitted without a license).
 */
function xpressui_pro_has_pro_usage() {
	if ( get_option( XPRESSUI_PRO_LICENSE_NAG_OPTION, false ) ) {
		return true;
	}

	if ( ! function_exists( 'xpressui_get_workflow_manifest_registry' ) ) {
		return false;
	}

	$registry = xpressui_get_workflow_manifest_registry();
	if ( ! is_array( $registry ) ) {
		return false;
	}
	foreach ( $registry as $entry ) {
		if ( is_array( $entry ) && 'pro' === (string) ( $entry['runtimeTier'] ?? '' ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Admin notice nudging the operator to configure their SaaS Console connection.
 * Shown only when the connection is inactive AND there is evidence of Pro usage.
 * Submissions keep working regardless — this is purely a reminder.
 */
function xpressui_pro_license_soft_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Don't nag on the connection screen itself.
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && false !== strpos( (string) $screen->id, 'xpressui-bridge' ) ) {
		return;
	}

	if ( function_exists( 'xpressui_pro_is_license_active' ) && xpressui_pro_is_license_active() ) {
		return;
	}

	if ( ! xpressui_pro_has_pro_usage() ) {
		return;
	}

	$connect_url = admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-bridge' );

	printf(
		'<div class="notice notice-warning is-dismissible"><p><strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a></p></div>',
		esc_html__( 'XPressUI Bridge PRO:', 'xpressui-bridge-pro' ),
		esc_html__( 'To use Pro workflows, please configure your Console Connection (API Token). Pro forms keep accepting submissions, but connecting ensures you stay supported and receive updates.', 'xpressui-bridge-pro' ),
		esc_url( $connect_url ),
		esc_html__( 'Configure Console Connection', 'xpressui-bridge-pro' )
	);
}
add_action( 'admin_notices', 'xpressui_pro_license_soft_notice' );
