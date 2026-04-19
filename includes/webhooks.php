<?php
/**
 * Outbound webhook dispatcher for XPressUI submissions.
 *
 * Best-effort: a webhook failure never blocks submission storage or the REST response.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Send an outbound webhook for a submission if a webhook URL is configured.
 *
 * @param int    $post_id      WordPress post ID of the stored submission.
 * @param string $project_slug Workflow project slug.
 * @param mixed  $payload      Submission payload (array or JSON string).
 */
function xpressui_maybe_send_webhook( $post_id, $project_slug, $payload ) {
	$webhook_url = xpressui_get_project_setting( $project_slug, 'webhookUrl' );
	if ( $webhook_url === '' ) {
		return;
	}

	$body   = xpressui_build_webhook_payload( $post_id, $project_slug, $payload );
	$result = wp_remote_post(
		$webhook_url,
		[
			'timeout'     => 5,
			'redirection' => 0,
			'headers'     => [
				'Content-Type'    => 'application/json',
				'X-XPressUI-Event' => 'xpressui.submission.created',
			],
			'body'        => wp_json_encode( $body ),
		]
	);

	xpressui_store_webhook_result( $post_id, $result );
}

/**
 * Build the JSON payload sent to the webhook endpoint.
 *
 * @param int    $post_id      Submission post ID.
 * @param string $project_slug Workflow project slug.
 * @param mixed  $payload      Raw submission values.
 * @return array<string, mixed>
 */
function xpressui_build_webhook_payload( $post_id, $project_slug, $payload ) {
	$project_id           = (string) get_post_meta( $post_id, '_xpressui_project_id', true );
	$project_config_version = (string) get_post_meta( $post_id, '_xpressui_project_config_version', true );
	$submission_id        = (string) get_post_meta( $post_id, '_xpressui_submission_id', true );
	$uploaded_files       = json_decode( (string) get_post_meta( $post_id, '_xpressui_uploaded_files', true ), true );
	$values               = is_array( $payload ) ? $payload : [];

	return [
		'event'   => 'xpressui.submission.created',
		'sentAt'  => gmdate( 'Y-m-d\TH:i:s\Z' ),
		'project' => [
			'id'            => $project_id,
			'slug'          => $project_slug,
			'configVersion' => $project_config_version,
		],
		'submission' => [
			'entryId'      => (int) $post_id,
			'submissionId' => $submission_id,
		],
		'site' => [
			'name' => get_bloginfo( 'name' ),
			'url'  => home_url( '/' ),
		],
		'values' => $values,
		'files'  => is_array( $uploaded_files ) ? $uploaded_files : [],
	];
}

/**
 * Store minimal webhook delivery metadata on the submission post.
 *
 * @param int                      $post_id Submission post ID.
 * @param array|WP_Error           $result  Return value from wp_remote_post().
 */
function xpressui_store_webhook_result( $post_id, $result ) {
	$sent_at = gmdate( 'Y-m-d\TH:i:s\Z' );

	if ( is_wp_error( $result ) ) {
		update_post_meta( $post_id, '_xpressui_webhook_status', 'failed' );
		update_post_meta( $post_id, '_xpressui_webhook_code', '' );
		update_post_meta( $post_id, '_xpressui_webhook_error', $result->get_error_message() );
		update_post_meta( $post_id, '_xpressui_webhook_sent_at', $sent_at );
		return;
	}

	$code = (int) wp_remote_retrieve_response_code( $result );
	if ( $code >= 200 && $code < 300 ) {
		update_post_meta( $post_id, '_xpressui_webhook_status', 'success' );
	} else {
		update_post_meta( $post_id, '_xpressui_webhook_status', 'failed' );
		update_post_meta( $post_id, '_xpressui_webhook_error', wp_remote_retrieve_response_message( $result ) );
	}
	update_post_meta( $post_id, '_xpressui_webhook_code', (string) $code );
	update_post_meta( $post_id, '_xpressui_webhook_sent_at', $sent_at );
}
