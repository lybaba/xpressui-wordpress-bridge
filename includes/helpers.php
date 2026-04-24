<?php
/**
 * Pure data helpers — no HTML output, no side effects.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Workflow package helpers
// ---------------------------------------------------------------------------

function xpressui_get_workflows_base_dir() {
	$upload_dir = wp_get_upload_dir();
	if ( ! is_array( $upload_dir ) || ! empty( $upload_dir['error'] ) ) {
		return '';
	}
	return trailingslashit( $upload_dir['basedir'] ) . 'xpressui/';
}

function xpressui_get_workflows_base_url() {
	$upload_dir = wp_get_upload_dir();
	if ( ! is_array( $upload_dir ) || ! empty( $upload_dir['error'] ) ) {
		return '';
	}
	return trailingslashit( $upload_dir['baseurl'] ) . 'xpressui/';
}

function xpressui_workflow_directory_has_required_artifacts( $workflow_dir ) {
	$workflow_dir = trailingslashit( (string) $workflow_dir );
	if ( '' === $workflow_dir || ! is_dir( $workflow_dir ) ) {
		return false;
	}

	$manifest_path = $workflow_dir . 'manifest.json';
	if ( ! file_exists( $manifest_path ) ) {
		return false;
	}

	$manifest_json = file_get_contents( $manifest_path );
	$manifest      = is_string( $manifest_json ) ? json_decode( $manifest_json, true ) : null;
	if ( ! is_array( $manifest ) ) {
		return false;
	}

	$artifacts = [
		'manifest.json',
	];

	$config_path = isset( $manifest['artifacts']['config'] ) ? sanitize_text_field( (string) $manifest['artifacts']['config'] ) : 'form.config.json';
	if ( '' !== $config_path ) {
		$artifacts[] = $config_path;
	}

	if ( isset( $manifest['artifacts']['templateContext'] ) ) {
		$template_context_path = sanitize_text_field( (string) $manifest['artifacts']['templateContext'] );
		if ( '' !== $template_context_path ) {
			$artifacts[] = $template_context_path;
		}
	}

	$runtime_path = isset( $manifest['artifacts']['wordpress']['runtime'] ) ? sanitize_text_field( (string) $manifest['artifacts']['wordpress']['runtime'] ) : '';
	if ( '' !== $runtime_path ) {
		$artifacts[] = $runtime_path;
	}

	foreach ( array_values( array_unique( $artifacts ) ) as $artifact ) {
		if ( ! file_exists( $workflow_dir . ltrim( $artifact, '/' ) ) ) {
			return false;
		}
	}

	return true;
}

function xpressui_get_workflow_package_dir( $slug ) {
	$slug     = sanitize_title( (string) $slug );
	$base_dir = xpressui_get_workflows_base_dir();
	if ( '' === $slug || '' === $base_dir ) {
		return '';
	}
	return trailingslashit( $base_dir ) . $slug . '/';
}

function xpressui_get_workflow_package_url( $slug ) {
	$slug     = sanitize_title( (string) $slug );
	$base_url = xpressui_get_workflows_base_url();
	if ( '' === $slug || '' === $base_url ) {
		return '';
	}
	return trailingslashit( $base_url ) . $slug . '/';
}

function xpressui_get_installed_workflow_slugs() {
	$target_dir = xpressui_get_workflows_base_dir();
	if ( $target_dir === '' || ! is_dir( $target_dir ) ) {
		return [];
	}

	$installed_slugs = [];
	foreach ( (array) scandir( $target_dir ) as $item ) {
		$slug = sanitize_title( (string) $item );
		if ( $slug === '' || $slug !== $item ) {
			continue;
		}
		if (
			is_dir( $target_dir . $item )
			&& xpressui_workflow_directory_has_required_artifacts( $target_dir . $item )
		) {
			$installed_slugs[] = $slug;
		}
	}

	sort( $installed_slugs );
	return array_values( array_unique( $installed_slugs ) );
}

function xpressui_is_installed_workflow( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( $slug === '' ) {
		return false;
	}
	return in_array( $slug, xpressui_get_installed_workflow_slugs(), true );
}

function xpressui_sanitize_request_identifier( $value, $max_length = 191 ) {
	$value = sanitize_text_field( (string) $value );
	$value = preg_replace( '/[^A-Za-z0-9._:-]/', '', $value );
	$value = is_string( $value ) ? trim( $value ) : '';
	if ( $value === '' ) {
		return '';
	}
	return substr( $value, 0, (int) $max_length );
}

function xpressui_get_workflow_manifest_path( $slug ) {
	$slug     = sanitize_title( (string) $slug );
	$base_dir = xpressui_get_workflows_base_dir();
	if ( $slug === '' || $base_dir === '' ) {
		return '';
	}
	return trailingslashit( $base_dir ) . $slug . '/manifest.json';
}

function xpressui_load_workflow_manifest( $slug ) {
	$manifest_path = xpressui_get_workflow_manifest_path( $slug );
	if ( $manifest_path === '' || ! file_exists( $manifest_path ) ) {
		return [];
	}

	$manifest_json = file_get_contents( $manifest_path );
	if ( ! is_string( $manifest_json ) || trim( $manifest_json ) === '' ) {
		return [];
	}

	$manifest = json_decode( $manifest_json, true );
	return is_array( $manifest ) ? $manifest : [];
}

function xpressui_load_bundled_workflow_manifest( $slug ) {
	$slug        = sanitize_title( (string) $slug );
	$bundled_dir = xpressui_get_bundled_workflow_source_dir( $slug );
	if ( '' === $slug || '' === $bundled_dir ) {
		return [];
	}

	$manifest_path = trailingslashit( $bundled_dir ) . $slug . '/manifest.json';
	if ( ! file_exists( $manifest_path ) ) {
		return [];
	}

	$manifest_json = file_get_contents( $manifest_path );
	if ( ! is_string( $manifest_json ) || '' === trim( $manifest_json ) ) {
		return [];
	}

	$manifest = json_decode( $manifest_json, true );
	return is_array( $manifest ) ? $manifest : [];
}

function xpressui_get_bundled_workflow_source_dirs() {
	$default_dir = defined( 'XPRESSUI_BRIDGE_BUNDLED_WORKFLOWS_DIR' ) ? XPRESSUI_BRIDGE_BUNDLED_WORKFLOWS_DIR : '';
	$dirs        = apply_filters( 'xpressui_bundled_workflow_source_dirs', [ $default_dir ] );
	$dirs        = is_array( $dirs ) ? $dirs : [];

	$normalized = [];
	foreach ( $dirs as $dir ) {
		$dir = is_string( $dir ) ? trailingslashit( $dir ) : '';
		if ( '' === $dir || ! is_dir( $dir ) ) {
			continue;
		}
		$normalized[] = $dir;
	}

	return array_values( array_unique( $normalized ) );
}

function xpressui_get_bundled_workflow_source_dir( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug ) {
		return '';
	}

	foreach ( xpressui_get_bundled_workflow_source_dirs() as $dir ) {
		$workflow_dir = trailingslashit( $dir ) . $slug;
		if ( is_dir( $workflow_dir ) && xpressui_workflow_directory_has_required_artifacts( $workflow_dir ) ) {
			return $dir;
		}
	}

	return '';
}

function xpressui_get_manifest_fingerprint( array $manifest ) {
	if ( empty( $manifest ) ) {
		return '';
	}
	return md5( wp_json_encode( $manifest ) );
}

function xpressui_get_workflow_artifact_path( $slug, $artifact_key, $fallback = '' ) {
	$manifest    = xpressui_load_workflow_manifest( $slug );
	$package_dir = xpressui_get_workflow_package_dir( $slug );
	if ( empty( $manifest ) || '' === $package_dir ) {
		return '';
	}

	$artifacts = is_array( $manifest['artifacts'] ?? null ) ? $manifest['artifacts'] : [];
	$value     = $artifacts[ $artifact_key ] ?? $fallback;

	if ( ! is_string( $value ) || '' === $value ) {
		return '';
	}

	$relative_path = ltrim( $value, '/' );
	return $package_dir . $relative_path;
}

function xpressui_get_workflow_artifact_url( $slug, $artifact_key, $fallback = '' ) {
	$manifest     = xpressui_load_workflow_manifest( $slug );
	$package_url  = xpressui_get_workflow_package_url( $slug );
	if ( empty( $manifest ) || '' === $package_url ) {
		return '';
	}

	$artifacts = is_array( $manifest['artifacts'] ?? null ) ? $manifest['artifacts'] : [];
	$value     = $artifacts[ $artifact_key ] ?? $fallback;

	if ( ! is_string( $value ) || '' === $value ) {
		return '';
	}

	$relative_path = ltrim( $value, '/' );
	return $package_url . $relative_path;
}

function xpressui_get_workflow_artifact_contents( $slug, $artifact_key, $fallback = '' ) {
	$artifact_path = xpressui_get_workflow_artifact_path( $slug, $artifact_key, $fallback );
	if ( '' === $artifact_path || ! file_exists( $artifact_path ) ) {
		return '';
	}

	$contents = file_get_contents( $artifact_path );
	return is_string( $contents ) ? $contents : '';
}

function xpressui_load_workflow_template_context( $slug ) {
	$template_context_json = xpressui_get_workflow_artifact_contents( $slug, 'templateContext', 'template.context.json' );
	if ( '' === $template_context_json ) {
		return [];
	}

	$template_context = json_decode( $template_context_json, true );
	return is_array( $template_context ) ? $template_context : [];
}

function xpressui_get_workflow_shell_url( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug ) {
		return '';
	}
	return add_query_arg(
		[
			'xpressui_shell' => $slug,
		],
		home_url( '/' )
	);
}

function xpressui_get_plugin_shell_init_url() {
	$relative = 'assets/shell/plugin-shell-init.js';
	$path     = XPRESSUI_BRIDGE_DIR . $relative;
	return file_exists( $path ) ? esc_url_raw( XPRESSUI_BRIDGE_URL . $relative ) : '';
}

function xpressui_describe_runtime_source( $runtime_url, $slug ) {
	$runtime_url = esc_url_raw( (string) $runtime_url );
	$slug        = sanitize_title( (string) $slug );
	if ( '' === $runtime_url ) {
		return 'missing';
	}

	$bridge_runtime_url = '';
	if ( defined( 'XPRESSUI_BRIDGE_RUNTIME_VERSION' ) ) {
		$bridge_runtime_url = esc_url_raw(
			XPRESSUI_BRIDGE_URL . 'runtime/xpressui-light-' . XPRESSUI_BRIDGE_RUNTIME_VERSION . '.umd.js'
		);
	}
	if ( '' !== $bridge_runtime_url && $runtime_url === $bridge_runtime_url ) {
		return 'plugin-bridge';
	}

	$pro_runtime_url = '';
	if ( defined( 'XPRESSUI_PRO_RUNTIME_VERSION' ) && defined( 'XPRESSUI_PRO_DIR' ) ) {
		$pro_runtime_url = esc_url_raw(
			plugin_dir_url( XPRESSUI_PRO_DIR . 'xpressui-wordpress-bridge-pro.php' )
			. 'runtime/xpressui-' . XPRESSUI_PRO_RUNTIME_VERSION . '.umd.js'
		);
	}
	if ( '' !== $pro_runtime_url && $runtime_url === $pro_runtime_url ) {
		return 'plugin-pro';
	}

	$workflow_package_url = xpressui_get_workflow_package_url( $slug );
	if ( '' !== $workflow_package_url && str_starts_with( $runtime_url, $workflow_package_url ) ) {
		return 'workflow-package';
	}

	return 'custom';
}

function xpressui_get_workflow_shell_payload( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug || ! xpressui_is_installed_workflow( $slug ) ) {
		return [];
	}

	$manifest = xpressui_load_workflow_manifest( $slug );
	if ( empty( $manifest ) ) {
		return [];
	}

	return [
		'slug'  => $slug,
		'title' => sanitize_text_field( (string) ( $manifest['projectName'] ?? $slug ) ),
	];
}

function xpressui_get_shell_translations() {
	return [
		'requiredFields' => __( '* Required fields', 'xpressui-bridge' ),
		'submissionFailedTitle' => __( 'Submission failed', 'xpressui-bridge' ),
		'submissionFailedMessage' => __( 'Submission failed. Please review the form and try again.', 'xpressui-bridge' ),
		'submissionFeedbackIdle' => __( 'Submission feedback will appear here after the runtime handles the form.', 'xpressui-bridge' ),
		'submitting' => __( 'Submitting...', 'xpressui-bridge' ),
		'submittingTitle' => __( 'Submitting', 'xpressui-bridge' ),
		'submissionReceivedTitle' => __( 'Submission received', 'xpressui-bridge' ),
		'submissionReceivedMessage' => __( 'Submission received.', 'xpressui-bridge' ),
		'submissionStatusTitle' => __( 'Submission status', 'xpressui-bridge' ),
		'unableLoadWorkflow' => __( 'Unable to load this XPressUI workflow.', 'xpressui-bridge' ),
	];
}

function xpressui_render_compiled_workflow_shell_html( $slug ) {
	$slug             = sanitize_title( (string) $slug );
	$template_context = xpressui_load_workflow_template_context( $slug );
	$manifest         = xpressui_load_workflow_manifest( $slug );
	if ( '' === $slug || empty( $template_context ) || empty( $manifest ) ) {
		return '';
	}

	// Allow extensions (e.g. the pro plugin) to modify the template context before rendering.
	$template_context = apply_filters( 'xpressui_template_context', $template_context, $slug );
	$template_context = xpressui_apply_additional_file_slots_to_template_context( $template_context, $slug );

	$runtime_file = XPRESSUI_BRIDGE_DIR . 'templates/runtime.php';
	if ( ! file_exists( $runtime_file ) ) {
		return '';
	}

	require_once $runtime_file;

	if ( ! function_exists( 'xpressui_bridge_template_render_template' ) ) {
		return '';
	}

	$rendered_html = xpressui_bridge_template_render_template( 'project-page.html.php', $template_context );
	if ( ! is_string( $rendered_html ) || '' === trim( $rendered_html ) ) {
		return '';
	}

	$wordpress_artifacts = is_array( $manifest['artifacts']['wordpress'] ?? null ) ? $manifest['artifacts']['wordpress'] : [];
	$runtime_relative    = is_string( $wordpress_artifacts['runtime'] ?? null ) ? ltrim( (string) $wordpress_artifacts['runtime'], '/' ) : '';
	$runtime_url         = XPRESSUI_BRIDGE_URL . 'runtime/xpressui-light-' . XPRESSUI_BRIDGE_RUNTIME_VERSION . '.umd.js';
	if ( '' !== $runtime_relative ) {
		$runtime_url = xpressui_get_workflow_package_url( $slug ) . $runtime_relative;
	}
	$runtime_url = (string) apply_filters( 'xpressui_runtime_url', $runtime_url, $slug );

	$init_url = xpressui_get_plugin_shell_init_url();
	$translations_script = '<script>window.XPRESSUI_I18N = ' . wp_json_encode( xpressui_get_shell_translations() ) . ';</script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- injected into a standalone HTML document string; wp_add_inline_script() requires a registered handle and cannot be used outside the WordPress enqueue lifecycle
	$shell_meta_script   = '<script>window.XPRESSUI_SHELL_META = ' . wp_json_encode(
		[
			'slug'             => $slug,
			'runtimeUrl'       => $runtime_url,
			'runtimeRelative'  => $runtime_relative,
			'runtimeSource'    => xpressui_describe_runtime_source( $runtime_url, $slug ),
			'workflowPackageUrl' => xpressui_get_workflow_package_url( $slug ),
			'shellInitUrl'     => $init_url,
		]
	) . ';</script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- injected into a standalone HTML document string; wp_add_inline_script() requires a registered handle and cannot be used outside the WordPress enqueue lifecycle

	if ( '' !== $runtime_relative && '' !== $runtime_url ) {
		$rendered_html = str_replace( './' . $runtime_relative, esc_url_raw( $runtime_url ), $rendered_html );
	}

	if ( '' !== $init_url ) {
		$rendered_html = str_replace( './init.js', esc_url_raw( $init_url ), $rendered_html );
	}

	if ( false !== strpos( $rendered_html, '</head>' ) ) {
		$rendered_html = str_replace( '</head>', $translations_script . $shell_meta_script . '</head>', $rendered_html );
	} else {
		$rendered_html = $translations_script . $shell_meta_script . $rendered_html;
	}

	$runtime_script = '';
	if ( '' !== $runtime_url ) {
		$runtime_script = '<script src="' . esc_url( $runtime_url ) . '"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- injected dynamically into rendered HTML string, wp_enqueue_script() cannot be used here
	}
	$init_script = '';
	if ( '' !== $init_url ) {
		$init_script = '<script src="' . esc_url( $init_url ) . '"></script>'; // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- injected dynamically into rendered HTML string, wp_enqueue_script() cannot be used here
	}

	if ( false === strpos( $rendered_html, esc_url_raw( $runtime_url ) ) && false === strpos( $rendered_html, './init.js' ) ) {
		if ( false !== strpos( $rendered_html, '</body>' ) ) {
			$rendered_html = str_replace( '</body>', $runtime_script . $init_script . '</body>', $rendered_html );
		} else {
			$rendered_html .= $runtime_script . $init_script;
		}
	}

	return $rendered_html;
}

function xpressui_can_render_compiled_workflow_shell( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug ) {
		return false;
	}

	$template_context = xpressui_load_workflow_template_context( $slug );
	$template_file    = XPRESSUI_BRIDGE_DIR . 'templates/core/project-page.html.php';
	$runtime_file     = XPRESSUI_BRIDGE_DIR . 'templates/runtime.php';

	return ! empty( $template_context ) && file_exists( $template_file ) && file_exists( $runtime_file );
}

function xpressui_get_workflow_manifest_registry() {
	$registry = get_option( 'xpressui_workflow_manifest_registry', [] );
	return is_array( $registry ) ? $registry : [];
}

function xpressui_get_workflow_manifest_meta( $slug ) {
	$slug     = sanitize_title( (string) $slug );
	$registry = xpressui_get_workflow_manifest_registry();
	$entry    = $registry[ $slug ] ?? [];
	$entry    = is_array( $entry ) ? $entry : [];

	if ( '' === $slug ) {
		return $entry;
	}

	$needs_listing_group = empty( $entry['listingGroup'] );
	$needs_listing_title = empty( $entry['listingTitle'] );
	$needs_project_name  = empty( $entry['projectName'] );

	if ( ! $needs_listing_group && ! $needs_listing_title && ! $needs_project_name ) {
		return $entry;
	}

	$manifest = [];
	if ( ! empty( $entry['isBundled'] ) ) {
		$manifest = xpressui_load_bundled_workflow_manifest( $slug );
	}

	if ( empty( $manifest ) ) {
		$manifest = xpressui_load_workflow_manifest( $slug );
	}

	if ( empty( $manifest ) ) {
		return $entry;
	}

	$meta = is_array( $manifest['meta'] ?? null ) ? $manifest['meta'] : [];
	if ( $needs_listing_group && ! empty( $meta['listingGroup'] ) ) {
		$entry['listingGroup'] = sanitize_key( (string) $meta['listingGroup'] );
	}
	if ( $needs_listing_title && ! empty( $meta['listingTitle'] ) ) {
		$entry['listingTitle'] = sanitize_text_field( (string) $meta['listingTitle'] );
	}
	if ( $needs_project_name && ! empty( $manifest['projectName'] ) ) {
		$entry['projectName'] = sanitize_text_field( (string) $manifest['projectName'] );
	}

	return $entry;
}

function xpressui_store_workflow_manifest_meta( $slug, array $manifest ) {
	$slug = sanitize_title( (string) $slug );
	if ( $slug === '' ) {
		return;
	}

	$runtime_requirements = is_array( $manifest['runtimeRequirements'] ?? null ) ? $manifest['runtimeRequirements'] : [];
	$capabilities         = is_array( $manifest['capabilities'] ?? null ) ? $manifest['capabilities'] : [];
	$compatibility        = is_array( $manifest['wordpressCompatibility'] ?? null ) ? $manifest['wordpressCompatibility'] : [];
	$meta                 = is_array( $manifest['meta'] ?? null ) ? $manifest['meta'] : [];

	$registry          = xpressui_get_workflow_manifest_registry();
	$registry[ $slug ] = [
		'schemaVersion' => sanitize_text_field( (string) ( $manifest['schemaVersion'] ?? '' ) ),
		'projectId'     => xpressui_sanitize_request_identifier( $manifest['projectId'] ?? '' ),
		'projectSlug'   => $slug,
		'projectName'   => sanitize_text_field( (string) ( $manifest['projectName'] ?? '' ) ),
		'generatedAt'   => sanitize_text_field( (string) ( $manifest['generatedAt'] ?? '' ) ),
		'runtimeVersion' => sanitize_text_field( (string) ( $manifest['xpressui']['version'] ?? '' ) ),
		'runtimeTier'   => sanitize_key( (string) ( $runtime_requirements['tier'] ?? '' ) ),
		'bridgeMode'    => sanitize_key( (string) ( $compatibility['bridgeMode'] ?? '' ) ),
		'shortcodeMode' => sanitize_key( (string) ( $compatibility['shortcodeMode'] ?? '' ) ),
		'templateProfile' => sanitize_key( (string) ( $compatibility['templateProfile'] ?? '' ) ),
		'components'    => array_values( array_filter( array_map( 'sanitize_key', (array) ( $capabilities['components'] ?? [] ) ) ) ),
		'features'      => array_values( array_filter( array_map( 'sanitize_key', (array) ( $capabilities['features'] ?? [] ) ) ) ),
		'themeFeatures' => array_values( array_filter( array_map( 'sanitize_key', (array) ( $capabilities['themeFeatures'] ?? [] ) ) ) ),
		'stepCount'     => isset( $meta['stepCount'] ) ? (int) $meta['stepCount'] : 0,
		'fieldCount'    => isset( $meta['fieldCount'] ) ? (int) $meta['fieldCount'] : 0,
		'listingGroup'  => sanitize_key( (string) ( $meta['listingGroup'] ?? '' ) ),
		'listingTitle'  => sanitize_text_field( (string) ( $meta['listingTitle'] ?? '' ) ),
		'manifestFingerprint' => xpressui_get_manifest_fingerprint( $manifest ),
		'usesLegacyShellArtifacts' => xpressui_manifest_uses_legacy_shell_artifacts( $manifest ),
		'isBundled'     => xpressui_is_bundled_workflow( $slug ),
		'storedAt'      => current_time( 'mysql' ),
	];
	update_option( 'xpressui_workflow_manifest_registry', $registry, false );
}

function xpressui_manifest_uses_legacy_shell_artifacts( array $manifest ) {
	$artifacts     = is_array( $manifest['artifacts'] ?? null ) ? $manifest['artifacts'] : [];
	$compatibility = is_array( $manifest['wordpressCompatibility'] ?? null ) ? $manifest['wordpressCompatibility'] : [];
	$bridge_mode   = sanitize_key( (string) ( $compatibility['bridgeMode'] ?? 'plugin-shell' ) );

	if ( 'legacy-shell' === $bridge_mode ) {
		return true;
	}

	return ! empty( $artifacts['html'] ) || ! empty( $artifacts['initJs'] );
}

function xpressui_delete_workflow_manifest_meta( $slug ) {
	$slug     = sanitize_title( (string) $slug );
	$registry = xpressui_get_workflow_manifest_registry();
	if ( isset( $registry[ $slug ] ) ) {
		unset( $registry[ $slug ] );
		update_option( 'xpressui_workflow_manifest_registry', $registry, false );
	}
}

function xpressui_get_bundled_workflow_slugs() {
	$slugs = [];
	foreach ( xpressui_get_bundled_workflow_source_dirs() as $bundled_dir ) {
		foreach ( (array) scandir( $bundled_dir ) as $item ) {
			$slug = sanitize_title( (string) $item );
			if ( $slug === '' || $slug !== $item ) {
				continue;
			}
			$workflow_dir = trailingslashit( $bundled_dir ) . $slug;
			if ( is_dir( $workflow_dir ) && xpressui_workflow_directory_has_required_artifacts( $workflow_dir ) ) {
				$slugs[] = $slug;
			}
		}
	}

	sort( $slugs );
	return array_values( array_unique( $slugs ) );
}

function xpressui_is_bundled_workflow( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( $slug === '' ) {
		return false;
	}
	return in_array( $slug, xpressui_get_bundled_workflow_slugs(), true );
}

function xpressui_copy_directory_recursive( $source_dir, $target_dir ) {
	if ( ! is_dir( $source_dir ) ) {
		return false;
	}

	if ( ! file_exists( $target_dir ) && ! wp_mkdir_p( $target_dir ) ) {
		return false;
	}

	$items = scandir( $source_dir );
	if ( ! is_array( $items ) ) {
		return false;
	}

	foreach ( $items as $item ) {
		if ( $item === '.' || $item === '..' ) {
			continue;
		}
		$source_path = trailingslashit( $source_dir ) . $item;
		$target_path = trailingslashit( $target_dir ) . $item;

		if ( is_dir( $source_path ) ) {
			if ( ! xpressui_copy_directory_recursive( $source_path, $target_path ) ) {
				return false;
			}
			continue;
		}

		if ( ! copy( $source_path, $target_path ) ) {
			return false;
		}
	}

	return true;
}

function xpressui_install_bundled_workflows() {
	$base_dir = xpressui_get_workflows_base_dir();
	if ( $base_dir === '' ) {
		return [];
	}

	if ( ! file_exists( $base_dir ) ) {
		wp_mkdir_p( $base_dir );
	}

	$installed = [];
	foreach ( xpressui_get_bundled_workflow_slugs() as $slug ) {
		if ( xpressui_is_installed_workflow( $slug ) ) {
			continue;
		}

		$bundled_dir = xpressui_get_bundled_workflow_source_dir( $slug );
		if ( '' === $bundled_dir ) {
			continue;
		}

		$source_dir = trailingslashit( $bundled_dir ) . $slug;
		$target_dir = trailingslashit( $base_dir ) . $slug;
		// Copy merges into existing directory, so a partial install gets repaired.
		if ( ! xpressui_copy_directory_recursive( $source_dir, $target_dir ) ) {
			continue;
		}

		$manifest = xpressui_load_workflow_manifest( $slug );
		if ( ! empty( $manifest ) ) {
			xpressui_store_workflow_manifest_meta( $slug, $manifest );
		}
		$installed[] = $slug;
	}

	return $installed;
}

function xpressui_reinstall_bundled_workflow( $slug ) {
	$slug        = sanitize_title( (string) $slug );
	$bundled_dir = xpressui_get_bundled_workflow_source_dir( $slug );
	$base_dir    = xpressui_get_workflows_base_dir();
	if ( $slug === '' || ! xpressui_is_bundled_workflow( $slug ) || $base_dir === '' ) {
		return new WP_Error( 'invalid_bundled_workflow', __( 'This bundled workflow could not be found.', 'xpressui-bridge' ) );
	}

	$source_dir = trailingslashit( $bundled_dir ) . $slug;
	$target_dir = trailingslashit( $base_dir ) . $slug;

	if ( file_exists( $target_dir ) ) {
		xpressui_delete_workflow(
			$slug,
			[
				'preserve_project_settings' => true,
				'mark_user_deleted'         => false,
			]
		);
	}

	if ( ! xpressui_copy_directory_recursive( $source_dir, $target_dir ) ) {
		return new WP_Error( 'bundled_reinstall_failed', __( 'The bundled workflow could not be installed.', 'xpressui-bridge' ) );
	}

	$manifest = xpressui_load_workflow_manifest( $slug );
	if ( ! empty( $manifest ) ) {
		xpressui_store_workflow_manifest_meta( $slug, $manifest );
	}

	$installed_registry          = get_option( 'xpressui_bundled_workflows_installed', [] );
	$installed_registry          = is_array( $installed_registry ) ? $installed_registry : [];
	$installed_registry[ $slug ] = current_time( 'mysql' );
	update_option( 'xpressui_bundled_workflows_installed', $installed_registry, false );

	return true;
}

function xpressui_is_bundled_workflow_update_available( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug || ! xpressui_is_bundled_workflow( $slug ) || ! xpressui_is_installed_workflow( $slug ) ) {
		return false;
	}

	$installed_meta       = xpressui_get_workflow_manifest_meta( $slug );
	$bundled_manifest     = xpressui_load_bundled_workflow_manifest( $slug );
	$bundled_fingerprint  = xpressui_get_manifest_fingerprint( $bundled_manifest );
	$installed_fingerprint = sanitize_text_field( (string) ( $installed_meta['manifestFingerprint'] ?? '' ) );

	if ( '' === $bundled_fingerprint || '' === $installed_fingerprint ) {
		return false;
	}

	return $bundled_fingerprint !== $installed_fingerprint;
}

function xpressui_delete_directory_recursive( $dir ) {
	$dir = rtrim( (string) $dir, '/\\' );
	if ( ! is_dir( $dir ) ) {
		return true;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();

	global $wp_filesystem;
	if ( ! $wp_filesystem ) {
		return false;
	}

	return (bool) $wp_filesystem->delete( $dir, true, 'd' );
}

function xpressui_delete_workflow( $slug, array $options = [] ) {
	$slug     = sanitize_title( (string) $slug );
	$base_dir = xpressui_get_workflows_base_dir();
	$preserve_project_settings = ! empty( $options['preserve_project_settings'] );
	$mark_user_deleted         = ! array_key_exists( 'mark_user_deleted', $options ) || ! empty( $options['mark_user_deleted'] );
	if ( $slug === '' || $base_dir === '' ) {
		return new WP_Error( 'invalid_workflow_slug', __( 'Invalid workflow slug.', 'xpressui-bridge' ) );
	}

	$target_dir = trailingslashit( $base_dir ) . $slug;
	if ( ! file_exists( $target_dir ) ) {
		return new WP_Error( 'missing_workflow', __( 'The workflow could not be found.', 'xpressui-bridge' ) );
	}

	if ( ! xpressui_delete_directory_recursive( $target_dir ) ) {
		return new WP_Error( 'delete_failed', __( 'The workflow directory could not be deleted.', 'xpressui-bridge' ) );
	}

	xpressui_delete_workflow_manifest_meta( $slug );

	$all_settings = get_option( 'xpressui_project_settings', [] );
	if ( ! $preserve_project_settings && is_array( $all_settings ) && isset( $all_settings[ $slug ] ) ) {
		unset( $all_settings[ $slug ] );
		update_option( 'xpressui_project_settings', $all_settings );
	}

	$installed_registry = get_option( 'xpressui_bundled_workflows_installed', [] );
	if ( is_array( $installed_registry ) && isset( $installed_registry[ $slug ] ) ) {
		unset( $installed_registry[ $slug ] );
		update_option( 'xpressui_bundled_workflows_installed', $installed_registry, false );
	}

	// Remember that the user explicitly deleted this bundled workflow so it is
	// not silently reinstalled on the next admin_init call.
	if ( $mark_user_deleted && xpressui_is_bundled_workflow( $slug ) ) {
		$user_deleted = get_option( 'xpressui_user_deleted_workflows', [] );
		if ( ! is_array( $user_deleted ) ) {
			$user_deleted = [];
		}
		$user_deleted[ $slug ] = current_time( 'mysql' );
		update_option( 'xpressui_user_deleted_workflows', $user_deleted, false );
	}

	return true;
}

function xpressui_maybe_install_bundled_workflows() {
	$current_version  = defined( 'XPRESSUI_BRIDGE_VERSION' ) ? XPRESSUI_BRIDGE_VERSION : '';
	$installed_version = get_option( 'xpressui_bundled_workflows_version', '' );
	$version_changed  = ( $current_version !== '' && $installed_version !== $current_version );

	$installed_registry = get_option( 'xpressui_bundled_workflows_installed', [] );
	if ( ! is_array( $installed_registry ) ) {
		$installed_registry = [];
	}

	$bundled_slugs = xpressui_get_bundled_workflow_slugs();

	$user_deleted = get_option( 'xpressui_user_deleted_workflows', [] );
	if ( ! is_array( $user_deleted ) ) {
		$user_deleted = [];
	}

	// On plugin update, force-reinstall all bundled workflows so generated artifacts
	// (e.g. template.context.json) are always up to date with the installed plugin version.
	// Also clears the user-deleted list so updated content is not suppressed.
	if ( $version_changed ) {
		foreach ( $bundled_slugs as $slug ) {
			xpressui_reinstall_bundled_workflow( $slug );
			$installed_registry[ $slug ] = current_time( 'mysql' );
		}
		update_option( 'xpressui_bundled_workflows_installed', $installed_registry, false );
		update_option( 'xpressui_bundled_workflows_version', $current_version, false );
		update_option( 'xpressui_user_deleted_workflows', [], false );
		return;
	}

	// Reinstall if: never registered OR artifacts missing — but NOT if the user
	// explicitly deleted it (respect the user's choice until the next plugin update).
	$needs_install = array_values(
		array_filter(
			$bundled_slugs,
			function ( $slug ) use ( $installed_registry, $user_deleted ) {
				if ( isset( $user_deleted[ $slug ] ) ) {
					return false;
				}
				return ! array_key_exists( $slug, $installed_registry )
					|| ! xpressui_is_installed_workflow( $slug );
			}
		)
	);

	if ( empty( $needs_install ) ) {
		return;
	}

	$newly_installed = xpressui_install_bundled_workflows();
	if ( empty( $newly_installed ) ) {
		return;
	}

	foreach ( $newly_installed as $slug ) {
		$installed_registry[ $slug ] = current_time( 'mysql' );
	}
	update_option( 'xpressui_bundled_workflows_installed', $installed_registry, false );
	update_option( 'xpressui_bundled_workflows_version', $current_version, false );
}

// ---------------------------------------------------------------------------
// Resume token helpers
// ---------------------------------------------------------------------------

function xpressui_generate_resume_token( $post_id ) {
	$token = bin2hex( random_bytes( 32 ) ); // 64 hex chars
	update_post_meta( $post_id, '_xpressui_resume_token', $token );
	update_post_meta( $post_id, '_xpressui_resume_token_expires', time() + 7 * DAY_IN_SECONDS );
	return $token;
}

/**
 * Default additional file slot definitions for the base bridge.
 *
 * @return array<int,array{id:string,label:string}>
 */
function xpressui_get_default_additional_file_slots() {
	return [
		[
			'id'    => 'xpressui_afile',
			'label' => '',
		],
	];
}

/**
 * Normalize additional file slot definitions.
 *
 * @param mixed $slots
 * @return array<int,array{id:string,label:string}>
 */
function xpressui_sanitize_additional_file_slots( $slots ) {
	$raw_slots   = is_array( $slots ) ? array_values( $slots ) : [];
	$clean_slots = [];
	$seen_ids    = [];

	foreach ( $raw_slots as $index => $slot ) {
		if ( ! is_array( $slot ) ) {
			continue;
		}

		$slot_id = sanitize_key( (string) ( $slot['id'] ?? '' ) );
		if ( '' === $slot_id ) {
			$slot_id = 0 === (int) $index ? 'xpressui_afile' : 'xpressui_afile_' . (int) $index;
		}
		if ( isset( $seen_ids[ $slot_id ] ) ) {
			continue;
		}
		$seen_ids[ $slot_id ] = true;

		$clean_slots[] = [
			'id'    => $slot_id,
			'label' => sanitize_text_field( (string) ( $slot['label'] ?? '' ) ),
		];
	}

	if ( empty( $clean_slots ) ) {
		return xpressui_get_default_additional_file_slots();
	}

	return $clean_slots;
}

/**
 * Resolve additional file slots for a workflow, allowing extensions to override them.
 *
 * @param string $project_slug
 * @return array<int,array{id:string,label:string}>
 */
function xpressui_get_additional_file_slots( $project_slug ) {
	$project_slug = sanitize_title( (string) $project_slug );
	$slots        = apply_filters( 'xpressui_additional_file_slots', xpressui_get_default_additional_file_slots(), $project_slug );

	return xpressui_sanitize_additional_file_slots( $slots );
}

/**
 * Ensure rendered_form always carries the additional file slot definitions.
 *
 * @param array<string,mixed> $template_context
 * @param string              $project_slug
 * @return array<string,mixed>
 */
function xpressui_apply_additional_file_slots_to_template_context( array $template_context, string $project_slug ): array {
	if ( ! isset( $template_context['rendered_form'] ) || ! is_array( $template_context['rendered_form'] ) ) {
		return $template_context;
	}

	$template_context['rendered_form']['additional_file_slots'] = xpressui_get_additional_file_slots( $project_slug );

	return $template_context;
}

function xpressui_get_resume_post_id_by_token( $token ) {
	$token = (string) $token;
	if ( strlen( $token ) !== 64 || ! ctype_xdigit( $token ) ) {
		return 0;
	}
	global $wpdb;
	$post_id = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_xpressui_resume_token' AND meta_value = %s LIMIT 1",
		$token
	) );
	if ( $post_id <= 0 ) {
		return 0;
	}
	$expires = (int) get_post_meta( $post_id, '_xpressui_resume_token_expires', true );
	if ( $expires > 0 && $expires < time() ) {
		delete_post_meta( $post_id, '_xpressui_resume_token' );
		delete_post_meta( $post_id, '_xpressui_resume_token_expires' );
		return 0;
	}
	return $post_id;
}

function xpressui_invalidate_resume_token( $post_id ) {
	delete_post_meta( $post_id, '_xpressui_resume_token' );
	delete_post_meta( $post_id, '_xpressui_resume_token_expires' );
}

function xpressui_get_project_form_url( $project_slug ) {
	$explicit = xpressui_get_project_setting( $project_slug, 'resumeUrl' );
	if ( $explicit !== '' ) {
		return $explicit;
	}
	// Auto-detect the published page/post that embeds this project's shortcode.
	global $wpdb;
	$slug_escaped = $wpdb->esc_like( $project_slug );
	$page_id = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT ID FROM {$wpdb->posts}
		WHERE post_status = 'publish'
		AND ( post_content LIKE %s OR post_content LIKE %s )
		LIMIT 1",
		'%[xpressui id="' . $slug_escaped . '"%]%',
		"%[xpressui id='" . $slug_escaped . "'%]%"
	) );
	if ( $page_id > 0 ) {
		return (string) get_permalink( $page_id );
	}
	return '';
}

function xpressui_build_resume_url( $post_id, $token ) {
	if ( $token === '' ) {
		return '';
	}
	$project_slug = (string) get_post_meta( $post_id, '_xpressui_project_slug', true );
	$base_url     = xpressui_get_project_form_url( $project_slug );
	if ( $base_url === '' ) {
		return '';
	}
	return add_query_arg( 'xpressui_resume', rawurlencode( $token ), $base_url );
}

function xpressui_get_flagged_fields( $post_id ) {
	$raw     = (string) get_post_meta( $post_id, '_xpressui_flagged_fields', true );
	$decoded = $raw !== '' ? json_decode( $raw, true ) : null;
	return is_array( $decoded ) ? array_values( array_filter( $decoded, 'is_string' ) ) : [];
}

/**
 * Returns the additional file request for this submission.
 * active/label/mode come from workflow-level settings; ref_file_id is per-submission.
 *
 * @param int $post_id
 * @return array{active:bool,label:string,ref_file_id:int}
 */
function xpressui_get_additional_file_request( $post_id ) {
	$project_slug = (string) get_post_meta( $post_id, '_xpressui_project_slug', true );
	$all_settings = get_option( 'xpressui_project_settings', [] );
	$s            = is_array( $all_settings[ $project_slug ] ?? null ) ? $all_settings[ $project_slug ] : [];

	// ref_file_id: new per-submission meta, with fallback to legacy JSON.
	$ref_file_id = (int) get_post_meta( $post_id, '_xpressui_afile_ref_file_id', true );
	if ( $ref_file_id === 0 ) {
		$raw     = (string) get_post_meta( $post_id, '_xpressui_additional_file_request', true );
		$decoded = $raw !== '' ? json_decode( $raw, true ) : null;
		if ( is_array( $decoded ) ) {
			$ref_file_id = (int) ( $decoded['ref_file_id'] ?? 0 );
		}
	}

	$request = [
		'active'      => ! empty( $s['additionalFileActive'] ),
		'label'       => (string) ( $s['additionalFileLabel'] ?? '' ),
		'ref_file_id' => $ref_file_id,
	];

	return apply_filters( 'xpressui_additional_file_request', $request, $post_id, $project_slug );
}

/**
 * Returns the operator-selected informational file for done notifications.
 * This is distinct from submitter uploads and from the pending_info reference file.
 *
 * @param int $post_id
 * @return int
 */
function xpressui_get_done_info_file_id( $post_id ) {
	return (int) get_post_meta( $post_id, '_xpressui_done_info_file_id', true );
}

/**
 * Resolve the post meta suffix for an additional slot.
 *
 * @param string $slot_id
 * @return string
 */
function xpressui_get_additional_file_slot_meta_suffix( string $slot_id ): string {
	$slot_id = sanitize_key( $slot_id );

	return 'xpressui_afile' === $slot_id ? '' : '_' . $slot_id;
}

/**
 * Returns the operator-selected pending_info reference file ID for a slot.
 *
 * @param int    $post_id
 * @param string $slot_id
 * @return int
 */
function xpressui_get_additional_file_ref_file_id( $post_id, string $slot_id ): int {
	$meta_key = '_xpressui_afile_ref_file_id' . xpressui_get_additional_file_slot_meta_suffix( $slot_id );

	return (int) get_post_meta( $post_id, $meta_key, true );
}

/**
 * Returns the operator-selected done informational file ID for a slot.
 *
 * @param int    $post_id
 * @param string $slot_id
 * @return int
 */
function xpressui_get_additional_file_done_info_file_id( $post_id, string $slot_id ): int {
	$meta_key = '_xpressui_done_info_file_id' . xpressui_get_additional_file_slot_meta_suffix( $slot_id );

	return (int) get_post_meta( $post_id, $meta_key, true );
}

/**
 * Returns whether an additional slot is active for a pending_info resubmission.
 *
 * @param int    $post_id
 * @param string $slot_id
 * @return bool
 */
function xpressui_is_additional_file_slot_active( $post_id, string $slot_id ): bool {
	$slot_id = sanitize_key( $slot_id );
	if ( 'xpressui_afile' === $slot_id ) {
		$request = xpressui_get_additional_file_request( $post_id );
		return ! empty( $request['active'] );
	}

	$meta_key = '_xpressui_afile_active_' . $slot_id;
	return ! empty( get_post_meta( $post_id, $meta_key, true ) );
}

/**
 * Resolve all additional-file slot payloads exposed to resume mode.
 *
 * @param int $post_id
 * @return array<int,array{id:string,label:string,active:bool,refFile:?array{url:string,name:string}}>
 */
function xpressui_get_resume_additional_files( $post_id ) {
	$project_slug      = (string) get_post_meta( $post_id, '_xpressui_project_slug', true );
	$slot_definitions  = xpressui_get_additional_file_slots( $project_slug );
	$base_request      = xpressui_get_additional_file_request( $post_id );
	$additional_files  = [];

	foreach ( $slot_definitions as $index => $slot ) {
		$slot_id    = sanitize_key( (string) ( $slot['id'] ?? '' ) );
		$slot_label = sanitize_text_field( (string) ( $slot['label'] ?? '' ) );
		if ( '' === $slot_id ) {
			continue;
		}

		$ref_id   = xpressui_get_additional_file_ref_file_id( $post_id, $slot_id );
		$ref_url  = $ref_id > 0 ? (string) wp_get_attachment_url( $ref_id ) : '';
		$ref_path = $ref_id > 0 ? (string) get_attached_file( $ref_id ) : '';
		$ref_name = $ref_path !== '' ? basename( $ref_path ) : ( $ref_id > 0 ? (string) get_the_title( $ref_id ) : '' );

		$additional_files[] = [
			'id'      => $slot_id,
			'label'   => 0 === (int) $index && ! empty( $base_request['label'] ) ? (string) $base_request['label'] : $slot_label,
			'active'  => xpressui_is_additional_file_slot_active( $post_id, $slot_id ),
			'refFile' => $ref_url !== '' ? [ 'url' => $ref_url, 'name' => $ref_name ] : null,
		];
	}

	$additional_files = apply_filters( 'xpressui_resume_additional_files', $additional_files, $post_id, $project_slug );

	return xpressui_sanitize_resume_additional_files( $additional_files );
}

/**
 * Normalize resume additional-file payloads after extension filters.
 *
 * @param mixed $additional_files
 * @return array<int,array{id:string,label:string,active:bool,refFile:?array{url:string,name:string}}>
 */
function xpressui_sanitize_resume_additional_files( $additional_files ) {
	$items    = is_array( $additional_files ) ? array_values( $additional_files ) : [];
	$cleaned  = [];
	$seen_ids = [];

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$id = sanitize_key( (string) ( $item['id'] ?? '' ) );
		if ( '' === $id || isset( $seen_ids[ $id ] ) ) {
			continue;
		}
		$seen_ids[ $id ] = true;

		$ref_file = null;
		if ( is_array( $item['refFile'] ?? null ) ) {
			$ref_url  = esc_url_raw( (string) ( $item['refFile']['url'] ?? '' ) );
			$ref_name = sanitize_text_field( (string) ( $item['refFile']['name'] ?? '' ) );
			if ( '' !== $ref_url ) {
				$ref_file = [
					'url'  => $ref_url,
					'name' => $ref_name,
				];
			}
		}

		$cleaned[] = [
			'id'      => $id,
			'label'   => sanitize_text_field( (string) ( $item['label'] ?? '' ) ),
			'active'  => ! empty( $item['active'] ),
			'refFile' => $ref_file,
		];
	}

	return $cleaned;
}

/**
 * Resolve the operator-selected informational documents for a done notification.
 *
 * @param int $post_id
 * @return array<int,array{url:string,name:string}>
 */
function xpressui_get_done_reference_files( $post_id ) {
	$project_slug     = (string) get_post_meta( $post_id, '_xpressui_project_slug', true );
	$done_info_file_id = xpressui_get_done_info_file_id( $post_id );
	$reference_files   = [];

	if ( $done_info_file_id > 0 ) {
		$ref_url  = (string) wp_get_attachment_url( $done_info_file_id );
		$ref_path = (string) get_attached_file( $done_info_file_id );
		$ref_name = $ref_path !== '' ? basename( $ref_path ) : (string) get_the_title( $done_info_file_id );
		if ( '' !== $ref_url ) {
			$reference_files[] = [
				'url'  => $ref_url,
				'name' => $ref_name,
			];
		}
	}

	$reference_files = apply_filters( 'xpressui_done_reference_files', $reference_files, $post_id, $project_slug );

	return is_array( $reference_files ) ? array_values( $reference_files ) : [];
}

/**
 * Returns stored reference file attachment IDs keyed by field name.
 * Format: [ 'fieldName' => attachmentId (int) ]
 *
 * @param int $post_id Submission post ID.
 * @return array<string,int>
 */
function xpressui_get_field_reference_files( $post_id ) {
	$raw     = (string) get_post_meta( $post_id, '_xpressui_field_reference_files', true );
	$decoded = $raw !== '' ? json_decode( $raw, true ) : null;
	if ( ! is_array( $decoded ) ) {
		return [];
	}
	$result = [];
	foreach ( $decoded as $field_name => $attachment_id ) {
		$id = (int) $attachment_id;
		if ( is_string( $field_name ) && $field_name !== '' && $id > 0 ) {
			$result[ $field_name ] = $id;
		}
	}
	return $result;
}

/**
 * Resolves reference file attachment IDs to [ 'url' => ..., 'name' => ... ] for API output.
 *
 * @param int $post_id
 * @return array<string,array{url:string,name:string}>
 */
function xpressui_resolve_field_reference_files( $post_id ) {
	$raw = xpressui_get_field_reference_files( $post_id );
	$out = [];
	foreach ( $raw as $field_name => $attachment_id ) {
		$url  = (string) wp_get_attachment_url( $attachment_id );
		$path = (string) get_attached_file( $attachment_id );
		$name = $path !== '' ? basename( $path ) : (string) get_the_title( $attachment_id );
		if ( $url !== '' ) {
			$out[ $field_name ] = [ 'url' => $url, 'name' => $name ];
		}
	}
	return $out;
}

// ---------------------------------------------------------------------------
// Status helpers
// ---------------------------------------------------------------------------

function xpressui_get_status_options() {
	return [
		'new'          => __( 'New', 'xpressui-bridge' ),
		'in-review'    => __( 'In review', 'xpressui-bridge' ),
		'pending_info' => __( 'Pending info', 'xpressui-bridge' ),
		'done'         => __( 'Done', 'xpressui-bridge' ),
		'rejected'     => __( 'Rejected', 'xpressui-bridge' ),
	];
}

function xpressui_get_current_runtime_tier() {
	return 'light';
}

function xpressui_runtime_supports_workflow( $required_tier = 'light' ) {
	$required_tier = sanitize_key( (string) $required_tier );

	return 'pro' !== $required_tier;
}

function xpressui_get_runtime_health_summary() {
	$bridge_runtime_name = defined( 'XPRESSUI_BRIDGE_RUNTIME_VERSION' )
		? 'xpressui-light-' . XPRESSUI_BRIDGE_RUNTIME_VERSION . '.umd.js'
		: '';
	$bridge_runtime_path = $bridge_runtime_name !== ''
		? XPRESSUI_BRIDGE_DIR . 'runtime/' . $bridge_runtime_name
		: '';
	$bridge_runtime_url  = $bridge_runtime_name !== ''
		? XPRESSUI_BRIDGE_URL . 'runtime/' . $bridge_runtime_name
		: '';

	$pro_runtime_name = ( defined( 'XPRESSUI_PRO_RUNTIME_VERSION' ) && defined( 'XPRESSUI_PRO_DIR' ) )
		? 'xpressui-' . XPRESSUI_PRO_RUNTIME_VERSION . '.umd.js'
		: '';
	$pro_runtime_path = ( $pro_runtime_name !== '' && defined( 'XPRESSUI_PRO_DIR' ) )
		? XPRESSUI_PRO_DIR . 'runtime/' . $pro_runtime_name
		: '';
	$pro_runtime_url  = ( $pro_runtime_name !== '' && defined( 'XPRESSUI_PRO_DIR' ) )
		? plugin_dir_url( XPRESSUI_PRO_DIR . 'xpressui-wordpress-bridge-pro.php' ) . 'runtime/' . $pro_runtime_name
		: '';

	$current_tier          = xpressui_get_current_runtime_tier();
	$active_runtime_source = 'pro' === $current_tier && $pro_runtime_url !== '' ? 'plugin-pro' : 'plugin-bridge';
	$active_runtime_url    = 'plugin-pro' === $active_runtime_source ? $pro_runtime_url : $bridge_runtime_url;

	return [
		'currentTier'        => $current_tier,
		'activeRuntimeSource' => $active_runtime_source,
		'activeRuntimeUrl'   => $active_runtime_url,
		'bridge'             => [
			'name'   => $bridge_runtime_name,
			'path'   => $bridge_runtime_path,
			'url'    => $bridge_runtime_url,
			'exists' => $bridge_runtime_path !== '' && file_exists( $bridge_runtime_path ),
		],
		'pro'                => [
			'name'      => $pro_runtime_name,
			'path'      => $pro_runtime_path,
			'url'       => $pro_runtime_url,
			'exists'    => $pro_runtime_path !== '' && file_exists( $pro_runtime_path ),
			'available' => defined( 'XPRESSUI_PRO_RUNTIME_VERSION' ) && defined( 'XPRESSUI_PRO_DIR' ),
		],
	];
}

function xpressui_get_workflow_page_ids( $slug ) {
	global $wpdb;

	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug ) {
		return [];
	}

	// Use a direct LIKE query instead of WP_Query 's' parameter.
	// WP_Query tokenises the search string via wp_parse_search_terms(), which
	// misinterprets the quotes in [xpressui id="slug"] and returns no results.
	$like = '%' . $wpdb->esc_like( '[xpressui id="' . $slug . '"]' ) . '%';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- precise shortcode search requires a targeted LIKE query and returns lightweight IDs only.
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type = 'page'
			   AND post_status IN ('draft','publish','pending','private')
			   AND post_content LIKE %s
			 ORDER BY post_date ASC",
			$like
		)
	);

	return array_map( 'intval', $ids ?: [] );
}

function xpressui_get_workflow_primary_page_id( $slug ) {
	$page_ids = xpressui_get_workflow_page_ids( $slug );
	return ! empty( $page_ids ) ? (int) $page_ids[0] : 0;
}

function xpressui_get_status_label( $status ) {
	$options = xpressui_get_status_options();
	return $options[ $status ] ?? $options['new'];
}

// ---------------------------------------------------------------------------
// Assignee helpers
// ---------------------------------------------------------------------------

function xpressui_get_assignable_users() {
	$users = get_users( [
		'orderby' => 'display_name',
		'order'   => 'ASC',
		'fields'  => [ 'ID', 'display_name', 'user_login' ],
	] );
	return is_array( $users ) ? $users : [];
}

function xpressui_get_assignee_display( $post_id ) {
	$assignee_id = (int) get_post_meta( $post_id, '_xpressui_assignee_id', true );
	if ( $assignee_id <= 0 ) {
		return '';
	}
	$user = get_user_by( 'id', $assignee_id );
	if ( ! $user ) {
		return '';
	}
	return (string) ( $user->display_name ?: $user->user_login ?: '' );
}

function xpressui_set_assignee( $post_id, $assignee_id ) {
	$assignee_id = (int) $assignee_id;
	if ( $assignee_id > 0 ) {
		update_post_meta( $post_id, '_xpressui_assignee_id', $assignee_id );
		return;
	}
	delete_post_meta( $post_id, '_xpressui_assignee_id' );
}

// ---------------------------------------------------------------------------
// Payload helpers
// ---------------------------------------------------------------------------

function xpressui_get_submission_payload( $post_id ) {
	$json    = get_post_meta( $post_id, '_xpressui_payload_json', true );
	$payload = $json ? json_decode( $json, true ) : [];
	return is_array( $payload ) ? $payload : [];
}

function xpressui_get_contact_summary( $payload ) {
	if ( ! is_array( $payload ) ) {
		return '';
	}
	$full_name  = trim( (string) ( $payload['fullName'] ?? '' ) );
	$first_name = trim( (string) ( $payload['firstName'] ?? $payload['firstname'] ?? '' ) );
	$last_name  = trim( (string) ( $payload['lastName'] ?? $payload['lastname'] ?? '' ) );
	$email      = trim( (string) ( $payload['email'] ?? '' ) );
	$phone      = trim( (string) ( $payload['phone'] ?? $payload['phoneNumber'] ?? '' ) );

	if ( $full_name !== '' ) {
		return $full_name;
	}
	if ( $first_name !== '' || $last_name !== '' ) {
		return trim( $first_name . ' ' . $last_name );
	}
	if ( $email !== '' ) {
		return $email;
	}
	if ( $phone !== '' ) {
		return $phone;
	}
	return '';
}

function xpressui_get_uploaded_file_count( $post_id ) {
	$json  = get_post_meta( $post_id, '_xpressui_uploaded_files', true );
	$files = $json ? json_decode( $json, true ) : [];
	return is_array( $files ) ? count( $files ) : 0;
}

function xpressui_get_uploaded_files( $post_id ) {
	$json  = get_post_meta( $post_id, '_xpressui_uploaded_files', true );
	$files = $json ? json_decode( (string) $json, true ) : [];
	return is_array( $files ) ? $files : [];
}

function xpressui_delete_submission_attachments( $post_id ) {
	$stored_files = xpressui_get_uploaded_files( $post_id );
	if ( empty( $stored_files ) ) {
		return;
	}

	foreach ( $stored_files as $file ) {
		$attachment_id = isset( $file['attachmentId'] ) ? (int) $file['attachmentId'] : 0;
		if ( $attachment_id > 0 ) {
			wp_delete_attachment( $attachment_id, true );
		}
	}
}

// ---------------------------------------------------------------------------
// Config snapshot helpers
// ---------------------------------------------------------------------------

function xpressui_normalize_config_snapshot( $raw ) {
	if ( is_string( $raw ) && trim( $raw ) !== '' ) {
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}
	}
	return is_array( $raw ) ? $raw : [];
}

function xpressui_store_config_snapshot( $project_id, $project_slug, $config_version, $config ) {
	$normalized = xpressui_normalize_config_snapshot( $config );
	if ( empty( $normalized ) ) {
		return [];
	}
	$registry = get_option( 'xpressui_project_config_registry', [] );
	if ( ! is_array( $registry ) ) {
		$registry = [];
	}
	$key = $config_version !== ''
		? 'config:' . $config_version
		: ( $project_id !== '' ? 'project:' . $project_id : 'slug:' . $project_slug );

	$registry[ $key ] = [
		'projectId'            => $project_id,
		'projectSlug'          => $project_slug,
		'projectConfigVersion' => $config_version,
		'storedAt'             => current_time( 'mysql' ),
		'config'               => $normalized,
	];
	update_option( 'xpressui_project_config_registry', $registry, false );
	return $normalized;
}

function xpressui_get_config_snapshot( $post_id ) {
	$json = get_post_meta( $post_id, '_xpressui_project_config_json', true );
	if ( is_string( $json ) && trim( $json ) !== '' ) {
		$stored = json_decode( $json, true );
		if ( is_array( $stored ) ) {
			return $stored;
		}
	}
	$registry       = get_option( 'xpressui_project_config_registry', [] );
	$project_id     = (string) get_post_meta( $post_id, '_xpressui_project_id', true );
	$project_slug   = (string) get_post_meta( $post_id, '_xpressui_project_slug', true );
	$config_version = (string) get_post_meta( $post_id, '_xpressui_project_config_version', true );

	$key   = $config_version !== ''
		? 'config:' . $config_version
		: ( $project_id !== '' ? 'project:' . $project_id : 'slug:' . $project_slug );
	$entry = is_array( $registry ) ? ( $registry[ $key ] ?? null ) : null;
	$config = is_array( $entry['config'] ?? null ) ? $entry['config'] : [];
	if ( ! empty( $config ) ) {
		return $config;
	}

	if ( $project_slug !== '' && xpressui_is_installed_workflow( $project_slug ) ) {
		$raw_form_config_json = xpressui_get_workflow_artifact_contents( $project_slug, 'config', 'form.config.json' );
		if ( is_string( $raw_form_config_json ) && trim( $raw_form_config_json ) !== '' ) {
			$live_config = json_decode( $raw_form_config_json, true );
			if ( is_array( $live_config ) ) {
				return xpressui_normalize_form_config( $live_config, $project_slug );
			}
		}
	}

	return [];
}

// ---------------------------------------------------------------------------
// Field index helpers
// ---------------------------------------------------------------------------

function xpressui_build_field_choice_map( $field ) {
	$choice_map = [];
	$choices    = is_array( $field['choices'] ?? null ) ? $field['choices'] : [];
	foreach ( $choices as $choice ) {
		if ( ! is_array( $choice ) ) {
			continue;
		}
		$label = (string) ( $choice['label'] ?? $choice['name'] ?? $choice['value'] ?? '' );
		foreach ( [ 'value', 'name', 'label' ] as $choice_key ) {
			$raw = $choice[ $choice_key ] ?? null;
			if ( $raw === null || $raw === '' ) {
				continue;
			}
			$choice_map[ (string) $raw ] = $label !== '' ? $label : (string) $raw;
		}
	}
	return $choice_map;
}

function xpressui_build_config_field_index( $config ) {
	$index         = [];
	$sections      = is_array( $config['sections'] ?? null ) ? $config['sections'] : [];
	$step_sections = [];

	if ( is_array( $sections['custom'] ?? null ) ) {
		$step_sections = array_values( $sections['custom'] );
	} elseif ( is_array( $config['stepSections'] ?? null ) ) {
		$step_sections = array_values( $config['stepSections'] );
	}

	foreach ( $step_sections as $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}
		$section_name = (string) ( $section['name'] ?? '' );
		if ( $section_name === '' ) {
			continue;
		}
		$section_label = (string) ( $section['label'] ?? $section['adminLabel'] ?? $section['title'] ?? $section_name );
		$fields        = is_array( $sections[ $section_name ] ?? null ) ? $sections[ $section_name ] : [];

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$field_name = (string) ( $field['name'] ?? '' );
			if ( $field_name === '' ) {
				continue;
			}
			$index[ $field_name ] = [
				'label'         => (string) ( $field['label'] ?? $field['adminLabel'] ?? $field['title'] ?? $field_name ),
				'sectionLabel'  => $section_label,
				'type'          => (string) ( $field['type'] ?? '' ),
				'choices'       => xpressui_build_field_choice_map( $field ),
				'choiceCatalog' => is_array( $field['choices'] ?? null ) ? array_values( $field['choices'] ) : [],
			];
		}
	}

	// Fallback for snapshots that lost sections.custom/stepSections but still keep per-section field arrays.
	if ( empty( $index ) ) {
		foreach ( $sections as $section_name => $fields ) {
			if ( ! is_string( $section_name ) || in_array( $section_name, [ 'custom', 'btngroup' ], true ) || ! is_array( $fields ) ) {
				continue;
			}
			$section_label = $section_name;
			foreach ( $fields as $field ) {
				if ( ! is_array( $field ) ) {
					continue;
				}
				$field_name = (string) ( $field['name'] ?? '' );
				if ( $field_name === '' ) {
					continue;
				}
				$index[ $field_name ] = [
					'label'         => (string) ( $field['label'] ?? $field['adminLabel'] ?? $field['title'] ?? $field_name ),
					'sectionLabel'  => $section_label,
					'type'          => (string) ( $field['type'] ?? '' ),
					'choices'       => xpressui_build_field_choice_map( $field ),
					'choiceCatalog' => is_array( $field['choices'] ?? null ) ? array_values( $field['choices'] ) : [],
				];
			}
		}
	}

	return $index;
}

/**
 * Fills in technical defaults for form.config.json fields normally generated by the Console builder.
 * Lets manually authored packages work without knowing the internal conventions.
 *
 * Applied at render time only — the uploaded file is never modified.
 *
 * @param array  $form_config Parsed form.config.json.
 * @param string $slug        Workflow slug (used as fallback projectId / projectSlug).
 * @return array Normalised config.
 */
function xpressui_normalize_form_config( array $form_config, string $slug ): array {
	$sections_map  = is_array( $form_config['sections'] ?? null ) ? $form_config['sections'] : [];
	$step_count    = is_array( $sections_map['custom'] ?? null ) ? count( $sections_map['custom'] ) : 0;
	$multi_step    = $step_count > 1;

	// mode
	if ( empty( $form_config['mode'] ) ) {
		$form_config['mode'] = $multi_step ? 'form-multi-step' : 'form';
	}

	// workflowConfig — all keys filled individually so partial manual configs still work.
	if ( ! is_array( $form_config['workflowConfig'] ?? null ) ) {
		$form_config['workflowConfig'] = [];
	}
	$wc = &$form_config['workflowConfig'];
	if ( empty( $wc['providerMode'] ) ) {
		$wc['providerMode'] = 'wordpress-bridge';
	}
	if ( empty( $wc['submissionMode'] ) ) {
		$wc['submissionMode'] = $multi_step ? 'multi-step-submit' : 'single-step-submit';
	}
	if ( empty( $wc['submissionEndpoint'] ) ) {
		$wc['submissionEndpoint'] = '/wp-json/xpressui/v1/submit';
	}
	if ( ! isset( $wc['resumeSupport'] ) ) {
		$wc['resumeSupport'] = 'disabled';
	}
	if ( ! isset( $wc['documentHandling'] ) ) {
		$wc['documentHandling'] = 'basic-upload';
	}
	if ( empty( $wc['successMessage'] ) ) {
		$wc['successMessage'] = __( 'Your submission was received successfully.', 'xpressui-bridge' );
	}
	if ( empty( $wc['errorMessage'] ) ) {
		$wc['errorMessage'] = __( 'Unable to submit. Please try again.', 'xpressui-bridge' );
	}
	unset( $wc );

	// submit block — endpoint + metadata required for the REST submission to work.
	if ( ! is_array( $form_config['submit'] ?? null ) ) {
		$form_config['submit'] = [];
	}
	if ( empty( $form_config['submit']['endpoint'] ) ) {
		// The JS runtime resolves this placeholder to window.XPRESSUI_WORDPRESS_REST_URL.
		$form_config['submit']['endpoint'] = '__XPRESSUI_WORDPRESS_REST_URL__';
	}
	if ( empty( $form_config['submit']['method'] ) ) {
		$form_config['submit']['method'] = 'POST';
	}
	if ( empty( $form_config['submit']['mode'] ) ) {
		$form_config['submit']['mode'] = 'form-data';
	}
	if ( ! isset( $form_config['submit']['includeDocumentData'] ) ) {
		$form_config['submit']['includeDocumentData'] = true;
	}
	if ( ! is_array( $form_config['submit']['metadata'] ?? null ) ) {
		$form_config['submit']['metadata'] = [];
	}
	$meta = &$form_config['submit']['metadata'];
	if ( empty( $meta['projectSlug'] ) ) {
		$meta['projectSlug'] = $slug;
	}
	if ( empty( $meta['projectId'] ) ) {
		// Empty projectId means the REST endpoint skips the ID cross-check — safe default.
		$meta['projectId'] = $slug;
	}
	if ( ! isset( $meta['projectConfigVersion'] ) ) {
		$meta['projectConfigVersion'] = '';
	}
	unset( $meta );

	// submitFeedback defaults.
	if ( ! is_array( $form_config['submitFeedback'] ?? null ) ) {
		$form_config['submitFeedback'] = [];
	}
	$sf = &$form_config['submitFeedback'];
	if ( empty( $sf['title'] ) ) {
		$sf['title'] = __( 'Submission status', 'xpressui-bridge' );
	}
	if ( empty( $sf['loading_message'] ) ) {
		$sf['loading_message'] = __( 'Submitting…', 'xpressui-bridge' );
	}
	if ( empty( $sf['success_title'] ) ) {
		$sf['success_title'] = __( 'Submission received', 'xpressui-bridge' );
	}
	if ( empty( $sf['success_message'] ) ) {
		$sf['success_message'] = $form_config['workflowConfig']['successMessage']
			?? __( 'Your submission was received successfully.', 'xpressui-bridge' );
	}
	if ( empty( $sf['error_title'] ) ) {
		$sf['error_title'] = __( 'Submission failed', 'xpressui-bridge' );
	}
	if ( empty( $sf['error_message'] ) ) {
		$sf['error_message'] = $form_config['workflowConfig']['errorMessage']
			?? __( 'Unable to submit. Please try again.', 'xpressui-bridge' );
	}
	unset( $sf );

	return $form_config;
}

/**
 * Builds a minimal rendered_form array from a parsed form.config.json.
 * Used as a fallback when template.context.json does not contain rendered_form
 * (e.g. workflows exported without a full Console context).
 *
 * @param array $form_config Parsed form.config.json.
 * @return array rendered_form structure expected by the PHP templates.
 */
function xpressui_build_rendered_form_from_config( array $form_config ): array {
	$sections_map    = is_array( $form_config['sections'] ?? null ) ? $form_config['sections'] : [];
	$custom_sections = is_array( $sections_map['custom'] ?? null ) ? $sections_map['custom'] : [];
	$step_count      = count( $custom_sections );

	// Map form.config.json field types to HTML input types expected by the templates.
	$input_type_map = [
		'text'      => 'text',
		'email'     => 'email',
		'tel'       => 'tel',
		'url'       => 'url',
		'number'    => 'number',
		'price'     => 'number',
		'date'      => 'date',
		'time'      => 'time',
		'password'  => 'password',
		'file'      => 'file',
		'upload-image' => 'file',
	];

	$sections = [];
	foreach ( $custom_sections as $section_def ) {
		if ( ! is_array( $section_def ) ) {
			continue;
		}
		$section_name = (string) ( $section_def['name'] ?? '' );
		$raw_fields   = is_array( $sections_map[ $section_name ] ?? null ) ? array_values( $sections_map[ $section_name ] ) : [];

		// Normalize each field so templates never receive null for required attributes.
		$fields = [];
		foreach ( $raw_fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$type = (string) ( $field['type'] ?? 'text' );
			$field['input_type']  = $input_type_map[ $type ] ?? 'text';
			$field['placeholder'] = $field['placeholder'] ?? '';
			$field['desc']        = $field['desc'] ?? '';
			$field['helpText']    = $field['helpText'] ?? '';
			$field['accept']      = $field['accept'] ?? '';
			$field['capture']     = $field['capture'] ?? '';
			$fields[]             = $field;
		}

		$sections[] = [
			'name'   => $section_name,
			'label'  => (string) ( $section_def['label'] ?? '' ),
			'desc'   => (string) ( $section_def['desc'] ?? '' ),
			'fields' => $fields,
		];
	}

	$nav_labels = is_array( $form_config['navigationLabels'] ?? null ) ? $form_config['navigationLabels'] : [];

	return [
		'has_sections'         => ! empty( $sections ),
		'sections'             => $sections,
		'show_title'           => false,
		'show_subtitle'        => false,
		'show_section_headers' => $step_count > 1,
		'navigation_labels'    => [
			'previous' => (string) ( $nav_labels['prevLabel'] ?? 'Back' ),
			'next'     => (string) ( $nav_labels['nextLabel'] ?? 'Continue' ),
		],
		'submit_label'         => (string) ( $nav_labels['submitLabel'] ?? 'Submit' ),
		'submit_feedback'      => [
			'title'        => (string) ( $form_config['submitFeedback']['title'] ?? '' ),
			'idle_message' => (string) ( $form_config['submitFeedback']['idle_message'] ?? '' ),
		],
		'step_status'          => [
			'enabled'       => $step_count > 1,
			'current_index' => 1,
			'total'         => $step_count,
			'idle_message'  => '',
		],
	];
}

function xpressui_build_choice_catalog_index( $field_meta = [] ) {
	$catalog = is_array( $field_meta['choiceCatalog'] ?? null ) ? $field_meta['choiceCatalog'] : [];
	$index   = [];
	foreach ( $catalog as $pos => $choice ) {
		if ( ! is_array( $choice ) ) {
			continue;
		}
		$id = (string) ( $choice['value'] ?? $choice['id'] ?? ( 'choice_' . ( $pos + 1 ) ) );
		if ( $id === '' ) {
			continue;
		}
		$index[ $id ] = $choice;
	}
	return $index;
}

// ---------------------------------------------------------------------------
// Status & history helpers
// ---------------------------------------------------------------------------

function xpressui_get_status_history( $post_id ) {
	$json    = get_post_meta( $post_id, '_xpressui_status_history', true );
	$history = $json ? json_decode( $json, true ) : [];
	return is_array( $history ) ? $history : [];
}

function xpressui_append_status_history( $post_id, $status, $note = '' ) {
	$history = xpressui_get_status_history( $post_id );
	$user    = function_exists( 'wp_get_current_user' ) ? wp_get_current_user() : null;
	$actor   = ( $user && ! empty( $user->user_login ) ) ? (string) $user->user_login : 'system';
	$history[] = [
		'status' => $status,
		'note'   => trim( (string) $note ),
		'at'     => current_time( 'mysql' ),
		'actor'  => $actor,
	];
	update_post_meta( $post_id, '_xpressui_status_history', wp_json_encode( $history ) );
}

function xpressui_set_submission_status( $post_id, $status, $note = '' ) {
	$current_status = (string) get_post_meta( $post_id, '_xpressui_submission_status', true );
	$current_note   = (string) get_post_meta( $post_id, '_xpressui_review_note', true );
	$normalized_note = trim( (string) $note );

	update_post_meta( $post_id, '_xpressui_submission_status', $status );
	update_post_meta( $post_id, '_xpressui_review_note', $normalized_note );

	if ( $status === 'in-review' && get_post_meta( $post_id, '_xpressui_reviewed_at', true ) === '' ) {
		update_post_meta( $post_id, '_xpressui_reviewed_at', current_time( 'mysql' ) );
	}
	if ( $status === 'done' ) {
		if ( get_post_meta( $post_id, '_xpressui_reviewed_at', true ) === '' ) {
			update_post_meta( $post_id, '_xpressui_reviewed_at', current_time( 'mysql' ) );
		}
		update_post_meta( $post_id, '_xpressui_done_at', current_time( 'mysql' ) );
		if ( $current_status !== 'done' ) {
			xpressui_maybe_send_done_notification( $post_id, $normalized_note );
		}
	} elseif ( $current_status === 'done' && $status !== 'done' ) {
		delete_post_meta( $post_id, '_xpressui_done_at' );
	}
	if ( $status === 'pending_info' ) {
		update_post_meta( $post_id, '_xpressui_pending_info_at', current_time( 'mysql' ) );
		if ( $current_status !== 'pending_info' ) {
			xpressui_generate_resume_token( $post_id );
			xpressui_maybe_send_pending_info_notification( $post_id, $normalized_note );
		}
	} elseif ( $current_status === 'pending_info' && $status !== 'pending_info' ) {
		delete_post_meta( $post_id, '_xpressui_pending_info_at' );
		xpressui_invalidate_resume_token( $post_id );
	}
	if ( $status === 'rejected' ) {
		if ( get_post_meta( $post_id, '_xpressui_reviewed_at', true ) === '' ) {
			update_post_meta( $post_id, '_xpressui_reviewed_at', current_time( 'mysql' ) );
		}
		update_post_meta( $post_id, '_xpressui_rejected_at', current_time( 'mysql' ) );
		if ( $current_status !== 'rejected' ) {
			xpressui_maybe_send_rejected_notification( $post_id, $normalized_note );
		}
	} elseif ( $current_status === 'rejected' && $status !== 'rejected' ) {
		delete_post_meta( $post_id, '_xpressui_rejected_at' );
	}

	if ( $current_status !== $status || $current_note !== $normalized_note ) {
		xpressui_append_status_history( $post_id, $status, $normalized_note );
	}
}

// ---------------------------------------------------------------------------
// Capture summary helpers
// ---------------------------------------------------------------------------

function xpressui_get_section_capture_summary( $fields, $payload ) {
	$filled_count      = 0;
	$interactive_count = 0;
	$interactive_types = [ 'product-list', 'quiz', 'select-image', 'select-one', 'select-multiple', 'radio-buttons', 'checkboxes' ];

	foreach ( $fields as $field_name => $field_meta ) {
		$raw = $payload[ $field_name ] ?? null;
		if ( $raw !== null && $raw !== '' && $raw !== [] ) {
			$filled_count++;
		}
		if ( in_array( (string) ( $field_meta['type'] ?? '' ), $interactive_types, true ) ) {
			$interactive_count++;
		}
	}
	$field_count = count( $fields );
	$status      = 'empty';
	if ( $filled_count > 0 && $filled_count < $field_count ) {
		$status = 'partial';
	} elseif ( $field_count > 0 && $filled_count >= $field_count ) {
		$status = 'complete';
	}
	return [
		'filledCount'      => $filled_count,
		'fieldCount'       => $field_count,
		'interactiveCount' => $interactive_count,
		'status'           => $status,
	];
}

function xpressui_get_submission_capture_summary( $field_index, $payload ) {
	$sections = [];
	foreach ( $field_index as $field_name => $field_meta ) {
		if ( ! array_key_exists( $field_name, $payload ) ) {
			continue;
		}
		$section_label = (string) ( $field_meta['sectionLabel'] ?? 'Submission' );
		if ( ! isset( $sections[ $section_label ] ) ) {
			$sections[ $section_label ] = [];
		}
		$sections[ $section_label ][ $field_name ] = $field_meta;
	}

	$field_total       = 0;
	$field_filled      = 0;
	$interactive_total = 0;
	$complete_sections = 0;
	$partial_sections  = 0;
	$empty_sections    = 0;

	foreach ( $sections as $fields ) {
		$summary            = xpressui_get_section_capture_summary( $fields, $payload );
		$field_total       += (int) $summary['fieldCount'];
		$field_filled      += (int) $summary['filledCount'];
		$interactive_total += (int) $summary['interactiveCount'];
		if ( ( $summary['status'] ?? '' ) === 'complete' ) {
			$complete_sections++;
		} elseif ( ( $summary['status'] ?? '' ) === 'partial' ) {
			$partial_sections++;
		} else {
			$empty_sections++;
		}
	}

	return [
		'sectionCount'     => count( $sections ),
		'completeSections' => $complete_sections,
		'partialSections'  => $partial_sections,
		'emptySections'    => $empty_sections,
		'fieldCount'       => $field_total,
		'filledCount'      => $field_filled,
		'interactiveCount' => $interactive_total,
	];
}

// ---------------------------------------------------------------------------
// Submission title builder
// ---------------------------------------------------------------------------

function xpressui_build_submission_title( $project_slug, $submission_id, $payload ) {
	$summary    = '';
	if ( is_array( $payload ) ) {
		$full_name  = trim( (string) ( $payload['fullName'] ?? '' ) );
		$first_name = trim( (string) ( $payload['firstName'] ?? $payload['firstname'] ?? '' ) );
		$last_name  = trim( (string) ( $payload['lastName'] ?? $payload['lastname'] ?? '' ) );
		$email      = trim( (string) ( $payload['email'] ?? '' ) );
		$phone      = trim( (string) ( $payload['phone'] ?? $payload['phoneNumber'] ?? '' ) );

		if ( $full_name !== '' ) {
			$summary = $full_name;
		} elseif ( $first_name !== '' || $last_name !== '' ) {
			$summary = trim( $first_name . ' ' . $last_name );
		} elseif ( $email !== '' ) {
			$summary = $email;
		} elseif ( $phone !== '' ) {
			$summary = $phone;
		}
	}
	if ( $summary === '' ) {
		$summary = (string) ( $submission_id ?: uniqid( 'submission_', true ) );
	}
	return sprintf( '%s · %s · %s', (string) $project_slug, $summary, wp_date( 'Y-m-d H:i' ) );
}

// ---------------------------------------------------------------------------
// Value rendering helpers
// ---------------------------------------------------------------------------

function xpressui_render_scalar_badge( $label, $tone = 'neutral' ) {
	$classes = [
		'neutral' => 'xpressui-badge',
		'success' => 'xpressui-badge xpressui-badge--success',
		'muted'   => 'xpressui-badge xpressui-badge--muted',
	];
	$class = $classes[ $tone ] ?? $classes['neutral'];
	return '<span class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
}

function xpressui_format_money( $raw ) {
	if ( ! is_numeric( $raw ) ) {
		return '';
	}
	$amount = (float) $raw;
	return floor( $amount ) === $amount
		? (string) (int) $amount
		: number_format( $amount, 2, '.', ' ' );
}

function xpressui_render_product_list_value( $value, $field_meta = [] ) {
	if ( ! is_array( $value ) || empty( $value ) ) {
		return '<span class="xpressui-empty">' . esc_html__( 'Empty', 'xpressui-bridge' ) . '</span>';
	}
	$catalog_index  = xpressui_build_choice_catalog_index( $field_meta );
	$rows           = [];
	$total_quantity = 0;
	$total_amount   = 0.0;

	foreach ( $value as $pos => $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}
		$entry_id      = (string) ( $entry['id'] ?? $entry['value'] ?? ( 'item_' . ( $pos + 1 ) ) );
		$catalog_entry = $catalog_index[ $entry_id ] ?? [];
		$name          = (string) ( $entry['name'] ?? $entry['label'] ?? $catalog_entry['name'] ?? $catalog_entry['label'] ?? $entry_id );
		$quantity      = max( 1, (int) ( $entry['quantity'] ?? 1 ) );
		$unit_amount   = $entry['discount_price'] ?? $entry['sale_price'] ?? $catalog_entry['discount_price'] ?? $catalog_entry['discountPrice'] ?? $catalog_entry['sale_price'] ?? $catalog_entry['salePrice'] ?? null;
		$line_amount   = is_numeric( $unit_amount ) ? ( (float) $unit_amount * $quantity ) : null;

		$total_quantity += $quantity;
		if ( $line_amount !== null ) {
			$total_amount += $line_amount;
		}
		$rows[] = [
			'name'     => $name,
			'quantity' => $quantity,
			'unit'     => is_numeric( $unit_amount ) ? xpressui_format_money( $unit_amount ) : '',
			'line'     => $line_amount !== null ? xpressui_format_money( $line_amount ) : '',
		];
	}

	if ( empty( $rows ) ) {
		return '<span class="xpressui-empty">' . esc_html__( 'Empty', 'xpressui-bridge' ) . '</span>';
	}

	$html  = '<div>';
	/* translators: 1: number of items, 2: total quantity */
	$html .= '<div class="xpressui-value-header">' . esc_html( sprintf( _n( '%1$d selected item · qty %2$d', '%1$d selected items · qty %2$d', count( $rows ), 'xpressui-bridge' ), count( $rows ), $total_quantity ) ) . '</div>';
	$html .= '<table class="widefat striped xpressui-value-table"><thead><tr>';
	$html .= '<th>' . esc_html__( 'Item', 'xpressui-bridge' ) . '</th>';
	$html .= '<th>' . esc_html__( 'Qty', 'xpressui-bridge' ) . '</th>';
	$html .= '<th>' . esc_html__( 'Unit', 'xpressui-bridge' ) . '</th>';
	$html .= '<th>' . esc_html__( 'Total', 'xpressui-bridge' ) . '</th>';
	$html .= '</tr></thead><tbody>';

	foreach ( $rows as $row ) {
		$html .= '<tr>';
		$html .= '<td>' . esc_html( $row['name'] ) . '</td>';
		$html .= '<td>' . esc_html( (string) $row['quantity'] ) . '</td>';
		$html .= '<td>' . ( $row['unit'] !== '' ? esc_html( $row['unit'] ) : '<span class="xpressui-empty">-</span>' ) . '</td>';
		$html .= '<td>' . ( $row['line'] !== '' ? esc_html( $row['line'] ) : '<span class="xpressui-empty">-</span>' ) . '</td>';
		$html .= '</tr>';
	}
	$html .= '</tbody></table>';
	if ( $total_amount > 0 ) {
		/* translators: %s: formatted total amount */
		$html .= '<div class="xpressui-value-header">' . esc_html( sprintf( __( 'Estimated total: %s', 'xpressui-bridge' ), xpressui_format_money( $total_amount ) ) ) . '</div>';
	}
	$html .= '</div>';
	return $html;
}

function xpressui_render_quiz_value( $value, $field_meta = [] ) {
	if ( is_string( $value ) ) {
		$trimmed = trim( $value );
		return $trimmed === ''
			? '<span class="xpressui-empty">' . esc_html__( 'Empty', 'xpressui-bridge' ) . '</span>'
			: nl2br( esc_html( $trimmed ) );
	}
	if ( ! is_array( $value ) || empty( $value ) ) {
		return '<span class="xpressui-empty">' . esc_html__( 'Empty', 'xpressui-bridge' ) . '</span>';
	}

	$catalog_index = xpressui_build_choice_catalog_index( $field_meta );
	$items         = [];
	foreach ( $value as $pos => $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}
		$entry_id      = (string) ( $entry['id'] ?? $entry['value'] ?? ( 'answer_' . ( $pos + 1 ) ) );
		$catalog_entry = $catalog_index[ $entry_id ] ?? [];
		$items[]       = [
			'name' => (string) ( $entry['name'] ?? $entry['label'] ?? $catalog_entry['name'] ?? $catalog_entry['label'] ?? $entry_id ),
			'desc' => (string) ( $entry['desc'] ?? $catalog_entry['desc'] ?? '' ),
		];
	}

	if ( empty( $items ) ) {
		return '<span class="xpressui-empty">' . esc_html__( 'Empty', 'xpressui-bridge' ) . '</span>';
	}

	$html  = '<div>';
	/* translators: %d: number of selected answers. */
	$html .= '<div class="xpressui-value-header">' . esc_html( sprintf( _n( '%d selected answer', '%d selected answers', count( $items ), 'xpressui-bridge' ), count( $items ) ) ) . '</div>';
	$html .= '<ul class="xpressui-answer-list">';
	foreach ( $items as $item ) {
		$html .= '<li><strong>' . esc_html( $item['name'] ) . '</strong>';
		if ( $item['desc'] !== '' ) {
			$html .= '<div class="xpressui-muted">' . esc_html( $item['desc'] ) . '</div>';
		}
		$html .= '</li>';
	}
	$html .= '</ul></div>';
	return $html;
}

function xpressui_render_image_gallery_value( $value, $field_meta = [] ) {
	if ( ! is_array( $value ) || empty( $value ) ) {
		return '<span class="xpressui-empty">' . esc_html__( 'Empty', 'xpressui-bridge' ) . '</span>';
	}

	$catalog_index = xpressui_build_choice_catalog_index( $field_meta );
	$items         = [];
	foreach ( $value as $pos => $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}
		$entry_id      = (string) ( $entry['id'] ?? $entry['value'] ?? ( 'image_' . ( $pos + 1 ) ) );
		$catalog_entry = $catalog_index[ $entry_id ] ?? [];
		$thumbnail     = (string) ( $entry['image_thumbnail'] ?? $catalog_entry['image_thumbnail'] ?? $catalog_entry['imageThumbnail'] ?? '' );
		$full_url      = (string) ( $entry['image_medium'] ?? $catalog_entry['image_medium'] ?? $catalog_entry['imageMedium'] ?? $thumbnail );
		$items[]       = [
			'name'      => (string) ( $entry['name'] ?? $entry['label'] ?? $catalog_entry['name'] ?? $catalog_entry['label'] ?? $entry_id ),
			'thumbnail' => $thumbnail,
			'fullUrl'   => $full_url,
		];
	}

	if ( empty( $items ) ) {
		return '<span class="xpressui-empty">' . esc_html__( 'Empty', 'xpressui-bridge' ) . '</span>';
	}

	$html  = '<div>';
	/* translators: %d: number of selected images. */
	$html .= '<div class="xpressui-value-header">' . esc_html( sprintf( _n( '%d selected image', '%d selected images', count( $items ), 'xpressui-bridge' ), count( $items ) ) ) . '</div>';
	$html .= '<div class="xpressui-image-grid">';
	foreach ( $items as $item ) {
		$html .= '<div class="xpressui-image-card">';
		if ( $item['thumbnail'] !== '' ) {
			$img = '<img src="' . esc_url( $item['thumbnail'] ) . '" alt="' . esc_attr( $item['name'] ) . '" class="xpressui-image-thumb" />';
			$html .= $item['fullUrl'] !== ''
				? '<a href="' . esc_url( $item['fullUrl'] ) . '" target="_blank" rel="noreferrer">' . $img . '</a>'
				: $img;
		}
		$html .= '<div class="xpressui-image-name">' . esc_html( $item['name'] ) . '</div>';
		$html .= '</div>';
	}
	$html .= '</div></div>';
	return $html;
}

function xpressui_format_submission_value( $value, $field_meta = [] ) {
	$field_type = (string) ( $field_meta['type'] ?? '' );
	$choice_map = is_array( $field_meta['choices'] ?? null ) ? $field_meta['choices'] : [];

	$map_choice = static function ( $raw ) use ( $choice_map ) {
		return $choice_map[ (string) $raw ] ?? (string) $raw;
	};

	if ( $field_type === 'product-list' ) {
		return xpressui_render_product_list_value( $value, $field_meta );
	}
	if ( $field_type === 'quiz' ) {
		return xpressui_render_quiz_value( $value, $field_meta );
	}
	if ( $field_type === 'select-image' ) {
		return xpressui_render_image_gallery_value( $value, $field_meta );
	}
	if ( is_array( $value ) ) {
		if ( ( $value['kind'] ?? '' ) === 'uploaded-file' ) {
			$original_name = (string) ( $value['originalName'] ?? $value['field'] ?? 'File' );
			$url           = (string) ( $value['url'] ?? '' );
			if ( $url !== '' ) {
				return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noreferrer">' . esc_html( $original_name ) . '</a>';
			}
			return esc_html( $original_name );
		}
		$is_list = array_keys( $value ) === range( 0, count( $value ) - 1 );
		if ( $is_list ) {
			$parts = [];
			foreach ( $value as $item ) {
				$parts[] = is_scalar( $item ) || $item === null
					? xpressui_render_scalar_badge( $map_choice( $item ) )
					: (string) xpressui_build_structured_item_summary( $item, $choice_map );
			}
			return implode( ' ', array_filter( $parts, static fn( $p ) => $p !== '' ) );
		}
		return '<pre class="xpressui-json">' . esc_html( wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) . '</pre>';
	}
	if ( is_bool( $value ) ) {
		return xpressui_render_scalar_badge( $value ? __( 'Yes', 'xpressui-bridge' ) : __( 'No', 'xpressui-bridge' ), $value ? 'success' : 'muted' );
	}
	if ( $value === null || $value === '' ) {
		return '<span class="xpressui-empty">' . esc_html__( 'Empty', 'xpressui-bridge' ) . '</span>';
	}
	if ( ! empty( $choice_map ) ) {
		return xpressui_render_scalar_badge( $map_choice( $value ) );
	}
	return esc_html( $map_choice( $value ) );
}

function xpressui_build_structured_item_summary( $item, $choice_map = [] ) {
	if ( ! is_array( $item ) ) {
		$normalized = (string) $item;
		return $choice_map[ $normalized ] ?? $normalized;
	}
	$parts   = [];
	$primary = '';
	foreach ( [ 'label', 'title', 'name', 'value', 'id' ] as $key ) {
		$candidate = $item[ $key ] ?? null;
		if ( $candidate === null || is_array( $candidate ) || is_object( $candidate ) ) {
			continue;
		}
		$normalized = (string) $candidate;
		if ( $normalized === '' ) {
			continue;
		}
		$primary = $choice_map[ $normalized ] ?? $normalized;
		break;
	}
	if ( $primary !== '' ) {
		$parts[] = $primary;
	}
	foreach ( [
		'quantity' => 'qty',
		'price'    => 'price',
		'amount'   => 'amount',
		'score'    => 'score',
		'result'   => 'result',
		'answer'   => 'answer',
		'selected' => 'selected',
		'correct'  => 'correct',
	] as $key => $label ) {
		$raw = $item[ $key ] ?? null;
		if ( $raw === null || is_array( $raw ) || is_object( $raw ) || $raw === '' ) {
			continue;
		}
		$parts[] = is_bool( $raw )
			? $label . ': ' . ( $raw ? 'yes' : 'no' )
			: $label . ': ' . (string) $raw;
	}
	return empty( $parts )
		? wp_json_encode( $item, JSON_UNESCAPED_SLASHES )
		: implode( ' · ', $parts );
}
