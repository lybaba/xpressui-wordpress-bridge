<?php
/**
 * Metabox registration and rendering for submission detail screens.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure "Request Additional Document" is never hidden via WP Screen Options.
// Without this, WP auto-hides it (user meta metaboxhidden_*) and the checkbox
// resets to unchecked on every page load.
function xpressui_register_metaboxes( $post_type, $post = null ) {
	add_meta_box( 'xpressui_submission_status_mb',  __( 'Submission Workflow', 'xpressui-bridge' ),        'xpressui_render_status_metabox',  'xpressui_submission', 'side',   'high' );
	add_meta_box( 'xpressui_submission_summary_mb', __( 'Submission Summary', 'xpressui-bridge' ),         'xpressui_render_summary_metabox', 'xpressui_submission', 'side',   'high' );
	add_meta_box( 'xpressui_submission_review_mb',  __( 'Review Notes', 'xpressui-bridge' ),               'xpressui_render_review_metabox',  'xpressui_submission', 'side',   'high' );
	add_meta_box( 'xpressui_submission_history_mb', __( 'Status History', 'xpressui-bridge' ),             'xpressui_render_history_metabox', 'xpressui_submission', 'side',   'default' );
	add_meta_box( 'xpressui_submission_preview_mb', __( 'Submission Preview', 'xpressui-bridge' ),         'xpressui_render_preview_metabox', 'xpressui_submission', 'normal', 'high' );
	add_meta_box( 'xpressui_submission_delivery_mb', __( 'Submission Delivery', 'xpressui-bridge' ),       'xpressui_render_delivery_metabox', 'xpressui_submission', 'normal', 'default' );
	add_meta_box( 'xpressui_submission_payload_mb', __( 'Submission Payload', 'xpressui-bridge' ),         'xpressui_render_payload_metabox', 'xpressui_submission', 'normal', 'default' );
	add_meta_box( 'xpressui_submission_files_mb',   __( 'Uploaded Files', 'xpressui-bridge' ),             'xpressui_render_files_metabox',   'xpressui_submission', 'side',   'default' );
}

// ---------------------------------------------------------------------------
// Summary metabox
// ---------------------------------------------------------------------------

function xpressui_render_summary_metabox( $post ) {
	$payload        = xpressui_get_submission_payload( $post->ID );
	$project_id     = (string) get_post_meta( $post->ID, '_xpressui_project_id', true );
	$project_slug   = (string) get_post_meta( $post->ID, '_xpressui_project_slug', true );
	$submission_id  = (string) get_post_meta( $post->ID, '_xpressui_submission_id', true );
	$status         = (string) get_post_meta( $post->ID, '_xpressui_submission_status', true );
	$reviewed_at    = (string) get_post_meta( $post->ID, '_xpressui_reviewed_at', true );
	$done_at        = (string) get_post_meta( $post->ID, '_xpressui_done_at', true );
	$rejected_at       = (string) get_post_meta( $post->ID, '_xpressui_rejected_at', true );
	$pending_info_at   = (string) get_post_meta( $post->ID, '_xpressui_pending_info_at', true );
	$config         = xpressui_get_config_snapshot( $post->ID );
	$field_index    = xpressui_build_config_field_index( $config );
	$capture        = xpressui_get_submission_capture_summary( $field_index, $payload );
	$email          = trim( (string) ( $payload['email'] ?? '' ) );
	$phone          = trim( (string) ( $payload['phone'] ?? $payload['phoneNumber'] ?? '' ) );
	$contact_name   = xpressui_get_contact_summary( $payload );

	echo '<dl class="xpressui-summary-dl">';
	echo '<dt>' . esc_html__( 'Status', 'xpressui-bridge' ) . '</dt><dd>' . esc_html( xpressui_get_status_label( $status ) ) . '</dd>';
	echo '<dt>' . esc_html__( 'Project', 'xpressui-bridge' ) . '</dt><dd>' . esc_html( $project_slug !== '' ? $project_slug : __( 'unknown-project', 'xpressui-bridge' ) ) . '</dd>';
	echo '<dt>' . esc_html__( 'Project ID', 'xpressui-bridge' ) . '</dt><dd class="xpressui-break-all">' . esc_html( $project_id !== '' ? $project_id : __( 'not recorded', 'xpressui-bridge' ) ) . '</dd>';
	echo '<dt>' . esc_html__( 'Submission ID', 'xpressui-bridge' ) . '</dt><dd class="xpressui-break-all">' . esc_html( $submission_id !== '' ? $submission_id : __( 'not recorded', 'xpressui-bridge' ) ) . '</dd>';
	echo '<dt>' . esc_html__( 'Assignee', 'xpressui-bridge' ) . '</dt><dd>' . esc_html( xpressui_get_assignee_display( $post->ID ) ?: __( 'Unassigned', 'xpressui-bridge' ) ) . '</dd>';

	if ( $reviewed_at !== '' ) {
		echo '<dt>' . esc_html__( 'Reviewed at', 'xpressui-bridge' ) . '</dt><dd>' . esc_html( $reviewed_at ) . '</dd>';
	}
	if ( $done_at !== '' ) {
		echo '<dt>' . esc_html__( 'Done at', 'xpressui-bridge' ) . '</dt><dd>' . esc_html( $done_at ) . '</dd>';
	}
	if ( $pending_info_at !== '' ) {
		echo '<dt>' . esc_html__( 'Pending info since', 'xpressui-bridge' ) . '</dt><dd>' . esc_html( $pending_info_at ) . '</dd>';
	}
	if ( $rejected_at !== '' ) {
		echo '<dt>' . esc_html__( 'Rejected at', 'xpressui-bridge' ) . '</dt><dd>' . esc_html( $rejected_at ) . '</dd>';
	}
	if ( $capture['fieldCount'] > 0 ) {
		$capture_text = sprintf(
			/* translators: 1: filled fields, 2: total fields, 3: complete sections, 4: total sections */
			__( '%1$d / %2$d fields · %3$d / %4$d sections complete', 'xpressui-bridge' ),
			$capture['filledCount'],
			$capture['fieldCount'],
			$capture['completeSections'],
			$capture['sectionCount']
		);
		if ( $capture['partialSections'] > 0 ) {
			/* translators: %d: partial sections count */
			$capture_text .= ' · ' . sprintf( __( '%d partial', 'xpressui-bridge' ), $capture['partialSections'] );
		}
		echo '<dt>' . esc_html__( 'Capture', 'xpressui-bridge' ) . '</dt><dd>' . esc_html( $capture_text ) . '</dd>';
	}
	if ( $contact_name !== '' ) {
		echo '<dt>' . esc_html__( 'Contact', 'xpressui-bridge' ) . '</dt><dd>' . esc_html( $contact_name ) . '</dd>';
	}
	if ( $email !== '' ) {
		echo '<dt>' . esc_html__( 'Email', 'xpressui-bridge' ) . '</dt><dd><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></dd>';
	}
	if ( $phone !== '' ) {
		echo '<dt>' . esc_html__( 'Phone', 'xpressui-bridge' ) . '</dt><dd><a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a></dd>';
	}
	echo '</dl>';
}

// ---------------------------------------------------------------------------
// Status / workflow metabox
// ---------------------------------------------------------------------------

function xpressui_render_status_metabox( $post ) {
	$current_status   = (string) get_post_meta( $post->ID, '_xpressui_submission_status', true );
	$current_assignee = (int) get_post_meta( $post->ID, '_xpressui_assignee_id', true );
	if ( $current_status === '' ) {
		$current_status = 'new';
	}
	wp_nonce_field( 'xpressui_save_submission_status', 'xpressui_submission_status_nonce' );

	echo '<label for="xpressui_submission_status"><strong>' . esc_html__( 'Operator status', 'xpressui-bridge' ) . '</strong></label><br />';
	echo '<select id="xpressui_submission_status" name="xpressui_submission_status" class="xpressui-full-width">';
	foreach ( xpressui_get_status_options() as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '"' . selected( $current_status, $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';

	echo '<label for="xpressui_assignee_id" class="xpressui-mt-12"><strong>' . esc_html__( 'Assignee', 'xpressui-bridge' ) . '</strong></label>';
	echo '<select id="xpressui_assignee_id" name="xpressui_assignee_id" class="xpressui-full-width">';
	echo '<option value="">' . esc_html__( 'Unassigned', 'xpressui-bridge' ) . '</option>';
	foreach ( xpressui_get_assignable_users() as $user ) {
		$label = (string) ( $user->display_name ?: $user->user_login ?: ( 'User #' . $user->ID ) );
		echo '<option value="' . esc_attr( (string) $user->ID ) . '"' . selected( $current_assignee, (int) $user->ID, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
	echo '<p class="xpressui-hint">' . esc_html__( 'Use Update to save the new status.', 'xpressui-bridge' ) . '</p>';

}

function xpressui_save_submission_status( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['xpressui_submission_status_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['xpressui_submission_status_nonce'] ) ), 'xpressui_save_submission_status' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$previous_status = (string) get_post_meta( $post_id, '_xpressui_submission_status', true );
	$previous_note   = (string) get_post_meta( $post_id, '_xpressui_review_note', true );
	$previous_flagged_fields = xpressui_get_flagged_fields( $post_id );
	$status      = isset( $_POST['xpressui_submission_status'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_submission_status'] ) ) : 'new';
	$note        = isset( $_POST['xpressui_review_note'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['xpressui_review_note'] ) ) : '';
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized with absint() after unslashing.
	$assignee_id = isset( $_POST['xpressui_assignee_id'] ) ? absint( wp_unslash( (string) $_POST['xpressui_assignee_id'] ) ) : 0;
	$options     = xpressui_get_status_options();

	$flagged_fields = [];
	if ( isset( $_POST['xpressui_flagged_fields'] ) ) {
		$raw_flagged = wp_unslash( $_POST['xpressui_flagged_fields'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized via array_map('sanitize_text_field') below
		if ( is_array( $raw_flagged ) ) {
			$flagged_fields = array_values( array_filter(
				array_map( 'sanitize_text_field', $raw_flagged ),
				static function ( $f ) { return is_string( $f ) && preg_match( '/^[a-zA-Z][a-zA-Z0-9_]*$/', $f ); }
			) );
		} else {
			$flagged_fields = array_values( array_filter(
				array_map( 'trim', explode( ',', sanitize_text_field( (string) $raw_flagged ) ) ),
				static function ( $f ) { return preg_match( '/^[a-zA-Z][a-zA-Z0-9_]*$/', $f ); }
			) );
		}
	}
	$raw_legacy_flagged = isset( $_POST['xpressui_flagged_fields_legacy'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_flagged_fields_legacy'] ) ) : '';
	if ( $raw_legacy_flagged !== '' ) {
		$flagged_fields = array_merge(
			$flagged_fields,
			array_values( array_filter(
				array_map( 'trim', explode( ',', $raw_legacy_flagged ) ),
				static function ( $f ) { return preg_match( '/^[a-zA-Z][a-zA-Z0-9_]*$/', $f ); }
			) )
		);
	}
	$flagged_fields = array_values( array_unique( $flagged_fields ) );
	update_post_meta( $post_id, '_xpressui_flagged_fields', wp_json_encode( $flagged_fields ) );

	// Save reference files (field name → attachment ID).
	$ref_files_raw = isset( $_POST['xpressui_ref_files'] ) && is_array( $_POST['xpressui_ref_files'] )
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		? wp_unslash( $_POST['xpressui_ref_files'] )
		: [];
	$ref_files = [];
	foreach ( $ref_files_raw as $field_name => $attachment_id ) {
		$field_name    = sanitize_key( (string) $field_name );
		$attachment_id = absint( $attachment_id );
		if ( $field_name !== '' && $attachment_id > 0 ) {
			$ref_files[ $field_name ] = $attachment_id;
		}
	}
	update_post_meta( $post_id, '_xpressui_field_reference_files', wp_json_encode( $ref_files ) );


	if ( ! isset( $options[ $status ] ) ) {
		$status = 'new';
	}
	xpressui_set_submission_status( $post_id, $status, $note );
	xpressui_set_assignee( $post_id, $assignee_id );

	// Clear the note after every save so the textarea is empty on the next page load.
	// The note is already persisted in status history and sent in any notification.
	delete_post_meta( $post_id, '_xpressui_review_note' );
}

// ---------------------------------------------------------------------------
// Review notes metabox (stub — afile metabox removed)
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Review notes metabox
// ---------------------------------------------------------------------------

function xpressui_get_review_field_groups( $post_id ) {
	$payload      = xpressui_get_submission_payload( $post_id );
	$config       = xpressui_get_config_snapshot( $post_id );
	$field_index  = xpressui_build_config_field_index( $config );
	$hidden_keys  = [ 'projectId', 'projectSlug', 'projectConfigVersion', 'submissionId', 'projectConfigSnapshotJson', 'rest_route' ];
	$grouped      = [];
	$rendered     = [];

	if ( ! empty( $field_index ) ) {
		foreach ( $field_index as $field_name => $field_meta ) {
			if ( ! array_key_exists( $field_name, $payload ) ) {
				continue;
			}
			$section_label = (string) ( $field_meta['sectionLabel'] ?? __( 'Submission', 'xpressui-bridge' ) );
			if ( ! isset( $grouped[ $section_label ] ) ) {
				$grouped[ $section_label ] = [];
			}
			$grouped[ $section_label ][ $field_name ] = is_array( $field_meta ) ? $field_meta : [];
			$rendered[ $field_name ]                  = true;
		}
	}

	foreach ( $payload as $field_name => $value ) {
		if ( isset( $rendered[ $field_name ] ) || in_array( $field_name, $hidden_keys, true ) ) {
			continue;
		}
		if ( ! isset( $grouped[ __( 'Additional fields', 'xpressui-bridge' ) ] ) ) {
			$grouped[ __( 'Additional fields', 'xpressui-bridge' ) ] = [];
		}
		$grouped[ __( 'Additional fields', 'xpressui-bridge' ) ][ $field_name ] = [
			'label' => $field_name,
			'type'  => '',
		];
	}

	return [
		'payload' => $payload,
		'groups'  => $grouped,
	];
}

function xpressui_get_review_field_value_preview( $value, $field_meta = [] ) {
	$formatted = wp_strip_all_tags( (string) xpressui_format_submission_value( $value, $field_meta ), true );
	$formatted = preg_replace( '/\s+/', ' ', $formatted );
	$formatted = is_string( $formatted ) ? trim( $formatted ) : '';
	if ( $formatted === '' ) {
		return __( 'Empty', 'xpressui-bridge' );
	}
	if ( function_exists( 'mb_strimwidth' ) ) {
		return mb_strimwidth( $formatted, 0, 96, '…' );
	}
	return strlen( $formatted ) > 96 ? substr( $formatted, 0, 93 ) . '...' : $formatted;
}

function xpressui_is_file_field( $field_meta, $value ) {
	if ( ( $field_meta['type'] ?? '' ) === 'file' ) {
		return true;
	}
	return is_array( $value ) && ( $value['kind'] ?? '' ) === 'uploaded-file';
}

function xpressui_render_ref_file_picker_row( $field_name, $ref_files ) {
	$attachment_id = (int) ( $ref_files[ $field_name ] ?? 0 );
	$has_file      = $attachment_id > 0;
	$file_url      = $has_file ? (string) wp_get_attachment_url( $attachment_id ) : '';
	$file_name     = '';
	if ( $has_file ) {
		$path      = (string) get_attached_file( $attachment_id );
		$file_name = $path !== '' ? basename( $path ) : (string) get_the_title( $attachment_id );
	}
	$clear_style = $has_file ? '' : ' style="display:none;"';

	echo '<tr class="xpressui-ref-file-row">';
	echo '<td colspan="3" class="xpressui-ref-file-cell">';
	echo '<div class="xpressui-ref-file-picker">';
	echo '<input type="hidden" name="xpressui_ref_files[' . esc_attr( $field_name ) . ']" value="' . esc_attr( (string) ( $attachment_id ?: '' ) ) . '">';
	echo '<button type="button" class="button xpressui-ref-file-btn" data-field="' . esc_attr( $field_name ) . '">';
	echo '<span class="dashicons dashicons-paperclip" style="font-size:14px;vertical-align:middle;margin-right:4px;"></span>';
	echo esc_html__( 'Attach reference file', 'xpressui-bridge' );
	echo '</button>';
	echo '<span class="xpressui-ref-file-preview"' . $clear_style . ' data-field="' . esc_attr( $field_name ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $clear_style is a hardcoded string
	if ( $file_url !== '' ) {
		echo ' <a href="' . esc_url( $file_url ) . '" target="_blank" rel="noreferrer">' . esc_html( $file_name ) . '</a>';
	}
	echo ' <button type="button" class="xpressui-ref-file-remove" data-field="' . esc_attr( $field_name ) . '" title="' . esc_attr__( 'Remove', 'xpressui-bridge' ) . '">✕</button>';
	echo '</span>';
	echo '</div>';
	echo '</td>';
	echo '</tr>';
}


function xpressui_render_review_metabox( $post ) {
	$saved_status = (string) get_post_meta( $post->ID, '_xpressui_submission_status', true );
	if ( $saved_status === '' ) {
		$saved_status = 'new';
	}

	echo '<label for="xpressui_review_note"><strong>' . esc_html__( 'Operator notes', 'xpressui-bridge' ) . '</strong></label>';
	echo '<textarea id="xpressui_review_note" name="xpressui_review_note" rows="5" class="xpressui-full-width"></textarea>';
}

// ---------------------------------------------------------------------------
// History metabox
// ---------------------------------------------------------------------------

function xpressui_render_history_metabox( $post ) {
	$history = array_reverse( xpressui_get_status_history( $post->ID ) );
	if ( empty( $history ) ) {
		echo '<p>' . esc_html__( 'No review history recorded yet.', 'xpressui-bridge' ) . '</p>';
		return;
	}
	echo '<ul class="xpressui-history-list">';
	foreach ( $history as $entry ) {
		$status = xpressui_get_status_label( (string) ( $entry['status'] ?? 'new' ) );
		$actor  = (string) ( $entry['actor'] ?? 'system' );
		$at     = (string) ( $entry['at'] ?? '' );
		$note   = trim( (string) ( $entry['note'] ?? '' ) );
		echo '<li>';
		echo '<strong>' . esc_html( $status ) . '</strong><br />';
		echo '<span class="xpressui-muted">' . esc_html( ( $at !== '' ? $at : __( 'unknown time', 'xpressui-bridge' ) ) . ' · ' . $actor ) . '</span>';
		if ( $note !== '' ) {
			echo '<br /><span>' . esc_html( $note ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ul>';
}

// ---------------------------------------------------------------------------
// Preview metabox
// ---------------------------------------------------------------------------

function xpressui_render_preview_metabox( $post ) {
	$payload   = xpressui_get_submission_payload( $post->ID );
	$ref_files = xpressui_get_field_reference_files( $post->ID );
	if ( empty( $payload ) ) {
		echo '<p>' . esc_html__( 'No structured submission data recorded.', 'xpressui-bridge' ) . '</p>';
		return;
	}
	$config      = xpressui_get_config_snapshot( $post->ID );
	$field_index = xpressui_build_config_field_index( $config );
	$hidden_keys = [ 'projectId', 'projectSlug', 'projectConfigVersion', 'submissionId', 'projectConfigSnapshotJson', 'rest_route' ];
	$grouped     = [];
	$rendered    = [];

	if ( ! empty( $field_index ) ) {
		foreach ( $field_index as $field_name => $field_meta ) {
			if ( ! array_key_exists( $field_name, $payload ) ) {
				continue;
			}
			$section_label = (string) ( $field_meta['sectionLabel'] ?? 'Submission' );
			if ( ! isset( $grouped[ $section_label ] ) ) {
				$grouped[ $section_label ] = [];
			}
			$grouped[ $section_label ][ $field_name ] = $field_meta;
			$rendered[ $field_name ]                  = true;
		}

		foreach ( $grouped as $section_label => $fields ) {
			$summary      = xpressui_get_section_capture_summary( $fields, $payload );
			$status_label = ucfirst( (string) ( $summary['status'] ?? 'empty' ) );
			echo '<h3 class="xpressui-section-heading">' . esc_html( $section_label ) . '</h3>';
			/* translators: 1: status, 2: filled fields, 3: total fields */
			$section_meta = sprintf( __( '%1$s · %2$d / %3$d fields captured', 'xpressui-bridge' ), $status_label, $summary['filledCount'], $summary['fieldCount'] );
			if ( $summary['interactiveCount'] > 0 ) {
				/* translators: %d: interactive field count */
				$section_meta .= ' · ' . sprintf( __( '%d interactive', 'xpressui-bridge' ), $summary['interactiveCount'] );
			}
			echo '<p class="xpressui-muted xpressui-section-meta">' . esc_html( $section_meta ) . '</p>';
			echo '<table class="widefat striped xpressui-preview-table"><tbody>';
			foreach ( $fields as $field_name => $field_meta ) {
				echo '<tr>';
				echo '<th class="xpressui-preview-th">' . esc_html( $field_meta['label'] ) . '</th>';
				echo '<td>' . wp_kses_post( xpressui_render_preview_field_value( $payload[ $field_name ], $field_meta ) ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}
	}

	$remaining = [];
	foreach ( $payload as $key => $value ) {
		if ( isset( $rendered[ $key ] ) || in_array( $key, $hidden_keys, true ) ) {
			continue;
		}
		$remaining[ $key ] = $value;
	}
	if ( ! empty( $remaining ) ) {
		echo '<h3 class="xpressui-section-heading">' . esc_html__( 'Additional fields', 'xpressui-bridge' ) . '</h3>';
		echo '<table class="widefat striped xpressui-preview-table"><tbody>';
		foreach ( $remaining as $key => $value ) {
			$field_meta = is_array( $field_index[ $key ] ?? null ) ? $field_index[ $key ] : [];
			echo '<tr>';
			echo '<th class="xpressui-preview-th">' . esc_html( $field_meta['label'] ?? $key ) . '</th>';
			echo '<td>' . wp_kses_post( xpressui_render_preview_field_value( $value, $field_meta ) ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}
}

// ---------------------------------------------------------------------------
// Delivery metabox
// ---------------------------------------------------------------------------

function xpressui_render_delivery_metabox( $post ) {
	$timing_json = (string) get_post_meta( $post->ID, '_xpressui_submit_timing', true );
	$timing      = $timing_json !== '' ? json_decode( $timing_json, true ) : [];
	$timing      = is_array( $timing ) ? $timing : [];

	$mail_status        = (string) get_post_meta( $post->ID, '_xpressui_mail_status', true );
	$mail_error         = (string) get_post_meta( $post->ID, '_xpressui_mail_error', true );
	$mail_sent_at       = (string) get_post_meta( $post->ID, '_xpressui_mail_sent_at', true );
	$mail_fallback_used = (string) get_post_meta( $post->ID, '_xpressui_mail_fallback_used', true );

	$webhook_status  = (string) get_post_meta( $post->ID, '_xpressui_webhook_status', true );
	$webhook_code    = (string) get_post_meta( $post->ID, '_xpressui_webhook_code', true );
	$webhook_error   = (string) get_post_meta( $post->ID, '_xpressui_webhook_error', true );
	$webhook_sent_at = (string) get_post_meta( $post->ID, '_xpressui_webhook_sent_at', true );
	$event_log       = function_exists( 'xpressui_get_submission_events' ) ? xpressui_get_submission_events( $post->ID ) : [];

	$badge = static function( string $status ): string {
		$cls = 'xpressui-badge';
		if ( in_array( $status, [ 'sent', 'ok', 'success' ], true ) ) {
			$cls .= ' xpressui-badge--green';
		} elseif ( $status === '' || $status === 'not-set' ) {
			$cls .= ' xpressui-badge--gray';
		} else {
			$cls .= ' xpressui-badge--amber';
		}
		return '<span class="' . esc_attr( $cls ) . '">' . esc_html( $status !== '' ? $status : 'not-set' ) . '</span>';
	};

	$fmt_date = static function( string $iso ): string {
		if ( $iso === '' ) {
			return '—';
		}
		$ts = strtotime( $iso );
		return $ts ? esc_html( (string) wp_date( 'M j, Y · H:i', $ts ) ) : esc_html( $iso );
	};

	echo '<div class="xpressui-delivery-grid">';

	// Submit timing.
	echo '<div class="xpressui-delivery-card">';
	echo '<h4>' . esc_html__( 'Submit timing', 'xpressui-bridge' ) . '</h4>';
	if ( empty( $timing ) ) {
		echo '<p class="xpressui-hint">' . esc_html__( 'No timing data recorded yet.', 'xpressui-bridge' ) . '</p>';
	} else {
		$total_ms = is_numeric( $timing['totalMs'] ?? null ) ? (float) $timing['totalMs'] : 0;
		echo '<div class="xpressui-timing-list">';
		foreach ( $timing as $key => $value ) {
			$label = preg_replace( '/([a-z])([A-Z])/', '$1 $2', (string) $key );
			$label = (string) preg_replace( '/\s+Ms$/', '', $label );
			$label = ucfirst( $label );
			$ms    = is_numeric( $value ) ? (float) $value : 0;
			$pct   = ( $total_ms > 0 ) ? min( 100, (int) round( ( $ms / $total_ms ) * 100 ) ) : 0;
			$row_cls = 'xpressui-timing-row' . ( $key === 'totalMs' ? ' xpressui-timing-row--total' : '' );
			echo '<div class="' . esc_attr( $row_cls ) . '">';
			echo '<div class="xpressui-timing-label">' . esc_html( $label ) . '</div>';
			echo '<div class="xpressui-timing-bar-wrap"><div class="xpressui-timing-bar" style="width:' . esc_attr( (string) $pct ) . '%"></div></div>';
			echo '<div class="xpressui-timing-val">' . esc_html( is_numeric( $value ) ? ( (string) $value . ' ms' ) : (string) $value ) . '</div>';
			echo '</div>';
		}
		echo '</div>';
	}
	echo '</div>';

	// Mail delivery.
	echo '<div class="xpressui-delivery-card">';
	echo '<h4>' . esc_html__( 'Mail delivery', 'xpressui-bridge' ) . '</h4>';
	echo '<div class="xpressui-delivery-dl">';
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- $badge() and $fmt_date() return pre-escaped HTML
	echo '<div class="xpressui-dl-row"><span class="xpressui-dl-key">' . esc_html__( 'Status', 'xpressui-bridge' ) . '</span><span>' . $badge( $mail_status !== '' ? $mail_status : 'not-set' ) . '</span></div>';
	echo '<div class="xpressui-dl-row"><span class="xpressui-dl-key">' . esc_html__( 'Sent at', 'xpressui-bridge' ) . '</span><span>' . $fmt_date( $mail_sent_at ) . '</span></div>';
	if ( $mail_fallback_used === '1' ) {
		echo '<div class="xpressui-dl-row"><span class="xpressui-dl-key">' . esc_html__( 'Fallback', 'xpressui-bridge' ) . '</span><span>' . $badge( 'yes' ) . '</span></div>';
	}
	if ( $mail_error !== '' ) {
		echo '<div class="xpressui-dl-row"><span class="xpressui-dl-key">' . esc_html__( 'Error', 'xpressui-bridge' ) . '</span><span class="xpressui-error-text">' . esc_html( $mail_error ) . '</span></div>';
	}
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';
	echo '</div>';

	// Webhook delivery.
	echo '<div class="xpressui-delivery-card">';
	echo '<h4>' . esc_html__( 'Webhook delivery', 'xpressui-bridge' ) . '</h4>';
	if ( $webhook_status === '' || $webhook_status === 'not-set' ) {
		echo '<p class="xpressui-hint">' . esc_html__( 'No webhook configured.', 'xpressui-bridge' ) . '</p>';
	} else {
		echo '<div class="xpressui-delivery-dl">';
		echo '<div class="xpressui-dl-row"><span class="xpressui-dl-key">' . esc_html__( 'Status', 'xpressui-bridge' ) . '</span><span>' . $badge( $webhook_status ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( $webhook_code !== '' ) {
			echo '<div class="xpressui-dl-row"><span class="xpressui-dl-key">' . esc_html__( 'HTTP code', 'xpressui-bridge' ) . '</span><span>' . esc_html( $webhook_code ) . '</span></div>';
		}
		echo '<div class="xpressui-dl-row"><span class="xpressui-dl-key">' . esc_html__( 'Sent at', 'xpressui-bridge' ) . '</span><span>' . $fmt_date( $webhook_sent_at ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( $webhook_error !== '' ) {
			echo '<div class="xpressui-dl-row"><span class="xpressui-dl-key">' . esc_html__( 'Error', 'xpressui-bridge' ) . '</span><span class="xpressui-error-text">' . esc_html( $webhook_error ) . '</span></div>';
		}
		echo '</div>';
	}
	echo '</div>';

	// Recent events.
	echo '<div class="xpressui-delivery-card">';
	echo '<h4>' . esc_html__( 'Recent events', 'xpressui-bridge' ) . '</h4>';
	if ( empty( $event_log ) ) {
		echo '<p class="xpressui-hint">' . esc_html__( 'No instrumentation events recorded yet.', 'xpressui-bridge' ) . '</p>';
	} else {
		echo '<ul class="xpressui-event-list">';
		foreach ( array_slice( array_reverse( $event_log ), 0, 8 ) as $event ) {
			$event_type  = sanitize_text_field( (string) ( $event['eventType'] ?? 'unknown' ) );
			$occurred_at = sanitize_text_field( (string) ( $event['occurredAt'] ?? '' ) );
			$source      = sanitize_text_field( (string) ( $event['source'] ?? 'bridge' ) );
			echo '<li>';
			echo '<span class="xpressui-event-chip">' . esc_html( $event_type ) . '</span>';
			echo '<span class="xpressui-event-meta">' . $fmt_date( $occurred_at ) . ' · ' . esc_html( $source ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</li>';
		}
		echo '</ul>';
	}
	echo '</div>';

	echo '</div>';
}

// ---------------------------------------------------------------------------
// Payload metabox
// ---------------------------------------------------------------------------

function xpressui_render_payload_metabox( $post ) {
	$payload = xpressui_get_submission_payload( $post->ID );
	if ( empty( $payload ) ) {
		echo '<p>' . esc_html__( 'No submission payload recorded.', 'xpressui-bridge' ) . '</p>';
		return;
	}
	echo '<pre class="xpressui-payload-pre">' . esc_html( wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) . '</pre>';
}

// ---------------------------------------------------------------------------
// Files metabox
// ---------------------------------------------------------------------------

function xpressui_render_files_metabox( $post ) {
	$json  = get_post_meta( $post->ID, '_xpressui_uploaded_files', true );
	$files = $json ? json_decode( $json, true ) : [];

	if ( ! is_array( $files ) || empty( $files ) ) {
		echo '<p>' . esc_html__( 'No uploaded files recorded.', 'xpressui-bridge' ) . '</p>';
		$debug_json = get_post_meta( $post->ID, '_xpressui_upload_debug', true );
		if ( $debug_json ) {
			echo '<pre class="xpressui-payload-pre">' . esc_html( (string) $debug_json ) . '</pre>';
		}
		return;
	}

	echo '<ul class="xpressui-file-list">';
	foreach ( $files as $file ) {
		$field         = isset( $file['field'] ) ? (string) $file['field'] : 'file';
		$url           = isset( $file['url'] ) ? (string) $file['url'] : '';
		$attachment_id = isset( $file['attachmentId'] ) ? (int) $file['attachmentId'] : 0;

		echo '<li>';
		echo '<strong>' . esc_html( $field ) . '</strong><br />';
		if ( $url !== '' ) {
			echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noreferrer">' . esc_html__( 'Open file', 'xpressui-bridge' ) . '</a>';
		}
		if ( $attachment_id > 0 ) {
			/* translators: %d: attachment ID */
			echo '<br /><span class="xpressui-muted">' . esc_html( sprintf( __( 'Attachment ID: %d', 'xpressui-bridge' ), $attachment_id ) ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ul>';
}
