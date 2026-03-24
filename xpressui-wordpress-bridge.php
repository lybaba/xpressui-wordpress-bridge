<?php
/**
 * Plugin Name:       XPressUI WordPress Bridge
 * Plugin URI:        https://iakpress.com/document-intake
 * Description:       Receives and manages submissions from exported XPressUI workflow packages. Embed any XPressUI form on your WordPress site with a shortcode and review submissions in wp-admin.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            IAKPress
 * Author URI:        https://iakpress.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       xpressui-bridge
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'XPRESSUI_BRIDGE_VERSION', '1.0.0' );
define( 'XPRESSUI_BRIDGE_DIR', plugin_dir_path( __FILE__ ) );
define( 'XPRESSUI_BRIDGE_URL', plugin_dir_url( __FILE__ ) );
define( 'XPRESSUI_BRIDGE_TEXT_DOMAIN', 'xpressui-bridge' );

require_once XPRESSUI_BRIDGE_DIR . 'includes/helpers.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/post-type.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/filters.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/metaboxes.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/admin-pages.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/rest-endpoint.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/shortcode.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/notifications.php';

// --- Post type ---
add_action( 'init', 'xpressui_register_submission_post_type' );
add_filter( 'manage_xpressui_submission_posts_columns', 'xpressui_submission_columns' );
add_action( 'manage_xpressui_submission_posts_custom_column', 'xpressui_submission_column_content', 10, 2 );

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

// --- REST endpoint ---
add_action( 'rest_api_init', 'xpressui_register_rest_routes' );

// --- Shortcode ---
add_shortcode( 'xpressui', 'xpressui_render_shortcode' );

// --- Activation ---
register_activation_hook( __FILE__, 'xpressui_activate' );

function xpressui_activate() {
	xpressui_register_submission_post_type();
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
