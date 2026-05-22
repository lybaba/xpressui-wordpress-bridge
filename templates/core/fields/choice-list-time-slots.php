<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><?php $xpressui_ctx['time_slots_catalog'] = xpressui_bridge_template_attr(xpressui_bridge_template_context_get($xpressui_ctx, 'field'), 'time_slots_catalog'); ?>
<?php $xpressui_ctx['catalog_page'] = xpressui_bridge_template_or_value(xpressui_bridge_template_context_get($xpressui_ctx, 'catalog_page'), ["base_url" => "#"]); ?>
<?php xpressui_bridge_template_include_template('time-slots-catalog/date-grouped-list.php', $xpressui_ctx); ?>

