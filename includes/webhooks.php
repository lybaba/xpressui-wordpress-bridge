<?php
/**
 * Submission cloud-sync dispatcher.
 *
 * The plugin no longer fires outbound webhooks directly. Instead, after a
 * submission is persisted locally it is uploaded (values + file references, not
 * file contents) to the IntakeFlow Console ingest endpoint server-to-server with
 * the stored developer API token. The Console persists it as a real backup AND
 * delivers any configured webhook(s) itself — so webhook delivery is owned by the
 * SaaS, not WordPress.
 *
 * Best-effort: a sync failure never blocks submission storage or the REST
 * response. The per-submission status meta (`_xpressui_webhook_status`) now
 * reflects the SaaS-sync result: local_only, local_only_quota_exceeded, queued,
 * synced, or failed.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'xpressui_dispatch_webhook_async', 'xpressui_dispatch_webhook_async', 10, 3 );

/**
 * Resolves the hosted-link id carried on a submission payload, if any.
 *
 * @param array|string $payload Submission payload.
 * @return string Hosted link id, or '' when project-scoped.
 */
function xpressui_resolve_submission_link_id( $payload ) {
	return is_array( $payload ) ? trim( (string) ( $payload['hostedLinkId'] ?? '' ) ) : '';
}

/**
 * Queue (or synchronously fall back) the cloud sync of a stored submission.
 *
 * Gated by the `xpressui_enable_cloud_sync` option and the Free-tier backup
 * quota — when sync is off or the quota is exceeded the submission stays
 * local-only and no upload happens.
 *
 * @param int    $post_id      WordPress post ID of the stored submission.
 * @param string $project_slug Workflow project slug.
 * @param mixed  $payload      Submission payload (array or JSON string).
 */
function xpressui_maybe_send_webhook( $post_id, $project_slug, $payload ) {
	$enable_sync = get_option( 'xpressui_enable_cloud_sync', '1' ) === '1';
	if ( ! $enable_sync ) {
		update_post_meta( $post_id, '_xpressui_webhook_status', 'local_only' );
		update_post_meta( $post_id, '_xpressui_webhook_error', __( 'Cloud sync is disabled in settings.', 'xpressui-bridge' ) );
		return;
	}

	// Count synced submissions to check quota (100 free tier backups).
	$synced_count = xpressui_count_synced_submissions();
	$quota_limit  = 100;

	$is_pro = xpressui_is_saas_connected();
	if ( ! $is_pro && $synced_count >= $quota_limit ) {
		update_post_meta( $post_id, '_xpressui_webhook_status', 'local_only_quota_exceeded' );
		update_post_meta( $post_id, '_xpressui_webhook_error', __( 'Cloud sync paused: backup quota exceeded on Free Plan.', 'xpressui-bridge' ) );
		return;
	}

	// Without a Console connection there is nothing to sync to.
	$conn = xpressui_get_console_connection();
	if ( empty( $conn['apiUrl'] ) || empty( $conn['apiToken'] ) ) {
		update_post_meta( $post_id, '_xpressui_webhook_status', 'local_only' );
		update_post_meta( $post_id, '_xpressui_webhook_error', __( 'Console connection is not configured.', 'xpressui-bridge' ) );
		return;
	}

	// Queue the upload to keep the submit response fast for end users.
	$event_args = [ (int) $post_id, (string) $project_slug, is_array( $payload ) ? $payload : [] ];
	update_post_meta( $post_id, '_xpressui_webhook_status', 'queued' );
	update_post_meta( $post_id, '_xpressui_webhook_code', '' );
	update_post_meta( $post_id, '_xpressui_webhook_error', '' );
	update_post_meta( $post_id, '_xpressui_webhook_sent_at', '' );
	xpressui_record_submission_event(
		$post_id,
		'delivery.webhook.queued',
		'bridge',
		[],
		[
			'project_slug' => $project_slug,
		]
	);

	if ( ! wp_next_scheduled( 'xpressui_dispatch_webhook_async', $event_args ) ) {
		$scheduled = wp_schedule_single_event( time() + 1, 'xpressui_dispatch_webhook_async', $event_args );
		if ( ! $scheduled ) {
			xpressui_dispatch_webhook_async( (int) $post_id, (string) $project_slug, is_array( $payload ) ? $payload : [] );
		}
	}
}

/**
 * Async cloud-sync worker (WP-Cron).
 *
 * Uploads the submission to the Console ingest endpoint. The Console persists the
 * backup and delivers any configured webhook(s); this worker no longer makes any
 * direct external webhook call.
 *
 * @param int    $post_id      Submission post ID.
 * @param string $project_slug Workflow project slug.
 * @param array  $payload      Submission payload.
 */
function xpressui_dispatch_webhook_async( $post_id, $project_slug, $payload ) {
	$post_id      = (int) $post_id;
	$project_slug = sanitize_title( (string) $project_slug );
	$payload      = is_array( $payload ) ? $payload : [];
	if ( $post_id <= 0 || $project_slug === '' ) {
		return;
	}

	$result = xpressui_sync_submission_to_saas( $post_id, $project_slug, $payload );
	xpressui_store_webhook_result( $post_id, $result );
}

/**
 * Upload a stored submission to the Console ingest endpoint.
 *
 * Server-to-server with the stored developer API token (mirrors the catalog-order
 * proxy). Sends submission VALUES + FILE REFERENCES — never file contents.
 *
 * @param int    $post_id      Submission post ID.
 * @param string $project_slug Workflow project slug.
 * @param array  $payload      Submission payload.
 * @param array  $extra        Optional extra body fields (e.g. returnUrl/cancelUrl
 *                             for a headless catalog order checkout).
 * @return array|WP_Error Return value from wp_remote_post().
 */
function xpressui_sync_submission_to_saas( $post_id, $project_slug, $payload, $extra = [] ) {
	$conn = xpressui_get_console_connection();
	if ( empty( $conn['apiUrl'] ) || empty( $conn['apiToken'] ) ) {
		return new WP_Error(
			'xpressui_no_connection',
			__( 'Console connection is not configured.', 'xpressui-bridge' )
		);
	}

	$body = xpressui_build_submission_ingest_body( $post_id, $project_slug, $payload );
	if ( is_array( $extra ) && ! empty( $extra ) ) {
		$body = array_merge( $body, $extra );
	}
	$link_id = (string) ( $body['hostedLinkId'] ?? '' );

	$endpoint = trailingslashit( $conn['apiUrl'] ) . 'api/v1/projects/' . rawurlencode( $project_slug );
	if ( $link_id !== '' ) {
		$endpoint .= '/hosted-links/' . rawurlencode( $link_id );
	}
	$endpoint .= '/submissions';

	return wp_remote_post(
		$endpoint,
		[
			'timeout'     => 15,
			'redirection' => 0,
			'headers'     => [
				'X-Api-Token'  => $conn['apiToken'],
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			],
			'body'        => wp_json_encode( $body ),
		]
	);
}

/**
 * Build the ingest request body for the Console submission endpoint.
 *
 * Mirrors the data the previous direct-webhook payload assembled (values + file
 * references + project/submission ids + hostedLinkId) in the shape the SaaS
 * ingest endpoint expects.
 *
 * @param int    $post_id      Submission post ID.
 * @param string $project_slug Workflow project slug.
 * @param mixed  $payload      Raw submission values.
 * @return array<string, mixed>
 */
function xpressui_build_submission_ingest_body( $post_id, $project_slug, $payload ) {
	$project_config_version = (string) get_post_meta( $post_id, '_xpressui_project_config_version', true );
	$submission_id          = (string) get_post_meta( $post_id, '_xpressui_submission_id', true );
	$uploaded_files         = json_decode( (string) get_post_meta( $post_id, '_xpressui_uploaded_files', true ), true );
	$values                 = is_array( $payload ) ? $payload : [];
	$link_id                = xpressui_resolve_submission_link_id( $payload );

	// Fall back to the entry id so the SaaS always has a stable idempotency key.
	if ( $submission_id === '' ) {
		$submission_id = 'wp-' . (string) $post_id;
	}

	$submitter_email = '';
	foreach ( [ 'email', 'Email', 'e-mail', 'emailAddress' ] as $key ) {
		if ( ! empty( $values[ $key ] ) && is_string( $values[ $key ] ) ) {
			$submitter_email = sanitize_email( $values[ $key ] );
			break;
		}
	}

	return [
		'event'                => 'xpressui.submission.created',
		'submissionId'         => $submission_id,
		'projectConfigVersion' => $project_config_version,
		'hostedLinkId'         => $link_id,
		'submittedAt'          => gmdate( 'Y-m-d\TH:i:s\Z' ),
		'submitterEmail'       => $submitter_email,
		'site'                 => [
			'name' => get_bloginfo( 'name' ),
			'url'  => home_url( '/' ),
		],
		'values'               => $values,
		'files'                => is_array( $uploaded_files ) ? $uploaded_files : [],
	];
}

/**
 * Count submissions that have actually been synced to the cloud.
 *
 * Used both for the Free-tier quota guard and the dashboard "cloud backups"
 * counter, so the number reflects real backups rather than every local
 * submission.
 *
 * @return int
 */
function xpressui_count_synced_submissions() {
	$posts = get_posts(
		[
			'post_type'      => 'xpressui_submission',
			'post_status'    => 'private',
			'posts_per_page' => -1,
		]
	);

	if ( ! is_array( $posts ) ) {
		return 0;
	}

	$count = 0;
	foreach ( $posts as $post ) {
		$status = get_post_meta( $post->ID, '_xpressui_webhook_status', true );
		if ( in_array( $status, [ 'synced', 'sent' ], true ) ) {
			$count++;
		}
	}
	return $count;
}

/**
 * Store minimal cloud-sync delivery metadata on the submission post.
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
		xpressui_record_submission_event(
			$post_id,
			'delivery.webhook.failed',
			'bridge',
			[],
			[
				'error_message' => (string) $result->get_error_message(),
			]
		);
		return;
	}

	$code = (int) wp_remote_retrieve_response_code( $result );
	if ( $code >= 200 && $code < 300 ) {
		update_post_meta( $post_id, '_xpressui_webhook_status', 'synced' );
		xpressui_record_submission_event(
			$post_id,
			'delivery.webhook.sent',
			'bridge',
			[
				'http_code' => $code,
			],
			[]
		);
	} else {
		$detail = wp_remote_retrieve_response_message( $result );
		$raw    = json_decode( (string) wp_remote_retrieve_body( $result ), true );
		if ( is_array( $raw ) && ! empty( $raw['detail'] ) && is_string( $raw['detail'] ) ) {
			$detail = $raw['detail'];
		}
		update_post_meta( $post_id, '_xpressui_webhook_status', 'failed' );
		update_post_meta( $post_id, '_xpressui_webhook_error', $detail );
		xpressui_record_submission_event(
			$post_id,
			'delivery.webhook.failed',
			'bridge',
			[
				'http_code' => $code,
			],
			[
				'error_message' => (string) $detail,
			]
		);
	}
	update_post_meta( $post_id, '_xpressui_webhook_code', (string) $code );
	update_post_meta( $post_id, '_xpressui_webhook_sent_at', $sent_at );
}

/**
 * Whether a stored submission payload carries a headless catalog ORDER (a product
 * cart + a chosen payment method). Such submissions are pushed to the SaaS
 * synchronously so the SaaS can re-price and (for Stripe) return a Checkout URL.
 *
 * @param mixed $payload Submission payload (array) or JSON string.
 * @return bool
 */
function xpressui_submission_is_catalog_order( $payload ) {
	if ( is_string( $payload ) ) {
		$payload = json_decode( $payload, true );
	}
	if ( ! is_array( $payload ) ) {
		return false;
	}
	if ( '' === trim( (string) ( $payload['xpressuiPaymentMethod'] ?? '' ) ) ) {
		return false;
	}
	$cart = $payload['xpressuiProductCart'] ?? '';
	if ( is_string( $cart ) ) {
		$cart = json_decode( $cart, true );
	}
	return is_array( $cart ) && ! empty( $cart );
}

/**
 * Returns the URL only if it is an absolute http(s) URL on this site's own host
 * (same origin as home_url()), else ''. Guards the Stripe return/cancel URLs
 * against open-redirect to a foreign host.
 *
 * @param mixed $url Candidate URL.
 * @return string Validated same-origin URL, or ''.
 */
function xpressui_validate_same_origin_url( $url ) {
	$url = esc_url_raw( trim( (string) $url ) );
	if ( '' === $url ) {
		return '';
	}
	$parts  = wp_parse_url( $url );
	$home   = wp_parse_url( home_url( '/' ) );
	$scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
	if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
		return '';
	}
	$host      = strtolower( (string) ( $parts['host'] ?? '' ) );
	$home_host = strtolower( (string) ( $home['host'] ?? '' ) );
	return ( '' !== $host && $host === $home_host ) ? $url : '';
}
