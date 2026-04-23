<?php
/**
 * Email notification helpers for new submissions.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Project settings accessor
// ---------------------------------------------------------------------------

/**
 * Returns a single setting value for a project.
 *
 * @param string $project_slug Project slug.
 * @param string $key          Setting key ('notifyEmail', 'redirectUrl', 'showProjectTitle', …).
 * @return string Setting value, or empty string if not set.
 */
function xpressui_get_project_setting( $project_slug, $key ) {
	$all_settings = get_option( 'xpressui_project_settings', [] );
	if ( ! is_array( $all_settings ) ) {
		return '';
	}
	$slug_settings = $all_settings[ $project_slug ] ?? [];
	if ( ! is_array( $slug_settings ) ) {
		return '';
	}
	return (string) ( $slug_settings[ $key ] ?? '' );
}

/**
 * Returns a boolean-like project setting flag.
 *
 * Stored values accept: "1", "true", "yes", "on".
 *
 * @param string $project_slug Project slug.
 * @param string $key          Setting key.
 * @param bool   $default      Default when not configured.
 * @return bool
 */
function xpressui_get_project_setting_flag( $project_slug, $key, $default = false ) {
	$value = xpressui_get_project_setting( $project_slug, $key );
	if ( '' === $value ) {
		return (bool) $default;
	}

	return in_array( strtolower( $value ), [ '1', 'true', 'yes', 'on' ], true );
}

/**
 * Returns an enum-like project setting value.
 *
 * @param string $project_slug Project slug.
 * @param string $key          Setting key.
 * @param array  $allowed      Allowed string values.
 * @param string $default      Default when not configured or invalid.
 * @return string
 */
function xpressui_get_project_setting_choice( $project_slug, $key, $allowed, $default = '' ) {
	$value = strtolower( xpressui_get_project_setting( $project_slug, $key ) );
	if ( '' === $value || ! in_array( $value, $allowed, true ) ) {
		return (string) $default;
	}

	return $value;
}

// ---------------------------------------------------------------------------
// Notification dispatch
// ---------------------------------------------------------------------------

/**
 * Sends a notification email if a notification address is configured for the project.
 *
 * @param int          $post_id      Submission post ID.
 * @param string       $project_slug Project slug.
 * @param array|string $payload      Submitted form data.
 */
function xpressui_maybe_send_notification( $post_id, $project_slug, $payload ) {
	$notify_email = xpressui_get_project_setting( $project_slug, 'notifyEmail' );
	if ( $notify_email === '' || ! is_email( $notify_email ) ) {
		return;
	}

	$subject = xpressui_build_notification_subject( $project_slug, $payload );
	$body    = xpressui_build_notification_body( $post_id, $project_slug, $payload );
	$headers = xpressui_build_notification_headers();

	wp_mail( $notify_email, $subject, $body, $headers );
}

// ---------------------------------------------------------------------------
// Email builders
// ---------------------------------------------------------------------------

/**
 * @param string       $project_slug
 * @param array|string $payload
 * @return string
 */
function xpressui_build_notification_subject( $project_slug, $payload ) {
	$site_name = get_bloginfo( 'name' );
	$contact   = is_array( $payload ) ? xpressui_get_contact_summary( $payload ) : '';

	if ( $contact !== '' ) {
		/* translators: 1: site name, 2: project slug, 3: contact name/email */
		return sprintf( __( '[%1$s] New submission for %2$s — %3$s', 'xpressui-bridge' ), $site_name, $project_slug, $contact );
	}
	/* translators: 1: site name, 2: project slug */
	return sprintf( __( '[%1$s] New submission for %2$s', 'xpressui-bridge' ), $site_name, $project_slug );
}

/**
 * Fields that carry internal plumbing data and should not appear in the email.
 */
function xpressui_notification_skip_fields() {
	return [
		'rest_route',
		'projectId',
		'projectSlug',
		'projectConfigVersion',
		'projectConfigSnapshotJson',
		'submissionId',
	];
}

/**
 * @param int          $post_id
 * @param string       $project_slug
 * @param array|string $payload
 * @return string HTML email body.
 */
function xpressui_build_notification_body( $post_id, $project_slug, $payload ) {
	$site_name   = esc_html( get_bloginfo( 'name' ) );
	$date        = esc_html( wp_date( 'Y-m-d H:i T' ) );
	$admin_url   = esc_url( add_query_arg( [ 'post' => $post_id, 'action' => 'edit' ], admin_url( 'post.php' ) ) );
	$contact     = is_array( $payload ) ? esc_html( xpressui_get_contact_summary( $payload ) ) : '';
	$skip        = xpressui_notification_skip_fields();

	// Build label index from the config snapshot attached to this submission.
	$config      = xpressui_get_config_snapshot( $post_id );
	$field_index = ! empty( $config ) ? xpressui_build_config_field_index( $config ) : [];

	// ── Group payload keys by section ───────────────────────────────────────
	// Keys not found in the field index land in a catch-all bucket.
	$sections_ordered = [];
	$ungrouped_keys   = [];

	if ( is_array( $payload ) ) {
		foreach ( $payload as $key => $value ) {
			if ( in_array( $key, $skip, true ) ) {
				continue;
			}
			if ( isset( $field_index[ $key ] ) ) {
				$sec = (string) ( $field_index[ $key ]['sectionLabel'] ?? '' );
				if ( $sec === '' ) {
					$sec = __( 'Submission', 'xpressui-bridge' );
				}
				if ( ! isset( $sections_ordered[ $sec ] ) ) {
					$sections_ordered[ $sec ] = [];
				}
				$sections_ordered[ $sec ][ $key ] = $value;
			} else {
				$ungrouped_keys[ $key ] = $value;
			}
		}
	}

	// Helper: format a single choice value as its human label when a map is available.
	$format_choice = static function ( $slug, $choice_map ) {
		$s     = (string) $slug;
		$lbl   = isset( $choice_map[ $s ] ) ? (string) $choice_map[ $s ] : '';
		if ( $lbl !== '' ) {
			return esc_html( $lbl );
		}
		return esc_html( $s );
	};

	// Helper: render a single field row.
	// $field_meta is the entry from $field_index (or [] if not found).
	$render_row = static function ( $label, $value, $i, $field_meta ) use ( $format_choice ) {
		$choice_map = is_array( $field_meta['choices'] ?? null ) ? $field_meta['choices'] : [];

		if ( is_array( $value ) ) {
			if ( ( $value['kind'] ?? '' ) === 'uploaded-file' ) {
				$display = esc_html( (string) ( $value['originalName'] ?? 'file' ) );
			} elseif ( ! empty( $choice_map ) ) {
				// Multi-select stored as array of slugs.
				$parts = [];
				foreach ( $value as $item ) {
					$parts[] = $format_choice( $item, $choice_map );
				}
				$display = implode( '<br>', $parts );
			} else {
				$display = '<code style="font-size:12px;color:#555;">' . esc_html( wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) . '</code>';
			}
		} elseif ( is_bool( $value ) ) {
			$display = esc_html( $value ? __( 'Yes', 'xpressui-bridge' ) : __( 'No', 'xpressui-bridge' ) );
		} elseif ( ! empty( $choice_map ) && is_string( $value ) && $value !== '' ) {
			$display = $format_choice( $value, $choice_map );
		} else {
			$display = nl2br( esc_html( (string) $value ) );
		}
		$bg = $i % 2 === 0 ? '#ffffff' : '#f9fafb';
		return '<tr style="background:' . $bg . ';">'
			. '<td style="padding:9px 14px;font-size:12px;color:#6b7280;white-space:nowrap;border-bottom:1px solid #f0f0f0;width:170px;vertical-align:top;">' . esc_html( $label ) . '</td>'
			. '<td style="padding:9px 14px;font-size:13px;color:#111827;border-bottom:1px solid #f0f0f0;word-break:break-word;">' . $display . '</td>'
			. '</tr>';
	};

	// ── Build rows HTML grouped by section ──────────────────────────────────
	$rows_html = '';
	$i         = 0;

	foreach ( $sections_ordered as $section_label => $fields ) {
		$rows_html .= '<tr>'
			. '<td colspan="2" style="padding:12px 14px 6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#9ca3af;background:#f9fafb;border-top:1px solid #f0f0f0;border-bottom:1px solid #f0f0f0;">'
			. esc_html( $section_label )
			. '</td></tr>';
		$i = 0; // reset alternating colour per section

		foreach ( $fields as $key => $value ) {
			$field_meta = is_array( $field_index[ $key ] ?? null ) ? $field_index[ $key ] : [];
			$label      = isset( $field_meta['label'] ) && $field_meta['label'] !== ''
				? $field_meta['label']
				: $key;
			$rows_html .= $render_row( $label, $value, $i, $field_meta );
			$i++;
		}
	}

	// Ungrouped keys (not in the field index) appended without a section header.
	foreach ( $ungrouped_keys as $key => $value ) {
		$rows_html .= $render_row( $key, $value, $i, [] );
		$i++;
	}

	// ── Meta rows ────────────────────────────────────────────────────────────
	$meta_rows = '<tr>'
		. '<td style="padding:6px 0;font-size:12px;color:#9ca3af;width:120px;">' . esc_html__( 'Submission ID', 'xpressui-bridge' ) . '</td>'
		. '<td style="padding:6px 0;font-size:13px;font-weight:600;color:#111827;">#' . esc_html( (string) $post_id ) . '</td>'
		. '</tr>'
		. '<tr>'
		. '<td style="padding:6px 0;font-size:12px;color:#9ca3af;">' . esc_html__( 'Date', 'xpressui-bridge' ) . '</td>'
		. '<td style="padding:6px 0;font-size:13px;color:#374151;">' . $date . '</td>'
		. '</tr>';
	if ( $contact !== '' ) {
		$meta_rows .= '<tr>'
			. '<td style="padding:6px 0;font-size:12px;color:#9ca3af;">' . esc_html__( 'Contact', 'xpressui-bridge' ) . '</td>'
			. '<td style="padding:6px 0;font-size:13px;color:#374151;">' . $contact . '</td>'
			. '</tr>';
	}

	// ── Template ─────────────────────────────────────────────────────────────
	return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">'
		. '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">'
		. '<tr><td align="center">'
		. '<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);max-width:600px;">'

		// Header
		. '<tr><td style="background:#1d2327;padding:22px 28px;">'
		. '<p style="margin:0;font-size:15px;font-weight:700;color:#ffffff;">' . $site_name . '</p>'
		. '<p style="margin:4px 0 0;font-size:13px;color:#9ca3af;">'
		/* translators: %s: workflow project slug. */
		. esc_html( sprintf( __( 'New submission — %s', 'xpressui-bridge' ), $project_slug ) )
		. '</p></td></tr>'

		// Meta
		. '<tr><td style="padding:20px 28px;border-bottom:1px solid #f0f0f0;">'
		. '<table cellpadding="0" cellspacing="0" width="100%">' . $meta_rows . '</table>'
		. '</td></tr>'

		// Fields (grouped by section — no outer heading)
		. '<tr><td>'
		. '<table cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;">'
		. $rows_html
		. '</table></td></tr>'

		// CTA footer
		. '<tr><td style="padding:24px 28px;background:#f9fafb;border-top:1px solid #f0f0f0;">'
		. '<a href="' . $admin_url . '" style="display:inline-block;padding:10px 20px;background:#2271b1;color:#ffffff;text-decoration:none;border-radius:4px;font-size:13px;font-weight:600;">'
		. esc_html__( 'Review submission →', 'xpressui-bridge' )
		. '</a>'
		. '<p style="margin:14px 0 0;font-size:11px;color:#d1d5db;">'
		. esc_html( sprintf(
			/* translators: %s: site name */
			__( 'Sent by XPressUI Bridge on %s.', 'xpressui-bridge' ),
			get_bloginfo( 'name' )
		) )
		. '</p></td></tr>'

		. '</table>'
		. '</td></tr></table>'
		. '</body></html>';
}

/**
 * @return string[]
 */
function xpressui_build_notification_headers() {
	return [
		'Content-Type: text/html; charset=UTF-8',
	];
}

// ---------------------------------------------------------------------------
// Pending-info submitter notification
// ---------------------------------------------------------------------------

/**
 * Retrieve the submitter's email address from the stored payload.
 * Returns an empty string if not found or not a valid email.
 *
 * @param int $post_id Submission post ID.
 * @return string
 */
function xpressui_get_submitter_email( $post_id ) {
	$payload  = xpressui_get_submission_payload( $post_id );
	$to_email = trim( (string) ( is_array( $payload ) ? ( $payload['email'] ?? '' ) : '' ) );
	return is_email( $to_email ) ? $to_email : '';
}

/**
 * Returns true when the project has submitter notifications enabled.
 *
 * @param string $project_slug
 * @return bool
 */
function xpressui_project_notifies_submitter( $project_slug ) {
	return xpressui_get_project_setting_flag( $project_slug, 'notifySubmitter', false );
}

/**
 * Send a "we need more information" email to the submitter when an operator
 * marks a submission as pending_info. Silently skips if no submitter email
 * is found in the stored payload or if submitter notifications are disabled.
 *
 * @param int    $post_id Submission post ID.
 * @param string $note    Operator review note explaining what is needed.
 */
function xpressui_maybe_send_pending_info_notification( $post_id, $note ) {
	$project_slug = (string) get_post_meta( $post_id, '_xpressui_project_slug', true );
	if ( ! xpressui_project_notifies_submitter( $project_slug ) ) {
		return;
	}
	$to_email = xpressui_get_submitter_email( $post_id );
	if ( $to_email === '' ) {
		return;
	}

	$subject = xpressui_build_pending_info_subject( $project_slug );
	$body    = xpressui_build_pending_info_body( $post_id, $project_slug, $note );
	$headers = xpressui_build_notification_headers();

	wp_mail( $to_email, $subject, $body, $headers );
}

/**
 * Send a confirmation email to the submitter when an operator marks a
 * submission as done.
 *
 * @param int    $post_id Submission post ID.
 * @param string $note    Operator review note (optional closing message).
 */
function xpressui_maybe_send_done_notification( $post_id, $note ) {
	$project_slug = (string) get_post_meta( $post_id, '_xpressui_project_slug', true );
	if ( ! xpressui_project_notifies_submitter( $project_slug ) ) {
		return;
	}
	$to_email = xpressui_get_submitter_email( $post_id );
	if ( $to_email === '' ) {
		return;
	}

	$subject = xpressui_build_done_subject( $project_slug );
	$body    = xpressui_build_done_body( $post_id, $project_slug, $note );
	$headers = xpressui_build_notification_headers();

	wp_mail( $to_email, $subject, $body, $headers );
}

/**
 * Send a rejection email to the submitter when an operator marks a
 * submission as rejected.
 *
 * @param int    $post_id Submission post ID.
 * @param string $note    Operator review note explaining the rejection.
 */
function xpressui_maybe_send_rejected_notification( $post_id, $note ) {
	$project_slug = (string) get_post_meta( $post_id, '_xpressui_project_slug', true );
	if ( ! xpressui_project_notifies_submitter( $project_slug ) ) {
		return;
	}
	$to_email = xpressui_get_submitter_email( $post_id );
	if ( $to_email === '' ) {
		return;
	}

	$subject = xpressui_build_rejected_subject( $project_slug );
	$body    = xpressui_build_rejected_body( $post_id, $project_slug, $note );
	$headers = xpressui_build_notification_headers();

	wp_mail( $to_email, $subject, $body, $headers );
}

/**
 * @param string $project_slug
 * @return string
 */
function xpressui_build_pending_info_subject( $project_slug ) {
	$site_name = get_bloginfo( 'name' );
	/* translators: 1: site name, 2: project slug */
	return sprintf( __( '[%1$s] Your submission for %2$s needs additional information', 'xpressui-bridge' ), $site_name, $project_slug );
}

/**
 * @param string $project_slug
 * @return string
 */
function xpressui_build_done_subject( $project_slug ) {
	$site_name = get_bloginfo( 'name' );
	/* translators: 1: site name, 2: project slug */
	return sprintf( __( '[%1$s] Your submission for %2$s has been processed', 'xpressui-bridge' ), $site_name, $project_slug );
}

/**
 * @param string $project_slug
 * @return string
 */
function xpressui_build_rejected_subject( $project_slug ) {
	$site_name = get_bloginfo( 'name' );
	/* translators: 1: site name, 2: project slug */
	return sprintf( __( '[%1$s] Update on your submission for %2$s', 'xpressui-bridge' ), $site_name, $project_slug );
}

/**
 * Shared HTML email shell. $accent_color and $header_label set the visual tone.
 * $intro_text and $note_html are injected into the body.
 */
function xpressui_build_submitter_email_html( $site_name, $header_label, $accent_color, $intro_text, $note_html, $footer_note ) {
	return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">'
		. '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">'
		. '<tr><td align="center">'
		. '<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,0.08);max-width:600px;">'
		. '<tr><td style="background:#1d2327;padding:22px 28px;">'
		. '<p style="margin:0;font-size:15px;font-weight:700;color:#ffffff;">' . esc_html( $site_name ) . '</p>'
		. '<p style="margin:4px 0 0;font-size:13px;color:#9ca3af;">' . esc_html( $header_label ) . '</p>'
		. '</td></tr>'
		. '<tr><td style="padding:28px 28px 24px;">'
		. '<p style="margin:0;font-size:14px;color:#374151;line-height:1.6;">' . $intro_text . '</p>'
		. $note_html
		. '<p style="margin:20px 0 0;font-size:13px;color:#6b7280;line-height:1.6;">'
		. esc_html__( 'If you have any questions, please reply to this email.', 'xpressui-bridge' )
		. '</p>'
		. '</td></tr>'
		. '<tr><td style="padding:16px 28px;background:#f9fafb;border-top:1px solid #f0f0f0;">'
		. '<p style="margin:0;font-size:11px;color:#d1d5db;">' . esc_html( $footer_note ) . '</p>'
		. '</td></tr>'
		. '</table>'
		. '</td></tr></table>'
		. '</body></html>';
}

/**
 * @param int    $post_id
 * @param string $project_slug
 * @param string $note
 * @return string HTML email body.
 */
function xpressui_build_done_body( $post_id, $project_slug, $note ) {
	$site_name   = get_bloginfo( 'name' );
	$footer_note = sprintf(
		/* translators: %s: site name */
		__( 'Sent by %s.', 'xpressui-bridge' ),
		$site_name,
	);
	$intro = esc_html( sprintf(
		/* translators: %s: project slug */
		__( 'Good news — your submission for %s has been reviewed and processed. Thank you for your time.', 'xpressui-bridge' ),
		$project_slug,
	) );
	$note_html = $note !== ''
		? '<p style="margin:16px 0 0;padding:14px 16px;background:#f0fdf4;border-left:3px solid #86efac;font-size:13px;color:#374151;line-height:1.6;">' . nl2br( esc_html( $note ) ) . '</p>'
		: '';

	return xpressui_build_submitter_email_html(
		$site_name,
		__( 'Submission processed', 'xpressui-bridge' ),
		'#22c55e',
		$intro,
		$note_html,
		$footer_note,
	);
}

/**
 * @param int    $post_id
 * @param string $project_slug
 * @param string $note
 * @return string HTML email body.
 */
function xpressui_build_rejected_body( $post_id, $project_slug, $note ) {
	$site_name   = get_bloginfo( 'name' );
	$footer_note = sprintf(
		/* translators: %s: site name */
		__( 'Sent by %s.', 'xpressui-bridge' ),
		$site_name,
	);
	$intro = esc_html( sprintf(
		/* translators: %s: project slug */
		__( 'After careful review, we are unable to process your submission for %s. Please see the note below for details.', 'xpressui-bridge' ),
		$project_slug,
	) );
	$note_html = $note !== ''
		? '<p style="margin:16px 0 0;padding:14px 16px;background:#fff5f5;border-left:3px solid #fca5a5;font-size:13px;color:#374151;line-height:1.6;">' . nl2br( esc_html( $note ) ) . '</p>'
		: '';

	return xpressui_build_submitter_email_html(
		$site_name,
		__( 'Submission update', 'xpressui-bridge' ),
		'#ef4444',
		$intro,
		$note_html,
		$footer_note,
	);
}

/**
 * @param int    $post_id
 * @param string $project_slug
 * @param string $note
 * @return string HTML email body.
 */
function xpressui_build_pending_info_body( $post_id, $project_slug, $note ) {
	$site_name   = get_bloginfo( 'name' );
	$footer_note = sprintf(
		/* translators: %s: site name */
		__( 'Sent by %s.', 'xpressui-bridge' ),
		$site_name,
	);
	$intro = esc_html( sprintf(
		/* translators: %s: project slug */
		__( 'Thank you for your submission for %s. After review, our team needs some additional information before we can proceed.', 'xpressui-bridge' ),
		$project_slug,
	) );
	$note_html = $note !== ''
		? '<p style="margin:16px 0 0;padding:14px 16px;background:#fffaf0;border-left:3px solid #f6cc87;font-size:13px;color:#374151;line-height:1.6;">' . nl2br( esc_html( $note ) ) . '</p>'
		: '';

	return xpressui_build_submitter_email_html(
		$site_name,
		__( 'Additional information required', 'xpressui-bridge' ),
		'#f59e0b',
		$intro,
		$note_html,
		$footer_note,
	);
}
