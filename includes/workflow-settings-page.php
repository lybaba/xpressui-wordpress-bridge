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
add_action( 'admin_enqueue_scripts', 'xpressui_workflow_settings_enqueue_scripts' );

function xpressui_is_pro_workflow_settings_available(): bool {
	return function_exists( 'xpressui_pro_load_workflow_overlay' )
		&& function_exists( 'xpressui_pro_save_workflow_overlay' )
		&& function_exists( 'xpressui_pro_normalize_additional_file_slots' );
}

function xpressui_workflow_settings_enqueue_scripts(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ( $_GET['page'] ?? '' ) !== 'xpressui-workflow-settings' ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script(
		'media-editor',
		'(function(){
			function openConfirmationFilePicker(slotIndex) {
				var frame = wp.media({ title: "Select reference file", button: { text: "Use this file" }, multiple: false });
				frame.on("select", function() {
					var att = frame.state().get("selection").first().toJSON();
					var input = document.getElementById("xpressui_confirmation_slot_file_id_" + slotIndex);
					var row = document.getElementById("xpressui_confirmation_slot_row_" + slotIndex);
					var p = document.getElementById("xpressui_confirmation_slot_preview_" + slotIndex);
					if (!input || !row || !p) { return; }
					input.value = att.id;
					p.textContent = att.filename || att.title || att.url;
					row.style.display = "";
				});
				frame.open();
			}
			document.addEventListener("DOMContentLoaded", function() {
				document.querySelectorAll("[data-xpressui-confirmation-slot-btn]").forEach(function(btn) {
					btn.addEventListener("click", function() {
						openConfirmationFilePicker(btn.getAttribute("data-slot-index"));
					});
				});
				document.querySelectorAll("[data-xpressui-confirmation-slot-remove]").forEach(function(btn) {
					btn.addEventListener("click", function() {
						var slotIndex = btn.getAttribute("data-slot-index");
						var input = document.getElementById("xpressui_confirmation_slot_file_id_" + slotIndex);
						var row = document.getElementById("xpressui_confirmation_slot_row_" + slotIndex);
						var preview = document.getElementById("xpressui_confirmation_slot_preview_" + slotIndex);
						if (input) input.value = "0";
						if (preview) preview.textContent = "";
						if (row) row.style.display = "none";
					});
				});
			});
		})()'
	);
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
		$raw_booking_url  = trim( wp_unslash( $_POST['xpressui_booking_url'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$booking_url      = esc_url_raw( $raw_booking_url );
		$raw_resume_url   = trim( wp_unslash( $_POST['xpressui_resume_url'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$resume_url       = esc_url_raw( $raw_resume_url );

		$show_project_title       = ! empty( $_POST['xpressui_show_project_title'] ) ? '1' : '0';
		$show_required_note       = ! empty( $_POST['xpressui_show_required_fields_note'] ) ? '1' : '0';
		$notify_submitter         = ! empty( $_POST['xpressui_notify_submitter'] ) ? '1' : '0';
		$section_label_visibility = sanitize_key( wp_unslash( (string) ( $_POST['xpressui_section_label_visibility'] ?? 'auto' ) ) );
		if ( ! in_array( $section_label_visibility, [ 'auto', 'show', 'hide' ], true ) ) {
			$section_label_visibility = 'auto';
		}

		$resubmit_btn_label = isset( $_POST['xpressui_resubmit_btn_label'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_resubmit_btn_label'] ) ) : '';

		$existing_settings = get_option( 'xpressui_project_settings', [] );
		$existing_row      = is_array( $existing_settings[ $slug ] ?? null ) ? $existing_settings[ $slug ] : [];
		$afile_label       = $has_pro_settings
			? (string) ( $existing_row['additionalFileLabel'] ?? '' )
			: ( isset( $_POST['xpressui_additional_file_label'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_additional_file_label'] ) ) : '' );
		$done_afile_label  = $has_pro_settings
			? (string) ( $existing_row['doneAdditionalFileLabel'] ?? '' )
			: ( isset( $_POST['xpressui_done_additional_file_label'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_done_additional_file_label'] ) ) : '' );

		$notify_submitter_on_submit     = ! empty( $_POST['xpressui_notify_submitter_on_submit'] ) ? '1' : '0';
		$submit_confirmation_message    = isset( $_POST['xpressui_submit_confirmation_message'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['xpressui_submit_confirmation_message'] ) ) : '';
		$max_confirmation_slots         = $has_pro_settings ? 5 : 1;
		$raw_confirmation_slot_labels   = isset( $_POST['xpressui_submit_confirmation_slot_labels'] ) && is_array( $_POST['xpressui_submit_confirmation_slot_labels'] )
			? wp_unslash( $_POST['xpressui_submit_confirmation_slot_labels'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			: [];
		$raw_confirmation_slot_file_ids = isset( $_POST['xpressui_submit_confirmation_slot_file_ids'] ) && is_array( $_POST['xpressui_submit_confirmation_slot_file_ids'] )
			? wp_unslash( $_POST['xpressui_submit_confirmation_slot_file_ids'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			: [];
		$raw_confirmation_slots         = [];
		for ( $slot_index = 0; $slot_index < $max_confirmation_slots; $slot_index++ ) {
			$raw_confirmation_slots[] = [
				'label'  => sanitize_text_field( (string) ( $raw_confirmation_slot_labels[ $slot_index ] ?? '' ) ),
				'fileId' => absint( $raw_confirmation_slot_file_ids[ $slot_index ] ?? 0 ),
			];
		}
		$submit_confirmation_slots      = xpressui_sanitize_submit_confirmation_slots( $raw_confirmation_slots, $max_confirmation_slots );
		$submit_confirmation_file_id    = absint( $submit_confirmation_slots[0]['fileId'] ?? 0 );
		$submit_confirmation_section_label  = isset( $_POST['xpressui_submit_confirmation_section_label'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_submit_confirmation_section_label'] ) ) : '';
		$pending_info_documents_section_label = isset( $_POST['xpressui_pending_info_documents_section_label'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_pending_info_documents_section_label'] ) ) : '';
		$done_documents_section_label       = isset( $_POST['xpressui_done_documents_section_label'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_done_documents_section_label'] ) ) : '';
		$submit_success_message         = isset( $_POST['xpressui_submit_success_message'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_submit_success_message'] ) ) : '';
		$submit_error_message           = isset( $_POST['xpressui_submit_error_message'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_submit_error_message'] ) ) : '';

		$all_settings = get_option( 'xpressui_project_settings', [] );
		if ( ! is_array( $all_settings ) ) {
			$all_settings = [];
		}
		$all_settings[ $slug ] = [
			'notifyEmail'                  => $notify_email,
			'redirectUrl'                  => $redirect_url,
			'webhookUrl'                   => $webhook_url,
			'bookingUrl'                   => $booking_url,
			'resumeUrl'                    => $resume_url,
			'resubmitButtonLabel'          => $resubmit_btn_label,
			'showProjectTitle'             => $show_project_title,
			'showRequiredFieldsNote'       => $show_required_note,
			'notifySubmitter'              => $notify_submitter,
			'sectionLabelVisibility'       => $section_label_visibility,
			'additionalFileLabel'          => $afile_label,
			'doneAdditionalFileLabel'      => $done_afile_label,
			'notifySubmitterOnSubmit'      => $notify_submitter_on_submit,
			'submitConfirmationMessage'          => $submit_confirmation_message,
			'submitConfirmationSectionLabel'     => $submit_confirmation_section_label,
			'pendingInfoDocumentsSectionLabel'   => $pending_info_documents_section_label,
			'doneDocumentsSectionLabel'          => $done_documents_section_label,
			'submitConfirmationSlots'        => $submit_confirmation_slots,
			'submitConfirmationFileId'       => $submit_confirmation_file_id > 0 ? $submit_confirmation_file_id : 0,
			'submitSuccessMessage'           => $submit_success_message,
			'submitErrorMessage'           => $submit_error_message,
		];
		update_option( 'xpressui_project_settings', $all_settings );

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

			$raw_slot_labels = isset( $_POST['xpressui_overlay_additional_file_slots'] ) && is_array( $_POST['xpressui_overlay_additional_file_slots'] )
				? wp_unslash( $_POST['xpressui_overlay_additional_file_slots' ] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				: [];
			$additional_slots = xpressui_pro_normalize_additional_file_slots(
				array_map(
					static function ( $slot_label ) {
						return [ 'label' => sanitize_text_field( (string) $slot_label ) ];
					},
					array_values( $raw_slot_labels )
				)
			);

			if ( ! empty( $additional_slots ) ) {
				$overlay['additional_file_slots'] = $additional_slots;
			} else {
				unset( $overlay['additional_file_slots'] );
			}

			$raw_done_slot_labels = isset( $_POST['xpressui_overlay_done_additional_file_slots'] ) && is_array( $_POST['xpressui_overlay_done_additional_file_slots'] )
				? wp_unslash( $_POST['xpressui_overlay_done_additional_file_slots' ] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				: [];
			$done_additional_slots = xpressui_pro_normalize_additional_file_slots(
				array_map(
					static function ( $slot_label ) {
						return [ 'label' => sanitize_text_field( (string) $slot_label ) ];
					},
					array_values( $raw_done_slot_labels )
				)
			);

			if ( ! empty( $done_additional_slots ) ) {
				$overlay['done_additional_file_slots'] = $done_additional_slots;
			} else {
				unset( $overlay['done_additional_file_slots'] );
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
	$ov_pending_info_slots = $has_pro_settings
		? xpressui_pro_normalize_additional_file_slots( $overlay['additional_file_slots'] ?? [] )
		: [];
	$ov_done_slots = $has_pro_settings
		? xpressui_pro_normalize_additional_file_slots( $overlay['done_additional_file_slots'] ?? ( $overlay['additional_file_slots'] ?? [] ) )
		: [];

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

	echo '<tr><th><label for="xpressui_booking_url">' . esc_html__( 'Booking link', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><input type="url" id="xpressui_booking_url" name="xpressui_booking_url" class="regular-text" placeholder="https://calendly.com/..." value="' . esc_attr( (string) ( $s['bookingUrl'] ?? '' ) ) . '">';
	echo '<p class="description">' . esc_html__( 'Show a "Book an appointment" button on the success screen. Leave empty to disable.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '<tr><th><label for="xpressui_resume_url">' . esc_html__( 'Resubmission page URL', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><input type="url" id="xpressui_resume_url" name="xpressui_resume_url" class="regular-text" placeholder="https://example.com/apply/" value="' . esc_attr( (string) ( $s['resumeUrl'] ?? '' ) ) . '">';
	echo '<p class="description">' . esc_html__( 'URL of the page where this form is embedded. Used to generate the resubmission link in pending info emails. Leave empty to auto-detect.', 'xpressui-bridge' ) . '</p></td></tr>';

	echo '<tr><th><label for="xpressui_resubmit_btn_label">' . esc_html__( 'Resubmit button label', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><input type="text" id="xpressui_resubmit_btn_label" name="xpressui_resubmit_btn_label" class="regular-text" placeholder="' . esc_attr__( 'Correct and resubmit', 'xpressui-bridge' ) . '" value="' . esc_attr( (string) ( $s['resubmitButtonLabel'] ?? '' ) ) . '">';
	echo '<p class="description">' . esc_html__( 'CTA button text in pending info emails. Leave empty to use the default.', 'xpressui-bridge' ) . '</p></td></tr>';

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
	// Submission Confirmation Email
	// -------------------------------------------------------------------------
	$max_confirmation_slots = $has_pro_settings ? 5 : 1;
	$stored_confirmation_slots = xpressui_get_submit_confirmation_slots( $slug );
	$confirmation_slots = [];
	for ( $slot_index = 0; $slot_index < $max_confirmation_slots; $slot_index++ ) {
		$slot = is_array( $stored_confirmation_slots[ $slot_index ] ?? null ) ? $stored_confirmation_slots[ $slot_index ] : [];
		$file_id = absint( $slot['fileId'] ?? 0 );
		$confirmation_slots[] = [
			'label'    => (string) ( $slot['label'] ?? '' ),
			'fileId'   => $file_id,
			'fileName' => $file_id > 0 ? xpressui_get_attachment_display_name( $file_id ) : '',
		];
	}

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

	echo '<tr><th><label for="xpressui_submit_confirmation_section_label">' . esc_html__( 'Documents section label', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><input type="text" id="xpressui_submit_confirmation_section_label" name="xpressui_submit_confirmation_section_label" class="regular-text" placeholder="' . esc_attr__( 'Documents to download', 'xpressui-bridge' ) . '" value="' . esc_attr( (string) ( $s['submitConfirmationSectionLabel'] ?? '' ) ) . '">';
	echo '<p class="description">' . esc_html__( 'Heading shown above the attached files in the confirmation email. Leave empty to use the default.', 'xpressui-bridge' ) . '</p></td></tr>';

	foreach ( $confirmation_slots as $slot_index => $confirmation_slot ) {
		$slot_number = $slot_index + 1;
		echo '<tr><th><label for="xpressui_submit_confirmation_slot_label_' . esc_attr( (string) $slot_number ) . '">' . esc_html( sprintf( __( 'Slot %d label', 'xpressui-bridge' ), $slot_number ) ) . '</label></th>';
		echo '<td>';
		echo '<input type="text" id="xpressui_submit_confirmation_slot_label_' . esc_attr( (string) $slot_number ) . '" name="xpressui_submit_confirmation_slot_labels[' . esc_attr( (string) $slot_index ) . ']" class="regular-text" placeholder="' . esc_attr( 1 === $slot_number ? __( 'e.g. Welcome guide', 'xpressui-bridge' ) : sprintf( __( 'e.g. Supporting document %d', 'xpressui-bridge' ), $slot_number ) ) . '" value="' . esc_attr( (string) $confirmation_slot['label'] ) . '">';
		echo '<input type="hidden" id="xpressui_confirmation_slot_file_id_' . esc_attr( (string) $slot_index ) . '" name="xpressui_submit_confirmation_slot_file_ids[' . esc_attr( (string) $slot_index ) . ']" value="' . esc_attr( (string) $confirmation_slot['fileId'] ) . '">';
		echo '<div id="xpressui_confirmation_slot_row_' . esc_attr( (string) $slot_index ) . '"' . ( (int) $confirmation_slot['fileId'] > 0 ? ' style="margin-top:8px;"' : ' style="display:none;margin-top:8px;"' ) . '>';
		echo '<span id="xpressui_confirmation_slot_preview_' . esc_attr( (string) $slot_index ) . '">' . esc_html( (string) $confirmation_slot['fileName'] ) . '</span> ';
		echo '<button type="button" class="button button-small" data-xpressui-confirmation-slot-remove="1" data-slot-index="' . esc_attr( (string) $slot_index ) . '">' . esc_html__( 'Remove', 'xpressui-bridge' ) . '</button>';
		echo '</div>';
		echo '<p style="margin:8px 0 0;"><button type="button" class="button" data-xpressui-confirmation-slot-btn="1" data-slot-index="' . esc_attr( (string) $slot_index ) . '">' . esc_html__( 'Attach file', 'xpressui-bridge' ) . '</button></p>';
		if ( 0 === $slot_index ) {
			echo '<p class="description">' . esc_html__( 'Labels are shown in the confirmation email. Attached files are sent to every submitter of this workflow.', 'xpressui-bridge' ) . '</p>';
		}
		echo '</td></tr>';
	}

	echo '</tbody></table>';
	echo '</details>';
	echo '</div>';

	if ( $has_pro_settings ) {
		$max_slots = 5;
		echo '<div class="card xpressui-admin-card">';
		echo '<details open><summary><h2>' . esc_html__( 'Pending Info Document Slots', 'xpressui-wordpress-bridge-pro' ) . '</h2><span class="xpressui-toggle-icon" aria-hidden="true">▾</span></summary>';
		echo '<p>' . esc_html__( 'Used for Pending info requests.', 'xpressui-wordpress-bridge-pro' ) . '</p>';
		echo '<table class="form-table"><tbody>';
		echo '<tr><th><label for="xpressui_pending_info_documents_section_label">' . esc_html__( 'Documents section label', 'xpressui-bridge' ) . '</label></th>';
		echo '<td><input type="text" id="xpressui_pending_info_documents_section_label" name="xpressui_pending_info_documents_section_label" class="regular-text" placeholder="' . esc_attr__( 'Documents to download', 'xpressui-bridge' ) . '" value="' . esc_attr( (string) ( $s['pendingInfoDocumentsSectionLabel'] ?? '' ) ) . '">';
		echo '<p class="description">' . esc_html__( 'Heading shown above the attached files in the pending info email. Leave empty to use the default.', 'xpressui-bridge' ) . '</p></td></tr>';
		for ( $i = 0; $i < $max_slots; $i++ ) {
			$slot_number = $i + 1;
			$slot_value  = isset( $ov_pending_info_slots[ $i ]['label'] ) ? (string) $ov_pending_info_slots[ $i ]['label'] : '';
			$placeholder = 1 === $slot_number
				? __( 'e.g. Signed contract', 'xpressui-wordpress-bridge-pro' )
				: sprintf( __( 'e.g. Supporting document %d', 'xpressui-wordpress-bridge-pro' ), $slot_number );

			echo '<tr><th><label for="xpressui_overlay_additional_file_slots_' . esc_attr( (string) $slot_number ) . '">' . esc_html( sprintf( __( 'Slot %d label', 'xpressui-wordpress-bridge-pro' ), $slot_number ) ) . '</label></th>';
			echo '<td><input type="text" id="xpressui_overlay_additional_file_slots_' . esc_attr( (string) $slot_number ) . '" name="xpressui_overlay_additional_file_slots[' . esc_attr( (string) $i ) . ']" class="regular-text" value="' . esc_attr( $slot_value ) . '" placeholder="' . esc_attr( $placeholder ) . '"></td></tr>';
		}
		echo '</tbody></table>';
		echo '</details>';
		echo '</div>';

		echo '<div class="card xpressui-admin-card">';
		echo '<details open><summary><h2>' . esc_html__( 'Done Informational File Slots', 'xpressui-wordpress-bridge-pro' ) . '</h2><span class="xpressui-toggle-icon" aria-hidden="true">▾</span></summary>';
		echo '<p>' . esc_html__( 'Used for Done informational files.', 'xpressui-wordpress-bridge-pro' ) . '</p>';
		echo '<table class="form-table"><tbody>';
		echo '<tr><th><label for="xpressui_done_documents_section_label">' . esc_html__( 'Documents section label', 'xpressui-bridge' ) . '</label></th>';
		echo '<td><input type="text" id="xpressui_done_documents_section_label" name="xpressui_done_documents_section_label" class="regular-text" placeholder="' . esc_attr__( 'Documents to download', 'xpressui-bridge' ) . '" value="' . esc_attr( (string) ( $s['doneDocumentsSectionLabel'] ?? '' ) ) . '">';
		echo '<p class="description">' . esc_html__( 'Heading shown above the attached files in the done email. Leave empty to use the default.', 'xpressui-bridge' ) . '</p></td></tr>';
		for ( $i = 0; $i < $max_slots; $i++ ) {
			$slot_number = $i + 1;
			$slot_value  = isset( $ov_done_slots[ $i ]['label'] ) ? (string) $ov_done_slots[ $i ]['label'] : '';
			$placeholder = 1 === $slot_number
				? __( 'e.g. Welcome pack', 'xpressui-wordpress-bridge-pro' )
				: sprintf( __( 'e.g. Done document %d', 'xpressui-wordpress-bridge-pro' ), $slot_number );

			echo '<tr><th><label for="xpressui_overlay_done_additional_file_slots_' . esc_attr( (string) $slot_number ) . '">' . esc_html( sprintf( __( 'Slot %d label', 'xpressui-wordpress-bridge-pro' ), $slot_number ) ) . '</label></th>';
			echo '<td><input type="text" id="xpressui_overlay_done_additional_file_slots_' . esc_attr( (string) $slot_number ) . '" name="xpressui_overlay_done_additional_file_slots[' . esc_attr( (string) $i ) . ']" class="regular-text" value="' . esc_attr( $slot_value ) . '" placeholder="' . esc_attr( $placeholder ) . '"></td></tr>';
		}
		echo '</tbody></table>';
		echo '</details>';
		echo '</div>';

	} else {
		// -------------------------------------------------------------------------
		// Pending Info Document Slots
		// -------------------------------------------------------------------------
		echo '<div class="card xpressui-admin-card">';
		echo '<details open><summary><h2>' . esc_html__( 'Pending Info Document Slots', 'xpressui-bridge' ) . '</h2><span class="xpressui-toggle-icon" aria-hidden="true">▾</span></summary>';
		echo '<p>' . esc_html__( 'Used for Pending info requests.', 'xpressui-bridge' ) . '</p>';
		echo '<table class="form-table"><tbody>';

		echo '<tr><th><label for="xpressui_pending_info_documents_section_label">' . esc_html__( 'Documents section label', 'xpressui-bridge' ) . '</label></th>';
		echo '<td><input type="text" id="xpressui_pending_info_documents_section_label" name="xpressui_pending_info_documents_section_label" class="regular-text" placeholder="' . esc_attr__( 'Documents to download', 'xpressui-bridge' ) . '" value="' . esc_attr( (string) ( $s['pendingInfoDocumentsSectionLabel'] ?? '' ) ) . '">';
		echo '<p class="description">' . esc_html__( 'Heading shown above the attached files in the pending info email. Leave empty to use the default.', 'xpressui-bridge' ) . '</p></td></tr>';

		echo '<tr><th><label for="xpressui_additional_file_label">' . esc_html__( 'Slot 1 label', 'xpressui-bridge' ) . '</label></th>';
		echo '<td><input type="text" id="xpressui_additional_file_label" name="xpressui_additional_file_label" class="regular-text" placeholder="' . esc_attr__( 'e.g. Signed contract, ID document…', 'xpressui-bridge' ) . '" value="' . esc_attr( (string) ( $s['additionalFileLabel'] ?? '' ) ) . '">';
		echo '<p class="description">' . esc_html__( 'Leave empty to disable the slot. When filled, the label is shown above the upload field in the resubmission form.', 'xpressui-bridge' ) . '</p></td></tr>';

		echo '</tbody></table>';
		echo '</details>';
		echo '</div>';

		echo '<div class="card xpressui-admin-card">';
		echo '<details open><summary><h2>' . esc_html__( 'Done Informational File Slots', 'xpressui-bridge' ) . '</h2><span class="xpressui-toggle-icon" aria-hidden="true">▾</span></summary>';
		echo '<p>' . esc_html__( 'Used for Done informational files.', 'xpressui-bridge' ) . '</p>';
		echo '<table class="form-table"><tbody>';

		echo '<tr><th><label for="xpressui_done_documents_section_label">' . esc_html__( 'Documents section label', 'xpressui-bridge' ) . '</label></th>';
		echo '<td><input type="text" id="xpressui_done_documents_section_label" name="xpressui_done_documents_section_label" class="regular-text" placeholder="' . esc_attr__( 'Documents to download', 'xpressui-bridge' ) . '" value="' . esc_attr( (string) ( $s['doneDocumentsSectionLabel'] ?? '' ) ) . '">';
		echo '<p class="description">' . esc_html__( 'Heading shown above the attached files in the done email. Leave empty to use the default.', 'xpressui-bridge' ) . '</p></td></tr>';

		echo '<tr><th><label for="xpressui_done_additional_file_label">' . esc_html__( 'Slot 1 label', 'xpressui-bridge' ) . '</label></th>';
		echo '<td><input type="text" id="xpressui_done_additional_file_label" name="xpressui_done_additional_file_label" class="regular-text" placeholder="' . esc_attr__( 'e.g. Welcome pack', 'xpressui-bridge' ) . '" value="' . esc_attr( (string) ( $s['doneAdditionalFileLabel'] ?? '' ) ) . '">';
		echo '<p class="description">' . esc_html__( 'Leave empty to disable the slot. When filled, the label is used for informational files sent on Done.', 'xpressui-bridge' ) . '</p></td></tr>';

		echo '</tbody></table>';
		echo '</details>';
		echo '</div>';
	}

	echo '</form>';
	echo '</div>';
}
