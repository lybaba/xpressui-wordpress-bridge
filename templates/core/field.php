<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><?php $xpressui_ctx['render_type'] = xpressui_bridge_template_or_value(xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'render_type'), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'type')); ?>
<?php if (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), "textarea"))): ?>
<?php xpressui_bridge_template_include_template('fields/textarea.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["select-image", "select-product"]))): ?>
<?php xpressui_bridge_template_include_template('fields/image-gallery.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), "product-list"))): ?>
<?php xpressui_bridge_template_include_template('fields/product-list.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), "quiz"))): ?>
<?php xpressui_bridge_template_include_template('fields/quiz.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["select-one", "select-multiple"]))): ?>
<?php xpressui_bridge_template_include_template('fields/choice-select.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["radio-buttons", "checkboxes"]), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'is_date_range_catalog')))): ?>
<?php xpressui_bridge_template_include_template('fields/choice-list-date-range.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["radio-buttons", "checkboxes"]), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'is_month_range_catalog')))): ?>
<?php xpressui_bridge_template_include_template('fields/choice-list-month-range.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_and_value(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["radio-buttons", "checkboxes"]), xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'is_time_slots_catalog')))): ?>
<?php xpressui_bridge_template_include_template('fields/choice-list-time-slots.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["radio-buttons", "checkboxes"]))): ?>
<?php xpressui_bridge_template_include_template('fields/choice-list.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), "repeater"))): ?>
<?php xpressui_bridge_template_include_template('fields/repeater.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["checkbox", "switch"]))): ?>
<?php xpressui_bridge_template_include_template('fields/toggle.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), "section-select"))): ?>
<?php xpressui_bridge_template_include_template('fields/reference-list.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["file", "upload-image", "payment-proof"]))): ?>
<?php xpressui_bridge_template_include_template('fields/upload.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), "camera-photo"))): ?>
<?php xpressui_bridge_template_include_template('fields/camera-photo.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), "camera-photo-list"))): ?>
<?php xpressui_bridge_template_include_template('fields/camera-photo-list.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), "qr-scan"))): ?>
<?php xpressui_bridge_template_include_template('fields/qr-scan.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), "document-scan"))): ?>
<?php xpressui_bridge_template_include_template('fields/document-scan.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), "signature"))): ?>
<?php xpressui_bridge_template_include_template('fields/signature.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), "payment-stripe"))): ?>
<?php xpressui_bridge_template_include_template('fields/payment-stripe.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), "setting"))): ?>
<?php xpressui_bridge_template_include_template('fields/setting.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_equals(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), "approval-state"))): ?>
<?php xpressui_bridge_template_include_template('fields/approval-state.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["image", "media", "logo", "hero"]))): ?>
<?php xpressui_bridge_template_include_template('fields/media-display.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["link", "btn", "call2action"]))): ?>
<?php xpressui_bridge_template_include_template('fields/link-action.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["action-type", "action-target"]))): ?>
<?php xpressui_bridge_template_include_template('fields/input.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["title", "html", "output", "form"]))): ?>
<?php xpressui_bridge_template_include_template('fields/content-block.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["rich-editor"]))): ?>
<?php xpressui_bridge_template_include_template('fields/textarea.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["grid-size", "slider"]))): ?>
<?php xpressui_bridge_template_include_template('fields/range-display.php', $xpressui_ctx); ?>
<?php elseif (xpressui_bridge_template_truthy(xpressui_bridge_template_contains(xpressui_bridge_template_context_get($xpressui_ctx, 'render_type'), ["text", "email", "password", "tel", "url", "number", "price", "integer", "age", "tax", "date", "time", "datetime", "search", "color", "range", "regex", "slug"]))): ?>
<?php xpressui_bridge_template_include_template('fields/input.php', $xpressui_ctx); ?>
<?php else: ?>
<?php xpressui_bridge_template_include_template('fields/unsupported.php', $xpressui_ctx); ?>
<?php endif; ?>

