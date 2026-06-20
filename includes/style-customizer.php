<?php
/**
 * Visual style customizer for IntakeFlow forms.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle saving customizer options.
 */
function xpressui_handle_save_style_customizer() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! isset( $_POST['xpressui_save_style_settings'] ) ) {
		return;
	}
	check_admin_referer( 'xpressui_style_settings_action', 'xpressui_style_settings_nonce' );

	$brand_color = sanitize_hex_color( wp_unslash( $_POST['xpressui_brand_color'] ?? '' ) );
	$text_color  = sanitize_hex_color( wp_unslash( $_POST['xpressui_text_color'] ?? '' ) );
	$bg_color    = sanitize_hex_color( wp_unslash( $_POST['xpressui_bg_color'] ?? '' ) );
	$radius      = sanitize_text_field( wp_unslash( $_POST['xpressui_button_radius'] ?? '' ) );
	$font        = sanitize_text_field( wp_unslash( $_POST['xpressui_font_family'] ?? '' ) );

	update_option( 'xpressui_brand_color', $brand_color );
	update_option( 'xpressui_text_color', $text_color );
	update_option( 'xpressui_bg_color', $bg_color );
	update_option( 'xpressui_button_radius', $radius );
	update_option( 'xpressui_font_family', $font );

	xpressui_set_admin_notice( __( 'Form style customizer settings saved.', 'xpressui-bridge' ), 'success' );
	wp_safe_redirect( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-settings&tab=style' ) );
	exit;
}
add_action( 'admin_init', 'xpressui_handle_save_style_customizer' );

/**
 * Generates the customizer CSS to override variables.
 *
 * @return string CSS variables override block.
 */
function xpressui_get_customizer_css() {
	$brand_color = get_option( 'xpressui_brand_color', '' );
	$text_color  = get_option( 'xpressui_text_color', '' );
	$bg_color    = get_option( 'xpressui_bg_color', '' );
	$radius      = get_option( 'xpressui_button_radius', '' );
	$font        = get_option( 'xpressui_font_family', '' );

	$css = '';
	if ( $brand_color || $text_color || $bg_color || '' !== $radius || $font ) {
		$css .= "\n/* IntakeFlow Style Customizer overrides */\n";
		$css .= ".xpressui-embed-wrapper, .xpressui-embed, #xpressui-root {\n";
		if ( $brand_color ) {
			$css .= "  --template-primary: " . esc_attr( $brand_color ) . " !important;\n";
		}
		if ( $text_color ) {
			$css .= "  --template-text: " . esc_attr( $text_color ) . " !important;\n";
		}
		if ( $bg_color ) {
			$css .= "  --template-surface: " . esc_attr( $bg_color ) . " !important;\n";
		}
		if ( '' !== $radius ) {
			$r_val = intval( $radius );
			$css .= "  --template-button-radius: " . esc_attr( $r_val ) . "px !important;\n";
			$css .= "  --template-card-radius: " . esc_attr( $r_val + 2 ) . "px !important;\n";
			$css .= "  --template-input-radius: " . esc_attr( $r_val ) . "px !important;\n";
		}
		if ( $font ) {
			$font_fallback = "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
			$css .= "  --template-font-family: '" . esc_attr( $font ) . "', " . $font_fallback . " !important;\n";
		}
		$css .= "}\n";
	}
	return $css;
}

/**
 * Renders the Style Customizer panel.
 */
function xpressui_render_style_customizer_tab() {
	$brand_color = get_option( 'xpressui_brand_color', '#2563eb' );
	$text_color  = get_option( 'xpressui_text_color', '#0f172a' );
	$bg_color    = get_option( 'xpressui_bg_color', '#ffffff' );
	$radius      = get_option( 'xpressui_button_radius', '12' );
	$font        = get_option( 'xpressui_font_family', '' );

	$fonts = [
		''          => __( 'System Default', 'xpressui-bridge' ),
		'Inter'     => 'Inter',
		'Roboto'    => 'Roboto',
		'Outfit'    => 'Outfit',
		'Open Sans' => 'Open Sans',
		'Montserrat'=> 'Montserrat',
		'Poppins'   => 'Poppins',
	];

	?>
	<div class="card xpressui-admin-card" style="margin-top: 15px;">
		<h2><?php esc_html_e( 'Style Customizer', 'xpressui-bridge' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Customize the look and feel of all embedded forms to match your WordPress theme.', 'xpressui-bridge' ); ?></p>
		
		<form method="post" style="margin-top: 20px;">
			<?php wp_nonce_field( 'xpressui_style_settings_action', 'xpressui_style_settings_nonce' ); ?>
			<input type="hidden" name="xpressui_save_style_settings" value="1">

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="xpressui_brand_color"><?php esc_html_e( 'Brand Color (Primary)', 'xpressui-bridge' ); ?></label></th>
					<td>
						<input type="color" id="xpressui_brand_color" name="xpressui_brand_color" value="<?php echo esc_attr( $brand_color ); ?>" style="width: 50px; height: 30px; border: 1px solid #ccc; cursor: pointer; vertical-align: middle;" />
						<code style="margin-left: 10px;"><?php echo esc_html( $brand_color ); ?></code>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="xpressui_text_color"><?php esc_html_e( 'Text Color', 'xpressui-bridge' ); ?></label></th>
					<td>
						<input type="color" id="xpressui_text_color" name="xpressui_text_color" value="<?php echo esc_attr( $text_color ); ?>" style="width: 50px; height: 30px; border: 1px solid #ccc; cursor: pointer; vertical-align: middle;" />
						<code style="margin-left: 10px;"><?php echo esc_html( $text_color ); ?></code>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="xpressui_bg_color"><?php esc_html_e( 'Card Background Color', 'xpressui-bridge' ); ?></label></th>
					<td>
						<input type="color" id="xpressui_bg_color" name="xpressui_bg_color" value="<?php echo esc_attr( $bg_color ); ?>" style="width: 50px; height: 30px; border: 1px solid #ccc; cursor: pointer; vertical-align: middle;" />
						<code style="margin-left: 10px;"><?php echo esc_html( $bg_color ); ?></code>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="xpressui_button_radius"><?php esc_html_e( 'Corner Radius (px)', 'xpressui-bridge' ); ?></label></th>
					<td>
						<input type="number" id="xpressui_button_radius" name="xpressui_button_radius" value="<?php echo esc_attr( $radius ); ?>" min="0" max="30" class="small-text" /> px
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="xpressui_font_family"><?php esc_html_e( 'Font Family', 'xpressui-bridge' ); ?></label></th>
					<td>
						<select id="xpressui_font_family" name="xpressui_font_family">
							<?php foreach ( $fonts as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $font, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endphp; ?>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Customizer Settings', 'xpressui-bridge' ), 'primary', 'submit_style' ); ?>
		</form>
	</div>
	<?php
}
