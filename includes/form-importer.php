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
		wp_safe_redirect( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-settings&tab=import' ) );
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

	wp_safe_redirect( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-settings&tab=import' ) );
	exit;
}
add_action( 'admin_init', 'xpressui_handle_form_import_submission' );

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

		$label = ucwords( str_replace( [ '-', '_' ], ' ', $name ) );
		$fields[] = [
			'id'          => $name,
			'label'       => $label,
			'type'        => $field_type,
			'required'    => $is_required,
			'placeholder' => $label,
		];
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

			$fields[] = [
				'id'          => $name,
				'label'       => $label,
				'type'        => $field_type,
				'required'    => $is_required,
				'placeholder' => $label,
			];
		}
	}

	return xpressui_create_imported_workflow_package( $slug, $title, $fields );
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

	$manifest = [
		'projectId'          => 'import_' . uniqid(),
		'projectName'        => $title . ' (' . __( 'Imported', 'xpressui-bridge' ) . ')',
		'projectSlug'        => $slug,
		'configVersion'      => '1.0.0',
		'runtimeTier'        => 'light', // Standalone offline execution
		'isBundled'          => true,
		'manifestFingerprint'=> md5( wp_json_encode( $fields ) ),
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
 * Renders the Form Importer panel.
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

	?>
	<div class="card xpressui-admin-card" style="margin-top: 15px;">
		<h2><?php esc_html_e( 'Form Migrator / Importer', 'xpressui-bridge' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Easily migrate your existing legacy Contact Form 7 or Gravity Forms into modern multi-step IntakeFlow portal workflows.', 'xpressui-bridge' ); ?></p>
		
		<?php if ( ! $has_forms ) : ?>
			<div style="margin-top: 20px; padding: 20px; text-align: center; border: 1.5px dashed #cbd5e1; border-radius: 10px; color: #64748b;">
				<p style="margin: 0; font-size: 15px; font-weight: 600;"><?php esc_html_e( 'No supported form plugins detected.', 'xpressui-bridge' ); ?></p>
				<p style="margin: 5px 0 0; font-size: 13px;"><?php esc_html_e( 'Install and build forms in Contact Form 7 or Gravity Forms to enable 1-click migration.', 'xpressui-bridge' ); ?></p>
			</div>
		<?php else : ?>
			<form method="post" style="margin-top: 25px; max-width: 600px;">
				<?php wp_nonce_field( 'xpressui_import_form_nonce_action', 'xpressui_import_form_nonce' ); ?>
				<input type="hidden" name="xpressui_import_form_action" value="1">

				<div style="margin-bottom: 20px;">
					<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #334155;"><?php esc_html_e( 'Select Source Form to Convert', 'xpressui-bridge' ); ?></label>
					<select name="form_source_type_id" id="xpui-import-select" style="width: 100%; height: 38px; border-radius: 6px;" required>
						<option value=""><?php esc_html_e( 'Choose a form...', 'xpressui-bridge' ); ?></option>
						<?php if ( ! empty( $cf7_forms ) ) : ?>
							<optgroup label="<?php esc_attr_e( 'Contact Form 7', 'xpressui-bridge' ); ?>">
								<?php foreach ( $cf7_forms as $form ) : ?>
									<option value="cf7:<?php echo (int) $form['id']; ?>"><?php echo esc_html( $form['title'] ); ?></option>
								<?php endforeach; ?>
							</optgroup>
						<?php endif; ?>
						<?php if ( ! empty( $gf_forms ) ) : ?>
							<optgroup label="<?php esc_attr_e( 'Gravity Forms', 'xpressui-bridge' ); ?>">
								<?php foreach ( $gf_forms as $form ) : ?>
									<option value="gf:<?php echo (int) $form['id']; ?>"><?php echo esc_html( $form['title'] ); ?></option>
								<?php endforeach; ?>
							</optgroup>
						<?php endif; ?>
					</select>
					<input type="hidden" name="form_source_type" id="xpui-import-source" value="" />
					<input type="hidden" name="source_form_id" id="xpui-import-id" value="" />
				</div>

				<button type="submit" class="button button-primary button-large" style="height: 38px; font-weight: 600; border-radius: 6px;"><?php esc_html_e( 'Convert to IntakeFlow Workflow', 'xpressui-bridge' ); ?></button>
			</form>

			<script type="text/javascript">
			jQuery(document).ready(function($) {
				$('#xpui-import-select').on('change', function() {
					var val = $(this).val();
					if (val) {
						var parts = val.split(':');
						$('#xpui-import-source').val(parts[0]);
						$('#xpui-import-id').val(parts[1]);
					} else {
						$('#xpui-import-source').val('');
						$('#xpui-import-id').val('');
					}
				});
			});
			</script>
		<?php endif; ?>
	</div>
	<?php
}
