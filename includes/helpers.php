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
// Status helpers
// ---------------------------------------------------------------------------

function xpressui_get_status_options() {
	return [
		'new'       => __( 'New', 'xpressui-bridge' ),
		'in-review' => __( 'In review', 'xpressui-bridge' ),
		'done'      => __( 'Done', 'xpressui-bridge' ),
	];
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
	return is_array( $entry['config'] ?? null ) ? $entry['config'] : [];
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
	$index           = [];
	$sections        = is_array( $config['sections'] ?? null ) ? $config['sections'] : [];
	$custom_sections = is_array( $sections['custom'] ?? null ) ? $sections['custom'] : [];

	foreach ( $custom_sections as $section ) {
		if ( ! is_array( $section ) ) {
			continue;
		}
		$section_name  = (string) ( $section['name'] ?? '' );
		if ( $section_name === '' ) {
			continue;
		}
		$section_label = (string) ( $section['label'] ?? $section['title'] ?? $section_name );
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
				'label'        => (string) ( $field['label'] ?? $field['adminLabel'] ?? $field_name ),
				'sectionLabel' => $section_label,
				'type'         => (string) ( $field['type'] ?? '' ),
				'choices'      => xpressui_build_field_choice_map( $field ),
				'choiceCatalog' => is_array( $field['choices'] ?? null ) ? array_values( $field['choices'] ) : [],
			];
		}
	}
	return $index;
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
