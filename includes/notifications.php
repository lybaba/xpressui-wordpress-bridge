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
 * @param string $key          Setting key ('notifyEmail', 'redirectUrl', …).
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

	// ── Field rows ──────────────────────────────────────────────────────────
	$rows_html = '';
	if ( is_array( $payload ) ) {
		$i = 0;
		foreach ( $payload as $key => $value ) {
			if ( in_array( $key, $skip, true ) ) {
				continue;
			}
			$label = isset( $field_index[ $key ]['label'] ) && $field_index[ $key ]['label'] !== ''
				? $field_index[ $key ]['label']
				: $key;
			if ( is_array( $value ) ) {
				if ( ( $value['kind'] ?? '' ) === 'uploaded-file' ) {
					$display = esc_html( (string) ( $value['originalName'] ?? 'file' ) );
				} else {
					$display = '<code style="font-size:12px;color:#555;">' . esc_html( wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) . '</code>';
				}
			} elseif ( is_bool( $value ) ) {
				$display = esc_html( $value ? __( 'Yes', 'xpressui-bridge' ) : __( 'No', 'xpressui-bridge' ) );
			} else {
				$display = nl2br( esc_html( (string) $value ) );
			}
			$bg        = $i % 2 === 0 ? '#ffffff' : '#f9fafb';
			$rows_html .= '<tr style="background:' . $bg . ';">'
				. '<td style="padding:9px 14px;font-size:12px;color:#6b7280;white-space:nowrap;border-bottom:1px solid #f0f0f0;width:170px;vertical-align:top;">' . esc_html( $label ) . '</td>'
				. '<td style="padding:9px 14px;font-size:13px;color:#111827;border-bottom:1px solid #f0f0f0;word-break:break-word;">' . $display . '</td>'
				. '</tr>';
			$i++;
		}
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
		. esc_html( sprintf( __( 'New submission — %s', 'xpressui-bridge' ), $project_slug ) )
		. '</p></td></tr>'

		// Meta
		. '<tr><td style="padding:20px 28px;border-bottom:1px solid #f0f0f0;">'
		. '<table cellpadding="0" cellspacing="0" width="100%">' . $meta_rows . '</table>'
		. '</td></tr>'

		// Fields
		. '<tr><td style="padding:20px 28px 0;">'
		. '<p style="margin:0 0 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#9ca3af;">'
		. esc_html__( 'Submitted fields', 'xpressui-bridge' )
		. '</p>'
		. '</td></tr>'
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
