<?php

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
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
