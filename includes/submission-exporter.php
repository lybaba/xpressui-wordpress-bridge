<?php
/**
 * Submissions CSV data exporter.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle CSV Export action trigger.
 */
function xpressui_handle_submission_export_action() {
	if ( ! isset( $_GET['xpressui_export_csv'] ) || '1' !== $_GET['xpressui_export_csv'] ) {
		return;
	}

	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Forbidden', 'xpressui-bridge' ), 403 );
	}

	check_admin_referer( 'xpressui_export_csv_action', 'nonce' );

	// Fetch all private submissions
	$args = [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	];

	// Filter by project slug if requested
	if ( isset( $_GET['xpressui_project'] ) && '' !== $_GET['xpressui_project'] ) {
		$args['meta_query'] = [
			[
				'key'   => '_xpressui_project_slug',
				'value' => sanitize_title( $_GET['xpressui_project'] ),
			]
		];
	}

	$posts = get_posts( $args );

	// Headers for download
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=intakeflow-submissions-' . gmdate( 'Y-m-d' ) . '.csv' );

	$output = fopen( 'php://output', 'w' );
	if ( ! $output ) {
		wp_die( esc_html__( 'Failed to write CSV stream.', 'xpressui-bridge' ) );
	}

	// Write UTF-8 BOM for proper Excel encoding
	fwrite( $output, "\xEF\xBB\xBF" );

	// Column Headers
	fputcsv( $output, [
		__( 'Submission ID', 'xpressui-bridge' ),
		__( 'Date Submitted', 'xpressui-bridge' ),
		__( 'Workflow Slug', 'xpressui-bridge' ),
		__( 'Workflow Name', 'xpressui-bridge' ),
		__( 'Client Email', 'xpressui-bridge' ),
		__( 'Status', 'xpressui-bridge' ),
		__( 'Form Responses (Summary)', 'xpressui-bridge' ),
		__( 'Full JSON Payload', 'xpressui-bridge' )
	] );

	foreach ( $posts as $post ) {
		$post_id      = $post->ID;
		$sub_id       = (string) get_post_meta( $post_id, '_xpressui_submission_id', true );
		$date         = get_the_date( 'Y-m-d H:i:s', $post_id );
		$project_slug = (string) get_post_meta( $post_id, '_xpressui_project_slug', true );
		$status       = (string) get_post_meta( $post_id, '_xpressui_submission_status', true );
		$email        = (string) get_post_meta( $post_id, '_xpressui_submitter_email', true );
		$payload_json = (string) get_post_meta( $post_id, '_xpressui_payload_json', true );

		if ( empty( $email ) ) {
			$email = xpressui_extract_email_from_payload( $payload_json );
		}

		// Nice workflow title
		$meta = xpressui_get_workflow_manifest_meta( $project_slug );
		$title = ! empty( $meta['projectName'] ) ? $meta['projectName'] : $project_slug;

		// Summary formatting
		$summary = '';
		$payload = json_decode( $payload_json, true );
		if ( is_array( $payload ) ) {
			$summary_parts = [];
			foreach ( $payload as $k => $v ) {
				// Skip internal fields and arrays
				if ( strpos( $k, '_' ) === 0 || is_array( $v ) ) {
					continue;
				}
				$summary_parts[] = esc_html( $k ) . ': ' . esc_html( $v );
			}
			$summary = implode( ' | ', $summary_parts );
		}

		fputcsv( $output, [
			$sub_id,
			$date,
			$project_slug,
			$title,
			$email,
			$status,
			$summary,
			$payload_json
		] );
	}

	fclose( $output );
	exit;
}
add_action( 'admin_init', 'xpressui_handle_submission_export_action' );
