<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Generated from export/_partials/field.j2. Do not edit manually.
if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "textarea"))): ?>
<?php xpressui_bridge_template_include_template('fields/textarea.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), ["select-image", "select-product"]))): ?>
<?php xpressui_bridge_template_include_template('fields/image-gallery.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "product-list"))): ?>
<?php xpressui_bridge_template_include_template('fields/product-list.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "quiz"))): ?>
<?php xpressui_bridge_template_include_template('fields/quiz.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), ["select-one", "select-multiple"]))): ?>
<?php xpressui_bridge_template_include_template('fields/choice-select.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), ["radio-buttons", "checkboxes"]))): ?>
<?php xpressui_bridge_template_include_template('fields/choice-list.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), ["checkbox", "switch"]))): ?>
<?php xpressui_bridge_template_include_template('fields/toggle.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "section-select"))): ?>
<?php xpressui_bridge_template_include_template('fields/reference-list.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), ["file", "upload-image"]))): ?>
<?php xpressui_bridge_template_include_template('fields/upload.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "camera-photo"))): ?>
<?php xpressui_bridge_template_include_template('fields/camera-photo.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "qr-scan"))): ?>
<?php xpressui_bridge_template_include_template('fields/qr-scan.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "document-scan"))): ?>
<?php xpressui_bridge_template_include_template('fields/document-scan.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "setting"))): ?>
<?php xpressui_bridge_template_include_template('fields/setting.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), "approval-state"))): ?>
<?php xpressui_bridge_template_include_template('fields/approval-state.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), ["image", "media", "logo", "hero"]))): ?>
<?php xpressui_bridge_template_include_template('fields/media-display.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), ["link", "btn", "call2action"]))): ?>
<?php xpressui_bridge_template_include_template('fields/link-action.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), ["action-type", "action-target"]))): ?>
<?php xpressui_bridge_template_include_template('fields/input.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), ["title", "html", "output", "form"]))): ?>
<?php xpressui_bridge_template_include_template('fields/content-block.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), ["rich-editor"]))): ?>
<?php xpressui_bridge_template_include_template('fields/textarea.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), ["grid-size", "slider"]))): ?>
<?php xpressui_bridge_template_include_template('fields/range-display.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type'), ["text", "email", "password", "tel", "url", "number", "price", "integer", "age", "tax", "date", "time", "datetime", "search", "color", "range", "regex", "slug"]))): ?>
<?php xpressui_bridge_template_include_template('fields/input.php', $xpressui_ctx); ?>
<?php else: ?>
<?php xpressui_bridge_template_include_template('fields/unsupported.php', $xpressui_ctx); ?>
<?php endif; ?>
