<?php
/**
 * [xpressui] shortcode — embeds an installed XPressUI workflow package.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the [xpressui id="project-slug"] shortcode.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string HTML output.
 */
function xpressui_render_shortcode( $atts ) {
	$atts = shortcode_atts(
		[
			'id'     => '',
			'height' => '',
			'width'  => '100%',
			'title'  => __( 'XPressUI Form', 'xpressui-bridge' ),
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
	$shell_url   = xpressui_get_workflow_shell_url( $slug );

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

	$iframe_id  = 'xpressui-frame-' . esc_attr( $slug );
	$src        = esc_url( $shell_url );
	$title      = esc_attr( (string) $atts['title'] );
	$width      = esc_attr( (string) ( $atts['width'] ?: '100%' ) );

	// Fixed-height mode (e.g. height="600") disables the auto-resize script.
	$fixed_height = (string) $atts['height'];
	if ( $fixed_height !== '' && is_numeric( $fixed_height ) ) {
		$height_attr = ' height="' . esc_attr( $fixed_height ) . '"';
		$resize_attr = '';
	} else {
		$height_attr = ' height="600"';
		$resize_attr = ' data-xpressui-autoresize="1"';
	}

	// Enqueue the resize script (only once, only when autoresize is needed).
	if ( $resize_attr !== '' ) {
		wp_enqueue_script(
			'xpressui-shortcode',
			XPRESSUI_BRIDGE_URL . 'assets/shortcode.js',
			[],
			XPRESSUI_BRIDGE_VERSION,
			true
		);
	}

	$html  = '<div class="xpressui-embed-wrapper">';
	$html .= '<iframe'
		. ' id="' . $iframe_id . '"'
		. ' src="' . $src . '"'
		. ' title="' . $title . '"'
		. ' style="width:' . $width . ';border:none;display:block;"'
		. $height_attr
		. $resize_attr
		. ' scrolling="no"'
		. ' allow="camera;microphone;geolocation"'
		. '></iframe>';
	$html .= '</div>';

	return $html;
}
