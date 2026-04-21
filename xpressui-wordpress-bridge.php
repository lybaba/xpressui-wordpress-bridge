<?php
/**
 * Plugin Name:       XPressUI Bridge
 * Plugin URI:        https://xpressui.iakpress.com/
 * Description:       Receives and manages submissions from exported XPressUI workflow packages. Embed any XPressUI form on your site with a shortcode and review submissions in wp-admin.
 * Version:           1.0.74
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            IAKPress
 * Author URI:        https://github.com/lybaba
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       xpressui-bridge
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'XPRESSUI_BRIDGE_VERSION', '1.0.74' );
define( 'XPRESSUI_BRIDGE_RUNTIME_VERSION', '1.0.12' );
define( 'XPRESSUI_BRIDGE_DIR', plugin_dir_path( __FILE__ ) );
define( 'XPRESSUI_BRIDGE_URL', plugin_dir_url( __FILE__ ) );
define( 'XPRESSUI_BRIDGE_TEXT_DOMAIN', 'xpressui-bridge' );
define( 'XPRESSUI_BRIDGE_BUNDLED_WORKFLOWS_DIR', XPRESSUI_BRIDGE_DIR . 'default-workflows/' );

require_once XPRESSUI_BRIDGE_DIR . 'includes/helpers.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/post-type.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/filters.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/metaboxes.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/admin-pages.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/rest-endpoint.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/shortcode.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/notifications.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/webhooks.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/shell.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/privacy.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/light-runtime.php';
// Guard: older versions of the Pro plugin also loaded these files.
// Skip if already defined to avoid fatal redeclaration errors during the transition.
if ( ! function_exists( 'xpressui_pro_filter_template_context' ) ) {
	require_once XPRESSUI_BRIDGE_DIR . 'includes/overlay.php';
	require_once XPRESSUI_BRIDGE_DIR . 'includes/overlay-admin.php';
}

// --- Post type ---
add_action( 'init', 'xpressui_register_submission_post_type' );
add_filter( 'manage_xpressui_submission_posts_columns', 'xpressui_submission_columns' );
add_action( 'manage_xpressui_submission_posts_custom_column', 'xpressui_submission_column_content', 10, 2 );
add_action( 'before_delete_post', 'xpressui_delete_submission_media_on_post_delete', 10, 2 );

// --- Metaboxes ---
add_action( 'add_meta_boxes', 'xpressui_register_metaboxes' );
add_action( 'save_post_xpressui_submission', 'xpressui_save_submission_status' );

// --- List filters & row actions ---
add_action( 'restrict_manage_posts', 'xpressui_render_submission_filters' );
add_action( 'pre_get_posts', 'xpressui_apply_submission_filters' );
add_filter( 'post_row_actions', 'xpressui_add_submission_row_actions', 10, 2 );
add_action( 'admin_init', 'xpressui_handle_submission_status_action' );

// --- Admin pages ---
add_action( 'admin_menu', 'xpressui_register_submission_admin_pages' );
add_action( 'admin_menu', 'xpressui_register_admin_page' );
add_action( 'admin_enqueue_scripts', 'xpressui_enqueue_admin_assets' );
add_action( 'admin_init', 'xpressui_maybe_install_bundled_workflows' );
add_action( 'admin_init', 'xpressui_handle_workflow_admin_actions' );

// --- REST endpoint ---
add_action( 'rest_api_init', 'xpressui_register_rest_routes' );

// --- Shortcode ---
add_shortcode( 'xpressui', 'xpressui_render_shortcode' );
add_filter( 'query_vars', 'xpressui_register_shell_query_var' );
add_action( 'template_redirect', 'xpressui_maybe_render_shell_page' );
add_action( 'admin_init', 'xpressui_register_privacy_content' );
add_filter( 'wp_privacy_personal_data_exporters', 'xpressui_register_personal_data_exporter' );
add_filter( 'wp_privacy_personal_data_erasers', 'xpressui_register_personal_data_eraser' );

// --- Activation ---
register_activation_hook( __FILE__, 'xpressui_activate' );

function xpressui_activate() {
	xpressui_register_submission_post_type();
	xpressui_install_bundled_workflows();
	flush_rewrite_rules();
}

function xpressui_enqueue_admin_assets( $hook ) {
	$screen = get_current_screen();
	if ( ! $screen ) {
		return;
	}
	if ( $screen->post_type !== 'xpressui_submission' && strpos( $hook, 'xpressui' ) === false ) {
		return;
	}
	wp_enqueue_style(
		'xpressui-bridge-admin',
		XPRESSUI_BRIDGE_URL . 'assets/admin.css',
		[],
		XPRESSUI_BRIDGE_VERSION
	);
}
