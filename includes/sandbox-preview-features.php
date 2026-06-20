<?php
/**
 * Premium features sandbox simulator panels (Google Drive & OCR).
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle form AJAX submissions for the Sandbox tests.
 */
function xpressui_sandbox_ajax_handlers() {
	// 1. Google Drive Test
	add_action( 'wp_ajax_xpressui_sandbox_test_gdrive', 'xpressui_sandbox_ajax_test_gdrive' );
	
	// 2. OCR OCR Test
	add_action( 'wp_ajax_xpressui_sandbox_test_ocr', 'xpressui_sandbox_ajax_test_ocr' );
}
add_action( 'admin_init', 'xpressui_sandbox_ajax_handlers' );

/**
 * AJAX Google Drive test handler.
 */
function xpressui_sandbox_ajax_test_gdrive() {
	check_ajax_referer( 'xpressui_sandbox_action', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'Forbidden', 'xpressui-bridge' ) ] );
	}

	$folder = isset( $_POST['folder'] ) ? sanitize_text_field( wp_unslash( $_POST['folder'] ) ) : '';
	if ( empty( $folder ) ) {
		wp_send_json_error( [ 'message' => __( 'Please enter a target folder name.', 'xpressui-bridge' ) ] );
	}

	wp_send_json_success( [
		/* translators: %s: target folder name */
		'message' => sprintf( __( 'Connection to Google Drive folder "%s" successful! (Simulation Sandbox mode)', 'xpressui-bridge' ), esc_html( $folder ) ),
	] );
}

/**
 * AJAX OCR test handler.
 */
function xpressui_sandbox_ajax_test_ocr() {
	check_ajax_referer( 'xpressui_sandbox_action', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'Forbidden', 'xpressui-bridge' ) ] );
	}

	wp_send_json_success( [
		'message' => __( 'OCR Scan completed! Extracted: Total $120.00, VAT 20%, Reference: INV-2026-089. Confidence Score: 94%. (Simulation Sandbox mode)', 'xpressui-bridge' ),
	] );
}

/**
 * Renders the Sandbox Features Cards inside the Settings panel.
 */
function xpressui_render_sandbox_features_cards() {
	$is_pro = xpressui_pro_is_license_active();
	$nonce = wp_create_nonce( 'xpressui_sandbox_action' );
	
	// Render styling and script
	?>
	<div class="card xpressui-admin-card" style="margin-top: 20px;">
		<h2><?php esc_html_e( 'Google Drive Integration (Sandbox Preview)', 'xpressui-bridge' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Automatically backup validated B2B dossier attachments into Google Drive folders.', 'xpressui-bridge' ); ?></p>
		
		<div style="margin-top: 15px; max-width: 600px;">
			<table class="form-table" role="presentation" style="margin: 0;">
				<tr>
					<th scope="row" style="width: 200px; padding: 10px 0;"><label><?php esc_html_e( 'Target Folder Path', 'xpressui-bridge' ); ?></label></th>
					<td style="padding: 10px 0;">
						<input type="text" id="xpui-sandbox-gdrive-folder" value="IntakeFlow/Submissions" placeholder="IntakeFlow/Submissions" class="regular-text" />
					</td>
				</tr>
			</table>
			<div style="margin-top: 10px; display: flex; align-items: center; gap: 15px;">
				<button type="button" id="xpui-sandbox-gdrive-btn" class="button button-secondary"><?php esc_html_e( 'Test Drive Connection', 'xpressui-bridge' ); ?></button>
				<span id="xpui-sandbox-gdrive-status" style="font-weight: 500; font-size: 13px;"></span>
			</div>
			<?php if ( ! $is_pro ) : ?>
				<p style="color: #0284c7; background: #e0f2fe; padding: 10px; border-radius: 6px; font-size: 12px; margin-top: 15px;">
					💡 <?php esc_html_e( 'Upgrade to Pro to enable automatic Google Drive upload for live production submissions.', 'xpressui-bridge' ); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>

	<div class="card xpressui-admin-card" style="margin-top: 20px;">
		<h2><?php esc_html_e( 'Gemini OCR Receipt Parsing (Sandbox Preview)', 'xpressui-bridge' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Accelerate manual checkout audits using artificial intelligence to extract receipt metadata.', 'xpressui-bridge' ); ?></p>
		
		<div style="margin-top: 15px; max-width: 600px;">
			<table class="form-table" role="presentation" style="margin: 0;">
				<tr>
					<th scope="row" style="width: 200px; padding: 10px 0;"><label><?php esc_html_e( 'Min Confidence Threshold', 'xpressui-bridge' ); ?></label></th>
					<td style="padding: 10px 0;">
						<input type="number" value="85" min="50" max="100" class="small-text" style="width: 65px;" /> %
					</td>
				</tr>
			</table>
			<div style="margin-top: 10px; display: flex; align-items: center; gap: 15px;">
				<button type="button" id="xpui-sandbox-ocr-btn" class="button button-secondary"><?php esc_html_e( 'Simulate OCR OCR Extraction', 'xpressui-bridge' ); ?></button>
				<span id="xpui-sandbox-ocr-status" style="font-weight: 500; font-size: 13px;"></span>
			</div>
			<?php if ( ! $is_pro ) : ?>
				<p style="color: #0284c7; background: #e0f2fe; padding: 10px; border-radius: 6px; font-size: 12px; margin-top: 15px;">
					💡 <?php esc_html_e( 'Upgrade to Pro to unlock OCR verification on actual receipts uploaded by clients.', 'xpressui-bridge' ); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>

	<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#xpui-sandbox-gdrive-btn').on('click', function() {
			var $btn = $(this);
			var folder = $('#xpui-sandbox-gdrive-folder').val();
			var $status = $('#xpui-sandbox-gdrive-status');
			
			$btn.prop('disabled', true);
			$status.css('color', '#64748b').text('Testing connection...');

			$.post(ajaxurl, {
				action: 'xpressui_sandbox_test_gdrive',
				nonce: '<?php echo esc_js( $nonce ); ?>',
				folder: folder
			}, function(res) {
				$btn.prop('disabled', false);
				if (res.success) {
					$status.css('color', '#16a34a').text(res.data.message);
				} else {
					$status.css('color', '#dc2626').text(res.data.message || 'Error.');
				}
			});
		});

		$('#xpui-sandbox-ocr-btn').on('click', function() {
			var $btn = $(this);
			var $status = $('#xpui-sandbox-ocr-status');
			
			$btn.prop('disabled', true);
			$status.css('color', '#64748b').text('Scanning mock document...');

			$.post(ajaxurl, {
				action: 'xpressui_sandbox_test_ocr',
				nonce: '<?php echo esc_js( $nonce ); ?>'
			}, function(res) {
				$btn.prop('disabled', false);
				if (res.success) {
					$status.css('color', '#16a34a').text(res.data.message);
				} else {
					$status.css('color', '#dc2626').text('OCR scanning failed.');
				}
			});
		});
	});
	</script>
	<?php
}
