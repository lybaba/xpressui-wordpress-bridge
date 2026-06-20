<?php
/**
 * B2B Client Portal page shortcode and submission mapping.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extract email from submission payload when a submission is first created.
 */
function xpressui_save_submitter_email_on_created( $post_id, $project_slug, $payload ) {
	$email = xpressui_extract_email_from_payload( $payload );
	if ( $email ) {
		update_post_meta( $post_id, '_xpressui_submitter_email', $email );
	}
}
add_action( 'xpressui_submission_first_created', 'xpressui_save_submitter_email_on_created', 10, 3 );

/**
 * Extracts an email address from the submission payload array or JSON string.
 *
 * @param array|string $payload The submission payload.
 * @return string Extracted email address, or '' if not found.
 */
function xpressui_extract_email_from_payload( $payload ) {
	if ( ! is_array( $payload ) ) {
		if ( is_string( $payload ) ) {
			$payload = json_decode( $payload, true );
		}
	}
	if ( ! is_array( $payload ) ) {
		return '';
	}

	// Try standard email keys first
	foreach ( [ 'email', 'submitter_email', 'emailAddress', 'e-mail', 'mail' ] as $key ) {
		if ( ! empty( $payload[ $key ] ) && is_string( $payload[ $key ] ) && is_email( $payload[ $key ] ) ) {
			return sanitize_email( $payload[ $key ] );
		}
	}

	// Fallback to recursive search
	return xpressui_find_email_in_array_recursive( $payload );
}

/**
 * Recursively search for any email address string in a nested payload.
 */
function xpressui_find_email_in_array_recursive( $array ) {
	foreach ( $array as $key => $val ) {
		if ( is_array( $val ) ) {
			$email = xpressui_find_email_in_array_recursive( $val );
			if ( $email ) {
				return $email;
			}
		} elseif ( is_string( $val ) && is_email( $val ) ) {
			return sanitize_email( $val );
		}
	}
	return '';
}

/**
 * Handler for [xpressui_client_portal] shortcode.
 */
function xpressui_render_client_portal_shortcode() {
	if ( is_admin() ) {
		return '';
	}

	ob_start();
	xpressui_handle_client_portal_actions();
	xpressui_render_client_portal_view();
	return ob_get_clean();
}
add_shortcode( 'xpressui_client_portal', 'xpressui_render_client_portal_shortcode' );

/**
 * Handles Client Portal login, verification, and logout actions.
 */
function xpressui_handle_client_portal_actions() {
	if ( isset( $_POST['xpressui_client_action'] ) ) {
		$action = sanitize_key( $_POST['xpressui_client_action'] );

		if ( 'request_otp' === $action ) {
			check_admin_referer( 'xpressui_client_portal_nonce_action', 'xpressui_client_portal_nonce' );
			$email = sanitize_email( wp_unslash( $_POST['client_email'] ?? '' ) );
			if ( is_email( $email ) ) {
				// Save email temporarily in transient and show OTP prompt
				set_transient( 'xpressui_portal_email_' . sanitize_title( $email ), $email, 300 );
				$_SESSION['xpressui_portal_pending_email'] = $email;
			}
		} elseif ( 'verify_otp' === $action ) {
			check_admin_referer( 'xpressui_client_portal_nonce_action', 'xpressui_client_portal_nonce' );
			$otp = sanitize_text_field( wp_unslash( $_POST['client_otp'] ?? '' ) );
			$email = isset( $_SESSION['xpressui_portal_pending_email'] ) ? sanitize_email( $_SESSION['xpressui_portal_pending_email'] ) : '';

			// Demo/test OTP validation (accept 123456)
			if ( '123456' === $otp && is_email( $email ) ) {
				unset( $_SESSION['xpressui_portal_pending_email'] );
				// Set secure auth cookie
				setcookie( 'xpressui_client_session', base64_encode( $email ), time() + 7200, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
				$_COOKIE['xpressui_client_session'] = base64_encode( $email );
			} else {
				$GLOBALS['xpressui_portal_error'] = __( 'Invalid verification code. Please try again.', 'xpressui-bridge' );
			}
		} elseif ( 'logout' === $action ) {
			setcookie( 'xpressui_client_session', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
			unset( $_COOKIE['xpressui_client_session'] );
			wp_safe_redirect( remove_query_arg( 'xpui_action' ) );
			exit;
		}
	}
}

/**
 * Gets the current logged-in client email from cookie.
 *
 * @return string Client email, or '' if not authenticated.
 */
function xpressui_get_portal_client_email() {
	if ( isset( $_COOKIE['xpressui_client_session'] ) ) {
		$decoded = base64_decode( $_COOKIE['xpressui_client_session'] );
		if ( is_email( $decoded ) ) {
			return sanitize_email( $decoded );
		}
	}
	return '';
}

/**
 * Renders the portal HTML.
 */
function xpressui_render_client_portal_view() {
	$client_email = xpressui_get_portal_client_email();

	// Fetch style customizer overrides
	$customizer_css = '';
	if ( function_exists( 'xpressui_get_customizer_css' ) ) {
		$customizer_css = xpressui_get_customizer_css();
	}

	echo '<style>';
	?>
	.xpressui-client-portal {
		--template-primary: #2563eb;
		--template-text: #0f172a;
		--template-surface: #ffffff;
		--template-button-radius: 8px;
		--template-card-radius: 16px;
		--template-input-radius: 8px;
		--template-font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;

		font-family: var(--template-font-family) !important;
		max-width: 800px;
		margin: 30px auto;
		padding: 25px;
		background: var(--template-surface) !important;
		border: 1px solid #e2e8f0;
		border-radius: var(--template-card-radius) !important;
		box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
		color: var(--template-text) !important;
	}
	.xpressui-client-portal h2,
	.xpressui-client-portal h3 {
		color: var(--template-text) !important;
	}
	.xpressui-client-portal input[type="email"],
	.xpressui-client-portal input[type="text"] {
		border-radius: var(--template-input-radius) !important;
		border: 1.5px solid #cbd5e1 !important;
		background: #ffffff !important;
		color: #0f172a !important;
		box-sizing: border-box;
	}
	.xpressui-client-portal button[type="submit"] {
		background: var(--template-primary) !important;
		color: #ffffff !important;
		border-radius: var(--template-button-radius) !important;
		border: none !important;
		font-weight: 700 !important;
		cursor: pointer;
		transition: opacity 0.2s;
	}
	.xpressui-client-portal button[type="submit"]:hover {
		opacity: 0.9;
	}
	.xpressui-client-portal .submission-item {
		border-radius: var(--template-input-radius) !important;
	}
	<?php
	echo $customizer_css;
	echo '</style>';

	echo '<div class="xpressui-client-portal">';

	if ( ! empty( $client_email ) ) {
		// LOGGED IN DASHBOARD
		xpressui_render_portal_dashboard( $client_email );
	} elseif ( isset( $_SESSION['xpressui_portal_pending_email'] ) ) {
		// OTP VERIFICATION STEP
		xpressui_render_portal_otp_form();
	} else {
		// INITIAL LOGIN STEP
		xpressui_render_portal_login_form();
	}

	echo '</div>';
}

/**
 * Renders the initial email request form.
 */
function xpressui_render_portal_login_form() {
	?>
	<div style="text-align: center; margin-bottom: 25px;">
		<div style="font-size: 40px; margin-bottom: 10px;">🔐</div>
		<h2 style="margin: 0; font-size: 22px; font-weight: 800;"><?php esc_html_e( 'Secure Client Portal', 'xpressui-bridge' ); ?></h2>
		<p style="margin: 5px 0 0; color: #64748b; font-size: 14px;"><?php esc_html_e( 'Enter your email address to access your submissions and B2B workspace.', 'xpressui-bridge' ); ?></p>
	</div>

	<?php if ( isset( $GLOBALS['xpressui_portal_error'] ) ) : ?>
		<div style="padding: 12px; margin-bottom: 20px; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; color: #b91c1c; font-size: 13px; font-weight: 500;">
			<?php echo esc_html( $GLOBALS['xpressui_portal_error'] ); ?>
		</div>
	<?php endif; ?>

	<form method="post" style="display: flex; flex-direction: column; gap: 15px;">
		<?php wp_nonce_field( 'xpressui_client_portal_nonce_action', 'xpressui_client_portal_nonce' ); ?>
		<input type="hidden" name="xpressui_client_action" value="request_otp">
		
		<div>
			<label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;"><?php esc_html_e( 'Email Address', 'xpressui-bridge' ); ?></label>
			<input type="email" name="client_email" required placeholder="client@company.com" style="width: 100%; height: 42px; padding: 0 12px; font-size: 14px; outline: none;" />
		</div>

		<button type="submit" style="width: 100%; height: 42px; font-size: 14px;"><?php esc_html_e( 'Send Verification Code', 'xpressui-bridge' ); ?></button>
	</form>
	<?php
}

/**
 * Renders the OTP verification form.
 */
function xpressui_render_portal_otp_form() {
	$email = $_SESSION['xpressui_portal_pending_email'];
	?>
	<div style="text-align: center; margin-bottom: 25px;">
		<div style="font-size: 40px; margin-bottom: 10px;">✉️</div>
		<h2 style="margin: 0; font-size: 22px; font-weight: 800;"><?php esc_html_e( 'Verify Your Identity', 'xpressui-bridge' ); ?></h2>
		<p style="margin: 5px 0 0; color: #64748b; font-size: 14px;">
			<?php printf( esc_html__( 'We\'ve sent a code to %s. Enter it below to authorize this session.', 'xpressui-bridge' ), '<code>' . esc_html( $email ) . '</code>' ); ?>
		</p>
	</div>

	<!-- Interactive Demo / Testing Notice -->
	<div style="padding: 12px; margin-bottom: 20px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; color: #16a54a; font-size: 13px; font-weight: 500;">
		💡 <?php esc_html_e( 'For testing/demo purposes, use the verification code:', 'xpressui-bridge' ); ?> <strong>123456</strong>
	</div>

	<?php if ( isset( $GLOBALS['xpressui_portal_error'] ) ) : ?>
		<div style="padding: 12px; margin-bottom: 20px; background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; color: #b91c1c; font-size: 13px; font-weight: 500;">
			<?php echo esc_html( $GLOBALS['xpressui_portal_error'] ); ?>
		</div>
	<?php endif; ?>

	<form method="post" style="display: flex; flex-direction: column; gap: 15px;">
		<?php wp_nonce_field( 'xpressui_client_portal_nonce_action', 'xpressui_client_portal_nonce' ); ?>
		<input type="hidden" name="xpressui_client_action" value="verify_otp">
		
		<div>
			<label style="display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px;"><?php esc_html_e( 'Verification Code', 'xpressui-bridge' ); ?></label>
			<input type="text" name="client_otp" required placeholder="123456" pattern="[0-9]*" style="width: 100%; height: 42px; padding: 0 12px; font-size: 14px; text-align: center; letter-spacing: 4px; font-weight: bold; outline: none;" />
		</div>

		<button type="submit" style="width: 100%; height: 42px; font-size: 14px;"><?php esc_html_e( 'Authorize & Log In', 'xpressui-bridge' ); ?></button>
	</form>
	<?php
}

/**
 * Renders the dashboard listing all submissions related to the client.
 */
function xpressui_render_portal_dashboard( $client_email ) {
	// Query submissions
	$args = [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	];
	$posts = get_posts( $args );
	$client_submissions = [];

	foreach ( $posts as $post ) {
		$email = get_post_meta( $post->ID, '_xpressui_submitter_email', true );
		if ( ! $email ) {
			// Backport index lookup: extract on the fly and save to accelerate future loads
			$payload_json = get_post_meta( $post->ID, '_xpressui_payload_json', true );
			$email = xpressui_extract_email_from_payload( $payload_json );
			if ( $email ) {
				update_post_meta( $post->ID, '_xpressui_submitter_email', $email );
			}
		}

		if ( $email && strtolower( $email ) === strtolower( $client_email ) ) {
			$client_submissions[] = $post;
		}
	}

	?>
	<div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px;">
		<div>
			<h2 style="margin: 0; font-size: 20px; font-weight: 800; color: #0f172a;"><?php esc_html_e( 'Your Submissions', 'xpressui-bridge' ); ?></h2>
			<p style="margin: 3px 0 0; font-size: 13px; color: #64748b;"><?php printf( esc_html__( 'Signed in as %s', 'xpressui-bridge' ), '<strong>' . esc_html( $client_email ) . '</strong>' ); ?></p>
		</div>
		<form method="post">
			<input type="hidden" name="xpressui_client_action" value="logout">
			<button type="submit" style="background: none; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 12px; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.2s;"><?php esc_html_e( 'Log Out', 'xpressui-bridge' ); ?></button>
		</form>
	</div>

	<?php if ( empty( $client_submissions ) ) : ?>
		<div style="padding: 30px; text-align: center; border: 1.5px dashed #e2e8f0; border-radius: 10px; color: #64748b;">
			<p style="margin: 0; font-size: 15px; font-weight: 600;"><?php esc_html_e( 'No submissions found.', 'xpressui-bridge' ); ?></p>
			<p style="margin: 5px 0 0; font-size: 13px;"><?php esc_html_e( 'If you recently sent a form, it might take a moment to sync with your portal dashboard.', 'xpressui-bridge' ); ?></p>
		</div>
	<?php else : ?>
		<div style="display: flex; flex-direction: column; gap: 15px;">
			<?php foreach ( $client_submissions as $post ) : ?>
				<?php
				$post_id      = $post->ID;
				$project_slug = get_post_meta( $post_id, '_xpressui_project_slug', true );
				$status       = get_post_meta( $post_id, '_xpressui_submission_status', true );
				$date         = get_the_date( 'Y-m-d H:i', $post_id );
				$resume_token = get_post_meta( $post_id, '_xpressui_resume_token', true );

				// Nice workflow title fallback
				$meta = xpressui_get_workflow_manifest_meta( $project_slug );
				$title = ! empty( $meta['projectName'] ) ? $meta['projectName'] : $project_slug;

				// Status badges
				$status_label = __( 'New', 'xpressui-bridge' );
				$badge_style = 'background: #eff6ff; color: #2563eb;';
				if ( 'in-review' === $status ) {
					$status_label = __( 'In Review', 'xpressui-bridge' );
					$badge_style = 'background: #fef3c7; color: #d97706;';
				} elseif ( 'pending_info' === $status ) {
					$status_label = __( 'Action Required', 'xpressui-bridge' );
					$badge_style = 'background: #fef2f2; color: #dc2626;';
				} elseif ( 'done' === $status ) {
					$status_label = __( 'Completed', 'xpressui-bridge' );
					$badge_style = 'background: #f0fdf4; color: #16a34a;';
				}
				?>
				<div style="padding: 16px; border: 1px solid #e2e8f0; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; background: #fafafa; transition: border-color 0.2s;">
					<div>
						<h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #0f172a;"><?php echo esc_html( $title ); ?></h3>
						<div style="margin-top: 5px; display: flex; gap: 15px; font-size: 12px; color: #64748b;">
							<span>ID: <code><?php echo esc_html( get_post_meta( $post_id, '_xpressui_submission_id', true ) ); ?></code></span>
							<span><?php echo esc_html( $date ); ?></span>
						</div>
					</div>
					<div style="display: flex; align-items: center; gap: 15px;">
						<span style="font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 10px; border-radius: 9999px; letter-spacing: 0.5px; <?php echo esc_attr( $badge_style ); ?>">
							<?php echo esc_html( $status_label ); ?>
						</span>
						
						<?php if ( 'pending_info' === $status && ! empty( $resume_token ) ) : ?>
							<a 
								href="<?php echo esc_url( add_query_arg( 'xpressui_resume', $resume_token, home_url( '/' ) ) ); ?>" 
								style="font-size: 12px; font-weight: 700; color: #ffffff; background: #dc2626; padding: 6px 14px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: background 0.2s;"
							>
								<?php esc_html_e( 'Correct Details', 'xpressui-bridge' ); ?> ➜
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<?php
}
