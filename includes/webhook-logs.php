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

	if ( 'synced' === $status || 'sent' === $status ) {
		wp_send_json_success( [
			'message' => __( 'Sync successful!', 'xpressui-bridge' ),
			'code'    => $code,
			'status'  => 'synced',
		] );
	} else {
		wp_send_json_error( [
			/* translators: 1: HTTP error code, 2: error message */
			'message' => sprintf( __( 'Sync failed with code %1$s: %2$s', 'xpressui-bridge' ), $code, $error ),
			'code'    => $code,
			'status'  => 'failed',
			'error'   => $error,
		] );
	}
}
add_action( 'wp_ajax_xpressui_retry_sync', 'xpressui_ajax_retry_sync' );

/**
 * Whether submissions are actually being backed up to the IntakeFlow Console.
 *
 * "Cloud subscription active" means the site is connected to the Console (a stored
 * developer apiToken) AND cloud sync is enabled — the exact combination under which
 * a submission gets uploaded to the SaaS (see xpressui_maybe_send_webhook()).
 *
 * @return bool
 */
function xpressui_cloud_sync_is_active() {
	$connected = xpressui_is_saas_connected();
	$enabled   = get_option( 'xpressui_enable_cloud_sync', '1' ) === '1';
	return $connected && $enabled;
}

/**
 * Whether ANY webhook endpoint is configured across installed workflows.
 *
 * Mirrors the two webhook sources the shortcode render resolves: the local
 * per-workflow override (xpressui_project_settings[*]['webhookUrl']) and any synced
 * hosted-link config (link.config.json → payload.webhookUrl). A non-empty match in
 * either source counts as "a webhook endpoint is defined".
 *
 * @return bool
 */
function xpressui_any_webhook_endpoint_configured() {
	// 1. Local per-workflow overrides.
	$all_settings = get_option( 'xpressui_project_settings', [] );
	if ( is_array( $all_settings ) ) {
		foreach ( $all_settings as $project_settings ) {
			if ( is_array( $project_settings ) && ! empty( $project_settings['webhookUrl'] ) ) {
				return true;
			}
		}
	}

	// 2. Synced hosted-link configs (payload.webhookUrl).
	if ( function_exists( 'xpressui_get_installed_workflow_slugs' ) && function_exists( 'xpressui_get_workflows_base_dir' ) ) {
		$base_dir = xpressui_get_workflows_base_dir();
		if ( $base_dir !== '' ) {
			foreach ( xpressui_get_installed_workflow_slugs() as $slug ) {
				$links_dir = trailingslashit( $base_dir ) . $slug . '/hosted-links/';
				if ( ! is_dir( $links_dir ) ) {
					continue;
				}
				$configs = glob( trailingslashit( $links_dir ) . '*/link.config.json' );
				foreach ( (array) $configs as $config_file ) {
					$raw = file_get_contents( $config_file );
					if ( ! is_string( $raw ) || $raw === '' ) {
						continue;
					}
					$config  = json_decode( $raw, true );
					$payload = ( is_array( $config ) && is_array( $config['payload'] ?? null ) ) ? $config['payload'] : [];
					if ( ! empty( $payload['webhookUrl'] ) ) {
						return true;
					}
				}
			}
		}
	}

	return false;
}

/**
 * Renders the clean empty / setup state shown when neither cloud sync nor any
 * webhook endpoint is configured — so the page never fills with meaningless rows.
 */
function xpressui_render_sync_logs_empty_state() {
	$connect_url   = xpressui_get_wordpress_connect_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-sync-logs' ) );
	$workflows_url = admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-bridge' );
	?>
	<div class="card xpressui-admin-card" style="margin-top: 15px; max-width: 720px;">
		<h2><?php esc_html_e( 'No sync activity yet', 'xpressui-bridge' ); ?></h2>
		<p class="description" style="font-size: 13px; line-height: 1.6;">
			<?php esc_html_e( 'Sync and delivery logs appear here once form submissions start flowing to a destination. Right now nothing is being delivered because:', 'xpressui-bridge' ); ?>
		</p>
		<ul style="list-style: disc; margin: 12px 0 16px 20px; color: #475569; font-size: 13px; line-height: 1.7;">
			<li><?php esc_html_e( 'this site is not connected to the IntakeFlow Console (cloud backup is off), and', 'xpressui-bridge' ); ?></li>
			<li><?php esc_html_e( 'no workflow has a webhook endpoint configured.', 'xpressui-bridge' ); ?></li>
		</ul>
		<p class="description" style="font-size: 13px; line-height: 1.6;">
			<?php esc_html_e( 'Connect the Console to back up submissions to the cloud (the Console then delivers any webhooks you configure), or set a webhook URL on a specific workflow.', 'xpressui-bridge' ); ?>
		</p>
		<p style="margin-top: 16px;">
			<a href="<?php echo esc_url( $connect_url ); ?>" class="button button-primary">
				<?php esc_html_e( 'Connect to IntakeFlow Console', 'xpressui-bridge' ); ?>
			</a>
			<a href="<?php echo esc_url( $workflows_url ); ?>" class="button" style="margin-left: 8px;">
				<?php esc_html_e( 'Configure a workflow webhook', 'xpressui-bridge' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * Option key for the standalone admin-notification email delivery log. Stored independently
 * of submission posts so it also captures emails for TRIAL/unconnected workflows, whose
 * submissions are emailed but never stored locally (no post to hang mail meta on).
 */
const XPRESSUI_EMAIL_LOG_OPTION = 'xpressui_email_delivery_log';

/**
 * Append one admin-notification email to the delivery log (capped, newest-first).
 *
 * @param string $to      Recipient email.
 * @param string $subject Email subject.
 * @param string $status  'sent' | 'failed' | 'queued'.
 * @param int    $post_id Submission post ID, or 0 for trial/unconnected workflows.
 */
function xpressui_log_notification_email( $to, $subject, $status, $post_id = 0 ) {
	$to = trim( (string) $to );
	if ( $to === '' ) {
		return;
	}
	$log = get_option( XPRESSUI_EMAIL_LOG_OPTION, [] );
	if ( ! is_array( $log ) ) {
		$log = [];
	}
	array_unshift(
		$log,
		[
			'subject'   => sanitize_text_field( (string) $subject ),
			'recipient' => sanitize_email( $to ),
			'status'    => in_array( $status, [ 'sent', 'failed', 'queued' ], true ) ? $status : 'queued',
			'sent_at'   => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'post_id'   => (int) $post_id,
		]
	);
	if ( count( $log ) > 50 ) {
		$log = array_slice( $log, 0, 50 );
	}
	update_option( XPRESSUI_EMAIL_LOG_OPTION, $log, false );
}

/**
 * Counts admin-notification emails successfully sent (status = "sent") from the delivery log.
 */
function xpressui_count_notification_emails() {
	$log = get_option( XPRESSUI_EMAIL_LOG_OPTION, [] );
	if ( ! is_array( $log ) ) {
		return 0;
	}
	$count = 0;
	foreach ( $log as $entry ) {
		if ( is_array( $entry ) && ( $entry['status'] ?? '' ) === 'sent' ) {
			$count++;
		}
	}
	return $count;
}

/**
 * On the All Submissions list, surface a reassurance banner: how many notification emails
 * have been sent + a link to the delivery log. Trial-form submissions are emailed (not always
 * stored), so without this a user may think a test submission "did nothing".
 */
function xpressui_submissions_list_mail_notice() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'edit-xpressui_submission' !== $screen->id || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$count = xpressui_count_notification_emails();
	$url   = admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-sync-logs' );
	$msg   = $count > 0
		? sprintf(
			/* translators: %s: number of emails */
			_n( '%s notification email sent for your submissions.', '%s notification emails sent for your submissions.', $count, 'xpressui-bridge' ),
			number_format_i18n( $count )
		)
		: __( 'New submissions trigger an admin notification email from this site.', 'xpressui-bridge' );
	printf(
		'<div class="notice notice-info" style="display:flex; align-items:center; gap:12px;"><span class="dashicons dashicons-email-alt" style="color:#2563eb;"></span><p style="margin:.5em 0; flex:1;">%1$s</p><a href="%2$s" class="button" style="margin-right:8px;">%3$s</a></div>',
		esc_html( $msg ),
		esc_url( $url ),
		esc_html__( 'View email delivery log', 'xpressui-bridge' )
	);
}
add_action( 'admin_notices', 'xpressui_submissions_list_mail_notice' );

/**
 * Renders the local email-delivery log shown to sites that aren't connected to the Console.
 *
 * Free / unconnected sites send admin notification emails via WordPress (wp_mail), so instead
 * of an empty "no sync activity" screen we surface proof that those emails are going out —
 * subject, recipient, status and time, read from the per-submission mail meta.
 */
function xpressui_render_mail_delivery_log() {
	$entries = get_option( XPRESSUI_EMAIL_LOG_OPTION, [] );
	if ( ! is_array( $entries ) ) {
		$entries = [];
	}
	$date_fmt = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	?>
	<div class="card xpressui-admin-card" style="margin-top: 15px; max-width: 100%;">
		<h2 style="display:flex; align-items:center; gap:10px;"><?php esc_html_e( 'Email delivery log', 'xpressui-bridge' ); ?>
			<?php $gs_sent = xpressui_count_notification_emails(); if ( $gs_sent > 0 ) : ?>
				<span style="background:#dcfce7; color:#166534; font-size:12px; font-weight:700; padding:2px 10px; border-radius:999px;"><?php
					/* translators: %s: number of emails */
					echo esc_html( sprintf( __( '%s sent', 'xpressui-bridge' ), number_format_i18n( $gs_sent ) ) );
				?></span>
			<?php endif; ?>
		</h2>
		<p class="description" style="font-size: 13px; line-height: 1.6;">
			<?php esc_html_e( 'Admin notification emails this site sent for new submissions. Connect the IntakeFlow Console to send via the cloud for higher deliverability (SPF/DKIM/DMARC) and to back up submissions.', 'xpressui-bridge' ); ?>
		</p>
		<?php if ( empty( $entries ) ) : ?>
			<div style="padding: 32px; text-align: center; color: #64748b;">
				<p style="font-weight: 600; margin: 0;"><?php esc_html_e( 'No notification emails sent yet.', 'xpressui-bridge' ); ?></p>
				<p style="font-size: 13px; margin: 6px 0 0;"><?php esc_html_e( 'They appear here once a submission arrives on a form that has a notification recipient configured.', 'xpressui-bridge' ); ?></p>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped" style="margin-top: 16px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Subject', 'xpressui-bridge' ); ?></th>
						<th style="width: 230px;"><?php esc_html_e( 'Recipient', 'xpressui-bridge' ); ?></th>
						<th style="width: 110px;"><?php esc_html_e( 'Status', 'xpressui-bridge' ); ?></th>
						<th style="width: 180px;"><?php esc_html_e( 'Sent', 'xpressui-bridge' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $entries as $entry ) :
					if ( ! is_array( $entry ) ) {
						continue;
					}
					$recipient = (string) ( $entry['recipient'] ?? '' );
					if ( $recipient === '' ) {
						continue;
					}
					$status  = (string) ( $entry['status'] ?? '' );
					$sent_at = (string) ( $entry['sent_at'] ?? '' );
					$subject = (string) ( $entry['subject'] ?? '' );
					$post_id = (int) ( $entry['post_id'] ?? 0 );
					if ( $subject === '' ) {
						$subject = __( 'New submission', 'xpressui-bridge' );
					}
					$color = 'sent' === $status ? '#16a34a' : ( 'failed' === $status ? '#dc2626' : '#64748b' );
					$status_label = $status !== '' ? $status : 'queued';
					$when = '—';
					if ( $sent_at !== '' ) {
						$when = mysql2date( $date_fmt, get_date_from_gmt( str_replace( [ 'T', 'Z' ], [ ' ', '' ], $sent_at ) ) );
					}
					$edit_link = $post_id > 0 ? get_edit_post_link( $post_id ) : '';
				?>
					<tr>
						<td><?php if ( $edit_link ) : ?><a href="<?php echo esc_url( $edit_link ); ?>"><?php echo esc_html( $subject ); ?></a><?php else : ?><?php echo esc_html( $subject ); ?><?php endif; ?></td>
						<td><code><?php echo esc_html( $recipient ); ?></code></td>
						<td><span style="display:inline-block; padding:2px 9px; border-radius:999px; font-size:11px; font-weight:600; color:#fff; background:<?php echo esc_attr( $color ); ?>;"><?php echo esc_html( $status_label ); ?></span></td>
						<td><?php echo esc_html( $when ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Renders the Sync Logs panel.
 */
function xpressui_render_sync_logs_tab() {
	// No cloud subscription AND no webhook endpoint anywhere → show the clean
	// setup state instead of a wall of meaningless "no destination" rows.
	$cloud_active   = xpressui_cloud_sync_is_active();
	$webhook_set    = xpressui_any_webhook_endpoint_configured();
	if ( ! $cloud_active && ! $webhook_set ) {
		// Not connected + no webhook → show the local email-delivery log (proof admin
		// notifications are going out) instead of an empty "no sync activity" screen.
		xpressui_render_mail_delivery_log();
		return;
	}

	// Query recent submissions
	$args = [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'posts_per_page' => 20,
		'orderby'        => 'date',
		'order'          => 'DESC',
	];
	$posts = get_posts( $args );

	$destination_label = $cloud_active
		? __( 'IntakeFlow Console (cloud backup + webhook delivery)', 'xpressui-bridge' )
		: __( 'Configured workflow webhook', 'xpressui-bridge' );

	?>
	<div class="card xpressui-admin-card" style="margin-top: 15px; max-width: 100%;">
		<h2><?php esc_html_e( 'Sync Logs', 'xpressui-bridge' ); ?></h2>
		<p class="description">
			<?php
			printf(
				/* translators: %s: sync destination label. */
				esc_html__( 'Per-submission backup status. Destination: %s.', 'xpressui-bridge' ),
				'<strong>' . esc_html( $destination_label ) . '</strong>'
			);
			?>
		</p>

		<table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
			<thead>
				<tr>
					<th style="width: 80px;"><?php esc_html_e( 'ID', 'xpressui-bridge' ); ?></th>
					<th><?php esc_html_e( 'Workflow', 'xpressui-bridge' ); ?></th>
					<th><?php esc_html_e( 'Date', 'xpressui-bridge' ); ?></th>
					<th><?php esc_html_e( 'Destination', 'xpressui-bridge' ); ?></th>
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
						$status       = (string) get_post_meta( $post_id, '_xpressui_webhook_status', true );
						$code         = get_post_meta( $post_id, '_xpressui_webhook_code', true );
						$error        = get_post_meta( $post_id, '_xpressui_webhook_error', true );
						$date         = get_the_date( 'Y-m-d H:i', $post_id );

						// Submissions sync to the IntakeFlow Console, which persists the
						// backup and delivers any configured webhook(s) itself.
						$destination = __( 'IntakeFlow Console', 'xpressui-bridge' );

						// Map the real sync status to a meaningful badge. Submissions with
						// no status meta predate cloud sync (stored locally only).
						$badge_style  = 'background: #e2e8f0; color: #475569;';
						$status_label = __( 'Local only', 'xpressui-bridge' );
						if ( 'synced' === $status || 'sent' === $status ) {
							$badge_style  = 'background: #dcfce7; color: #15803d;';
							$status_label = __( 'Synced', 'xpressui-bridge' );
						} elseif ( 'failed' === $status ) {
							$badge_style  = 'background: #fee2e2; color: #b91c1c;';
							$status_label = __( 'Failed', 'xpressui-bridge' );
						} elseif ( 'queued' === $status ) {
							$badge_style  = 'background: #fef9c3; color: #a16207;';
							$status_label = __( 'Queued', 'xpressui-bridge' );
						} elseif ( 'local_only' === $status ) {
							$badge_style  = 'background: #e2e8f0; color: #475569;';
							$status_label = __( 'Local only', 'xpressui-bridge' );
						} elseif ( 'local_only_quota_exceeded' === $status ) {
							$badge_style  = 'background: #ffedd5; color: #c2410c;';
							$status_label = __( 'Local only (quota reached)', 'xpressui-bridge' );
						}

						// Retry only helps when a sync was attempted/queued but did not
						// land — i.e. failed or quota-paused. Already-synced rows and rows
						// that were never eligible to sync get no retry button.
						$can_retry = in_array( $status, [ 'failed', 'queued', 'local_only_quota_exceeded' ], true );
						?>
						<tr id="xpressui-sync-row-<?php echo esc_attr( $post_id ); ?>">
							<td><code>#<?php echo esc_html( $post_id ); ?></code></td>
							<td><strong><?php echo esc_html( $project_slug ); ?></strong></td>
							<td><?php echo esc_html( $date ); ?></td>
							<td style="font-size: 12px;"><?php echo esc_html( $destination ); ?></td>
							<td>
								<span class="xpressui-status-badge" style="padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; <?php echo esc_attr( $badge_style ); ?>">
									<?php echo esc_html( $status_label ); ?>
								</span>
							</td>
							<td class="sync-code"><code><?php echo esc_html( $code ?: '-' ); ?></code></td>
							<td class="sync-error" style="font-size: 11px; color: #dc2626;"><?php echo esc_html( $error ?: '-' ); ?></td>
							<td style="text-align: center;">
								<?php if ( $can_retry ) : ?>
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
								.text('<?php echo esc_js( __( 'Synced', 'xpressui-bridge' ) ); ?>')
								.attr('style', 'padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: #dcfce7; color: #15803d;');
							$row.find('.sync-code code').text(response.data.code);
							$row.find('.sync-error').text('-');
							// Synced rows are no longer retryable.
							$btn.remove();
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
