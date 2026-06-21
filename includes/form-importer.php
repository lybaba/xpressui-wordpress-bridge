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
 * Imports a Contact Form 7 form as an IntakeFlow workflow.
 */
/**
 * Imports a Contact Form 7 form as an IntakeFlow workflow.
 */
function xpressui_import_cf7_form( $form_id ) {
	$post = get_post( $form_id );
	if ( ! $post || 'wpcf7_contact_form' !== $post->post_type ) {
		return new WP_Error( 'invalid_form', __( 'Contact Form 7 form not found.', 'xpressui-bridge' ) );
	}

	$title = $post->post_title;
	$slug  = 'cf7-import-' . sanitize_title( $title ) . '-' . rand( 100, 999 );
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

	return xpressui_create_imported_workflow_package( $slug, $title, $fields );
}

/**
 * Imports a Gravity Form as an IntakeFlow workflow.
 */
function xpressui_import_gravity_form( $form_id ) {
	if ( ! class_exists( 'GFAPI' ) ) {
		return new WP_Error( 'gf_not_installed', __( 'Gravity Forms API is not available.', 'xpressui-bridge' ) );
	}

	$form = GFAPI::get_form( $form_id );
	if ( ! $form || is_wp_error( $form ) ) {
		return new WP_Error( 'invalid_form', __( 'Gravity Form not found.', 'xpressui-bridge' ) );
	}

	$title = $form['title'];
	$slug  = 'gf-import-' . sanitize_title( $title ) . '-' . rand( 100, 999 );
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

	return xpressui_create_imported_workflow_package( $slug, $title, $fields );
}

/**
 * Helper to construct the ConsoleProjectDocumentPayload for importing to SaaS Console.
 */
function xpressui_build_import_document( $slug, $title, $fields, $steps ) {
	$project_id = 'import_' . uniqid();
	$now_iso = gmdate( 'Y-m-d\TH:i:s\Z' );

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
			'type'             => 'multistepform',
			'name'             => $slug,
			'title'            => $title,
			'mode'             => 'form-multi-step',
			'navigationLabels' => [
				'prevLabel'   => __( 'Back', 'xpressui-bridge' ),
				'nextLabel'   => __( 'Continue', 'xpressui-bridge' ),
				'submitLabel' => __( 'Submit', 'xpressui-bridge' )
			],
			'workflowConfig'   => [
				'submissionMode'     => 'multi-step-submit',
				'providerMode'       => 'wordpress-bridge',
				'resumeSupport'      => 'disabled',
				'documentHandling'   => 'basic-upload',
				'submissionEndpoint' => '/wp-json/xpressui/v1/submit',
				'successMessage'     => __( 'Thank you — your answers are in.', 'xpressui-bridge' ),
				'errorMessage'       => __( 'Something went wrong. Please try again.', 'xpressui-bridge' )
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
				}, $steps )
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
				$type_mapped = $f['type'];
				if ( 'checkbox' === $type_mapped ) {
					$type_mapped = 'checkboxes';
				} elseif ( 'radio' === $type_mapped ) {
					$type_mapped = 'radio-buttons';
				}

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

	$manifest = [
		'projectId'          => 'import_' . uniqid(),
		'projectName'        => $title . ' (' . __( 'Imported', 'xpressui-bridge' ) . ')',
		'projectSlug'        => $slug,
		'configVersion'      => '1.0.0',
		'runtimeTier'        => 'light', // Standalone offline execution
		'isBundled'          => true,
		'manifestFingerprint'=> md5( wp_json_encode( $fields ) ),
		// Declare the on-disk artifacts so the list scan and the shortcode renderer
		// resolve form.config.json (xpressui_get_workflow_artifact_path()).
		'artifacts'          => [
			'config' => 'form.config.json',
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
	$config_written = file_put_contents(
		trailingslashit( $target_dir ) . 'form.config.json',
		wp_json_encode( $form_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
	);
	if ( false === $config_written ) {
		return new WP_Error( 'write_failed', __( 'Could not write form.config.json configuration.', 'xpressui-bridge' ) );
	}

	// Write manifest.json
	$written = file_put_contents(
		trailingslashit( $target_dir ) . 'manifest.json',
		wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES )
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
 * Renders the Form Importer panel.
 */
/**
 * AJAX: handles the guided wizard import process.
 */
add_action( 'wp_ajax_xpressui_import_form_wizard', 'xpressui_ajax_import_form_wizard' );

function xpressui_ajax_import_form_wizard() {
	check_ajax_referer( 'xpressui_import_form_wizard_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'xpressui-bridge' ) ], 403 );
	}

	$source_type = sanitize_key( $_POST['source_type'] ?? '' );
	$form_id     = intval( $_POST['source_form_id'] ?? 0 );
	$custom_name = sanitize_text_field( wp_unslash( $_POST['custom_name'] ?? '' ) );
	$custom_slug = sanitize_title( wp_unslash( $_POST['custom_slug'] ?? '' ) );

	// Destination is decided automatically server-side (source of truth): if the
	// Console connection has an active API token, create the workflow in the SaaS;
	// otherwise save it locally as a standalone workflow. Any client-sent value is ignored.
	$import_mode = xpressui_pro_is_license_active() ? 'saas' : 'local';

	if ( empty( $source_type ) || ! $form_id ) {
		wp_send_json_error( [ 'message' => __( 'Missing source form selection.', 'xpressui-bridge' ) ] );
	}

	// 1. Parse fields and choices from the source form
	$fields = [];
	$title  = '';

	if ( 'cf7' === $source_type ) {
		$post = get_post( $form_id );
		if ( ! $post || 'wpcf7_contact_form' !== $post->post_type ) {
			wp_send_json_error( [ 'message' => __( 'Contact Form 7 form not found.', 'xpressui-bridge' ) ] );
		}
		$title   = $post->post_title;
		$content = $post->post_content;

		preg_match_all( '/\[([a-zA-Z0-9_*]+)\s+([a-zA-Z0-9_-]+)([^\]]*)\]/', $content, $matches, PREG_SET_ORDER );
		if ( empty( $matches ) ) {
			wp_send_json_error( [ 'message' => __( 'No fields detected in Contact Form 7 form.', 'xpressui-bridge' ) ] );
		}

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

	} elseif ( 'gf' === $source_type ) {
		if ( ! class_exists( 'GFAPI' ) ) {
			wp_send_json_error( [ 'message' => __( 'Gravity Forms API is not available.', 'xpressui-bridge' ) ] );
		}
		$form = GFAPI::get_form( $form_id );
		if ( ! $form || is_wp_error( $form ) ) {
			wp_send_json_error( [ 'message' => __( 'Gravity Form not found.', 'xpressui-bridge' ) ] );
		}
		$title = $form['title'];

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
	} else {
		wp_send_json_error( [ 'message' => __( 'Unsupported source form type.', 'xpressui-bridge' ) ] );
	}

	// 2. Resolve final Title & Slug
	if ( ! empty( $custom_name ) ) {
		$title = $custom_name;
	}
	$slug = ! empty( $custom_slug ) ? $custom_slug : sanitize_title( $title ) . '-' . rand( 100, 999 );

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
				/* translators: %s: short human-readable reason the Console could not be reached. */
				'notice'    => sprintf( __( "Couldn't reach your IntakeFlow Console (%s). The workflow was saved locally instead — you can sync it to the Console later.", 'xpressui-bridge' ), $reason ),
				'message'   => sprintf( __( "Couldn't reach your IntakeFlow Console (%s). The workflow was saved locally instead — you can sync it to the Console later.", 'xpressui-bridge' ), $reason ),
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
	$is_saas_connected = xpressui_pro_is_license_active();

	// CTA target for the local-import conversion nudge. Point at the Settings page's
	// Console Connection section (the same 1-Click "Connect IntakeFlow Account" entry
	// point) — robust for a brand-new, never-connected site where the Console URL may
	// not be configured yet, so we can't safely pre-build the wordpress-connect URL.
	$connect_settings_url = admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-settings' );

	?>
	<style>
	.xpui-wiz-container {
		font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
		max-width: 700px;
		margin-top: 15px;
	}
	.xpui-wiz-card {
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 16px;
		padding: 30px;
		box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
	}
	/* Step indicator */
	.xpui-wiz-steps {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 35px;
		position: relative;
	}
	.xpui-wiz-line {
		position: absolute;
		top: 16px;
		left: 10%;
		right: 10%;
		height: 3px;
		background: #e2e8f0;
		z-index: 1;
	}
	.xpui-wiz-line-progress {
		position: absolute;
		top: 16px;
		left: 10%;
		width: 0%;
		height: 3px;
		background: #2563eb;
		z-index: 2;
		transition: width 0.3s ease;
	}
	.xpui-wiz-step-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		z-index: 3;
		flex: 1;
	}
	.xpui-wiz-badge {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		background: #ffffff;
		border: 3px solid #cbd5e1;
		color: #64748b;
		display: flex;
		align-items: center;
		justify-content: center;
		font-weight: 700;
		font-size: 13px;
		transition: all 0.3s ease;
	}
	.xpui-wiz-step-item.active .xpui-wiz-badge {
		border-color: #2563eb;
		color: #2563eb;
		box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
	}
	.xpui-wiz-step-item.completed .xpui-wiz-badge {
		background: #2563eb;
		border-color: #2563eb;
		color: #ffffff;
	}
	.xpui-wiz-step-label {
		font-size: 11px;
		font-weight: 700;
		color: #64748b;
		margin-top: 8px;
		text-transform: uppercase;
		letter-spacing: 0.05em;
	}
	.xpui-wiz-step-item.active .xpui-wiz-step-label {
		color: #0f172a;
	}
	.xpui-wiz-step-item.completed .xpui-wiz-step-label {
		color: #2563eb;
	}

	/* Step bodies */
	.xpui-wiz-step-body {
		display: none;
	}
	.xpui-wiz-step-body.active {
		display: block;
		animation: xpui-fade-in 0.3s ease;
	}
	@keyframes xpui-fade-in {
		from { opacity: 0; transform: translateY(5px); }
		to { opacity: 1; transform: translateY(0); }
	}

	/* Progress indicator */
	.xpui-wiz-progress-container {
		margin: 25px 0;
	}
	.xpui-wiz-progress-bar {
		width: 100%;
		height: 12px;
		background: #e2e8f0;
		border-radius: 9999px;
		overflow: hidden;
		position: relative;
	}
	.xpui-wiz-progress-fill {
		height: 100%;
		background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%);
		width: 0%;
		border-radius: 9999px;
		transition: width 0.5s ease;
	}
	.xpui-wiz-progress-steps {
		margin-top: 20px;
		list-style: none;
		padding: 0;
	}
	.xpui-wiz-progress-step {
		display: flex;
		align-items: center;
		gap: 10px;
		font-size: 13.5px;
		color: #64748b;
		margin-bottom: 10px;
		font-weight: 500;
	}
	.xpui-wiz-progress-step.done {
		color: #16a34a;
	}
	.xpui-wiz-progress-step.active {
		color: #2563eb;
		font-weight: 600;
	}
	.xpui-wiz-progress-step-icon {
		width: 18px;
		height: 18px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-weight: bold;
	}

	/* Finish card */
	.xpui-wiz-success-card {
		text-align: center;
		padding: 15px 0;
	}
	.xpui-wiz-success-icon {
		width: 56px;
		height: 56px;
		background: #dcfce7;
		color: #15803d;
		border-radius: 50%;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		font-size: 28px;
		margin-bottom: 15px;
	}
	.xpui-wiz-code-box {
		background: #f8fafc;
		border: 1px solid #e2e8f0;
		border-radius: 8px;
		padding: 12px 15px;
		display: inline-flex;
		align-items: center;
		justify-content: space-between;
		width: 100%;
		max-width: 450px;
		margin: 20px 0;
		box-sizing: border-box;
	}
	.xpui-wiz-code {
		font-family: monospace;
		font-size: 13px;
		font-weight: 700;
		color: #0f172a;
	}
	.xpui-wiz-copy-btn {
		background: #ffffff;
		border: 1px solid #cbd5e1;
		border-radius: 6px;
		padding: 4px 10px;
		font-size: 11px;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.2s;
	}
	.xpui-wiz-copy-btn:hover {
		border-color: #2563eb;
		color: #2563eb;
	}

	/* Buttons */
	.xpui-wiz-buttons {
		display: flex;
		justify-content: space-between;
		margin-top: 30px;
		border-top: 1px solid #f1f5f9;
		padding-top: 20px;
	}
	.xpui-wiz-btn {
		height: 38px;
		font-weight: 600;
		border-radius: 6px;
		cursor: pointer;
		transition: all 0.2s;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 6px;
		padding: 0 16px;
		box-sizing: border-box;
		font-size: 13px;
	}
	.xpui-wiz-btn-primary {
		background: #2563eb;
		border: 1px solid #2563eb;
		color: #ffffff;
	}
	.xpui-wiz-btn-primary:hover {
		background: #1d4ed8;
		border-color: #1d4ed8;
	}
	.xpui-wiz-btn-secondary {
		background: #ffffff;
		border: 1px solid #cbd5e1;
		color: #475569;
	}
	.xpui-wiz-btn-secondary:hover {
		border-color: #94a3b8;
		color: #334155;
	}
	.xpui-wiz-btn:disabled {
		opacity: 0.5;
		cursor: not-allowed;
	}
	</style>

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
					<div style="display:flex; align-items:flex-start; gap:10px; padding:14px 16px; border:1px solid #e2e8f0; background:#f8fafc; border-radius:10px; margin-bottom:20px;">
						<span style="font-size:18px; line-height:1.2;">💾</span>
						<div style="font-size:13px; color:#475569; line-height:1.5;">
							<strong><?php esc_html_e( 'Destination: Local (Standalone)', 'xpressui-bridge' ); ?></strong><br>
							<?php esc_html_e( 'Saved locally in WordPress (no Console connection). Connect your API token in the General Settings tab to create workflows in the Console instead.', 'xpressui-bridge' ); ?>
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
					
					<div class="xpui-wiz-code-box">
						<span class="xpui-wiz-code" id="xpui-wiz-shortcode">[xpressui id=""]</span>
						<button type="button" class="xpui-wiz-copy-btn" id="xpui-wiz-copy-btn" data-clipboard-text=""><?php esc_html_e( 'Copy', 'xpressui-bridge' ); ?></button>
					</div>

					<p style="margin-top:15px; display:flex; gap:10px; justify-content:center;" id="xpui-wiz-success-actions">
						<a href="" target="_blank" rel="noopener noreferrer" class="xpui-wiz-btn xpui-wiz-btn-primary" id="xpui-wiz-edit-btn" style="display:none;">
							⚡ <?php esc_html_e( 'Edit in SaaS Console', 'xpressui-bridge' ); ?>
						</a>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-bridge' ) ); ?>" class="xpui-wiz-btn xpui-wiz-btn-secondary">
							📂 <?php esc_html_e( 'View Workflows', 'xpressui-bridge' ); ?>
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
							🚀 <?php esc_html_e( 'Workflow created locally — connect to unlock the full product', 'xpressui-bridge' ); ?>
						</h3>
						<p style="margin:0 0 14px; font-size:13px; color:#475569; line-height:1.5;">
							<?php esc_html_e( 'Your workflow runs right now on this site. Connect a free IntakeFlow account to unlock the rest:', 'xpressui-bridge' ); ?>
						</p>
						<ul style="list-style:none; margin:0 0 18px; padding:0;">
							<li style="display:flex; gap:10px; align-items:flex-start; margin-bottom:9px; font-size:13px; color:#334155; line-height:1.45;">
								<span style="font-size:15px; line-height:1.3;">☁️</span>
								<span><strong><?php esc_html_e( 'Cloud backup of submissions', 'xpressui-bridge' ); ?></strong> — <?php esc_html_e( 'every entry safely stored off your server.', 'xpressui-bridge' ); ?></span>
							</li>
							<li style="display:flex; gap:10px; align-items:flex-start; margin-bottom:9px; font-size:13px; color:#334155; line-height:1.45;">
								<span style="font-size:15px; line-height:1.3;">🎨</span>
								<span><strong><?php esc_html_e( 'Visual multi-step editor', 'xpressui-bridge' ); ?></strong> — <?php esc_html_e( 'redesign and re-order steps without touching code.', 'xpressui-bridge' ); ?></span>
							</li>
							<li style="display:flex; gap:10px; align-items:flex-start; margin-bottom:9px; font-size:13px; color:#334155; line-height:1.45;">
								<span style="font-size:15px; line-height:1.3;">🔗</span>
								<span><strong><?php esc_html_e( 'Reliable integrations & webhooks', 'xpressui-bridge' ); ?></strong> — <?php esc_html_e( 'delivered server-side by the Console.', 'xpressui-bridge' ); ?></span>
							</li>
							<li style="display:flex; gap:10px; align-items:flex-start; margin-bottom:9px; font-size:13px; color:#334155; line-height:1.45;">
								<span style="font-size:15px; line-height:1.3;">⏰</span>
								<span><strong><?php esc_html_e( 'Automated reminders', 'xpressui-bridge' ); ?></strong> — <?php esc_html_e( 'follow up with people automatically.', 'xpressui-bridge' ); ?></span>
							</li>
							<li style="display:flex; gap:10px; align-items:flex-start; margin-bottom:0; font-size:13px; color:#334155; line-height:1.45;">
								<span style="font-size:15px; line-height:1.3;">📊</span>
								<span><strong><?php esc_html_e( 'Analytics', 'xpressui-bridge' ); ?></strong> — <?php esc_html_e( 'see completion rates and where people drop off.', 'xpressui-bridge' ); ?></span>
							</li>
						</ul>
						<a href="<?php echo esc_url( $connect_settings_url ); ?>" class="xpui-wiz-btn xpui-wiz-btn-primary" style="width:100%; box-sizing:border-box;">
							🔌 <?php esc_html_e( 'Connect IntakeFlow Account', 'xpressui-bridge' ); ?>
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

	<script type="text/javascript">
	jQuery(document).ready(function($) {
		var currentStep = 1;
		var sourceFormVal = '';
		var isSaasConnected = <?php echo $is_saas_connected ? 'true' : 'false'; ?>;
		var importMode = isSaasConnected ? 'saas' : 'local';

		// Step Navigation Line & Badge updates
		function updateStepUi() {
			$('.xpui-wiz-step-item').removeClass('active completed');
			for(var s=1; s<=4; s++) {
				var item = $('.xpui-wiz-step-item[data-step="' + s + '"]');
				if (s < currentStep) {
					item.addClass('completed');
				} else if (s === currentStep) {
					item.addClass('active');
				}
			}
			
			// line progress
			var progressPercent = 0;
			if (currentStep === 2) progressPercent = 33;
			if (currentStep === 3) progressPercent = 66;
			if (currentStep === 4) progressPercent = 100;
			$('#xpui-line-progress').css('width', progressPercent + '%');

			// button row toggles
			if (currentStep === 1) {
				$('#xpui-wiz-prev').hide();
				$('#xpui-wiz-next').show().html('<?php echo esc_js( __( 'Next', 'xpressui-bridge' ) ); ?> <span class="dashicons dashicons-arrow-right-alt2" style="font-size:16px; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center;"></span>').prop('disabled', !sourceFormVal);
			} else if (currentStep === 2) {
				$('#xpui-wiz-prev').show();
				$('#xpui-wiz-next').show().html('<?php echo esc_js( __( 'Convert & Sync', 'xpressui-bridge' ) ); ?> <span class="dashicons dashicons-arrow-right-alt2" style="font-size:16px; width:16px; height:16px; display:inline-flex; align-items:center; justify-content:center;"></span>').prop('disabled', false);
			} else {
				// Step 3 (Processing) and Step 4 (Finish) hide the footer buttons row
				$('#xpui-wiz-buttons-row').hide();
			}

			// show hide step bodies
			$('.xpui-wiz-step-body').removeClass('active');
			$('#xpui-wiz-step-' + currentStep + '-body').addClass('active');
		}

		// Step 1 input selection
		$('#xpui-wizard-select').on('change', function() {
			sourceFormVal = $(this).val();
			var title = $(this).find('option:selected').data('title') || '';
			if (title) {
				$('#xpui-wizard-name').val(title);
				var cleanSlug = title.toLowerCase().replace(/[^a-z0-9_-]+/g, '-').replace(/^-+|-+$/g, '');
				$('#xpui-wizard-slug').val(cleanSlug);
			}
			updateStepUi();
		});

		// Destination is decided automatically (saas when connected, else local) — see
		// `importMode` above; the server re-derives it authoritatively, so no UI choice.

		// Footer buttons handlers
		$('#xpui-wiz-next').on('click', function() {
			if (currentStep === 1) {
				currentStep = 2;
				updateStepUi();
			} else if (currentStep === 2) {
				currentStep = 3;
				updateStepUi();
				startConversion();
			}
		});

		$('#xpui-wiz-prev').on('click', function() {
			if (currentStep === 2) {
				currentStep = 1;
				updateStepUi();
			} else if (currentStep === 3) {
				currentStep = 2;
				updateStepUi();
			}
		});

		// Copy shortcode to clipboard
		$('#xpui-wiz-copy-btn').on('click', function() {
			var text = $(this).attr('data-clipboard-text');
			var $btn = $(this);
			navigator.clipboard.writeText(text).then(function() {
				$btn.text('<?php echo esc_js( __( 'Copied!', 'xpressui-bridge' ) ); ?>').css('background', '#dcfce7');
				setTimeout(function() {
					$btn.text('<?php echo esc_js( __( 'Copy', 'xpressui-bridge' ) ); ?>').css('background', '#ffffff');
				}, 1500);
			});
		});

		// Start AJAX Conversion & Sync
		function startConversion() {
			var parts = sourceFormVal.split(':');
			var sourceType = parts[0];
			var sourceId = parts[1];

			// Reset progress screen
			$('#xpui-progress-fill').css({
				'width': '10%',
				'background': 'linear-gradient(90deg, #3b82f6 0%, #1d4ed8 100%)'
			});
			
			$('.xpui-wiz-progress-step').removeClass('done active error');
			$('.xpui-wiz-progress-step-text').css('color', '');
			
			$('#xpui-prog-step-1').addClass('active').find('.xpui-wiz-progress-step-icon').text('⏳');
			$('#xpui-prog-step-2').find('.xpui-wiz-progress-step-icon').text('⏳');
			$('#xpui-prog-step-3').find('.xpui-wiz-progress-step-icon').text('⏳');

			if (importMode === 'saas') {
				$('#xpui-prog-text-2').text('<?php echo esc_js( __( 'Uploading and creating project in SaaS Console...', 'xpressui-bridge' ) ); ?>');
				$('#xpui-prog-text-3').text('<?php echo esc_js( __( 'Downloading and synchronizing workflow files...', 'xpressui-bridge' ) ); ?>');
			} else {
				$('#xpui-prog-text-2').text('<?php echo esc_js( __( 'Generating local standalone workflow package...', 'xpressui-bridge' ) ); ?>');
				$('#xpui-prog-text-3').text('<?php echo esc_js( __( 'Import complete!', 'xpressui-bridge' ) ); ?>');
			}

			setTimeout(function() {
				// Parse stage done
				$('#xpui-progress-fill').css('width', '40%');
				$('#xpui-prog-step-1').removeClass('active').addClass('done').find('.xpui-wiz-progress-step-icon').text('✓');
				$('#xpui-prog-step-2').addClass('active');

				// Fire AJAX request
				var data = {
					action: 'xpressui_import_form_wizard',
					nonce: '<?php echo wp_create_nonce( "xpressui_import_form_wizard_nonce" ); ?>',
					source_type: sourceType,
					source_form_id: sourceId,
					import_mode: importMode,
					custom_name: $('#xpui-wizard-name').val(),
					custom_slug: $('#xpui-wizard-slug').val()
				};

				$.ajax({
					url: '<?php echo admin_url( "admin-ajax.php" ); ?>',
					type: 'POST',
					data: data,
					success: function(response) {
						if (!response.success) {
							// Handle error in Step 3
							$('#xpui-progress-fill').css('background', '#ef4444');
							$('#xpui-prog-step-2').removeClass('active').addClass('error');
							$('#xpui-prog-step-2').find('.xpui-wiz-progress-step-icon').text('❌');
							$('#xpui-prog-text-2').css('color', '#ef4444').text(response.data.message);
							// Provide back button to try again
							$('#xpui-wiz-buttons-row').show();
							$('#xpui-wiz-prev').show();
							$('#xpui-wiz-next').hide();
							return;
						}

						// Step 2 done
						$('#xpui-progress-fill').css('width', '75%');
						$('#xpui-prog-step-2').removeClass('active').addClass('done').find('.xpui-wiz-progress-step-icon').text('✓');
						$('#xpui-prog-step-3').addClass('active');

						setTimeout(function() {
							// Step 3 done
							$('#xpui-progress-fill').css('width', '100%');
							$('#xpui-prog-step-3').removeClass('active').addClass('done').find('.xpui-wiz-progress-step-icon').text('✓');

							setTimeout(function() {
								// Move to Step 4 (Finish)
								currentStep = 4;
								updateStepUi();

								// Fill success details
								$('#xpui-wiz-shortcode').text(response.data.shortcode);
								$('#xpui-wiz-copy-btn').attr('data-clipboard-text', response.data.shortcode);
								$('#xpui-wiz-success-msg').text(response.data.message);

								// Fallback (Console unreachable → saved locally): show the
								// notice as an info banner, not a SaaS-synced success.
								$('#xpui-wiz-fallback-notice').remove();
								if (response.data.fallback) {
									$('#xpui-wiz-success-title').text('<?php echo esc_js( __( 'Saved Locally', 'xpressui-bridge' ) ); ?>');
									$('<div id="xpui-wiz-fallback-notice" style="margin:15px auto 0; max-width:450px; text-align:left; display:flex; gap:10px; align-items:flex-start; padding:12px 14px; border:1px solid #fde68a; background:#fffbeb; border-radius:10px;"></div>')
										.append($('<span style="font-size:16px; line-height:1.2;"></span>').text('⚠️'))
										.append($('<div style="font-size:13px; color:#92400e; line-height:1.5;"></div>').text(response.data.notice || response.data.message))
										.insertAfter('#xpui-wiz-success-msg');
								}

								if (response.data.saas && response.data.edit_url) {
									$('#xpui-wiz-edit-btn').attr('href', response.data.edit_url).show();
								} else {
									$('#xpui-wiz-edit-btn').hide();
								}

								// Conversion nudge: show it ONLY for a LOCAL import (no SaaS
								// account, or fallback-to-local). On a connected SaaS import
								// keep the normal success unchanged. response.data.saas is
								// true only when the workflow was created on the Console.
								if (response.data.saas) {
									$('#xpui-wiz-connect-nudge').hide();
									$('#xpui-wiz-success-actions').show();
								} else {
									// The nudge carries its own primary CTA + "continue to my
									// workflow" link, so hide the default action row to avoid
									// a duplicate "View Workflows" button.
									$('#xpui-wiz-success-actions').hide();
									$('#xpui-wiz-connect-nudge').show();
								}
							}, 800);
						}, 600);
					},
					error: function() {
						$('#xpui-progress-fill').css('background', '#ef4444');
						$('#xpui-prog-step-2').removeClass('active').addClass('error');
						$('#xpui-prog-step-2').find('.xpui-wiz-progress-step-icon').text('❌');
						$('#xpui-prog-text-2').css('color', '#ef4444').text('<?php echo esc_js( __( "Network error. Please try again.", "xpressui-bridge" ) ); ?>');
						$('#xpui-wiz-buttons-row').show();
						$('#xpui-wiz-prev').show();
						$('#xpui-wiz-next').hide();
					}
				});

			}, 1000);
		}

		function escapeHtml(value) {
			return String(value || '').replace(/[&<>"']/g, function (char) {
				return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
			});
		}

		// Init UI
		updateStepUi();
	});
	</script>
	<?php
}

