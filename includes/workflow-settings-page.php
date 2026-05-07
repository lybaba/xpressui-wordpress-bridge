<?php
/**
 * Workflow Settings admin page — per-workflow configuration.
 * Accessible via the "Settings" link in the Manage Workflows table row actions.
 * Registered as a hidden submenu (null parent) so it doesn't appear in the menu.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'xpressui_register_workflow_settings_page' );

function xpressui_is_pro_workflow_settings_available(): bool {
	return function_exists( 'xpressui_pro_load_workflow_overlay' )
		&& function_exists( 'xpressui_pro_save_workflow_overlay' );
}

function xpressui_register_workflow_settings_page(): void {
	add_submenu_page(
		null,
		__( 'Workflow Settings', 'xpressui-bridge' ),
		__( 'Workflow Settings', 'xpressui-bridge' ),
		'manage_options',
		'xpressui-workflow-settings',
		'xpressui_render_workflow_settings_page'
	);
}

// Inject "Settings" as the first row action in the Manage Workflows table.
add_filter( 'xpressui_workflow_row_actions', 'xpressui_workflow_settings_row_action', 5, 2 );

function xpressui_workflow_settings_row_action( array $actions, string $slug ): array {
	$url = add_query_arg(
		[
			'post_type'     => 'xpressui_submission',
			'page'          => 'xpressui-workflow-settings',
			'xpressui_slug' => $slug,
		],
		admin_url( 'edit.php' )
	);
	array_unshift( $actions, '<a href="' . esc_url( $url ) . '" class="xpressui-primary-action-link"><span>' . esc_html__( 'Settings', 'xpressui-bridge' ) . '</span></a>' );
	return $actions;
}

function xpressui_render_workflow_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'xpressui-bridge' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$slug = isset( $_GET['xpressui_slug'] ) ? sanitize_title( wp_unslash( (string) $_GET['xpressui_slug'] ) ) : '';
	if ( $slug === '' || ! xpressui_is_installed_workflow( $slug ) ) {
		wp_die( esc_html__( 'Unknown workflow.', 'xpressui-bridge' ) );
	}

	$notice_class   = '';
	$notice_message = '';
	$has_pro_settings = xpressui_is_pro_workflow_settings_available();

	if ( isset( $_POST['xpressui_save_workflow_settings'] ) && check_admin_referer( 'xpressui_workflow_settings_' . $slug, 'xpressui_workflow_settings_nonce' ) ) {
		$raw_notify_email = trim( wp_unslash( $_POST['xpressui_notify_email'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$notify_email     = sanitize_email( $raw_notify_email );
		$raw_redirect_url = trim( wp_unslash( $_POST['xpressui_redirect_url'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$redirect_url     = esc_url_raw( $raw_redirect_url );
		$raw_webhook_url  = trim( wp_unslash( $_POST['xpressui_webhook_url'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$webhook_url      = esc_url_raw( $raw_webhook_url );
		$raw_booking_url      = trim( wp_unslash( $_POST['xpressui_booking_url'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$booking_url          = esc_url_raw( $raw_booking_url );
		$booking_button_label = isset( $_POST['xpressui_booking_button_label'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_booking_button_label'] ) ) : '';

		$show_project_title       = ! empty( $_POST['xpressui_show_project_title'] ) ? '1' : '0';
		$show_required_note       = ! empty( $_POST['xpressui_show_required_fields_note'] ) ? '1' : '0';
		$notify_submitter         = ! empty( $_POST['xpressui_notify_submitter'] ) ? '1' : '0';
		$section_label_visibility = sanitize_key( wp_unslash( (string) ( $_POST['xpressui_section_label_visibility'] ?? 'auto' ) ) );
		if ( ! in_array( $section_label_visibility, [ 'auto', 'show', 'hide' ], true ) ) {
			$section_label_visibility = 'auto';
		}

		$notify_submitter_on_submit  = ! empty( $_POST['xpressui_notify_submitter_on_submit'] ) ? '1' : '0';
		$submit_confirmation_message = isset( $_POST['xpressui_submit_confirmation_message'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['xpressui_submit_confirmation_message'] ) ) : '';
		$submit_success_message      = isset( $_POST['xpressui_submit_success_message'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_submit_success_message'] ) ) : '';
		$submit_error_message        = isset( $_POST['xpressui_submit_error_message'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_submit_error_message'] ) ) : '';

		$all_settings = get_option( 'xpressui_project_settings', [] );
		if ( ! is_array( $all_settings ) ) {
			$all_settings = [];
		}
		$all_settings[ $slug ] = [
			'notifyEmail'             => $notify_email,
			'redirectUrl'             => $redirect_url,
			'webhookUrl'              => $webhook_url,
			'bookingUrl'              => $booking_url,
			'bookingButtonLabel'      => $booking_button_label,
			'showProjectTitle'        => $show_project_title,
			'showRequiredFieldsNote'  => $show_required_note,
			'notifySubmitter'         => $notify_submitter,
			'sectionLabelVisibility'  => $section_label_visibility,
			'notifySubmitterOnSubmit' => $notify_submitter_on_submit,
			'submitConfirmationMessage' => $submit_confirmation_message,
			'submitSuccessMessage'    => $submit_success_message,
			'submitErrorMessage'      => $submit_error_message,
		];
		update_option( 'xpressui_project_settings', $all_settings );

		do_action( 'xpressui_workflow_settings_extra_save', $slug );

		if ( $has_pro_settings ) {
			$overlay = xpressui_pro_load_workflow_overlay( $slug );
			if ( ! is_array( $overlay ) ) {
				$overlay = [];
			}

			$success_msg = sanitize_text_field( wp_unslash( (string) ( $_POST['xpressui_overlay_success_message'] ?? '' ) ) );
			$error_msg   = sanitize_text_field( wp_unslash( (string) ( $_POST['xpressui_overlay_error_message'] ?? '' ) ) );

			if ( $success_msg !== '' ) {
				$overlay['success_message'] = $success_msg;
			} else {
				unset( $overlay['success_message'] );
			}
			if ( $error_msg !== '' ) {
				$overlay['error_message'] = $error_msg;
			} else {
				unset( $overlay['error_message'] );
			}

			if ( empty( $overlay ) ) {
				xpressui_pro_delete_workflow_overlay( $slug );
			} else {
				xpressui_pro_save_workflow_overlay( $slug, $overlay );
			}
		}

		$warnings = [];
		if ( $raw_notify_email !== '' && $notify_email === '' ) {
			$warnings[] = __( 'The notification email is not valid.', 'xpressui-bridge' );
		}
		if ( $raw_redirect_url !== '' && $redirect_url === '' ) {
			$warnings[] = __( 'The post-submit redirect URL is not valid.', 'xpressui-bridge' );
		}
		if ( $raw_webhook_url !== '' && $webhook_url === '' ) {
			$warnings[] = __( 'The webhook URL is not valid.', 'xpressui-bridge' );
		}
		if ( $raw_booking_url !== '' && $booking_url === '' ) {
			$warnings[] = __( 'The booking URL is not valid.', 'xpressui-bridge' );
		}
		$notice_class   = empty( $warnings ) ? 'notice-success' : 'notice-error';
		$notice_message = empty( $warnings ) ? __( 'Settings saved.', 'xpressui-bridge' ) : implode( ' ', $warnings );
	}

	$all_settings  = get_option( 'xpressui_project_settings', [] );
	$s             = is_array( $all_settings[ $slug ] ?? null ) ? $all_settings[ $slug ] : [];
	$overlay       = $has_pro_settings ? xpressui_pro_load_workflow_overlay( $slug ) : [];
	$overlay       = is_array( $overlay ) ? $overlay : [];
	$manifest_meta = xpressui_get_workflow_manifest_meta( $slug );
	$project_name  = sanitize_text_field( (string) ( $manifest_meta['projectName'] ?? '' ) );
	$display_name  = $project_name !== '' ? $project_name : $slug;
	$ov_success_message = $has_pro_settings ? (string) ( $overlay['success_message'] ?? '' ) : '';
	$ov_error_message   = $has_pro_settings ? (string) ( $overlay['error_message'] ?? '' ) : '';
	$submit_success_message = (string) ( $s['submitSuccessMessage'] ?? '' );
	$submit_error_message   = (string) ( $s['submitErrorMessage'] ?? '' );
	if ( '' === $submit_success_message ) {
		$submit_success_message = $ov_success_message;
	}
	if ( '' === $submit_error_message ) {
		$submit_error_message = $ov_error_message;
	}
	$back_url = add_query_arg(
		[ 'post_type' => 'xpressui_submission', 'page' => 'xpressui-bridge' ],
		admin_url( 'edit.php' )
	);

	if ( $notice_message ) {
		echo '<div class="notice ' . esc_attr( $notice_class ) . ' is-dismissible"><p>' . esc_html( $notice_message ) . '</p></div>';
	}

	echo '<div class="wrap xpressui-wrap">';
	$_name_badge = ( $project_name !== '' && $project_name !== $slug )
		? ' <span style="font-weight:400;color:#787c82;">/ ' . esc_html( $project_name ) . '</span>'
		: '';
	echo '<h1 class="wp-heading-inline">' . esc_html( $slug ) . $_name_badge . ': Settings</h1>';
	echo ' <a href="' . esc_url( $back_url ) . '" class="page-title-action">← ' . esc_html__( 'Workflows', 'xpressui-bridge' ) . '</a>';
	echo '<hr class="wp-header-end">';

	echo '<style>
.xpressui-admin-card details { margin: 0; }
.xpressui-admin-card summary {
	list-style: none;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	padding-bottom: 14px;
	margin-bottom: 0;
	user-select: none;
}
.xpressui-admin-card summary::-webkit-details-marker { display: none; }
.xpressui-admin-card summary h2 { margin: 0; padding: 0; font-size: 1.3em; }
.xpressui-admin-card summary .xpressui-toggle-icon {
	color: #787c82;
	font-size: 20px;
	line-height: 1;
	flex-shrink: 0;
	transition: transform 0.15s ease;
	display: inline-block;
}
.xpressui-admin-card details:not([open]) summary .xpressui-toggle-icon {
	transform: rotate(-90deg);
}
.xpressui-admin-card:has(details:not([open])) { padding-bottom: 6px; }
.xpressui-settings-sticky {
	position: sticky;
	top: 32px;
	z-index: 100;
	background: #fff;
	border-left: 3px solid #2271b1;
	border-radius: 0 4px 4px 0;
	padding: 7px 14px;
	margin-bottom: 14px;
	box-shadow: 0 2px 10px rgba(34,113,177,.15);
	display: flex;
	align-items: center;
}
.xpressui-settings-sticky .button { margin: 0; }
</style>';

	echo '<form method="post">';
	wp_nonce_field( 'xpressui_workflow_settings_' . $slug, 'xpressui_workflow_settings_nonce' );
	echo '<input type="hidden" name="xpressui_save_workflow_settings" value="1">';

	echo '<div class="xpressui-settings-sticky">';
	submit_button( __( 'Save settings', 'xpressui-bridge' ), 'primary', 'submit', false );
	echo '</div>';

	// -------------------------------------------------------------------------
	// Project Settings
	// -------------------------------------------------------------------------
	echo '<div class="card xpressui-admin-card">';
	echo '<details open><summary><h2>' . esc_html__( 'Project Settings', 'xpressui-bridge' ) . '</h2><span class="xpressui-toggle-icon" aria-hidden="true">▾</span></summary>';
	echo '<table class="form-table"><tbody>';

	echo '<tr><th><label for="xpressui_notify_email">' . esc_html__( 'Notification email', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><input type="email" id="xpressui_notify_email" name="xpressui_notify_email" class="regular-text" placeholder="hello@example.com" value="' . esc_attr( (string) ( $s['notifyEmail'] ?? '' ) ) . '">';
	echo '<p class="description">' . esc_html__( 'Receive an email on each new submission. Leave empty to disable.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '<tr><th><label for="xpressui_redirect_url">' . esc_html__( 'Post-submit redirect', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><input type="url" id="xpressui_redirect_url" name="xpressui_redirect_url" class="regular-text" placeholder="https://" value="' . esc_attr( (string) ( $s['redirectUrl'] ?? '' ) ) . '">';
	echo '<p class="description">' . esc_html__( 'Redirect after a successful submission. Leave empty to show the success message.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '<tr><th><label for="xpressui_webhook_url">' . esc_html__( 'Webhook destination', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><input type="url" id="xpressui_webhook_url" name="xpressui_webhook_url" class="regular-text" placeholder="https://" value="' . esc_attr( (string) ( $s['webhookUrl'] ?? '' ) ) . '">';
	echo '<p class="description">' . esc_html__( 'Receive a POST JSON payload after each submission. Leave empty to disable.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '<tr><th>' . esc_html__( 'Form title', 'xpressui-bridge' ) . '</th>';
	echo '<td><label><input type="checkbox" id="xpressui_show_project_title" name="xpressui_show_project_title" value="1"' . checked( '1', (string) ( $s['showProjectTitle'] ?? '0' ), false ) . '> ';
	echo esc_html__( 'Display the workflow title above the form inside the WordPress page.', 'xpressui-bridge' ) . '</label>';
	echo '<p class="description">' . esc_html__( 'Disabled by default to avoid duplicating the WordPress page title.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '<tr><th>' . esc_html__( 'Required fields note', 'xpressui-bridge' ) . '</th>';
	echo '<td><label><input type="checkbox" id="xpressui_show_required_fields_note" name="xpressui_show_required_fields_note" value="1"' . checked( '1', (string) ( $s['showRequiredFieldsNote'] ?? '0' ), false ) . '> ';
	echo esc_html__( 'Display the "* Required fields" note above the form.', 'xpressui-bridge' ) . '</label>';
	echo '<p class="description">' . esc_html__( 'Disabled by default for a cleaner WordPress page layout.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '<tr><th>' . esc_html__( 'Submitter notifications', 'xpressui-bridge' ) . '</th>';
	echo '<td><label><input type="checkbox" id="xpressui_notify_submitter" name="xpressui_notify_submitter" value="1"' . checked( '1', (string) ( $s['notifySubmitter'] ?? '0' ), false ) . '> ';
	echo esc_html__( 'Send status update emails to the submitter (pending info, done, rejected).', 'xpressui-bridge' ) . '</label>';
	echo '<p class="description">' . esc_html__( 'Requires the submission to include an email field. The operator review note is included.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '<tr><th><label for="xpressui_section_label_visibility">' . esc_html__( 'Section labels', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><select id="xpressui_section_label_visibility" name="xpressui_section_label_visibility" class="regular-text">';
	foreach ( [ 'auto' => __( 'Auto', 'xpressui-bridge' ), 'show' => __( 'Always show', 'xpressui-bridge' ), 'hide' => __( 'Always hide', 'xpressui-bridge' ) ] as $val => $label ) {
		echo '<option value="' . esc_attr( $val ) . '"' . selected( (string) ( $s['sectionLabelVisibility'] ?? 'auto' ), $val, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
	echo '<p class="description">' . esc_html__( 'Auto hides section titles when the workflow only contains one section.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '</tbody></table>';
	echo '</details>';
	echo '</div>';

	echo '<div class="card xpressui-admin-card">';
	echo '<details open><summary><h2>' . esc_html__( 'Submit Feedback', 'xpressui-bridge' ) . '</h2><span class="xpressui-toggle-icon" aria-hidden="true">▾</span></summary>';
	echo '<table class="form-table"><tbody>';

	echo '<tr><th><label for="xpressui_submit_success_message">' . esc_html__( 'Success message', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><input type="text" id="xpressui_submit_success_message" name="xpressui_submit_success_message" class="large-text" value="' . esc_attr( $submit_success_message ) . '">';
	echo '<p class="description">' . esc_html__( 'Optional custom success message shown after a valid submission.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '<tr><th><label for="xpressui_submit_error_message">' . esc_html__( 'Error message', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><input type="text" id="xpressui_submit_error_message" name="xpressui_submit_error_message" class="large-text" value="' . esc_attr( $submit_error_message ) . '">';
	echo '<p class="description">' . esc_html__( 'Optional custom error message shown when the submission fails.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '</tbody></table>';
	echo '</details>';
	echo '</div>';

	// -------------------------------------------------------------------------
	// Booking Link
	// -------------------------------------------------------------------------
	echo '<div class="card xpressui-admin-card">';
	echo '<details open><summary><h2>' . esc_html__( 'Booking Link', 'xpressui-bridge' ) . '</h2><span class="xpressui-toggle-icon" aria-hidden="true">▾</span></summary>';
	echo '<table class="form-table"><tbody>';

	echo '<tr><th><label for="xpressui_booking_url">' . esc_html__( 'Booking URL', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><input type="url" id="xpressui_booking_url" name="xpressui_booking_url" class="regular-text" placeholder="https://calendly.com/..." value="' . esc_attr( (string) ( $s['bookingUrl'] ?? '' ) ) . '">';
	echo '<p class="description">' . esc_html__( 'Show a booking button on the success screen and in the confirmation email. Leave empty to disable.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '<tr><th><label for="xpressui_booking_button_label">' . esc_html__( 'Button label', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><input type="text" id="xpressui_booking_button_label" name="xpressui_booking_button_label" class="regular-text" placeholder="' . esc_attr__( 'Book an appointment', 'xpressui-bridge' ) . '" value="' . esc_attr( (string) ( $s['bookingButtonLabel'] ?? '' ) ) . '">';
	echo '<p class="description">' . esc_html__( 'Label of the booking button. Leave empty to use the default.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '</tbody></table>';
	echo '</details>';
	echo '</div>';

	// -------------------------------------------------------------------------
	// Submission Confirmation Email
	// -------------------------------------------------------------------------
	echo '<div class="card xpressui-admin-card">';
	echo '<details open><summary><h2>' . esc_html__( 'Submission Confirmation Email', 'xpressui-bridge' ) . '</h2><span class="xpressui-toggle-icon" aria-hidden="true">▾</span></summary>';
	echo '<p>' . esc_html__( 'Send an automated confirmation email to the submitter when they submit for the first time. Resubmissions do not trigger this email.', 'xpressui-bridge' ) . '</p>';
	echo '<table class="form-table"><tbody>';

	echo '<tr><th>' . esc_html__( 'Enable', 'xpressui-bridge' ) . '</th>';
	echo '<td><label><input type="checkbox" name="xpressui_notify_submitter_on_submit" value="1"' . checked( '1', (string) ( $s['notifySubmitterOnSubmit'] ?? '0' ), false ) . '> ';
	echo esc_html__( 'Send a confirmation email to the submitter when they submit.', 'xpressui-bridge' ) . '</label>';
	echo '<p class="description">' . esc_html__( 'Requires the submission to include an email field.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '<tr><th><label for="xpressui_submit_confirmation_message">' . esc_html__( 'Custom message', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><textarea id="xpressui_submit_confirmation_message" name="xpressui_submit_confirmation_message" class="regular-text" rows="3">' . esc_textarea( (string) ( $s['submitConfirmationMessage'] ?? '' ) ) . '</textarea>';
	echo '<p class="description">' . esc_html__( 'Optional message shown at the top of the confirmation email. Leave empty to use the default.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '</tbody></table>';
	echo '</details>';
	echo '</div>';

	do_action( 'xpressui_workflow_settings_extra_sections', $slug, $s, $overlay );

	echo '</form>';
	echo '</div>';
}
