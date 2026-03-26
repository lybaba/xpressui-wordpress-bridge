<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'xpressui_runtime_url', 'xpressui_bridge_override_runtime_url', 5, 2 );

function xpressui_bridge_override_runtime_url( string $url, string $slug ): string {
	$slug = sanitize_title( $slug );
	$runtime_file = XPRESSUI_BRIDGE_DIR . 'runtime/xpressui-light-' . XPRESSUI_BRIDGE_RUNTIME_VERSION . '.umd.js';
	if ( file_exists( $runtime_file ) ) {
		return XPRESSUI_BRIDGE_URL . 'runtime/xpressui-light-' . XPRESSUI_BRIDGE_RUNTIME_VERSION . '.umd.js';
	}

	return $url;
}
