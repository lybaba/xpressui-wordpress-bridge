<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!isset($xpressui_ctx) || !is_array($xpressui_ctx)) {
    throw new RuntimeException('Missing template context array.');
}
?><?php xpressui_bridge_template_include_template('time-slots-catalog/date-grouped-list.php', $xpressui_ctx); ?>

