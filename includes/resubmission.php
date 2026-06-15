<?php
/**
 * Resubmission — request corrections (PRO).
 *
 * Lets an operator flag specific fields a submitter must correct, and builds the
 * self-service resume link delivered in the pending_info notification so the
 * submitter can re-open the form pre-filled and resubmit only the flagged
 * fields.
 *
 * This operator UI + resume-link building lived in the free plugin but was
 * removed during the wordpress.org review; re-introduced here in PRO (not on
 * wordpress.org). The resume MECHANICS stay in the free plugin and are reused
 * as-is: the resume token, the /resume REST endpoint, the partial-merge on
 * resubmit, the resume-mode form rendering, and the metabox save handler that
 * persists `xpressui_flagged_fields[]`. This module only reads submission DATA
 * (post_meta / pages) and renders the operator checkboxes — no free-plugin
 * function is called (no dormant code in free).
 *
 * NOTE: code-complete + lint-clean; requires a WordPress staging test of the
 * full loop (flag fields -> pending_info -> email link -> resume -> resubmit).
 *
 * @package XPressUI_Bridge_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the resume URL for a submission: the published page embedding the
 * workflow, with ?xpressui_resume=<token>. Self-contained (data + WP queries).
 */
function xpressui_pro_build_resume_url( int $post_id ): string {
	$token = (string) get_post_meta( $post_id, '_xpressui_resume_token', true );
	if ( '' === $token ) {
		return '';
	}
	$slug = sanitize_title( (string) get_post_meta( $post_id, '_xpressui_project_slug', true ) );
	if ( '' === $slug ) {
		return '';
	}
	$base_url = xpressui_pro_resolve_workflow_page_url( $slug );
	if ( '' === $base_url ) {
		return '';
	}
	return add_query_arg( 'xpressui_resume', rawurlencode( $token ), $base_url );
}

/**
 * Finds the published page that embeds the given workflow shortcode and returns
 * its permalink (short-cached). Data-only lookup over page content.
 */
function xpressui_pro_resolve_workflow_page_url( string $slug ): string {
	$slug = sanitize_title( $slug );
	if ( '' === $slug ) {
		return '';
	}
	$cache_key = 'xpressui_pro_wf_page_' . md5( $slug );
	$cached    = wp_cache_get( $cache_key, 'xpressui_bridge_pro' );
	if ( false !== $cached ) {
		return (string) $cached;
	}

	$url = '';
	foreach (
		get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => array( 'publish' ),
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'ASC',
			)
		) as $page
	) {
		$content = (string) ( $page->post_content ?? '' );
		if (
			false !== strpos( $content, '[xpressui id="' . $slug . '"' )
			|| false !== strpos( $content, "[xpressui id='" . $slug . "'" )
		) {
			$url = (string) get_permalink( (int) $page->ID );
			break;
		}
	}

	wp_cache_set( $cache_key, $url, 'xpressui_bridge_pro', MINUTE_IN_SECONDS );
	return $url;
}

/**
 * Submission payload field keys (data only).
 *
 * @return array<int,string>
 */
function xpressui_pro_submission_field_names( int $post_id ): array {
	$json    = (string) get_post_meta( $post_id, '_xpressui_payload_json', true );
	$payload = '' !== $json ? json_decode( $json, true ) : array();
	if ( ! is_array( $payload ) ) {
		return array();
	}
	$system = array( 'projectId', 'projectSlug', 'projectConfigVersion', 'submissionId', 'projectConfigSnapshotJson', 'rest_route' );
	$names  = array();
	foreach ( array_keys( $payload ) as $key ) {
		$key = (string) $key;
		if ( '' === $key || str_starts_with( $key, '_' ) || in_array( $key, $system, true ) ) {
			continue;
		}
		$names[] = $key;
	}
	return $names;
}

/**
 * Field types that are display/structural only (not submitter-correctable) and
 * are therefore hidden from the resubmission selector.
 *
 * @return array<int,string>
 */
function xpressui_pro_non_input_field_types(): array {
	return array(
		'section', 'title', 'html', 'output', 'form', 'content-block', 'rich-editor',
		'image', 'media', 'logo', 'hero', 'media-display', 'range-display',
		'link', 'btn', 'call2action', 'setting', 'approval-state', 'grid-size', 'slider',
	);
}

/**
 * Currently-flagged field names (data only).
 *
 * @return array<int,string>
 */
function xpressui_pro_get_flagged_fields( int $post_id ): array {
	$raw     = (string) get_post_meta( $post_id, '_xpressui_flagged_fields', true );
	$decoded = '' !== $raw ? json_decode( $raw, true ) : array();
	return is_array( $decoded ) ? array_values( array_filter( $decoded, 'is_string' ) ) : array();
}

/**
 * Reads the submission's stored config snapshot (data only): post_meta
 * `_xpressui_project_config_json`, then the shared config registry option.
 *
 * @return array<string,mixed>
 */
function xpressui_pro_read_config_snapshot( int $post_id ): array {
	$json = (string) get_post_meta( $post_id, '_xpressui_project_config_json', true );
	if ( '' !== trim( $json ) ) {
		$decoded = json_decode( $json, true );
		if ( is_array( $decoded ) ) {
			return $decoded;
		}
	}

	$registry = get_option( 'xpressui_project_config_registry', array() );
	if ( is_array( $registry ) ) {
		$version = (string) get_post_meta( $post_id, '_xpressui_project_config_version', true );
		$pid     = (string) get_post_meta( $post_id, '_xpressui_project_id', true );
		$slug    = (string) get_post_meta( $post_id, '_xpressui_project_slug', true );
		$key     = '' !== $version ? 'config:' . $version : ( '' !== $pid ? 'project:' . $pid : 'slug:' . $slug );
		$entry   = $registry[ $key ] ?? null;
		if ( is_array( $entry ) && is_array( $entry['config'] ?? null ) ) {
			return $entry['config'];
		}
	}

	return array();
}

/**
 * Groups the submission's fields by step/section (ordered as in the config),
 * with human labels. Payload fields not found in the config are listed under
 * "Other fields". Data-only.
 *
 * @return array<int,array{section:string,fields:array<int,array{name:string,label:string}>}>
 */
function xpressui_pro_grouped_submission_fields( int $post_id ): array {
	$payload_fields = xpressui_pro_submission_field_names( $post_id );
	$payload_set    = array_fill_keys( $payload_fields, true );

	$config     = xpressui_pro_read_config_snapshot( $post_id );
	$sections   = is_array( $config['sections'] ?? null ) ? $config['sections'] : array();
	$steps      = is_array( $sections['custom'] ?? null ) ? array_values( $sections['custom'] ) : array();
	$skip_types = xpressui_pro_non_input_field_types();

	$groups  = array();
	$covered = array();
	foreach ( $steps as $section ) {
		$section_name = is_array( $section ) ? (string) ( $section['name'] ?? '' ) : '';
		if ( '' === $section_name ) {
			continue;
		}
		$section_label = is_array( $section ) ? (string) ( $section['label'] ?? $section['adminLabel'] ?? $section['title'] ?? $section_name ) : $section_name;
		$fields        = is_array( $sections[ $section_name ] ?? null ) ? $sections[ $section_name ] : array();
		$items         = array();
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			$fname = (string) ( $field['name'] ?? '' );
			if ( '' === $fname || ! isset( $payload_set[ $fname ] ) ) {
				continue;
			}
			// Hide display/structural fields — they are not submitter-correctable.
			if ( in_array( (string) ( $field['type'] ?? '' ), $skip_types, true ) ) {
				$covered[ $fname ] = true;
				continue;
			}
			$items[]           = array(
				'name'  => $fname,
				'label' => (string) ( $field['label'] ?? $field['adminLabel'] ?? $field['title'] ?? $fname ),
			);
			$covered[ $fname ] = true;
		}
		if ( ! empty( $items ) ) {
			$groups[] = array(
				'section' => $section_label,
				'fields'  => $items,
			);
		}
	}

	$extras = array();
	foreach ( $payload_fields as $fname ) {
		if ( isset( $covered[ $fname ] ) ) {
			continue;
		}
		$extras[] = array(
			'name'  => $fname,
			'label' => $fname,
		);
	}
	if ( ! empty( $extras ) ) {
		$groups[] = array(
			'section' => __( 'Other fields', 'xpressui-bridge' ),
			'fields'  => $extras,
		);
	}

	return $groups;
}

add_action( 'add_meta_boxes', 'xpressui_pro_register_resubmission_metabox' );

/**
 * Registers the operator "request resubmission" metabox on submissions.
 */
function xpressui_pro_register_resubmission_metabox(): void {
	add_meta_box(
		'xpressui_pro_resubmission_mb',
		__( 'Request resubmission', 'xpressui-bridge' ),
		'xpressui_pro_render_resubmission_metabox',
		'xpressui_submission',
		'side',
		'high'
	);
}

/**
 * Renders per-field "needs correction" checkboxes. The inputs are named
 * xpressui_flagged_fields[] and are persisted by the free plugin's existing
 * submission save handler (which runs on Update under its own nonce).
 *
 * @param WP_Post|object $post
 */
function xpressui_pro_render_resubmission_metabox( $post ): void {
	$post_id = (int) ( is_object( $post ) ? ( $post->ID ?? 0 ) : 0 );
	$flagged = xpressui_pro_get_flagged_fields( $post_id );
	$groups  = xpressui_pro_grouped_submission_fields( $post_id );

	echo '<p class="description">'
		. esc_html__( 'Tick the fields the submitter must correct, set status to “Pending info”, then click Update. The submitter gets a resume link to fix only those fields.', 'xpressui-bridge' )
		. '</p>';

	if ( empty( $groups ) ) {
		echo '<p>' . esc_html__( 'No fields available for this submission yet.', 'xpressui-bridge' ) . '</p>';
		return;
	}

	echo '<div class="xpressui-pro-flagged-fields" style="max-height:300px;overflow:auto;">';
	foreach ( $groups as $group ) {
		echo '<p style="margin:12px 0 4px;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.04em;color:#646970;">'
			. esc_html( $group['section'] ) . '</p>';
		foreach ( $group['fields'] as $item ) {
			$checked = in_array( $item['name'], $flagged, true );
			echo '<label style="display:block;margin:4px 0;">';
			echo '<input type="checkbox" name="xpressui_flagged_fields[]" value="' . esc_attr( $item['name'] ) . '" ' . checked( $checked, true, false ) . ' /> ';
			echo esc_html( $item['label'] );
			echo '</label>';
		}
	}
	echo '</div>';

	$resume_url = xpressui_pro_build_resume_url( $post_id );
	if ( '' !== $resume_url ) {
		echo '<p class="description" style="margin-top:8px;">'
			. esc_html__( 'Resume link', 'xpressui-bridge' ) . ': '
			. '<a href="' . esc_url( $resume_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'open', 'xpressui-bridge' ) . '</a>'
			. '</p>';
	}
}
