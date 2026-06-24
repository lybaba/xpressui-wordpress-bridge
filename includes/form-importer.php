<?php
/**
 * Form importer for third-party WordPress plugins (CF7 & Gravity Forms).
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle form import action.
 */
function xpressui_handle_form_import_submission() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! isset( $_POST['xpressui_import_form_action'] ) ) {
		return;
	}
	check_admin_referer( 'xpressui_import_form_nonce_action', 'xpressui_import_form_nonce' );

	$source_type = sanitize_key( $_POST['form_source_type'] ?? '' );
	$form_id     = intval( $_POST['source_form_id'] ?? 0 );

	if ( empty( $source_type ) || ! $form_id ) {
		xpressui_set_admin_notice( __( 'Invalid form selection.', 'xpressui-bridge' ), 'error' );
		wp_safe_redirect( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-bridge&tab=import' ) );
		exit;
	}

	$imported_slug = '';
	if ( 'cf7' === $source_type ) {
		$imported_slug = xpressui_import_cf7_form( $form_id );
	} elseif ( 'gf' === $source_type ) {
		$imported_slug = xpressui_import_gravity_form( $form_id );
	}

	if ( is_wp_error( $imported_slug ) ) {
		xpressui_set_admin_notice( $imported_slug->get_error_message(), 'error' );
	} elseif ( ! empty( $imported_slug ) ) {
		/* translators: %s: shortcode text */
		xpressui_set_admin_notice( sprintf( __( 'Form successfully converted to IntakeFlow! Use shortcode: %s', 'xpressui-bridge' ), '<code>[xpressui id="' . esc_html( $imported_slug ) . '"]</code>' ), 'success' );
	} else {
		xpressui_set_admin_notice( __( 'Form conversion failed.', 'xpressui-bridge' ), 'error' );
	}

	wp_safe_redirect( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-bridge&tab=import' ) );
	exit;
}
add_action( 'admin_init', 'xpressui_handle_form_import_submission' );

/**
 * Parses a Contact Form 7 form into a normalised { title, fields } structure.
 *
 * Single source of the CF7 field-type mapping, shared by BOTH import entry
 * points: the guided wizard AJAX (xpressui_ajax_import_form_wizard) and the
 * non-JS fallback (xpressui_import_cf7_form). Keeping the parse in one place is
 * what stops the two paths drifting (e.g. one mapping [tel] to a tel input and
 * the other degrading it to text).
 *
 * @param int $form_id CF7 form post ID.
 * @return array|WP_Error { 'title' => string, 'fields' => array } or WP_Error.
 */
function xpressui_parse_cf7_form_fields( $form_id ) {
	$post = get_post( $form_id );
	if ( ! $post || 'wpcf7_contact_form' !== $post->post_type ) {
		return new WP_Error( 'invalid_form', __( 'Contact Form 7 form not found.', 'xpressui-bridge' ) );
	}

	$title   = $post->post_title;
	$content = $post->post_content;

	// Parse CF7 shortcode tags
	preg_match_all( '/\[([a-zA-Z0-9_*]+)\s+([a-zA-Z0-9_-]+)([^\]]*)\]/', $content, $matches, PREG_SET_ORDER );
	if ( empty( $matches ) ) {
		return new WP_Error( 'empty_form', __( 'No form tags detected in the selected form.', 'xpressui-bridge' ) );
	}

	$fields = [];
	foreach ( $matches as $match ) {
		$type_raw = $match[1];
		$name     = $match[2];
		$attrs    = $match[3];

		$is_required = strpos( $type_raw, '*' ) !== false;
		$clean_type  = str_replace( '*', '', $type_raw );

		$field_type = 'text';
		if ( 'email' === $clean_type ) {
			$field_type = 'email';
		} elseif ( 'textarea' === $clean_type ) {
			$field_type = 'textarea';
		} elseif ( 'file' === $clean_type ) {
			$field_type = 'file';
		} elseif ( in_array( $clean_type, [ 'tel', 'number', 'date', 'url' ], true ) ) {
			// CF7 ships [tel], [number], [date] and [url] tags. Their names already
			// match the runtime field types (the render path's input_type map +
			// templates/core/field.php recognise tel/number/date/url), so keep them
			// verbatim instead of degrading to a plain text input — that preserves
			// the right HTML5 input type, mobile keyboard and native validation.
			$field_type = $clean_type;
		} elseif ( in_array( $clean_type, [ 'select', 'checkbox', 'radio' ], true ) ) {
			$field_type = $clean_type;
		}

		$choices = [];
		if ( in_array( $field_type, [ 'select', 'checkbox', 'radio' ], true ) ) {
			preg_match_all( '/"([^"]+)"/', $attrs, $attr_matches );
			if ( ! empty( $attr_matches[1] ) ) {
				foreach ( $attr_matches[1] as $choice_val ) {
					$choices[] = [
						'label' => $choice_val,
						'value' => sanitize_title( $choice_val ),
					];
				}
			}
			if ( empty( $choices ) ) {
				$choices = [
					[ 'label' => __( 'Option 1', 'xpressui-bridge' ), 'value' => 'option_1' ],
					[ 'label' => __( 'Option 2', 'xpressui-bridge' ), 'value' => 'option_2' ],
				];
			}
		}

		$label = ucwords( str_replace( [ '-', '_' ], ' ', $name ) );
		$field_entry = [
			'id'          => $name,
			'label'       => $label,
			'type'        => $field_type,
			'required'    => $is_required,
			'placeholder' => $label,
		];
		if ( ! empty( $choices ) ) {
			$field_entry['choices'] = $choices;
		}
		$fields[] = $field_entry;
	}

	return [ 'title' => $title, 'fields' => $fields ];
}

/**
 * Imports a Contact Form 7 form as an IntakeFlow workflow (non-JS fallback path).
 */
function xpressui_import_cf7_form( $form_id ) {
	$parsed = xpressui_parse_cf7_form_fields( $form_id );
	if ( is_wp_error( $parsed ) ) {
		return $parsed;
	}
	$slug = 'cf7-import-' . sanitize_title( $parsed['title'] ) . '-' . wp_rand( 100, 999 );
	return xpressui_create_imported_workflow_package( $slug, $parsed['title'], $parsed['fields'] );
}

/**
 * Parses a Gravity Form into a normalised { title, fields } structure.
 *
 * Single source of the Gravity field-type mapping, shared by both the wizard
 * AJAX and the non-JS fallback (see xpressui_parse_cf7_form_fields()).
 *
 * @param int $form_id Gravity Forms form ID.
 * @return array|WP_Error { 'title' => string, 'fields' => array } or WP_Error.
 */
function xpressui_parse_gravity_form_fields( $form_id ) {
	if ( ! class_exists( 'GFAPI' ) ) {
		return new WP_Error( 'gf_not_installed', __( 'Gravity Forms API is not available.', 'xpressui-bridge' ) );
	}

	$form = GFAPI::get_form( $form_id );
	if ( ! $form || is_wp_error( $form ) ) {
		return new WP_Error( 'invalid_form', __( 'Gravity Form not found.', 'xpressui-bridge' ) );
	}

	$title = $form['title'];
	$fields = [];

	if ( ! empty( $form['fields'] ) && is_array( $form['fields'] ) ) {
		foreach ( $form['fields'] as $gf_field ) {
			$name = 'field_' . $gf_field->id;
			$type = $gf_field->type;
			$label = $gf_field->label;
			$is_required = ! empty( $gf_field->isRequired );

			$field_type = 'text';
			if ( 'email' === $type ) {
				$field_type = 'email';
			} elseif ( 'textarea' === $type ) {
				$field_type = 'textarea';
			} elseif ( 'fileupload' === $type ) {
				$field_type = 'file';
			} elseif ( 'phone' === $type ) {
				// Gravity's phone field → runtime `phone`, normalised to a `tel` input
				// by xpressui_import_map_field_type(). Without this it rendered as text.
				$field_type = 'phone';
			} elseif ( 'website' === $type ) {
				// Gravity names its URL field `website`; the runtime/templates expect `url`.
				$field_type = 'url';
			} elseif ( in_array( $type, [ 'number', 'date', 'time' ], true ) ) {
				// Names already match the runtime field types — keep verbatim so the
				// import yields the right HTML5 input (number/date/time) instead of text.
				$field_type = $type;
			} elseif ( in_array( $type, [ 'select', 'checkbox', 'radio' ], true ) ) {
				$field_type = $type;
			}

			$choices = [];
			if ( in_array( $field_type, [ 'select', 'checkbox', 'radio' ], true ) ) {
				if ( ! empty( $gf_field->choices ) && is_array( $gf_field->choices ) ) {
					foreach ( $gf_field->choices as $c ) {
						$choices[] = [
							'label' => $c['text'],
							'value' => $c['value'],
						];
					}
				}
				if ( empty( $choices ) ) {
					$choices = [
						[ 'label' => __( 'Option 1', 'xpressui-bridge' ), 'value' => 'option_1' ],
						[ 'label' => __( 'Option 2', 'xpressui-bridge' ), 'value' => 'option_2' ],
					];
				}
			}

			$field_entry = [
				'id'          => $name,
				'label'       => $label,
				'type'        => $field_type,
				'required'    => $is_required,
				'placeholder' => $label,
			];
			if ( ! empty( $choices ) ) {
				$field_entry['choices'] = $choices;
			}
			$fields[] = $field_entry;
		}
	}

	return [ 'title' => $title, 'fields' => $fields ];
}

/**
 * Imports a Gravity Form as an IntakeFlow workflow (non-JS fallback path).
 */
function xpressui_import_gravity_form( $form_id ) {
	$parsed = xpressui_parse_gravity_form_fields( $form_id );
	if ( is_wp_error( $parsed ) ) {
		return $parsed;
	}
	$slug = 'gf-import-' . sanitize_title( $parsed['title'] ) . '-' . wp_rand( 100, 999 );
	return xpressui_create_imported_workflow_package( $slug, $parsed['title'], $parsed['fields'] );
}

/**
 * The default IntakeFlow theme applied to imported workflows.
 *
 * Mirrors the SaaS Console default preset (`console-clean`, see
 * services/api/app/project_documents.py::DEFAULT_PROJECT_THEME_CONFIG) so an
 * imported CF7/Gravity form renders with the SAME themed look as a real
 * IntakeFlow workflow instead of a bare, unstyled form. Returned in the
 * camelCase `themeConfig` shape carried inside form.config.json.
 *
 * @return array
 */
function xpressui_import_default_theme_config() {
	return [
		'presetId'        => 'console-clean',
		'layout'          => 'centered',
		'density'         => 'comfortable',
		'labelPosition'   => 'stacked',
		'submitWidth'     => 'auto',
		'backgroundStyle' => 'panel',
		'frameStyle'      => 'card',
		'fieldColumns'    => 1,
		'stepLayout'      => 'default',
		'colors'          => [
			'pageBackground' => '#edf3fb',
			'surface'        => '#ffffff',
			'text'           => '#0f172a',
			'mutedText'      => '#475569',
			'primary'        => '#2563eb',
			'border'         => '#d7e0ea',
		],
		'radius'          => [
			'card'   => 28,
			'input'  => 14,
			'button' => 14,
		],
	];
}

/**
 * Builds the snake_case `theme` block for template.context.json from the
 * camelCase `themeConfig`. This is the shape the WordPress render path reads
 * (includes/shortcode.php → xpressui_build_shortcode_inline_css() and the
 * project-page template) to emit the `--template-*` CSS variables. It mirrors
 * the exporter's template-context `theme` projection (services/api/app/exporter.py).
 *
 * @param array $theme_config camelCase themeConfig.
 * @return array snake_case theme block.
 */
function xpressui_import_theme_context_from_config( array $theme_config ) {
	$colors = is_array( $theme_config['colors'] ?? null ) ? $theme_config['colors'] : [];
	$radius = is_array( $theme_config['radius'] ?? null ) ? $theme_config['radius'] : [];

	return [
		'preset_id'        => $theme_config['presetId'] ?? 'console-clean',
		'layout'           => $theme_config['layout'] ?? 'centered',
		'density'          => $theme_config['density'] ?? 'comfortable',
		'label_position'   => $theme_config['labelPosition'] ?? 'stacked',
		'background_style' => $theme_config['backgroundStyle'] ?? 'panel',
		'frame_style'      => $theme_config['frameStyle'] ?? 'card',
		'field_columns'    => $theme_config['fieldColumns'] ?? 1,
		'step_layout'      => $theme_config['stepLayout'] ?? 'default',
		'colors'           => [
			'page_background' => $colors['pageBackground'] ?? '#edf3fb',
			'surface'         => $colors['surface'] ?? '#ffffff',
			'text'            => $colors['text'] ?? '#0f172a',
			'muted_text'      => $colors['mutedText'] ?? '#475569',
			'primary'         => $colors['primary'] ?? '#2563eb',
			'border'          => $colors['border'] ?? '#d7e0ea',
		],
		'radius'           => [
			'card'   => $radius['card'] ?? 28,
			'input'  => $radius['input'] ?? 14,
			'button' => $radius['button'] ?? 14,
		],
	];
}

/**
 * Maps a parsed import field type (CF7/Gravity) to the IntakeFlow runtime field
 * type the templates and runtime expect. Without this, types like `select`,
 * `phone`, `checkbox`, `radio` fall through to the templates/core/field.php
 * `unsupported` branch (render_type === type with no matching case), producing a
 * broken/blank control. `select-one`, `checkboxes`, `radio-buttons`, `tel` are
 * the names templates/core/field.php and the runtime recognise.
 *
 * @param string $type Parsed source field type.
 * @return string IntakeFlow runtime field type.
 */
function xpressui_import_map_field_type( $type ) {
	switch ( $type ) {
		case 'select':
			return 'select-one';
		case 'checkbox':
			return 'checkboxes';
		case 'radio':
			return 'radio-buttons';
		case 'phone':
			return 'tel';
		default:
			return $type;
	}
}

/**
 * Helper to construct the ConsoleProjectDocumentPayload for importing to SaaS Console.
 */
function xpressui_build_import_document( $slug, $title, $fields, $steps ) {
	$project_id = 'import_' . uniqid();
	$now_iso = gmdate( 'Y-m-d\TH:i:s\Z' );

	// A workflow with a single step must be declared as a single-step form, not a
	// multi-step one. The hydration-only runtime gates the Submit button behind
	// reaching the LAST step: in a multi-step workflow the user advances with
	// "Continue"/"Next" before Submit is enabled. With only one step there is no
	// "next" to advance, so a multi-step declaration leaves Submit permanently
	// greyed/disabled. Mirror the shape a working single-step Console config has
	// (`type: form`, `mode: form`, `submissionMode: single-step-submit`) so the
	// imported form is actually submittable.
	$is_multi_step  = is_array( $steps ) && count( $steps ) > 1;
	$config_type    = $is_multi_step ? 'multistepform' : 'form';
	$config_mode    = $is_multi_step ? 'form-multi-step' : 'form';
	$submission_mode = $is_multi_step ? 'multi-step-submit' : 'single-step-submit';

	$properties = [];
	$required = [];
	foreach ( $fields as $f ) {
		$properties[ $f['id'] ] = [
			'type'  => 'string',
			'title' => $f['label']
		];
		if ( ! empty( $f['required'] ) ) {
			$required[] = $f['id'];
		}
	}

	$import_doc = [
		'schemaVersion' => 'console.project/v1',
		'project'       => [
			'id'        => $project_id,
			'slug'      => $slug,
			'name'      => $title,
			'status'    => 'draft',
			'createdAt' => $now_iso,
			'updatedAt' => $now_iso,
			'createdBy' => 'wp',
			'updatedBy' => 'wp'
		],
		'runtime'       => [
			'xpressuiVersion' => '1.0.0',
			'target'          => 'form'
		],
		'builder'       => [
			'source' => 'legacy-form-config'
		],
		'assets'        => [],
		'config'        => [
			'version'          => 1,
			'id'               => $project_id,
			'uid'              => $project_id . '-uid',
			'type'             => $config_type,
			'name'             => $slug,
			'title'            => $title,
			'mode'             => $config_mode,
			'navigationLabels' => [
				'prevLabel'   => __( 'Back', 'xpressui-bridge' ),
				'nextLabel'   => __( 'Continue', 'xpressui-bridge' ),
				'submitLabel' => __( 'Submit', 'xpressui-bridge' )
			],
			'workflowConfig'   => [
				'submissionMode'     => $submission_mode,
				'providerMode'       => 'wordpress-bridge',
				'resumeSupport'      => 'disabled',
				'documentHandling'   => 'basic-upload',
				'submissionEndpoint' => '/wp-json/xpressui/v1/submit',
				'successMessage'     => __( 'Thank you — your answers are in.', 'xpressui-bridge' ),
				'errorMessage'       => __( 'Something went wrong. Please try again.', 'xpressui-bridge' )
			],
			// Default IntakeFlow theme so the render path emits the --template-* CSS
			// variables and the form looks like a real workflow (not a bare form).
			// The WP render reads the snake_case `theme` from template.context.json
			// (written by xpressui_create_imported_workflow_package); themeConfig here
			// keeps form.config.json complete for SaaS parity + future re-export.
			'themeConfig'      => xpressui_import_default_theme_config(),
			// Multi-step UI placement (parity with a real workflow config). Harmless
			// for single-step forms (the runtime hides step nav when stepCount <= 1).
			'stepUi'           => [
				'progressPlacement'   => 'top',
				'navigationPlacement' => 'bottom',
				'backBehavior'        => 'always'
			],
			'sections'         => [
				'custom' => array_map( function( $step ) {
					return [
						'type'       => 'section',
						'subType'    => 'section',
						'label'      => $step['title'],
						'adminLabel' => $step['title'],
						'name'       => $step['id']
					];
				}, $steps ),
				// Navigation/submit button group — parity with a real workflow config.
				// The runtime enables/binds these by name; labels mirror navigationLabels.
				'btngroup' => [
					[ 'type' => 'submit', 'name' => 'prevbtn',   'label' => __( 'Back', 'xpressui-bridge' ),     'adminLabel' => __( 'Back', 'xpressui-bridge' ) ],
					[ 'type' => 'submit', 'name' => 'nextbtn',   'label' => __( 'Continue', 'xpressui-bridge' ), 'adminLabel' => __( 'Continue', 'xpressui-bridge' ) ],
					[ 'type' => 'submit', 'name' => 'submitbtn', 'label' => __( 'Submit', 'xpressui-bridge' ),   'adminLabel' => __( 'Submit', 'xpressui-bridge' ) ],
				],
			],
			'submit'           => [
				'endpoint'            => '__XPRESSUI_WORDPRESS_REST_URL__',
				'method'              => 'POST',
				'mode'                => 'form-data',
				'includeDocumentData' => true,
				'metadata'            => [
					'projectId'   => $project_id,
					'projectSlug' => $slug
				]
			],
			'submitFeedback'   => [
				'title'           => __( 'Submission status', 'xpressui-bridge' ),
				'idle_message'    => __( 'Submission feedback will appear here.', 'xpressui-bridge' ),
				'loading_message' => __( 'Sending your answers...', 'xpressui-bridge' ),
				'success_title'   => __( 'Got it — thank you!', 'xpressui-bridge' ),
				'success_message' => __( 'Your answers are in.', 'xpressui-bridge' ),
				'error_title'     => __( 'Submission failed', 'xpressui-bridge' ),
				'error_message'   => __( 'Something went wrong.', 'xpressui-bridge' )
			]
		]
	];

	// Append field controls to each section
	foreach ( $steps as $step ) {
		$step_fields = [];
		foreach ( $fields as $f ) {
			if ( in_array( $f['id'], $step['fields'], true ) ) {
				$type_mapped = xpressui_import_map_field_type( $f['type'] );

				$field_control = [
					'type'        => $type_mapped,
					'label'       => $f['label'],
					'name'        => $f['id'],
					'required'    => ! empty( $f['required'] ),
					'placeholder' => $f['placeholder'],
				];
				if ( ! empty( $f['choices'] ) ) {
					$field_control['choices'] = $f['choices'];
				}
				$step_fields[] = $field_control;
			}
		}
		$import_doc['config']['sections'][ $step['id'] ] = $step_fields;
	}

	return $import_doc;
}

/**
 * Creates the workflow folder structure and saves the manifest.json snapshot.
 */
function xpressui_create_imported_workflow_package( $slug, $title, $fields ) {
	$base_dir = xpressui_get_workflows_base_dir();
	if ( empty( $base_dir ) ) {
		return new WP_Error( 'dir_error', __( 'Workflows directory not configurable.', 'xpressui-bridge' ) );
	}

	$target_dir = trailingslashit( $base_dir ) . $slug;
	if ( ! file_exists( $target_dir ) ) {
		wp_mkdir_p( $target_dir );
	}

	// Write the package artifacts through the WordPress filesystem API (same
	// pattern as includes/console-sync.php) rather than raw file_put_contents,
	// so the plugin honours the host's configured FS method and FS_CHMOD_FILE.
	global $wp_filesystem;
	if ( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! WP_Filesystem() ) {
		return new WP_Error( 'fs_error', __( 'Could not initialise the WordPress filesystem.', 'xpressui-bridge' ) );
	}

	// Map fields to steps (IntakeFlow workflow schema structure)
	$steps = [
		[
			'id'    => 'step_1',
			'title' => __( 'General Details', 'xpressui-bridge' ),
			'fields'=> array_column( $fields, 'id' ),
		]
	];

	// Split file fields to a dedicated step if present (standard IntakeFlow design pattern)
	$file_fields = [];
	$normal_fields = [];
	foreach ( $fields as $f ) {
		if ( 'file' === $f['type'] ) {
			$file_fields[] = $f;
		} else {
			$normal_fields[] = $f;
		}
	}

	if ( ! empty( $file_fields ) && ! empty( $normal_fields ) ) {
		$steps = [
			[
				'id'    => 'step_1',
				'title' => __( 'Information', 'xpressui-bridge' ),
				'fields'=> array_column( $normal_fields, 'id' ),
			],
			[
				'id'    => 'step_2',
				'title' => __( 'Required Documents', 'xpressui-bridge' ),
				'fields'=> array_column( $file_fields, 'id' ),
			]
		];
	}

	// Build the XPressUI form config (same shape the SaaS Console export produces)
	// so the local package carries a real form.config.json. Without it the package
	// has only manifest.json, which fails xpressui_workflow_directory_has_required_artifacts()
	// (it requires the config artifact too) — so the workflow never appears in the
	// Installed Workflows list and the shortcode cannot render it.
	$import_document = xpressui_build_import_document( $slug, $title, $fields, $steps );
	$form_config     = is_array( $import_document['config'] ?? null ) ? $import_document['config'] : [];

	// Source-of-truth project identifiers come from the SAME document that produced
	// form.config.json (whose submit.metadata.projectId the runtime sends back on
	// submit). The manifest + template.context.json below MUST reuse these exact
	// values, otherwise xpressui_validate_submission_identifiers() (rest-endpoint.php)
	// rejects the submission with "Submission project metadata does not match the
	// installed workflow" — the manifest projectId is hash_equals()-compared against
	// the submitted one. Reading them from the document (not regenerating uniqid())
	// is what keeps the three artifacts consistent.
	$project_id      = is_string( $import_document['project']['id'] ?? null ) ? $import_document['project']['id'] : ( 'import_' . uniqid() );
	$config_version  = isset( $import_document['config']['version'] ) ? (string) $import_document['config']['version'] : '1';

	// Build the template.context.json — the WordPress render reads the `theme` block
	// from HERE (xpressui_load_workflow_template_context → xpressui_build_shortcode_inline_css),
	// NOT from form.config.json. Without it the shortcode falls back to an empty theme,
	// so every --template-* CSS variable resolves blank → the form renders BARE
	// (unstyled inputs, a greyed/colourless submit). Carrying the default theme here is
	// what makes an imported form look like a real IntakeFlow workflow. `rendered_form`
	// is intentionally NOT written: the shortcode rebuilds it from form.config.json on
	// every render (xpressui_build_rendered_form_from_config), keeping it fresh.
	$theme_config     = is_array( $form_config['themeConfig'] ?? null ) ? $form_config['themeConfig'] : xpressui_import_default_theme_config();
	$template_context = [
		'xpressui_locale' => 'en',
		'project'         => [
			'id'                   => $project_id,
			'slug'                 => $slug,
			'name'                 => $title,
			'show_project_title'   => true,
			'status'               => 'draft',
			'background_image_url' => null,
		],
		'theme'           => xpressui_import_theme_context_from_config( $theme_config ),
		'runtime'         => [
			'target'           => 'form',
			'mount_node_id'    => 'xpressui-root',
			'form_config_json' => wp_json_encode( $form_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
		],
	];

	$manifest = [
		'projectId'          => $project_id,
		'projectName'        => $title,
		'projectSlug'        => $slug,
		'configVersion'      => $config_version,
		'runtimeTier'        => xpressui_is_saas_connected() ? 'light' : 'trial', // Standalone offline execution
		'runtimeRequirements'=> [
			'tier' => xpressui_is_saas_connected() ? 'light' : 'trial',
		],
		'isBundled'          => true,
		'manifestFingerprint'=> md5( wp_json_encode( $fields ) ),
		// Declare the on-disk artifacts so the list scan and the shortcode renderer
		// resolve form.config.json (xpressui_get_workflow_artifact_path()).
		'artifacts'          => [
			'config'          => 'form.config.json',
			'templateContext' => 'template.context.json',
		],
		'fields'             => $fields,
		'steps'              => $steps,
		'schema'             => [
			'type'       => 'object',
			'required'   => array_values( array_filter( array_map( function( $f ) { return $f['required'] ? $f['id'] : null; }, $fields ) ) ),
			'properties' => (object) array_combine(
				array_column( $fields, 'id' ),
				array_map( function( $f ) {
					return [ 'type' => 'string', 'title' => $f['label'] ];
				}, $fields )
			),
		]
	];

	// Write form.config.json FIRST — it is a required artifact for the package to be
	// considered installed. If it cannot be written, fail before leaving a half-built
	// (invisible) package behind.
	$config_written = $wp_filesystem->put_contents(
		trailingslashit( $target_dir ) . 'form.config.json',
		wp_json_encode( $form_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
		FS_CHMOD_FILE
	);
	if ( false === $config_written ) {
		return new WP_Error( 'write_failed', __( 'Could not write form.config.json configuration.', 'xpressui-bridge' ) );
	}

	// Write template.context.json — carries the default theme so the form renders
	// styled (not bare). Declared as the `templateContext` artifact above.
	$context_written = $wp_filesystem->put_contents(
		trailingslashit( $target_dir ) . 'template.context.json',
		wp_json_encode( $template_context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
		FS_CHMOD_FILE
	);
	if ( false === $context_written ) {
		return new WP_Error( 'write_failed', __( 'Could not write template.context.json configuration.', 'xpressui-bridge' ) );
	}

	// Write manifest.json
	$written = $wp_filesystem->put_contents(
		trailingslashit( $target_dir ) . 'manifest.json',
		wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
		FS_CHMOD_FILE
	);

	if ( false === $written ) {
		return new WP_Error( 'write_failed', __( 'Could not write manifest.json configuration.', 'xpressui-bridge' ) );
	}

	// Register locally
	xpressui_store_workflow_manifest_meta( $slug, $manifest );

	return $slug;
}

/**
 * Creates the imported workflow as a project on the SaaS Console.
 *
 * Encapsulates the Console "create" interaction only (build document → POST →
 * validate response). Returns an array with the created project id + slug on
 * success, or a WP_Error describing the failure. ANY failure here means the
 * SaaS project could NOT be created, and the caller should fall back to a local
 * import. The error code identifies the failure class for messaging:
 *   - missing_connection : URL/token not configured
 *   - connection_failed  : cURL/WP_Error (refused, timeout, DNS, ...)
 *   - http_error         : non-2xx response (data => [ 'status' => int ])
 *   - invalid_response   : 2xx but no usable project id
 *
 * @return array|WP_Error { projectId, slug } on success.
 */
function xpressui_console_create_project( $slug, $title, $fields, $steps ) {
	$conn = xpressui_get_console_connection();
	if ( empty( $conn['apiUrl'] ) || empty( $conn['apiToken'] ) ) {
		return new WP_Error( 'missing_connection', __( 'Console Connection URL or Token is missing.', 'xpressui-bridge' ) );
	}

	$document = xpressui_build_import_document( $slug, $title, $fields, $steps );
	$payload  = [
		'schemaVersion'    => 'console.workflow-export/v1',
		'workflow'         => $document,
		'conflictStrategy' => 'auto_suffix',
		'name'             => $title,
		'slug'             => $slug,
	];

	$response = wp_remote_post(
		trailingslashit( $conn['apiUrl'] ) . 'api/v1/projects/import',
		[
			'headers' => [
				'X-Api-Token'  => $conn['apiToken'],
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json',
			],
			'body'    => wp_json_encode( $payload ),
			'timeout' => 30,
		]
	);

	if ( is_wp_error( $response ) ) {
		// Connection-level failure (refused, timeout, DNS, ...). Preserve the
		// original error message for logging, but flag the class as connection_failed.
		return new WP_Error( 'connection_failed', $response->get_error_message(), [ 'wp_error' => $response ] );
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 201 !== $code ) {
		$err_body = json_decode( wp_remote_retrieve_body( $response ), true );
		$detail   = ! empty( $err_body['detail'] ) ? $err_body['detail'] : __( 'API Import Request failed.', 'xpressui-bridge' );
		return new WP_Error( 'http_error', $detail, [ 'status' => (int) $code ] );
	}

	$body           = json_decode( wp_remote_retrieve_body( $response ), true );
	$api_project_id = ! empty( $body['imported']['projectId'] ) ? sanitize_text_field( $body['imported']['projectId'] ) : '';
	$final_slug     = ! empty( $body['slug'] ) ? sanitize_title( $body['slug'] ) : $slug;

	if ( empty( $api_project_id ) ) {
		return new WP_Error( 'invalid_response', __( 'Console API did not return a valid project ID.', 'xpressui-bridge' ) );
	}

	return [
		'projectId' => $api_project_id,
		'slug'      => $final_slug,
	];
}

/**
 * Maps a Console-create WP_Error to a short, human-readable reason for the user.
 * Avoids dumping raw cURL text (e.g. "cURL error 7: ...").
 *
 * @param WP_Error $error Error from xpressui_console_create_project().
 * @return string Short reason, e.g. "connection refused", "authentication failed (401)".
 */
function xpressui_console_failure_reason( $error ) {
	$code = $error->get_error_code();

	if ( 'missing_connection' === $code ) {
		return __( 'connection not configured', 'xpressui-bridge' );
	}

	if ( 'http_error' === $code ) {
		$data   = $error->get_error_data();
		$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 0;
		if ( 401 === $status || 403 === $status ) {
			/* translators: %d: HTTP status code. */
			return sprintf( __( 'authentication failed (%d)', 'xpressui-bridge' ), $status );
		}
		if ( $status >= 500 ) {
			/* translators: %d: HTTP status code. */
			return sprintf( __( 'server error (%d)', 'xpressui-bridge' ), $status );
		}
		if ( $status > 0 ) {
			/* translators: %d: HTTP status code. */
			return sprintf( __( 'request rejected (%d)', 'xpressui-bridge' ), $status );
		}
		return __( 'request rejected', 'xpressui-bridge' );
	}

	if ( 'invalid_response' === $code ) {
		return __( 'unexpected response', 'xpressui-bridge' );
	}

	// connection_failed (and any other) — infer from the raw message text.
	$raw = strtolower( $error->get_error_message() );
	if ( false !== strpos( $raw, 'timed out' ) || false !== strpos( $raw, 'timeout' ) ) {
		return __( 'connection timed out', 'xpressui-bridge' );
	}
	if ( false !== strpos( $raw, 'could not resolve' ) || false !== strpos( $raw, 'resolve host' ) ) {
		return __( 'host not found', 'xpressui-bridge' );
	}
	// cURL 7 / "connection refused" / generic connect failures.
	return __( 'connection refused', 'xpressui-bridge' );
}

/**
 * AJAX: handles the guided wizard import process.
 */
add_action( 'wp_ajax_xpressui_import_form_wizard', 'xpressui_ajax_import_form_wizard' );

function xpressui_ajax_import_form_wizard() {
	check_ajax_referer( 'xpressui_import_form_wizard_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'xpressui-bridge' ) ], 403 );
	}

	$source_type = sanitize_key( wp_unslash( $_POST['source_type'] ?? '' ) );
	$form_id     = intval( $_POST['source_form_id'] ?? 0 );
	$custom_name = sanitize_text_field( wp_unslash( $_POST['custom_name'] ?? '' ) );
	$custom_slug = sanitize_title( wp_unslash( $_POST['custom_slug'] ?? '' ) );

	// Destination is decided automatically server-side (source of truth): if the
	// Console connection has an active API token, create the workflow in the SaaS;
	// otherwise save it locally as a standalone workflow. Any client-sent value is ignored.
	$import_mode = xpressui_is_saas_connected() ? 'saas' : 'local';

	if ( empty( $source_type ) || ! $form_id ) {
		wp_send_json_error( [ 'message' => __( 'Missing source form selection.', 'xpressui-bridge' ) ] );
	}

	// 1. Parse the source form into { title, fields } via the shared parser — the
	// SAME field-type mapping the non-JS fallback path uses, so the two import
	// paths never drift on which field types are recognised.
	if ( 'cf7' === $source_type ) {
		$parsed = xpressui_parse_cf7_form_fields( $form_id );
	} elseif ( 'gf' === $source_type ) {
		$parsed = xpressui_parse_gravity_form_fields( $form_id );
	} else {
		wp_send_json_error( [ 'message' => __( 'Unsupported source form type.', 'xpressui-bridge' ) ] );
	}
	if ( is_wp_error( $parsed ) ) {
		wp_send_json_error( [ 'message' => $parsed->get_error_message() ] );
	}
	$title  = $parsed['title'];
	$fields = $parsed['fields'];

	// 2. Resolve final Title & Slug
	if ( ! empty( $custom_name ) ) {
		$title = $custom_name;
	}
	$slug = ! empty( $custom_slug ) ? $custom_slug : sanitize_title( $title ) . '-' . wp_rand( 100, 999 );

	// Split file fields to a dedicated step
	$file_fields = [];
	$normal_fields = [];
	foreach ( $fields as $f ) {
		if ( 'file' === $f['type'] ) {
			$file_fields[] = $f;
		} else {
			$normal_fields[] = $f;
		}
	}

	$steps = [
		[
			'id'    => 'step_1',
			'title' => __( 'General Details', 'xpressui-bridge' ),
			'fields'=> array_column( $fields, 'id' ),
		]
	];

	if ( ! empty( $file_fields ) && ! empty( $normal_fields ) ) {
		$steps = [
			[
				'id'    => 'step_1',
				'title' => __( 'Information', 'xpressui-bridge' ),
				'fields'=> array_column( $normal_fields, 'id' ),
			],
			[
				'id'    => 'step_2',
				'title' => __( 'Required Documents', 'xpressui-bridge' ),
				'fields'=> array_column( $file_fields, 'id' ),
			]
		];
	}

	// 3. Process Import
	if ( 'saas' === $import_mode ) {
		// Attempt to create the project on the Console. If the *create* itself fails
		// (connection refused/timeout, non-2xx, missing config, or no project id
		// returned), we DON'T hard-fail: we fall back to a local standalone workflow
		// so the user always ends up with something working. The fallback is keyed off
		// the create only — a successful create whose post-create file sync fails is a
		// distinct case (the SaaS project exists) and stays a warning, never a fallback.
		$create_result = xpressui_console_create_project( $slug, $title, $fields, $steps );

		if ( is_wp_error( $create_result ) ) {
			// Console create failed → fall back to LOCAL.
			$reason     = xpressui_console_failure_reason( $create_result );
			$local_slug = xpressui_create_imported_workflow_package( $slug, $title, $fields );
			if ( is_wp_error( $local_slug ) ) {
				// Even local failed — surface the local error.
				wp_send_json_error( [ 'message' => $local_slug->get_error_message() ] );
			}

			wp_send_json_success( [
				'shortcode' => '[xpressui id="' . esc_attr( $local_slug ) . '"]',
				'slug'      => $local_slug,
				'saas'      => false,
				'fallback'  => true,
				// Friendly headline note shown once (no raw technical detail). The raw
				// reason travels separately in `reason` so the UI can render it small/muted.
				'notice'    => __( "We couldn't reach your IntakeFlow Console just now, so your workflow was saved on this site. You can sync it to the Console anytime.", 'xpressui-bridge' ),
				// Subtle technical detail (e.g. "authentication failed (401)") — rendered muted.
				'reason'    => $reason,
				'message'   => __( 'Workflow saved on this site.', 'xpressui-bridge' ),
			] );
		}

		// Console create succeeded.
		$api_project_id = $create_result['projectId'];
		$final_slug     = $create_result['slug'];

		// Pull the newly created project back to WordPress. A sync failure here is NOT
		// a "Console unreachable" condition (the SaaS project was created), so we do
		// NOT create a duplicate local workflow — we report it as a warning.
		$sync_res = xpressui_sync_project( $api_project_id );
		if ( is_wp_error( $sync_res ) ) {
			/* translators: %s: error message details */
			wp_send_json_error( [ 'message' => sprintf( __( 'SaaS project created, but local sync failed: %s', 'xpressui-bridge' ), $sync_res->get_error_message() ) ] );
		}

		$edit_url = xpressui_console_workflow_url( $final_slug );

		wp_send_json_success( [
			'shortcode' => '[xpressui id="' . esc_attr( $final_slug ) . '"]',
			'slug'      => $final_slug,
			'saas'      => true,
			'edit_url'  => $edit_url,
			'message'   => __( 'Form successfully converted and synchronized with SaaS Console!', 'xpressui-bridge' )
		] );

	} else {
		// Standalone local import
		$local_slug = xpressui_create_imported_workflow_package( $slug, $title, $fields );
		if ( is_wp_error( $local_slug ) ) {
			wp_send_json_error( [ 'message' => $local_slug->get_error_message() ] );
		}

		wp_send_json_success( [
			'shortcode' => '[xpressui id="' . esc_attr( $local_slug ) . '"]',
			'slug'      => $local_slug,
			'saas'      => false,
			'message'   => __( 'Form successfully converted locally as Standalone Workflow!', 'xpressui-bridge' )
		] );
	}
}

/**
 * Renders the Form Importer panel as a premium guided Wizard.
 */
function xpressui_render_form_importer_tab() {
	// Detect CF7 Forms
	$cf7_forms = [];
	if ( post_type_exists( 'wpcf7_contact_form' ) ) {
		$posts = get_posts( [
			'post_type'      => 'wpcf7_contact_form',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
		foreach ( $posts as $p ) {
			$cf7_forms[] = [ 'id' => $p->ID, 'title' => $p->post_title ];
		}
	}

	// Detect Gravity Forms
	$gf_forms = [];
	if ( class_exists( 'GFAPI' ) ) {
		$forms = GFAPI::get_forms();
		if ( is_array( $forms ) ) {
			foreach ( $forms as $f ) {
				$gf_forms[] = [ 'id' => $f['id'], 'title' => $f['title'] ];
			}
		}
	}

	$has_forms = ! empty( $cf7_forms ) || ! empty( $gf_forms );
	$is_saas_connected = xpressui_is_saas_connected();

	// CTA target for the local-import conversion nudge. Point at the Settings page's
	// Console Connection section (the same 1-Click "Connect IntakeFlow Account" entry
	// point) — robust for a brand-new, never-connected site where the Console URL may
	// not be configured yet, so we can't safely pre-build the wordpress-connect URL.
	$connect_settings_url = admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-settings' );

	// Wizard styles + behaviour are enqueued (assets/admin-importer.css + .js) by
	// xpressui_enqueue_admin_assets() — gated to this page's "import" tab — so this
	// render emits no inline <style>/<script>. PHP→JS values (ajax URL, nonces,
	// SaaS-connected flag, i18n) are passed through the localised `xpressuiImporter`.
	?>

	<div class="xpui-wiz-container">
		<div class="xpui-wiz-card">
			<!-- Step Header -->
			<div class="xpui-wiz-steps">
				<div class="xpui-wiz-line"></div>
				<div class="xpui-wiz-line-progress" id="xpui-line-progress"></div>
				
				<div class="xpui-wiz-step-item active" data-step="1">
					<div class="xpui-wiz-badge">1</div>
					<div class="xpui-wiz-step-label"><?php esc_html_e( 'Select', 'xpressui-bridge' ); ?></div>
				</div>
				<div class="xpui-wiz-step-item" data-step="2">
					<div class="xpui-wiz-badge">2</div>
					<div class="xpui-wiz-step-label"><?php esc_html_e( 'Destination', 'xpressui-bridge' ); ?></div>
				</div>
				<div class="xpui-wiz-step-item" data-step="3">
					<div class="xpui-wiz-badge">3</div>
					<div class="xpui-wiz-step-label"><?php esc_html_e( 'Importing', 'xpressui-bridge' ); ?></div>
				</div>
				<div class="xpui-wiz-step-item" data-step="4">
					<div class="xpui-wiz-badge">4</div>
					<div class="xpui-wiz-step-label"><?php esc_html_e( 'Success', 'xpressui-bridge' ); ?></div>
				</div>
			</div>

			<!-- Step 1: Select Form -->
			<div class="xpui-wiz-step-body active" id="xpui-wiz-step-1-body">
				<h2 style="margin-top:0; font-size:18px; font-weight:800; color:#0f172a;"><?php esc_html_e( 'Convert Legacy Forms', 'xpressui-bridge' ); ?></h2>
				<p class="description" style="margin-bottom:20px;"><?php esc_html_e( 'Select an existing Contact Form 7 or Gravity Form to begin importing it into a modern multi-step IntakeFlow portal workflow.', 'xpressui-bridge' ); ?></p>
				
				<?php if ( ! $has_forms ) : ?>
					<div style="margin-top: 20px; padding: 25px; text-align: center; border: 1.5px dashed #cbd5e1; border-radius: 10px; color: #64748b; background:#f8fafc;">
						<p style="margin: 0; font-size: 14px; font-weight: 600;"><?php esc_html_e( 'No legacy form plugins detected.', 'xpressui-bridge' ); ?></p>
						<p style="margin: 5px 0 0; font-size: 12px;"><?php esc_html_e( 'Please install Contact Form 7 or Gravity Forms and create a form first.', 'xpressui-bridge' ); ?></p>
					</div>
				<?php else : ?>
					<div style="margin-bottom: 20px;">
						<label style="display: block; font-weight: 700; margin-bottom: 8px; color: #334155; font-size:13px;"><?php esc_html_e( 'Select Source Form to Convert', 'xpressui-bridge' ); ?></label>
						<select id="xpui-wizard-select" style="width: 100%; height: 38px; border-radius: 6px; border:1px solid #cbd5e1;" required>
							<option value=""><?php esc_html_e( 'Choose a form...', 'xpressui-bridge' ); ?></option>
							<?php if ( ! empty( $cf7_forms ) ) : ?>
								<optgroup label="<?php esc_attr_e( 'Contact Form 7', 'xpressui-bridge' ); ?>">
									<?php foreach ( $cf7_forms as $form ) : ?>
										<option value="cf7:<?php echo (int) $form['id']; ?>" data-title="<?php echo esc_attr( $form['title'] ); ?>"><?php echo esc_html( $form['title'] ); ?></option>
									<?php endforeach; ?>
								</optgroup>
							<?php endif; ?>
							<?php if ( ! empty( $gf_forms ) ) : ?>
								<optgroup label="<?php esc_attr_e( 'Gravity Forms', 'xpressui-bridge' ); ?>">
									<?php foreach ( $gf_forms as $form ) : ?>
										<option value="gf:<?php echo (int) $form['id']; ?>" data-title="<?php echo esc_attr( $form['title'] ); ?>"><?php echo esc_html( $form['title'] ); ?></option>
									<?php endforeach; ?>
								</optgroup>
							<?php endif; ?>
						</select>
					</div>
				<?php endif; ?>
			</div>

			<!-- Step 2: Destination Settings -->
			<div class="xpui-wiz-step-body" id="xpui-wiz-step-2-body">
				<h2 style="margin-top:0; font-size:18px; font-weight:800; color:#0f172a;"><?php esc_html_e( 'Name Your Workflow', 'xpressui-bridge' ); ?></h2>
				<p class="description" style="margin-bottom:20px;"><?php esc_html_e( 'Give your imported workflow a name and slug. The destination is chosen automatically based on your Console connection.', 'xpressui-bridge' ); ?></p>

				<?php if ( $is_saas_connected ) : ?>
					<div style="display:flex; align-items:flex-start; gap:10px; padding:14px 16px; border:1px solid #bfdbfe; background:#eff6ff; border-radius:10px; margin-bottom:20px;">
						<span style="font-size:18px; line-height:1.2;">⚡</span>
						<div style="font-size:13px; color:#1e3a8a; line-height:1.5;">
							<strong><?php esc_html_e( 'Destination: IntakeFlow Console', 'xpressui-bridge' ); ?></strong><br>
							<?php esc_html_e( 'This workflow will be created in your IntakeFlow Console and synced back to WordPress.', 'xpressui-bridge' ); ?>
						</div>
					</div>
				<?php else : ?>
					<div style="display:flex; flex-direction:column; gap:12px; padding:16px; border:1px solid #fed7aa; background:#fff7ed; border-radius:10px; margin-bottom:20px;">
						<div style="display:flex; align-items:flex-start; gap:10px;">
							<span style="font-size:18px; line-height:1.2;">⚠️</span>
							<div style="font-size:13px; color:#7c2d12; line-height:1.5;">
								<strong><?php esc_html_e( 'Destination: Local (Standalone)', 'xpressui-bridge' ); ?></strong><br>
								<?php esc_html_e( 'This form will be saved locally. Connect your site to an IntakeFlow account to synchronize workflows and unlock SaaS features like notifications, cloud backups, and PDF/OCR generation.', 'xpressui-bridge' ); ?>
							</div>
						</div>
						<div style="margin-left: 28px;">
							<?php
							$connect_url = xpressui_get_wordpress_connect_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-bridge&tab=import' ) );
							?>
							<a href="<?php echo esc_url( $connect_url ); ?>" class="button button-primary" style="background:#2563eb; border-color:#2563eb; color:#fff; font-weight:600; padding:4px 14px; height:auto; line-height:1.8; border-radius:6px; display:inline-block; text-decoration:none;">
								<?php esc_html_e( 'Connect IntakeFlow Account', 'xpressui-bridge' ); ?>
							</a>
							<span style="font-size:12px; color:#6b7280; margin-left:10px; display:inline-block; vertical-align:middle;">
								<?php esc_html_e( 'Sign up is free and takes less than a minute.', 'xpressui-bridge' ); ?>
							</span>
						</div>
					</div>
				<?php endif; ?>

				<div style="margin-top:20px; display:flex; gap:15px;">
					<div style="flex:1;">
						<label style="display: block; font-weight: 700; margin-bottom: 6px; color: #334155; font-size:12px;"><?php esc_html_e( 'Custom Workflow Name', 'xpressui-bridge' ); ?></label>
						<input type="text" id="xpui-wizard-name" style="width: 100%; height: 38px; border-radius: 6px; border:1px solid #cbd5e1; padding:0 10px;" placeholder="<?php esc_attr_e( 'Leave empty to keep original', 'xpressui-bridge' ); ?>">
					</div>
					<div style="flex:1;">
						<label style="display: block; font-weight: 700; margin-bottom: 6px; color: #334155; font-size:12px;"><?php esc_html_e( 'Custom Workflow Slug', 'xpressui-bridge' ); ?></label>
						<input type="text" id="xpui-wizard-slug" style="width: 100%; height: 38px; border-radius: 6px; border:1px solid #cbd5e1; padding:0 10px;" placeholder="<?php esc_attr_e( 'e.g. contact-form', 'xpressui-bridge' ); ?>">
					</div>
				</div>
			</div>

			<!-- Step 3: Conversion & Sync Progress -->
			<div class="xpui-wiz-step-body" id="xpui-wiz-step-3-body">
				<h2 style="margin-top:0; font-size:18px; font-weight:800; color:#0f172a;"><?php esc_html_e( 'Converting Form...', 'xpressui-bridge' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Converting fields, mapping options, and preparing your workflow. Please do not close this page.', 'xpressui-bridge' ); ?></p>
				
				<div class="xpui-wiz-progress-container">
					<div class="xpui-wiz-progress-bar">
						<div class="xpui-wiz-progress-fill" id="xpui-progress-fill"></div>
					</div>

					<ul class="xpui-wiz-progress-steps">
						<li class="xpui-wiz-progress-step" id="xpui-prog-step-1">
							<span class="xpui-wiz-progress-step-icon" id="xpui-prog-icon-1">⏳</span>
							<span class="xpui-wiz-progress-step-text" id="xpui-prog-text-1"><?php esc_html_e( 'Parsing source form fields & choices...', 'xpressui-bridge' ); ?></span>
						</li>
						<li class="xpui-wiz-progress-step" id="xpui-prog-step-2">
							<span class="xpui-wiz-progress-step-icon" id="xpui-prog-icon-2">⏳</span>
							<span class="xpui-wiz-progress-step-text" id="xpui-prog-text-2"><?php esc_html_e( 'Generating local standalone workflow package...', 'xpressui-bridge' ); ?></span>
						</li>
						<li class="xpui-wiz-progress-step" id="xpui-prog-step-3">
							<span class="xpui-wiz-progress-step-icon" id="xpui-prog-icon-3">⏳</span>
							<span class="xpui-wiz-progress-step-text" id="xpui-prog-text-3"><?php esc_html_e( 'Conversion complete!', 'xpressui-bridge' ); ?></span>
						</li>
					</ul>
				</div>
			</div>

			<!-- Step 4: Success & Action -->
			<div class="xpui-wiz-step-body" id="xpui-wiz-step-4-body">
				<div class="xpui-wiz-success-card">
					<div class="xpui-wiz-success-icon">✓</div>
					<h2 style="margin-top:0; font-size:20px; font-weight:850; color:#15803d;" id="xpui-wiz-success-title"><?php esc_html_e( 'Conversion Complete!', 'xpressui-bridge' ); ?></h2>
					<p style="font-size:13.5px; color:#475569;" id="xpui-wiz-success-msg"><?php esc_html_e( 'Your form was converted to an IntakeFlow Workflow.', 'xpressui-bridge' ); ?></p>

					<!--
						Fallback note (Console unreachable -> saved locally). One friendly
						line; the raw technical reason renders muted underneath, with an
						optional "Retry sync to Console" action. Hidden by default; the JS
						reveals + fills it for the fallback state only.
					-->
					<div id="xpui-wiz-fallback-note" style="display:none; margin:14px auto 0; max-width:450px; text-align:left; gap:10px; align-items:flex-start; padding:12px 14px; border:1px solid #e2e8f0; background:#f8fafc; border-radius:10px;">
						<div style="flex:1;">
							<div id="xpui-wiz-fallback-note-text" style="font-size:13px; color:#334155; line-height:1.5;"></div>
							<div id="xpui-wiz-fallback-note-reason" style="margin-top:4px; font-size:11px; color:#94a3b8; line-height:1.4;"></div>
							<button type="button" class="xpui-wiz-copy-btn" id="xpui-wiz-retry-sync-btn" style="margin-top:10px; padding:5px 12px; font-size:12px;"><?php esc_html_e( 'Retry sync to Console', 'xpressui-bridge' ); ?></button>
							<span id="xpui-wiz-retry-status" style="margin-left:10px; font-size:12px; color:#64748b;"></span>
						</div>
					</div>
					
					<div class="xpui-wiz-code-box">
						<span class="xpui-wiz-code" id="xpui-wiz-shortcode">[xpressui id=""]</span>
						<button type="button" class="xpui-wiz-copy-btn" id="xpui-wiz-copy-btn" data-clipboard-text=""><?php esc_html_e( 'Copy', 'xpressui-bridge' ); ?></button>
					</div>

					<p style="margin-top:15px; display:flex; gap:10px; justify-content:center;" id="xpui-wiz-success-actions">
						<a href="" target="_blank" rel="noopener noreferrer" class="xpui-wiz-btn xpui-wiz-btn-primary" id="xpui-wiz-edit-btn" style="display:none;">
							<?php esc_html_e( 'Edit in Console', 'xpressui-bridge' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-bridge' ) ); ?>" class="xpui-wiz-btn xpui-wiz-btn-secondary">
							<?php esc_html_e( 'View Workflows', 'xpressui-bridge' ); ?>
						</a>
					</p>

					<!--
						Conversion nudge — shown ONLY when the import landed LOCALLY (no
						Console account, or fallback-to-local). Hidden by default; the JS
						reveals it on the Success step when response.data.saas is falsy.
						When connected (SaaS), this stays hidden and the normal success shows.
					-->
					<div id="xpui-wiz-connect-nudge" style="display:none; text-align:left; margin:24px auto 0; max-width:520px; border:1px solid #bfdbfe; background:linear-gradient(180deg,#eff6ff 0%,#ffffff 100%); border-radius:14px; padding:22px 24px; box-shadow:0 4px 6px -1px rgba(37,99,235,0.08);">
						<h3 style="margin:0 0 6px; font-size:16px; font-weight:800; color:#1e3a8a;">
							<?php esc_html_e( 'Your form is live on this site', 'xpressui-bridge' ); ?>
						</h3>
						<p style="margin:0 0 14px; font-size:13px; color:#475569; line-height:1.5;">
							<?php esc_html_e( 'Connect a free IntakeFlow account to get more out of it:', 'xpressui-bridge' ); ?>
						</p>
						<ul style="list-style:none; margin:0 0 18px; padding:0;">
							<li style="display:flex; gap:10px; align-items:flex-start; margin-bottom:9px; font-size:13px; color:#334155; line-height:1.45;">
								<span><strong><?php esc_html_e( 'Cloud backup', 'xpressui-bridge' ); ?></strong> — <?php esc_html_e( 'submissions are stored off your server.', 'xpressui-bridge' ); ?></span>
							</li>
							<li style="display:flex; gap:10px; align-items:flex-start; margin-bottom:9px; font-size:13px; color:#334155; line-height:1.45;">
								<span><strong><?php esc_html_e( 'Visual editor', 'xpressui-bridge' ); ?></strong> — <?php esc_html_e( 'change steps and design without code.', 'xpressui-bridge' ); ?></span>
							</li>
							<li style="display:flex; gap:10px; align-items:flex-start; margin-bottom:9px; font-size:13px; color:#334155; line-height:1.45;">
								<span><strong><?php esc_html_e( 'Webhooks and integrations', 'xpressui-bridge' ); ?></strong> — <?php esc_html_e( 'delivered reliably by the Console.', 'xpressui-bridge' ); ?></span>
							</li>
							<li style="display:flex; gap:10px; align-items:flex-start; margin-bottom:9px; font-size:13px; color:#334155; line-height:1.45;">
								<span><strong><?php esc_html_e( 'Follow-up reminders', 'xpressui-bridge' ); ?></strong> — <?php esc_html_e( 'sent automatically.', 'xpressui-bridge' ); ?></span>
							</li>
							<li style="display:flex; gap:10px; align-items:flex-start; margin-bottom:0; font-size:13px; color:#334155; line-height:1.45;">
								<span><strong><?php esc_html_e( 'Analytics', 'xpressui-bridge' ); ?></strong> — <?php esc_html_e( 'completion and drop-off rates.', 'xpressui-bridge' ); ?></span>
							</li>
						</ul>
						<a href="<?php echo esc_url( $connect_settings_url ); ?>" class="xpui-wiz-btn xpui-wiz-btn-primary" style="width:100%; box-sizing:border-box;">
							<?php esc_html_e( 'Connect IntakeFlow Account', 'xpressui-bridge' ); ?>
						</a>
						<p style="margin:12px 0 0; text-align:center;">
							<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-bridge' ) ); ?>" style="font-size:12px; color:#64748b; text-decoration:underline;">
								<?php esc_html_e( 'Not now — continue to my workflow', 'xpressui-bridge' ); ?>
							</a>
						</p>
					</div>
				</div>
			</div>

			<!-- Footer Buttons -->
			<div class="xpui-wiz-buttons" id="xpui-wiz-buttons-row">
				<button type="button" class="xpui-wiz-btn xpui-wiz-btn-secondary" id="xpui-wiz-prev" style="display:none;">
					<span class="dashicons dashicons-arrow-left-alt2" style="font-size:16px; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center;"></span>
					<?php esc_html_e( 'Back', 'xpressui-bridge' ); ?>
				</button>
				<div style="flex:1;"></div>
				<button type="button" class="xpui-wiz-btn xpui-wiz-btn-primary" id="xpui-wiz-next" disabled>
					<?php esc_html_e( 'Next', 'xpressui-bridge' ); ?>
					<span class="dashicons dashicons-arrow-right-alt2" style="font-size:16px; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center;"></span>
				</button>
			</div>
		</div>
	</div>

	<?php
}

// ---------------------------------------------------------------------------
// Migration nudge: when Contact Form 7 or Gravity Forms is active, invite the
// admin to import their existing forms. This is the plugin's main acquisition
// on-ramp, so it is surfaced once (dismissable, per-user) on the Dashboard,
// Plugins screen and the plugin's own screens — never on the Import tab itself.
// ---------------------------------------------------------------------------

/**
 * Whether the CF7/Gravity Forms import nudge should render on the current screen.
 *
 * @return bool
 */
function xpressui_import_nudge_should_show() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	if ( get_user_meta( get_current_user_id(), 'xpressui_import_nudge_dismissed', true ) ) {
		return false;
	}
	if ( ! post_type_exists( 'wpcf7_contact_form' ) && ! class_exists( 'GFAPI' ) ) {
		return false;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen ) {
		return false;
	}
	$allowed = [ 'dashboard', 'plugins', 'edit-xpressui_submission', 'xpressui_submission_page_xpressui-bridge' ];
	if ( ! in_array( $screen->id, $allowed, true ) ) {
		return false;
	}
	// Don't nag on the Import tab itself. Read the current admin tab via the input-filter
	// API (no direct $_GET superglobal access — read-only navigation, no form processing).
	$tab = sanitize_key( (string) filter_input( INPUT_GET, 'tab' ) );
	if ( 'xpressui_submission_page_xpressui-bridge' === $screen->id && 'import' === $tab ) {
		return false;
	}
	return true;
}

/**
 * Renders the CF7/Gravity Forms migration nudge admin notice.
 */
function xpressui_render_import_nudge() {
	if ( ! xpressui_import_nudge_should_show() ) {
		return;
	}
	$source      = post_type_exists( 'wpcf7_contact_form' ) ? __( 'Contact Form 7', 'xpressui-bridge' ) : __( 'Gravity Forms', 'xpressui-bridge' );
	$import_url  = admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-bridge&tab=import' );
	$dismiss_url = wp_nonce_url( add_query_arg( 'xpressui_dismiss', 'import-nudge' ), 'xpressui_dismiss_import_nudge' );
	?>
	<div class="notice notice-info is-dismissible" style="border-left-color:#2563eb;">
		<p style="font-size:13px;">
			<?php
			/* translators: %s: the detected form plugin name (Contact Form 7 / Gravity Forms). */
			printf( esc_html__( 'IntakeFlow detected %s forms on your site. Import and convert them into IntakeFlow workflows in a few clicks — field types, choices and required rules are preserved.', 'xpressui-bridge' ), '<strong>' . esc_html( $source ) . '</strong>' );
			?>
		</p>
		<p>
			<?php /* translators: %s: the detected form plugin name. */ ?>
			<a href="<?php echo esc_url( $import_url ); ?>" class="button button-primary"><?php printf( esc_html__( 'Import my %s forms', 'xpressui-bridge' ), esc_html( $source ) ); ?></a>
			<a href="<?php echo esc_url( $dismiss_url ); ?>" style="margin-left:8px; color:#64748b; text-decoration:underline;"><?php esc_html_e( 'Dismiss', 'xpressui-bridge' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'xpressui_render_import_nudge' );

/**
 * Persists dismissal of the import nudge (per-user).
 */
function xpressui_handle_import_nudge_dismiss() {
	if ( ! is_admin() || ! isset( $_GET['xpressui_dismiss'] ) ) {
		return;
	}
	if ( 'import-nudge' !== sanitize_key( wp_unslash( $_GET['xpressui_dismiss'] ) ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	check_admin_referer( 'xpressui_dismiss_import_nudge' );
	update_user_meta( get_current_user_id(), 'xpressui_import_nudge_dismissed', 1 );
	wp_safe_redirect( remove_query_arg( [ 'xpressui_dismiss', '_wpnonce' ] ) );
	exit;
}
add_action( 'admin_init', 'xpressui_handle_import_nudge_dismiss' );

