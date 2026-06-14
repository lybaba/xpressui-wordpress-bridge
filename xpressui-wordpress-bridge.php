<?php
/**
 * Plugin Name:       Multi-Step Forms & Client Document Intake – XPressUI Bridge
 * Description:       Receives and manages submissions from exported XPressUI workflow packages. Embed any XPressUI form on your site with a shortcode and review submissions in wp-admin.
 * Version:           1.0.93
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            IAKPress
 * Author URI:        https://intakeflow.dev/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       xpressui-bridge
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only isset() presence check; triggers an idempotent opcache_reset only. No form data is read, sanitized, or stored, and this runs at plugin load before pluggable functions exist (so a capability check is not possible here).
if ( ( isset( $_GET['xpressui_clear_cache'] ) || isset( $_GET['nocache'] ) ) && function_exists( 'opcache_reset' ) ) {
	opcache_reset();
}

register_activation_hook( __FILE__, function() {
	if ( function_exists( 'opcache_reset' ) ) {
		opcache_reset();
	}
} );

define( 'XPRESSUI_BRIDGE_VERSION', '1.0.93' );
define( 'XPRESSUI_BRIDGE_RUNTIME_VERSION', '1.0.16' );
define( 'XPRESSUI_BRIDGE_DIR', plugin_dir_path( __FILE__ ) );
define( 'XPRESSUI_BRIDGE_URL', plugin_dir_url( __FILE__ ) );
define( 'XPRESSUI_BRIDGE_TEXT_DOMAIN', 'xpressui-bridge' );
define( 'XPRESSUI_BRIDGE_BUNDLED_WORKFLOWS_DIR', XPRESSUI_BRIDGE_DIR . 'default-workflows/' );

require_once XPRESSUI_BRIDGE_DIR . 'includes/helpers.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/post-type.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/filters.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/metaboxes.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/admin-pages.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/workflow-settings-page.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/instrumentation.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/rest-endpoint.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/shortcode.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/notifications.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/webhooks.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/shell.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/privacy.php';

// --- SaaS / PRO Connection loading ---
require_once XPRESSUI_BRIDGE_DIR . 'includes/license-handler.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/console-sync.php';

define( 'XPRESSUI_PRO_VERSION', XPRESSUI_BRIDGE_VERSION );
define( 'XPRESSUI_PRO_RUNTIME_VERSION', XPRESSUI_BRIDGE_RUNTIME_VERSION );
define( 'XPRESSUI_PRO_DIR', XPRESSUI_BRIDGE_DIR );
define( 'XPRESSUI_PRO_URL', XPRESSUI_BRIDGE_URL );
define( 'XPRESSUI_PRO_BUNDLED_WORKFLOWS_DIR', XPRESSUI_BRIDGE_BUNDLED_WORKFLOWS_DIR );

function xpressui_pro_get_runtime_file(): string {
	return XPRESSUI_PRO_DIR . 'runtime/xpressui-' . XPRESSUI_PRO_RUNTIME_VERSION . '.umd.js';
}

function xpressui_pro_has_bundled_runtime(): bool {
	return true;
}

function xpressui_pro_get_runtime_asset_url(): string {
	return XPRESSUI_PRO_URL . 'runtime/xpressui-' . XPRESSUI_PRO_RUNTIME_VERSION . '.umd.js';
}

require_once XPRESSUI_BRIDGE_DIR . 'includes/pro-runtime.php';

// --- Post type ---
add_action( 'init', 'xpressui_register_submission_post_type' );
add_filter( 'manage_xpressui_submission_posts_columns', 'xpressui_submission_columns' );
add_action( 'manage_xpressui_submission_posts_custom_column', 'xpressui_submission_column_content', 10, 2 );
add_action( 'before_delete_post', 'xpressui_delete_submission_media_on_post_delete', 10, 2 );

// --- Metaboxes ---
add_action( 'add_meta_boxes', 'xpressui_register_metaboxes', 10, 2 );
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
	if ( $screen->post_type === 'xpressui_submission' ) {
		wp_enqueue_media();
		wp_enqueue_script(
			'xpressui-bridge-admin-submissions',
			XPRESSUI_BRIDGE_URL . 'assets/admin-submissions.js',
			[],
			XPRESSUI_BRIDGE_VERSION,
			true
		);
	}
	if ( 'xpressui_submission_page_xpressui-bridge' === $screen->id ) {
		wp_enqueue_script(
			'xpressui-bridge-admin-wf',
			XPRESSUI_BRIDGE_URL . 'assets/admin-workflows.js',
			[],
			XPRESSUI_BRIDGE_VERSION,
			true
		);
		wp_localize_script(
			'xpressui-bridge-admin-wf',
			'xpressuiBridgeAdmin',
			[
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
				'settingsMap' => [],
				'i18n'        => [
					'saving'        => __( 'Saving…', 'xpressui-bridge' ),
					'saved'         => __( 'Saved.', 'xpressui-bridge' ),
					'error'         => __( 'Error.', 'xpressui-bridge' ),
					'networkError'  => __( 'Network error.', 'xpressui-bridge' ),
					'toggleSection' => __( 'Toggle section', 'xpressui-bridge' ),
				],
			]
		);
	}
}
