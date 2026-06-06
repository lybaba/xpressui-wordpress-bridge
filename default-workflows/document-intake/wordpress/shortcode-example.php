<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Example shortcode wrapper for document-intake.
function xpressui_render_document_intake() {
	$allowed_html = function_exists( 'xpressui_get_shell_allowed_html' ) ? xpressui_get_shell_allowed_html() : 'post';
	return wp_kses( do_shortcode( '[xpressui id="document-intake"]' ), $allowed_html );
}

add_shortcode( 'xpressui_document_intake', 'xpressui_render_document_intake' );
