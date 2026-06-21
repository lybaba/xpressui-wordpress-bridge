<?php
defined( 'ABSPATH' ) || exit;

require_once XPRESSUI_PRO_DIR . 'includes/submission-gate.php';
require_once XPRESSUI_PRO_DIR . 'includes/console-sync.php';
require_once XPRESSUI_PRO_DIR . 'includes/overlay.php';
require_once XPRESSUI_PRO_DIR . 'includes/overlay-admin.php';
require_once XPRESSUI_PRO_DIR . 'includes/status-page.php';
require_once XPRESSUI_PRO_DIR . 'includes/mobile-capture.php';
require_once XPRESSUI_PRO_DIR . 'includes/status-notifications.php';
// update-checker.php is loaded unconditionally from the main plugin file.

add_filter( 'xpressui_field_template_dirs', 'xpressui_pro_register_template_dirs' );

function xpressui_pro_register_template_dirs( array $dirs ): array {
	$dirs[] = XPRESSUI_PRO_DIR . 'templates/generated/';
	return $dirs;
}
