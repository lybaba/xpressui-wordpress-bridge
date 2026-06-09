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

function xpressui_get_shell_allowed_html() {
	$global_attrs = [
		'id'          => true,
		'class'       => true,
		'style'       => true,
		'title'       => true,
		'role'        => true,
		'aria-label'  => true,
		'aria-hidden' => true,
		'data-*'      => true,
	];

	return [
		'html'     => [ 'lang' => true ],
		'head'     => [],
		'meta'     => [ 'charset' => true, 'name' => true, 'content' => true, 'viewport' => true ],
		'title'    => [],
		'link'     => [ 'rel' => true, 'href' => true, 'as' => true, 'crossorigin' => true ],
		'style'    => array_merge( $global_attrs, [ 'type' => true ] ),
		'script'   => array_merge( $global_attrs, [ 'type' => true, 'src' => true, 'defer' => true, 'async' => true ] ),
		'body'     => $global_attrs,
		'main'     => $global_attrs,
		'section'  => $global_attrs,
		'div'      => $global_attrs,
		'span'     => $global_attrs,
		'p'        => $global_attrs,
		'h1'       => $global_attrs,
		'h2'       => $global_attrs,
		'h3'       => $global_attrs,
		'h4'       => $global_attrs,
		'form'     => array_merge( $global_attrs, [ 'action' => true, 'method' => true, 'enctype' => true, 'novalidate' => true ] ),
		'fieldset' => $global_attrs,
		'legend'   => $global_attrs,
		'label'    => array_merge( $global_attrs, [ 'for' => true ] ),
		'input'    => array_merge( $global_attrs, [ 'type' => true, 'name' => true, 'value' => true, 'placeholder' => true, 'required' => true, 'checked' => true, 'disabled' => true, 'readonly' => true, 'min' => true, 'max' => true, 'step' => true, 'accept' => true, 'autocomplete' => true ] ),
		'textarea' => array_merge( $global_attrs, [ 'name' => true, 'placeholder' => true, 'required' => true, 'disabled' => true, 'readonly' => true, 'rows' => true, 'cols' => true ] ),
		'select'   => array_merge( $global_attrs, [ 'name' => true, 'required' => true, 'disabled' => true, 'multiple' => true ] ),
		'option'   => array_merge( $global_attrs, [ 'value' => true, 'selected' => true, 'disabled' => true ] ),
		'button'   => array_merge( $global_attrs, [ 'type' => true, 'name' => true, 'value' => true, 'disabled' => true ] ),
		'a'        => array_merge( $global_attrs, [ 'href' => true, 'target' => true, 'rel' => true ] ),
		'img'      => array_merge( $global_attrs, [ 'src' => true, 'alt' => true, 'width' => true, 'height' => true, 'loading' => true, 'decoding' => true, 'hidden' => true ] ),
		'svg'      => array_merge( $global_attrs, [ 'xmlns' => true, 'viewBox' => true, 'fill' => true, 'stroke' => true ] ),
		'path'     => [ 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ],
		'ul'       => $global_attrs,
		'ol'       => $global_attrs,
		'li'       => $global_attrs,
		'table'    => $global_attrs,
		'thead'    => $global_attrs,
		'tbody'    => $global_attrs,
		'tr'       => $global_attrs,
		'th'       => array_merge( $global_attrs, [ 'scope' => true, 'colspan' => true, 'rowspan' => true ] ),
		'td'       => array_merge( $global_attrs, [ 'colspan' => true, 'rowspan' => true ] ),
		'template' => array_merge( $global_attrs, [ 'type' => true ] ),
		'dialog'   => array_merge( $global_attrs, [ 'open' => true ] ),
	];
}

/**
 * Builds the scoped inline CSS string for a shortcode embed.
 *
 * Sets CSS custom properties on the mount element, loads the static shell CSS
 * and scopes body-level selectors to the mount ID so they don't affect the
 * surrounding WordPress page. Delivered via wp_add_inline_style().
 *
 * @param array  $template_context Full template context array.
 * @param string $mount_node_id    CSS ID of the mount element (without #).
 * @return string Scoped CSS ready for wp_add_inline_style().
 */
function xpressui_build_shortcode_inline_css( array $template_context, $mount_node_id ) {
	$scope   = '#' . $mount_node_id;
	$theme   = $template_context['theme']   ?? [];
	$colors  = $theme['colors']             ?? [];
	$radius  = $theme['radius']             ?? [];
	$project = $template_context['project'] ?? [];
	$bg_url  = $project['background_image_url'] ?? '';
	$font    = ! empty( $theme['font_family'] ) ? $theme['font_family'] : 'Inter, system-ui, sans-serif';

	// CSS custom properties scoped to the mount element (equivalent to :root for this embed).
	$inline_css  = "{$scope} {\n";
	$inline_css .= '  --template-font-family: '      . esc_attr( $font ) . ";\n";
	$inline_css .= '  --template-page-background: '  . esc_attr( $colors['page_background'] ?? '' ) . ";\n";
	$inline_css .= '  --template-surface: '          . esc_attr( $colors['surface']         ?? '' ) . ";\n";
	$inline_css .= '  --template-text: '             . esc_attr( $colors['text']            ?? '' ) . ";\n";
	$inline_css .= "  --template-muted-text: color-mix(in srgb, var(--template-text) 65%, transparent);\n";
	$inline_css .= '  --template-primary: '          . esc_attr( $colors['primary']         ?? '' ) . ";\n";
	$inline_css .= '  --template-border: '           . esc_attr( $colors['border']          ?? '' ) . ";\n";
	$inline_css .= '  --template-card-radius: '      . esc_attr( (string) ( $radius['card']   ?? 0 ) ) . "px;\n";
	$inline_css .= '  --template-input-radius: '     . esc_attr( (string) ( $radius['input']  ?? 0 ) ) . "px;\n";
	$inline_css .= '  --template-button-radius: '    . esc_attr( (string) ( $radius['button'] ?? 0 ) ) . "px;\n";
	$inline_css .= '  --template-background-image: ' . ( $bg_url ? 'url(' . esc_url_raw( $bg_url ) . ')' : 'none' ) . ";\n";
	$inline_css .= "}\n";

	// Load component CSS from the static shell file, scoping body-level selectors
	// to the mount element so they don't affect the surrounding WordPress page.
	$shell_css_path = XPRESSUI_BRIDGE_DIR . 'assets/shell/xpressui-shell.css';
	if ( file_exists( $shell_css_path ) ) {
		$shell_css  = (string) file_get_contents( $shell_css_path );
		$shell_css  = preg_replace( '/#xpressui-root(?![-\w])/', $scope, $shell_css );
		$shell_css  = str_replace(
			[ 'body::', 'body {', 'body,', 'body ' ],
			[ $scope . '::', $scope . ' {', $scope . ',', $scope . ' ' ],
			$shell_css
		);
		$inline_css .= "\n" . $shell_css . "\n";
	}

	$has_bg = ! empty( $bg_url )
		&& isset( $theme['background_style'] )
		&& $theme['background_style'] !== 'none';

	// Static WordPress embedding overrides — scoped to the mount ID.
	$inline_css .= "\n/* Resume mode */\n";
	$inline_css .= "{$scope}[data-resume-loading] .template-runtime-shell { display: none !important; }\n";
	$inline_css .= "{$scope}[data-resume-loading] [data-resume-loader] { display: grid !important; }\n";
	$inline_css .= "{$scope} .xpressui-resume-banner { background: #fffaf0; border: 1px solid #f6cc87; border-radius: 4px; padding: 12px 16px; font-size: 13px; color: #374151; line-height: 1.5; }\n";
	$inline_css .= "{$scope} .xpressui-ref-file-block,\n{$scope} .xpressui-afile-ref-block { padding: 10px 14px; background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 6px; }\n";
	$inline_css .= "{$scope} .xpressui-ref-file-link,\n{$scope} .xpressui-afile-ref-link { font-size: 13px; font-weight: 600; color: #1d4ed8; text-decoration: underline; display: block; margin-bottom: 6px; }\n";
	$inline_css .= "{$scope} .xpressui-ref-file-hint,\n{$scope} .xpressui-afile-ref-hint { margin: 0; font-size: 12px; color: #374151; line-height: 1.5; }\n";
	$inline_css .= "{$scope} .xpressui-resume-loader { min-height: 320px; display: none; place-items: center; padding: 28px 0; }\n";
	$inline_css .= "{$scope} .xpressui-resume-loader-card { width: min(100%, 420px); display: grid; justify-items: center; gap: 12px; padding: 28px 24px; border-radius: 24px; border: 1px solid rgba(37, 99, 235, 0.12); background: rgba(255, 255, 255, 0.94); box-shadow: 0 24px 60px -36px rgba(15, 23, 42, 0.28); text-align: center; }\n";
	$inline_css .= "{$scope} .xpressui-resume-loader-spinner { width: 42px; height: 42px; border-radius: 999px; border: 3px solid rgba(37, 99, 235, 0.16); border-top-color: #2563eb; animation: xpressui-spin 0.72s linear infinite; }\n";
	$inline_css .= "{$scope} .xpressui-resume-loader-title { font-size: 18px; font-weight: 700; letter-spacing: -0.02em; color: #0f172a; }\n";
	$inline_css .= "{$scope} .xpressui-resume-loader-text { margin: 0; max-width: 32ch; font-size: 13px; line-height: 1.6; color: #64748b; }\n";
	$inline_css .= "{$scope} .xpressui-info-only-block { padding: 16px; background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 6px; }\n";
	$inline_css .= "{$scope} .xpressui-info-only-link { font-size: 14px; font-weight: 600; color: #1d4ed8; text-decoration: underline; display: block; margin-bottom: 8px; }\n";
	$inline_css .= "{$scope} .xpressui-info-only-hint { margin: 0; font-size: 13px; color: #374151; line-height: 1.5; }\n";
	$inline_css .= "/* WordPress inline embed — reset standalone-page layout */\n";
	if ( $has_bg ) {
		$inline_css .= "{$scope}.page-shell { min-height: 0 !important; height: auto !important; overflow: hidden !important; padding: 48px max(5%, 24px) !important; display: grid !important; place-items: center !important; background: transparent !important; position: relative !important; border-radius: 24px !important; }\n";
	} else {
		$inline_css .= "{$scope}.page-shell { min-height: 0 !important; height: auto !important; overflow: visible !important; padding: 0 !important; display: block !important; background: transparent !important; }\n";
	}
	$box_shadow = $has_bg ? '0 28px 80px -38px rgba(0,0,0,0.42)' : '0 16px 44px rgba(15, 23, 42, 0.1)';
	$extra_fw   = $has_bg ? ' max-width: 680px !important; width: 100% !important;' : '';
	// Single framed card (like the hosted link). Forced with !important so the
	// surrounding WordPress theme can't strip the border/background/radius — the
	// unscoped shell rule loses specificity to most themes.
	$inline_css .= "{$scope} .form-frame { background: color-mix(in srgb, var(--template-surface) 92%, white) !important; border: 1px solid color-mix(in srgb, var(--template-border) 72%, transparent) !important; border-radius: var(--template-card-radius) !important; padding: 24px !important; box-shadow: {$box_shadow} !important;{$extra_fw} }\n";
	$inline_css .= "{$scope} .template-runtime-shell { gap: 16px; }\n";
	$inline_css .= "{$scope} .template-form-header { gap: 2px; padding-top: 0; }\n";
	$inline_css .= "{$scope} .template-form-title { font-size: clamp(22px, 2.8vw, 30px); line-height: 1.08; letter-spacing: -0.03em; }\n";
	// Sections are flat content inside the single .form-frame card — no inner card
	// chrome, no extra padding (the frame already pads), so nav/submit sit inside.
	$inline_css .= "{$scope} .template-section { gap: 18px; padding: 0; border: 0; background: none; }\n";
	$inline_css .= "{$scope} .template-fields { gap: 12px; }\n";
	$inline_css .= "{$scope} .template-field { gap: 6px; }\n";
	$inline_css .= "{$scope} .template-field-label { font-size: 13px; }\n";
	$inline_css .= "{$scope} .template-field-help { font-size: 12px; line-height: 1.4; }\n";
	// Force input/textarea theming with !important too — same reason as the card:
	// some WordPress themes style bare inputs dark and beat the unscoped shell rule.
	$inline_css .= "{$scope} .template-input,\n{$scope} .template-textarea { background-color: color-mix(in srgb, var(--template-surface) 96%, white) !important; color: var(--template-text) !important; border-color: var(--template-border) !important; font-size: 14px; line-height: 1.4; padding: 11px 13px; }\n";
	$inline_css .= "{$scope} .template-input:focus,\n{$scope} .template-textarea:focus { border-color: var(--template-primary) !important; box-shadow: 0 0 0 3px color-mix(in srgb, var(--template-primary) 15%, transparent) !important; outline: none !important; }\n";
	$inline_css .= "{$scope} .template-input::placeholder,\n{$scope} .template-textarea::placeholder { color: var(--template-muted-text) !important; }\n";
	$inline_css .= "{$scope} .template-textarea { min-height: 124px; }\n";
	$inline_css .= "{$scope} .template-choice-card { padding: 9px 12px; gap: 3px; }\n";
	$inline_css .= "{$scope} .template-choice-title { font-size: 12px; line-height: 1.2; font-weight: 600; }\n";
	$inline_css .= "{$scope} .template-choice-footer { font-size: 11px; }\n";
	$inline_css .= "{$scope} select.template-input[multiple] { min-height: 120px; padding-top: 7px; padding-bottom: 7px; }\n";
	$inline_css .= "{$scope} select.template-input option { font-size: 14px; line-height: 1.35; }\n";
	$inline_css .= "{$scope} .template-runtime-shell select { background-color: color-mix(in srgb, var(--template-surface) 96%, white) !important; color: var(--template-text) !important; border-color: var(--template-border) !important; accent-color: var(--template-primary) !important; }\n";
	$inline_css .= "{$scope} .template-runtime-shell select:focus { border-color: var(--template-primary) !important; box-shadow: 0 0 0 3px color-mix(in srgb, var(--template-primary) 15%, transparent) !important; outline: none !important; }\n";
	$inline_css .= "{$scope} .template-step-progress-track { height: 7px; }\n";
	$inline_css .= "{$scope} .template-step-actions { margin-top: 14px; gap: 10px; }\n";
	$inline_css .= "{$scope} .template-step-actions [data-step-action] { font-size: 13px; padding: 11px 16px; min-width: 112px; }\n";
	$inline_css .= "{$scope} .template-submit-row { padding-top: 14px; }\n";
	$inline_css .= "{$scope} .template-submit-btn { cursor: pointer; font-size: 13px; line-height: 1.1; padding: 11px 16px; min-width: 112px; }\n";
	$inline_css .= "{$scope} .template-section-header { padding-bottom: 14px; border-bottom: 2px solid color-mix(in srgb, var(--template-primary, #2563eb) 18%, transparent); margin-bottom: 2px; }\n";
	$inline_css .= "{$scope} .template-section-label { font-size: 15px; font-weight: 700; letter-spacing: -0.01em; color: var(--template-text, #0f172a); }\n";
	$inline_css .= "@keyframes xpressui-step-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }\n";
	$inline_css .= "{$scope} .template-section[data-template-zone=\"section\"] { animation: xpressui-step-in 220ms cubic-bezier(0.22, 1, 0.36, 1) both; }\n";
	$inline_css .= "@media (max-width: 720px) {\n";
	$inline_css .= "  {$scope} .form-frame { padding: 16px !important; }\n";
	$inline_css .= "  {$scope} .template-form-title { font-size: clamp(20px, 7vw, 26px); }\n";
	$inline_css .= "  {$scope} .template-section { padding: 0; }\n";
	$inline_css .= "  {$scope} .template-input,\n  {$scope} .template-textarea { font-size: 13px; }\n";
	$inline_css .= "}\n";

	return $inline_css;
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
	$raw_form_config_json = $template_context['runtime']['form_config_json'] ?? '';
	if ( '' === $raw_form_config_json ) {
		$raw_form_config_json = xpressui_get_workflow_artifact_contents( $slug, 'config', 'form.config.json' );
	}
	$form_config = is_string( $raw_form_config_json ) && '' !== $raw_form_config_json
		? json_decode( $raw_form_config_json, true )
		: null;
	if ( is_array( $form_config ) ) {
		$form_config = xpressui_normalize_form_config( $form_config, $slug );
		$fresh_rendered_form = xpressui_build_rendered_form_from_config( $form_config );
		if ( ! is_array( $template_context['rendered_form'] ?? null ) ) {
			$template_context['rendered_form'] = $fresh_rendered_form;
		} elseif ( is_array( $fresh_rendered_form['sections'] ?? null ) ) {
			$template_context['rendered_form']['sections'] = $fresh_rendered_form['sections'];
		}
		if ( ! is_array( $template_context['runtime'] ?? null ) ) {
			$template_context['runtime'] = [];
		}
		$template_context['runtime']['form_config_json'] = wp_json_encode( $form_config );
	}

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
	if ( '' !== $runtime_relative && file_exists( xpressui_get_workflow_package_dir( $slug ) . $runtime_relative ) ) {
		$runtime_url = xpressui_get_workflow_package_url( $slug ) . $runtime_relative;
	} else {
		$runtime_relative = '';
	}
	$runtime_url = (string) apply_filters( 'xpressui_runtime_url', $runtime_url, $slug );

	$init_url     = xpressui_get_plugin_shell_init_url();
	$handle_prefix = 'xpressui-shell-' . sanitize_key( $slug );

	// Fix relative script URLs that the template emits for the standalone target.
	if ( '' !== $runtime_relative && '' !== $runtime_url ) {
		$rendered_html = str_replace( './' . $runtime_relative, esc_url_raw( $runtime_url ), $rendered_html );
	}
	if ( '' !== $init_url ) {
		$rendered_html = str_replace( './init.js', esc_url_raw( $init_url ), $rendered_html );
	}

	// Register inline data through the WordPress enqueue API.
	// The captured output is then injected into the standalone HTML document that
	// this function returns — wp_head()/wp_footer() are not called in this code
	// path because the caller (shell.php) outputs a direct HTTP response and exits.
	wp_register_script( $handle_prefix . '-data', false, [], XPRESSUI_BRIDGE_VERSION, false );
	wp_add_inline_script(
		$handle_prefix . '-data',
		'window.XPRESSUI_I18N = ' . wp_json_encode( xpressui_get_shell_translations() ) . ';' .
		'window.XPRESSUI_SHELL_META = ' . wp_json_encode(
			[
				'slug'               => $slug,
				'runtimeUrl'         => $runtime_url,
				'runtimeRelative'    => $runtime_relative,
				'runtimeSource'      => xpressui_describe_runtime_source( $runtime_url, $slug ),
				'workflowPackageUrl' => xpressui_get_workflow_package_url( $slug ),
				'shellInitUrl'       => $init_url,
			]
		) . ';'
	);
	wp_enqueue_script( $handle_prefix . '-data' );

	// If the template did not already embed the runtime/init scripts (fallback),
	// enqueue them now so they appear in the captured output below.
	$runtime_already_embedded = '' !== $runtime_url && false !== strpos( $rendered_html, esc_url_raw( $runtime_url ) );
	if ( ! $runtime_already_embedded && '' !== $runtime_url ) {
		wp_enqueue_script( $handle_prefix . '-runtime', $runtime_url, [ $handle_prefix . '-data' ], XPRESSUI_BRIDGE_VERSION, false );
	}
	$init_already_embedded = '' !== $init_url && false !== strpos( $rendered_html, esc_url_raw( $init_url ) );
	if ( ! $init_already_embedded && '' !== $init_url ) {
		$runtime_dep = $runtime_already_embedded ? [] : [ $handle_prefix . '-runtime' ];
		wp_enqueue_script( $handle_prefix . '-init', $init_url, array_merge( [ $handle_prefix . '-data' ], $runtime_dep ), XPRESSUI_BRIDGE_VERSION, false );
	}

	// Capture the script tags produced by the WP enqueue API for injection.
	global $wp_scripts;
	$handles_to_print = [ $handle_prefix . '-data' ];
	if ( ! $runtime_already_embedded && '' !== $runtime_url ) {
		$handles_to_print[] = $handle_prefix . '-runtime';
	}
	if ( ! $init_already_embedded && '' !== $init_url ) {
		$handles_to_print[] = $handle_prefix . '-init';
	}
	ob_start();
	$wp_scripts->do_items( $handles_to_print );
	$scripts_html = (string) ob_get_clean();

	if ( false !== strpos( $rendered_html, '</head>' ) ) {
		$rendered_html = str_replace( '</head>', $scripts_html . '</head>', $rendered_html );
	} else {
		$rendered_html = $scripts_html . $rendered_html;
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

function xpressui_get_all_submission_ids_for_lookup() {
	return get_posts( [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'fields'         => 'ids',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );
}

function xpressui_get_resume_post_id_by_token( $token ) {
	$token = (string) $token;
	if ( strlen( $token ) !== 64 || ! ctype_xdigit( $token ) ) {
		return 0;
	}
	$cache_key = 'resume_token_' . md5( $token );
	$cached    = wp_cache_get( $cache_key, 'xpressui_bridge' );
	if ( false !== $cached ) {
		$post_id = (int) $cached;
	} else {
		$post_id = 0;
		foreach ( xpressui_get_all_submission_ids_for_lookup() as $candidate_id ) {
			if ( hash_equals( $token, (string) get_post_meta( $candidate_id, '_xpressui_resume_token', true ) ) ) {
				$post_id = (int) $candidate_id;
				break;
			}
		}
		wp_cache_set( $cache_key, $post_id, 'xpressui_bridge', MINUTE_IN_SECONDS );
	}
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
	$page_ids = xpressui_get_workflow_page_ids( $project_slug, [ 'publish' ] );
	$page_id  = ! empty( $page_ids ) ? (int) $page_ids[0] : 0;
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
 * Return a human-readable display name for a WP attachment, truncated to 48 chars.
 * Prefers the attachment post title over the raw filename.
 */
function xpressui_get_attachment_display_name( int $attachment_id ): string {
	$title = trim( (string) get_the_title( $attachment_id ) );
	if ( $title === '' ) {
		$path  = (string) get_attached_file( $attachment_id );
		$title = $path !== '' ? basename( $path ) : '';
	}
	if ( $title === '' ) {
		return '';
	}
	return function_exists( 'mb_strimwidth' )
		? mb_strimwidth( $title, 0, 48, '…' )
		: ( strlen( $title ) > 48 ? substr( $title, 0, 47 ) . '…' : $title );
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

function xpressui_get_workflow_page_ids( $slug, $statuses = [ 'draft', 'publish', 'pending', 'private' ] ) {
	$slug = sanitize_title( (string) $slug );
	if ( '' === $slug ) {
		return [];
	}

	$statuses = array_values( array_filter( array_map( 'sanitize_key', (array) $statuses ) ) );
	if ( empty( $statuses ) ) {
		$statuses = [ 'draft', 'publish', 'pending', 'private' ];
	}

	$cache_key = 'workflow_pages_' . md5( $slug . '|' . implode( ',', $statuses ) );
	$cached    = wp_cache_get( $cache_key, 'xpressui_bridge' );
	if ( false !== $cached ) {
		return is_array( $cached ) ? array_map( 'intval', $cached ) : [];
	}

	$ids = [];
	foreach ( get_posts( [
		'post_type'      => 'page',
		'post_status'    => $statuses,
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'ASC',
	] ) as $page ) {
		$content = (string) ( $page->post_content ?? '' );
		if (
			false !== strpos( $content, '[xpressui id="' . $slug . '"' )
			|| false !== strpos( $content, "[xpressui id='" . $slug . "'" )
		) {
			$ids[] = (int) $page->ID;
		}
	}

	wp_cache_set( $cache_key, $ids, 'xpressui_bridge', MINUTE_IN_SECONDS );
	return $ids;
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
	$all_settings  = get_option( 'xpressui_project_settings', [] );
	$project_settings = is_array( $all_settings[ $slug ] ?? null ) ? $all_settings[ $slug ] : [];
	$custom_success_message = sanitize_text_field( (string) ( $project_settings['submitSuccessMessage'] ?? '' ) );
	$custom_error_message   = sanitize_text_field( (string) ( $project_settings['submitErrorMessage'] ?? '' ) );
	$submission_action      = sanitize_key( (string) ( $project_settings['submissionAction'] ?? 'submit' ) );
	if ( ! in_array( $submission_action, [ 'submit', 'print' ], true ) ) {
		$submission_action = 'submit';
	}

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
	if ( 'print' === $submission_action ) {
		$wc['submissionMode'] = 'print-only';
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
	if ( $custom_success_message !== '' ) {
		$wc['successMessage'] = $custom_success_message;
	}
	if ( $custom_error_message !== '' ) {
		$wc['errorMessage'] = $custom_error_message;
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
	if ( 'print' === $submission_action ) {
		$form_config['submit']['action']    = 'print';
		$form_config['submit']['includeDocumentData'] = false;
		$form_config['submit']['documentReadyMessage'] = __( 'Votre document est prêt.', 'xpressui-bridge' );
		$form_config['submit']['documentDownloadLabel'] = __( 'Télécharger le document', 'xpressui-bridge' );
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
		$sf['loading_message'] = 'print' === $submission_action
			? __( 'Préparation du document…', 'xpressui-bridge' )
			: __( 'Submitting…', 'xpressui-bridge' );
	}
	if ( empty( $sf['success_title'] ) ) {
		$sf['success_title'] = 'print' === $submission_action
			? __( 'Document prêt', 'xpressui-bridge' )
			: __( 'Submission received', 'xpressui-bridge' );
	}
	if ( empty( $sf['success_message'] ) ) {
		$sf['success_message'] = 'print' === $submission_action
			? __( 'Votre document est prêt.', 'xpressui-bridge' )
			: ( $form_config['workflowConfig']['successMessage']
				?? __( 'Your submission was received successfully.', 'xpressui-bridge' ) );
	}
	if ( empty( $sf['document_download_label'] ) ) {
		$sf['document_download_label'] = __( 'Télécharger le document', 'xpressui-bridge' );
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
	$legacy_type_map = [
		'uploadfile'        => 'file',
		'uploadimage'       => 'upload-image',
		'paymentproof'      => 'payment-proof',
		'payment-screenshot' => 'payment-proof',
		'paymentscreenshot' => 'payment-proof',
		'camera-screenshot' => 'payment-proof',
		'camerascreenshot'  => 'payment-proof',
		'wave-payment-proof' => 'payment-proof',
		'wavepaymentproof'  => 'payment-proof',
	];
	$normalize_field_type = static function ( $type ) use ( $legacy_type_map ): string {
		$type = is_string( $type ) ? trim( $type ) : '';
		if ( '' === $type ) {
			return 'text';
		}
		return $legacy_type_map[ $type ] ?? $type;
	};
	$build_upload_accept_label = static function ( string $type, array $field ): string {
		if ( $type === 'upload-image' || $type === 'payment-proof' ) {
			return 'Accepted: images';
		}
		if ( $type === 'camera-photo' || $type === 'camera-photo-list' ) {
			return 'Accepted: camera images';
		}
		if ( $type === 'qr-scan' ) {
			return 'Accepted: QR image or live camera';
		}
		if ( $type === 'document-scan' ) {
			return 'Accepted: ID, passport image or PDF';
		}

		$accept = isset( $field['accept'] ) ? trim( (string) $field['accept'] ) : '';
		if ( '' !== $accept ) {
			$parts = array_filter( array_map( 'trim', explode( ',', $accept ) ) );
			$exts  = array_values( array_filter( $parts, static fn( $p ) => str_starts_with( $p, '.' ) ) );
			if ( count( $exts ) > 0 ) {
				return 'Accepted: ' . implode( ', ', $exts );
			}
		}

		$lower_label = strtolower( (string) ( $field['label'] ?? '' ) );
		$lower_name  = strtolower( (string) ( $field['name'] ?? '' ) );
		foreach ( [ 'resume', 'cv' ] as $kw ) {
			if ( str_contains( $lower_label, $kw ) || str_contains( $lower_name, $kw ) ) {
				return 'Accepted: PDF, DOC';
			}
		}
		foreach ( [ 'identity', 'passport', 'document', 'proof' ] as $kw ) {
			if ( str_contains( $lower_label, $kw ) || str_contains( $lower_name, $kw ) ) {
				return 'Accepted: PDF or image';
			}
		}

		return 'Accepted: documents';
	};

	// Map form.config.json field types to HTML input types expected by the templates.
	$input_type_map = [
		'text'             => 'text',
		'email'            => 'email',
		'tel'              => 'tel',
		'url'              => 'url',
		'number'           => 'number',
		'price'            => 'number',
		'date'             => 'date',
		'time'             => 'time',
		'password'         => 'password',
		'file'             => 'file',
		'upload-image'     => 'file',
		'camera-photo'     => 'file',
		'camera-photo-list' => 'file',
		'qr-scan'          => 'file',
		'document-scan'    => 'file',
		'payment-proof'    => 'file',
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
			$type = $normalize_field_type( $field['type'] ?? 'text' );
			$field['type'] = $type;
			$field['input_type']  = $input_type_map[ $type ] ?? 'text';
			$field['placeholder'] = $field['placeholder'] ?? '';
			$field['desc']        = $field['desc'] ?? '';
			$field['helpText']    = $field['helpText'] ?? '';
			$field['accept']      = $field['accept'] ?? '';
			$field['capture']     = $field['capture'] ?? '';
			if ( $type === 'upload-image' && empty( $field['accept'] ) ) {
				$field['accept'] = 'image/*';
			}
			if ( $type === 'camera-photo' || $type === 'camera-photo-list' ) {
				if ( empty( $field['accept'] ) ) {
					$field['accept'] = 'image/*';
				}
				if ( empty( $field['capture'] ) ) {
					$field['capture'] = 'environment';
				}
			}
			if ( $type === 'qr-scan' ) {
				if ( empty( $field['accept'] ) ) {
					$field['accept'] = 'image/*';
				}
				if ( empty( $field['capture'] ) ) {
					$field['capture'] = 'environment';
				}
				$field['upload_accept_label'] = $field['upload_accept_label'] ?? $build_upload_accept_label( $type, $field );
			}
			if ( $type === 'document-scan' ) {
				if ( empty( $field['accept'] ) ) {
					$field['accept'] = 'image/*,application/pdf';
				}
				$field['document_scan_mode'] = $field['documentScanMode'] ?? $field['document_scan_mode'] ?? 'double';
				$field['document_scan_slots'] = $field['document_scan_slots'] ?? (
					'single' === $field['document_scan_mode']
						? [
							[
								'index'         => 0,
								'key'           => 'front',
								'label'         => 'Front',
								'control_label' => 'Capture Front',
							],
						]
						: [
							[
								'index'         => 0,
								'key'           => 'front',
								'label'         => 'Front',
								'control_label' => 'Capture Front',
							],
							[
								'index'         => 1,
								'key'           => 'back',
								'label'         => 'Back',
								'control_label' => 'Capture Back',
							],
						]
				);
				$field['upload_accept_label'] = $field['upload_accept_label'] ?? $build_upload_accept_label( $type, $field );
			}
			if ( in_array( $type, [ 'file', 'upload-image', 'camera-photo', 'camera-photo-list' ], true ) ) {
				$field['upload_accept_label'] = $field['upload_accept_label'] ?? $build_upload_accept_label( $type, $field );
			}
			if ( $type === 'payment-proof' ) {
				if ( empty( $field['accept'] ) ) {
					$field['accept'] = 'image/*';
				}
				$field['upload_accept_label'] = $field['upload_accept_label'] ?? $build_upload_accept_label( $type, $field );

				$prov_labels = [
					'wave'          => 'Wave',
					'orange-money'  => 'Orange Money',
					'free-money'    => 'Free Money',
					'bank-transfer' => 'Bank transfer',
					'manual'        => 'Manual payment',
				];
				$format_amount_display = function ( $raw, string $currency ): ?string {
					if ( $raw === '' || $raw === null || ! is_numeric( $raw ) ) return null;
					$amt      = (float) $raw;
					$decimals = ( $amt == floor( $amt ) ) ? 0 : 2;
					$fmt      = number_format( $amt, $decimals, '.', "\u{202F}" );
					return $currency ? "$fmt $currency" : $fmt;
				};
				$format_iban = function ( string $raw ): ?string {
					$clean = strtoupper( preg_replace( '/\s+/', '', $raw ) );
					return $clean ? trim( implode( ' ', str_split( $clean, 4 ) ) ) : null;
				};
				$make_prov = function ( string $provider, array $cfg, $fallback_amount, string $fallback_instr, string $currency ) use ( $prov_labels, $format_amount_display, $format_iban ): array {
					$raw_amt  = $cfg['paymentAmount'] ?? $cfg['payment_amount'] ?? $fallback_amount ?? '';
					$iban_raw = trim( (string) ( $cfg['paymentIban'] ?? $cfg['payment_iban'] ?? '' ) );
					$clean_iban = strtoupper( preg_replace( '/\s+/', '', $iban_raw ) );
					$label    = $prov_labels[ $provider ] ?? ucwords( str_replace( [ '-', '_' ], ' ', $provider ) );
					return [
						'provider'                => $provider,
						'provider_label'          => $label,
						'payment_amount'          => $raw_amt,
						'payment_amount_display'  => $format_amount_display( $raw_amt, $currency ),
						'payment_currency'        => $currency,
						'payment_instructions'    => (string) ( $cfg['paymentInstructions'] ?? $cfg['payment_instructions'] ?? $fallback_instr ),
						'merchant_phone'          => (string) ( $cfg['merchantPhone'] ?? $cfg['merchant_phone'] ?? '' ),
						'merchant_phone_display'  => (string) ( $cfg['merchantPhone'] ?? $cfg['merchant_phone'] ?? '' ),
						'merchant_phone_href'     => (string) ( $cfg['merchantPhone'] ?? $cfg['merchant_phone'] ?? '' ),
						'merchant_qr_code'        => (string) ( $cfg['merchantQrCode'] ?? $cfg['merchant_qr_code'] ?? '' ),
						'merchant_name'           => (string) ( $cfg['merchantName'] ?? $cfg['merchant_name'] ?? '' ),
						'payment_iban'            => $clean_iban ?: null,
						'payment_iban_display'    => $format_iban( $iban_raw ),
						'payment_bic'             => strtoupper( trim( (string) ( $cfg['paymentBic'] ?? $cfg['payment_bic'] ?? '' ) ) ) ?: null,
						'payment_reference_prefix'=> strtoupper( trim( (string) ( $cfg['paymentReferencePrefix'] ?? $cfg['payment_reference_prefix'] ?? '' ) ) ) ?: null,
					];
				};

				$currency        = (string) ( $field['paymentCurrency'] ?? $field['payment_currency'] ?? 'XOF' );
				$global_amount   = $field['paymentAmount'] ?? $field['payment_amount'] ?? '';
				$global_instr    = (string) ( $field['paymentInstructions'] ?? $field['payment_instructions'] ?? '' );
				$mobile_cfg      = is_array( $field['mobileMoney'] ?? null ) ? $field['mobileMoney'] : [];
				$bank_cfg        = is_array( $field['bankTransfer'] ?? null ) ? $field['bankTransfer'] : [];
				$manual_cfg      = is_array( $field['manualPayment'] ?? null ) ? $field['manualPayment'] : [];
				$mobile_provider = (string) ( $field['mobileMoneyProvider'] ?? '' );
				$has_new_format  = isset( $field['mobileMoneyProvider'] ) || ! empty( $bank_cfg ) || ! empty( $manual_cfg );

				$payment_providers = [];
				if ( ! $has_new_format ) {
					$legacy_provider = (string) ( $field['paymentProvider'] ?? $field['subType'] ?? $field['payment_provider'] ?? 'manual' );
					$legacy_cfg      = array_merge( [
						'merchantPhone'       => $field['merchantPhone'] ?? $field['merchant_phone'] ?? '',
						'merchantQrCode'      => $field['merchantQrCode'] ?? $field['merchant_qr_code'] ?? '',
						'merchantName'        => $field['merchantName'] ?? $field['merchant_name'] ?? '',
						'paymentAmount'       => $global_amount,
						'paymentInstructions' => $global_instr,
						'paymentIban'         => $field['paymentIban'] ?? $field['payment_iban'] ?? '',
						'paymentBic'          => $field['paymentBic'] ?? $field['payment_bic'] ?? '',
						'paymentReferencePrefix' => $field['paymentReferencePrefix'] ?? $field['payment_reference_prefix'] ?? '',
					], [] );
					$payment_providers[] = $make_prov( $legacy_provider, $legacy_cfg, $global_amount, $global_instr, $currency );
				} else {
					if ( in_array( $mobile_provider, [ 'wave', 'orange-money', 'free-money' ], true ) ) {
						$payment_providers[] = $make_prov( $mobile_provider, $mobile_cfg, $global_amount, $global_instr, $currency );
					}
					if ( ! empty( $bank_cfg['enabled'] ) ) {
						$payment_providers[] = $make_prov( 'bank-transfer', $bank_cfg, $global_amount, $global_instr, $currency );
					}
					if ( ! empty( $manual_cfg['enabled'] ) ) {
						$payment_providers[] = $make_prov( 'manual', $manual_cfg, $global_amount, $global_instr, $currency );
					}
					if ( empty( $payment_providers ) ) {
						$payment_providers[] = $make_prov( 'manual', [], $global_amount, $global_instr, $currency );
					}
				}

				$first                           = $payment_providers[0];
				$field['has_multiple_providers'] = count( $payment_providers ) > 1;
				$field['payment_providers']      = $payment_providers;
				$field['payment_provider']       = $first['provider'];
				$field['payment_provider_label'] = $first['provider_label'];
				$field['payment_merchant_name']  = $first['merchant_name'];
				$field['payment_merchant_phone'] = $first['merchant_phone'];
				$field['payment_amount']         = $first['payment_amount'];
				$field['payment_amount_display'] = $first['payment_amount_display'];
				$field['payment_currency']       = $currency;
				$field['payment_instructions']   = $first['payment_instructions'];
				$field['payment_iban']           = $first['payment_iban'];
				$field['payment_iban_display']   = $first['payment_iban_display'];
				$field['payment_bic']            = $first['payment_bic'];
				$field['payment_reference_prefix'] = $first['payment_reference_prefix'];
			}
			if ( $type === 'camera-photo-list' ) {
				$min_files                        = (int) ( $field['minFiles'] ?? 2 );
				$max_files                        = (int) ( $field['maxFiles'] ?? 5 );
				$field['min_files']               = $min_files;
				$field['max_files']               = $max_files;
				$field['photo_placeholder_slots'] = array_fill( 0, $max_files, null );
			} elseif ( $type === 'camera-photo' ) {
				$field['max_files'] = isset( $field['maxFiles'] ) ? (int) $field['maxFiles'] : null;
			}
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
	} elseif ( $current_status === 'done' && $status !== 'done' ) {
		delete_post_meta( $post_id, '_xpressui_done_at' );
	}
	if ( $status === 'pending_info' ) {
		update_post_meta( $post_id, '_xpressui_pending_info_at', current_time( 'mysql' ) );
		if ( $current_status !== 'pending_info' ) {
			xpressui_generate_resume_token( $post_id );
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

function xpressui_format_money( $raw, $currency = '', $format = 'amount-code' ) {
	if ( ! is_numeric( $raw ) ) {
		return '';
	}
	$amount = (float) $raw;
	$formatted = floor( $amount ) === $amount
		? number_format( $amount, 0, '.', ' ' )
		: number_format( $amount, 2, '.', ' ' );
	$currency = strtoupper( trim( (string) $currency ) );
	if ( $currency === '' ) {
		return $formatted;
	}
	$symbols = [
		'EUR' => '€',
		'USD' => '$',
		'GBP' => '£',
		'XOF' => 'F CFA',
		'XAF' => 'F CFA',
	];
	$symbol = $symbols[ $currency ] ?? $currency;
	switch ( (string) $format ) {
		case 'code-amount':
			return $currency . ' ' . $formatted;
		case 'amount-symbol':
			return $formatted . ' ' . $symbol;
		case 'symbol-amount':
			return $symbol . ' ' . $formatted;
		case 'amount-code':
		default:
			return $formatted . ' ' . $currency;
	}
}

function xpressui_render_product_list_value( $value, $field_meta = [] ) {
	if ( ! is_array( $value ) || empty( $value ) ) {
		return '<span class="xpressui-empty">' . esc_html__( 'Empty', 'xpressui-bridge' ) . '</span>';
	}
	$catalog_index  = xpressui_build_choice_catalog_index( $field_meta );
	$currency       = strtoupper( trim( (string) ( $field_meta['productCurrency'] ?? $field_meta['product_currency'] ?? $field_meta['paymentCurrency'] ?? $field_meta['payment_currency'] ?? 'EUR' ) ) );
	$amount_format  = (string) ( $field_meta['productAmountFormat'] ?? $field_meta['product_amount_format'] ?? 'amount-code' );
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
			'unit'     => is_numeric( $unit_amount ) ? xpressui_format_money( $unit_amount, $currency, $amount_format ) : '',
			'line'     => $line_amount !== null ? xpressui_format_money( $line_amount, $currency, $amount_format ) : '',
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
		$html .= '<div class="xpressui-value-header">' . esc_html( sprintf( __( 'Estimated total: %s', 'xpressui-bridge' ), xpressui_format_money( $total_amount, $currency, $amount_format ) ) ) . '</div>';
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

function xpressui_get_file_thumb_url( int $attachment_id ): string {
	if ( $attachment_id <= 0 || ! wp_attachment_is_image( $attachment_id ) ) {
		return '';
	}
	$src = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
	return $src ? (string) $src[0] : '';
}

function xpressui_render_file_entry_html( array $file ): string {
	$name          = (string) ( $file['originalName'] ?? $file['field'] ?? 'File' );
	$url           = (string) ( $file['url'] ?? '' );
	$attachment_id = (int) ( $file['attachmentId'] ?? 0 );
	$thumb_url     = xpressui_get_file_thumb_url( $attachment_id );

	if ( $url === '' ) {
		return esc_html( $name );
	}
	if ( $thumb_url !== '' ) {
		return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noreferrer" style="display:inline-flex;align-items:center;gap:6px;">'
			. '<img src="' . esc_url( $thumb_url ) . '" alt="' . esc_attr( $name ) . '" style="width:48px;height:36px;object-fit:cover;border-radius:3px;border:1px solid #ddd;flex-shrink:0;">'
			. '<span>' . esc_html( $name ) . '</span>'
			. '</a>';
	}
	return '&#128196; <a href="' . esc_url( $url ) . '" target="_blank" rel="noreferrer">' . esc_html( $name ) . '</a>';
}

function xpressui_render_file_list_html( array $files ): string {
	$images = [];
	$docs   = [];
	foreach ( $files as $file ) {
		$attachment_id = (int) ( $file['attachmentId'] ?? 0 );
		if ( xpressui_get_file_thumb_url( $attachment_id ) !== '' ) {
			$images[] = $file;
		} else {
			$docs[] = $file;
		}
	}

	$html = '';

	if ( ! empty( $images ) ) {
		$html .= '<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:' . ( $docs ? '10px' : '0' ) . ';">';
		foreach ( $images as $img ) {
			$name      = (string) ( $img['originalName'] ?? 'file' );
			$url       = (string) ( $img['url'] ?? '' );
			$thumb_url = xpressui_get_file_thumb_url( (int) ( $img['attachmentId'] ?? 0 ) );
			$html     .= '<div style="text-align:center;width:100px;">'
				. '<a href="' . esc_url( $url ) . '" target="_blank" rel="noreferrer" title="' . esc_attr( $name ) . '">'
				. '<img src="' . esc_url( $thumb_url ) . '" alt="' . esc_attr( $name ) . '" style="width:100px;height:75px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">'
				. '</a>'
				. '<div style="margin-top:4px;font-size:11px;line-height:1.3;word-break:break-all;">'
				. '<a href="' . esc_url( $url ) . '" target="_blank" rel="noreferrer">' . esc_html( $name ) . '</a>'
				. '</div>'
				. '</div>';
		}
		$html .= '</div>';
	}

	if ( ! empty( $docs ) ) {
		$html .= '<ul class="xpressui-file-list" style="margin:0;padding-left:0;list-style:none;">';
		foreach ( $docs as $doc ) {
			$name  = (string) ( $doc['originalName'] ?? $doc['field'] ?? 'File' );
			$url   = (string) ( $doc['url'] ?? '' );
			$html .= '<li style="margin-bottom:3px;">&#128196; '
				. ( $url !== '' ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noreferrer">' . esc_html( $name ) . '</a>' : esc_html( $name ) )
				. '</li>';
		}
		$html .= '</ul>';
	}

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
			return xpressui_render_file_entry_html( $value );
		}
		$is_list = array_keys( $value ) === range( 0, count( $value ) - 1 );
		if ( $is_list ) {
			$all_uploaded_files = ! empty( $value );
			foreach ( $value as $entry ) {
				if ( ! is_array( $entry ) || ( $entry['kind'] ?? '' ) !== 'uploaded-file' ) {
					$all_uploaded_files = false;
					break;
				}
			}
			if ( $all_uploaded_files ) {
				return xpressui_render_file_list_html( $value );
			}

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

function xpressui_render_preview_field_value( $value, array $field_meta ): string {
	$field_type = (string) ( $field_meta['type'] ?? '' );
	if ( $field_type === 'signature' && is_string( $value ) && $value !== '' ) {
		$is_data_uri = str_starts_with( $value, 'data:image/' );
		$src         = $is_data_uri ? esc_attr( $value ) : esc_url( $value );
		$img         = '<img src="' . $src . '" alt="' . esc_attr__( 'Signature', 'xpressui-bridge' ) . '" class="xpressui-signature-preview" />';
		$allowed     = [ 'img' => [ 'src' => true, 'alt' => true, 'class' => true ] ];
		return wp_kses( $img, $allowed, [ 'data', 'http', 'https' ] );
	}
	return wp_kses_post( xpressui_format_submission_value( $value, $field_meta ) );
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
