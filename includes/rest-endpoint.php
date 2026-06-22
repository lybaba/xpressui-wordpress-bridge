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
	register_rest_route( 'xpressui/v1', '/catalog-order', [
		'methods'             => 'POST',
		'callback'            => 'xpressui_handle_catalog_order',
		'permission_callback' => '__return_true',
	] );
	register_rest_route( 'xpressui/v1', '/sync', [
		'methods'             => 'POST',
		'callback'            => 'xpressui_handle_sync_webhook',
		'permission_callback' => 'xpressui_sync_permissions_check',
	] );
}

/**
 * Proxies a headless catalog checkout to the SaaS orders endpoint.
 *
 * The browser posts the customer form values + cart items + chosen payment method
 * here; this calls the SaaS `.../hosted-links/{id}/orders` server-to-server with the
 * stored developer API token (never exposed to the browser). The SaaS re-prices the
 * cart, creates the submission, and returns a Stripe checkout URL (card) or a
 * created status (manual). Nothing client-supplied is trusted for pricing.
 */
function xpressui_handle_catalog_order( WP_REST_Request $request ) {
	$slug    = sanitize_title( (string) $request->get_param( 'projectSlug' ) );
	$link_id = sanitize_text_field( (string) $request->get_param( 'linkId' ) );
	if ( '' === $slug || '' === $link_id || ! xpressui_is_installed_workflow( $slug ) ) {
		return new WP_Error( 'xpressui_bad_request', __( 'Unknown workflow or link.', 'xpressui-bridge' ), [ 'status' => 400 ] );
	}

	$conn = xpressui_get_console_connection();
	if ( empty( $conn['apiUrl'] ) || empty( $conn['apiToken'] ) ) {
		return new WP_Error( 'xpressui_no_connection', __( 'Console connection is not configured.', 'xpressui-bridge' ), [ 'status' => 503 ] );
	}

	$raw_items = $request->get_param( 'items' );
	$items     = [];
	if ( is_array( $raw_items ) ) {
		foreach ( $raw_items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$id = sanitize_text_field( (string) ( $item['id'] ?? '' ) );
			if ( '' === $id ) {
				continue;
			}
			$items[] = [ 'id' => $id, 'quantity' => max( 1, (int) ( $item['quantity'] ?? 1 ) ) ];
		}
	}
	if ( empty( $items ) ) {
		return new WP_Error( 'xpressui_empty_cart', __( 'Your cart is empty.', 'xpressui-bridge' ), [ 'status' => 422 ] );
	}

	$form_values = $request->get_param( 'formValues' );
	$form_values = is_array( $form_values ) ? $form_values : [];
	$payment_method = sanitize_text_field( (string) $request->get_param( 'paymentMethod' ) );

	$endpoint = trailingslashit( $conn['apiUrl'] ) . 'api/v1/projects/' . rawurlencode( $slug )
		. '/hosted-links/' . rawurlencode( $link_id ) . '/orders';
	$response = wp_remote_post(
		$endpoint,
		[
			'headers' => [
				'X-Api-Token'  => $conn['apiToken'],
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			],
			'body'    => wp_json_encode(
				[ 'formValues' => $form_values, 'items' => $items, 'paymentMethod' => $payment_method ]
			),
			'timeout' => 30,
		]
	);
	if ( is_wp_error( $response ) ) {
		return new WP_Error( 'xpressui_order_failed', $response->get_error_message(), [ 'status' => 502 ] );
	}
	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( $code < 200 || $code >= 300 ) {
		$detail = ( is_array( $body ) && ! empty( $body['detail'] ) && is_string( $body['detail'] ) )
			? $body['detail']
			: __( 'Order could not be created.', 'xpressui-bridge' );
		return new WP_Error( 'xpressui_order_failed', $detail, [ 'status' => 502 ] );
	}
	return rest_ensure_response( is_array( $body ) ? $body : [] );
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

	// Spam Honeypot Protection: Reject submission if honeypot fields are filled.
	$hp_val1 = $request->get_param( 'xpressui_honeypot' );
	$hp_val2 = $request->get_param( 'confirm_email' );
	$payload_hp1 = is_array( $payload ) ? ( $payload['xpressui_honeypot'] ?? '' ) : '';
	$payload_hp2 = is_array( $payload ) ? ( $payload['confirm_email'] ?? '' ) : '';

	if ( ! empty( $hp_val1 ) || ! empty( $hp_val2 ) || ! empty( $payload_hp1 ) || ! empty( $payload_hp2 ) ) {
		return new WP_Error(
			'xpressui_spam_detected',
			__( 'Spam submission detected.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
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

	// Submission gate: allows add-ons (e.g. Pro license) to block submission.
	$submission_gate = apply_filters( 'xpressui_submission_gate', null, $project_slug, $payload, $request );
	if ( is_wp_error( $submission_gate ) ) {
		return $submission_gate;
	}

	$is_trial = false;
	if ( ! xpressui_is_saas_connected() ) {
		$is_trial = true;
	} else {
		$meta = xpressui_get_workflow_manifest_meta( $project_slug );
		$tier = is_array( $meta ) ? (string) ( $meta['runtimeTier'] ?? '' ) : '';
		if ( 'trial' === $tier ) {
			$is_trial = true;
		}
	}

	if ( $is_trial ) {
		// Trial connect model:
		// Send notification emails but do not save to local WordPress database.
		$stored_files       = xpressui_store_uploaded_files( 0, $request );
		$payload_with_files = xpressui_attach_file_references( $payload, $stored_files );
		$payload_with_files = xpressui_store_signature_attachments( 0, $payload_with_files );

		xpressui_maybe_send_notification( 0, $project_slug, $payload_with_files );
		xpressui_maybe_send_submitter_sample( 0, $project_slug, $payload_with_files );
		xpressui_maybe_send_submit_confirmation( 0, $project_slug, $payload_with_files );

		$redirect_url = xpressui_resolve_redirect_url( $project_slug, $payload_with_files );

		$timing_summary = [
			'totalMs' => (int) round( ( microtime( true ) - $timing_marks['start'] ) * 1000 ),
		];

		$response = [
			'success'      => true,
			'message'      => __( 'Submission received', 'xpressui-bridge' ),
			'entryId'      => 0,
			'submissionId' => $submission_id,
			'files'        => $stored_files,
			'timing'       => $timing_summary,
		];
		if ( $redirect_url !== '' ) {
			$response['redirectUrl'] = $redirect_url;
		}

		return new WP_REST_Response( $response, 200 );
	}

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
	// Optionally email the submitter a copy of the admin-style notification (no admin button).
	xpressui_maybe_send_submitter_sample( $post_id, $project_slug, $payload_with_files );
	$mark_timing( 'notification_sent' );

	// Send confirmation email to the submitter on first submit.
	xpressui_maybe_send_submit_confirmation( $post_id, $project_slug, $payload_with_files );
	$mark_timing( 'submit_confirmation_sent' );

	// Delivery to the SaaS. For a headless catalog ORDER, push SYNCHRONOUSLY so the
	// SaaS can re-price the cart and (for Stripe) create the Checkout Session, returning
	// its URL for the browser to redirect to — payment + status are owned by the SaaS.
	// Non-order submissions keep the fast async (WP-Cron) delivery.
	$order_checkout_url   = '';
	$order_payment_status = '';
	if ( xpressui_submission_is_catalog_order( $payload_with_files ) ) {
		$order_extra = array_filter( [
			'returnUrl' => xpressui_validate_same_origin_url( $request->get_param( 'returnUrl' ) ),
			'cancelUrl' => xpressui_validate_same_origin_url( $request->get_param( 'cancelUrl' ) ),
		] );
		$order_result = xpressui_sync_submission_to_saas( $post_id, $project_slug, $payload_with_files, $order_extra );
		xpressui_store_webhook_result( $post_id, $order_result );
		if ( ! is_wp_error( $order_result ) ) {
			$decoded = json_decode( (string) wp_remote_retrieve_body( $order_result ), true );
			if ( is_array( $decoded ) ) {
				$order_checkout_url   = isset( $decoded['checkoutUrl'] ) ? esc_url_raw( (string) $decoded['checkoutUrl'] ) : '';
				$order_payment_status = isset( $decoded['paymentStatus'] ) ? sanitize_text_field( (string) $decoded['paymentStatus'] ) : '';
			}
		}
	} else {
		// Send outbound webhook (best-effort — failure does not affect submission response).
		xpressui_maybe_send_webhook( $post_id, $project_slug, $payload_with_files );
	}
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

	// Redirect URL comes from the synced Hosted Link presentation (or site home).
	$redirect_url = xpressui_resolve_redirect_url( $project_slug, $payload );

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
	// Headless catalog order: hand the browser the Stripe Checkout URL (card) so it can
	// redirect; manual methods carry the pending status. catalog-checkout.js reads these.
	if ( $order_checkout_url !== '' ) {
		$response['checkoutUrl'] = $order_checkout_url;
	}
	if ( $order_payment_status !== '' ) {
		$response['paymentStatus'] = $order_payment_status;
	}

	return new WP_REST_Response( $response, 200 );
}

function xpressui_handle_resubmission( WP_REST_Request $request, $payload, $token, $project_slug ) {
	$post_id = xpressui_get_resume_post_id_by_token( $token );
	return xpressui_handle_resubmission_by_post_id( $request, $payload, $post_id, $project_slug );
}

function xpressui_get_submission_ids_for_project_and_submission( $project_slug, $submission_id ) {
	$project_slug  = sanitize_title( (string) $project_slug );
	$submission_id = xpressui_sanitize_request_identifier( $submission_id );
	if ( '' === $project_slug || '' === $submission_id ) {
		return [];
	}

	$cache_key = 'submission_lookup_' . md5( $project_slug . '|' . $submission_id );
	$cached    = wp_cache_get( $cache_key, 'xpressui_bridge' );
	if ( false !== $cached ) {
		return is_array( $cached ) ? array_map( 'intval', $cached ) : [];
	}

	$matches = [];
	foreach ( get_posts( [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	] ) as $post_id ) {
		if ( $project_slug !== (string) get_post_meta( $post_id, '_xpressui_project_slug', true ) ) {
			continue;
		}
		if ( $submission_id !== (string) get_post_meta( $post_id, '_xpressui_submission_id', true ) ) {
			continue;
		}
		$matches[] = (int) $post_id;
	}

	wp_cache_set( $cache_key, $matches, 'xpressui_bridge', MINUTE_IN_SECONDS );
	return $matches;
}

function xpressui_find_resubmission_post_id( $project_slug, $submission_id ) {
	$project_slug  = sanitize_title( (string) $project_slug );
	$submission_id = xpressui_sanitize_request_identifier( $submission_id );
	if ( '' === $project_slug || '' === $submission_id ) {
		return 0;
	}

	foreach ( xpressui_get_submission_ids_for_project_and_submission( $project_slug, $submission_id ) as $post_id ) {
		if ( 'pending_info' === (string) get_post_meta( $post_id, '_xpressui_submission_status', true ) ) {
			return (int) $post_id;
		}
	}

	return 0;
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

	$file_validation = xpressui_validate_uploaded_files( $file_params, $project_slug );
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
		$existing_ids = xpressui_get_submission_ids_for_project_and_submission( $project_slug, $submission_id );
		if ( ! empty( $existing_ids ) ) {
			return new WP_Error(
				'xpressui_duplicate_submission',
				__( 'This submission has already been received.', 'xpressui-bridge' ),
				[ 'status' => 409 ]
			);
		}
	}

	$file_validation = xpressui_validate_uploaded_files( xpressui_get_request_file_params( $request ), $project_slug );
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
	$request_files = $request->get_file_params();
	return is_array( $request_files ) ? $request_files : [];
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

function xpressui_validate_uploaded_files( array $file_params, $project_slug = '' ) {
	$files             = xpressui_normalize_uploaded_files( $file_params );
	$allowed_mime_map  = get_allowed_mime_types();
	$allowed_exts      = array_keys( $allowed_mime_map );
	$max_files         = 20;
	$max_bytes_per_file = 10 * MB_IN_BYTES;

	$field_configs = [];
	if ( ! empty( $project_slug ) ) {
		$config_json = xpressui_get_workflow_artifact_contents( $project_slug, 'config', 'form.config.json' );
		if ( ! empty( $config_json ) ) {
			$form_config = json_decode( $config_json, true );
			if ( is_array( $form_config ) && isset( $form_config['sections'] ) && is_array( $form_config['sections'] ) ) {
				foreach ( $form_config['sections'] as $section_name => $fields ) {
					if ( is_array( $fields ) ) {
						foreach ( $fields as $field ) {
							if ( is_array( $field ) && isset( $field['name'] ) ) {
								$field_configs[ $field['name'] ] = $field;
							}
						}
					}
				}
			}
		}
	}

	if ( count( $files ) > $max_files ) {
		return new WP_Error(
			'xpressui_too_many_files',
			__( 'Too many uploaded files.', 'xpressui-bridge' ),
			[ 'status' => 400 ]
		);
	}

	foreach ( $files as $file ) {
		$name       = isset( $file['name'] ) ? sanitize_file_name( (string) $file['name'] ) : '';
		$size       = isset( $file['size'] ) ? (int) $file['size'] : 0;
		$field_name = isset( $file['field'] ) ? $file['field'] : '';

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

		// Check if archives are explicitly disallowed for this field.
		if ( ! empty( $field_name ) && isset( $field_configs[ $field_name ] ) ) {
			$field_cfg = $field_configs[ $field_name ];
			if ( ! empty( $field_cfg['disableArchives'] ) ) {
				$mime = isset( $file['type'] ) ? strtolower( (string) $file['type'] ) : '';
				$is_archive = ( $ext === 'zip' ) || ( $ext === 'rar' ) || ( $ext === '7z' ) || ( $ext === 'tar' ) || ( $ext === 'gz' ) || ( $ext === 'tgz' ) ||
							  ( strpos( $mime, 'zip' ) !== false || strpos( $mime, 'compressed' ) !== false || strpos( $mime, 'archive' ) !== false );
				if ( $is_archive ) {
					return new WP_Error(
						'xpressui_archives_disallowed',
						__( 'Archives (.zip) are not allowed for this field.', 'xpressui-bridge' ),
						[ 'status' => 400 ]
					);
				}
			}
		}
	}

	return true;
}

function xpressui_store_uploaded_files( $post_id, WP_REST_Request $request ) {
	$stored_files = [];

	// Performance: the operator inbox only ever uses the 'thumbnail' size
	// (see xpressui_get_file_thumb_url). Generating the full WordPress size set
	// (medium, medium_large, large, 1536/2048 and theme sizes) for every
	// submission upload is the slow part of a file submission, so restrict
	// intermediate-size generation to 'thumbnail' while we store the files.
	$xpressui_limit_sizes = static function ( $sizes ) {
		return is_array( $sizes ) ? array_intersect_key( $sizes, [ 'thumbnail' => true ] ) : $sizes;
	};
	add_filter( 'intermediate_image_sizes_advanced', $xpressui_limit_sizes, 99 );
		$debug        = [
			'requestFileKeys'   => [],
			'normalizedFiles'   => [],
			'errors'            => [],
		];

		$file_params = xpressui_get_request_file_params( $request );
		$debug['requestFileKeys']    = array_keys( (array) $request->get_file_params() );

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

			$sideload_file = [
				'name'     => $file['name'],
				'type'     => $file['type'],
				'tmp_name' => $file['tmp_name'],
				'error'    => $file['error'],
				'size'     => $file['size'],
			];

			require_once ABSPATH . 'wp-admin/includes/file.php';
			$upload = wp_handle_sideload( $sideload_file, [ 'test_form' => false ] );

			if ( ! is_array( $upload ) || ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
				$debug['errors'][] = [
					'field'     => $file['field'] ?? '',
					'message'   => is_array( $upload ) && ! empty( $upload['error'] ) ? (string) $upload['error'] : 'Upload sideload failed.',
					'errorCode' => 'upload_failed',
				];
				continue;
			}

			$attachment = wp_insert_attachment(
				[
					'post_mime_type' => sanitize_mime_type( (string) ( $upload['type'] ?? $file['type'] ?? '' ) ),
					'post_title'     => sanitize_file_name( (string) ( $file['name'] ?? 'upload' ) ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				],
				(string) $upload['file'],
				$post_id
			);
			if ( is_wp_error( $attachment ) ) {
				wp_delete_file( (string) $upload['file'] );
				$debug['errors'][] = [
					'field'     => $file['field'] ?? '',
					'message'   => $attachment->get_error_message(),
					'errorCode' => $attachment->get_error_code(),
				];
				continue;
			}

			require_once ABSPATH . 'wp-admin/includes/image.php';
			wp_update_attachment_metadata( $attachment, wp_generate_attachment_metadata( $attachment, (string) $upload['file'] ) );

			$stored_files[] = [
				'field'        => $file['field'],
				'originalName' => $file['name'],
			'attachmentId' => $attachment,
			'url'          => wp_get_attachment_url( $attachment ),
		];
	}

	remove_filter( 'intermediate_image_sizes_advanced', $xpressui_limit_sizes, 99 );

	if ( $post_id > 0 ) {
		update_post_meta( $post_id, '_xpressui_uploaded_files', wp_json_encode( $stored_files ) );
		update_post_meta( $post_id, '_xpressui_upload_debug', wp_json_encode( $debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
	}
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

/**
 * Permissions check for the Console sync webhook.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return bool|WP_Error True if authorized, WP_Error otherwise.
 */
function xpressui_sync_permissions_check( WP_REST_Request $request ) {
	$timestamp = $request->get_header( 'x-bridge-timestamp' );
	$site_id   = $request->get_header( 'x-bridge-site-id' );
	$signature = $request->get_header( 'x-bridge-signature' );

	if ( empty( $timestamp ) || empty( $site_id ) || empty( $signature ) ) {
		return new WP_Error(
			'xpressui_webhook_missing_headers',
			__( 'Missing security headers.', 'xpressui-bridge' ),
			[ 'status' => 401 ]
		);
	}

	$conn          = xpressui_get_console_connection();
	$shared_secret = $conn['sharedSecret'] ?? $conn['shared_secret'] ?? '';

	if ( empty( $shared_secret ) ) {
		return new WP_Error(
			'xpressui_webhook_not_configured',
			__( 'Webhook synchronization is not configured (missing shared secret).', 'xpressui-bridge' ),
			[ 'status' => 403 ]
		);
	}

	$method    = 'POST';
	$path      = '/wp-json/xpressui/v1/sync';
	$body      = $request->get_body();
	$body_hash = hash( 'sha256', $body );

	$payload = implode(
		"\n",
		[
			strtoupper( $method ),
			$path,
			$timestamp,
			$site_id,
			$body_hash,
		]
	);

	$expected = hash_hmac( 'sha256', $payload, $shared_secret );

	if ( 0 === strpos( (string) $signature, 'v1=' ) ) {
		$signature = substr( (string) $signature, 3 );
	}

	if ( ! hash_equals( $expected, (string) $signature ) ) {
		return new WP_Error(
			'xpressui_webhook_invalid_signature',
			__( 'Invalid signature.', 'xpressui-bridge' ),
			[ 'status' => 403 ]
		);
	}

	return true;
}

/**
 * Handle sync webhook from the IntakeFlow Console.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return WP_REST_Response Response object.
 */
function xpressui_handle_sync_webhook( WP_REST_Request $request ): WP_REST_Response {
	$body       = json_decode( $request->get_body(), true );
	$project_id = sanitize_text_field( (string) ( $body['projectSlug'] ?? $body['projectId'] ?? '' ) );

	if ( empty( $project_id ) ) {
		return new WP_REST_Response(
			[
				'success' => false,
				'message' => __( 'Missing project slug or ID.', 'xpressui-bridge' ),
			],
			400
		);
	}

	$res = xpressui_sync_project( $project_id );
	if ( is_wp_error( $res ) ) {
		return new WP_REST_Response(
			[
				'success' => false,
				'message' => $res->get_error_message(),
			],
			500
		);
	}

	return new WP_REST_Response(
		[
			'success' => true,
			'slug'    => $res['slug'],
			'message' => __( 'Project synchronized successfully.', 'xpressui-bridge' ),
		],
		200
	);
}
