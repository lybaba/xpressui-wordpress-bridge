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
 * @param int          $post_id
 * @param string       $project_slug
 * @param array|string $payload
 * @return string Plain-text email body.
 */
function xpressui_build_notification_body( $post_id, $project_slug, $payload ) {
	$lines   = [];
	$lines[] = sprintf( __( 'A new submission has been received for project: %s', 'xpressui-bridge' ), $project_slug );
	$lines[] = '';
	$lines[] = sprintf( __( 'Submission ID : %d', 'xpressui-bridge' ), $post_id );
	$lines[] = sprintf( __( 'Date          : %s', 'xpressui-bridge' ), wp_date( 'Y-m-d H:i T' ) );

	if ( is_array( $payload ) ) {
		$contact = xpressui_get_contact_summary( $payload );
		if ( $contact !== '' ) {
			$lines[] = sprintf( __( 'Contact       : %s', 'xpressui-bridge' ), $contact );
		}

		$lines[] = '';
		$lines[] = __( 'Submitted fields', 'xpressui-bridge' );
		$lines[] = str_repeat( '-', 40 );

		foreach ( $payload as $key => $value ) {
			if ( is_array( $value ) ) {
				if ( ( $value['kind'] ?? '' ) === 'uploaded-file' ) {
					$lines[] = sprintf( '%s : %s', $key, (string) ( $value['originalName'] ?? 'file' ) );
					continue;
				}
				$lines[] = sprintf( '%s : %s', $key, wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
				continue;
			}
			if ( is_bool( $value ) ) {
				$lines[] = sprintf( '%s : %s', $key, $value ? __( 'Yes', 'xpressui-bridge' ) : __( 'No', 'xpressui-bridge' ) );
				continue;
			}
			$lines[] = sprintf( '%s : %s', $key, (string) $value );
		}
	}

	$lines[] = '';
	$lines[] = str_repeat( '-', 40 );
	$admin_url = add_query_arg(
		[
			'post'   => $post_id,
			'action' => 'edit',
		],
		admin_url( 'post.php' )
	);
	$lines[] = sprintf( __( 'Review this submission in wp-admin:', 'xpressui-bridge' ) );
	$lines[] = $admin_url;
	$lines[] = '';
	$lines[] = sprintf(
		/* translators: %s: site name */
		__( 'This notification was sent by the XPressUI Bridge plugin on %s.', 'xpressui-bridge' ),
		get_bloginfo( 'name' )
	);

	return implode( "\r\n", $lines );
}

/**
 * @return string[]
 */
function xpressui_build_notification_headers() {
	return [
		'Content-Type: text/plain; charset=UTF-8',
	];
}
