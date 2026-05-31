<?php

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// Plugin constants the bridge defines at runtime; stubbed so compiled templates
// that reference them (e.g. enqueued asset URLs) render outside WordPress.
if ( ! defined( 'XPRESSUI_BRIDGE_URL' ) ) {
	define( 'XPRESSUI_BRIDGE_URL', '' );
}
if ( ! defined( 'XPRESSUI_BRIDGE_VERSION' ) ) {
	define( 'XPRESSUI_BRIDGE_VERSION', '0' );
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( mixed $value, int $flags = 0, int $depth = 512 ): string|false {
		return json_encode( $value, $flags, $depth );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( string $url ): string {
		return htmlspecialchars( $url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}

// Style/script enqueue and print helpers are no-ops outside WordPress: the
// parity harness compares structural HTML, and these functions emit nothing
// (their PHP calls are stripped on the Jinja side too).
if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( ...$args ): void {}
}

if ( ! function_exists( 'wp_add_inline_style' ) ) {
	function wp_add_inline_style( ...$args ): bool {
		return true;
	}
}

if ( ! function_exists( 'wp_print_styles' ) ) {
	function wp_print_styles( ...$args ): void {}
}

if ( ! function_exists( 'wp_print_script_tag' ) ) {
	function wp_print_script_tag( ...$args ): void {}
}

if ( ! function_exists( 'wp_print_inline_script_tag' ) ) {
	function wp_print_inline_script_tag( ...$args ): void {}
}

require __DIR__ . '/runtime.php';

if ($argc < 3) {
    fwrite(STDERR, "Usage: php render-compiled-template.php <template> <context-json>\n");
    exit(1);
}

$template = $argv[1];
$contextPath = $argv[2];
if (!is_file($contextPath)) {
    fwrite(STDERR, "Missing context JSON file: {$contextPath}\n");
    exit(1);
}

$json = file_get_contents($contextPath);
if ($json === false) {
    fwrite(STDERR, "Unable to read context JSON file: {$contextPath}\n");
    exit(1);
}

$context = json_decode($json, true);
if (!is_array($context)) {
    fwrite(STDERR, "Invalid JSON context in {$contextPath}\n");
    exit(1);
}

echo xpressui_bridge_template_render_template($template, $context);
