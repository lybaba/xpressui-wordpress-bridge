<?php
/**
 * Workflow overlay: per-workflow customizations stored in wp_options.
 *
 * The overlay is a minimal diff applied on top of the static template context
 * at render time. Only values that differ from the pack defaults are stored.
 *
 * Overlay structure:
 *   [
 *     'project_name' => 'Custom form title',
 *     'sections'     => [ 'section_name' => 'Custom section label', ... ],
 *     'fields'       => [
 *       'fieldname'  => [
 *         'label'         => 'Custom label',
 *         'required'      => true|false,      // omit to keep pack default
 *         'placeholder'   => 'Hint text',
 *         'desc'          => 'Help text',
 *         'error_message' => 'Custom error',
 *         'choices'       => [
 *           'choice_value' => [
 *             'label'   => 'Custom choice label',
 *             'enabled' => true|false,
 *           ],
 *         ],
 *       ],
 *       ...
 *     ],
 *   ]
 *
 * @package XPressUI_Bridge_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns whether a field supports min/max choice limits.
 */
function xpressui_pro_overlay_supports_choice_limits( array $field ): bool {
	$field_type = (string) ( $field['type'] ?? '' );
	$multiple   = ! empty( $field['multiple'] );

	return $multiple || in_array( $field_type, [ 'select-multiple', 'checkboxes' ], true );
}

/**
 * Returns whether a field supports regex pattern validation.
 */
function xpressui_pro_overlay_supports_pattern( array $field ): bool {
	$field_type = (string) ( $field['type'] ?? '' );

	return in_array( $field_type, [ 'text', 'email', 'tel', 'url', 'search', 'slug' ], true );
}

/**
 * Returns the wp_options key for a workflow's overlay.
 */
function xpressui_pro_get_overlay_option_key( string $slug ): string {
	return 'xpressui_overlay_' . $slug;
}

/**
 * Normalize a choice overlay entry while staying backward-compatible with
 * older string-only label overrides.
 *
 * @param mixed $entry Raw overlay entry.
 * @return array{label:string, has_label:bool, enabled:bool, has_enabled:bool}
 */
function xpressui_pro_normalize_choice_overlay_entry( $entry ): array {
	$normalized = [
		'label'       => '',
		'has_label'   => false,
		'enabled'     => true,
		'has_enabled' => false,
	];

	if ( is_string( $entry ) ) {
		$label = trim( $entry );
		if ( $label !== '' ) {
			$normalized['label']     = $label;
			$normalized['has_label'] = true;
		}
		return $normalized;
	}

	if ( ! is_array( $entry ) ) {
		return $normalized;
	}

	if ( isset( $entry['label'] ) ) {
		$label = trim( (string) $entry['label'] );
		if ( $label !== '' ) {
			$normalized['label']     = $label;
			$normalized['has_label'] = true;
		}
	}

	if ( array_key_exists( 'enabled', $entry ) ) {
		$normalized['enabled']     = (bool) $entry['enabled'];
		$normalized['has_enabled'] = true;
	}

	return $normalized;
}

/**
 * Load the customization overlay for a workflow.
 *
 * @return array<string, mixed>
 */
function xpressui_pro_load_workflow_overlay( string $slug ): array {
	$value = get_option( xpressui_pro_get_overlay_option_key( $slug ), [] );
	return is_array( $value ) ? $value : [];
}

/**
 * Save the customization overlay for a workflow.
 *
 * @param array<string, mixed> $overlay
 */
function xpressui_pro_save_workflow_overlay( string $slug, array $overlay ): void {
	update_option( xpressui_pro_get_overlay_option_key( $slug ), $overlay, false );
}

/**
 * Delete the customization overlay for a workflow (reset to pack defaults).
 */
function xpressui_pro_delete_workflow_overlay( string $slug ): void {
	delete_option( xpressui_pro_get_overlay_option_key( $slug ) );
}

/**
 * Apply a customization overlay onto a template context array.
 *
 * Patches rendered_form and runtime.form_config_json in a single pass.
 * Only non-empty overlay values are applied; pack defaults are preserved
 * for any key absent from the overlay.
 *
 * @param array<string, mixed> $context Original template context.
 * @param array<string, mixed> $overlay Customization overlay.
 * @return array<string, mixed> Patched context.
 */
function xpressui_pro_apply_workflow_overlay( array $context, array $overlay ): array {
	$project_name     = isset( $overlay['project_name'] ) ? trim( (string) $overlay['project_name'] ) : '';

	if ( isset( $context['rendered_form'] ) && is_array( $context['rendered_form'] ) ) {
		$context['rendered_form'] = xpressui_pro_patch_rendered_form( $context['rendered_form'], $overlay );
	}

	if ( isset( $context['theme'] ) && is_array( $context['theme'] ) && ! empty( $overlay['theme'] ) ) {
		$context['theme'] = xpressui_pro_patch_theme( $context['theme'], $overlay['theme'] );
	}

	$project_bg = isset( $overlay['project_background_image_url'] ) ? trim( (string) $overlay['project_background_image_url'] ) : '';
	if ( isset( $context['project'] ) && is_array( $context['project'] ) ) {
		if ( $project_name !== '' ) {
			$context['project']['name'] = $project_name;
		}
		if ( $project_bg !== '' ) {
			$context['project']['background_image_url'] = $project_bg;
		}
	}

	if ( isset( $context['runtime']['form_config_json'] ) && is_string( $context['runtime']['form_config_json'] ) ) {
		$cfg = json_decode( $context['runtime']['form_config_json'], true );
		if ( is_array( $cfg ) ) {
			$cfg     = xpressui_pro_patch_form_config( $cfg, $overlay );
			$patched = wp_json_encode( $cfg );
			if ( $patched !== false ) {
				$context['runtime']['form_config_json'] = $patched;
			}
		}
	}

	return $context;
}

function xpressui_pro_patch_rendered_form( array $rendered_form, array $overlay ): array {
	$sections_overlay = isset( $overlay['sections'] ) && is_array( $overlay['sections'] ) ? $overlay['sections'] : [];
	$fields_overlay   = isset( $overlay['fields'] ) && is_array( $overlay['fields'] ) ? $overlay['fields'] : [];
	$navigation       = isset( $overlay['navigation'] ) && is_array( $overlay['navigation'] ) ? $overlay['navigation'] : [];
	$project_name     = isset( $overlay['project_name'] ) ? trim( (string) $overlay['project_name'] ) : '';

	if ( $project_name !== '' ) {
		$rendered_form['title'] = $project_name;
	}

	// Navigation button labels (rendered by the PHP template — the web component reuses these DOM buttons).
	if ( ! empty( $navigation ) ) {
		$v = isset( $navigation['prev'] ) ? trim( (string) $navigation['prev'] ) : '';
		if ( $v !== '' ) {
			$rendered_form['navigation_labels']['previous'] = $v;
		}
		$v = isset( $navigation['next'] ) ? trim( (string) $navigation['next'] ) : '';
		if ( $v !== '' ) {
			$rendered_form['navigation_labels']['next'] = $v;
		}
		$v = isset( $navigation['submit'] ) ? trim( (string) $navigation['submit'] ) : '';
		if ( $v !== '' ) {
			$rendered_form['submit_label'] = $v;
		}
	}

	// Section labels.
	if ( ! empty( $sections_overlay ) && isset( $rendered_form['sections'] ) ) {
		foreach ( $rendered_form['sections'] as &$section ) {
			$sname = (string) ( $section['name'] ?? '' );
			if ( $sname !== '' && isset( $sections_overlay[ $sname ] ) ) {
				$custom = trim( (string) $sections_overlay[ $sname ] );
				if ( $custom !== '' ) {
					$section['label'] = $custom;
				}
			}
		}
		unset( $section );
	}

	// Field properties.
	if ( ! empty( $fields_overlay ) && isset( $rendered_form['sections'] ) ) {
		foreach ( $rendered_form['sections'] as &$section ) {
			if ( ! isset( $section['fields'] ) || ! is_array( $section['fields'] ) ) {
				continue;
			}
			foreach ( $section['fields'] as &$field ) {
				$fname = (string) ( $field['name'] ?? '' );
				if ( $fname === '' || ! isset( $fields_overlay[ $fname ] ) ) {
					continue;
				}
				$fo = $fields_overlay[ $fname ];

				$v = isset( $fo['label'] ) ? trim( (string) $fo['label'] ) : '';
				if ( $v !== '' ) {
					$field['label'] = $v;
				}

				if ( array_key_exists( 'required', $fo ) ) {
					$field['required'] = (bool) $fo['required'];
				}

				$v = isset( $fo['placeholder'] ) ? trim( (string) $fo['placeholder'] ) : '';
				if ( $v !== '' ) {
					$field['placeholder'] = $v;
				}

				$v = isset( $fo['desc'] ) ? trim( (string) $fo['desc'] ) : '';
				if ( $v !== '' ) {
					$field['desc'] = $v;
				}

				$v = isset( $fo['error_message'] ) ? trim( (string) $fo['error_message'] ) : '';
				if ( $v !== '' ) {
					$field['error_message'] = $v;
				}

				$v = isset( $fo['pattern'] ) ? trim( (string) $fo['pattern'] ) : '';
				if ( $v !== '' && xpressui_pro_overlay_supports_pattern( $field ) ) {
					$field['pattern'] = $v;
				}

				foreach (
					[
						'min_len'             => 'min_len',
						'max_len'             => 'max_len',
						'accept'              => 'accept',
						'min_value'           => 'min_value',
						'max_value'           => 'max_value',
						'step_value'          => 'step_value',
						'upload_accept_label' => 'upload_accept_label',
					] as $overlay_key => $field_key
				) {
					if ( array_key_exists( $overlay_key, $fo ) && '' !== (string) $fo[ $overlay_key ] ) {
						$field[ $field_key ] = $fo[ $overlay_key ];
					}
				}
				if ( xpressui_pro_overlay_supports_choice_limits( $field ) ) {
					foreach ( [ 'min_choices' => 'min_choices', 'max_choices' => 'max_choices' ] as $overlay_key => $field_key ) {
						if ( array_key_exists( $overlay_key, $fo ) && '' !== (string) $fo[ $overlay_key ] ) {
							$field[ $field_key ] = $fo[ $overlay_key ];
						}
					}
				}

				// Choice labels and enabled state.
				$choices_overlay = isset( $fo['choices'] ) && is_array( $fo['choices'] ) ? $fo['choices'] : [];
				if ( ! empty( $choices_overlay ) && isset( $field['choices'] ) && is_array( $field['choices'] ) ) {
					$filtered              = [];
					$pack_choices_by_value = [];
					foreach ( $field['choices'] as $choice ) {
						$cv = (string) ( $choice['value'] ?? '' );
						if ( $cv !== '' ) {
							$pack_choices_by_value[ $cv ] = $choice;
						}
					}
					foreach ( $choices_overlay as $cv => $choice_overlay_raw ) {
						$cv = (string) $cv;
						if ( isset( $pack_choices_by_value[ $cv ] ) ) {
							$choice         = $pack_choices_by_value[ $cv ];
							$choice_overlay = xpressui_pro_normalize_choice_overlay_entry( $choice_overlay_raw );
							if ( $choice_overlay['has_enabled'] && ! $choice_overlay['enabled'] ) {
								unset( $pack_choices_by_value[ $cv ] );
								continue; // Remove disabled choice entirely.
							}
							if ( $choice_overlay['has_label'] ) {
								$choice['label'] = $choice_overlay['label'];
							}
							$filtered[] = $choice;
							unset( $pack_choices_by_value[ $cv ] );
						}
					}
					// Append any remaining pack choices that were not reordered in the overlay
					foreach ( $pack_choices_by_value as $choice ) {
						$filtered[] = $choice;
					}
					$field['choices'] = array_values( $filtered );
				}
			}
			unset( $field );
		}
		unset( $section );
	}

	return $rendered_form;
}

function xpressui_pro_patch_theme( array $theme, array $overlay_theme ): array {
	if ( ! empty( $overlay_theme['background_style'] ) ) {
		$theme['background_style'] = $overlay_theme['background_style'];
	}
	if ( ! empty( $overlay_theme['font_family'] ) ) {
		$theme['font_family'] = $overlay_theme['font_family'];
	}

	if ( isset( $overlay_theme['colors'] ) && is_array( $overlay_theme['colors'] ) ) {
		if ( ! isset( $theme['colors'] ) ) {
			$theme['colors'] = [];
		}
		foreach ( [ 'primary', 'surface', 'page_background', 'text', 'muted_text', 'border' ] as $color ) {
			if ( ! empty( $overlay_theme['colors'][ $color ] ) ) {
				$theme['colors'][ $color ] = $overlay_theme['colors'][ $color ];
			}
		}
	}
	if ( isset( $overlay_theme['radius'] ) && is_array( $overlay_theme['radius'] ) ) {
		if ( ! isset( $theme['radius'] ) ) {
			$theme['radius'] = [];
		}
		foreach ( [ 'card', 'input', 'button' ] as $radius ) {
			if ( array_key_exists( $radius, $overlay_theme['radius'] ) ) {
				$theme['radius'][ $radius ] = $overlay_theme['radius'][ $radius ];
			}
		}
	}
	return $theme;
}

function xpressui_pro_patch_form_config( array $cfg, array $overlay ): array {
	$sections_overlay = isset( $overlay['sections'] ) && is_array( $overlay['sections'] ) ? $overlay['sections'] : [];
	$fields_overlay   = isset( $overlay['fields'] ) && is_array( $overlay['fields'] ) ? $overlay['fields'] : [];
	$navigation       = isset( $overlay['navigation'] ) && is_array( $overlay['navigation'] ) ? $overlay['navigation'] : [];
	$project_name     = isset( $overlay['project_name'] ) ? trim( (string) $overlay['project_name'] ) : '';
	$success_message  = isset( $overlay['success_message'] ) ? trim( (string) $overlay['success_message'] ) : '';
	$form_error_msg   = isset( $overlay['error_message'] ) ? trim( (string) $overlay['error_message'] ) : '';

	// Form title.
	if ( $project_name !== '' && array_key_exists( 'title', $cfg ) ) {
		$cfg['title'] = $project_name;
	}

	// Navigation button labels.
	if ( ! empty( $navigation ) ) {
		foreach ( [ 'prev' => 'prevLabel', 'next' => 'nextLabel', 'submit' => 'submitLabel' ] as $key => $cfg_key ) {
			$v = isset( $navigation[ $key ] ) ? trim( (string) $navigation[ $key ] ) : '';
			if ( $v !== '' ) {
				$cfg['navigationLabels'][ $cfg_key ] = $v;
				// Also patch project-level navigationLabels if present.
				if ( isset( $cfg['project']['navigationLabels'] ) ) {
					$cfg['project']['navigationLabels'][ $cfg_key ] = $v;
				}
			}
		}
	}

	// Success / error messages (workflowConfig + submitFeedback).
	if ( $success_message !== '' ) {
		if ( isset( $cfg['workflowConfig']['successMessage'] ) ) {
			$cfg['workflowConfig']['successMessage'] = $success_message;
		}
		if ( isset( $cfg['submitFeedback']['success_message'] ) ) {
			$cfg['submitFeedback']['success_message'] = $success_message;
		}
	}
	if ( $form_error_msg !== '' ) {
		if ( isset( $cfg['workflowConfig']['errorMessage'] ) ) {
			$cfg['workflowConfig']['errorMessage'] = $form_error_msg;
		}
		if ( isset( $cfg['submitFeedback']['error_message'] ) ) {
			$cfg['submitFeedback']['error_message'] = $form_error_msg;
		}
	}

	// Sections and fields.
	if ( isset( $cfg['sections'] ) && is_array( $cfg['sections'] ) ) {
		// Section labels (sections.custom[]).
		if ( ! empty( $sections_overlay ) && isset( $cfg['sections']['custom'] ) && is_array( $cfg['sections']['custom'] ) ) {
			foreach ( $cfg['sections']['custom'] as &$cs ) {
				$sname = (string) ( $cs['name'] ?? '' );
				if ( $sname !== '' && isset( $sections_overlay[ $sname ] ) ) {
					$custom = trim( (string) $sections_overlay[ $sname ] );
					if ( $custom !== '' ) {
						$cs['label']      = $custom;
						$cs['adminLabel'] = $custom;
					}
				}
			}
			unset( $cs );
		}

		// Field properties.
		if ( ! empty( $fields_overlay ) ) {
			foreach ( $cfg['sections'] as $skey => &$sfields ) {
				if ( $skey === 'custom' || ! is_array( $sfields ) ) {
					continue;
				}
				foreach ( $sfields as &$field ) {
					$fname = (string) ( $field['name'] ?? '' );
					if ( $fname === '' || ! isset( $fields_overlay[ $fname ] ) ) {
						continue;
					}
					$fo = $fields_overlay[ $fname ];

					$v = isset( $fo['label'] ) ? trim( (string) $fo['label'] ) : '';
					if ( $v !== '' ) {
						$field['label'] = $v;
					}

					if ( array_key_exists( 'required', $fo ) ) {
						$field['required'] = (bool) $fo['required'];
					}

					$v = isset( $fo['placeholder'] ) ? trim( (string) $fo['placeholder'] ) : '';
					if ( $v !== '' ) {
						$field['placeholder'] = $v;
					}

					$v = isset( $fo['desc'] ) ? trim( (string) $fo['desc'] ) : '';
					if ( $v !== '' ) {
						$field['desc'] = $v;
					}

					$v = isset( $fo['error_message'] ) ? trim( (string) $fo['error_message'] ) : '';
					if ( $v !== '' ) {
						$field['errorMsg'] = $v;
					}

					$v = isset( $fo['pattern'] ) ? trim( (string) $fo['pattern'] ) : '';
					if ( $v !== '' && xpressui_pro_overlay_supports_pattern( $field ) ) {
						$field['pattern'] = $v;
					}

					if ( array_key_exists( 'min_len', $fo ) ) {
						$field['minLen'] = (int) $fo['min_len'];
					}
					if ( array_key_exists( 'max_len', $fo ) ) {
						$field['maxLen'] = (int) $fo['max_len'];
					}
					if ( xpressui_pro_overlay_supports_choice_limits( $field ) && array_key_exists( 'min_choices', $fo ) ) {
						$field['minNumOfChoices'] = (int) $fo['min_choices'];
					}
					if ( xpressui_pro_overlay_supports_choice_limits( $field ) && array_key_exists( 'max_choices', $fo ) ) {
						$field['maxNumOfChoices'] = (int) $fo['max_choices'];
					}
					if ( array_key_exists( 'min_value', $fo ) ) {
						$field['min'] = 0 + $fo['min_value'];
					}
					if ( array_key_exists( 'max_value', $fo ) ) {
						$field['max'] = 0 + $fo['max_value'];
					}
					if ( array_key_exists( 'step_value', $fo ) ) {
						$field['step'] = 0 + $fo['step_value'];
					}
					if ( array_key_exists( 'max_file_size_mb', $fo ) ) {
						$field['maxFileSizeMb'] = 0 + $fo['max_file_size_mb'];
					}
					$v = isset( $fo['accept'] ) ? trim( (string) $fo['accept'] ) : '';
					if ( $v !== '' ) {
						$field['accept'] = $v;
					}
					$v = isset( $fo['file_type_error_message'] ) ? trim( (string) $fo['file_type_error_message'] ) : '';
					if ( $v !== '' ) {
						$field['fileTypeErrorMsg'] = $v;
					}
					$v = isset( $fo['file_size_error_message'] ) ? trim( (string) $fo['file_size_error_message'] ) : '';
					if ( $v !== '' ) {
						$field['fileSizeErrorMsg'] = $v;
					}

					$choices_overlay = isset( $fo['choices'] ) && is_array( $fo['choices'] ) ? $fo['choices'] : [];
					if ( ! empty( $choices_overlay ) && isset( $field['choices'] ) && is_array( $field['choices'] ) ) {
						$filtered              = [];
						$pack_choices_by_value = [];
						foreach ( $field['choices'] as $choice ) {
							$cv = (string) ( $choice['value'] ?? '' );
							if ( $cv !== '' ) {
								$pack_choices_by_value[ $cv ] = $choice;
							}
						}
						foreach ( $choices_overlay as $cv => $choice_overlay_raw ) {
							$cv = (string) $cv;
							if ( isset( $pack_choices_by_value[ $cv ] ) ) {
								$choice         = $pack_choices_by_value[ $cv ];
								$choice_overlay = xpressui_pro_normalize_choice_overlay_entry( $choice_overlay_raw );
								if ( $choice_overlay['has_enabled'] && ! $choice_overlay['enabled'] ) {
									unset( $pack_choices_by_value[ $cv ] );
									continue; // Remove disabled choice entirely.
								}
								if ( $choice_overlay['has_label'] ) {
									$choice['label'] = $choice_overlay['label'];
								}
								$filtered[] = $choice;
								unset( $pack_choices_by_value[ $cv ] );
							}
						}
						// Append any remaining pack choices that were not reordered in the overlay
						foreach ( $pack_choices_by_value as $choice ) {
							$filtered[] = $choice;
						}
						$field['choices'] = array_values( $filtered );
					}
				}
				unset( $field );
			}
			unset( $sfields );
		}
	}

	return $cfg;
}


// ---------------------------------------------------------------------------
// Filter hooks — bridge the overlay into the base plugin's render pipeline
// ---------------------------------------------------------------------------

add_filter( 'xpressui_template_context', 'xpressui_pro_filter_template_context', 10, 2 );

function xpressui_pro_filter_template_context( array $context, string $slug ): array {
	$overlay = xpressui_pro_load_workflow_overlay( $slug );
	if ( empty( $overlay ) ) {
		return $context;
	}
	return xpressui_pro_apply_workflow_overlay( $context, $overlay );
}
