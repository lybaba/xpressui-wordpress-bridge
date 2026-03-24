<?php
/**
 * REST API endpoint for receiving XPressUI form submissions.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function xpressui_register_rest_routes() {
	register_rest_route( 'xpressui/v1', '/submit', [
		'methods'             => 'POST',
		'callback'            => 'xpressui_handle_submission',
		'permission_callback' => '__return_true',
	] );
}

function xpressui_handle_submission( WP_REST_Request $request ) {
	$payload              = $request->get_param( 'payload' );
	$project_id           = $request->get_param( 'projectId' ) ?: 'dev-project';
	$project_config_version = $request->get_param( 'projectConfigVersion' ) ?: '';
	$submission_id        = $request->get_param( 'submissionId' );
	$project_slug         = $request->get_param( 'projectSlug' ) ?: 'dev-project';
	$project_config       = xpressui_normalize_config_snapshot( $request->get_param( 'projectConfigSnapshotJson' ) );

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

	update_post_meta( $post_id, '_xpressui_project_id', $project_id );
	update_post_meta( $post_id, '_xpressui_project_slug', $project_slug );
	update_post_meta( $post_id, '_xpressui_project_config_version', $project_config_version );
	update_post_meta( $post_id, '_xpressui_submission_id', $submission_id ?: '' );

	xpressui_store_config_snapshot( $project_id, $project_slug, $project_config_version, $project_config );
	xpressui_set_submission_status( $post_id, 'new', __( 'Submission received', 'xpressui-bridge' ) );

	$stored_files        = xpressui_store_uploaded_files( $post_id, $request );
	$payload_with_files  = xpressui_attach_file_references( $payload, $stored_files );

	update_post_meta( $post_id, '_xpressui_payload_json',
		is_string( $payload_with_files )
			? $payload_with_files
			: wp_json_encode( $payload_with_files )
	);

	// Fire notification after payload is stored.
	xpressui_maybe_send_notification( $post_id, $project_slug, $payload_with_files );

	// Read per-project redirect URL.
	$redirect_url = xpressui_get_project_setting( $project_slug, 'redirectUrl' );

	$response = [
		'success'      => true,
		'message'      => __( 'Submission received', 'xpressui-bridge' ),
		'entryId'      => $post_id,
		'submissionId' => $submission_id,
		'files'        => $stored_files,
	];
	if ( $redirect_url !== '' ) {
		$response['redirectUrl'] = $redirect_url;
	}

	return new WP_REST_Response( $response, 200 );
}

// ---------------------------------------------------------------------------
// File handling
// ---------------------------------------------------------------------------

function xpressui_get_request_file_params( WP_REST_Request $request ) {
	$request_files    = $request->get_file_params();
	$superglobal_files = is_array( $_FILES ) ? $_FILES : [];

	if ( ! is_array( $request_files ) || empty( $request_files ) ) {
		return $superglobal_files;
	}
	return array_replace_recursive( $superglobal_files, $request_files );
}

function xpressui_normalize_uploaded_files( array $file_params ) {
	$normalized = [];
	foreach ( $file_params as $field_name => $file_info ) {
		if ( ! is_array( $file_info ) || ! isset( $file_info['name'] ) ) {
			continue;
		}
		if ( is_array( $file_info['name'] ) ) {
			$count = count( $file_info['name'] );
			for ( $i = 0; $i < $count; $i++ ) {
				$normalized[] = [
					'field'    => $field_name,
					'name'     => $file_info['name'][ $i ] ?? '',
					'type'     => $file_info['type'][ $i ] ?? '',
					'tmp_name' => $file_info['tmp_name'][ $i ] ?? '',
					'error'    => $file_info['error'][ $i ] ?? UPLOAD_ERR_NO_FILE,
					'size'     => $file_info['size'][ $i ] ?? 0,
				];
			}
			continue;
		}
		$normalized[] = [
			'field'    => $field_name,
			'name'     => $file_info['name'],
			'type'     => $file_info['type'] ?? '',
			'tmp_name' => $file_info['tmp_name'] ?? '',
			'error'    => $file_info['error'] ?? UPLOAD_ERR_NO_FILE,
			'size'     => $file_info['size'] ?? 0,
		];
	}
	return $normalized;
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
	$debug['superglobalFileKeys'] = array_keys( is_array( $_FILES ) ? $_FILES : [] );

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
	foreach ( $stored_files as $file ) {
		$field_name = isset( $file['field'] ) ? (string) $file['field'] : '';
		if ( $field_name === '' ) {
			continue;
		}
		$payload[ $field_name ] = [
			'field'        => $field_name,
			'kind'         => 'uploaded-file',
			'originalName' => isset( $file['originalName'] ) ? (string) $file['originalName'] : '',
			'attachmentId' => isset( $file['attachmentId'] ) ? (int) $file['attachmentId'] : 0,
			'url'          => isset( $file['url'] ) ? (string) $file['url'] : '',
		];
	}
	return $payload;
}
