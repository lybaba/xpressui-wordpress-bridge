<?php
/**
 * Webhook receiver to sync or delete hosted link configurations from the IntakeFlow Console.
 *
 * @package XPressUI_Bridge_Pro
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', 'xpressui_register_webhook_sync_route' );

/**
 * Register the sync-link REST API route.
 */
function xpressui_register_webhook_sync_route(): void {
	register_rest_route(
		'xpressui/v1',
		'/sync-link',
		[
			'methods'             => 'POST',
			'callback'            => 'xpressui_handle_webhook_sync_link',
			'permission_callback' => 'xpressui_webhook_sync_link_permissions_check',
		]
	);
}

/**
 * Perform signature verification using HMAC-SHA256.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return bool|WP_Error True if valid, WP_Error if unauthorized.
 */
function xpressui_webhook_sync_link_permissions_check( WP_REST_Request $request ) {
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
	$path      = '/wp-json/xpressui/v1/sync-link';
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
 * Handle sync and delete actions for hosted link configurations.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return WP_REST_Response Response object.
 */
function xpressui_handle_webhook_sync_link( WP_REST_Request $request ): WP_REST_Response {
	$body    = json_decode( $request->get_body(), true );
	$action  = sanitize_text_field( (string) ( $body['action'] ?? '' ) );
	$slug    = sanitize_title( (string) ( $body['projectSlug'] ?? '' ) );
	$link_id = sanitize_file_name( (string) ( $body['linkId'] ?? '' ) );

	if ( empty( $action ) || empty( $slug ) || empty( $link_id ) ) {
		return new WP_REST_Response(
			[
				'success' => false,
				'message' => __( 'Missing required parameters.', 'xpressui-bridge' ),
			],
			400
		);
	}

	if ( ! xpressui_is_installed_workflow( $slug ) ) {
		return new WP_REST_Response(
			[
				'success' => false,
				'message' => __( 'Unknown workflow project.', 'xpressui-bridge' ),
			],
			404
		);
	}

	$target_dir = xpressui_get_workflows_base_dir();
	if ( empty( $target_dir ) || ! file_exists( $target_dir ) ) {
		return new WP_REST_Response(
			[
				'success' => false,
				'message' => __( 'Workflows directory not found.', 'xpressui-bridge' ),
			],
			500
		);
	}

	$slug_dir = trailingslashit( $target_dir ) . $slug . '/';
	if ( ! is_dir( $slug_dir ) ) {
		return new WP_REST_Response(
			[
				'success' => false,
				'message' => __( 'Workflow project directory not found.', 'xpressui-bridge' ),
			],
			404
		);
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	global $wp_filesystem;

	$hosted_links_dir = $slug_dir . 'hosted-links/';
	$link_dir         = $hosted_links_dir . $link_id . '/';

	if ( 'delete' === $action ) {
		if ( file_exists( $link_dir ) ) {
			$wp_filesystem->delete( $link_dir, true );
		}
		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Hosted link deleted successfully.', 'xpressui-bridge' )
			],
			200
		);
	}

	if ( 'sync' === $action ) {
		$config = $body['config'] ?? null;
		if ( ! is_array( $config ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Missing config payload for sync action.', 'xpressui-bridge' ),
				],
				400
			);
		}

		// Double-check: only retrieve/sync hosted links of type default (pas les relances)
		$payload_data = is_array( $config['payload'] ?? null ) ? $config['payload'] : [];
		$follow_up    = $payload_data['followUp'] ?? $payload_data['follow_up'] ?? [];
		if ( is_array( $follow_up ) && ( ! empty( $follow_up['parentSubmissionId'] ) || ! empty( $follow_up['parent_submission_id'] ) ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Ignored non-default/follow-up hosted link webhook.', 'xpressui-bridge' ),
				],
				200
			);
		}

		// Clean up any old, orphaned hosted link configurations for this link_id in other projects.
		if ( is_dir( $target_dir ) ) {
			$subdirs = glob( trailingslashit( $target_dir ) . '*', GLOB_ONLYDIR );
			if ( is_array( $subdirs ) ) {
				foreach ( $subdirs as $subdir ) {
					$potential_slug = basename( $subdir );
					if ( $potential_slug !== $slug ) {
						$old_link_dir = trailingslashit( $subdir ) . 'hosted-links/' . $link_id . '/';
						if ( file_exists( $old_link_dir ) ) {
							$wp_filesystem->delete( $old_link_dir, true );
						}
					}
				}
			}
		}

		if ( ! file_exists( $hosted_links_dir ) ) {
			wp_mkdir_p( $hosted_links_dir );
		}
		if ( ! file_exists( $link_dir ) ) {
			wp_mkdir_p( $link_dir );
		}

		$written = $wp_filesystem->put_contents(
			$link_dir . 'link.config.json',
			wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
			FS_CHMOD_FILE
		);

		if ( ! $written ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => __( 'Could not write link configuration file.', 'xpressui-bridge' ),
				],
				500
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Hosted link synchronized successfully.', 'xpressui-bridge' ),
			],
			200
		);
	}

	return new WP_REST_Response(
		[
			'success' => false,
			'message' => __( 'Unsupported action.', 'xpressui-bridge' ),
		],
		400
	);
}
