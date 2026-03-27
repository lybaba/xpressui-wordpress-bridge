<?php
/**
 * Privacy helpers, exporter/eraser registration, and policy content.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function xpressui_register_privacy_content() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$content  = '<p>' . esc_html__( 'XPressUI Bridge stores form submissions sent by embedded XPressUI workflows.', 'xpressui-bridge' ) . '</p>';
	$content .= '<p>' . esc_html__( 'Depending on the form you publish, submissions may include names, email addresses, phone numbers, free-form answers, uploaded files, project identifiers, operator review notes, and workflow status history.', 'xpressui-bridge' ) . '</p>';
	$content .= '<p>' . esc_html__( 'Uploaded files are stored in the WordPress media library and linked to the related submission. Notification emails may be sent to the address configured for a workflow project.', 'xpressui-bridge' ) . '</p>';
	$content .= '<p>' . esc_html__( 'The plugin also keeps workflow package metadata and project settings such as notification email addresses and post-submit redirect URLs.', 'xpressui-bridge' ) . '</p>';
	wp_add_privacy_policy_content( __( 'XPressUI Bridge', 'xpressui-bridge' ), wp_kses_post( $content ) );
}

function xpressui_register_personal_data_exporter( $exporters ) {
	$exporters['xpressui-bridge'] = [
		'exporter_friendly_name' => __( 'XPressUI submissions', 'xpressui-bridge' ),
		'callback'               => 'xpressui_personal_data_exporter',
	];
	return $exporters;
}

function xpressui_register_personal_data_eraser( $erasers ) {
	$erasers['xpressui-bridge'] = [
		'eraser_friendly_name' => __( 'XPressUI submissions', 'xpressui-bridge' ),
		'callback'             => 'xpressui_personal_data_eraser',
	];
	return $erasers;
}

function xpressui_get_submission_ids_by_email( $email_address ) {
	$email_address = sanitize_email( (string) $email_address );
	if ( '' === $email_address ) {
		return [];
	}

	$submission_ids = get_posts(
		[
			'post_type'      => 'xpressui_submission',
			'post_status'    => 'private',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'ASC',
		]
	);

	$matches = [];
	foreach ( $submission_ids as $submission_id ) {
		$payload = xpressui_get_submission_payload( (int) $submission_id );
		$email   = sanitize_email( (string) ( $payload['email'] ?? '' ) );
		if ( '' !== $email && strtolower( $email ) === strtolower( $email_address ) ) {
			$matches[] = (int) $submission_id;
		}
	}

	return $matches;
}

function xpressui_personal_data_exporter( $email_address, $page = 1 ) {
	$submission_ids = xpressui_get_submission_ids_by_email( $email_address );
	$page           = max( 1, (int) $page );
	$per_page       = 25;
	$offset         = ( $page - 1 ) * $per_page;
	$current_ids    = array_slice( $submission_ids, $offset, $per_page );
	$data_to_export = [];

	foreach ( $current_ids as $submission_id ) {
		$payload      = xpressui_get_submission_payload( $submission_id );
		$stored_files = xpressui_get_uploaded_files( $submission_id );
		$items        = [
			[
				'name'  => __( 'Submission ID', 'xpressui-bridge' ),
				'value' => (string) get_post_meta( $submission_id, '_xpressui_submission_id', true ),
			],
			[
				'name'  => __( 'Project slug', 'xpressui-bridge' ),
				'value' => (string) get_post_meta( $submission_id, '_xpressui_project_slug', true ),
			],
			[
				'name'  => __( 'Submitted at', 'xpressui-bridge' ),
				'value' => get_the_date( DATE_RFC3339, $submission_id ) ?: '',
			],
		];

		foreach ( $payload as $key => $value ) {
			$items[] = [
				'name'  => sanitize_text_field( (string) $key ),
				'value' => is_scalar( $value ) ? (string) $value : wp_json_encode( $value ),
			];
		}

		foreach ( $stored_files as $index => $file ) {
			$items[] = [
				/* translators: %d: file number */
				'name'  => sprintf( __( 'Uploaded file %d', 'xpressui-bridge' ), $index + 1 ),
				'value' => esc_url_raw( (string) ( $file['url'] ?? '' ) ),
			];
		}

		$data_to_export[] = [
			'group_id'    => 'xpressui-bridge',
			'group_label' => __( 'XPressUI submissions', 'xpressui-bridge' ),
			'item_id'     => 'xpressui-submission-' . $submission_id,
			'data'        => $items,
		];
	}

	return [
		'data' => $data_to_export,
		'done' => $offset + count( $current_ids ) >= count( $submission_ids ),
	];
}

function xpressui_personal_data_eraser( $email_address, $page = 1 ) {
	$submission_ids = xpressui_get_submission_ids_by_email( $email_address );
	$page           = max( 1, (int) $page );
	$per_page       = 10;
	$offset         = ( $page - 1 ) * $per_page;
	$current_ids    = array_slice( $submission_ids, $offset, $per_page );
	$items_removed  = false;

	foreach ( $current_ids as $submission_id ) {
		wp_delete_post( $submission_id, true );
		$items_removed = true;
	}

	return [
		'items_removed'  => $items_removed,
		'items_retained' => false,
		'messages'       => [],
		'done'           => $offset + count( $current_ids ) >= count( $submission_ids ),
	];
}
