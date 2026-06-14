<?php
/**
 * Mobile Capture Relay — Pro feature.
 *
 * Allows users filling a form on desktop to capture signatures or photos
 * on their mobile phone via a QR code, then relay the data back to the
 * desktop form — without any permanent storage on this server.
 *
 * Flow:
 *  1. Desktop form calls POST /xpressui/v1/capture/session → gets {token, captureUrl}
 *  2. Desktop displays QR code pointing to captureUrl (?xpressui_capture={token})
 *  3. Mobile opens captureUrl → WordPress serves a lightweight capture page
 *  4. Mobile captures data → POST /xpressui/v1/capture/relay/{token}
 *  5. Desktop polls  GET  /xpressui/v1/capture/poll/{token} → gets data when ready
 *
 * Data lives only in WordPress transients (TTL 10 minutes). Deleted immediately
 * after the desktop picks it up.
 *
 * @package XPressUI_Bridge_Pro
 */

defined( 'ABSPATH' ) || exit;

define( 'XPRESSUI_CAPTURE_TTL', 10 * MINUTE_IN_SECONDS );

// ---------------------------------------------------------------------------
// REST routes
// ---------------------------------------------------------------------------

add_action( 'rest_api_init', 'xpressui_pro_register_capture_routes' );

function xpressui_pro_register_capture_routes(): void {
	register_rest_route( 'xpressui/v1', '/capture/session', [
		'methods'             => 'POST',
		'callback'            => 'xpressui_pro_create_capture_session',
		'permission_callback' => '__return_true',
		'args'                => [
			'fieldName'   => [ 'required' => true, 'sanitize_callback' => 'sanitize_key' ],
			'fieldType'   => [ 'required' => true, 'sanitize_callback' => 'sanitize_key' ],
			'projectSlug' => [ 'required' => false, 'default' => '', 'sanitize_callback' => 'sanitize_title' ],
		],
	] );

	register_rest_route( 'xpressui/v1', '/capture/relay/(?P<token>[a-f0-9]{32,64})', [
		'methods'             => 'POST',
		'callback'            => 'xpressui_pro_relay_capture_data',
		'permission_callback' => '__return_true',
	] );

	register_rest_route( 'xpressui/v1', '/capture/poll/(?P<token>[a-f0-9]{32,64})', [
		'methods'             => 'GET',
		'callback'            => 'xpressui_pro_poll_capture_session',
		'permission_callback' => '__return_true',
	] );
}

function xpressui_pro_create_capture_session( WP_REST_Request $request ): WP_REST_Response {
	$token        = bin2hex( random_bytes( 16 ) ); // 32 hex chars
	$field_name   = $request->get_param( 'fieldName' );
	$field_type   = $request->get_param( 'fieldType' );
	$project_slug = $request->get_param( 'projectSlug' );

	$allowed_types = [ 'signature', 'camera-photo', 'camera-photo-list', 'qr-scan', 'document-scan' ];
	if ( ! in_array( $field_type, $allowed_types, true ) ) {
		return new WP_REST_Response( [ 'message' => 'Unsupported field type.' ], 400 );
	}

	$session = [
		'token'       => $token,
		'fieldName'   => $field_name,
		'fieldType'   => $field_type,
		'projectSlug' => $project_slug,
		'status'      => 'pending',
		'data'        => null,
		'createdAt'   => time(),
	];

	set_transient( 'xpressui_capture_' . $token, $session, XPRESSUI_CAPTURE_TTL );

	$capture_url = add_query_arg(
		[
			'xpressui_capture'       => $token,
			'xpressui_capture_nonce' => hash_hmac( 'sha256', $token, wp_salt( 'nonce' ) ),
		],
		home_url( '/' )
	);

	return new WP_REST_Response( [
		'token'      => $token,
		'captureUrl' => $capture_url,
	], 200 );
}

function xpressui_pro_relay_capture_data( WP_REST_Request $request ): WP_REST_Response {
	$token   = (string) $request->get_param( 'token' );
	$session = get_transient( 'xpressui_capture_' . $token );

	if ( ! is_array( $session ) ) {
		return new WP_REST_Response( [ 'message' => 'Session not found or expired.' ], 404 );
	}

	$content_type = $request->get_header( 'Content-Type' ) ?? '';

	if ( str_contains( $content_type, 'multipart/form-data' ) ) {
		// Photo upload: store as WP attachment, relay URL to desktop
		$file = $request->get_file_params()['capture_file'] ?? null;
		if ( ! $file || ! isset( $file['tmp_name'] ) ) {
			return new WP_REST_Response( [ 'message' => 'No file received.' ], 400 );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$file_name = sanitize_file_name( $file['name'] ?? 'capture.jpg' );
		$upload    = wp_handle_upload( $file, [ 'test_form' => false ] );

		if ( isset( $upload['error'] ) || ! isset( $upload['file'] ) ) {
			return new WP_REST_Response( [ 'message' => $upload['error'] ?? 'Upload error.' ], 500 );
		}

		$attachment_id = wp_insert_attachment( [
			'post_mime_type' => $upload['type'],
			'post_title'     => $file_name,
			'post_content'   => '',
			'post_status'    => 'inherit',
		], $upload['file'] );

		if ( is_wp_error( $attachment_id ) ) {
			return new WP_REST_Response( [ 'message' => $attachment_id->get_error_message() ], 500 );
		}

		wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		$data = wp_json_encode( [ 'attachmentId' => $attachment_id, 'url' => $upload['url'] ] );

		// Store the attachment ID in session meta for cleanup after pickup
		$session['pendingAttachmentId'] = $attachment_id;
	} else {
		// Signature / QR: expect JSON with { data: string }
		$body = $request->get_json_params();
		$data = sanitize_text_field( (string) ( $body['data'] ?? '' ) );
		if ( '' === $data ) {
			return new WP_REST_Response( [ 'message' => 'No data received.' ], 400 );
		}
	}

	$session['status'] = 'completed';
	$session['data']   = $data;

	// Keep in transient for a short period so the desktop can pick it up
	set_transient( 'xpressui_capture_' . $token, $session, 2 * MINUTE_IN_SECONDS );

	return new WP_REST_Response( [ 'ok' => true ], 200 );
}

function xpressui_pro_poll_capture_session( WP_REST_Request $request ): WP_REST_Response {
	$token   = (string) $request->get_param( 'token' );
	$session = get_transient( 'xpressui_capture_' . $token );

	if ( ! is_array( $session ) ) {
		return new WP_REST_Response( [ 'status' => 'expired' ], 200 );
	}

	if ( $session['status'] !== 'completed' ) {
		return new WP_REST_Response( [ 'status' => 'pending' ], 200 );
	}

	$data = $session['data'];

	// Consume immediately: delete transient after first pickup
	delete_transient( 'xpressui_capture_' . $token );

	return new WP_REST_Response( [ 'status' => 'completed', 'data' => $data ], 200 );
}

// ---------------------------------------------------------------------------
// Capture page — served by WordPress for ?xpressui_capture={token}
// ---------------------------------------------------------------------------

add_filter( 'query_vars', 'xpressui_pro_register_capture_query_vars' );
add_action( 'template_redirect', 'xpressui_pro_maybe_serve_capture_page', 1 );

function xpressui_pro_register_capture_query_vars( array $vars ): array {
	$vars[] = 'xpressui_capture';
	$vars[] = 'xpressui_capture_nonce';
	return $vars;
}

function xpressui_pro_maybe_serve_capture_page(): void {
	$capture_params = xpressui_pro_get_capture_query_params();
	if ( '' === $capture_params['token'] ) {
		return;
	}

	$token = $capture_params['token'];
	$expected_signature = hash_hmac( 'sha256', $token, wp_salt( 'nonce' ) );
	if ( ! hash_equals( $expected_signature, $capture_params['nonce'] ) ) {
		status_header( 403 );
		exit;
	}
	$session = get_transient( 'xpressui_capture_' . $token );

	$field_type = 'signature';
	if ( is_array( $session ) ) {
		$field_type = (string) ( $session['fieldType'] ?? 'signature' );
	}

	$relay_url  = rest_url( 'xpressui/v1/capture/relay/' . rawurlencode( $token ) );
	$field_labels = [
		'signature'          => __( 'Draw your signature below', 'xpressui-bridge-pro' ),
		'camera-photo'       => __( 'Take a photo below', 'xpressui-bridge-pro' ),
		'camera-photo-list'  => __( 'Take a photo below', 'xpressui-bridge-pro' ),
		'document-scan'      => __( 'Photograph your document', 'xpressui-bridge-pro' ),
		'qr-scan'            => __( 'Scan a QR code', 'xpressui-bridge-pro' ),
	];
	$field_label = $field_labels[ $field_type ] ?? __( 'Capture on mobile', 'xpressui-bridge-pro' );

	xpressui_pro_output_capture_page( $token, $field_type, $relay_url, $field_label );
	exit;
}

function xpressui_pro_output_capture_page(
	string $token,
	string $field_type,
	string $relay_url,
	string $field_label
): void {
	unset( $token );
	// The compiled PHP template uses xpressui_bridge_template_* helpers — load them if not already loaded.
	require_once XPRESSUI_BRIDGE_DIR . 'templates/runtime.php';
	$xpressui_ctx = [
		'field_label' => $field_label,
		'field_type'  => $field_type,
		'relay_url'   => $relay_url,
		'runtime_url' => xpressui_pro_get_runtime_asset_url(),
	];
	include XPRESSUI_PRO_DIR . 'templates/generated/mobile-capture-page.html.php';
}

function xpressui_pro_get_capture_query_params(): array {
	$raw_capture_token = (string) get_query_var( 'xpressui_capture', '' );
	if ( '' === $raw_capture_token ) {
		return [
			'token' => '',
			'nonce' => '',
		];
	}

	$raw_capture_token = sanitize_text_field( $raw_capture_token );
	$token             = preg_replace( '/[^a-f0-9]/i', '', $raw_capture_token );
	$nonce             = sanitize_text_field( (string) get_query_var( 'xpressui_capture_nonce', '' ) );

	return [
		'token' => is_string( $token ) ? $token : '',
		'nonce' => $nonce,
	];
}
