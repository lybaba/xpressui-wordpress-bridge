<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Shared runtime for PHP templates compiled from Jinja source.

final class XuiJinjaSafeString
{
    public function __construct(public readonly string $value)
    {
    }
}

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

function xui_jinja_templates_root(): string
{
    $override = getenv('XPRESSUI_PHP_TEMPLATES_ROOT');
    if (is_string($override) && $override !== '') {
        return $override;
    }
    return __DIR__ . '/generated';
}

function xui_jinja_render_template(string $template, array $context): string
{
    ob_start();
    $__ctx = $context;
    include xui_jinja_templates_root() . '/' . $template;
    return (string) ob_get_clean();
}

function xui_jinja_include(string $template, array $context): void
{
    $default_root = xui_jinja_templates_root();
    $template_dirs = function_exists('apply_filters')
        ? (array) apply_filters('xpressui_field_template_dirs', [$default_root])
        : [$default_root];
    $template_path = null;
    foreach ($template_dirs as $dir) {
        $candidate = rtrim((string) $dir, '/') . '/' . $template;
        if (is_file($candidate)) {
            $template_path = $candidate;
            break;
        }
    }
    if ($template_path === null) {
        $fallback = $default_root . '/fields/unsupported.php';
        if (is_file($fallback) && $template !== 'fields/unsupported.php') {
            $__ctx = $context;
            include $fallback;
        }
        return;
    }
    $__ctx = $context;
    include $template_path;
}

function xui_jinja_context_get(array $context, string $name): mixed
{
    return $context[$name] ?? null;
}

function xui_jinja_attr(mixed $value, string $attr): mixed
{
    if ($value instanceof XuiJinjaSafeString && $attr === 'value') {
        return $value->value;
    }
    if (is_array($value)) {
        return $value[$attr] ?? null;
    }
    if (is_object($value)) {
        if (isset($value->{$attr}) || property_exists($value, $attr)) {
            return $value->{$attr};
        }
        if (method_exists($value, '__get')) {
            return $value->{$attr};
        }
    }
    return null;
}

function xui_jinja_mark_safe(mixed $value): XuiJinjaSafeString
{
    if ($value instanceof XuiJinjaSafeString) {
        return $value;
    }
    return new XuiJinjaSafeString(xui_jinja_stringify($value));
}

function xui_jinja_escape(mixed $value): string
{
    if ($value instanceof XuiJinjaSafeString) {
        return $value->value;
    }
    return htmlspecialchars(xui_jinja_stringify($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function xui_jinja_stringify(mixed $value): string
{
    if ($value instanceof XuiJinjaSafeString) {
        return $value->value;
    }
    if ($value === null) {
        return 'None';
    }
    if ($value === true) {
        return 'True';
    }
    if ($value === false) {
        return 'False';
    }
    if (is_string($value)) {
        return $value;
    }
    if (is_int($value)) {
        return (string) $value;
    }
    if (is_float($value)) {
        $encoded = json_encode($value, JSON_PRESERVE_ZERO_FRACTION);
        return is_string($encoded) ? $encoded : (string) $value;
    }
    if (is_array($value)) {
        $is_list = array_is_list($value);
        $parts = [];
        foreach ($value as $key => $item) {
            if ($is_list) {
                $parts[] = xui_jinja_stringify($item);
                continue;
            }
            $parts[] = xui_jinja_stringify($key) . ': ' . xui_jinja_stringify($item);
        }
        return $is_list ? '[' . implode(', ', $parts) . ']' : '{' . implode(', ', $parts) . '}';
    }
    if (is_object($value) && method_exists($value, '__toString')) {
        return (string) $value;
    }
    return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
}

function xui_jinja_truthy(mixed $value): bool
{
    if ($value instanceof XuiJinjaSafeString) {
        return $value->value !== '';
    }
    if (is_array($value)) {
        return count($value) > 0;
    }
    return (bool) $value;
}

function xui_jinja_concat(array $items): string
{
    $result = '';
    foreach ($items as $item) {
        $result .= xui_jinja_stringify($item);
    }
    return $result;
}

function xui_jinja_or(mixed $left, mixed $right): mixed
{
    return xui_jinja_truthy($left) ? $left : $right;
}

function xui_jinja_and(mixed $left, mixed $right): mixed
{
    return xui_jinja_truthy($left) ? $right : $left;
}

function xui_jinja_eq(mixed $left, mixed $right): bool
{
    return $left == $right;
}

function xui_jinja_in(mixed $needle, mixed $haystack): bool
{
    if (is_array($haystack)) {
        return in_array($needle, $haystack, false);
    }
    if (is_string($haystack)) {
        return $needle !== null && str_contains($haystack, xui_jinja_stringify($needle));
    }
    return false;
}

function xui_jinja_iterable(mixed $value): array
{
    if (is_array($value)) {
        return array_values($value);
    }
    if ($value instanceof Traversable) {
        return array_values(iterator_to_array($value));
    }
    if ($value === null || $value === false) {
        return [];
    }
    return array_values((array) $value);
}

function xui_jinja_filter_length(mixed $value): int
{
    if (is_array($value)) {
        return count($value);
    }
    if (is_string($value)) {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
    if ($value instanceof Countable) {
        return count($value);
    }
    if ($value === null) {
        return 0;
    }
    return count((array) $value);
}

function xui_jinja_filter_round(mixed $value, mixed $precision = 0, mixed $method = 'common'): float
{
    $number = (float) $value;
    $digits = (int) $precision;
    return match ((string) $method) {
        'floor' => floor($number * (10 ** $digits)) / (10 ** $digits),
        'ceil' => ceil($number * (10 ** $digits)) / (10 ** $digits),
        default => round($number, $digits),
    };
}

function xui_jinja_filter_tojson(mixed $value): string
{
    $json = json_encode(
        $value,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
    );
    if (!is_string($json)) {
        return 'null';
    }
    return str_replace(
        ['<', '>', '&', '\''],
        ['\\u003c', '\\u003e', '\\u0026', '\\u0027'],
        $json
    );
}

function xui_jinja_test_none(mixed $value): bool
{
    return $value === null;
}

function xui_wp_text(mixed $value, string $domain = 'xpressui-bridge'): string
{
    $text = xui_jinja_stringify($value);
    if (function_exists('__')) {
        return (string) __($text, $domain);
    }
    return $text;
}
