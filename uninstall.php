<?php
/**
 * Uninstall routine for XPressUI WordPress Bridge.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$submission_ids = get_posts(
	[
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	]
);

foreach ( $submission_ids as $submission_id ) {
	$stored_files = get_post_meta( $submission_id, '_xpressui_uploaded_files', true );
	$stored_files = $stored_files ? json_decode( (string) $stored_files, true ) : [];
	if ( is_array( $stored_files ) ) {
		foreach ( $stored_files as $file ) {
			$attachment_id = isset( $file['attachmentId'] ) ? (int) $file['attachmentId'] : 0;
			if ( $attachment_id > 0 ) {
				wp_delete_attachment( $attachment_id, true );
			}
		}
	}

	wp_delete_post( $submission_id, true );
}

delete_option( 'xpressui_project_settings' );
delete_option( 'xpressui_workflow_manifest_registry' );
delete_option( 'xpressui_bundled_workflows_installed' );
delete_option( 'xpressui_license_settings' );
