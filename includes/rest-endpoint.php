<?php
/**
 * REST API endpoint for receiving XPressUI form submissions.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function xpressui_register_rest_routes() {
	register_rest_route( 'xpressui/v1', '/submit', [
		'methods'             => 'POST',
		'callback'            => 'xpressui_handle_submission',
		'permission_callback' => 'xpressui_submission_permissions_check',
	] );
	register_rest_route( 'xpressui/v1', '/resume', [
		'methods'             => 'GET',
		'callback'            => 'xpressui_handle_resume_request',
		'permission_callback' => '__return_true',
	] );
}

function xpressui_submission_permissions_check( WP_REST_Request $request ) {
	$project_slug = sanitize_title( (string) $request->get_param( 'projectSlug' ) );
	if ( $project_slug === '' || ! xpressui_is_installed_workflow( $project_slug ) ) {
		return new WP_Error(
			'xpressui_invalid_project',
			__( 'Unknown workflow project.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
	}

	$rate_limit = xpressui_check_submission_rate_limit( $project_slug );
	if ( is_wp_error( $rate_limit ) ) {
		return $rate_limit;
	}

	return true;
}

function xpressui_get_request_origin_candidates() {
	$candidates = [];
	$headers    = [ 'HTTP_ORIGIN', 'HTTP_REFERER' ];

	foreach ( $headers as $header ) {
		$raw = isset( $_SERVER[ $header ] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER[ $header ] ) ) : '';
		$raw = trim( $raw );
		if ( $raw === '' ) {
			continue;
		}

		$parts  = wp_parse_url( $raw );
		$scheme = isset( $parts['scheme'] ) ? strtolower( sanitize_key( (string) $parts['scheme'] ) ) : '';
		$host   = isset( $parts['host'] ) ? strtolower( sanitize_text_field( (string) $parts['host'] ) ) : '';
		$port   = isset( $parts['port'] ) ? (int) $parts['port'] : 0;

		if ( $scheme === '' || $host === '' ) {
			continue;
		}

		$candidates[] = [
			'header' => $header,
			'raw'    => $raw,
			'scheme' => $scheme,
			'host'   => $host,
			'port'   => $port,
		];
	}

	return $candidates;
}

function xpressui_validate_submission_origin() {
	$candidates = xpressui_get_request_origin_candidates();
	if ( empty( $candidates ) ) {
		return true;
	}

	$site_url    = home_url( '/' );
	$site_parts  = wp_parse_url( $site_url );
	$site_scheme = isset( $site_parts['scheme'] ) ? strtolower( sanitize_key( (string) $site_parts['scheme'] ) ) : '';
	$site_host   = isset( $site_parts['host'] ) ? strtolower( sanitize_text_field( (string) $site_parts['host'] ) ) : '';
	$site_port   = isset( $site_parts['port'] ) ? (int) $site_parts['port'] : 0;

	if ( $site_scheme === '' || $site_host === '' ) {
		return true;
	}

	foreach ( $candidates as $candidate ) {
		$same_host = hash_equals( $site_host, $candidate['host'] );
		$same_port = $site_port === $candidate['port'];

		if ( $same_host && $same_port ) {
			return true;
		}
	}

	return new WP_Error(
		'xpressui_invalid_origin',
		__( 'Submission origin is not allowed for this workflow.', 'xpressui-bridge' ),
		[ 'status' => 403 ]
	);
}

function xpressui_validate_request_identifier_value( $value, $field_name, $required = false ) {
	$value      = is_string( $value ) ? trim( $value ) : '';
	$field_name = sanitize_key( (string) $field_name );

	if ( $value === '' ) {
		if ( $required ) {
			return new WP_Error(
				'xpressui_missing_identifier',
				__( 'A required submission identifier is missing.', 'xpressui-bridge' ),
				[ 'status' => 400 ]
			);
		}
		return '';
	}

	if ( strlen( $value ) > 191 ) {
		return new WP_Error(
			'xpressui_identifier_too_long',
			__( 'One of the submission identifiers is too long.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
	}

	if ( ! preg_match( '/^[A-Za-z0-9._:+\-]+$/', $value ) ) {
		return new WP_Error(
			'xpressui_invalid_identifier',
			__( 'One of the submission identifiers is invalid.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
	}

	if ( 'projectslug' === $field_name && ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value ) ) {
		return new WP_Error(
			'xpressui_invalid_project',
			__( 'Unknown workflow project.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
	}

	return $value;
}

function xpressui_validate_submission_identifiers( $project_slug, $project_id, $project_config_version, $submission_id ) {
	$project_slug = xpressui_validate_request_identifier_value( $project_slug, 'projectslug', true );
	if ( is_wp_error( $project_slug ) ) {
		return $project_slug;
	}

	$project_id = xpressui_validate_request_identifier_value( $project_id, 'projectid', false );
	if ( is_wp_error( $project_id ) ) {
		return $project_id;
	}

	$project_config_version = xpressui_validate_request_identifier_value( $project_config_version, 'projectconfigversion', false );
	if ( is_wp_error( $project_config_version ) ) {
		return $project_config_version;
	}

	$submission_id = xpressui_validate_request_identifier_value( $submission_id, 'submissionid', false );
	if ( is_wp_error( $submission_id ) ) {
		return $submission_id;
	}

	if ( ! xpressui_is_installed_workflow( $project_slug ) ) {
		return new WP_Error(
			'xpressui_invalid_project',
			__( 'Unknown workflow project.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
	}

	$manifest_meta = xpressui_get_workflow_manifest_meta( $project_slug );
	if ( empty( $manifest_meta ) ) {
		$manifest_meta = xpressui_load_workflow_manifest( $project_slug );
	}

	$manifest_slug = sanitize_title( (string) ( $manifest_meta['projectSlug'] ?? '' ) );
	if ( $manifest_slug !== '' && ! hash_equals( $manifest_slug, $project_slug ) ) {
		return new WP_Error(
			'xpressui_manifest_mismatch',
			__( 'Workflow metadata does not match the installed project.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
	}

	$manifest_project_id = xpressui_sanitize_request_identifier( $manifest_meta['projectId'] ?? '' );
	if ( $manifest_project_id !== '' && $project_id !== '' && ! hash_equals( $manifest_project_id, $project_id ) ) {
		return new WP_Error(
			'xpressui_invalid_project_id',
			__( 'Submission project metadata does not match the installed workflow.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
	}

	return [
		'projectSlug'          => $project_slug,
		'projectId'            => $project_id,
		'projectConfigVersion' => $project_config_version,
		'submissionId'         => $submission_id,
	];
}

function xpressui_handle_resume_request( WP_REST_Request $request ) {
	$token   = sanitize_text_field( (string) $request->get_param( 'token' ) );
	$post_id = xpressui_get_resume_post_id_by_token( $token );

	if ( $post_id <= 0 ) {
		return new WP_Error(
			'xpressui_invalid_token',
			__( 'Invalid or expired resume token.', 'xpressui-bridge' ),
			[ 'status' => 404 ]
		);
	}

	$status = (string) get_post_meta( $post_id, '_xpressui_submission_status', true );
	if ( $status !== 'pending_info' ) {
		return new WP_Error(
			'xpressui_token_not_applicable',
			__( 'This submission is not awaiting corrections.', 'xpressui-bridge' ),
			[ 'status' => 410 ]
		);
	}

	$payload         = xpressui_get_submission_payload( $post_id );
	$flagged_fields  = xpressui_get_flagged_fields( $post_id );
	$project_slug    = (string) get_post_meta( $post_id, '_xpressui_project_slug', true );
	$submission_id   = (string) get_post_meta( $post_id, '_xpressui_submission_id', true );
	$note            = (string) get_post_meta( $post_id, '_xpressui_review_note', true );
	$reference_files = xpressui_resolve_field_reference_files( $post_id );

	return new WP_REST_Response( [
		'success'        => true,
		'entryId'        => $post_id,
		'projectSlug'    => $project_slug,
		'submissionId'   => $submission_id,
		'payload'        => is_array( $payload ) ? $payload : [],
		'flaggedFields'  => $flagged_fields,
		'note'           => $note,
		'referenceFiles' => $reference_files,
	], 200 );
}

function xpressui_handle_submission( WP_REST_Request $request ) {
	$timing_marks = [
		'start' => microtime( true ),
	];
	$mark_timing = static function ( $name ) use ( &$timing_marks ) {
		$timing_marks[ (string) $name ] = microtime( true );
	};
	$timing_diff_ms = static function ( $from, $to ) use ( &$timing_marks ) {
		$from_key = (string) $from;
		$to_key   = (string) $to;
		if ( ! isset( $timing_marks[ $from_key ], $timing_marks[ $to_key ] ) ) {
			return null;
		}
		return (int) round( ( $timing_marks[ $to_key ] - $timing_marks[ $from_key ] ) * 1000 );
	};

	$payload              = $request->get_param( 'payload' );
	$project_id           = xpressui_sanitize_request_identifier( $request->get_param( 'projectId' ) );
	$project_config_version = xpressui_sanitize_request_identifier( $request->get_param( 'projectConfigVersion' ) );
	$submission_id        = xpressui_sanitize_request_identifier( $request->get_param( 'submissionId' ) );
	$project_slug         = sanitize_title( (string) $request->get_param( 'projectSlug' ) );
	$project_config       = xpressui_normalize_config_snapshot( $request->get_param( 'projectConfigSnapshotJson' ) );
	$submission_channel   = xpressui_detect_submission_channel( $request );

	// Fall back to raw body if payload param is empty.
	if ( empty( $payload ) ) {
		$payload = $request->get_json_params();
		if ( ! is_array( $payload ) || empty( $payload ) ) {
			$payload = $request->get_params();
		}
		if ( is_array( $payload ) ) {
			unset( $payload['payload'], $payload['projectConfigSnapshotJson'] );
		}
	}

	// Resume resubmission path: bypass normal insert when a resume token is present.
	$resume_token = is_array( $payload ) ? sanitize_text_field( (string) ( $payload['xpressui_resume_token'] ?? '' ) ) : '';
	if ( $resume_token !== '' ) {
		return xpressui_handle_resubmission( $request, $payload, $resume_token, $project_slug );
	}

	$resume_entry_id = is_array( $payload ) ? absint( $payload['xpressui_resume_entry_id'] ?? 0 ) : 0;
	if ( $resume_entry_id > 0 ) {
		return xpressui_handle_resubmission_by_post_id( $request, $payload, $resume_entry_id, $project_slug );
	}

	$resume_post_id = xpressui_find_resubmission_post_id( $project_slug, $submission_id );
	if ( $resume_post_id > 0 ) {
		return xpressui_handle_resubmission_by_post_id( $request, $payload, $resume_post_id, $project_slug );
	}

	$validation = xpressui_validate_submission_request( $request, $project_slug, $submission_id, $payload );
	if ( is_wp_error( $validation ) ) {
		return $validation;
	}
	$mark_timing( 'validated' );

	$post_id = wp_insert_post( [
		'post_type'   => 'xpressui_submission',
		'post_status' => 'private',
		'post_title'  => xpressui_build_submission_title( $project_slug, $submission_id, $payload ),
	] );

	if ( is_wp_error( $post_id ) ) {
		return new WP_REST_Response( [
			'success' => false,
			'message' => __( 'Submission failed. Please review the form and try again.', 'xpressui-bridge' ),
		], 500 );
	}
	$mark_timing( 'post_created' );

	update_post_meta( $post_id, '_xpressui_project_id', $project_id );
	update_post_meta( $post_id, '_xpressui_project_slug', $project_slug );
	update_post_meta( $post_id, '_xpressui_project_config_version', $project_config_version );
	update_post_meta( $post_id, '_xpressui_submission_id', $submission_id ?: '' );

	xpressui_store_config_snapshot( $project_id, $project_slug, $project_config_version, $project_config );
	xpressui_set_submission_status( $post_id, 'new', __( 'Submission received', 'xpressui-bridge' ) );

	$stored_files        = xpressui_store_uploaded_files( $post_id, $request );
	$mark_timing( 'files_stored' );
	$payload_with_files  = xpressui_attach_file_references( $payload, $stored_files );
	$payload_with_files  = xpressui_store_signature_attachments( $post_id, $payload_with_files );

	update_post_meta( $post_id, '_xpressui_payload_json',
		is_string( $payload_with_files )
			? $payload_with_files
			: wp_json_encode( $payload_with_files )
	);
	$mark_timing( 'payload_stored' );

	// Allow extensions to react immediately after a brand-new submission is persisted.
	do_action( 'xpressui_submission_first_created', $post_id, $project_slug, $payload_with_files );
	$mark_timing( 'first_created_action' );

	// Fire notification after payload is stored.
	xpressui_maybe_send_notification( $post_id, $project_slug, $payload_with_files );
	$mark_timing( 'notification_sent' );

	// Send confirmation email to the submitter on first submit.
	xpressui_maybe_send_submit_confirmation( $post_id, $project_slug, $payload_with_files );
	$mark_timing( 'submit_confirmation_sent' );

	// Send outbound webhook (best-effort — failure does not affect submission response).
	xpressui_maybe_send_webhook( $post_id, $project_slug, $payload_with_files );
	$mark_timing( 'webhook_sent' );

	$timing_summary = [
		'totalMs'             => $timing_diff_ms( 'start', 'webhook_sent' ),
		'validateMs'          => $timing_diff_ms( 'start', 'validated' ),
		'postCreateMs'        => $timing_diff_ms( 'validated', 'post_created' ),
		'storeFilesMs'        => $timing_diff_ms( 'post_created', 'files_stored' ),
		'storePayloadMs'      => $timing_diff_ms( 'files_stored', 'payload_stored' ),
		'firstCreatedHookMs'  => $timing_diff_ms( 'payload_stored', 'first_created_action' ),
		'notificationMs'      => $timing_diff_ms( 'first_created_action', 'notification_sent' ),
		'submitConfirmMs'     => $timing_diff_ms( 'notification_sent', 'submit_confirmation_sent' ),
		'webhookMs'           => $timing_diff_ms( 'submit_confirmation_sent', 'webhook_sent' ),
	];
	update_post_meta( $post_id, '_xpressui_submit_timing', wp_json_encode( $timing_summary ) );
	xpressui_record_submission_event(
		$post_id,
		'submit.completed',
		'bridge',
		[
			'total_ms'         => (int) ( $timing_summary['totalMs'] ?? 0 ),
			'store_files_ms'   => (int) ( $timing_summary['storeFilesMs'] ?? 0 ),
			'notification_ms'  => (int) ( $timing_summary['notificationMs'] ?? 0 ),
			'webhook_ms'       => (int) ( $timing_summary['webhookMs'] ?? 0 ),
		],
		[
			'project_slug'       => $project_slug,
			'submission_channel' => $submission_channel,
			'submission_id'      => $submission_id,
		]
	);

	// Read per-project redirect URL.
	$redirect_url = xpressui_get_project_setting( $project_slug, 'redirectUrl' );

	$response = [
		'success'      => true,
		'message'      => __( 'Submission received', 'xpressui-bridge' ),
		'entryId'      => $post_id,
		'submissionId' => $submission_id,
		'files'        => $stored_files,
		'timing'       => $timing_summary,
	];
	if ( $redirect_url !== '' ) {
		$response['redirectUrl'] = $redirect_url;
	}

	return new WP_REST_Response( $response, 200 );
}

function xpressui_handle_resubmission( WP_REST_Request $request, $payload, $token, $project_slug ) {
	$post_id = xpressui_get_resume_post_id_by_token( $token );
	return xpressui_handle_resubmission_by_post_id( $request, $payload, $post_id, $project_slug );
}

function xpressui_find_resubmission_post_id( $project_slug, $submission_id ) {
	$project_slug  = sanitize_title( (string) $project_slug );
	$submission_id = xpressui_sanitize_request_identifier( $submission_id );
	if ( '' === $project_slug || '' === $submission_id ) {
		return 0;
	}

	$existing_ids = get_posts( [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'fields'         => 'ids',
		'posts_per_page' => 1,
		'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			[
				'key'   => '_xpressui_project_slug',
				'value' => $project_slug,
			],
			[
				'key'   => '_xpressui_submission_id',
				'value' => $submission_id,
			],
			[
				'key'   => '_xpressui_submission_status',
				'value' => 'pending_info',
			],
		],
	] );

	return ! empty( $existing_ids ) ? (int) $existing_ids[0] : 0;
}

function xpressui_handle_resubmission_by_post_id( WP_REST_Request $request, $payload, $post_id, $project_slug ) {
	if ( $post_id <= 0 ) {
		return new WP_Error(
			'xpressui_invalid_token',
			__( 'Invalid or expired resume link. Please contact us to request a new one.', 'xpressui-bridge' ),
			[ 'status' => 404 ]
		);
	}

	$stored_slug = (string) get_post_meta( $post_id, '_xpressui_project_slug', true );
	if ( $stored_slug !== $project_slug ) {
		return new WP_Error(
			'xpressui_token_mismatch',
			__( 'Resume token does not match the submitted project.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
	}

	$status = (string) get_post_meta( $post_id, '_xpressui_submission_status', true );
	if ( $status !== 'pending_info' ) {
		return new WP_Error(
			'xpressui_token_not_applicable',
			__( 'This submission is no longer awaiting corrections.', 'xpressui-bridge' ),
			[ 'status' => 410 ]
		);
	}

	$flagged_fields   = xpressui_get_flagged_fields( $post_id );
	$existing_payload = xpressui_get_submission_payload( $post_id );
	$merged           = is_array( $existing_payload ) ? $existing_payload : [];
	$file_params      = xpressui_get_request_file_params( $request );

	$file_validation = xpressui_validate_uploaded_files( $file_params );
	if ( is_wp_error( $file_validation ) ) {
		xpressui_record_submission_event(
			$post_id,
			'upload.failed',
			'bridge',
			[],
			[
				'project_slug'  => $project_slug,
				'error_code'    => (string) $file_validation->get_error_code(),
				'error_message' => (string) $file_validation->get_error_message(),
				'phase'         => 'resubmission',
			]
		);
		return $file_validation;
	}

	$skip_keys = [
		'xpressui_resume_token', 'xpressui_confirm_email',
		'projectId', 'projectSlug', 'projectConfigVersion',
		'submissionId', 'projectConfigSnapshotJson', 'rest_route',
	];

	if ( empty( $flagged_fields ) ) {
		// No specific fields flagged — update all non-internal keys.
		foreach ( (array) $payload as $key => $value ) {
			if ( in_array( $key, $skip_keys, true ) ) {
				continue;
			}
			$merged[ $key ] = $value;
		}
	} else {
		// Update only explicitly flagged fields.
		foreach ( $flagged_fields as $field_name ) {
			if ( array_key_exists( $field_name, (array) $payload ) ) {
				$merged[ $field_name ] = $payload[ $field_name ];
			}
		}
	}

	// Handle new file uploads for flagged fields only.
	$stored_files = xpressui_store_uploaded_files( $post_id, $request );
	$merged       = xpressui_attach_file_references( $merged, $stored_files );
	$merged       = xpressui_store_signature_attachments( $post_id, $merged );
	xpressui_record_submission_event(
		$post_id,
		'resubmit.triggered',
		'bridge',
		[
			'uploaded_file_count' => is_array( $stored_files ) ? count( $stored_files ) : 0,
		],
		[
			'project_slug' => $project_slug,
		]
	);

	// Invalidate token before status change (avoids any race on double-submit).
	xpressui_invalidate_resume_token( $post_id );

	update_post_meta( $post_id, '_xpressui_payload_json', wp_json_encode( $merged ) );
	update_post_meta( $post_id, '_xpressui_resubmitted_at', current_time( 'mysql' ) );

	xpressui_set_submission_status( $post_id, 'in-review', __( 'Resubmitted by submitter', 'xpressui-bridge' ) );
	xpressui_maybe_send_resubmitted_notification( $post_id, $project_slug, $merged );

	return new WP_REST_Response( [
		'success' => true,
		'message' => __( 'Your corrections have been received. Thank you.', 'xpressui-bridge' ),
		'entryId' => $post_id,
	], 200 );
}

function xpressui_validate_submission_request( WP_REST_Request $request, $project_slug, $submission_id, $payload ) {
	$origin_validation = xpressui_validate_submission_origin();
	if ( is_wp_error( $origin_validation ) ) {
		return $origin_validation;
	}

	$identifier_validation = xpressui_validate_submission_identifiers(
		$project_slug,
		xpressui_sanitize_request_identifier( $request->get_param( 'projectId' ) ),
		xpressui_sanitize_request_identifier( $request->get_param( 'projectConfigVersion' ) ),
		$submission_id
	);
	if ( is_wp_error( $identifier_validation ) ) {
		return $identifier_validation;
	}

	// Honeypot: the field is injected by the init script and must remain empty.
	// Bots that auto-fill all inputs will populate it, triggering rejection.
	$honeypot = $request->get_param( 'xpressui_confirm_email' );
	if ( $honeypot !== null && $honeypot !== '' ) {
		return new WP_Error(
			'xpressui_spam',
			__( 'Submission rejected.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
	}

	if ( ! is_array( $payload ) ) {
		return new WP_Error(
			'xpressui_invalid_payload',
			__( 'Submission payload must be a JSON object or form payload.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
	}

	if ( empty( $payload ) ) {
		return new WP_Error(
			'xpressui_empty_payload',
			__( 'Submission payload is empty.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
	}

	if ( $submission_id !== '' ) {
		$existing_ids = get_posts( [
			'post_type'      => 'xpressui_submission',
			'post_status'    => 'private',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				[
					'key'   => '_xpressui_project_slug',
					'value' => $project_slug,
				],
				[
					'key'   => '_xpressui_submission_id',
					'value' => $submission_id,
				],
			],
		] );
		if ( ! empty( $existing_ids ) ) {
			return new WP_Error(
				'xpressui_duplicate_submission',
				__( 'This submission has already been received.', 'xpressui-bridge' ),
				[ 'status' => 409 ]
			);
		}
	}

	$file_validation = xpressui_validate_uploaded_files( xpressui_get_request_file_params( $request ) );
	if ( is_wp_error( $file_validation ) ) {
		return $file_validation;
	}

	return true;
}

function xpressui_get_request_ip() {
	$keys = [ 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ];
	foreach ( $keys as $key ) {
		$raw = isset( $_SERVER[ $key ] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER[ $key ] ) ) : '';
		if ( $raw === '' ) {
			continue;
		}
		$parts = array_map( 'trim', explode( ',', $raw ) );
		foreach ( $parts as $part ) {
			if ( filter_var( $part, FILTER_VALIDATE_IP ) ) {
				return $part;
			}
		}
	}
	return '';
}

/**
 * Best-effort channel detection for instrumentation.
 *
 * @param WP_REST_Request $request Request instance.
 * @return string
 */
function xpressui_detect_submission_channel( WP_REST_Request $request ) {
	$hint = strtolower( sanitize_text_field( (string) $request->get_header( 'X-XPressUI-Channel' ) ) );
	if ( in_array( $hint, [ 'mobile', 'web', 'desktop-relay' ], true ) ) {
		return $hint;
	}

	$user_agent = strtolower( sanitize_text_field( (string) $request->get_header( 'User-Agent' ) ) );
	if ( $user_agent !== '' && preg_match( '/android|iphone|ipad|mobile/', $user_agent ) ) {
		return 'mobile';
	}

	return 'web';
}

function xpressui_check_submission_rate_limit( $project_slug ) {
	$ip_address = xpressui_get_request_ip();
	if ( $ip_address === '' ) {
		return true;
	}

	$transient_key = 'xpressui_rate_' . md5( $project_slug . '|' . $ip_address );
	$attempts      = (int) get_transient( $transient_key );
	$max_attempts  = 10;

	if ( $attempts >= $max_attempts ) {
		return new WP_Error(
			'xpressui_rate_limited',
			__( 'Too many submissions from this address. Please try again in a few minutes.', 'xpressui-bridge' ),
			[ 'status' => 429 ]
		);
	}

	set_transient( $transient_key, $attempts + 1, 10 * MINUTE_IN_SECONDS );
	return true;
}

// ---------------------------------------------------------------------------
// File handling
// ---------------------------------------------------------------------------

function xpressui_get_request_file_params( WP_REST_Request $request ) {
	$request_files    = $request->get_file_params();

	$superglobal_files = is_array( $_FILES ) ? $_FILES : []; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- REST route, authentication via permission_callback

	if ( ! is_array( $request_files ) || empty( $request_files ) ) {
		return $superglobal_files;
	}
	return array_replace_recursive( $superglobal_files, $request_files );
}

function xpressui_normalize_uploaded_files( array $file_params ) {
	$normalized = [];
	foreach ( $file_params as $field_name => $file_info ) {
		$field_name = preg_replace( '/[^a-zA-Z0-9_]/', '', (string) $field_name );
		if ( '' === $field_name || ! is_array( $file_info ) || ! isset( $file_info['name'] ) ) {
			continue;
		}
		if ( is_array( $file_info['name'] ) ) {
			$count = count( $file_info['name'] );
			for ( $i = 0; $i < $count; $i++ ) {
				$normalized[] = [
					'field'    => $field_name,
					'name'     => sanitize_file_name( (string) ( $file_info['name'][ $i ] ?? '' ) ),
					'type'     => sanitize_mime_type( (string) ( $file_info['type'][ $i ] ?? '' ) ),
					'tmp_name' => $file_info['tmp_name'][ $i ] ?? '',
					'error'    => $file_info['error'][ $i ] ?? UPLOAD_ERR_NO_FILE,
					'size'     => $file_info['size'][ $i ] ?? 0,
				];
			}
			continue;
		}
		$normalized[] = [
			'field'    => $field_name,
			'name'     => sanitize_file_name( (string) $file_info['name'] ),
			'type'     => sanitize_mime_type( (string) ( $file_info['type'] ?? '' ) ),
			'tmp_name' => $file_info['tmp_name'] ?? '',
			'error'    => $file_info['error'] ?? UPLOAD_ERR_NO_FILE,
			'size'     => $file_info['size'] ?? 0,
		];
	}
	return $normalized;
}

function xpressui_request_has_uploaded_file( array $file_params, $field_name ) {
	$field_name = (string) $field_name;
	if ( $field_name === '' ) {
		return false;
	}
	foreach ( xpressui_normalize_uploaded_files( $file_params ) as $file ) {
		if ( (string) ( $file['field'] ?? '' ) !== $field_name ) {
			continue;
		}
		if ( ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) === UPLOAD_ERR_OK && ! empty( $file['tmp_name'] ) ) {
			return true;
		}
	}
	return false;
}

function xpressui_validate_uploaded_files( array $file_params ) {
	$files             = xpressui_normalize_uploaded_files( $file_params );
	$allowed_mime_map  = get_allowed_mime_types();
	$allowed_exts      = array_keys( $allowed_mime_map );
	$max_files         = 20;
	$max_bytes_per_file = 10 * MB_IN_BYTES;

	if ( count( $files ) > $max_files ) {
		return new WP_Error(
			'xpressui_too_many_files',
			__( 'Too many uploaded files.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
	}

	foreach ( $files as $file ) {
		$name = isset( $file['name'] ) ? sanitize_file_name( (string) $file['name'] ) : '';
		$size = isset( $file['size'] ) ? (int) $file['size'] : 0;

		if ( ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) !== UPLOAD_ERR_OK ) {
			return new WP_Error(
				'xpressui_invalid_upload',
				__( 'One of the uploaded files is invalid.', 'xpressui-bridge' ),
				[ 'status' => 400 ]
			);
		}

		if ( $name === '' || $size <= 0 ) {
			return new WP_Error(
				'xpressui_empty_upload',
				__( 'Uploaded files must have a valid name and size.', 'xpressui-bridge' ),
				[ 'status' => 400 ]
			);
		}

		if ( $size > $max_bytes_per_file ) {
			return new WP_Error(
				'xpressui_file_too_large',
				__( 'Uploaded files must be 10 MB or smaller.', 'xpressui-bridge' ),
				[ 'status' => 400 ]
			);
		}

		$ext = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( $ext === '' ) {
			return new WP_Error(
				'xpressui_missing_extension',
				__( 'Uploaded files must have a valid file extension.', 'xpressui-bridge' ),
				[ 'status' => 400 ]
			);
		}

		$ext_allowed = false;
		foreach ( $allowed_exts as $allowed_ext_group ) {
			$group = explode( '|', (string) $allowed_ext_group );
			if ( in_array( $ext, $group, true ) ) {
				$ext_allowed = true;
				break;
			}
		}
		if ( ! $ext_allowed ) {
			return new WP_Error(
				'xpressui_disallowed_extension',
				__( 'One of the uploaded file types is not allowed.', 'xpressui-bridge' ),
				[ 'status' => 400 ]
			);
		}
	}

	return true;
}

function xpressui_store_uploaded_files( $post_id, WP_REST_Request $request ) {
	$stored_files = [];
	$debug        = [
		'requestFileKeys'   => [],
		'superglobalFileKeys' => [],
		'normalizedFiles'   => [],
		'errors'            => [],
	];

	$file_params = xpressui_get_request_file_params( $request );
	$debug['requestFileKeys']    = array_keys( (array) $request->get_file_params() );

	$debug['superglobalFileKeys'] = array_keys( is_array( $_FILES ) ? $_FILES : [] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- REST route, authentication via permission_callback

	foreach ( xpressui_normalize_uploaded_files( $file_params ) as $index => $file ) {
		$debug['normalizedFiles'][] = [
			'field'      => $file['field'] ?? '',
			'name'       => $file['name'] ?? '',
			'error'      => $file['error'] ?? UPLOAD_ERR_NO_FILE,
			'size'       => $file['size'] ?? 0,
			'hasTmpName' => ! empty( $file['tmp_name'] ),
		];

		if ( ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) !== UPLOAD_ERR_OK || empty( $file['tmp_name'] ) ) {
			$debug['errors'][] = [
				'field'     => $file['field'] ?? '',
				'message'   => 'Upload missing tmp_name or has PHP upload error.',
				'errorCode' => $file['error'] ?? UPLOAD_ERR_NO_FILE,
			];
			continue;
		}

		$tmp_key          = sprintf( 'xpressui_upload_%d', $index );
		$_FILES[ $tmp_key ] = [
			'name'     => $file['name'],
			'type'     => $file['type'],
			'tmp_name' => $file['tmp_name'],
			'error'    => $file['error'],
			'size'     => $file['size'],
		];

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attachment = media_handle_upload( $tmp_key, $post_id, [], [ 'test_form' => false ] );
		unset( $_FILES[ $tmp_key ] );

		if ( is_wp_error( $attachment ) ) {
			$debug['errors'][] = [
				'field'     => $file['field'] ?? '',
				'message'   => $attachment->get_error_message(),
				'errorCode' => $attachment->get_error_code(),
			];
			continue;
		}

		$stored_files[] = [
			'field'        => $file['field'],
			'originalName' => $file['name'],
			'attachmentId' => $attachment,
			'url'          => wp_get_attachment_url( $attachment ),
		];
	}

	update_post_meta( $post_id, '_xpressui_uploaded_files', wp_json_encode( $stored_files ) );
	update_post_meta( $post_id, '_xpressui_upload_debug', wp_json_encode( $debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
	return $stored_files;
}

function xpressui_attach_file_references( $payload, array $stored_files ) {
	if ( ! is_array( $payload ) || empty( $stored_files ) ) {
		return $payload;
	}
	// Group refs by field name so multi-file fields produce an array instead of
	// overwriting the entry on each iteration.
	$by_field = [];
	foreach ( $stored_files as $file ) {
		$field_name = isset( $file['field'] ) ? (string) $file['field'] : '';
		if ( $field_name === '' ) {
			continue;
		}
		$by_field[ $field_name ][] = [
			'field'        => $field_name,
			'kind'         => 'uploaded-file',
			'originalName' => isset( $file['originalName'] ) ? (string) $file['originalName'] : '',
			'attachmentId' => isset( $file['attachmentId'] ) ? (int) $file['attachmentId'] : 0,
			'url'          => isset( $file['url'] ) ? (string) $file['url'] : '',
		];
	}
	foreach ( $by_field as $field_name => $refs ) {
		$payload[ $field_name ] = count( $refs ) === 1 ? $refs[0] : $refs;
	}
	return $payload;
}

/**
 * Scan the payload for signature data URIs (data:image/...) and save each
 * as a WordPress media attachment. Replaces the data URI with the
 * attachment URL so the payload stays lightweight and email-renderable.
 *
 * @param int   $post_id
 * @param mixed $payload
 * @return mixed Updated payload.
 */
function xpressui_store_signature_attachments( $post_id, $payload ) {
	if ( ! is_array( $payload ) ) {
		return $payload;
	}

	$ext_map = [
		'image/png'  => 'png',
		'image/jpeg' => 'jpg',
		'image/gif'  => 'gif',
		'image/webp' => 'webp',
	];

	foreach ( $payload as $key => $value ) {
		if ( ! is_string( $value ) || ! str_starts_with( $value, 'data:image/' ) ) {
			continue;
		}
		if ( ! preg_match( '/^data:(image\/[a-zA-Z+]+);base64,(.+)$/s', $value, $m ) ) {
			continue;
		}
		$mime_type = $m[1];
		$data      = base64_decode( $m[2], true );
		if ( $data === false ) {
			continue;
		}

		$ext      = $ext_map[ $mime_type ] ?? 'png';
		$filename = sanitize_file_name( "signature-{$key}-{$post_id}.{$ext}" );
		$upload = wp_upload_bits( $filename, null, $data );

		if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
			continue;
		}

		$attachment_id = wp_insert_attachment(
			[
				'post_mime_type' => $mime_type,
				'post_title'     => $filename,
				'post_content'   => '',
				'post_status'    => 'inherit',
			],
			$upload['file'],
			$post_id
		);

		if ( is_wp_error( $attachment_id ) ) {
			continue;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );

		$payload[ $key ] = wp_get_attachment_url( $attachment_id );
	}

	return $payload;
}
