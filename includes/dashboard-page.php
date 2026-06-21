<?php
/**
 * IntakeFlow Submissions Dashboard page.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Submissions Dashboard submenu page.
 */
function xpressui_register_dashboard_page() {
	add_submenu_page(
		'edit.php?post_type=xpressui_submission',
		__( 'IntakeFlow Dashboard', 'xpressui-bridge' ),
		__( 'Dashboard', 'xpressui-bridge' ),
		'manage_options',
		'xpressui-dashboard',
		'xpressui_render_dashboard_page'
	);
}
add_action( 'admin_menu', 'xpressui_register_dashboard_page' );

/**
 * Reorder submenu so Dashboard is the first page loaded under IntakeFlow menu.
 */
function xpressui_reorder_dashboard_submenu() {
	global $submenu;
	$parent = 'edit.php?post_type=xpressui_submission';
	if ( empty( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) ) {
		return;
	}

	$dashboard_item = null;
	foreach ( $submenu[ $parent ] as $item ) {
		if ( isset( $item[2] ) && 'xpressui-dashboard' === $item[2] ) {
			$dashboard_item = $item;
			break;
		}
	}
	if ( null === $dashboard_item ) {
		return;
	}

	$rebuilt = [];
	$rebuilt[] = $dashboard_item;

	foreach ( $submenu[ $parent ] as $item ) {
		if ( isset( $item[2] ) && 'xpressui-dashboard' === $item[2] ) {
			continue;
		}
		$rebuilt[] = $item;
	}
	$submenu[ $parent ] = array_values( $rebuilt );
}
add_action( 'admin_menu', 'xpressui_reorder_dashboard_submenu', 9999 );

/**
 * Renders the dashboard page.
 */
function xpressui_render_dashboard_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'xpressui-bridge' ) );
	}

	// 1. Fetch Metrics & Data
	$today_year  = gmdate( 'Y' );
	$today_month = gmdate( 'm' );

	$current_month_submissions = count( get_posts( [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'date_query'     => [
			[
				'year'  => $today_year,
				'month' => $today_month,
			],
		],
	] ) );

	// Cloud backups counts only submissions that actually synced to the Console,
	// not every local submission, so the "N / 100 cloud backups" figure is honest.
	$synced_backups = function_exists( 'xpressui_count_synced_submissions' )
		? xpressui_count_synced_submissions()
		: 0;

	$total_submissions = count( get_posts( [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	] ) );

	$posts = get_posts( [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'posts_per_page' => -1,
	] );

	$pending_info_count = 0;
	$in_review_count    = 0;
	$done_count         = 0;
	$corrections_count  = 0;

	if ( is_array( $posts ) ) {
		foreach ( $posts as $post ) {
			$status = get_post_meta( $post->ID, '_xpressui_submission_status', true );
			if ( 'pending_info' === $status ) {
				$pending_info_count++;
			} elseif ( 'in-review' === $status ) {
				$in_review_count++;
			} elseif ( 'done' === $status ) {
				$done_count++;
			}

			$flagged = get_post_meta( $post->ID, '_xpressui_flagged_fields', true );
			if ( ! empty( $flagged ) ) {
				$corrections_count++;
			}
		}
	}

	$hours_saved = $total_submissions > 0 ? max( 0.5, round( ( $total_submissions * 15 + $corrections_count * 20 ) / 60, 1 ) ) : 0;
	$quota_limit = 100;
	$quota_percentage = min( 100, round( ( $synced_backups / $quota_limit ) * 100 ) );

	$is_pro      = xpressui_is_saas_connected();
	$conn        = xpressui_get_console_connection();
	$console_url = ! empty( $conn['apiUrl'] ) ? $conn['apiUrl'] : 'https://app.intakeflow.dev';
	$upgrade_url = trailingslashit( $console_url ) . 'billing';

	// Fetch 10 Latest Submissions
	$latest_posts = get_posts( [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'posts_per_page' => 10,
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );

	?>
	<style>
	.xpui-dashboard-container {
		font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
		max-width: 1200px;
		margin-top: 10px;
	}
	.xpui-stats-row {
		display: flex;
		gap: 20px;
		flex-wrap: wrap;
		margin-bottom: 25px;
	}
	.xpui-stat-card {
		flex: 1;
		min-width: 220px;
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 12px;
		padding: 16px 20px;
		display: flex;
		align-items: center;
		gap: 15px;
		box-shadow: 0 1px 3px rgba(0,0,0,0.04);
		transition: transform 0.2s, border-color 0.2s;
	}
	.xpui-stat-card:hover {
		transform: translateY(-2px);
		border-color: #cbd5e1;
		box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06);
	}
	.xpui-stat-icon {
		font-size: 22px;
		width: 44px;
		height: 44px;
		border-radius: 10px;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-shrink: 0;
	}
	.xpui-stat-value {
		font-size: 22px;
		font-weight: 800;
		color: #0f172a;
		margin: 0;
		line-height: 1.2;
	}
	.xpui-stat-label {
		font-size: 12px;
		font-weight: 600;
		color: #64748b;
		margin: 0;
	}
	
	/* Layout grid for top widgets */
	.xpui-top-grid {
		display: flex;
		gap: 20px;
		flex-wrap: wrap;
		margin-bottom: 25px;
	}
	
	.xpui-dashboard-card {
		transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
		border: 1px solid #e2e8f0 !important;
		box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03), 0 2px 4px -1px rgba(0,0,0,0.02) !important;
	}
	.xpui-dashboard-card:hover {
		transform: translateY(-3px) !important;
		box-shadow: 0 10px 15px -3px rgba(15,23,42,0.08), 0 4px 6px -2px rgba(15,23,42,0.04) !important;
		border-color: #cbd5e1 !important;
	}
	.xpui-badge-status {
		font-size: 10px !important;
		font-weight: 700 !important;
		padding: 3px 10px !important;
		border-radius: 9999px !important;
		text-transform: uppercase !important;
		letter-spacing: 0.05em !important;
		display: inline-block !important;
	}
	.xpui-badge-pro {
		background: #dcfce7 !important;
		color: #166534 !important;
		border: 1px solid #bbf7d0 !important;
	}
	.xpui-badge-free {
		background: #eff6ff !important;
		color: #1e40af !important;
		border: 1px solid #bfdbfe !important;
	}
	.xpui-badge-disabled {
		background: #f1f5f9 !important;
		color: #475569 !important;
		border: 1px solid #e2e8f0 !important;
	}
	.xpui-badge-exceeded {
		background: #fee2e2 !important;
		color: #991b1b !important;
		border: 1px solid #fca5a5 !important;
	}
	
	/* Badges for Submissions Table */
	.xpui-sub-badge {
		font-size: 11px;
		font-weight: 700;
		padding: 2px 8px;
		border-radius: 6px;
		text-transform: uppercase;
		display: inline-block;
	}
	.xpui-sub-new { background: #eff6ff; color: #2563eb; }
	.xpui-sub-review { background: #fef3c7; color: #d97706; }
	.xpui-sub-pending { background: #fef2f2; color: #dc2626; }
	.xpui-sub-done { background: #f0fdf4; color: #16a34a; }
	
	.xpui-gradient-btn {
		background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
		color: white !important;
		border: none !important;
		font-weight: 700 !important;
		border-radius: 8px !important;
		padding: 6px 16px !important;
		text-decoration: none !important;
		box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.2) !important;
		transition: all 0.2s !important;
		cursor: pointer !important;
		display: inline-flex !important;
		align-items: center !important;
		justify-content: center !important;
		text-align: center !important;
		vertical-align: middle !important;
		font-size: 11px !important;
		line-height: 20px !important;
	}
	.xpui-gradient-btn:hover {
		opacity: 0.95 !important;
		transform: translateY(-1px) !important;
		box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3) !important;
	}
	.xpui-roi-gradient {
		background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important;
		border: 1px solid #bbf7d0 !important;
	}
	.xpui-roi-gradient:hover {
		border-color: #86efac !important;
	}
	.xpui-roi-icon-container {
		background: #ffffff !important;
		border: 1px solid #e2e8f0 !important;
		box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.08) !important;
		border-radius: 12px !important;
		padding: 8px !important;
		flex-shrink: 0 !important;
		display: flex !important;
		align-items: center !important;
		justify-content: center !important;
		width: 48px !important;
		height: 48px !important;
		box-sizing: border-box !important;
		animation: xpui-pulse 2s infinite ease-in-out !important;
	}
	@keyframes xpui-pulse {
		0% { transform: scale(1); }
		50% { transform: scale(1.05); }
		100% { transform: scale(1); }
	}
	
	.xpui-table {
		border: 1px solid #e2e8f0 !important;
		border-radius: 12px !important;
		overflow: hidden !important;
		box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
		border-collapse: separate !important;
		border-spacing: 0 !important;
		background: #ffffff !important;
		width: 100%;
	}
	.xpui-table thead th {
		background: #f8fafc !important;
		border-bottom: 1px solid #e2e8f0 !important;
		padding: 12px 15px !important;
		font-weight: 700 !important;
		color: #475569 !important;
		text-transform: uppercase !important;
		font-size: 11px !important;
		letter-spacing: 0.05em !important;
	}
	.xpui-table tbody td {
		padding: 14px 15px !important;
		vertical-align: middle !important;
		border-bottom: 1px solid #f1f5f9 !important;
	}
	.xpui-table tbody tr:last-child td {
		border-bottom: none !important;
	}
	.xpui-table tbody tr:hover td {
		background: #fafafa !important;
	}
	</style>

	<div class="wrap xpui-dashboard-container">
		<h1><?php esc_html_e( 'IntakeFlow Submissions Dashboard', 'xpressui-bridge' ); ?></h1>
		<p class="description" style="margin-bottom: 20px;"><?php esc_html_e( 'Monitor your submission volumes, track administrative efficiency, and manage recent entries.', 'xpressui-bridge' ); ?></p>

		<!-- Grid for Quota and ROI Widgets -->
		<div class="xpui-top-grid">
			<?php
			$enable_sync = get_option( 'xpressui_enable_cloud_sync', '1' ) === '1';
			$quota_exceeded = ! $is_pro && $synced_backups >= $quota_limit;

			$status_label = __( 'Free Plan', 'xpressui-bridge' );
			$status_class = 'xpui-badge-free';
			if ( $is_pro ) {
				$status_label = __( 'Pro Active', 'xpressui-bridge' );
				$status_class = 'xpui-badge-pro';
			} elseif ( ! $enable_sync ) {
				$status_label = __( 'Sync Off', 'xpressui-bridge' );
				$status_class = 'xpui-badge-disabled';
			} elseif ( $quota_exceeded ) {
				$status_label = __( 'Local Only (Quota Exceeded)', 'xpressui-bridge' );
				$status_class = 'xpui-badge-exceeded';
			}

			$bar_bg = $quota_exceeded 
				? 'linear-gradient(90deg, #f87171 0%, #ef4444 100%)' 
				: 'linear-gradient(90deg, #60a5fa 0%, #2563eb 100%)';
			?>

			<!-- Quota Card -->
			<div class="card xpressui-admin-card xpui-dashboard-card xpressui-quota-card" style="flex: 1; min-width: 320px; margin: 0; padding: 22px; border-radius: 16px; background: #ffffff; display: flex; flex-direction: column; justify-content: space-between;">
				<div>
					<h3 style="margin: 0 0 12px; font-size: 13px; font-weight: 750; color: #475569; display: flex; align-items: center; justify-content: space-between;">
						<span><?php esc_html_e( 'Cloud Sync Backup Quota', 'xpressui-bridge' ); ?></span>
						<span class="xpui-badge-status <?php echo esc_attr( $status_class ); ?>">
							<?php echo esc_html( $status_label ); ?>
						</span>
					</h3>
					<p style="font-size: 28px; font-weight: 900; color: #0f172a; margin: 0 0 14px; letter-spacing: -0.02em;">
						<?php echo (int) $synced_backups; ?> <span style="font-size: 15px; font-weight: 500; color: #64748b;">/ <?php echo (int) $quota_limit; ?> <?php esc_html_e( 'cloud backups', 'xpressui-bridge' ); ?></span>
					</p>
					
					<!-- Progress Bar -->
					<div style="width: 100%; height: 10px; background: #e2e8f0; border-radius: 999px; overflow: hidden; margin-bottom: 14px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);">
						<div style="width: <?php echo (int) $quota_percentage; ?>%; height: 100%; background: <?php echo esc_attr( $bar_bg ); ?>; border-radius: 999px; transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);"></div>
					</div>
				</div>
				
				<div style="margin-top: 15px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
					<span style="font-size: 12px; color: #64748b; font-weight: 500;">
						<?php 
						if ( ! $enable_sync ) {
							esc_html_e( 'Cloud sync is disabled. Live forms remain functional.', 'xpressui-bridge' );
						} elseif ( $quota_exceeded ) {
							esc_html_e( 'Cloud sync paused. Submissions continue saving locally.', 'xpressui-bridge' );
						} elseif ( $quota_percentage > 85 ) {
							esc_html_e( 'Approaching limit! Please upgrade soon.', 'xpressui-bridge' );
						} else {
							esc_html_e( 'Resets on the 1st of next month.', 'xpressui-bridge' );
						}
						?>
					</span>
					<?php if ( ! $is_pro ) : ?>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-settings' ) ); ?>" class="xpui-gradient-btn">
							<?php esc_html_e( 'Connect to Upgrade', 'xpressui-bridge' ); ?>
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( $upgrade_url ); ?>" target="_blank" rel="noopener noreferrer" class="xpui-gradient-btn">
							<?php esc_html_e( 'Upgrade Plan', 'xpressui-bridge' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- ROI Card -->
			<div class="card xpressui-admin-card xpui-dashboard-card xpui-roi-gradient xpressui-roi-card" style="flex: 1.2; min-width: 340px; margin: 0; padding: 22px; border-radius: 16px; display: flex; gap: 18px; align-items: flex-start;">
				<div class="xpui-roi-icon-container" style="font-size: 24px;">
					⚡
				</div>
				<div>
					<h3 style="margin: 0 0 6px; font-size: 15px; font-weight: 800; color: #14532d; letter-spacing: -0.01em;">
						<?php esc_html_e( 'IntakeFlow Productivity ROI', 'xpressui-bridge' ); ?>
					</h3>
					<p style="font-size: 13.5px; color: #15803d; margin: 0 0 16px; line-height: 1.5; font-weight: 500;">
						<?php 
						printf(
							/* translators: 1: total submissions, 2: corrections count */
							esc_html__( 'By automating %1$d submissions and %2$d client document correction requests, IntakeFlow has saved you from tedious manual email follow-ups.', 'xpressui-bridge' ),
							(int) $total_submissions,
							(int) $corrections_count
						);
						?>
					</p>
					<div style="display: flex; gap: 15px; align-items: baseline;">
						<span style="font-size: 34px; font-weight: 900; color: #16a34a; letter-spacing: -0.03em; line-height: 1;">
							<?php echo esc_html( (string) $hours_saved ); ?>
						</span>
						<span style="font-size: 12px; font-weight: 750; color: #15803d; text-transform: uppercase; letter-spacing: 0.05em;">
							<?php esc_html_e( 'Hours of admin work saved', 'xpressui-bridge' ); ?>
						</span>
					</div>
				</div>
			</div>
		</div>

		<!-- Row of Metrics Cards -->
		<div class="xpui-stats-row">
			<!-- Total card -->
			<div class="xpui-stat-card">
				<div class="xpui-stat-icon" style="background: #e0f2fe; color: #0284c7;">📥</div>
				<div>
					<p class="xpui-stat-value"><?php echo (int) $total_submissions; ?></p>
					<p class="xpui-stat-label"><?php esc_html_e( 'Total Submissions', 'xpressui-bridge' ); ?></p>
				</div>
			</div>

			<!-- Pending Info card -->
			<div class="xpui-stat-card">
				<div class="xpui-stat-icon" style="background: #fee2e2; color: #dc2626;">⚠️</div>
				<div>
					<p class="xpui-stat-value"><?php echo (int) $pending_info_count; ?></p>
					<p class="xpui-stat-label"><?php esc_html_e( 'Action Required', 'xpressui-bridge' ); ?></p>
				</div>
			</div>

			<!-- In Review card -->
			<div class="xpui-stat-card">
				<div class="xpui-stat-icon" style="background: #fef3c7; color: #d97706;">🔍</div>
				<div>
					<p class="xpui-stat-value"><?php echo (int) $in_review_count; ?></p>
					<p class="xpui-stat-label"><?php esc_html_e( 'In Review', 'xpressui-bridge' ); ?></p>
				</div>
			</div>

			<!-- Done card -->
			<div class="xpui-stat-card">
				<div class="xpui-stat-icon" style="background: #dcfce7; color: #16a34a;">✅</div>
				<div>
					<p class="xpui-stat-value"><?php echo (int) $done_count; ?></p>
					<p class="xpui-stat-label"><?php esc_html_e( 'Completed', 'xpressui-bridge' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Latest 10 Submissions Section -->
		<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 30px;">
			<h2 style="margin: 0 0 15px; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; justify-content: space-between; align-items: center;">
				<span><?php esc_html_e( 'Latest Submissions', 'xpressui-bridge' ); ?></span>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=xpressui_submission' ) ); ?>" class="button" style="border-radius: 8px; font-weight: 600; font-size: 11px; display: inline-flex; align-items: center; justify-content: center; gap: 4px; vertical-align: middle;">
					<?php esc_html_e( 'View All Submissions', 'xpressui-bridge' ); ?>
					<span class="dashicons dashicons-arrow-right-alt2" style="font-size: 14px; width: 14px; height: 14px; display: flex; align-items: center; justify-content: center;"></span>
				</a>
			</h2>

			<?php if ( ! xpressui_is_saas_connected() ) : ?>
				<?php
				$connect_url = xpressui_get_wordpress_connect_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-dashboard' ) );
				?>
				<div style="padding: 40px; text-align: center; color: #64748b;">
					<p style="font-size: 15px; font-weight: 600; margin: 0; color: #1e3a8a;"><?php esc_html_e( 'Submissions for trial forms are sent by email.', 'xpressui-bridge' ); ?></p>
					<p style="font-size: 13px; margin: 8px 0 20px; max-width: 500px; margin-left: auto; margin-right: auto; line-height: 1.5;"><?php esc_html_e( 'To store, display, and manage your submissions in this dashboard, connect your site to the IntakeFlow Console.', 'xpressui-bridge' ); ?></p>
					<a href="<?php echo esc_url( $connect_url ); ?>" class="xpui-gradient-btn" style="padding: 8px 20px !important; font-size: 13px !important;">
						<?php esc_html_e( 'Connect to Console', 'xpressui-bridge' ); ?>
					</a>
				</div>
			<?php elseif ( empty( $latest_posts ) ) : ?>
				<div style="padding: 40px; text-align: center; color: #64748b;">
					<p style="font-size: 15px; font-weight: 600; margin: 0;"><?php esc_html_e( 'No submissions received yet.', 'xpressui-bridge' ); ?></p>
					<p style="font-size: 13px; margin: 5px 0 0;"><?php esc_html_e( 'Embed a workflow on your pages to start collecting client files and details.', 'xpressui-bridge' ); ?></p>
				</div>
			<?php else : ?>
				<table class="xpui-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Submitter', 'xpressui-bridge' ); ?></th>
							<th><?php esc_html_e( 'Workflow', 'xpressui-bridge' ); ?></th>
							<th><?php esc_html_e( 'Date', 'xpressui-bridge' ); ?></th>
							<th><?php esc_html_e( 'Status', 'xpressui-bridge' ); ?></th>
							<th><?php esc_html_e( 'Sync State', 'xpressui-bridge' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $latest_posts as $post ) : ?>
							<?php
							$post_id      = $post->ID;
							$project_slug = get_post_meta( $post_id, '_xpressui_project_slug', true );
							$status       = get_post_meta( $post_id, '_xpressui_submission_status', true ) ?: 'new';
							$sync_status  = get_post_meta( $post_id, '_xpressui_webhook_status', true ) ?: 'not_configured';
							$date         = get_the_date( 'Y-m-d H:i', $post_id );

							$payload_json = get_post_meta( $post_id, '_xpressui_payload_json', true );
							$payload      = json_decode( $payload_json, true );
							
							// Extract submitter email
							$email = '';
							if ( function_exists( 'xpressui_extract_email_from_payload' ) ) {
								$email = xpressui_extract_email_from_payload( $payload );
							}
							if ( empty( $email ) ) {
								$email = __( '(Unknown Client)', 'xpressui-bridge' );
							}

							// Nice workflow title fallback
							$meta = xpressui_get_workflow_manifest_meta( $project_slug );
							$title = ! empty( $meta['projectName'] ) ? $meta['projectName'] : $project_slug;

							// Nice badges styles
							$status_class = 'xpui-sub-new';
							$status_label = __( 'New', 'xpressui-bridge' );
							if ( 'in-review' === $status ) {
								$status_class = 'xpui-sub-review';
								$status_label = __( 'In Review', 'xpressui-bridge' );
							} elseif ( 'pending_info' === $status ) {
								$status_class = 'xpui-sub-pending';
								$status_label = __( 'Action Required', 'xpressui-bridge' );
							} elseif ( 'done' === $status ) {
								$status_class = 'xpui-sub-done';
								$status_label = __( 'Completed', 'xpressui-bridge' );
							}

							$sync_badge = 'background: #cbd5e1; color: #475569;';
							$sync_label = __( 'None', 'xpressui-bridge' );
							if ( 'sent' === $sync_status ) {
								$sync_badge = 'background: #dcfce7; color: #15803d;';
								$sync_label = __( 'Synced', 'xpressui-bridge' );
							} elseif ( 'failed' === $sync_status ) {
								$sync_badge = 'background: #fee2e2; color: #b91c1c;';
								$sync_label = __( 'Sync Failed', 'xpressui-bridge' );
							} elseif ( 'local_only_quota_exceeded' === $sync_status ) {
								$sync_badge = 'background: #fee2e2; color: #b91c1c;';
								$sync_label = __( 'Local Only (Quota)', 'xpressui-bridge' );
							} elseif ( 'local_only' === $sync_status ) {
								$sync_badge = 'background: #f1f5f9; color: #475569;';
								$sync_label = __( 'Local Only', 'xpressui-bridge' );
							}
							?>
							<tr class="xpui-clickable-row" data-href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>" style="cursor: pointer;">
								<td>
									<strong>
										<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>">
											<?php echo esc_html( $email ); ?>
										</a>
									</strong>
								</td>
								<td><code><?php echo esc_html( $project_slug ); ?></code></td>
								<td style="font-size: 12px; color: #64748b;"><?php echo esc_html( $date ); ?></td>
								<td>
									<span class="xpui-sub-badge <?php echo esc_attr( $status_class ); ?>">
										<?php echo esc_html( $status_label ); ?>
									</span>
								</td>
								<td>
									<span class="xpui-sub-badge" style="<?php echo esc_attr( $sync_badge ); ?>">
										<?php echo esc_html( $sync_label ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<script>
				( function () {
					document.querySelectorAll( '.xpui-clickable-row' ).forEach( function ( row ) {
						row.addEventListener( 'click', function ( e ) {
							if ( e.target.closest( 'a' ) ) { return; }
							var href = row.getAttribute( 'data-href' );
							if ( href ) { window.location.href = href; }
						} );
					} );
				} )();
				</script>
			<?php endif; ?>
		</div>

		<!-- Row of Quick Links / Admin controls -->
		<div style="display: flex; gap: 20px; flex-wrap: wrap;">
			<div class="card xpressui-admin-card" style="flex: 1; min-width: 250px; margin: 0; padding: 20px; border-radius: 12px; background: #ffffff;">
				<h3 style="margin-top: 0; font-size: 14px; font-weight: 750; color: #0f172a;">🛠️ <?php esc_html_e( 'Quick Actions', 'xpressui-bridge' ); ?></h3>
				<ul style="margin: 15px 0 0; padding: 0 0 0 15px; line-height: 1.8;">
					<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-bridge' ) ); ?>"><?php esc_html_e( 'Manage Workflow Packages', 'xpressui-bridge' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-bridge&tab=import' ) ); ?>"><?php esc_html_e( 'Import forms from Gravity / CF7', 'xpressui-bridge' ); ?></a></li>
					<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-sync-logs' ) ); ?>"><?php esc_html_e( 'Inspect webhook sync logs', 'xpressui-bridge' ); ?></a></li>
				</ul>
			</div>
			
			<div class="card xpressui-admin-card" style="flex: 1; min-width: 250px; margin: 0; padding: 20px; border-radius: 12px; background: #ffffff;">
				<h3 style="margin-top: 0; font-size: 14px; font-weight: 750; color: #0f172a;">🔌 <?php esc_html_e( 'Integration status', 'xpressui-bridge' ); ?></h3>
				<ul style="margin: 15px 0 0; padding: 0; list-style: none; line-height: 1.8;">
					<li>
						<strong><?php esc_html_e( 'Cloud Sync:', 'xpressui-bridge' ); ?></strong> 
						<?php echo ( $is_pro && $enable_sync ) ? '<span style="color: #16a34a; font-weight: bold;">' . esc_html__( 'Active', 'xpressui-bridge' ) . '</span>' : '<span style="color: #64748b; font-weight: bold;">' . esc_html__( 'Inactive', 'xpressui-bridge' ) . '</span>'; ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'License Tier:', 'xpressui-bridge' ); ?></strong> 
						<?php echo $is_pro ? '<span style="color: #16a34a; font-weight: bold;">' . esc_html__( 'Pro Active', 'xpressui-bridge' ) . '</span>' : '<span style="color: #2563eb; font-weight: bold;">' . esc_html__( 'Free / Trial', 'xpressui-bridge' ) . '</span>'; ?>
					</li>
				</ul>
			</div>
		</div>
	</div>
	<?php
}
