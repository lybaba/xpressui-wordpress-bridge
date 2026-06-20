<?php
/**
 * WordPress dashboard widget for displaying Quota usage and ROI simulator.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the dashboard widget.
 */
function xpressui_register_dashboard_widget() {
	wp_add_dashboard_widget(
		'xpressui_dashboard_widget',
		__( 'IntakeFlow — ROI & Usage Overview', 'xpressui-bridge' ),
		'xpressui_render_dashboard_widget_content'
	);
}
add_action( 'wp_dashboard_setup', 'xpressui_register_dashboard_widget' );

/**
 * Render the dashboard widget content.
 */
function xpressui_render_dashboard_widget_content() {
	// Query local submissions count
	$args = [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	];
	$local_subs = get_posts( $args );
	$sub_count  = is_array( $local_subs ) ? count( $local_subs ) : 0;
	$quota_limit = 100; // Simulated Trial Limit
	$percentage  = min( 100, round( ( $sub_count / $quota_limit ) * 100 ) );

	// Calculate ROI: 18 minutes saved per submission on average
	$hours_saved = round( ( $sub_count * 18 ) / 60, 1 );

	// Fetch console details
	$conn = xpressui_get_console_connection();
	$has_token = ! empty( $conn['apiToken'] );
	$billing_url = 'https://intakeflow.dev/console/billing'; // Fallback redirect

	?>
	<div class="xpressui-db-widget" style="font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif; padding: 5px 0;">
		<!-- Interactive Quota Gauge -->
		<div style="margin-bottom: 20px;">
			<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
				<span style="font-size: 13px; font-weight: 600; color: #475569;"><?php esc_html_e( 'Trial Submission Volume', 'xpressui-bridge' ); ?></span>
				<span style="font-size: 13px; font-weight: 700; color: #0f172a;"><?php echo esc_html( "$sub_count / $quota_limit" ); ?></span>
			</div>
			
			<!-- Elegant progress bar -->
			<div style="width: 100%; height: 10px; background: #e2e8f0; border-radius: 9999px; overflow: hidden; position: relative;">
				<div style="width: <?php echo (int) $percentage; ?>%; height: 100%; background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%); border-radius: 9999px; transition: width 0.3s;"></div>
			</div>
		</div>

		<!-- ROI simulation -->
		<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; margin-bottom: 20px;">
			<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
				<span style="font-size: 16px;">⏱️</span>
				<strong style="font-size: 13px; color: #0f172a;"><?php esc_html_e( 'Administrative Time Saved', 'xpressui-bridge' ); ?></strong>
			</div>
			<p style="margin: 0; font-size: 12px; color: #64748b; line-height: 1.5;">
				<?php
				printf(
					/* translators: 1: Submissions count, 2: Hours saved count */
					esc_html__( 'IntakeFlow has automated %1$d submissions and follow-ups this month, saving you approximately %2$g hours of manual administrative work.', 'xpressui-bridge' ),
					(int) $sub_count,
					(float) $hours_saved
				);
				?>
			</p>
		</div>

		<!-- Actions -->
		<div style="display: flex; gap: 10px; align-items: center;">
			<a href="<?php echo esc_url( $billing_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary" style="background: #2563eb; border-color: #2563eb; font-weight: 700; text-shadow: none; box-shadow: none; border-radius: 6px;">
				🚀 <?php esc_html_e( 'Upgrade Plan', 'xpressui-bridge' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-settings' ) ); ?>" class="button" style="border-radius: 6px;">
				⚙️ <?php esc_html_e( 'Manage Settings', 'xpressui-bridge' ); ?>
			</a>
		</div>
	</div>
	<?php
}
