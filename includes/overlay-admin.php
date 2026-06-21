<?php
/**
 * Workflow overlay admin UI — renders Pro sections into the unified
 * Workflow Settings page via action hooks.
 *
 * @package XPressUI_Bridge_Pro
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Menu registration
// ---------------------------------------------------------------------------

add_action( 'admin_menu', 'xpressui_pro_register_console_link', 20 );
add_action( 'admin_footer', 'xpressui_pro_patch_console_menu_link' );
// Additional file slot metabox hooks removed — feature moved to cloud offering.
add_action( 'xpressui_workflow_settings_extra_sections', 'xpressui_pro_render_extra_workflow_sections', 10, 3 );
add_action( 'xpressui_workflow_settings_extra_save', 'xpressui_pro_extra_workflow_save' );

function xpressui_pro_register_console_link(): void {
	add_submenu_page(
		'edit.php?post_type=xpressui_submission',
		__( 'IntakeFlow Console', 'xpressui-bridge' ),
		__( '↗ Console', 'xpressui-bridge' ),
		'manage_options',
		'xpressui-console-redirect',
		'xpressui_pro_redirect_to_console'
	);
}

function xpressui_pro_patch_console_menu_link(): void {
	$console_url = (string) wp_json_encode( xpressui_pro_get_console_url() );
	wp_print_inline_script_tag(
		"(function () {
			document.querySelectorAll( '#adminmenu a[href*=\"xpressui-console-redirect\"]' ).forEach( function ( link ) {
				link.href = {$console_url};
				link.target = '_blank';
				link.rel = 'noopener noreferrer';
			} );
		}());"
	);
}

function xpressui_pro_get_console_url(): string {
	return 'https://app.intakeflow.dev';
}

function xpressui_pro_redirect_to_console(): void {
	$console_url = (string) wp_json_encode( xpressui_pro_get_console_url() );
	$back_url    = (string) wp_json_encode( admin_url( 'edit.php?post_type=xpressui_submission' ) );
	wp_print_inline_script_tag(
		"(function () {
			window.open( {$console_url}, '_blank', 'noopener,noreferrer' );
			window.location.href = {$back_url};
		}());"
	);
}

// ---------------------------------------------------------------------------
// Enqueue overlay assets on the unified Workflow Settings page
// ---------------------------------------------------------------------------

add_action( 'admin_enqueue_scripts', 'xpressui_enqueue_overlay_assets' );

function xpressui_enqueue_overlay_assets(): void {
	if ( ! defined( 'XPRESSUI_PRO_VERSION' ) || ! defined( 'XPRESSUI_PRO_URL' ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen ) {
		return;
	}
	$is_workflows = 'xpressui_submission_page_xpressui-bridge' === $screen->id;
	$is_settings  = 'xpressui_submission_page_xpressui-workflow-settings' === $screen->id;
	if ( ! $is_workflows && ! $is_settings ) {
		return;
	}
	wp_enqueue_style(
		'xpressui-pro-admin-overlay',
		XPRESSUI_PRO_URL . 'assets/admin-overlay.css',
		[],
		XPRESSUI_PRO_VERSION . '-ux-v5'
	);
	if ( $is_settings ) {
		wp_enqueue_media();
		wp_enqueue_script(
			'xpressui-pro-admin-overlay-js',
			XPRESSUI_PRO_URL . 'assets/admin-overlay.js',
			[],
			XPRESSUI_PRO_VERSION . '-ux-v5',
			true
		);
	}
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------


/**
 * Returns a trimmed raw form value from an overlay field payload.
 */
function xpressui_pro_overlay_raw_value( array $field_data, string $key ): string {
	return trim( wp_unslash( (string) ( $field_data[ $key ] ?? '' ) ) );
}

/**
 * Stores a non-empty integer overlay value, or records a validation warning.
 *
 * @param array<string, mixed> $entry
 * @param array<int, string>   $warnings
 * @param array<int, string>   $invalid_fields
 */
function xpressui_pro_collect_overlay_int(
	array &$entry,
	array &$warnings,
	array &$invalid_fields,
	array $field_data,
	string $field_name,
	string $source_key,
	string $target_key,
	string $label
): void {
	$raw = xpressui_pro_overlay_raw_value( $field_data, $source_key );
	if ( $raw === '' ) {
		return;
	}
	if ( ! preg_match( '/^\d+$/', $raw ) ) {
		$warnings[]       = sprintf(
			/* translators: 1: field label, 2: validation label. */
			__( 'The value for "%1$s" (%2$s) was not saved because it must be a whole number.', 'xpressui-bridge' ),
			$field_name,
			$label
		);
		$invalid_fields[] = 'xpressui_overlay_fields_' . sanitize_html_class( $field_name ) . '_' . sanitize_html_class( $source_key );
		return;
	}
	$entry[ $target_key ] = (int) $raw;
}

/**
 * Stores a non-empty numeric overlay value, or records a validation warning.
 *
 * @param array<string, mixed> $entry
 * @param array<int, string>   $warnings
 * @param array<int, string>   $invalid_fields
 */
function xpressui_pro_collect_overlay_number(
	array &$entry,
	array &$warnings,
	array &$invalid_fields,
	array $field_data,
	string $field_name,
	string $source_key,
	string $target_key,
	string $label
): void {
	$raw = xpressui_pro_overlay_raw_value( $field_data, $source_key );
	if ( $raw === '' ) {
		return;
	}
	if ( ! is_numeric( $raw ) ) {
		$warnings[]       = sprintf(
			/* translators: 1: field label, 2: validation label. */
			__( 'The value for "%1$s" (%2$s) was not saved because it must be numeric.', 'xpressui-bridge' ),
			$field_name,
			$label
		);
		$invalid_fields[] = 'xpressui_overlay_fields_' . sanitize_html_class( $field_name ) . '_' . sanitize_html_class( $source_key );
		return;
	}
	$entry[ $target_key ] = 0 + $raw;
}

/**
 * Returns whether a field supports min/max choice limits.
 */
function xpressui_pro_field_supports_choice_limits( array $field ): bool {
	$field_type = (string) ( $field['type'] ?? '' );
	$multiple   = ! empty( $field['multiple'] );

	return $multiple || in_array( $field_type, [ 'select-multiple', 'checkboxes' ], true );
}

/**
 * Returns whether a field exposes predefined choices that can be relabeled.
 */
function xpressui_pro_field_has_choices( array $field ): bool {
	return isset( $field['choices'] ) && is_array( $field['choices'] ) && ! empty( $field['choices'] );
}

/**
 * Normalize a saved choice overlay entry for admin rendering.
 *
 * @param mixed $entry Raw overlay entry.
 * @return array{label:string, enabled:bool|null}
 */
function xpressui_pro_admin_normalize_choice_overlay_entry( $entry ): array {
	if ( is_string( $entry ) ) {
		return [
			'label'   => $entry,
			'enabled' => null,
		];
	}

	if ( ! is_array( $entry ) ) {
		return [
			'label'   => '',
			'enabled' => null,
		];
	}

	return [
		'label'   => isset( $entry['label'] ) ? (string) $entry['label'] : '',
		'enabled' => array_key_exists( 'enabled', $entry ) ? (bool) $entry['enabled'] : null,
	];
}

/**
 * Returns whether a field supports regex pattern validation.
 */
function xpressui_pro_field_supports_pattern( array $field ): bool {
	$field_type = (string) ( $field['type'] ?? '' );

	return in_array( $field_type, [ 'text', 'email', 'tel', 'url', 'search', 'slug' ], true );
}

function xpressui_pro_render_card_appearance( array $ov_theme, array $pack_theme, array $summary_stats, string $ov_project_bg, string $pack_project_bg ): void {
	$customized = $summary_stats['has_theme'] ? '1' : '0';
	$open       = ' open';
	echo '<details class="xpressui-admin-card"' . $open . ' data-xpressui-card-type="appearance" data-xpressui-customized="' . esc_attr( $customized ) . '" data-xpressui-search-text="appearance design tokens colors primary background surface text border radius" data-xpressui-reset-scope="appearance" id="xpressui-pro-card-appearance">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<summary class="xpressui-card-summary"><h2>' . esc_html__( 'Appearance & Design Tokens', 'xpressui-bridge' ) . '</h2><span class="xpressui-card-meta">';
	if ( $summary_stats['has_theme'] ) {
		echo '<span class="xpressui-card-badge is-customized">' . esc_html__( 'Customized', 'xpressui-bridge' ) . '</span>';
		echo '<button type="button" class="xpressui-reset-chip" data-xpressui-reset-trigger="appearance">' . esc_html__( 'Restore block', 'xpressui-bridge' ) . '</button>';
	}
	echo '</span></summary>';
	echo '<div class="xpressui-card-body"><table class="form-table"><tbody>';

	$bg_styles = [
		'none'       => __( 'None (Clean)', 'xpressui-bridge' ),
		'panel'      => __( 'Panel Focus', 'xpressui-bridge' ),
		'full-bleed' => __( 'Full Bleed', 'xpressui-bridge' ),
	];
	$pack_bg_style = (string) ( $pack_theme['background_style'] ?? 'none' );
	$ov_bg_style   = (string) ( $ov_theme['background_style'] ?? '' );
	
	$html  = '<select name="xpressui_overlay_theme[background_style]">';
	$html .= '<option value="">' . esc_html__( 'Pack default', 'xpressui-bridge' ) . ' (' . esc_html( $bg_styles[ $pack_bg_style ] ?? $pack_bg_style ) . ')</option>';
	foreach ( $bg_styles as $val => $label ) {
		$html .= '<option value="' . esc_attr( $val ) . '"' . selected( $ov_bg_style, $val, false ) . '>' . esc_html( $label ) . '</option>';
	}
	$html .= '</select>';
	xpressui_pro_row( '', __( 'Background Style', 'xpressui-bridge' ), $html );

	$frame_styles = [
		'card'  => __( 'Card (framed)', 'xpressui-bridge' ),
		'plain' => __( 'Plain (transparent)', 'xpressui-bridge' ),
	];
	$pack_frame_style = (string) ( $pack_theme['frame_style'] ?? 'card' );
	$ov_frame_style   = (string) ( $ov_theme['frame_style'] ?? '' );

	$html  = '<select name="xpressui_overlay_theme[frame_style]">';
	$html .= '<option value="">' . esc_html__( 'Pack default', 'xpressui-bridge' ) . ' (' . esc_html( $frame_styles[ $pack_frame_style ] ?? $pack_frame_style ) . ')</option>';
	foreach ( $frame_styles as $val => $label ) {
		$html .= '<option value="' . esc_attr( $val ) . '"' . selected( $ov_frame_style, $val, false ) . '>' . esc_html( $label ) . '</option>';
	}
	$html .= '</select>';
	$html .= '<p class="description">' . esc_html__( 'Use “Plain” when your theme already wraps content in a card, to avoid a double frame.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( '', __( 'Frame Style', 'xpressui-bridge' ), $html );

	$html  = '<div style="display:flex;gap:10px;align-items:center;max-width:420px;">';
	$html .= '<input type="url" id="xpressui_overlay_project_background_image_url" name="xpressui_overlay_project_background_image_url" class="regular-text" style="flex:1;" value="' . esc_attr( $ov_project_bg ) . '" placeholder="' . esc_attr( $pack_project_bg ) . '" />';
	$html .= '<button type="button" class="button xpressui-media-upload-btn" data-target="xpressui_overlay_project_background_image_url">' . esc_html__( 'Select Image', 'xpressui-bridge' ) . '</button>';
	$html .= '</div>';
	$html .= '<p class="description">' . esc_html__( 'Select or upload an image from your Media Library.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( '', __( 'Background Image', 'xpressui-bridge' ), $html );

	$pack_font = (string) ( $pack_theme['font_family'] ?? 'inherit' );
	$ov_font   = (string) ( $ov_theme['font_family'] ?? '' );

	$fonts_list = [
		''                          => __( 'Inherit from Theme', 'xpressui-bridge' ),
		'Inter, sans-serif'         => 'Inter',
		'Outfit, sans-serif'        => 'Outfit',
		'Poppins, sans-serif'       => 'Poppins',
		'Roboto, sans-serif'        => 'Roboto',
		'Montserrat, sans-serif'    => 'Montserrat',
		'Playfair Display, serif'   => 'Playfair Display',
	];

	$is_custom = $ov_font !== '' && ! isset( $fonts_list[ $ov_font ] );
	$selected_font = $is_custom ? 'custom' : $ov_font;

	$html  = '<select id="xpressui_font_family_select" style="max-width:420px; width:100%;">';
	foreach ( $fonts_list as $val => $label ) {
		$html .= '<option value="' . esc_attr( $val ) . '"' . selected( $selected_font, $val, false ) . '>' . esc_html( $label ) . '</option>';
	}
	$html .= '<option value="custom"' . selected( $selected_font, 'custom', false ) . '>' . esc_html__( 'Custom CSS font family...', 'xpressui-bridge' ) . '</option>';
	$html .= '</select>';
	$html .= '<input type="hidden" id="xpressui_overlay_theme_font_family" name="xpressui_overlay_theme[font_family]" value="' . esc_attr( $ov_font ) . '" />';

	$custom_style = $is_custom ? 'display:block; margin-top:8px;' : 'display:none; margin-top:8px;';
	$html .= '<input type="text" id="xpressui_custom_font_family_input" class="regular-text" style="' . $custom_style . '" value="' . esc_attr( $is_custom ? $ov_font : '' ) . '" placeholder="e.g. \'Open Sans\', sans-serif" />';
	$html .= '<p class="description">' . esc_html__( 'Choose a font family from the list or define a custom CSS font stack.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( '', __( 'Typography (Font Family)', 'xpressui-bridge' ), $html );

	echo '<tr><td colspan="2"><hr style="border:none;border-top:1px solid #eee;margin:4px 0 8px"></td></tr>';

	$colors = [
		'primary'         => __( 'Primary color', 'xpressui-bridge' ),
		'surface'         => __( 'Surface color (Cards)', 'xpressui-bridge' ),
		'page_background' => __( 'Page background', 'xpressui-bridge' ),
		'text'            => __( 'Text color', 'xpressui-bridge' ),
		'border'          => __( 'Border color', 'xpressui-bridge' ),
	];

	$swatches = [
		'primary'         => [ '#2563eb', '#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#0f172a' ],
		'surface'         => [ '#ffffff', '#f8fafc', '#f1f5f9' ],
		'page_background' => [ '#f1f5f9', '#eff6ff', '#f0fdf4', '#fafaf9', '#ffffff' ],
		'text'            => [ '#0f172a', '#334155', '#1e293b' ],
		'border'          => [ '#e2e8f0', '#cbd5e1', '#e5e7eb' ],
	];

	foreach ( $colors as $key => $label ) {
		$pack_val    = (string) ( $pack_theme['colors'][ $key ] ?? '' );
		$ov_val      = (string) ( $ov_theme['colors'][ $key ] ?? '' );
		$display_val = $ov_val !== '' ? $ov_val : $pack_val;

		$html  = '<div style="display:flex;align-items:center;gap:10px;">';
		$html .= '<input type="color" value="' . esc_attr( $display_val ) . '" oninput="this.nextElementSibling.value=this.value; this.nextElementSibling.dispatchEvent(new Event(\'input\', { bubbles: true }));" />';
		$html .= '<input type="text" name="xpressui_overlay_theme[colors][' . esc_attr( $key ) . ']" class="regular-text" style="width:100px;" value="' . esc_attr( $ov_val ) . '" placeholder="' . esc_attr( $pack_val ) . '" oninput="this.previousElementSibling.value=this.value || this.placeholder;" />';
		$html .= '</div>';

		if ( isset( $swatches[ $key ] ) ) {
			$html .= '<div class="xpressui-color-swatches" style="margin-top:6px; display:flex; gap:6px; flex-wrap:wrap;">';
			foreach ( $swatches[ $key ] as $swatch_color ) {
				$html .= '<button type="button" class="xpressui-swatch" data-color="' . esc_attr( $swatch_color ) . '" style="width:20px; height:20px; border-radius:50%; background:' . esc_attr( $swatch_color ) . '; border:1px solid rgba(0,0,0,0.15); cursor:pointer;" title="' . esc_attr( $swatch_color ) . '"></button>';
			}
			$html .= '</div>';
		}

		if ( $pack_val !== '' ) {
			$html .= '<p class="description">' . esc_html__( 'Pack default:', 'xpressui-bridge' ) . ' <code style="display:inline-block;width:12px;height:12px;background:' . esc_attr( $pack_val ) . ';border-radius:2px;vertical-align:middle;margin-right:4px;border:1px solid #ccc;"></code>' . esc_html( $pack_val ) . '</p>';
		}
		xpressui_pro_row( '', $label, $html );
	}

	echo '<tr><td colspan="2"><hr style="border:none;border-top:1px solid #eee;margin:4px 0 8px"></td></tr>';

	$radii = [
		'card'   => __( 'Card radius (px)', 'xpressui-bridge' ),
		'input'  => __( 'Input radius (px)', 'xpressui-bridge' ),
		'button' => __( 'Button radius (px)', 'xpressui-bridge' ),
	];

	foreach ( $radii as $key => $label ) {
		$pack_val = (string) ( $pack_theme['radius'][ $key ] ?? '' );
		$ov_val   = isset( $ov_theme['radius'][ $key ] ) ? (string) $ov_theme['radius'][ $key ] : '';

		$html  = '<input type="number" name="xpressui_overlay_theme[radius][' . esc_attr( $key ) . ']" class="small-text" value="' . esc_attr( $ov_val ) . '" placeholder="' . esc_attr( $pack_val ) . '" min="0" step="1" />';
		$html .= '<p class="description">' . esc_html__( 'Pack default:', 'xpressui-bridge' ) . ' ' . esc_html( $pack_val ) . 'px</p>';
		xpressui_pro_row( '', $label, $html );
	}

	echo '</tbody></table></div>';
	echo '</details>';
}

// xpressui_pro_render_afile_metabox_extension and xpressui_pro_save_afile_metabox_extension
// removed — additional file slots feature moved to cloud offering.

function xpressui_pro_render_card_navigation( array $ov_navigation, array $pack_nav ): void {
	$nav_open = ' open';
	$nav_fields = [
		'prev'   => [ __( 'Back button', 'xpressui-bridge' ), (string) ( $pack_nav['prevLabel'] ?? 'Back' ) ],
		'next'   => [ __( 'Continue button', 'xpressui-bridge' ), (string) ( $pack_nav['nextLabel'] ?? 'Continue' ) ],
		'submit' => [ __( 'Submit button', 'xpressui-bridge' ), (string) ( $pack_nav['submitLabel'] ?? 'Submit' ) ],
	];
	echo '<details class="xpressui-admin-card"' . $nav_open . ' data-xpressui-card-type="navigation" data-xpressui-customized="' . ( ! empty( $ov_navigation ) ? '1' : '0' ) . '" data-xpressui-search-text="navigation labels back continue submit buttons" data-xpressui-reset-scope="navigation" id="xpressui-pro-card-navigation">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $nav_open is a hardcoded HTML attribute built from a boolean
	echo '<summary class="xpressui-card-summary"><h2>' . esc_html__( 'Navigation Labels', 'xpressui-bridge' ) . '</h2><span class="xpressui-card-meta">';
	if ( ! empty( $ov_navigation ) ) {
		echo '<span class="xpressui-card-badge is-customized">' . esc_html__( 'Customized', 'xpressui-bridge' ) . '</span>';
		echo '<button type="button" class="xpressui-reset-chip" data-xpressui-reset-trigger="navigation">' . esc_html__( 'Restore block', 'xpressui-bridge' ) . '</button>';
	}
	echo '<span class="xpressui-card-badge">' . esc_html( (string) count( $nav_fields ) ) . ' ' . esc_html__( 'Buttons', 'xpressui-bridge' ) . '</span>';
	echo '</span></summary>';
	echo '<div class="xpressui-card-body"><table class="form-table"><tbody>';

	foreach ( $nav_fields as $nav_key => [ $nav_label, $nav_default ] ) {
		$current_val = (string) ( $ov_navigation[ $nav_key ] ?? '' );
		xpressui_pro_row(
			'xpressui_overlay_nav_' . $nav_key,
			$nav_label,
			'<input type="text" id="xpressui_overlay_nav_' . esc_attr( $nav_key ) . '" name="xpressui_overlay_nav_' . esc_attr( $nav_key ) . '" class="regular-text" value="' . esc_attr( $current_val ) . '" placeholder="' . esc_attr( $nav_default ) . '" />'
			. '<p class="description">' . esc_html__( 'Pack default:', 'xpressui-bridge' ) . ' <em>' . esc_html( $nav_default ) . '</em></p>'
		);
	}

	echo '</tbody></table></div>';
	echo '</details>';
}

function xpressui_pro_render_card_sections( array $sections, array $ov_sections, array $ov_fields, array $invalid_fields ): void {
	foreach ( $sections as $section ) {
		$section_name  = (string) ( $section['name'] ?? '' );
		$section_label = (string) ( $section['label'] ?? $section_name );
		$fields        = isset( $section['fields'] ) && is_array( $section['fields'] ) ? $section['fields'] : [];
		if ( $section_name === '' || empty( $fields ) ) {
			continue;
		}

		$current_section_label = (string) ( $ov_sections[ $section_name ] ?? '' );

		// Open the card if any customization exists for this section or its fields.
		$section_has_custom = $current_section_label !== '';
		if ( ! $section_has_custom ) {
			foreach ( $fields as $f ) {
				$fn = (string) ( $f['name'] ?? '' );
				if ( $fn !== '' && isset( $ov_fields[ $fn ] ) && ! empty( $ov_fields[ $fn ] ) ) {
					$section_has_custom = true;
					break;
				}
			}
		}
		$card_open = ' open';

		$customized_field_count = 0;
		foreach ( $fields as $field_for_count ) {
			$field_name_for_count = (string) ( $field_for_count['name'] ?? '' );
			if ( $field_name_for_count !== '' && isset( $ov_fields[ $field_name_for_count ] ) && ! empty( $ov_fields[ $field_name_for_count ] ) ) {
				$customized_field_count++;
			}
		}

		$section_search_tokens = [ $section_name, $section_label ];
		foreach ( $fields as $field_for_search ) {
			$section_search_tokens[] = (string) ( $field_for_search['name'] ?? '' );
			$section_search_tokens[] = (string) ( $field_for_search['label'] ?? '' );
			$section_search_tokens[] = (string) ( $field_for_search['type'] ?? '' );
		}
		$section_search_text = strtolower( trim( implode( ' ', array_filter( array_map( 'strval', $section_search_tokens ) ) ) ) );

		echo '<details class="xpressui-admin-card"' . $card_open . ' data-xpressui-card-type="section" data-xpressui-customized="' . ( $section_has_custom ? '1' : '0' ) . '" data-xpressui-search-text="' . esc_attr( $section_search_text ) . '" data-xpressui-reset-scope="section-' . esc_attr( $section_name ) . '" id="xpressui-pro-card-' . esc_attr( $section_name ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $card_open is a hardcoded HTML attribute built from a boolean
		echo '<summary class="xpressui-card-summary"><h2>' . esc_html( $section_label ) . '</h2><span class="xpressui-card-meta">';
		if ( $section_has_custom ) {
			echo '<span class="xpressui-card-badge is-customized">' . esc_html__( 'Customized', 'xpressui-bridge' ) . '</span>';
			echo '<button type="button" class="xpressui-reset-chip" data-xpressui-reset-trigger="section-' . esc_attr( $section_name ) . '">' . esc_html__( 'Restore section', 'xpressui-bridge' ) . '</button>';
		}
		echo '<span class="xpressui-card-badge">' . esc_html( (string) count( $fields ) ) . ' ' . esc_html__( 'Fields', 'xpressui-bridge' ) . '</span>';
		if ( $customized_field_count > 0 ) {
			echo '<span class="xpressui-card-badge">' . esc_html( (string) $customized_field_count ) . ' ' . esc_html__( 'Overrides', 'xpressui-bridge' ) . '</span>';
		}
		echo '</span></summary>';
		echo '<div class="xpressui-card-body"><table class="form-table"><tbody>';

		// Section label row.
		xpressui_pro_row(
			'xpressui_overlay_sections[' . esc_attr( $section_name ) . ']',
			'<strong>' . esc_html__( 'Section label', 'xpressui-bridge' ) . '</strong>',
			'<input type="text" name="xpressui_overlay_sections[' . esc_attr( $section_name ) . ']" class="regular-text" value="' . esc_attr( $current_section_label ) . '" placeholder="' . esc_attr( $section_label ) . '" />'
			. '<p class="description">' . esc_html__( 'Pack default:', 'xpressui-bridge' ) . ' <em>' . esc_html( $section_label ) . '</em></p>'
		);

		echo '<tr><td colspan="2"><hr style="border:none;border-top:1px solid #eee;margin:4px 0 8px"></td></tr>';

		// Field rows.
		foreach ( $fields as $field ) {
			$fname       = (string) ( $field['name'] ?? '' );
			$flabel      = (string) ( $field['label'] ?? $fname );
			$ftype       = (string) ( $field['type'] ?? '' );
			$choices     = isset( $field['choices'] ) && is_array( $field['choices'] ) ? $field['choices'] : [];
			$pack_req    = ! empty( $field['required'] );
			if ( $fname === '' ) {
				continue;
			}

			$fo = isset( $ov_fields[ $fname ] ) && is_array( $ov_fields[ $fname ] ) ? $ov_fields[ $fname ] : [];

			$ov_label  = (string) ( $fo['label'] ?? '' );
			$ov_req    = array_key_exists( 'required', $fo ) ? ( $fo['required'] ? '1' : '0' ) : '';
			$ov_ph     = (string) ( $fo['placeholder'] ?? '' );
			$ov_desc   = (string) ( $fo['desc'] ?? '' );
			$ov_errmsg = (string) ( $fo['error_message'] ?? '' );
			$ov_pattern = isset( $fo['pattern'] ) ? (string) $fo['pattern'] : '';
			$ov_min_len = isset( $fo['min_len'] ) ? (string) $fo['min_len'] : '';
			$ov_max_len = isset( $fo['max_len'] ) ? (string) $fo['max_len'] : '';
			$ov_min_value = isset( $fo['min_value'] ) ? (string) $fo['min_value'] : '';
			$ov_max_value = isset( $fo['max_value'] ) ? (string) $fo['max_value'] : '';
			$ov_step_value = isset( $fo['step_value'] ) ? (string) $fo['step_value'] : '';
			$ov_min_choices = isset( $fo['min_choices'] ) ? (string) $fo['min_choices'] : '';
			$ov_max_choices = isset( $fo['max_choices'] ) ? (string) $fo['max_choices'] : '';
			$ov_accept = isset( $fo['accept'] ) ? (string) $fo['accept'] : '';
			$ov_upload_accept_label = isset( $fo['upload_accept_label'] ) ? (string) $fo['upload_accept_label'] : '';
			$ov_max_file_size_mb = isset( $fo['max_file_size_mb'] ) ? (string) $fo['max_file_size_mb'] : '';
			$ov_file_type_error_message = isset( $fo['file_type_error_message'] ) ? (string) $fo['file_type_error_message'] : '';
			$ov_file_size_error_message = isset( $fo['file_size_error_message'] ) ? (string) $fo['file_size_error_message'] : '';
			$field_has_custom = ! empty( $fo );
			$text_validation_types = [ 'text', 'email', 'tel', 'url', 'search', 'slug', 'textarea', 'rich-editor' ];
			$pattern_validation_types = [ 'text', 'email', 'tel', 'url', 'search', 'slug' ];
			$numeric_validation_types = [ 'number', 'price', 'integer', 'age', 'tax', 'date', 'time', 'datetime' ];
			$upload_validation_types = [ 'file', 'upload-image', 'camera-photo', 'camera-photo-list', 'payment-proof', 'qr-scan', 'document-scan' ];
			$multi_choice_validation_types = [ 'select-multiple', 'checkboxes' ];
			$supports_choice_limits = ! empty( $choices ) && ( ! empty( $field['multiple'] ) || in_array( $ftype, $multi_choice_validation_types, true ) );
			$supports_choice_labels = xpressui_pro_field_has_choices( $field );

			$field_prefix = 'xpressui_overlay_fields[' . esc_attr( $fname ) . ']';
			$header       = '<div class="xpressui-field-block' . ( $field_has_custom ? ' is-customized' : '' ) . '" data-xpressui-reset-scope="field-' . esc_attr( $fname ) . '">';
			$header      .= '<div class="xpressui-field-block-header">';
			$header      .= '<div><div class="xpressui-field-block-title">' . esc_html( $flabel ) . '</div><div class="xpressui-field-block-type">' . esc_html( $ftype ) . '</div></div>';
			if ( $field_has_custom ) {
				$header .= '<div style="display:flex;align-items:center;gap:8px">';
				$header .= '<span class="xpressui-card-badge is-customized">' . esc_html__( 'Customized', 'xpressui-bridge' ) . '</span>';
				$header .= '<button type="button" class="xpressui-reset-chip" data-xpressui-reset-trigger="field-' . esc_attr( $fname ) . '">' . esc_html__( 'Restore this field', 'xpressui-bridge' ) . '</button>';
				$header .= '</div>';
			}
			$header .= '</div>';
			$header .= '<div class="xpressui-field-block-grid">';

			$html = '';

			// Label.
			$html .= '<div class="xpressui-field-control">';
			$html .= '<label>' . esc_html__( 'Label', 'xpressui-bridge' ) . '</label>';
			$html .= '<input type="text" name="' . $field_prefix . '[label]" class="regular-text" value="' . esc_attr( $ov_label ) . '" placeholder="' . esc_attr( $flabel ) . '" />';
			$html .= '</div>';

			// Required (select, 3 states).
			$req_options = [
				''  => __( 'Pack default', 'xpressui-bridge' ) . ' (' . ( $pack_req ? __( 'required', 'xpressui-bridge' ) : __( 'optional', 'xpressui-bridge' ) ) . ')',
				'1' => __( 'Required', 'xpressui-bridge' ),
				'0' => __( 'Optional', 'xpressui-bridge' ),
			];
			$html .= '<div class="xpressui-field-control">';
			$html .= '<label>' . esc_html__( 'Required', 'xpressui-bridge' ) . '</label>';
			$html .= '<select name="' . $field_prefix . '[required]">';
			foreach ( $req_options as $opt_val => $opt_label ) {
				$html .= '<option value="' . esc_attr( (string) $opt_val ) . '"' . selected( $ov_req, (string) $opt_val, false ) . '>' . esc_html( $opt_label ) . '</option>';
			}
			$html .= '</select>';
			$html .= '</div>';

			// Placeholder (text-like fields).
			$text_types = [ 'text', 'email', 'tel', 'url', 'number', 'price', 'integer', 'age', 'tax', 'date', 'time', 'datetime', 'search', 'slug', 'textarea', 'rich-editor' ];
			if ( in_array( $ftype, $text_types, true ) ) {
				$html .= '<div class="xpressui-field-control">';
				$html .= '<label>' . esc_html__( 'Placeholder', 'xpressui-bridge' ) . '</label>';
				$html .= '<input type="text" name="' . $field_prefix . '[placeholder]" class="regular-text" value="' . esc_attr( $ov_ph ) . '" placeholder="' . esc_attr( (string) ( $field['placeholder'] ?? '' ) ) . '" />';
				$html .= '</div>';
			}

			// Description.
			$html .= '<div class="xpressui-field-control is-full">';
			$html .= '<label>' . esc_html__( 'Help text', 'xpressui-bridge' ) . '</label>';
			$html .= '<textarea name="' . $field_prefix . '[desc]" class="large-text" rows="2">' . esc_textarea( $ov_desc ) . '</textarea>';
			$html .= '</div>';

			// ------------------ ADVANCED / VALIDATION SETTINGS ------------------
			$adv_html = '';

			// Error message.
			$pack_errmsg = (string) ( $field['error_message'] ?? '' );
			$adv_html .= '<div class="xpressui-field-control is-full">';
			$adv_html .= '<label>' . esc_html__( 'Custom error message', 'xpressui-bridge' ) . '</label>';
			$adv_html .= '<input type="text" name="' . $field_prefix . '[error_message]" class="large-text" value="' . esc_attr( $ov_errmsg ) . '" placeholder="' . esc_attr( $pack_errmsg ) . '" />';
			$adv_html .= '</div>';

			if ( in_array( $ftype, $text_validation_types, true ) ) {
				$adv_html .= '<div class="xpressui-field-control-row">';
				$adv_html .= '<div class="xpressui-field-control">';
				$adv_html .= '<label>' . esc_html__( 'Min length', 'xpressui-bridge' ) . '</label>';
				$adv_html .= '<input type="number" min="0" step="1" id="xpressui_overlay_fields_' . esc_attr( $fname ) . '_min_len" name="' . $field_prefix . '[min_len]" class="small-text' . ( in_array( 'xpressui_overlay_fields_' . $fname . '_min_len', $invalid_fields, true ) ? ' xpressui-input-invalid' : '' ) . '" value="' . esc_attr( $ov_min_len ) . '" placeholder="" />';
				$adv_html .= '</div>';
				$adv_html .= '<div class="xpressui-field-control">';
				$adv_html .= '<label>' . esc_html__( 'Max length', 'xpressui-bridge' ) . '</label>';
				$adv_html .= '<input type="number" min="0" step="1" id="xpressui_overlay_fields_' . esc_attr( $fname ) . '_max_len" name="' . $field_prefix . '[max_len]" class="small-text' . ( in_array( 'xpressui_overlay_fields_' . $fname . '_max_len', $invalid_fields, true ) ? ' xpressui-input-invalid' : '' ) . '" value="' . esc_attr( $ov_max_len ) . '" placeholder="" />';
				$adv_html .= '</div>';
				$adv_html .= '</div>';
				if ( in_array( $ftype, $pattern_validation_types, true ) ) {
					$adv_html .= '<div class="xpressui-field-control is-full">';
					$adv_html .= '<label>' . esc_html__( 'Pattern (Regex)', 'xpressui-bridge' ) . '</label>';
					$adv_html .= '<input type="text" name="' . $field_prefix . '[pattern]" class="large-text" value="' . esc_attr( $ov_pattern ) . '" placeholder="' . esc_attr( (string) ( $field['pattern'] ?? '' ) ) . '" />';
					$adv_html .= '<p class="description">' . esc_html__( 'Optional regex pattern enforced by the runtime schema. Use ^...$ to match the whole value.', 'xpressui-bridge' ) . '</p>';
					$adv_html .= '</div>';
				}
			}

			if ( in_array( $ftype, $numeric_validation_types, true ) ) {
				$adv_html .= '<div class="xpressui-field-control-row">';
				$adv_html .= '<div class="xpressui-field-control">';
				$adv_html .= '<label>' . esc_html__( 'Min value', 'xpressui-bridge' ) . '</label>';
				$adv_html .= '<input type="text" id="xpressui_overlay_fields_' . esc_attr( $fname ) . '_min_value" name="' . $field_prefix . '[min_value]" class="small-text' . ( in_array( 'xpressui_overlay_fields_' . $fname . '_min_value', $invalid_fields, true ) ? ' xpressui-input-invalid' : '' ) . '" value="' . esc_attr( $ov_min_value ) . '" placeholder="' . esc_attr( (string) ( $field['min_value'] ?? '' ) ) . '" />';
				$adv_html .= '</div>';
				$adv_html .= '<div class="xpressui-field-control">';
				$adv_html .= '<label>' . esc_html__( 'Max value', 'xpressui-bridge' ) . '</label>';
				$adv_html .= '<input type="text" id="xpressui_overlay_fields_' . esc_attr( $fname ) . '_max_value" name="' . $field_prefix . '[max_value]" class="small-text' . ( in_array( 'xpressui_overlay_fields_' . $fname . '_max_value', $invalid_fields, true ) ? ' xpressui-input-invalid' : '' ) . '" value="' . esc_attr( $ov_max_value ) . '" placeholder="' . esc_attr( (string) ( $field['max_value'] ?? '' ) ) . '" />';
				$adv_html .= '</div>';
				$adv_html .= '<div class="xpressui-field-control">';
				$adv_html .= '<label>' . esc_html__( 'Step size', 'xpressui-bridge' ) . '</label>';
				$adv_html .= '<input type="text" id="xpressui_overlay_fields_' . esc_attr( $fname ) . '_step_value" name="' . $field_prefix . '[step_value]" class="small-text' . ( in_array( 'xpressui_overlay_fields_' . $fname . '_step_value', $invalid_fields, true ) ? ' xpressui-input-invalid' : '' ) . '" value="' . esc_attr( $ov_step_value ) . '" placeholder="' . esc_attr( (string) ( $field['step_value'] ?? '' ) ) . '" />';
				$adv_html .= '</div>';
				$adv_html .= '</div>';
			}

			if ( $supports_choice_limits ) {
				$adv_html .= '<div class="xpressui-field-control-row">';
				$adv_html .= '<div class="xpressui-field-control">';
				$adv_html .= '<label>' . esc_html__( 'Minimum choices', 'xpressui-bridge' ) . '</label>';
				$adv_html .= '<input type="number" min="0" step="1" id="xpressui_overlay_fields_' . esc_attr( $fname ) . '_min_choices" name="' . $field_prefix . '[min_choices]" class="small-text' . ( in_array( 'xpressui_overlay_fields_' . $fname . '_min_choices', $invalid_fields, true ) ? ' xpressui-input-invalid' : '' ) . '" value="' . esc_attr( $ov_min_choices ) . '" placeholder="' . esc_attr( (string) ( $field['min_choices'] ?? '' ) ) . '" />';
				$adv_html .= '</div>';
				$adv_html .= '<div class="xpressui-field-control">';
				$adv_html .= '<label>' . esc_html__( 'Maximum choices', 'xpressui-bridge' ) . '</label>';
				$adv_html .= '<input type="number" min="0" step="1" id="xpressui_overlay_fields_' . esc_attr( $fname ) . '_max_choices" name="' . $field_prefix . '[max_choices]" class="small-text' . ( in_array( 'xpressui_overlay_fields_' . $fname . '_max_choices', $invalid_fields, true ) ? ' xpressui-input-invalid' : '' ) . '" value="' . esc_attr( $ov_max_choices ) . '" placeholder="' . esc_attr( (string) ( $field['max_choices'] ?? '' ) ) . '" />';
				$adv_html .= '</div>';
				$adv_html .= '</div>';
			}

			if ( in_array( $ftype, $upload_validation_types, true ) ) {
				$adv_html .= '<div class="xpressui-field-control-row">';
				$adv_html .= '<div class="xpressui-field-control">';
				$adv_html .= '<label>' . esc_html__( 'Max file size (MB)', 'xpressui-bridge' ) . '</label>';
				$adv_html .= '<input type="text" id="xpressui_overlay_fields_' . esc_attr( $fname ) . '_max_file_size_mb" name="' . $field_prefix . '[max_file_size_mb]" class="small-text' . ( in_array( 'xpressui_overlay_fields_' . $fname . '_max_file_size_mb', $invalid_fields, true ) ? ' xpressui-input-invalid' : '' ) . '" value="' . esc_attr( $ov_max_file_size_mb ) . '" placeholder="' . esc_attr( (string) ( $field['maxFileSizeMb'] ?? '' ) ) . '" />';
				$adv_html .= '</div>';
				$adv_html .= '</div>';
				$adv_html .= '<div class="xpressui-field-control is-full">';
				$adv_html .= '<label>' . esc_html__( 'Accepted file types', 'xpressui-bridge' ) . '</label>';
				$adv_html .= '<input type="text" name="' . $field_prefix . '[accept]" class="large-text" value="' . esc_attr( $ov_accept ) . '" placeholder="' . esc_attr( (string) ( $field['accept'] ?? '' ) ) . '" />';
				$adv_html .= '<p class="description">' . esc_html__( 'Example: image/*,application/pdf', 'xpressui-bridge' ) . '</p>';
				$adv_html .= '</div>';
				$adv_html .= '<div class="xpressui-field-control is-full">';
				$adv_html .= '<label>' . esc_html__( 'Accepted file types label', 'xpressui-bridge' ) . '</label>';
				$adv_html .= '<input type="text" name="' . $field_prefix . '[upload_accept_label]" class="large-text" value="' . esc_attr( $ov_upload_accept_label ) . '" placeholder="' . esc_attr( (string) ( $field['upload_accept_label'] ?? '' ) ) . '" />';
				$adv_html .= '</div>';
				$adv_html .= '<div class="xpressui-field-control is-full">';
				$adv_html .= '<label>' . esc_html__( 'File type error message', 'xpressui-bridge' ) . '</label>';
				$adv_html .= '<input type="text" name="' . $field_prefix . '[file_type_error_message]" class="large-text" value="' . esc_attr( $ov_file_type_error_message ) . '" placeholder="' . esc_attr( (string) ( $field['fileTypeErrorMsg'] ?? '' ) ) . '" />';
				$adv_html .= '</div>';
				$adv_html .= '<div class="xpressui-field-control is-full">';
				$adv_html .= '<label>' . esc_html__( 'File size error message', 'xpressui-bridge' ) . '</label>';
				$adv_html .= '<input type="text" name="' . $field_prefix . '[file_size_error_message]" class="large-text" value="' . esc_attr( $ov_file_size_error_message ) . '" placeholder="' . esc_attr( (string) ( $field['fileSizeErrorMsg'] ?? '' ) ) . '" />';
				$adv_html .= '</div>';
			}

			if ( $adv_html !== '' ) {
				$html .= '<details class="xpressui-field-advanced-settings" style="grid-column: 1 / -1; margin-top: 10px; border-top: 1px solid #eee; padding-top: 8px;">';
				$html .= '<summary class="xpressui-field-advanced-summary" style="cursor:pointer; color:#183ea8; font-weight:600; font-size:11.5px; user-select:none;">' . esc_html__( '⚙️ Validation & Advanced Settings', 'xpressui-bridge' ) . '</summary>';
				$html .= '<div class="xpressui-field-advanced-body" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px 12px; margin-top:10px;">';
				$html .= $adv_html;
				$html .= '</div>';
				$html .= '</details>';
			}

			// Choice labels and enabled state.
			if ( $supports_choice_labels ) {
				$ov_choices = isset( $fo['choices'] ) && is_array( $fo['choices'] ) ? $fo['choices'] : [];
				$html      .= '<div class="xpressui-field-control is-full"><div class="xpressui-choice-group">';
				$html      .= '<p class="description" style="margin-bottom:6px">' . esc_html__( 'Choice labels, availability, and order (drag to reorder):', 'xpressui-bridge' ) . '</p>';
				$html      .= '<div class="xpressui-sortable-list" data-xpressui-sortable>';
				
				// Pre-sort choices based on overlay data to render them in the correct saved order
				$ordered_choices = [];
				$choices_by_value = [];
				foreach ( $choices as $choice ) {
					$cv = (string) ( $choice['value'] ?? '' );
					if ( $cv !== '' ) {
						$choices_by_value[ $cv ] = $choice;
					}
				}
				foreach ( $ov_choices as $cv => $ovc ) {
					$cv = (string) $cv;
					if ( isset( $choices_by_value[ $cv ] ) ) {
						$ordered_choices[] = $choices_by_value[ $cv ];
						unset( $choices_by_value[ $cv ] );
					}
				}
				foreach ( $choices_by_value as $choice ) {
					$ordered_choices[] = $choice;
				}

				foreach ( $ordered_choices as $choice ) {
					$cv = (string) ( $choice['value'] ?? '' );
					$cl = (string) ( $choice['label'] ?? $cv );
					if ( $cv === '' ) {
						continue;
					}
					$ov_choice      = xpressui_pro_admin_normalize_choice_overlay_entry( $ov_choices[ $cv ] ?? [] );
					$choice_enabled = null === $ov_choice['enabled'] ? empty( $choice['disabled'] ) : (bool) $ov_choice['enabled'];
					$html .= '<div class="xpressui-choice-row" draggable="true">';
					$html .= '<span class="xpressui-drag-handle" aria-hidden="true" title="' . esc_attr__( 'Drag to reorder', 'xpressui-bridge' ) . '">&#x2195;</span>';
					$html .= '<span class="xpressui-choice-label">' . esc_html( $cl ) . '</span>';
					$html .= '<input type="text" name="' . $field_prefix . '[choices][' . esc_attr( $cv ) . '][label]" class="regular-text" style="max-width:260px" value="' . esc_attr( $ov_choice['label'] ) . '" placeholder="' . esc_attr( $cl ) . '" />';
					$html .= '<label class="xpressui-choice-toggle">';
					$html .= '<input type="hidden" name="' . $field_prefix . '[choices][' . esc_attr( $cv ) . '][enabled]" value="0" />';
					$html .= '<input type="checkbox" name="' . $field_prefix . '[choices][' . esc_attr( $cv ) . '][enabled]" value="1"' . checked( $choice_enabled, true, false ) . ' />';
					$html .= '<span>' . esc_html__( 'Enabled', 'xpressui-bridge' ) . '</span>';
					$html .= '</label>';
					$html .= '</div>';
				}
				$html .= '</div></div></div>';
			}

			$html .= '</div></div>';

			echo '<tr><td colspan="2" style="padding:0; padding-bottom: 12px;">';
			echo $header . $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $header and $html are built exclusively with esc_attr()/esc_html() throughout the loop above
			echo '</td></tr>';
		}

		echo '</tbody></table></div>';
		echo '</details>';
	}
}



// ---------------------------------------------------------------------------
// Helper: render a form-table row
// ---------------------------------------------------------------------------

function xpressui_pro_row( string $for, string $label, string $content ): void {
	echo '<tr>';
	echo '<th scope="row">';
	if ( $for !== '' ) {
		echo '<label for="' . esc_attr( $for ) . '">' . wp_kses_post( $label ) . '</label>';
	} else {
		echo wp_kses_post( $label );
	}
	echo '</th>';
	echo '<td>' . $content . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $content is escaped at every call site before being passed here
	echo '</tr>';
}

// ---------------------------------------------------------------------------
// Unified Workflow Settings integration — extra sections (Pro)
// ---------------------------------------------------------------------------

function xpressui_normalize_optional_settings_url( $value ): string {
	$value = trim( sanitize_text_field( wp_unslash( (string) $value ) ) );
	if ( in_array( $value, [ '', 'http://', 'https://' ], true ) ) {
		return '';
	}

	return $value;
}

function xpressui_pro_render_card_settings( string $slug ): void {
	$all_settings  = get_option( 'xpressui_project_settings', [] );
	$s             = is_array( $all_settings[ $slug ] ?? null ) ? $all_settings[ $slug ] : [];

	$notify_email                = (string) ( $s['notifyEmail'] ?? '' );
	$redirect_url                = (string) ( $s['redirectUrl'] ?? '' );
	$webhook_url                 = (string) ( $s['webhookUrl'] ?? '' );
	$booking_url                 = (string) ( $s['bookingUrl'] ?? '' );
	$booking_button_label        = (string) ( $s['bookingButtonLabel'] ?? '' );
	$show_project_title          = (string) ( $s['showProjectTitle'] ?? '0' );
	$show_required_note          = (string) ( $s['showRequiredFieldsNote'] ?? '0' );
	$section_label_visibility    = (string) ( $s['sectionLabelVisibility'] ?? 'auto' );
	$notify_submitter_on_submit = (string) ( $s['notifySubmitterOnSubmit'] ?? '0' );
	$submit_confirmation_message = (string) ( $s['submitConfirmationMessage'] ?? '' );
	$submit_success_message     = (string) ( $s['submitSuccessMessage'] ?? '' );
	$submit_error_message       = (string) ( $s['submitErrorMessage'] ?? '' );
	$submission_action          = (string) ( $s['submissionAction'] ?? 'submit' );

	echo '<details class="xpressui-admin-card" open id="xpressui-pro-card-settings">';
	echo '<summary class="xpressui-card-summary"><h2>⚙️ ' . esc_html__( 'Workflow Settings & Integrations', 'xpressui-bridge' ) . '</h2></summary>';
	echo '<div class="xpressui-card-body">';

	// 1. General Project Settings
	echo '<h3 style="margin-top: 15px; border-bottom: 1px solid #eee; padding-bottom: 5px;">' . esc_html__( 'Project Settings', 'xpressui-bridge' ) . '</h3>';
	echo '<table class="form-table"><tbody>';

	$html = '<input type="email" id="xpressui_notify_email" name="xpressui_notify_email" class="regular-text" placeholder="hello@example.com" value="' . esc_attr( $notify_email ) . '">';
	$html .= '<p class="description">' . esc_html__( 'Receive an email on each new submission. Leave empty to disable.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( 'xpressui_notify_email', __( 'Notification email', 'xpressui-bridge' ), $html );

	$html = '<input type="url" id="xpressui_redirect_url" name="xpressui_redirect_url" class="regular-text" placeholder="https://" value="' . esc_attr( $redirect_url ) . '">';
	$html .= '<p class="description">' . esc_html__( 'Redirect after a successful submission. Leave empty to show the success message.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( 'xpressui_redirect_url', __( 'Post-submit redirect', 'xpressui-bridge' ), $html );

	$html = '<input type="url" id="xpressui_webhook_url" name="xpressui_webhook_url" class="regular-text" placeholder="https://" value="' . esc_attr( $webhook_url ) . '">';
	$html .= '<p class="description">' . esc_html__( 'Receive a POST JSON payload after each submission. Leave empty to disable.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( 'xpressui_webhook_url', __( 'Webhook destination', 'xpressui-bridge' ), $html );

	$html = '<label><input type="checkbox" id="xpressui_show_project_title" name="xpressui_show_project_title" value="1"' . checked( '1', $show_project_title, false ) . '> ';
	$html .= esc_html__( 'Display the workflow title above the form inside the WordPress page.', 'xpressui-bridge' ) . '</label>';
	$html .= '<p class="description">' . esc_html__( 'Disabled by default to avoid duplicating the WordPress page title.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( 'xpressui_show_project_title', __( 'Form title', 'xpressui-bridge' ), $html );

	$html = '<label><input type="checkbox" id="xpressui_show_required_fields_note" name="xpressui_show_required_fields_note" value="1"' . checked( '1', $show_required_note, false ) . '> ';
	$html .= esc_html__( 'Display the "* Required fields" note above the form.', 'xpressui-bridge' ) . '</label>';
	$html .= '<p class="description">' . esc_html__( 'Disabled by default for a cleaner WordPress page layout.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( 'xpressui_show_required_fields_note', __( 'Required fields note', 'xpressui-bridge' ), $html );

	$html = '<select id="xpressui_section_label_visibility" name="xpressui_section_label_visibility" class="regular-text">';
	foreach ( [ 'auto' => __( 'Auto', 'xpressui-bridge' ), 'show' => __( 'Always show', 'xpressui-bridge' ), 'hide' => __( 'Always hide', 'xpressui-bridge' ) ] as $val => $label ) {
		$html .= '<option value="' . esc_attr( $val ) . '"' . selected( $section_label_visibility, $val, false ) . '>' . esc_html( $label ) . '</option>';
	}
	$html .= '</select>';
	$html .= '<p class="description">' . esc_html__( 'Auto hides section titles when the workflow only contains one section.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( 'xpressui_section_label_visibility', __( 'Section labels', 'xpressui-bridge' ), $html );

	$html = '<select id="xpressui_submission_action" name="xpressui_submission_action" class="regular-text">';
	foreach ( [ 'submit' => __( 'Default', 'xpressui-bridge' ), 'print' => __( 'Print / download PDF only', 'xpressui-bridge' ) ] as $val => $label ) {
		$html .= '<option value="' . esc_attr( $val ) . '"' . selected( $submission_action, $val, false ) . '>' . esc_html( $label ) . '</option>';
	}
	$html .= '</select>';
	$html .= '<p class="description">' . esc_html__( 'PDF only validates the form and prepares a temporary document link without storing the submission in WordPress.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( 'xpressui_submission_action', __( 'Submission action', 'xpressui-bridge' ), $html );

	echo '</tbody></table>';

	// 2. Submit Feedback
	echo '<h3 style="margin-top: 25px; border-bottom: 1px solid #eee; padding-bottom: 5px;">' . esc_html__( 'Submit Feedback', 'xpressui-bridge' ) . '</h3>';
	echo '<table class="form-table"><tbody>';

	$html = '<input type="text" id="xpressui_submit_success_message" name="xpressui_submit_success_message" class="large-text" value="' . esc_attr( $submit_success_message ) . '">';
	$html .= '<p class="description">' . esc_html__( 'Optional custom success message shown after a valid submission.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( 'xpressui_submit_success_message', __( 'Success message', 'xpressui-bridge' ), $html );

	$html = '<input type="text" id="xpressui_submit_error_message" name="xpressui_submit_error_message" class="large-text" value="' . esc_attr( $submit_error_message ) . '">';
	$html .= '<p class="description">' . esc_html__( 'Optional custom error message shown when the submission fails.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( 'xpressui_submit_error_message', __( 'Error message', 'xpressui-bridge' ), $html );

	echo '</tbody></table>';

	// 3. Booking Link
	echo '<h3 style="margin-top: 25px; border-bottom: 1px solid #eee; padding-bottom: 5px;">' . esc_html__( 'Booking Link', 'xpressui-bridge' ) . '</h3>';
	echo '<table class="form-table"><tbody>';

	$html = '<input type="url" id="xpressui_booking_url" name="xpressui_booking_url" class="regular-text" placeholder="https://calendly.com/..." value="' . esc_attr( $booking_url ) . '">';
	$html .= '<p class="description">' . esc_html__( 'Show a booking button on the success screen and in the confirmation email. Leave empty to disable.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( 'xpressui_booking_url', __( 'Booking URL', 'xpressui-bridge' ), $html );

	$html = '<input type="text" id="xpressui_booking_button_label" name="xpressui_booking_button_label" class="regular-text" placeholder="' . esc_attr__( 'Book an appointment', 'xpressui-bridge' ) . '" value="' . esc_attr( $booking_button_label ) . '">';
	$html .= '<p class="description">' . esc_html__( 'Label of the booking button. Leave empty to use the default.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( 'xpressui_booking_button_label', __( 'Button label', 'xpressui-bridge' ), $html );

	echo '</tbody></table>';

	// 4. Submission Confirmation Email
	echo '<h3 style="margin-top: 25px; border-bottom: 1px solid #eee; padding-bottom: 5px;">' . esc_html__( 'Submission Confirmation Email', 'xpressui-bridge' ) . '</h3>';
	echo '<p style="margin-bottom: 15px; color: #64748b;">' . esc_html__( 'Send an automated confirmation email to the submitter when they submit for the first time. Resubmissions do not trigger this email.', 'xpressui-bridge' ) . '</p>';
	echo '<table class="form-table"><tbody>';

	$html = '<label><input type="checkbox" name="xpressui_notify_submitter_on_submit" value="1"' . checked( '1', $notify_submitter_on_submit, false ) . '> ';
	$html .= esc_html__( 'Send a confirmation email to the submitter when they submit.', 'xpressui-bridge' ) . '</label>';
	$html .= '<p class="description">' . esc_html__( 'Requires the submission to include an email field.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( 'xpressui_notify_submitter_on_submit', __( 'Enable', 'xpressui-bridge' ), $html );

	$html = '<textarea id="xpressui_submit_confirmation_message" name="xpressui_submit_confirmation_message" class="regular-text" rows="3">' . esc_textarea( $submit_confirmation_message ) . '</textarea>';
	$html .= '<p class="description">' . esc_html__( 'Optional message shown at the top of the confirmation email. Leave empty to use the default.', 'xpressui-bridge' ) . '</p>';
	xpressui_pro_row( 'xpressui_submit_confirmation_message', __( 'Custom message', 'xpressui-bridge' ), $html );

	echo '</tbody></table>';

	echo '</div>';
	echo '</details>';
}

function xpressui_pro_render_extra_workflow_sections( string $slug, array $s, array $overlay ): void {
	$template_context = xpressui_load_workflow_template_context( $slug );
	$sections         = isset( $template_context['rendered_form']['sections'] ) && is_array( $template_context['rendered_form']['sections'] )
		? $template_context['rendered_form']['sections']
		: [];

	$pack_fields_by_name = [];
	foreach ( $sections as $section ) {
		foreach ( (array) ( $section['fields'] ?? [] ) as $field ) {
			$fname = (string) ( $field['name'] ?? '' );
			if ( $fname !== '' ) {
				$pack_fields_by_name[ $fname ] = $field;
			}
		}
	}

	$rf_nav_labels = isset( $template_context['rendered_form']['navigation_labels'] ) && is_array( $template_context['rendered_form']['navigation_labels'] )
		? $template_context['rendered_form']['navigation_labels']
		: [];
	$pack_nav = [
		'prevLabel'   => (string) ( $rf_nav_labels['previous'] ?? 'Back' ),
		'nextLabel'   => (string) ( $rf_nav_labels['next'] ?? 'Continue' ),
		'submitLabel' => (string) ( $template_context['rendered_form']['submit_label'] ?? 'Submit' ),
	];
	$pack_theme      = isset( $template_context['theme'] ) && is_array( $template_context['theme'] ) ? $template_context['theme'] : [];
	$pack_project_bg = (string) ( $template_context['project']['background_image_url'] ?? '' );

	$ov_navigation = isset( $overlay['navigation'] ) && is_array( $overlay['navigation'] ) ? $overlay['navigation'] : [];
	$ov_theme      = isset( $overlay['theme'] ) && is_array( $overlay['theme'] ) ? $overlay['theme'] : [];
	$ov_sections   = isset( $overlay['sections'] ) && is_array( $overlay['sections'] ) ? $overlay['sections'] : [];
	$ov_fields     = isset( $overlay['fields'] ) && is_array( $overlay['fields'] ) ? $overlay['fields'] : [];
	$ov_project_bg = (string) ( $overlay['project_background_image_url'] ?? '' );

	$summary_stats = [
		'has_theme' => ! empty( $ov_theme ) || $ov_project_bg !== '',
	];

	xpressui_pro_render_card_appearance( $ov_theme, $pack_theme, $summary_stats, $ov_project_bg, $pack_project_bg );
	xpressui_pro_render_card_navigation( $ov_navigation, $pack_nav );
	xpressui_pro_render_card_sections( $sections, $ov_sections, $ov_fields, [] );
	xpressui_pro_render_card_settings( $slug );
}

function xpressui_pro_extra_workflow_save( string $slug ): void {
	if (
		! isset( $_POST['xpressui_workflow_settings_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_POST['xpressui_workflow_settings_nonce'] ) ), 'xpressui_workflow_settings_' . $slug )
	) {
		return;
	}

	// 1. First parse and save the local project settings to xpressui_project_settings option
	$raw_notify_email = isset( $_POST['xpressui_notify_email'] ) ? trim( sanitize_text_field( wp_unslash( (string) $_POST['xpressui_notify_email'] ) ) ) : '';
	$raw_redirect_url = isset( $_POST['xpressui_redirect_url'] ) ? xpressui_normalize_optional_settings_url( sanitize_text_field( wp_unslash( (string) $_POST['xpressui_redirect_url'] ) ) ) : '';
	$raw_webhook_url  = isset( $_POST['xpressui_webhook_url'] ) ? xpressui_normalize_optional_settings_url( sanitize_text_field( wp_unslash( (string) $_POST['xpressui_webhook_url'] ) ) ) : '';
	$raw_booking_url  = isset( $_POST['xpressui_booking_url'] ) ? xpressui_normalize_optional_settings_url( sanitize_text_field( wp_unslash( (string) $_POST['xpressui_booking_url'] ) ) ) : '';

	$notify_email         = '' !== $raw_notify_email ? sanitize_email( $raw_notify_email ) : '';
	$redirect_url         = '' !== $raw_redirect_url ? esc_url_raw( $raw_redirect_url ) : '';
	$webhook_url          = '' !== $raw_webhook_url ? esc_url_raw( $raw_webhook_url ) : '';
	$booking_url          = '' !== $raw_booking_url ? esc_url_raw( $booking_url ) : '';
	$booking_button_label = isset( $_POST['xpressui_booking_button_label'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_booking_button_label'] ) ) : '';

	$show_project_title       = ! empty( $_POST['xpressui_show_project_title'] ) ? '1' : '0';
	$show_required_note       = ! empty( $_POST['xpressui_show_required_fields_note'] ) ? '1' : '0';
	$section_label_visibility = sanitize_key( wp_unslash( (string) ( $_POST['xpressui_section_label_visibility'] ?? 'auto' ) ) );
	if ( ! in_array( $section_label_visibility, [ 'auto', 'show', 'hide' ], true ) ) {
		$section_label_visibility = 'auto';
	}

	$notify_submitter_on_submit  = ! empty( $_POST['xpressui_notify_submitter_on_submit'] ) ? '1' : '0';
	$submit_confirmation_message = isset( $_POST['xpressui_submit_confirmation_message'] ) ? sanitize_textarea_field( wp_unslash( (string) $_POST['xpressui_submit_confirmation_message'] ) ) : '';
	$submit_success_message      = isset( $_POST['xpressui_submit_success_message'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_submit_success_message'] ) ) : '';
	$submit_error_message        = isset( $_POST['xpressui_submit_error_message'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_submit_error_message'] ) ) : '';
	$submission_action           = sanitize_key( wp_unslash( (string) ( $_POST['xpressui_submission_action'] ?? 'submit' ) ) );
	if ( ! in_array( $submission_action, [ 'submit', 'print' ], true ) ) {
		$submission_action = 'submit';
	}

	$all_settings = get_option( 'xpressui_project_settings', [] );
	if ( ! is_array( $all_settings ) ) {
		$all_settings = [];
	}
	$all_settings[ $slug ] = [
		'notifyEmail'               => $notify_email,
		'redirectUrl'               => $redirect_url,
		'webhookUrl'                => $webhook_url,
		'bookingUrl'                => $booking_url,
		'bookingButtonLabel'        => $booking_button_label,
		'showProjectTitle'          => $show_project_title,
		'showRequiredFieldsNote'    => $show_required_note,
		'sectionLabelVisibility'    => $section_label_visibility,
		'notifySubmitterOnSubmit'   => $notify_submitter_on_submit,
		'submitConfirmationMessage' => $submit_confirmation_message,
		'submitSuccessMessage'      => $submit_success_message,
		'submitErrorMessage'        => $submit_error_message,
		'submissionAction'          => $submission_action,
	];
	update_option( 'xpressui_project_settings', $all_settings );

	// Handle validation warnings and set notice if any
	$warnings = [];
	if ( $raw_notify_email !== '' && $notify_email === '' ) {
		$warnings[] = __( 'The notification email is not valid.', 'xpressui-bridge' );
	}
	if ( $raw_redirect_url !== '' && $redirect_url === '' ) {
		$warnings[] = __( 'The post-submit redirect URL is not valid.', 'xpressui-bridge' );
	}
	if ( $raw_webhook_url !== '' && $webhook_url === '' ) {
		$warnings[] = __( 'The webhook URL is not valid.', 'xpressui-bridge' );
	}
	if ( $raw_booking_url !== '' && $booking_url === '' ) {
		$warnings[] = __( 'The booking URL is not valid.', 'xpressui-bridge' );
	}

	if ( ! empty( $warnings ) ) {
		xpressui_set_admin_notice( implode( ' ', $warnings ), 'error' );
	}

	// 2. Next parse and save the original overlay settings
	$overlay = xpressui_pro_load_workflow_overlay( $slug );
	if ( ! is_array( $overlay ) ) {
		$overlay = [];
	}

	$nav = [];
	foreach ( [ 'prev', 'next', 'submit' ] as $nav_key ) {
		$v = sanitize_text_field( wp_unslash( (string) ( $_POST[ 'xpressui_overlay_nav_' . $nav_key ] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by caller (xpressui_render_workflow_settings_page)
		if ( $v !== '' ) {
			$nav[ $nav_key ] = $v;
		}
	}
	if ( ! empty( $nav ) ) {
		$overlay['navigation'] = $nav;
	} else {
		unset( $overlay['navigation'] );
	}

	$theme_overlay = isset( $overlay['theme'] ) && is_array( $overlay['theme'] ) ? $overlay['theme'] : [];
	$bg_style = sanitize_key( wp_unslash( $_POST['xpressui_overlay_theme']['background_style'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( in_array( $bg_style, [ 'none', 'panel', 'full-bleed' ], true ) ) {
		$theme_overlay['background_style'] = $bg_style;
	} else {
		unset( $theme_overlay['background_style'] );
	}
	$frame_style = sanitize_key( wp_unslash( $_POST['xpressui_overlay_theme']['frame_style'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( in_array( $frame_style, [ 'card', 'plain' ], true ) ) {
		$theme_overlay['frame_style'] = $frame_style;
	} else {
		unset( $theme_overlay['frame_style'] );
	}
	$font_family = sanitize_text_field( wp_unslash( $_POST['xpressui_overlay_theme']['font_family'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $font_family !== '' ) {
		$theme_overlay['font_family'] = $font_family;
	} else {
		unset( $theme_overlay['font_family'] );
	}
	$colors = [ 'primary', 'surface', 'page_background', 'text', 'border' ];
	foreach ( $colors as $c ) {
		$val = sanitize_hex_color( wp_unslash( $_POST['xpressui_overlay_theme']['colors'][ $c ] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $val !== '' && $val !== null ) {
			$theme_overlay['colors'][ $c ] = $val;
		} else {
			unset( $theme_overlay['colors'][ $c ] );
		}
		}
		$radii = [ 'card', 'input', 'button' ];
		foreach ( $radii as $r ) {
			$raw_r = isset( $_POST['xpressui_overlay_theme']['radius'][ $r ] )
				? sanitize_text_field( wp_unslash( (string) $_POST['xpressui_overlay_theme']['radius'][ $r ] ) )
				: '';
			if ( '' !== $raw_r && preg_match( '/^\d+$/', (string) $raw_r ) ) {
				$theme_overlay['radius'][ $r ] = (int) $raw_r;
		} else {
			unset( $theme_overlay['radius'][ $r ] );
		}
	}
	if ( ! empty( $theme_overlay ) ) {
		$overlay['theme'] = $theme_overlay;
	} else {
		unset( $overlay['theme'] );
	}

	$bg_image_url = esc_url_raw( wp_unslash( $_POST['xpressui_overlay_project_background_image_url'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $bg_image_url !== '' ) {
		$overlay['project_background_image_url'] = $bg_image_url;
	} else {
		unset( $overlay['project_background_image_url'] );
	}

		$raw_sections = isset( $_POST['xpressui_overlay_sections'] ) && is_array( $_POST['xpressui_overlay_sections'] )
			? map_deep( wp_unslash( $_POST['xpressui_overlay_sections'] ), 'sanitize_text_field' )
			: [];
	$sections_overlay = [];
	foreach ( $raw_sections as $sname => $slabel ) {
		$sname  = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $sname );
		$slabel = sanitize_text_field( wp_unslash( (string) $slabel ) );
		if ( $sname !== '' && $slabel !== '' ) {
			$sections_overlay[ $sname ] = $slabel;
		}
	}
	if ( ! empty( $sections_overlay ) ) {
		$overlay['sections'] = $sections_overlay;
	} else {
		unset( $overlay['sections'] );
	}

	// Fields overlay.
		$raw_fields_post = isset( $_POST['xpressui_overlay_fields'] ) && is_array( $_POST['xpressui_overlay_fields'] )
			? map_deep( wp_unslash( $_POST['xpressui_overlay_fields'] ), 'sanitize_text_field' )
			: [];
	if ( ! empty( $raw_fields_post ) ) {
		$tc              = xpressui_load_workflow_template_context( $slug );
		$_pack_fields    = [];
		foreach ( (array) ( $tc['rendered_form']['sections'] ?? [] ) as $_section ) {
			foreach ( (array) ( $_section['fields'] ?? [] ) as $_field ) {
				$_fn = (string) ( $_field['name'] ?? '' );
				if ( $_fn !== '' ) {
					$_pack_fields[ $_fn ] = $_field;
				}
			}
		}
		$fields_overlay = xpressui_pro_build_fields_overlay_from_post( $raw_fields_post, $_pack_fields );
		if ( ! empty( $fields_overlay ) ) {
			$overlay['fields'] = $fields_overlay;
		} else {
			unset( $overlay['fields'] );
		}
	} else {
		unset( $overlay['fields'] );
	}

	if ( empty( $overlay ) ) {
		xpressui_pro_delete_workflow_overlay( $slug );
	} else {
		xpressui_pro_save_workflow_overlay( $slug, $overlay );
	}
}

function xpressui_pro_build_fields_overlay_from_post( array $raw_fields, array $pack_fields_by_name ): array {
	$fields_overlay = [];
	$ignored        = []; // validation warnings ignored in unified save (no inline error UI)

	foreach ( $raw_fields as $field_name => $field_data ) {
		$field_name = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $field_name );
		if ( $field_name === '' || ! is_array( $field_data ) ) {
			continue;
		}
		$pack_field = isset( $pack_fields_by_name[ $field_name ] ) && is_array( $pack_fields_by_name[ $field_name ] ) ? $pack_fields_by_name[ $field_name ] : [];
		$entry      = [];

		$v = sanitize_text_field( wp_unslash( (string) ( $field_data['label'] ?? '' ) ) );
		if ( $v !== '' ) {
			$entry['label'] = $v;
		}
		$req = (string) ( $field_data['required'] ?? '' );
		if ( $req === '1' ) {
			$entry['required'] = true;
		} elseif ( $req === '0' ) {
			$entry['required'] = false;
		}
		foreach ( [ 'placeholder' => 'sanitize_text_field', 'error_message' => 'sanitize_text_field' ] as $fkey => $sanitizer ) {
			$v = $sanitizer( wp_unslash( (string) ( $field_data[ $fkey ] ?? '' ) ) );
			if ( $v !== '' ) {
				$entry[ $fkey ] = $v;
			}
		}
		$v = sanitize_textarea_field( wp_unslash( (string) ( $field_data['desc'] ?? '' ) ) );
		if ( $v !== '' ) {
			$entry['desc'] = $v;
		}
		$v = sanitize_text_field( xpressui_pro_overlay_raw_value( $field_data, 'pattern' ) );
		if ( $v !== '' && xpressui_pro_field_supports_pattern( $pack_field ) ) {
			$entry['pattern'] = $v;
		}
		foreach ( [ 'min_len', 'max_len', 'min_choices', 'max_choices' ] as $int_key ) {
			$raw = xpressui_pro_overlay_raw_value( $field_data, $int_key );
			if ( $raw !== '' && preg_match( '/^\d+$/', $raw ) ) {
				$entry[ $int_key ] = (int) $raw;
			}
		}
		foreach ( [ 'min_value', 'max_value', 'step_value', 'max_file_size_mb' ] as $num_key ) {
			$raw = xpressui_pro_overlay_raw_value( $field_data, $num_key );
			if ( $raw !== '' && is_numeric( $raw ) ) {
				$entry[ $num_key ] = 0 + $raw;
			}
		}
		foreach ( [ 'accept', 'upload_accept_label', 'file_type_error_message', 'file_size_error_message' ] as $str_key ) {
			$v = sanitize_text_field( xpressui_pro_overlay_raw_value( $field_data, $str_key ) );
			if ( $v !== '' ) {
				$entry[ $str_key ] = $v;
			}
		}
		if ( xpressui_pro_field_has_choices( $pack_field ) && isset( $field_data['choices'] ) && is_array( $field_data['choices'] ) ) {
			$choices_entry    = [];
			$pack_choices_map = [];
			foreach ( (array) $pack_field['choices'] as $pc ) {
				$pcv = (string) ( $pc['value'] ?? '' );
				if ( $pcv !== '' ) {
					$pack_choices_map[ $pcv ] = [ 'label' => (string) ( $pc['label'] ?? $pcv ), 'enabled' => empty( $pc['disabled'] ) ];
				}
			}
			$posted_keys   = array_values( array_filter( array_map( 'strval', array_keys( $field_data['choices'] ) ), fn( $k ) => isset( $pack_choices_map[ $k ] ) ) );
			$order_changed = $posted_keys !== array_keys( $pack_choices_map );
			foreach ( $field_data['choices'] as $cv => $choice_data ) {
				$cv = (string) $cv;
				if ( $cv === '' || ! isset( $pack_choices_map[ $cv ] ) ) {
					continue;
				}
				$choice_entry = [];
				if ( is_array( $choice_data ) ) {
					$cl = sanitize_text_field( wp_unslash( (string) ( $choice_data['label'] ?? '' ) ) );
					if ( $cl !== '' && $cl !== $pack_choices_map[ $cv ]['label'] ) {
						$choice_entry['label'] = $cl;
					}
					$ce = isset( $choice_data['enabled'] ) && '1' === (string) wp_unslash( $choice_data['enabled'] );
					if ( $ce !== $pack_choices_map[ $cv ]['enabled'] ) {
						$choice_entry['enabled'] = $ce;
					}
				}
				if ( ! empty( $choice_entry ) || $order_changed ) {
					$choices_entry[ $cv ] = $choice_entry;
				}
			}
			if ( ! empty( $choices_entry ) ) {
				$entry['choices'] = $choices_entry;
			}
		}
		if ( ! empty( $entry ) ) {
			$fields_overlay[ $field_name ] = $entry;
		}
	}
	return $fields_overlay;
}
