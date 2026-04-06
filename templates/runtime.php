<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Shared runtime for PHP templates compiled from Jinja source.

final class XpressuiBridgeTemplateSafeString {
	public function __construct( public readonly string $value ) {
	}
}

function xpressui_bridge_template_templates_root(): string {
	$override = getenv( 'XPRESSUI_PHP_TEMPLATES_ROOT' );
	if ( is_string( $override ) && '' !== $override ) {
		return $override;
	}

	return __DIR__ . '/core';
}

function xpressui_bridge_template_render_template( string $template, array $context ): string {
	ob_start();
	$xpressui_ctx = $context;
	include xpressui_bridge_template_templates_root() . '/' . $template;
	return (string) ob_get_clean();
}

function xpressui_bridge_template_include_template( string $template, array $context ): void {
	$default_root   = xpressui_bridge_template_templates_root();
	$template_dirs  = function_exists( 'apply_filters' )
		? (array) apply_filters( 'xpressui_field_template_dirs', [ $default_root ] )
		: [ $default_root ];
	$template_path = null;

	foreach ( $template_dirs as $dir ) {
		$candidate = rtrim( (string) $dir, '/' ) . '/' . $template;
		if ( is_file( $candidate ) ) {
			$template_path = $candidate;
			break;
		}
	}

	if ( null === $template_path ) {
		$fallback = $default_root . '/fields/unsupported.php';
		if ( is_file( $fallback ) && 'fields/unsupported.php' !== $template ) {
			$xpressui_ctx = $context;
			include $fallback;
		}
		return;
	}

	$xpressui_ctx = $context;
	include $template_path;
}

function xpressui_bridge_template_context_get( array $context, string $name ): mixed {
	return $context[ $name ] ?? null;
}

function xpressui_bridge_template_attr( mixed $value, string $attr ): mixed {
	if ( $value instanceof XpressuiBridgeTemplateSafeString && 'value' === $attr ) {
		return $value->value;
	}
	if ( is_array( $value ) ) {
		return $value[ $attr ] ?? null;
	}
	if ( is_object( $value ) ) {
		if ( isset( $value->{$attr} ) || property_exists( $value, $attr ) ) {
			return $value->{$attr};
		}
		if ( method_exists( $value, '__get' ) ) {
			return $value->{$attr};
		}
	}
	return null;
}

function xpressui_bridge_template_mark_safe( mixed $value ): XpressuiBridgeTemplateSafeString {
	if ( $value instanceof XpressuiBridgeTemplateSafeString ) {
		return $value;
	}
	return new XpressuiBridgeTemplateSafeString( xpressui_bridge_template_stringify( $value ) );
}

function xpressui_bridge_template_stringify( mixed $value ): string {
	if ( $value instanceof XpressuiBridgeTemplateSafeString ) {
		return $value->value;
	}
	if ( null === $value ) {
		return 'None';
	}
	if ( true === $value ) {
		return 'True';
	}
	if ( false === $value ) {
		return 'False';
	}
	if ( is_string( $value ) ) {
		return $value;
	}
	if ( is_int( $value ) ) {
		return (string) $value;
	}
	if ( is_float( $value ) ) {
		$encoded = json_encode( $value, JSON_PRESERVE_ZERO_FRACTION );
		return is_string( $encoded ) ? $encoded : (string) $value;
	}
	if ( is_array( $value ) ) {
		$is_list = array_is_list( $value );
		$parts   = [];
		foreach ( $value as $key => $item ) {
			if ( $is_list ) {
				$parts[] = xpressui_bridge_template_stringify( $item );
				continue;
			}
			$parts[] = xpressui_bridge_template_stringify( $key ) . ': ' . xpressui_bridge_template_stringify( $item );
		}
		return $is_list ? '[' . implode( ', ', $parts ) . ']' : '{' . implode( ', ', $parts ) . '}';
	}
	if ( is_object( $value ) && method_exists( $value, '__toString' ) ) {
		return (string) $value;
	}
	return (string) json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION );
}

function xpressui_bridge_template_truthy( mixed $value ): bool {
	if ( $value instanceof XpressuiBridgeTemplateSafeString ) {
		return '' !== $value->value;
	}
	if ( is_array( $value ) ) {
		return count( $value ) > 0;
	}
	return (bool) $value;
}

function xpressui_bridge_template_concat( array $items ): string {
	$result = '';
	foreach ( $items as $item ) {
		$result .= xpressui_bridge_template_stringify( $item );
	}
	return $result;
}

function xpressui_bridge_template_or_value( mixed $left, mixed $right ): mixed {
	return xpressui_bridge_template_truthy( $left ) ? $left : $right;
}

function xpressui_bridge_template_and_value( mixed $left, mixed $right ): mixed {
	return xpressui_bridge_template_truthy( $left ) ? $right : $left;
}

function xpressui_bridge_template_not( mixed $value ): bool {
	return ! xpressui_bridge_template_truthy( $value );
}

function xpressui_bridge_template_all( array $items ): bool {
	foreach ( $items as $item ) {
		if ( ! xpressui_bridge_template_truthy( $item ) ) {
			return false;
		}
	}

	return true;
}

function xpressui_bridge_template_any( array $items ): bool {
	foreach ( $items as $item ) {
		if ( xpressui_bridge_template_truthy( $item ) ) {
			return true;
		}
	}

	return false;
}

function xpressui_bridge_template_equals( mixed $left, mixed $right ): bool {
	return $left == $right;
}

function xpressui_bridge_template_contains( mixed $needle, mixed $haystack ): bool {
	if ( is_array( $haystack ) ) {
		return in_array( $needle, $haystack, false );
	}
	if ( is_string( $haystack ) ) {
		return null !== $needle && str_contains( $haystack, xpressui_bridge_template_stringify( $needle ) );
	}
	return false;
}

function xpressui_bridge_template_iterable( mixed $value ): array {
	if ( is_array( $value ) ) {
		return array_values( $value );
	}
	if ( $value instanceof Traversable ) {
		return array_values( iterator_to_array( $value ) );
	}
	if ( null === $value || false === $value ) {
		return [];
	}
	return array_values( (array) $value );
}

function xpressui_bridge_template_filter_length( mixed $value ): int {
	if ( is_array( $value ) ) {
		return count( $value );
	}
	if ( is_string( $value ) ) {
		return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
	}
	if ( $value instanceof Countable ) {
		return count( $value );
	}
	if ( null === $value ) {
		return 0;
	}
	return count( (array) $value );
}

function xpressui_bridge_template_filter_round( mixed $value, mixed $precision = 0, mixed $method = 'common' ): float {
	$number = (float) $value;
	$digits = (int) $precision;

	return match ( (string) $method ) {
		'floor' => floor( $number * ( 10 ** $digits ) ) / ( 10 ** $digits ),
		'ceil'  => ceil( $number * ( 10 ** $digits ) ) / ( 10 ** $digits ),
		default => round( $number, $digits ),
	};
}

function xpressui_bridge_template_filter_tojson( mixed $value ): string {
	$json = json_encode(
		$value,
		JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
	);
	if ( ! is_string( $json ) ) {
		return 'null';
	}
	return str_replace(
		[ '<', '>', '&', '\'' ],
		[ '\\u003c', '\\u003e', '\\u0026', '\\u0027' ],
		$json
	);
}

function xpressui_bridge_template_test_none( mixed $value ): bool {
	return null === $value;
}

function xpressui_bridge_template_wp_text( mixed $value, string $domain = 'xpressui-bridge' ): string {
	// Template strings are dynamic and cannot be extracted by i18n tooling.
	// Return them as-is; translations are handled by the XPressUI console at export time.
	return xpressui_bridge_template_stringify( $value );
}
