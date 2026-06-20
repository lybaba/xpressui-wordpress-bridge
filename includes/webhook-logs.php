<?php
/**
 * Outbound webhook sync logs and manual re-try handler.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handler to manually retry submission sync to Console/Webhook.
 */
function xpressui_ajax_retry_sync() {
	check_ajax_referer( 'xpressui_retry_sync_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'Permission denied.', 'xpressui-bridge' ) ] );
	}

	$post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
	if ( $post_id <= 0 || 'xpressui_submission' !== get_post_type( $post_id ) ) {
		wp_send_json_error( [ 'message' => __( 'Invalid submission ID.', 'xpressui-bridge' ) ] );
	}

	$project_slug = get_post_meta( $post_id, '_xpressui_project_slug', true );
	$payload_json = get_post_meta( $post_id, '_xpressui_payload_json', true );
	$payload      = json_decode( $payload_json, true );

	if ( empty( $project_slug ) || ! is_array( $payload ) ) {
		wp_send_json_error( [ 'message' => __( 'Missing submission payload.', 'xpressui-bridge' ) ] );
	}

	// Trigger the webhook dispatcher directly
	if ( function_exists( 'xpressui_dispatch_webhook_async' ) ) {
		xpressui_dispatch_webhook_async( $post_id, $project_slug, $payload );
	} else {
		wp_send_json_error( [ 'message' => __( 'Webhook dispatcher is unavailable.', 'xpressui-bridge' ) ] );
	}

	$status = get_post_meta( $post_id, '_xpressui_webhook_status', true );
	$code   = get_post_meta( $post_id, '_xpressui_webhook_code', true );
	$error  = get_post_meta( $post_id, '_xpressui_webhook_error', true );

	if ( 'sent' === $status ) {
		wp_send_json_success( [
			'message' => __( 'Sync successful!', 'xpressui-bridge' ),
			'code'    => $code,
			'status'  => 'sent',
		] );
	} else {
		wp_send_json_error( [
			'message' => sprintf( __( 'Sync failed with code %1$s: %2$s', 'xpressui-bridge' ), $code, $error ),
			'code'    => $code,
			'status'  => 'failed',
			'error'   => $error,
		] );
	}
}
add_action( 'wp_ajax_xpressui_retry_sync', 'xpressui_ajax_retry_sync' );

/**
 * Renders the Sync Logs panel.
 */
function xpressui_render_sync_logs_tab() {
	// Query recent submissions
	$args = [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'posts_per_page' => 20,
		'orderby'        => 'date',
		'order'          => 'DESC',
	];
	$posts = get_posts( $args );

	?>
	<div class="card xpressui-admin-card" style="margin-top: 15px; max-width: 100%;">
		<h2><?php esc_html_e( 'Sync Logs (Webhook Deliveries)', 'xpressui-bridge' ); ?></h2>
		<p class="description"><?php esc_html_e( 'View the outgoing sync history of form submissions sent to the console/webhook destination, and retry failed syncs.', 'xpressui-bridge' ); ?></p>
		
		<table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
			<thead>
				<tr>
					<th style="width: 80px;"><?php esc_html_e( 'ID', 'xpressui-bridge' ); ?></th>
					<th><?php esc_html_e( 'Workflow', 'xpressui-bridge' ); ?></th>
					<th><?php esc_html_e( 'Date', 'xpressui-bridge' ); ?></th>
					<th><?php esc_html_e( 'Webhook URL', 'xpressui-bridge' ); ?></th>
					<th><?php esc_html_e( 'Status', 'xpressui-bridge' ); ?></th>
					<th><?php esc_html_e( 'HTTP Code', 'xpressui-bridge' ); ?></th>
					<th><?php esc_html_e( 'Error Message', 'xpressui-bridge' ); ?></th>
					<th style="width: 120px; text-align: center;"><?php esc_html_e( 'Actions', 'xpressui-bridge' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $posts ) ) : ?>
					<tr>
						<td colspan="8" style="text-align: center; padding: 20px; color: #64748b;">
							<?php esc_html_e( 'No submissions synced yet.', 'xpressui-bridge' ); ?>
						</td>
					</tr>
				<?php else : ?>
					<?php foreach ( $posts as $post ) : ?>
						<?php
						$post_id      = $post->ID;
						$project_slug = get_post_meta( $post_id, '_xpressui_project_slug', true );
						$status       = get_post_meta( $post_id, '_xpressui_webhook_status', true ) ?: 'not_configured';
						$code         = get_post_meta( $post_id, '_xpressui_webhook_code', true );
						$error        = get_post_meta( $post_id, '_xpressui_webhook_error', true );
						$date         = get_the_date( 'Y-m-d H:i', $post_id );

						$payload_json = get_post_meta( $post_id, '_xpressui_payload_json', true );
						$payload      = json_decode( $payload_json, true );
						$webhook_url  = '';
						if ( function_exists( 'xpressui_resolve_webhook_url' ) ) {
							$webhook_url = xpressui_resolve_webhook_url( $project_slug, $payload );
						}
						if ( empty( $webhook_url ) ) {
							$webhook_url = __( 'No Webhook Configured', 'xpressui-bridge' );
						}

						// Nice badge styles
						$badge_style = 'background: #cbd5e1; color: #475569;';
						$status_label = __( 'None', 'xpressui-bridge' );
						if ( 'sent' === $status ) {
							$badge_style = 'background: #dcfce7; color: #15803d;';
							$status_label = __( 'Sent', 'xpressui-bridge' );
						} elseif ( 'failed' === $status ) {
							$badge_style = 'background: #fee2e2; color: #b91c1c;';
							$status_label = __( 'Failed', 'xpressui-bridge' );
						} elseif ( 'queued' === $status ) {
							$badge_style = 'background: #fef9c3; color: #a16207;';
							$status_label = __( 'Queued', 'xpressui-bridge' );
						}
						?>
						<tr id="xpressui-sync-row-<?php echo esc_attr( $post_id ); ?>">
							<td><code>#<?php echo esc_html( $post_id ); ?></code></td>
							<td><strong><?php echo esc_html( $project_slug ); ?></strong></td>
							<td><?php echo esc_html( $date ); ?></td>
							<td style="word-break: break-all; font-size: 11px;"><code><?php echo esc_html( $webhook_url ); ?></code></td>
							<td>
								<span class="xpressui-status-badge" style="padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; <?php echo esc_attr( $badge_style ); ?>">
									<?php echo esc_html( $status_label ); ?>
								</span>
							</td>
							<td class="sync-code"><code><?php echo esc_html( $code ?: '-' ); ?></code></td>
							<td class="sync-error" style="font-size: 11px; color: #dc2626;"><?php echo esc_html( $error ?: '-' ); ?></td>
							<td style="text-align: center;">
								<?php if ( 'not_configured' !== $status ) : ?>
									<button 
										class="button xpressui-retry-sync-btn" 
										data-post-id="<?php echo esc_attr( $post_id ); ?>"
										data-nonce="<?php echo esc_attr( wp_create_nonce( 'xpressui_retry_sync_nonce' ) ); ?>"
									>
										<?php esc_html_e( 'Retry Sync', 'xpressui-bridge' ); ?>
									</button>
								<?php else : ?>
									<span style="color: #94a3b8; font-size: 11px;">-</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>

		<script>
		jQuery(document).ready(function($) {
			$('.xpressui-retry-sync-btn').on('click', function(e) {
				e.preventDefault();
				var $btn = $(this);
				var postId = $btn.data('post-id');
				var nonce = $btn.data('nonce');
				var $row = $('#xpressui-sync-row-' + postId);

				$btn.prop('disabled', true).text('<?php esc_html_e( 'Syncing...', 'xpressui-bridge' ); ?>');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'xpressui_retry_sync',
						post_id: postId,
						nonce: nonce
					},
					success: function(response) {
						if (response.success) {
							$row.find('.xpressui-status-badge')
								.text('<?php esc_html_e( 'Sent', 'xpressui-bridge' ); ?>')
								.attr('style', 'padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: #dcfce7; color: #15803d;');
							$row.find('.sync-code code').text(response.data.code);
							$row.find('.sync-error').text('-');
							alert(response.data.message);
						} else {
							$row.find('.xpressui-status-badge')
								.text('<?php esc_html_e( 'Failed', 'xpressui-bridge' ); ?>')
								.attr('style', 'padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: #fee2e2; color: #b91c1c;');
							$row.find('.sync-code code').text(response.data.code || '-');
							$row.find('.sync-error').text(response.data.error || response.data.message);
							alert(response.data.message);
						}
					},
					error: function() {
						alert('<?php esc_html_e( 'HTTP error occurred while retrying sync.', 'xpressui-bridge' ); ?>');
					},
					complete: function() {
						$btn.prop('disabled', false).text('<?php esc_html_e( 'Retry Sync', 'xpressui-bridge' ); ?>');
					}
				});
			});
		});
		</script>
	</div>
	<?php
}
