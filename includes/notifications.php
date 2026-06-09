<?php
/**
 * Email notification helpers for new submissions.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'xpressui_send_mail_async', 'xpressui_dispatch_async_mail', 10, 5 );

/**
 * Queue an email for asynchronous delivery via WP-Cron.
 *
 * @param string          $to      Recipient email.
 * @param string          $subject Email subject.
 * @param string          $body    Email HTML/text body.
 * @param array|string[]  $headers Optional headers.
 */
function xpressui_enqueue_mail( $to, $subject, $body, $headers = [], $post_id = 0 ) {
	$to      = trim( (string) $to );
	$subject = (string) $subject;
	$body    = (string) $body;
	$headers = is_array( $headers ) ? array_values( $headers ) : [];
	$post_id = (int) $post_id;

	if ( $to === '' || ! is_email( $to ) ) {
		return;
	}

	$scheduled = wp_schedule_single_event(
		time() + 1,
		'xpressui_send_mail_async',
		[ $to, $subject, $body, $headers, $post_id ]
	);

	if ( $post_id > 0 ) {
		update_post_meta( $post_id, '_xpressui_mail_status', 'queued' );
		update_post_meta( $post_id, '_xpressui_mail_error', '' );
		update_post_meta( $post_id, '_xpressui_mail_sent_at', '' );
		update_post_meta( $post_id, '_xpressui_mail_fallback_used', '0' );
		xpressui_record_submission_event(
			$post_id,
			'delivery.email.queued',
			'bridge',
			[],
			[
				'recipient_domain' => substr( strrchr( $to, '@' ) ?: '', 1 ),
			]
		);
	}

	if ( ! $scheduled ) {
		$sent = wp_mail( $to, $subject, $body, $headers );
		if ( $post_id > 0 ) {
			update_post_meta( $post_id, '_xpressui_mail_fallback_used', '1' );
			update_post_meta( $post_id, '_xpressui_mail_sent_at', gmdate( 'Y-m-d\TH:i:s\Z' ) );
			update_post_meta( $post_id, '_xpressui_mail_status', $sent ? 'sent' : 'failed' );
			if ( ! $sent ) {
				update_post_meta( $post_id, '_xpressui_mail_error', 'wp_mail() fallback failed' );
			}
			xpressui_record_submission_event(
				$post_id,
				$sent ? 'delivery.email.sent' : 'delivery.email.failed',
				'bridge',
				[],
				[
					'fallback_used' => '1',
				]
			);
		}
	}
}

/**
 * Async email worker.
 *
 * @param string         $to      Recipient email.
 * @param string         $subject Email subject.
 * @param string         $body    Email body.
 * @param array|string[] $headers Headers.
 */
function xpressui_dispatch_async_mail( $to, $subject, $body, $headers = [], $post_id = 0 ) {
	$to      = trim( (string) $to );
	$subject = (string) $subject;
	$body    = (string) $body;
	$headers = is_array( $headers ) ? array_values( $headers ) : [];
	$post_id = (int) $post_id;

	if ( $to === '' || ! is_email( $to ) ) {
		return;
	}

	$sent = wp_mail( $to, $subject, $body, $headers );
	if ( $post_id > 0 ) {
		update_post_meta( $post_id, '_xpressui_mail_status', $sent ? 'sent' : 'failed' );
		update_post_meta( $post_id, '_xpressui_mail_sent_at', gmdate( 'Y-m-d\TH:i:s\Z' ) );
		if ( ! $sent ) {
			update_post_meta( $post_id, '_xpressui_mail_error', 'wp_mail() async dispatch failed' );
		}
		xpressui_record_submission_event(
			$post_id,
			$sent ? 'delivery.email.sent' : 'delivery.email.failed',
			'bridge',
			[],
			[
				'fallback_used' => '0',
			]
		);
	}
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

	$subject = xpressui_build_notification_subject( $project_slug, $payload, $post_id );
	$body    = xpressui_build_notification_body( $post_id, $project_slug, $payload );
	$headers = xpressui_build_notification_headers();

	xpressui_enqueue_mail( $notify_email, $subject, $body, $headers, $post_id );
}

/**
 * When the submitter opted in (the `emailMeCopy` field is checked), email THEM the
 * same field-by-field notification the admin receives — minus the admin-only
 * "Review submission" button (they have no wp-admin access). Independent of the
 * admin notifyEmail setting.
 *
 * @param int          $post_id      Submission post ID.
 * @param string       $project_slug Project slug.
 * @param array|string $payload      Submitted payload (incl. files).
 */
function xpressui_maybe_send_submitter_sample( $post_id, $project_slug, $payload ) {
	if ( ! is_array( $payload ) ) {
		return;
	}
	$opt_in = $payload['emailMeCopy'] ?? false;
	$opt_in = in_array( $opt_in, [ true, 1, '1', 'true', 'yes', 'on' ], true );
	if ( ! $opt_in ) {
		return;
	}

	$to_email = trim( (string) ( $payload['email'] ?? '' ) );
	if ( $to_email === '' || ! is_email( $to_email ) ) {
		return;
	}

	$site_name = get_bloginfo( 'name' );
	$label     = xpressui_get_submitter_workflow_label( $project_slug );
	/* translators: 1: site name, 2: workflow label */
	$subject = sprintf( __( '[%1$s] Your %2$s submission', 'xpressui-bridge' ), $site_name, $label !== '' ? $label : $project_slug );
	$body    = xpressui_build_notification_body( $post_id, $project_slug, $payload, false );
	$headers = xpressui_build_notification_headers();

	xpressui_enqueue_mail( $to_email, $subject, $body, $headers, $post_id );
}

/**
 * Sends an admin notification email when a submitter resubmits requested files
 * or corrections for an existing pending_info submission.
 *
 * @param int          $post_id      Submission post ID.
 * @param string       $project_slug Project slug.
 * @param array|string $payload      Updated payload.
 */
function xpressui_maybe_send_resubmitted_notification( $post_id, $project_slug, $payload ) {
	$notify_email = xpressui_get_project_setting( $project_slug, 'notifyEmail' );
	if ( $notify_email === '' || ! is_email( $notify_email ) ) {
		return;
	}

	$subject = xpressui_build_resubmitted_notification_subject( $project_slug, $payload, $post_id );
	$body    = xpressui_build_resubmitted_notification_body( $post_id, $project_slug, $payload );
	$headers = xpressui_build_notification_headers();

	xpressui_enqueue_mail( $notify_email, $subject, $body, $headers, $post_id );
}

// ---------------------------------------------------------------------------
// Email builders
// ---------------------------------------------------------------------------

/**
 * @param string       $project_slug
 * @param array|string $payload
 * @param int          $post_id
 * @return string
 */
function xpressui_build_notification_subject( $project_slug, $payload, $post_id = 0 ) {
	$site_name = get_bloginfo( 'name' );
	$contact   = is_array( $payload ) ? xpressui_get_contact_summary( $payload ) : '';
	$reference = xpressui_build_notification_subject_reference( $post_id, $payload );

	if ( $contact !== '' && $reference !== '' ) {
		/* translators: 1: site name, 2: project slug, 3: contact name/email, 4: submission reference */
		return sprintf( __( '[%1$s] New %2$s submission - %3$s - %4$s', 'xpressui-bridge' ), $site_name, $project_slug, $contact, $reference );
	}
	if ( $contact !== '' ) {
		/* translators: 1: site name, 2: project slug, 3: contact name/email */
		return sprintf( __( '[%1$s] New %2$s submission - %3$s', 'xpressui-bridge' ), $site_name, $project_slug, $contact );
	}
	if ( $reference !== '' ) {
		/* translators: 1: site name, 2: project slug, 3: submission reference */
		return sprintf( __( '[%1$s] New %2$s submission - %3$s', 'xpressui-bridge' ), $site_name, $project_slug, $reference );
	}
	/* translators: 1: site name, 2: project slug */
	return sprintf( __( '[%1$s] New %2$s submission', 'xpressui-bridge' ), $site_name, $project_slug );
}

/**
 * @param string       $project_slug
 * @param array|string $payload
 * @param int          $post_id
 * @return string
 */
function xpressui_build_resubmitted_notification_subject( $project_slug, $payload, $post_id = 0 ) {
	$site_name = get_bloginfo( 'name' );
	$contact   = is_array( $payload ) ? xpressui_get_contact_summary( $payload ) : '';
	$reference = xpressui_build_notification_subject_reference( $post_id, $payload );

	if ( $contact !== '' && $reference !== '' ) {
		/* translators: 1: site name, 2: project slug, 3: contact name/email, 4: submission reference */
		return sprintf( __( '[%1$s] %2$s submission updated - %3$s - %4$s', 'xpressui-bridge' ), $site_name, $project_slug, $contact, $reference );
	}
	if ( $contact !== '' ) {
		/* translators: 1: site name, 2: project slug, 3: contact name/email */
		return sprintf( __( '[%1$s] %2$s submission updated - %3$s', 'xpressui-bridge' ), $site_name, $project_slug, $contact );
	}
	if ( $reference !== '' ) {
		/* translators: 1: site name, 2: project slug, 3: submission reference */
		return sprintf( __( '[%1$s] %2$s submission updated - %3$s', 'xpressui-bridge' ), $site_name, $project_slug, $reference );
	}
	/* translators: 1: site name, 2: project slug */
	return sprintf( __( '[%1$s] %2$s submission updated', 'xpressui-bridge' ), $site_name, $project_slug );
}

/**
 * Builds a short stable reference for email subjects so mailbox clients do not
 * group unrelated submissions into the same conversation.
 *
 * @param int          $post_id Submission post ID.
 * @param array|string $payload Submitted form data.
 * @return string
 */
function xpressui_build_notification_subject_reference( $post_id, $payload ) {
	if ( is_array( $payload ) ) {
		$submission_id = isset( $payload['submissionId'] ) && is_scalar( $payload['submissionId'] )
			? sanitize_text_field( (string) $payload['submissionId'] )
			: '';

		if ( $submission_id !== '' ) {
			return 'ID ' . substr( $submission_id, -8 );
		}
	}

	$post_id = absint( $post_id );
	if ( $post_id > 0 ) {
		return '#' . $post_id;
	}

	return '';
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
		'emailMeCopy',
	];
}

/**
 * @param int          $post_id
 * @param string       $project_slug
 * @param array|string $payload
 * @return string HTML email body.
 */
function xpressui_build_notification_body( $post_id, $project_slug, $payload, $include_admin_link = true ) {
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
		if ( is_array( $slug ) ) {
			foreach ( [ 'label', 'name', 'title' ] as $k ) {
				if ( isset( $slug[ $k ] ) && is_scalar( $slug[ $k ] ) && (string) $slug[ $k ] !== '' ) {
					return esc_html( (string) $slug[ $k ] );
				}
			}
			$resolved = '';
			foreach ( [ 'value', 'id' ] as $k ) {
				if ( isset( $slug[ $k ] ) && is_scalar( $slug[ $k ] ) && (string) $slug[ $k ] !== '' ) {
					$resolved = (string) $slug[ $k ];
					break;
				}
			}
			$slug = $resolved !== '' ? $resolved : (string) reset( $slug );
		}

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

		// Returns [ 'name', 'url', 'thumb_url' ] for a single uploaded-file entry.
		$file_info = static function ( $file ) {
			$attachment_id = (int) ( $file['attachmentId'] ?? 0 );
			$thumb_url     = '';
			if ( $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ) {
				$src = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
				if ( $src ) {
					$thumb_url = $src[0];
				}
			}
			return [
				'name'      => (string) ( $file['originalName'] ?? 'file' ),
				'url'       => (string) ( $file['url'] ?? '' ),
				'thumb_url' => $thumb_url,
			];
		};

		// Renders a list of uploaded-file objects as either an image grid or a file list.
		$render_file_list = static function ( array $files ) use ( $file_info ) {
			$images = [];
			$docs   = [];
			foreach ( $files as $file ) {
				$info = $file_info( $file );
				if ( $info['thumb_url'] !== '' ) {
					$images[] = $info;
				} else {
					$docs[] = $info;
				}
			}

			$html = '';

			// Images: grid of 3 per row using table.
			if ( ! empty( $images ) ) {
				$html .= '<table cellpadding="0" cellspacing="0" style="border-collapse:separate;border-spacing:6px 6px;">';
				$chunks = array_chunk( $images, 3 );
				foreach ( $chunks as $row ) {
					$html .= '<tr>';
					foreach ( $row as $img ) {
						$name = esc_html( $img['name'] );
						$url  = esc_url( $img['url'] );
						$html .= '<td style="vertical-align:top;text-align:center;">'
							. '<a href="' . $url . '"><img src="' . esc_url( $img['thumb_url'] ) . '" alt="' . $name . '" style="width:80px;height:60px;object-fit:cover;border-radius:4px;border:1px solid #e5e7eb;display:block;"></a>'
							. '<div style="margin-top:3px;font-size:10px;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><a href="' . $url . '" style="color:#2563eb;">' . $name . '</a></div>'
							. '</td>';
					}
					// Pad incomplete last row so columns stay aligned.
					for ( $p = count( $row ); $p < 3; $p++ ) {
						$html .= '<td style="width:80px;"></td>';
					}
					$html .= '</tr>';
				}
				$html .= '</table>';
			}

			// Documents / non-image files: compact list.
			if ( ! empty( $docs ) ) {
				$html .= '<div style="margin-top:' . ( $images ? '8px' : '0' ) . ';">';
				foreach ( $docs as $doc ) {
					$name  = esc_html( $doc['name'] );
					$url   = esc_url( $doc['url'] );
					$html .= '<div style="margin-bottom:4px;">&#128196; <a href="' . $url . '" style="color:#2563eb;">' . $name . '</a></div>';
				}
				$html .= '</div>';
			}

			return $html;
		};

		$type = (string) ( $field_meta['type'] ?? '' );
		if ( in_array( $type, [ 'repeater', 'quiz', 'product-list', 'select-image' ], true ) ) {
			$display = xpressui_format_submission_value( $value, $field_meta );
		} elseif ( is_array( $value ) ) {
			if ( ( $value['kind'] ?? '' ) === 'uploaded-file' ) {
				$display = $render_file_list( [ $value ] );
			} elseif ( isset( $value[0] ) && is_array( $value[0] ) && ( $value[0]['kind'] ?? '' ) === 'uploaded-file' ) {
				$display = $render_file_list( $value );
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
		} elseif ( is_string( $value ) && ( str_starts_with( $value, 'data:image/' ) || ( $field_meta['type'] ?? '' ) === 'signature' ) && $value !== '' ) {
			$img_src = str_starts_with( $value, 'data:image/' ) ? esc_attr( $value ) : esc_url( $value );
			$display = '<img src="' . $img_src . '" alt="' . esc_attr__( 'Signature', 'xpressui-bridge' ) . '" style="max-width:220px;max-height:110px;border:1px solid #e5e7eb;border-radius:4px;background:#fff;">';
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
				? xpressui_clean_field_label( $field_meta['label'] )
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

	// Admin-only "Review submission" button — omitted for submitter sample copies
	// (the submitter has no wp-admin access).
	$cta_button = $include_admin_link
		? '<a href="' . $admin_url . '" style="display:inline-block;padding:10px 20px;background:#2271b1;color:#ffffff;text-decoration:none;border-radius:4px;font-size:13px;font-weight:600;">'
			. esc_html__( 'Review submission', 'xpressui-bridge' )
			. '</a>'
		: '';

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
		. esc_html( sprintf( __( 'New submission: %s', 'xpressui-bridge' ), $project_slug ) )
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
		. $cta_button
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
 * @param int          $post_id
 * @param string       $project_slug
 * @param array|string $payload
 * @return string
 */
function xpressui_build_resubmitted_notification_body( $post_id, $project_slug, $payload ) {
	$body = xpressui_build_notification_body( $post_id, $project_slug, $payload );

	$banner = '<tr><td style="padding:18px 28px;background:#eff6ff;border-bottom:1px solid #dbeafe;">'
		. '<p style="margin:0;font-size:13px;font-weight:700;color:#1d4ed8;">'
		. esc_html__( 'The submitter has provided the requested corrections or files.', 'xpressui-bridge' )
		. '</p>'
		. '</td></tr>';

	return str_replace(
		'<tr><td style="padding:20px 28px;border-bottom:1px solid #f0f0f0;">',
		$banner . '<tr><td style="padding:20px 28px;border-bottom:1px solid #f0f0f0;">',
		$body
	);
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
// Submission confirmation email helpers
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
 * Resolve the submitter-facing workflow label.
 *
 * @param string $project_slug Project slug.
 * @return string
 */
function xpressui_get_submitter_workflow_label( $project_slug ) {
	$slug = sanitize_title( (string) $project_slug );
	if ( $slug === '' ) {
		return '';
	}

	$project_name = '';
	if ( function_exists( 'xpressui_get_workflow_manifest_meta' ) ) {
		$manifest = xpressui_get_workflow_manifest_meta( $slug );
		if ( is_array( $manifest ) ) {
			$project_name = sanitize_text_field( (string) ( $manifest['projectName'] ?? '' ) );
		}
	}

	return $project_name !== '' ? $project_name : $slug;
}

/**
 * Resolve the submitter email brand line shown in the header.
 *
 * @param string $project_slug Project slug.
 * @return string
 */
function xpressui_get_submitter_email_header_name( $project_slug ) {
	$site_name      = get_bloginfo( 'name' );
	$workflow_label = xpressui_get_submitter_workflow_label( $project_slug );

	return $site_name . ' / ' . $workflow_label;
}

/**
 * Shared HTML email shell. $accent_color and $header_label set the visual tone.
 * $intro_text and $note_html are injected into the body.
 */
function xpressui_build_submitter_email_html( $site_name, $header_label, $accent_color, $intro_text, $note_html, $footer_note, $cta_html = '' ) {
	$action_html = $cta_html !== '' ? $cta_html : '';

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
		. $action_html
		. '</td></tr>'
		. '<tr><td style="padding:16px 28px;background:#f9fafb;border-top:1px solid #f0f0f0;">'
		. '<p style="margin:0;font-size:11px;color:#d1d5db;">' . esc_html( $footer_note ) . '</p>'
		. '</td></tr>'
		. '</table>'
		. '</td></tr></table>'
		. '</body></html>';
}

/**
 * Send a confirmation email to the submitter on their first (non-resume) submission.
 * Silently skips if confirmations are disabled, no email field is present, or the
 * payload contains a resume token (resubmission).
 *
 * @param int          $post_id      Submission post ID.
 * @param string       $project_slug
 * @param array|string $payload      Submitted payload (already stored).
 */
function xpressui_maybe_send_submit_confirmation( $post_id, $project_slug, $payload ) {
	if ( ! xpressui_get_project_setting_flag( $project_slug, 'notifySubmitterOnSubmit', false ) ) {
		return;
	}
	$to_email = xpressui_get_submitter_email( $post_id );
	if ( $to_email === '' ) {
		return;
	}

	$site_name     = get_bloginfo( 'name' );
	$header_name   = xpressui_get_submitter_email_header_name( $project_slug );
	$workflow_label = xpressui_get_submitter_workflow_label( $project_slug );
	$all_settings = get_option( 'xpressui_project_settings', [] );
	$s            = is_array( $all_settings[ $project_slug ] ?? null ) ? $all_settings[ $project_slug ] : [];

	$custom_message = (string) ( $s['submitConfirmationMessage'] ?? '' );

	$intro = $custom_message !== ''
		? esc_html( $custom_message )
		: esc_html( sprintf(
			/* translators: %s: project name / slug */
			__( 'Thank you for your submission for %s. We have received it and will review it shortly.', 'xpressui-bridge' ),
			$project_slug,
		) );

	$booking_url = trim( (string) ( $s['bookingUrl'] ?? '' ) );
	$booking_cta_html = '';
	if ( $booking_url !== '' ) {
		$_raw_btn_label    = trim( (string) ( $s['bookingButtonLabel'] ?? '' ) );
		$booking_btn_label = '' !== $_raw_btn_label ? $_raw_btn_label : __( 'Book an appointment', 'xpressui-bridge' );
		$booking_cta_html  = '<div style="margin:24px 0 0;text-align:center;">'
			. '<a href="' . esc_url( $booking_url ) . '" style="display:inline-block;padding:12px 24px;background:#2271b1;color:#ffffff;text-decoration:none;border-radius:6px;font-size:14px;font-weight:600;">'
			. esc_html( $booking_btn_label )
			. '</a>'
			. '</div>';
	}

	$footer_note = sprintf(
		/* translators: %s: site name */
		__( 'Sent by %s.', 'xpressui-bridge' ),
		$site_name,
	);

	// Allow extensions (e.g. Pro) to append extra HTML to the confirmation email (e.g. status tracking link).
	$extra_cta_html = (string) apply_filters( 'xpressui_submit_confirmation_extra_html', '', $post_id, $project_slug );

	$subject = sprintf(
		/* translators: 1: site name, 2: workflow label */
		__( '[%1$s / %2$s] We received your submission', 'xpressui-bridge' ),
		$site_name,
		$workflow_label,
	);
	$body    = xpressui_build_submitter_email_html(
		$header_name,
		__( 'Submission received', 'xpressui-bridge' ),
		'#2271b1',
		$intro,
		'',
		$footer_note,
		$booking_cta_html . $extra_cta_html,
	);
	$headers = xpressui_build_notification_headers();

	xpressui_enqueue_mail( $to_email, $subject, $body, $headers, $post_id );
}
