<?php
/**
 * [xpressui] shortcode — embeds an installed XPressUI workflow package inline.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the "Reference documents" collapsible block from a synced Hosted Link
 * presentation, for parity with the SaaS hosted-link render. Returns '' when the
 * link has no reference documents.
 *
 * @param array  $presentation Hosted Link presentation (from link.config.json).
 * @param string $locale       'fr' or 'en' (for the default section title).
 * @return string HTML (kses-safe: details/summary/section/div/span/a/svg).
 */
function xpressui_render_reference_documents( $presentation, $locale = 'en' ) {
	if ( ! is_array( $presentation ) || ! is_array( $presentation['referenceDocuments'] ?? null ) ) {
		return '';
	}

	$documents = [];
	foreach ( $presentation['referenceDocuments'] as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$url = trim( (string) ( $item['url'] ?? '' ) );
		if ( $url === '' || ( stripos( $url, 'http://' ) !== 0 && stripos( $url, 'https://' ) !== 0 ) ) {
			continue;
		}
		$path = strtok( strtolower( $url ), '?#' );
		$ext  = ( $path !== false && strrpos( $path, '.' ) !== false ) ? substr( $path, strrpos( $path, '.' ) + 1 ) : '';
		if ( 'pdf' === $ext ) {
			$kind = 'pdf';
		} elseif ( in_array( $ext, [ 'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg' ], true ) ) {
			$kind = 'image';
		} elseif ( in_array( $ext, [ 'zip', 'rar', '7z', 'tar', 'gz', 'tgz' ], true ) ) {
			$kind = 'archive';
		} else {
			$kind = 'file';
		}
		$documents[] = [
			'name'     => trim( (string) ( $item['name'] ?? '' ) ),
			'url'      => $url,
			'kind'     => $kind,
			'filename' => rawurldecode( (string) basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ) ),
		];
		if ( count( $documents ) >= 25 ) {
			break;
		}
	}

	if ( empty( $documents ) ) {
		return '';
	}

	$configured_title = trim( (string) ( $presentation['referenceDocumentsTitle'] ?? '' ) );
	$title            = '' !== $configured_title
		? $configured_title
		: ( 'fr' === $locale ? __( 'Documents de référence', 'xpressui-bridge' ) : __( 'Reference documents', 'xpressui-bridge' ) );

	$icons = [
		'pdf'     => '<svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true"><path d="M20 2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM9.5 11.5c0 .83-.67 1.5-1.5 1.5H7v2H5.5V9H8c.83 0 1.5.67 1.5 1.5v1zm5 2c0 .83-.67 1.5-1.5 1.5h-2.5V9H13c.83 0 1.5.67 1.5 1.5v4zm4-3H17v1h1.5V14H17v2h-1.5V9h3v1.5zM7 11.5h1v-1H7v1zm5 0h1v3h-1v-3zM4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6z"></path></svg>',
		'image'   => '<svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"></path></svg>',
		'archive' => '<svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true"><path d="M22 8c0-1.1-.9-2-2-2h-8l-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h7v-2h2v2h7c1.1 0 2-.9 2-2V8zm-9 2h-2V8h2v2zm0 4h-2v-2h2v2zm0 4h-2v-2h2v2z"></path></svg>',
		'file'    => '<svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28" aria-hidden="true"><path d="M6 2c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6H6zm7 7V3.5L18.5 9H13z"></path></svg>',
	];
	$colors  = [ 'pdf' => '#b91c1c', 'image' => '#2563eb', 'archive' => '#ca8a04', 'file' => '#64748b' ];
	$ext_svg = '<svg class="xpressui-ref-docs__ext" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>';

	ob_start();
	?>
<style>
.xpressui-ref-docs{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin:0 0 1.25rem;overflow:hidden;font-size:.9rem;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.xpressui-ref-docs summary{list-style:none;cursor:pointer;display:flex;align-items:center;gap:.75rem;padding:.85rem 1.25rem;font-weight:700;color:#0f172a}
.xpressui-ref-docs summary::-webkit-details-marker{display:none}
.xpressui-ref-docs__count{margin-left:auto;color:#64748b;font-weight:700;font-size:.82rem}
.xpressui-ref-docs__chev{flex:0 0 auto;width:.5rem;height:.5rem;border-right:2px solid #64748b;border-bottom:2px solid #64748b;transform:rotate(45deg);transition:transform .16s ease}
.xpressui-ref-docs[open] summary .xpressui-ref-docs__chev{transform:rotate(225deg)}
.xpressui-ref-docs__body{border-top:1px solid #e2e8f0;padding:.2rem .85rem .35rem}
.xpressui-ref-docs__row+.xpressui-ref-docs__row{border-top:1px solid #e2e8f0}
.xpressui-ref-docs__link{display:flex;align-items:center;gap:.7rem;width:100%;min-width:0;padding:.6rem .35rem;color:#0f172a;text-decoration:none!important;border-radius:8px}
.xpressui-ref-docs__link:hover{background:#f1f5f9}
.xpressui-ref-docs__icon{flex:0 0 auto;display:inline-flex;width:28px;height:28px}
.xpressui-ref-docs__icon svg{width:28px;height:28px;display:block}
.xpressui-ref-docs__text{display:grid;gap:.08rem;min-width:0;flex:1 1 auto}
.xpressui-ref-docs__name{min-width:0;color:#0f172a;font-weight:700;line-height:1.25;word-break:break-word}
.xpressui-ref-docs__file{color:#64748b;font-size:.78rem;line-height:1.2;word-break:break-all}
.xpressui-ref-docs__ext{flex:0 0 auto;color:#94a3b8;align-self:center}
</style>
<details class="xpressui-ref-docs" open>
	<summary>
		<span><?php echo esc_html( $title ); ?></span>
		<span class="xpressui-ref-docs__count"><?php echo (int) count( $documents ); ?></span>
		<span class="xpressui-ref-docs__chev" aria-hidden="true"></span>
	</summary>
	<div class="xpressui-ref-docs__body">
		<?php foreach ( $documents as $doc ) : ?>
		<div class="xpressui-ref-docs__row">
			<a class="xpressui-ref-docs__link" href="<?php echo esc_url( $doc['url'] ); ?>" target="_blank" rel="noopener noreferrer nofollow">
				<span class="xpressui-ref-docs__icon" style="color:<?php echo esc_attr( $colors[ $doc['kind'] ] ); ?>"><?php echo $icons[ $doc['kind'] ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG ?></span>
				<span class="xpressui-ref-docs__text">
					<span class="xpressui-ref-docs__name"><?php echo esc_html( '' !== $doc['name'] ? $doc['name'] : $doc['url'] ); ?></span>
					<?php if ( '' !== $doc['filename'] && $doc['filename'] !== $doc['name'] ) : ?>
					<span class="xpressui-ref-docs__file"><?php echo esc_html( $doc['filename'] ); ?></span>
					<?php endif; ?>
				</span>
				<?php echo $ext_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG ?>
			</a>
		</div>
		<?php endforeach; ?>
	</div>
</details>
	<?php
	return (string) ob_get_clean();
}

/**
 * Renders the [xpressui id="project-slug"] shortcode.
 *
 * Inline rendering: form HTML and CSS are output directly into the page.
 * The runtime UMD and init script are enqueued from plugin assets (never
 * from the uploads directory), satisfying WordPress.org guideline 8.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string HTML output.
 */
function xpressui_render_shortcode( $atts ) {
	$atts = shortcode_atts(
		[
			'id'      => '',
			'link'    => '',
			'link_id' => '',
			'title'   => __( 'XPressUI Form', 'xpressui-bridge' ),
		],
		$atts,
		'xpressui'
	);

	$link_attr = isset( $atts['link'] ) && '' !== $atts['link']
		? sanitize_file_name( (string) $atts['link'] )
		: ( isset( $atts['link_id'] ) && '' !== $atts['link_id'] ? sanitize_file_name( (string) $atts['link_id'] ) : '' );

	$slug = '';
	$link_config = null;

	if ( $link_attr !== '' ) {
		$base_dir = xpressui_get_workflows_base_dir();
		if ( $base_dir !== '' && is_dir( $base_dir ) ) {
			$subdirs = glob( trailingslashit( $base_dir ) . '*', GLOB_ONLYDIR );
			if ( is_array( $subdirs ) ) {
				foreach ( $subdirs as $subdir ) {
					$potential_slug = basename( $subdir );
					$config_file = trailingslashit( $subdir ) . 'hosted-links/' . $link_attr . '/link.config.json';
					if ( file_exists( $config_file ) ) {
						$slug = $potential_slug;
						$link_config_raw = file_get_contents( $config_file );
						if ( is_string( $link_config_raw ) ) {
							$link_config = json_decode( $link_config_raw, true );
						}
						break;
					}
				}
			}
		}
	} else {
		$slug = sanitize_title( (string) $atts['id'] );
	}

	if ( $slug === '' ) {
		return wp_kses_post(
			'<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: the "id" or "link" attribute is required.', 'xpressui-bridge' )
			. '</p>'
		);
	}

	if ( is_array( $link_config ) ) {
		$link_status = $link_config['status'] ?? 'active';

		$expires_at = ! empty( $link_config['expiresAt'] ) ? strtotime( $link_config['expiresAt'] ) : null;
		if ( $expires_at && $expires_at <= time() ) {
			$link_status = 'expired';
		}

		$max_subs = isset( $link_config['maxSubmissions'] ) ? (int) $link_config['maxSubmissions'] : 0;
		$sub_count = isset( $link_config['submissionCount'] ) ? (int) $link_config['submissionCount'] : 0;
		if ( $max_subs > 0 && $sub_count >= $max_subs ) {
			$link_status = 'used_up';
		}

		if ( 'active' !== $link_status ) {
			$close_message = '';
			if ( 'paused' === $link_status ) {
				$close_msg = (string) ( $link_config['payload']['closeMessage'] ?? '' );
				$close_message = $close_msg !== '' ? $close_msg : __( 'This form is currently paused.', 'xpressui-bridge' );
			} elseif ( 'maintenance' === $link_status ) {
				$maintenance_msg = (string) ( $link_config['payload']['maintenanceMessage'] ?? '' );
				$close_message = $maintenance_msg !== '' ? $maintenance_msg : __( 'This form is under maintenance.', 'xpressui-bridge' );
			} elseif ( 'expired' === $link_status ) {
				$close_message = __( 'This link has expired.', 'xpressui-bridge' );
			} elseif ( 'used_up' === $link_status ) {
				$close_message = __( 'This link has already reached its maximum submission limit.', 'xpressui-bridge' );
			} else {
				$close_message = __( 'This form is currently unavailable.', 'xpressui-bridge' );
			}
			return wp_kses_post(
				'<div class="xpressui-embed-wrapper xpressui-inline-embed xpressui-link-closed">'
				. '<div class="xpressui-closed-card">'
				. '<p style="text-align:center;padding:24px;background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;border-radius:8px;font-weight:500;">'
				. esc_html( $close_message )
				. '</p>'
				. '</div>'
				. '</div>'
			);
		}
	}

	$base_dir = xpressui_get_workflows_base_dir();
	if ( $base_dir === '' ) {
		return wp_kses_post(
			'<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: could not resolve the uploads directory.', 'xpressui-bridge' )
			. '</p>'
		);
	}

	$package_dir = xpressui_get_workflow_package_dir( $slug );

	if ( ! is_dir( $package_dir ) || ! xpressui_workflow_directory_has_required_artifacts( $package_dir ) ) {
		return wp_kses_post(
			'<p class="xpressui-embed-error">'
			. esc_html(
				sprintf(
					/* translators: %s: project slug */
					__( '[xpressui] error: no package found for id "%s". Please install the workflow package first.', 'xpressui-bridge' ),
					$slug
				)
			)
			. '</p>'
		);
	}

	// Load template context from uploads — JSON data only, no executable code.
	$template_context = xpressui_load_workflow_template_context( $slug );
	if ( empty( $template_context ) ) {
		// No template.context.json — build a minimal skeleton; rendered_form will be
		// populated from form.config.json below if that file exists.
		$template_context = [
			'project' => [ 'id' => $slug, 'slug' => $slug, 'name' => $slug ],
			'theme'   => [],
			'target'  => 'wordpress',
			'runtime' => [],
		];
	}

	// Allow extensions (e.g. the pro plugin) to modify the template context before rendering.
	$template_context = apply_filters( 'xpressui_template_context', $template_context, $slug );

	$show_project_title  = xpressui_get_project_setting_flag( $slug, 'showProjectTitle', false );
	$show_required_note  = xpressui_get_project_setting_flag( $slug, 'showRequiredFieldsNote', false );
	$section_label_visibility = xpressui_get_project_setting_choice( $slug, 'sectionLabelVisibility', [ 'auto', 'show', 'hide' ], 'auto' );
	$section_count = 0;
	if ( is_array( $template_context['rendered_form']['sections'] ?? null ) ) {
		$section_count = count( $template_context['rendered_form']['sections'] );
	}
	$show_section_headers = 'show' === $section_label_visibility
		? true
		: ( 'hide' === $section_label_visibility ? false : $section_count > 1 );
	// Resolve form config: prefer inlined runtime.form_config_json, fall back to form.config.json.
	$raw_form_config_json = $template_context['runtime']['form_config_json'] ?? '';
	if ( '' === $raw_form_config_json ) {
		$raw_form_config_json = xpressui_get_workflow_artifact_contents( $slug, 'config', 'form.config.json' );
	}
	$form_config = is_string( $raw_form_config_json ) && '' !== $raw_form_config_json
		? json_decode( $raw_form_config_json, true )
		: null;

	if ( is_array( $form_config ) ) {
		$form_config = xpressui_normalize_form_config( $form_config, $slug );

		if ( is_array( $link_config ) ) {
			$presentation = is_array( $link_config['presentation'] ?? null ) ? $link_config['presentation'] : [];
			$payload_data = is_array( $link_config['payload'] ?? null ) ? $link_config['payload'] : [];

			if ( ! is_array( $form_config['workflowConfig'] ?? null ) ) {
				$form_config['workflowConfig'] = [];
			}

			if ( ! empty( $presentation['successMessage'] ) ) {
				$form_config['workflowConfig']['successMessage'] = $presentation['successMessage'];
			}
			if ( ! empty( $presentation['successRedirectUrl'] ) ) {
				$form_config['workflowConfig']['redirectUrl'] = $presentation['successRedirectUrl'];
			}
			if ( ! empty( $presentation['bookingUrl'] ) ) {
				$form_config['workflowConfig']['bookingUrl'] = $presentation['bookingUrl'];
			}
			if ( ! empty( $presentation['bookingButtonLabel'] ) ) {
				$form_config['workflowConfig']['bookingButtonLabel'] = $presentation['bookingButtonLabel'];
			}
			if ( ! empty( $payload_data['notifyEmails'] ) ) {
				$form_config['workflowConfig']['notifyEmail'] = implode( ',', (array) $payload_data['notifyEmails'] );
			}
			if ( ! empty( $payload_data['webhookUrl'] ) ) {
				$form_config['workflowConfig']['webhookUrl'] = $payload_data['webhookUrl'];
			}
			if ( ! is_array( $form_config['submit'] ?? null ) ) {
				$form_config['submit'] = [];
			}
			if ( ! is_array( $form_config['submit']['metadata'] ?? null ) ) {
				$form_config['submit']['metadata'] = [];
			}
			$form_config['submit']['metadata']['hostedLinkId'] = $link_config['id'];

			// Override properties in template context
			if ( ! empty( $link_config['label'] ) ) {
				$template_context['project']['name'] = $link_config['label'];
			}
			if ( ! empty( $presentation['backgroundImageUrl'] ) ) {
				$template_context['project']['background_image_url'] = $presentation['backgroundImageUrl'];
			}
			if ( ! empty( $presentation['accentColor'] ) ) {
				$template_context['theme']['colors']['primary'] = $presentation['accentColor'];
			}
			if ( ! empty( $presentation['pageTemplate'] ) ) {
				$template_context['theme']['frame_style'] = $presentation['pageTemplate'] === 'panel' ? 'panel' : 'card';
			}
		} else {
			if ( ! is_array( $form_config['workflowConfig'] ?? null ) ) {
				$form_config['workflowConfig'] = [];
			}
			if ( empty( $form_config['workflowConfig']['redirectUrl'] ) ) {
				// No hosted link (default render) → redirect to the site home.
				$form_config['workflowConfig']['redirectUrl'] = home_url( '/' );
			}
		}

		$fresh_rendered_form = xpressui_build_rendered_form_from_config( $form_config );
		// Refresh rendered_form sections from the live form config so shortcode output
		// stays aligned with the current runtime even when template.context.json is stale.
		if ( ! is_array( $template_context['rendered_form'] ?? null ) ) {
			$template_context['rendered_form'] = $fresh_rendered_form;
		} elseif ( is_array( $fresh_rendered_form['sections'] ?? null ) ) {
			$template_context['rendered_form']['sections'] = $fresh_rendered_form['sections'];
			if ( isset( $fresh_rendered_form['navigation_labels'] ) ) {
				$template_context['rendered_form']['navigation_labels'] = $fresh_rendered_form['navigation_labels'];
			}
			if ( isset( $fresh_rendered_form['submit_label'] ) ) {
				$template_context['rendered_form']['submit_label'] = $fresh_rendered_form['submit_label'];
			}
			if ( isset( $fresh_rendered_form['step_status'] ) ) {
				$template_context['rendered_form']['step_status'] = array_merge(
					is_array( $template_context['rendered_form']['step_status'] ?? null )
						? $template_context['rendered_form']['step_status']
						: [],
					$fresh_rendered_form['step_status']
				);
			}
		}
		// Recompute section count now that rendered_form is populated/refreshed.
		$section_count        = count( $template_context['rendered_form']['sections'] ?? [] );
		$show_section_headers = 'show' === $section_label_visibility
			? true
			: ( 'hide' === $section_label_visibility ? false : $section_count > 1 );
		if ( ! is_array( $template_context['runtime'] ?? null ) ) {
			$template_context['runtime'] = [];
		}
		$form_config['showProjectTitle']       = $show_project_title;
		$form_config['showRequiredFieldsNote'] = $show_required_note;
		$form_config['sectionLabelVisibility'] = $section_label_visibility;
		$template_context['runtime']['form_config_json'] = wp_json_encode( $form_config );
	}

	// Apply show_* flags to rendered_form (works whether built above or loaded from template context).
	if ( is_array( $template_context['rendered_form'] ?? null ) ) {
		$template_context['rendered_form']['show_title']           = $show_project_title;
		$template_context['rendered_form']['show_subtitle']        = $show_required_note;
		$template_context['rendered_form']['show_section_headers'] = $show_section_headers;
		// Ensure camera-photo-list/camera-photo fields always have the right input_type and
		// photo_placeholder_slots (may be absent in old exports that pre-date this feature).
		if ( ! empty( $template_context['rendered_form']['sections'] ) ) {
			$patched_sections = [];
			foreach ( $template_context['rendered_form']['sections'] as $xp_sec ) {
				if ( ! empty( $xp_sec['fields'] ) ) {
					$patched_fields = [];
					foreach ( $xp_sec['fields'] as $xp_fld ) {
						$xp_type = (string) ( $xp_fld['type'] ?? '' );
						if ( $xp_type === 'camera-photo-list' ) {
							$xp_fld['input_type'] = 'file';
							if ( ! isset( $xp_fld['photo_placeholder_slots'] ) ) {
								$xp_min                              = (int) ( $xp_fld['minFiles'] ?? $xp_fld['min_files'] ?? 2 );
								$xp_max                              = (int) ( $xp_fld['maxFiles'] ?? $xp_fld['max_files'] ?? 5 );
								$xp_fld['min_files']                 = $xp_min;
								$xp_fld['max_files']                 = $xp_max;
								$xp_fld['photo_placeholder_slots']   = array_fill( 0, $xp_max, null );
							}
						} elseif ( $xp_type === 'camera-photo' || $xp_type === 'payment-proof' ) {
							$xp_fld['input_type'] = 'file';
							if ( $xp_type === 'payment-proof' && empty( $xp_fld['accept'] ) ) {
								$xp_fld['accept'] = '.pdf,application/pdf,.jpg,.jpeg,image/jpeg,.png,image/png';
							}
						}
						$patched_fields[] = $xp_fld;
					}
					$xp_sec['fields'] = $patched_fields;
				}
				$patched_sections[] = $xp_sec;
			}
			$template_context['rendered_form']['sections'] = $patched_sections;
		}
	}

	// Ensure the PHP template runtime helpers are available.
	$runtime_file = XPRESSUI_BRIDGE_DIR . 'templates/runtime.php';
	if ( ! file_exists( $runtime_file ) ) {
		return wp_kses_post(
			'<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: template runtime not found.', 'xpressui-bridge' )
			. '</p>'
		);
	}
	require_once $runtime_file;

	// Ensure the fragment template exists.
	$fragment_path = XPRESSUI_BRIDGE_DIR . 'templates/form-fragment.php';
	if ( ! file_exists( $fragment_path ) ) {
		return wp_kses_post(
			'<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: form fragment template not found.', 'xpressui-bridge' )
			. '</p>'
		);
	}

	// Inject unique IDs so multiple forms on the same page don't collide.
	// _mount_node_id  → outer .page-shell div (init.js mounts here)
	// runtime.mount_node_id → inner runtime-mount div (rendered-form wrapper)
	$mount_node_id                      = 'xpressui-root-' . $slug;
	$config_script_id                   = 'xpressui-config-' . $slug;
	$template_context['_mount_node_id'] = $mount_node_id;
	if ( is_array( $template_context['runtime'] ?? null ) ) {
		$template_context['runtime']['mount_node_id']        = 'xpressui-mount-' . $slug;
		
		$booking_url = xpressui_get_project_setting( $slug, 'bookingUrl' );
		if ( is_array( $link_config ) && ! empty( $link_config['presentation']['bookingUrl'] ) ) {
			$booking_url = $link_config['presentation']['bookingUrl'];
		}
		$template_context['runtime']['booking_url'] = $booking_url;

		$_booking_btn = xpressui_get_project_setting( $slug, 'bookingButtonLabel' );
		if ( is_array( $link_config ) && ! empty( $link_config['presentation']['bookingButtonLabel'] ) ) {
			$_booking_btn = $link_config['presentation']['bookingButtonLabel'];
		}
		$template_context['runtime']['booking_button_label'] = '' !== $_booking_btn ? $_booking_btn : __( 'Book an appointment', 'xpressui-bridge' );
	}

	// Render the form fragment (HTML only; CSS and scripts are enqueued below).
	// form-fragment.php lives in templates/ (not generated/) as it is manually maintained.
	ob_start();
	$xpressui_ctx = $template_context;
	include $fragment_path;
	$fragment_html = (string) ob_get_clean();

	// -----------------------------------------------------------------
	// Enqueue the bundled XPressUI light runtime.
	// Served from plugin/runtime/, never from uploads.
	// -----------------------------------------------------------------
	$runtime_handle    = 'xpressui-runtime';
	$runtime_url       = XPRESSUI_BRIDGE_URL . 'runtime/xpressui-' . XPRESSUI_BRIDGE_RUNTIME_VERSION . '.umd.js';
	$runtime_file_path = XPRESSUI_BRIDGE_DIR . 'runtime/xpressui-' . XPRESSUI_BRIDGE_RUNTIME_VERSION . '.umd.js';

	// Allow extensions (e.g. the pro plugin) to override the runtime URL and file path.
	$runtime_url       = (string) apply_filters( 'xpressui_runtime_url', $runtime_url, $slug );
	$runtime_file_path = (string) apply_filters( 'xpressui_runtime_file_path', $runtime_file_path, $slug );

	// Use file mtime as cache-bust version so rebuilds invalidate the browser cache without a version bump.
	$runtime_ver = file_exists( $runtime_file_path ) ? (string) filemtime( $runtime_file_path ) : XPRESSUI_BRIDGE_RUNTIME_VERSION;

	wp_enqueue_script(
		$runtime_handle,
		$runtime_url,
		[],
		$runtime_ver,
		false
	);

	// Enqueue the shell init script (depends on the runtime).
	wp_enqueue_script(
		'xpressui-shell-init',
		XPRESSUI_BRIDGE_URL . 'assets/shell/plugin-shell-init.js',
		[ $runtime_handle ],
		XPRESSUI_BRIDGE_VERSION,
		false
	);

	// Inject REST endpoint, translations, and shell metadata before init runs.
	$booking_url = xpressui_get_project_setting( $slug, 'bookingUrl' );
	if ( is_array( $link_config ) && ! empty( $link_config['presentation']['bookingUrl'] ) ) {
		$booking_url = $link_config['presentation']['bookingUrl'];
	}

	$shell_meta = [
		'mountNodeId'   => $mount_node_id,
		'configId'      => $config_script_id,
		'slug'          => $slug,
		'runtimeSource' => 'plugin-bundled',
		'runtimeUrl'    => $runtime_url,
		'bookingUrl'    => $booking_url,
	];

	$inline_before  = 'window.XPRESSUI_WORDPRESS_REST_URL = ' . wp_json_encode( rest_url( 'xpressui/v1/submit' ) ) . ';';
	$inline_before .= 'window.XPRESSUI_I18N = ' . wp_json_encode( xpressui_get_shell_translations() ) . ';';
	$inline_before .= 'window.XPRESSUI_SHELL_META = ' . wp_json_encode( $shell_meta ) . ';';

	wp_add_inline_script( 'xpressui-shell-init', $inline_before, 'before' );

	// Enqueue the shortcode-specific CSS via the WordPress style API.
	// The static CSS file covers component base styles; dynamic/scoped overrides
	// (theme colours, mount-ID scope) are appended as an inline style block.
	$style_handle = 'xpressui-shortcode-' . sanitize_key( $slug );
	$static_css_url  = XPRESSUI_BRIDGE_URL . 'assets/css/xpressui-shortcode.css';
	$static_css_path = XPRESSUI_BRIDGE_DIR . 'assets/css/xpressui-shortcode.css';
	$static_css_ver  = file_exists( $static_css_path ) ? (string) filemtime( $static_css_path ) : XPRESSUI_BRIDGE_VERSION;
	if ( file_exists( $static_css_path ) ) {
		wp_enqueue_style( $style_handle, $static_css_url, [], $static_css_ver );
	} else {
		// No static file yet — register a virtual handle so wp_add_inline_style works.
		wp_register_style( $style_handle, false, [], XPRESSUI_BRIDGE_VERSION );
		wp_enqueue_style( $style_handle );
	}
	$inline_css = xpressui_build_shortcode_inline_css( $template_context, $mount_node_id );
	if ( '' !== $inline_css ) {
		wp_add_inline_style( $style_handle, $inline_css );
	}

	// Resume-mode detection: set data-resume-loading on the mount element when
	// ?xpressui_resume= is present. Delivered via wp_add_inline_script.
	$resume_script = 'try{if(/[?&]xpressui_resume=/.test(location.search)){var _xpEl=document.getElementById(' . wp_json_encode( $mount_node_id ) . ');if(_xpEl)_xpEl.setAttribute("data-resume-loading","");}}catch(e){}';
	wp_add_inline_script( 'xpressui-shell-init', $resume_script, 'after' );

	do_action( 'xpressui_shortcode_scripts_enqueued', $slug, $template_context );

	// Embed the form config as inert JSON markup read by plugin-shell-init.js.
	$form_config_json = $template_context['runtime']['form_config_json'] ?? '{}';

	$config_tag = '<template id="' . esc_attr( $config_script_id ) . '">'
		. esc_html( wp_json_encode( json_decode( $form_config_json, true ) ) )
		. '</template>';

	// Parity with the SaaS hosted render: a collapsible "reference documents" block
	// above the form, sourced from the synced Hosted Link presentation.
	$reference_docs_html = '';
	if ( is_array( $link_config ) ) {
		$ref_presentation    = is_array( $link_config['presentation'] ?? null ) ? $link_config['presentation'] : [];
		$ref_locale          = ( ( $ref_presentation['locale'] ?? '' ) === 'fr' ) ? 'fr' : 'en';
		$reference_docs_html = xpressui_render_reference_documents( $ref_presentation, $ref_locale );
	}

	$html_out = '<div class="xpressui-embed-wrapper xpressui-inline-embed">'
		. $reference_docs_html
		. $fragment_html
		. $config_tag
		. '</div>';

	return wp_kses( $html_out, xpressui_get_shell_allowed_html() );
}
