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
			'id'    => '',
			'title' => __( 'XPressUI Form', 'xpressui-bridge' ),
		],
		$atts,
		'xpressui'
	);

	$slug = sanitize_title( (string) $atts['id'] );

	if ( $slug === '' ) {
		return '<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: the "id" attribute is required.', 'xpressui-bridge' )
			. '</p>';
	}

	$base_dir = xpressui_get_workflows_base_dir();
	if ( $base_dir === '' ) {
		return '<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: could not resolve the uploads directory.', 'xpressui-bridge' )
			. '</p>';
	}

	$package_dir = xpressui_get_workflow_package_dir( $slug );

	if ( ! is_dir( $package_dir ) || ! xpressui_workflow_directory_has_required_artifacts( $package_dir ) ) {
		return '<p class="xpressui-embed-error">'
			. esc_html(
				sprintf(
					/* translators: %s: project slug */
					__( '[xpressui] error: no package found for id "%s". Please install the workflow package first.', 'xpressui-bridge' ),
					$slug
				)
			)
			. '</p>';
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
		return '<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: template runtime not found.', 'xpressui-bridge' )
			. '</p>';
	}
	require_once $runtime_file;

	// Ensure the fragment template exists.
	$fragment_path = XPRESSUI_BRIDGE_DIR . 'templates/form-fragment.php';
	if ( ! file_exists( $fragment_path ) ) {
		return '<p class="xpressui-embed-error">'
			. esc_html__( '[xpressui] error: form fragment template not found.', 'xpressui-bridge' )
			. '</p>';
	}

	// Inject unique IDs so multiple forms on the same page don't collide.
	// _mount_node_id  → outer .page-shell div (init.js mounts here)
	// runtime.mount_node_id → inner runtime-mount div (rendered-form wrapper)
	$mount_node_id                      = 'xpressui-root-' . $slug;
	$config_script_id                   = 'xpressui-config-' . $slug;
	$template_context['_mount_node_id'] = $mount_node_id;
	if ( is_array( $template_context['runtime'] ?? null ) ) {
		$template_context['runtime']['mount_node_id']        = 'xpressui-mount-' . $slug;
		$template_context['runtime']['booking_url']          = xpressui_get_project_setting( $slug, 'bookingUrl' );
		$_booking_btn = xpressui_get_project_setting( $slug, 'bookingButtonLabel' );
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
	$runtime_handle    = 'xpressui-light-runtime';
	$runtime_url       = XPRESSUI_BRIDGE_URL . 'runtime/xpressui-light-' . XPRESSUI_BRIDGE_RUNTIME_VERSION . '.umd.js';
	$runtime_file_path = XPRESSUI_BRIDGE_DIR . 'runtime/xpressui-light-' . XPRESSUI_BRIDGE_RUNTIME_VERSION . '.umd.js';

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
	$shell_meta = [
		'mountNodeId'   => $mount_node_id,
		'configId'      => $config_script_id,
		'slug'          => $slug,
		'runtimeSource' => 'plugin-bundled',
		'runtimeUrl'    => $runtime_url,
		'bookingUrl'    => xpressui_get_project_setting( $slug, 'bookingUrl' ),
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

	return '<div class="xpressui-embed-wrapper xpressui-inline-embed">'
		. $fragment_html
		. $config_tag
		. '</div>';
}
