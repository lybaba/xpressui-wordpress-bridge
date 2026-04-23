<?php
/**
 * Uninstall routine for XPressUI WordPress Bridge.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$xpressui_delete_project_settings = defined( 'XPRESSUI_BRIDGE_DELETE_PROJECT_SETTINGS_ON_UNINSTALL' )
	&& XPRESSUI_BRIDGE_DELETE_PROJECT_SETTINGS_ON_UNINSTALL;
$xpressui_delete_submissions = defined( 'XPRESSUI_BRIDGE_DELETE_SUBMISSIONS_ON_UNINSTALL' )
	&& XPRESSUI_BRIDGE_DELETE_SUBMISSIONS_ON_UNINSTALL;

if ( $xpressui_delete_submissions ) {
	$xpressui_submission_ids = get_posts(
		[
			'post_type'      => 'xpressui_submission',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		]
	);

	foreach ( $xpressui_submission_ids as $xpressui_submission_id ) {
		wp_delete_post( $xpressui_submission_id, true );
	}
}

if ( $xpressui_delete_project_settings ) {
	delete_option( 'xpressui_project_settings' );
}
delete_option( 'xpressui_workflow_manifest_registry' );
delete_option( 'xpressui_bundled_workflows_installed' );
delete_option( 'xpressui_bundled_workflows_version' );
delete_option( 'xpressui_user_deleted_workflows' );
if ( $xpressui_delete_submissions ) {
	delete_option( 'xpressui_project_config_registry' );
}
