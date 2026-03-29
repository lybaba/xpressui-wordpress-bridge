<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Example shortcode output for validation-playground.
function xpressui_render_validation_playground() {
	return do_shortcode( '[xpressui id="validation-playground"]' );
}

add_shortcode( 'xpressui_validation_playground', 'xpressui_render_validation_playground' );
