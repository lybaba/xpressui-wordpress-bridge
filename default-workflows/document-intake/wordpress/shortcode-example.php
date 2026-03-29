<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Example shortcode wrapper for document-intake.
function xpressui_render_document_intake() {
	return do_shortcode( '[xpressui id="document-intake"]' );
}

add_shortcode( 'xpressui_document_intake', 'xpressui_render_document_intake' );
