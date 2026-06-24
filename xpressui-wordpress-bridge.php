<?php
/**
 * Plugin Name:       IntakeFlow Forms – Multi-Step Form Builder, File Upload & Client Intake
 * Description:       Multi-step form builder for WordPress: contact forms, file upload, client intake & document collection. Import from Contact Form 7 / Gravity Forms, embed with a shortcode, and manage submissions in a wp-admin inbox.
 * Version:           1.1.0
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

if ( isset( $_SERVER['QUERY_STRING'] ) ) {
	$xpressui_query = [];
	parse_str( sanitize_text_field( wp_unslash( (string) $_SERVER['QUERY_STRING'] ) ), $xpressui_query );
	if ( ( isset( $xpressui_query['xpressui_clear_cache'] ) || isset( $xpressui_query['nocache'] ) ) && function_exists( 'opcache_reset' ) ) {
		opcache_reset();
	}
}

register_activation_hook( __FILE__, function() {
	if ( function_exists( 'opcache_reset' ) ) {
		opcache_reset();
	}
} );

define( 'XPRESSUI_BRIDGE_VERSION', '1.1.0' );
define( 'XPRESSUI_BRIDGE_RUNTIME_VERSION', '1.0.17' );
define( 'XPRESSUI_BRIDGE_DIR', plugin_dir_path( __FILE__ ) );
define( 'XPRESSUI_BRIDGE_URL', plugin_dir_url( __FILE__ ) );
define( 'XPRESSUI_BRIDGE_TEXT_DOMAIN', 'xpressui-bridge' );
define( 'XPRESSUI_BRIDGE_BUNDLED_WORKFLOWS_DIR', XPRESSUI_BRIDGE_DIR . 'default-workflows/' );

require_once XPRESSUI_BRIDGE_DIR . 'includes/helpers.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/post-type.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/filters.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/metaboxes.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/admin-pages.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/dashboard-page.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/instrumentation.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/rest-endpoint.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/shortcode.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/catalog-render.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/time-slots-render.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/notifications.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/webhooks.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/shell.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/privacy.php';

// --- SaaS / PRO Connection loading ---
require_once XPRESSUI_BRIDGE_DIR . 'includes/console-sync.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/webhook-sync.php';

// --- IntakeFlow Plugin Add-ons & Integrations ---
require_once XPRESSUI_BRIDGE_DIR . 'includes/page-builder-widgets.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/client-portal.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/form-importer.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/submission-exporter.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/webhook-logs.php';
require_once XPRESSUI_BRIDGE_DIR . 'includes/dashboard-widget.php';

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
// Submission status is owned by the IntakeFlow Console (SaaS) and is READ-ONLY in
// WordPress, so the inline "Mark <status>" row actions and their handler are not
// registered. The list status FILTER stays (it only filters, it never mutates).

// --- Admin pages ---
add_action( 'admin_menu', 'xpressui_register_admin_page' );
add_action( 'admin_menu', 'xpressui_register_settings_page' );
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
	// First-run: land the admin on the IntakeFlow Dashboard (quick start + import +
	// quota) instead of the empty submissions list. Handled in dashboard-page.php.
	set_transient( 'xpressui_activation_redirect', 1, 60 );
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
	if ( in_array( $screen->id, [ 'xpressui_submission_page_xpressui-bridge', 'xpressui_submission_page_xpressui-settings' ], true ) ) {
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
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'settingsMap'    => [],
				'localWorkflows' => xpressui_get_local_workflows_metadata(),
				'nonce'          => wp_create_nonce( 'xpressui_console_sync_nonce' ),
				'i18n'           => [
					'saving'        => __( 'Saving…', 'xpressui-bridge' ),
					'saved'         => __( 'Saved.', 'xpressui-bridge' ),
					'error'         => __( 'Error.', 'xpressui-bridge' ),
					'networkError'  => __( 'Network error.', 'xpressui-bridge' ),
					'toggleSection' => __( 'Toggle section', 'xpressui-bridge' ),
					'outOfSync'     => __( 'Out of Sync', 'xpressui-bridge' ),
					'creating'      => __( 'Creating…', 'xpressui-bridge' ),
					'created'       => __( 'Workflow created on your IntakeFlow Console.', 'xpressui-bridge' ),
				],
			]
		);
	}

	// Form Importer wizard — only the bridge page's "import" tab renders the wizard
	// markup (see xpressui_render_form_importer_tab()), so scope its CSS/JS to that tab.
	$current_tab = 'list';
	if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$parsed_url = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		if ( ! empty( $parsed_url['query'] ) ) {
			parse_str( $parsed_url['query'], $parsed_query );
			if ( isset( $parsed_query['tab'] ) && is_string( $parsed_query['tab'] ) ) {
				$current_tab = sanitize_key( $parsed_query['tab'] );
			}
		}
	}
	if ( 'xpressui_submission_page_xpressui-bridge' === $screen->id && 'import' === $current_tab ) {
		wp_enqueue_style(
			'xpressui-bridge-admin-importer',
			XPRESSUI_BRIDGE_URL . 'assets/admin-importer.css',
			[],
			XPRESSUI_BRIDGE_VERSION
		);
		wp_enqueue_script(
			'xpressui-bridge-admin-importer',
			XPRESSUI_BRIDGE_URL . 'assets/admin-importer.js',
			[ 'jquery' ],
			XPRESSUI_BRIDGE_VERSION,
			true
		);
		wp_localize_script(
			'xpressui-bridge-admin-importer',
			'xpressuiImporter',
			[
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'importNonce'     => wp_create_nonce( 'xpressui_import_form_wizard_nonce' ),
				'syncNonce'       => wp_create_nonce( 'xpressui_console_sync_nonce' ),
				'isSaasConnected' => xpressui_is_saas_connected(),
				'i18n'            => [
					'next'             => __( 'Next', 'xpressui-bridge' ),
					'convertSync'      => __( 'Import & Sync', 'xpressui-bridge' ),
					'convertSave'      => __( 'Import', 'xpressui-bridge' ),
					'copied'           => __( 'Copied!', 'xpressui-bridge' ),
					'copy'             => __( 'Copy', 'xpressui-bridge' ),
					'syncingToConsole' => __( 'Syncing to Console...', 'xpressui-bridge' ),
					'syncFailed'       => __( 'Sync failed. Please try again.', 'xpressui-bridge' ),
					'createdOnConsole' => __( 'Created on your IntakeFlow Console', 'xpressui-bridge' ),
					'syncedBack'       => __( 'Synced back to this site and ready to embed.', 'xpressui-bridge' ),
					'networkError'     => __( 'Network error. Please try again.', 'xpressui-bridge' ),
					'uploadingProject' => __( 'Uploading and creating project in SaaS Console...', 'xpressui-bridge' ),
					'downloadingFiles' => __( 'Downloading and synchronizing workflow files...', 'xpressui-bridge' ),
					'generatingLocal'  => __( 'Generating local standalone workflow package...', 'xpressui-bridge' ),
					'importComplete'   => __( 'Import complete!', 'xpressui-bridge' ),
					'savedLocally'     => __( 'Saved Locally', 'xpressui-bridge' ),
					'convertedRunning' => __( 'Your form was converted and is running on this site.', 'xpressui-bridge' ),
					'details'          => __( 'Details:', 'xpressui-bridge' ),
					'convertedWorkflow' => __( 'Your form was converted to an IntakeFlow Workflow.', 'xpressui-bridge' ),
				],
			]
		);
	}

	if ( 'xpressui_submission_page_xpressui-console' === $screen->id ) {
		wp_enqueue_script(
			'xpressui-bridge-admin-console',
			XPRESSUI_BRIDGE_URL . 'assets/admin-console.js',
			[ 'jquery' ],
			XPRESSUI_BRIDGE_VERSION,
			true
		);
		$conn = xpressui_get_console_connection();
		wp_localize_script(
			'xpressui-bridge-admin-console',
			'xpressuiBridgeConsole',
			[
				'apiUrl'   => $conn['apiUrl'] ?? 'https://app.intakeflow.dev',
				'apiToken' => $conn['apiToken'] ?? '',
				'nonce'    => wp_create_nonce( 'xpressui_console_sync_nonce' ),
				'i18n'     => [
					'syncing' => __( 'Synchronizing workflow updates...', 'xpressui-bridge' ),
					'synced'  => __( 'Workflow synchronized successfully!', 'xpressui-bridge' ),
					'failed'  => __( 'Sync failed.', 'xpressui-bridge' ),
				],
			]
		);
	}
}
