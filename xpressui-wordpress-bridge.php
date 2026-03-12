<?php
/**
 * Plugin Name: XPressUI WordPress Bridge
 * Description: Development bridge for receiving exported XPressUI workflow submissions.
 * Version: 0.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

add_action('init', 'xpressui_register_submission_post_type');
add_action('rest_api_init', 'xpressui_register_rest_routes');
add_action('restrict_manage_posts', 'xpressui_render_submission_filters');
add_action('pre_get_posts', 'xpressui_apply_submission_filters');
add_action('save_post_xpressui_submission', 'xpressui_save_submission_status');
add_action('admin_init', 'xpressui_handle_submission_status_action');
add_filter('post_row_actions', 'xpressui_add_submission_row_actions', 10, 2);
add_action('admin_menu', 'xpressui_register_submission_admin_pages');

function xpressui_register_submission_post_type() {
    register_post_type('xpressui_submission', [
        'public' => false,
        'show_ui' => true,
        'label' => 'XPressUI Submissions',
        'supports' => ['title'],
    ]);
}

function xpressui_register_rest_routes() {
    register_rest_route('xpressui/v1', '/submit', [
        'methods' => 'POST',
        'callback' => 'xpressui_handle_submission',
        'permission_callback' => '__return_true',
    ]);
}

add_filter('manage_xpressui_submission_posts_columns', function ($columns) {
    $columns['xpressui_project_id'] = 'Project ID';
    $columns['xpressui_project_slug'] = 'Project';
    $columns['xpressui_submission_status'] = 'Status';
    $columns['xpressui_submission_contact'] = 'Contact';
    $columns['xpressui_submission_files_count'] = 'Files';
    $columns['xpressui_submission_id'] = 'Submission ID';
    return $columns;
});

add_action('manage_xpressui_submission_posts_custom_column', function ($column, $post_id) {
    if ($column === 'xpressui_project_id') {
        echo esc_html((string) get_post_meta($post_id, '_xpressui_project_id', true));
        return;
    }
    if ($column === 'xpressui_project_slug') {
        echo esc_html((string) get_post_meta($post_id, '_xpressui_project_slug', true));
        return;
    }
    if ($column === 'xpressui_submission_status') {
        echo esc_html(xpressui_get_submission_status_label((string) get_post_meta($post_id, '_xpressui_submission_status', true)));
        return;
    }
    if ($column === 'xpressui_submission_contact') {
        $payload = xpressui_get_submission_payload($post_id);
        $summary = xpressui_get_submission_contact_summary($payload);
        echo esc_html($summary !== '' ? $summary : 'No contact details');
        return;
    }
    if ($column === 'xpressui_submission_files_count') {
        echo esc_html((string) xpressui_get_uploaded_file_count($post_id));
        return;
    }
    if ($column === 'xpressui_submission_id') {
        echo esc_html((string) get_post_meta($post_id, '_xpressui_submission_id', true));
    }
}, 10, 2);

add_action('add_meta_boxes', function () {
    add_meta_box('xpressui_submission_status', 'Submission Workflow', 'xpressui_render_submission_status_metabox', 'xpressui_submission', 'side', 'high');
    add_meta_box('xpressui_submission_summary', 'Submission Summary', 'xpressui_render_submission_summary_metabox', 'xpressui_submission', 'side', 'high');
    add_meta_box('xpressui_submission_review', 'Review Notes', 'xpressui_render_submission_review_metabox', 'xpressui_submission', 'normal', 'default');
    add_meta_box('xpressui_submission_history', 'Status History', 'xpressui_render_submission_history_metabox', 'xpressui_submission', 'side', 'default');
    add_meta_box('xpressui_submission_preview', 'Submission Preview', 'xpressui_render_submission_preview_metabox', 'xpressui_submission', 'normal', 'high');
    add_meta_box('xpressui_submission_payload', 'Submission Payload', 'xpressui_render_submission_payload_metabox', 'xpressui_submission', 'normal', 'default');
    add_meta_box('xpressui_submission_files', 'Uploaded Files', 'xpressui_render_submission_files_metabox', 'xpressui_submission', 'side', 'default');
});

function xpressui_get_submission_status_options() {
    return [
        'new' => 'New',
        'in-review' => 'In review',
        'done' => 'Done',
    ];
}

function xpressui_get_submission_status_label($status) {
    $options = xpressui_get_submission_status_options();
    return $options[$status] ?? $options['new'];
}

function xpressui_get_submission_payload($post_id) {
    $payload_json = get_post_meta($post_id, '_xpressui_payload_json', true);
    $payload = $payload_json ? json_decode($payload_json, true) : [];
    return is_array($payload) ? $payload : [];
}

function xpressui_normalize_project_config_snapshot($raw_config) {
    if (is_string($raw_config) && trim($raw_config) !== '') {
        $decoded = json_decode($raw_config, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return is_array($raw_config) ? $raw_config : [];
}

function xpressui_store_project_config_snapshot($project_id, $project_slug, $project_config_version, $config) {
    $normalized = xpressui_normalize_project_config_snapshot($config);
    if (empty($normalized)) {
        return [];
    }

    $registry = get_option('xpressui_project_config_registry', []);
    if (!is_array($registry)) {
        $registry = [];
    }

    $registry_key = $project_config_version !== '' ? 'config:' . $project_config_version : ($project_id !== '' ? 'project:' . $project_id : 'slug:' . $project_slug);
    $registry[$registry_key] = [
        'projectId' => $project_id,
        'projectSlug' => $project_slug,
        'projectConfigVersion' => $project_config_version,
        'storedAt' => current_time('mysql'),
        'config' => $normalized,
    ];

    update_option('xpressui_project_config_registry', $registry, false);
    return $normalized;
}

function xpressui_get_project_config_snapshot($post_id) {
    $stored_json = get_post_meta($post_id, '_xpressui_project_config_json', true);
    if (is_string($stored_json) && trim($stored_json) !== '') {
        $stored = json_decode($stored_json, true);
        if (is_array($stored)) {
            return $stored;
        }
    }

    $registry = get_option('xpressui_project_config_registry', []);
    if (!is_array($registry)) {
        $registry = [];
    }

    $project_id = (string) get_post_meta($post_id, '_xpressui_project_id', true);
    $project_slug = (string) get_post_meta($post_id, '_xpressui_project_slug', true);
    $project_config_version = (string) get_post_meta($post_id, '_xpressui_project_config_version', true);
    $registry_key = $project_config_version !== '' ? 'config:' . $project_config_version : ($project_id !== '' ? 'project:' . $project_id : 'slug:' . $project_slug);
    $entry = $registry[$registry_key] ?? null;
    return is_array($entry['config'] ?? null) ? $entry['config'] : [];
}

function xpressui_build_field_choice_map($field) {
    $choice_map = [];
    $choices = is_array($field['choices'] ?? null) ? $field['choices'] : [];
    foreach ($choices as $choice) {
        if (!is_array($choice)) {
            continue;
        }
        $label = (string) ($choice['label'] ?? $choice['name'] ?? $choice['value'] ?? '');
        foreach (['value', 'name', 'label'] as $choice_key) {
            $raw_value = $choice[$choice_key] ?? null;
            if ($raw_value === null || $raw_value === '') {
                continue;
            }
            $choice_map[(string) $raw_value] = $label !== '' ? $label : (string) $raw_value;
        }
    }
    return $choice_map;
}

function xpressui_build_structured_item_summary($item, $choice_map = []) {
    if (!is_array($item)) {
        $normalized = (string) $item;
        return $choice_map[$normalized] ?? $normalized;
    }

    $parts = [];
    $primary = '';
    foreach (['label', 'title', 'name', 'value', 'id'] as $candidate_key) {
        $candidate_value = $item[$candidate_key] ?? null;
        if ($candidate_value === null || is_array($candidate_value) || is_object($candidate_value)) {
            continue;
        }
        $normalized = (string) $candidate_value;
        if ($normalized === '') {
            continue;
        }
        $primary = $choice_map[$normalized] ?? $normalized;
        break;
    }
    if ($primary !== '') {
        $parts[] = $primary;
    }
    foreach ([
        'quantity' => 'qty',
        'price' => 'price',
        'amount' => 'amount',
        'score' => 'score',
        'result' => 'result',
        'answer' => 'answer',
        'selected' => 'selected',
        'correct' => 'correct',
    ] as $key => $label) {
        $raw_value = $item[$key] ?? null;
        if ($raw_value === null || is_array($raw_value) || is_object($raw_value) || $raw_value === '') {
            continue;
        }
        if (is_bool($raw_value)) {
            $parts[] = $label . ': ' . ($raw_value ? 'yes' : 'no');
            continue;
        }
        $parts[] = $label . ': ' . (string) $raw_value;
    }
    if (empty($parts)) {
        return wp_json_encode($item, JSON_UNESCAPED_SLASHES);
    }
    return implode(' · ', $parts);
}

function xpressui_build_config_field_index($config) {
    $index = [];
    $sections = is_array($config['sections'] ?? null) ? $config['sections'] : [];
    $custom_sections = is_array($sections['custom'] ?? null) ? $sections['custom'] : [];

    foreach ($custom_sections as $section) {
        if (!is_array($section)) {
            continue;
        }
        $section_name = (string) ($section['name'] ?? '');
        if ($section_name === '') {
            continue;
        }
        $section_label = (string) ($section['label'] ?? $section['title'] ?? $section_name);
        $fields = is_array($sections[$section_name] ?? null) ? $sections[$section_name] : [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }
            $field_name = (string) ($field['name'] ?? '');
            if ($field_name === '') {
                continue;
            }
            $index[$field_name] = [
                'label' => (string) ($field['label'] ?? $field['adminLabel'] ?? $field_name),
                'sectionLabel' => $section_label,
                'type' => (string) ($field['type'] ?? ''),
                'choices' => xpressui_build_field_choice_map($field),
                'choiceCatalog' => is_array($field['choices'] ?? null) ? array_values($field['choices']) : [],
            ];
        }
    }

    return $index;
}

function xpressui_build_choice_catalog_index($field_meta = []) {
    $catalog = is_array($field_meta['choiceCatalog'] ?? null) ? $field_meta['choiceCatalog'] : [];
    $index = [];
    foreach ($catalog as $position => $choice) {
        if (!is_array($choice)) {
            continue;
        }
        $id = (string) ($choice['value'] ?? $choice['id'] ?? ('choice_' . ($position + 1)));
        if ($id === '') {
            continue;
        }
        $index[$id] = $choice;
    }
    return $index;
}

function xpressui_format_money_amount($raw_amount) {
    if (!is_numeric($raw_amount)) {
        return '';
    }
    $amount = (float) $raw_amount;
    if (floor($amount) === $amount) {
        return (string) ((int) $amount);
    }
    return number_format($amount, 2, '.', ' ');
}

function xpressui_render_product_list_value($value, $field_meta = []) {
    if (!is_array($value) || empty($value)) {
        return '<span style="opacity:0.6;">Empty</span>';
    }

    $catalog_index = xpressui_build_choice_catalog_index($field_meta);
    $rows = [];
    $total_quantity = 0;
    $total_amount = 0.0;

    foreach ($value as $position => $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $entry_id = (string) ($entry['id'] ?? $entry['value'] ?? ('item_' . ($position + 1)));
        $catalog_entry = $catalog_index[$entry_id] ?? [];
        $name = (string) ($entry['name'] ?? $entry['label'] ?? $catalog_entry['name'] ?? $catalog_entry['label'] ?? $entry_id);
        $quantity = max(1, (int) ($entry['quantity'] ?? 1));
        $unit_amount = $entry['discount_price'] ?? $entry['sale_price'] ?? $catalog_entry['discount_price'] ?? $catalog_entry['discountPrice'] ?? $catalog_entry['sale_price'] ?? $catalog_entry['salePrice'] ?? null;
        $line_amount = is_numeric($unit_amount) ? ((float) $unit_amount * $quantity) : null;
        $total_quantity += $quantity;
        if ($line_amount !== null) {
            $total_amount += $line_amount;
        }
        $price_label = is_numeric($unit_amount) ? xpressui_format_money_amount($unit_amount) : '';
        $line_label = $line_amount !== null ? xpressui_format_money_amount($line_amount) : '';
        $rows[] = [
            'name' => $name,
            'quantity' => $quantity,
            'unit' => $price_label,
            'line' => $line_label,
        ];
    }

    if (empty($rows)) {
        return '<span style="opacity:0.6;">Empty</span>';
    }

    $html = '<div>';
    $html .= '<div style="margin:0 0 8px;font-weight:600;">' . esc_html(count($rows) . ' selected item' . (count($rows) > 1 ? 's' : '') . ' · qty ' . $total_quantity) . '</div>';
    $html .= '<table class="widefat striped" style="margin:0;"><thead><tr><th>Item</th><th style="width:90px;">Qty</th><th style="width:120px;">Unit</th><th style="width:120px;">Total</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        $html .= '<tr>';
        $html .= '<td>' . esc_html($row['name']) . '</td>';
        $html .= '<td>' . esc_html((string) $row['quantity']) . '</td>';
        $html .= '<td>' . ($row['unit'] !== '' ? esc_html($row['unit']) : '<span style="opacity:0.6;">-</span>') . '</td>';
        $html .= '<td>' . ($row['line'] !== '' ? esc_html($row['line']) : '<span style="opacity:0.6;">-</span>') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    if ($total_amount > 0) {
        $html .= '<div style="margin-top:8px;font-weight:600;">Estimated total: ' . esc_html(xpressui_format_money_amount($total_amount)) . '</div>';
    }
    $html .= '</div>';

    return $html;
}

function xpressui_render_quiz_value($value, $field_meta = []) {
    if (!is_array($value) || empty($value)) {
        return '<span style="opacity:0.6;">Empty</span>';
    }

    $catalog_index = xpressui_build_choice_catalog_index($field_meta);
    $items = [];
    foreach ($value as $position => $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $entry_id = (string) ($entry['id'] ?? $entry['value'] ?? ('answer_' . ($position + 1)));
        $catalog_entry = $catalog_index[$entry_id] ?? [];
        $name = (string) ($entry['name'] ?? $entry['label'] ?? $catalog_entry['name'] ?? $catalog_entry['label'] ?? $entry_id);
        $desc = (string) ($entry['desc'] ?? $catalog_entry['desc'] ?? '');
        $items[] = [
            'name' => $name,
            'desc' => $desc,
        ];
    }

    if (empty($items)) {
        return '<span style="opacity:0.6;">Empty</span>';
    }

    $html = '<div>';
    $html .= '<div style="margin:0 0 8px;font-weight:600;">' . esc_html(count($items) . ' selected answer' . (count($items) > 1 ? 's' : '')) . '</div>';
    $html .= '<ul style="margin:0;padding-left:18px;">';
    foreach ($items as $item) {
        $html .= '<li style="margin:0 0 6px;">';
        $html .= '<strong>' . esc_html($item['name']) . '</strong>';
        if ($item['desc'] !== '') {
            $html .= '<div style="opacity:0.8;">' . esc_html($item['desc']) . '</div>';
        }
        $html .= '</li>';
    }
    $html .= '</ul></div>';

    return $html;
}

function xpressui_render_image_gallery_value($value, $field_meta = []) {
    if (!is_array($value) || empty($value)) {
        return '<span style="opacity:0.6;">Empty</span>';
    }

    $catalog_index = xpressui_build_choice_catalog_index($field_meta);
    $items = [];
    foreach ($value as $position => $entry) {
        if (!is_array($entry)) {
            continue;
        }
        $entry_id = (string) ($entry['id'] ?? $entry['value'] ?? ('image_' . ($position + 1)));
        $catalog_entry = $catalog_index[$entry_id] ?? [];
        $name = (string) ($entry['name'] ?? $entry['label'] ?? $catalog_entry['name'] ?? $catalog_entry['label'] ?? $entry_id);
        $thumbnail = (string) ($entry['image_thumbnail'] ?? $catalog_entry['image_thumbnail'] ?? $catalog_entry['imageThumbnail'] ?? '');
        $full_url = (string) ($entry['image_medium'] ?? $catalog_entry['image_medium'] ?? $catalog_entry['imageMedium'] ?? $thumbnail);
        $items[] = [
            'name' => $name,
            'thumbnail' => $thumbnail,
            'fullUrl' => $full_url,
        ];
    }

    if (empty($items)) {
        return '<span style="opacity:0.6;">Empty</span>';
    }

    $html = '<div>';
    $html .= '<div style="margin:0 0 8px;font-weight:600;">' . esc_html(count($items) . ' selected image' . (count($items) > 1 ? 's' : '')) . '</div>';
    $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;">';
    foreach ($items as $item) {
        $html .= '<div style="border:1px solid rgba(15,23,42,0.12);border-radius:12px;padding:10px;background:#fff;">';
        if ($item['thumbnail'] !== '') {
            $image = '<img src="' . esc_url($item['thumbnail']) . '" alt="' . esc_attr($item['name']) . '" style="display:block;width:100%;height:120px;object-fit:cover;border-radius:8px;margin-bottom:8px;" />';
            if ($item['fullUrl'] !== '') {
                $html .= '<a href="' . esc_url($item['fullUrl']) . '" target="_blank" rel="noreferrer">' . $image . '</a>';
            } else {
                $html .= $image;
            }
        }
        $html .= '<div style="font-weight:600;">' . esc_html($item['name']) . '</div>';
        $html .= '</div>';
    }
    $html .= '</div></div>';

    return $html;
}

function xpressui_render_scalar_badge($label, $tone = 'neutral') {
    $styles = [
        'neutral' => 'background:#f8fafc;color:#0f172a;border-color:rgba(15,23,42,0.12);',
        'success' => 'background:#dcfce7;color:#166534;border-color:#86efac;',
        'muted' => 'background:#f1f5f9;color:#475569;border-color:#cbd5e1;',
    ];
    $style = $styles[$tone] ?? $styles['neutral'];
    return '<span style="display:inline-flex;align-items:center;padding:2px 10px;border:1px solid;border-radius:999px;font-size:12px;font-weight:600;' . $style . '">' . esc_html($label) . '</span>';
}

function xpressui_get_section_capture_summary($fields, $payload) {
    $filled_count = 0;
    $interactive_count = 0;
    foreach ($fields as $field_name => $field_meta) {
        $raw_value = $payload[$field_name] ?? null;
        if ($raw_value !== null && $raw_value !== '' && $raw_value !== []) {
            $filled_count += 1;
        }
        if (in_array((string) ($field_meta['type'] ?? ''), ['product-list', 'quiz', 'image-gallery', 'select-one', 'select-multiple', 'radio-buttons', 'checkboxes'], true)) {
            $interactive_count += 1;
        }
    }
    $field_count = count($fields);
    $status = 'empty';
    if ($filled_count > 0 && $filled_count < $field_count) {
        $status = 'partial';
    } elseif ($field_count > 0 && $filled_count >= $field_count) {
        $status = 'complete';
    }
    return [
        'filledCount' => $filled_count,
        'fieldCount' => $field_count,
        'interactiveCount' => $interactive_count,
        'status' => $status,
    ];
}

function xpressui_format_submission_value($value, $field_meta = []) {
    $field_type = (string) ($field_meta['type'] ?? '');
    $choice_map = is_array($field_meta['choices'] ?? null) ? $field_meta['choices'] : [];
    $map_choice = static function ($raw_value) use ($choice_map) {
        $normalized_value = (string) $raw_value;
        return $choice_map[$normalized_value] ?? $normalized_value;
    };
    if ($field_type === 'product-list') {
        return xpressui_render_product_list_value($value, $field_meta);
    }
    if ($field_type === 'quiz') {
        return xpressui_render_quiz_value($value, $field_meta);
    }
    if ($field_type === 'image-gallery') {
        return xpressui_render_image_gallery_value($value, $field_meta);
    }
    if (is_array($value)) {
        if (($value['kind'] ?? '') === 'uploaded-file') {
            $original_name = (string) ($value['originalName'] ?? $value['field'] ?? 'File');
            $url = (string) ($value['url'] ?? '');
            if ($url !== '') {
                return '<a href="' . esc_url($url) . '" target="_blank" rel="noreferrer">' . esc_html($original_name) . '</a>';
            }
            return esc_html($original_name);
        }
        $is_list = array_keys($value) === range(0, count($value) - 1);
        if ($is_list) {
            $formatted_values = [];
            foreach ($value as $item) {
                if (is_scalar($item) || $item === null) {
                    $formatted_values[] = xpressui_render_scalar_badge($map_choice($item));
                    continue;
                }
                $formatted_values[] = xpressui_build_structured_item_summary($item, $choice_map);
            }
            return implode(' ', array_filter($formatted_values, static fn ($item) => $item !== ''));
        }
        return '<pre style="white-space:pre-wrap;margin:0;">' . esc_html(wp_json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
    }
    if (is_bool($value)) {
        return xpressui_render_scalar_badge($value ? 'Yes' : 'No', $value ? 'success' : 'muted');
    }
    if ($value === null || $value === '') {
        return '<span style="opacity:0.6;">Empty</span>';
    }
    if (!empty($choice_map)) {
        return xpressui_render_scalar_badge($map_choice($value));
    }
    return esc_html($map_choice($value));
}

function xpressui_get_submission_contact_summary($payload) {
    if (!is_array($payload)) {
        return '';
    }

    $full_name = trim((string) ($payload['fullName'] ?? ''));
    $first_name = trim((string) ($payload['firstName'] ?? $payload['firstname'] ?? ''));
    $last_name = trim((string) ($payload['lastName'] ?? $payload['lastname'] ?? ''));
    $email = trim((string) ($payload['email'] ?? ''));
    $phone = trim((string) ($payload['phone'] ?? $payload['phoneNumber'] ?? ''));

    if ($full_name !== '') {
        return $full_name;
    }
    if ($first_name !== '' || $last_name !== '') {
        return trim($first_name . ' ' . $last_name);
    }
    if ($email !== '') {
        return $email;
    }
    if ($phone !== '') {
        return $phone;
    }

    return '';
}

function xpressui_get_uploaded_file_count($post_id) {
    $files_json = get_post_meta($post_id, '_xpressui_uploaded_files', true);
    $files = $files_json ? json_decode($files_json, true) : [];
    return is_array($files) ? count($files) : 0;
}

function xpressui_get_status_history($post_id) {
    $history_json = get_post_meta($post_id, '_xpressui_status_history', true);
    $history = $history_json ? json_decode($history_json, true) : [];
    return is_array($history) ? $history : [];
}

function xpressui_append_status_history($post_id, $status, $note = '') {
    $history = xpressui_get_status_history($post_id);
    $user = function_exists('wp_get_current_user') ? wp_get_current_user() : null;
    $actor = ($user && !empty($user->user_login)) ? (string) $user->user_login : 'system';
    $history[] = [
        'status' => $status,
        'note' => trim((string) $note),
        'at' => current_time('mysql'),
        'actor' => $actor,
    ];
    update_post_meta($post_id, '_xpressui_status_history', wp_json_encode($history));
}

function xpressui_set_submission_status($post_id, $status, $note = '') {
    $current_status = (string) get_post_meta($post_id, '_xpressui_submission_status', true);
    $current_note = (string) get_post_meta($post_id, '_xpressui_review_note', true);
    $normalized_note = trim((string) $note);
    update_post_meta($post_id, '_xpressui_submission_status', $status);
    update_post_meta($post_id, '_xpressui_review_note', $normalized_note);

    if ($current_status !== $status || $current_note !== $normalized_note) {
        xpressui_append_status_history($post_id, $status, $normalized_note);
    }

}

function xpressui_register_submission_admin_pages() {
    add_submenu_page(
        'edit.php?post_type=xpressui_submission',
        'Project Inbox',
        'Project Inbox',
        'edit_posts',
        'xpressui-project-inbox',
        'xpressui_render_project_inbox_page',
    );
}

function xpressui_get_project_inbox_rows() {
    $submission_ids = get_posts([
        'post_type' => 'xpressui_submission',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'orderby' => 'date',
        'order' => 'DESC',
    ]);
    $rows = [];
    foreach ($submission_ids as $submission_id) {
        $project_slug = (string) get_post_meta($submission_id, '_xpressui_project_slug', true);
        $status = (string) get_post_meta($submission_id, '_xpressui_submission_status', true);
        if ($project_slug === '') {
            $project_slug = 'unknown-project';
        }
        if ($status === '') {
            $status = 'new';
        }
        if (!isset($rows[$project_slug])) {
            $rows[$project_slug] = [
                'projectSlug' => $project_slug,
                'total' => 0,
                'new' => 0,
                'in-review' => 0,
                'done' => 0,
                'latestSubmissionId' => '',
                'latestDate' => '',
            ];
        }
        $rows[$project_slug]['total'] += 1;
        if (isset($rows[$project_slug][$status])) {
            $rows[$project_slug][$status] += 1;
        }
        if ($rows[$project_slug]['latestSubmissionId'] === '') {
            $rows[$project_slug]['latestSubmissionId'] = (string) get_post_meta($submission_id, '_xpressui_submission_id', true);
            $rows[$project_slug]['latestDate'] = get_the_date('Y-m-d H:i', $submission_id) ?: '';
        }
    }

    ksort($rows);
    return array_values($rows);
}

function xpressui_render_project_inbox_page() {
    if (!current_user_can('edit_posts')) {
        return;
    }

    $rows = xpressui_get_project_inbox_rows();

    echo '<div class="wrap">';
    echo '<h1>Project Inbox</h1>';
    echo '<p>Review incoming submissions grouped by project, then jump into filtered queues.</p>';

    if (empty($rows)) {
        echo '<p>No submissions recorded yet.</p>';
        echo '</div>';
        return;
    }

    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>Project</th><th>Total</th><th>New</th><th>In review</th><th>Done</th><th>Latest submission</th><th>Actions</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        $all_url = add_query_arg([
            'post_type' => 'xpressui_submission',
            'xpressui_project' => $row['projectSlug'],
        ], admin_url('edit.php'));
        $new_url = add_query_arg([
            'post_type' => 'xpressui_submission',
            'xpressui_project' => $row['projectSlug'],
            'xpressui_status' => 'new',
        ], admin_url('edit.php'));
        echo '<tr>';
        echo '<td><strong>' . esc_html($row['projectSlug']) . '</strong></td>';
        echo '<td>' . esc_html((string) $row['total']) . '</td>';
        echo '<td>' . esc_html((string) $row['new']) . '</td>';
        echo '<td>' . esc_html((string) $row['in-review']) . '</td>';
        echo '<td>' . esc_html((string) $row['done']) . '</td>';
        echo '<td>' . esc_html($row['latestSubmissionId'] !== '' ? $row['latestSubmissionId'] . ' · ' . $row['latestDate'] : 'No submissions yet') . '</td>';
        echo '<td><a href="' . esc_url($all_url) . '">Open all</a> · <a href="' . esc_url($new_url) . '">Open new</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

function xpressui_render_submission_filters($post_type) {
    if ($post_type !== 'xpressui_submission') {
        return;
    }

    $selected_status = isset($_GET['xpressui_status']) ? sanitize_text_field((string) $_GET['xpressui_status']) : '';
    $selected_project = isset($_GET['xpressui_project']) ? sanitize_text_field((string) $_GET['xpressui_project']) : '';
    $submission_ids = get_posts([
        'post_type' => 'xpressui_submission',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_key' => '_xpressui_project_slug',
        'orderby' => 'meta_value',
        'order' => 'ASC',
    ]);
    $projects = [];
    foreach ($submission_ids as $submission_id) {
        $project_slug = (string) get_post_meta($submission_id, '_xpressui_project_slug', true);
        if ($project_slug !== '') {
            $projects[$project_slug] = $project_slug;
        }
    }

    echo '<select name="xpressui_status">';
    echo '<option value="">All statuses</option>';
    foreach (xpressui_get_submission_status_options() as $value => $label) {
        echo '<option value="' . esc_attr($value) . '"' . selected($selected_status, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';

    echo '<select name="xpressui_project">';
    echo '<option value="">All projects</option>';
    foreach ($projects as $value => $label) {
        echo '<option value="' . esc_attr($value) . '"' . selected($selected_project, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
}

function xpressui_apply_submission_filters($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    if (($query->get('post_type') ?: '') !== 'xpressui_submission') {
        return;
    }

    $meta_query = $query->get('meta_query');
    if (!is_array($meta_query)) {
        $meta_query = [];
    }

    if (!empty($_GET['xpressui_status'])) {
        $meta_query[] = [
            'key' => '_xpressui_submission_status',
            'value' => sanitize_text_field((string) $_GET['xpressui_status']),
        ];
    }

    if (!empty($_GET['xpressui_project'])) {
        $meta_query[] = [
            'key' => '_xpressui_project_slug',
            'value' => sanitize_text_field((string) $_GET['xpressui_project']),
        ];
    }

    if (!empty($meta_query)) {
        $query->set('meta_query', $meta_query);
    }
}

function xpressui_render_submission_summary_metabox($post) {
    $payload = xpressui_get_submission_payload($post->ID);

    $project_id = (string) get_post_meta($post->ID, '_xpressui_project_id', true);
    $project_slug = (string) get_post_meta($post->ID, '_xpressui_project_slug', true);
    $submission_id = (string) get_post_meta($post->ID, '_xpressui_submission_id', true);
    $status = (string) get_post_meta($post->ID, '_xpressui_submission_status', true);
    $email = trim((string) ($payload['email'] ?? ''));
    $phone = trim((string) ($payload['phone'] ?? $payload['phoneNumber'] ?? ''));
    $contact_name = xpressui_get_submission_contact_summary($payload);

    echo '<dl style="margin:0;">';
    echo '<dt><strong>Status</strong></dt><dd style="margin:0 0 8px;">' . esc_html(xpressui_get_submission_status_label($status)) . '</dd>';
    echo '<dt><strong>Project</strong></dt><dd style="margin:0 0 8px;">' . esc_html($project_slug !== '' ? $project_slug : 'unknown-project') . '</dd>';
    echo '<dt><strong>Project ID</strong></dt><dd style="margin:0 0 8px;word-break:break-all;">' . esc_html($project_id !== '' ? $project_id : 'not recorded') . '</dd>';
    echo '<dt><strong>Submission ID</strong></dt><dd style="margin:0 0 8px;word-break:break-all;">' . esc_html($submission_id !== '' ? $submission_id : 'not recorded') . '</dd>';
    if ($contact_name !== '') {
        echo '<dt><strong>Contact</strong></dt><dd style="margin:0 0 8px;">' . esc_html($contact_name) . '</dd>';
    }
    if ($email !== '') {
        echo '<dt><strong>Email</strong></dt><dd style="margin:0 0 8px;"><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></dd>';
    }
    if ($phone !== '') {
        echo '<dt><strong>Phone</strong></dt><dd style="margin:0 0 8px;"><a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a></dd>';
    }
    echo '</dl>';
}

function xpressui_render_submission_status_metabox($post) {
    $current_status = (string) get_post_meta($post->ID, '_xpressui_submission_status', true);
    if ($current_status === '') {
        $current_status = 'new';
    }

    wp_nonce_field('xpressui_save_submission_status', 'xpressui_submission_status_nonce');

    echo '<label for="xpressui_submission_status"><strong>Operator status</strong></label><br />';
    echo '<select id="xpressui_submission_status" name="xpressui_submission_status" style="width:100%;margin-top:8px;">';
    foreach (xpressui_get_submission_status_options() as $value => $label) {
        echo '<option value="' . esc_attr($value) . '"' . selected($current_status, $value, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    echo '<p style="margin-top:8px;opacity:0.8;">Use Update to save the new status.</p>';
}

function xpressui_render_submission_review_metabox($post) {
    $note = (string) get_post_meta($post->ID, '_xpressui_review_note', true);
    echo '<label for="xpressui_review_note"><strong>Operator notes</strong></label>';
    echo '<textarea id="xpressui_review_note" name="xpressui_review_note" rows="5" style="width:100%;margin-top:8px;">' . esc_textarea($note) . '</textarea>';
    echo '<p style="margin-top:8px;opacity:0.8;">Saved with the current status and added to the review history when changed.</p>';
}

function xpressui_render_submission_history_metabox($post) {
    $history = array_reverse(xpressui_get_status_history($post->ID));
    if (empty($history)) {
        echo '<p>No review history recorded yet.</p>';
        return;
    }

    echo '<ul style="margin:0;padding-left:18px;">';
    foreach ($history as $entry) {
        $status = xpressui_get_submission_status_label((string) ($entry['status'] ?? 'new'));
        $actor = (string) ($entry['actor'] ?? 'system');
        $at = (string) ($entry['at'] ?? '');
        $note = trim((string) ($entry['note'] ?? ''));
        echo '<li style="margin-bottom:10px;">';
        echo '<strong>' . esc_html($status) . '</strong><br />';
        echo '<span style="opacity:0.7;">' . esc_html($at !== '' ? $at : 'unknown time') . ' · ' . esc_html($actor) . '</span>';
        if ($note !== '') {
            echo '<br /><span>' . esc_html($note) . '</span>';
        }
        echo '</li>';
    }
    echo '</ul>';
}

function xpressui_render_submission_preview_metabox($post) {
    $payload = xpressui_get_submission_payload($post->ID);
    if (empty($payload)) {
        echo '<p>No submission payload recorded.</p>';
        return;
    }

    $config = xpressui_get_project_config_snapshot($post->ID);
    $field_index = xpressui_build_config_field_index($config);
    $hidden_keys = [
        'projectId',
        'projectSlug',
        'projectConfigVersion',
        'submissionId',
        'projectConfigSnapshotJson',
    ];
    $rendered_fields = [];

    if (!empty($field_index)) {
        $grouped = [];
        foreach ($field_index as $field_name => $field_meta) {
            if (!array_key_exists($field_name, $payload)) {
                continue;
            }
            $section_label = $field_meta['sectionLabel'] ?: 'Submission fields';
            if (!isset($grouped[$section_label])) {
                $grouped[$section_label] = [];
            }
            $grouped[$section_label][$field_name] = $field_meta;
            $rendered_fields[$field_name] = true;
        }

        foreach ($grouped as $section_label => $fields) {
            $summary = xpressui_get_section_capture_summary($fields, $payload);
            $status_label = ucfirst((string) ($summary['status'] ?? 'empty'));
            echo '<h3 style="margin:16px 0 8px;">' . esc_html($section_label) . '</h3>';
            echo '<p style="margin:0 0 8px;opacity:0.75;">' . esc_html($status_label . ' · ' . $summary['filledCount'] . ' / ' . $summary['fieldCount'] . ' fields captured' . ($summary['interactiveCount'] > 0 ? ' · ' . $summary['interactiveCount'] . ' interactive' : '')) . '</p>';
            echo '<table class="widefat striped" style="margin-bottom:12px;"><tbody>';
            foreach ($fields as $field_name => $field_meta) {
                echo '<tr>';
                echo '<th style="width:30%;">' . esc_html($field_meta['label']) . '</th>';
                echo '<td>' . xpressui_format_submission_value($payload[$field_name], $field_meta) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
    }

    $remaining = [];
    foreach ($payload as $key => $value) {
        if (isset($rendered_fields[$key]) || in_array($key, $hidden_keys, true)) {
            continue;
        }
        $remaining[$key] = $value;
    }

    if (!empty($remaining)) {
        echo '<h3 style="margin:16px 0 8px;">Additional fields</h3>';
        echo '<table class="widefat striped"><tbody>';
        foreach ($remaining as $key => $value) {
            echo '<tr>';
            $field_meta = is_array($field_index[$key] ?? null) ? $field_index[$key] : [];
            echo '<th style="width:30%;">' . esc_html($field_meta['label'] ?? $key) . '</th>';
            echo '<td>' . xpressui_format_submission_value($value, $field_meta) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}

function xpressui_save_submission_status($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!isset($_POST['xpressui_submission_status_nonce']) || !wp_verify_nonce((string) $_POST['xpressui_submission_status_nonce'], 'xpressui_save_submission_status')) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $status = isset($_POST['xpressui_submission_status']) ? sanitize_text_field((string) $_POST['xpressui_submission_status']) : 'new';
    $note = isset($_POST['xpressui_review_note']) ? sanitize_textarea_field((string) $_POST['xpressui_review_note']) : '';
    $options = xpressui_get_submission_status_options();
    if (!isset($options[$status])) {
        $status = 'new';
    }

    xpressui_set_submission_status($post_id, $status, $note);
}

function xpressui_add_submission_row_actions($actions, $post) {
    if (($post->post_type ?? '') !== 'xpressui_submission') {
        return $actions;
    }

    $current_status = (string) get_post_meta($post->ID, '_xpressui_submission_status', true);
    foreach (xpressui_get_submission_status_options() as $status => $label) {
        if ($status === $current_status) {
            continue;
        }
        $url = wp_nonce_url(
            add_query_arg([
                'post_type' => 'xpressui_submission',
                'xpressui_submission_id' => $post->ID,
                'xpressui_mark_status' => $status,
            ], admin_url('edit.php')),
            'xpressui_mark_submission_status_' . $post->ID . '_' . $status,
        );
        $actions['xpressui_mark_' . $status] = '<a href="' . esc_url($url) . '">Mark ' . esc_html($label) . '</a>';
    }

    return $actions;
}

function xpressui_handle_submission_status_action() {
    if (!is_admin() || !isset($_GET['xpressui_submission_id']) || !isset($_GET['xpressui_mark_status'])) {
        return;
    }

    $post_id = (int) $_GET['xpressui_submission_id'];
    $status = sanitize_text_field((string) $_GET['xpressui_mark_status']);
    $options = xpressui_get_submission_status_options();
    if ($post_id <= 0 || !isset($options[$status])) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    check_admin_referer('xpressui_mark_submission_status_' . $post_id . '_' . $status);
    xpressui_set_submission_status($post_id, $status);

    wp_safe_redirect(add_query_arg([
        'post_type' => 'xpressui_submission',
        'xpressui_status_updated' => 1,
    ], admin_url('edit.php')));
    exit;
}

function xpressui_render_submission_payload_metabox($post) {
    $payload = xpressui_get_submission_payload($post->ID);
    if (empty($payload)) {
        echo '<p>No submission payload recorded.</p>';
        return;
    }

    echo '<pre style="white-space:pre-wrap;overflow:auto;max-height:420px;">' . esc_html(wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
}

function xpressui_render_submission_files_metabox($post) {
    $files_json = get_post_meta($post->ID, '_xpressui_uploaded_files', true);
    $files = $files_json ? json_decode($files_json, true) : [];
    if (!is_array($files) || empty($files)) {
        echo '<p>No uploaded files recorded.</p>';
        $debug_json = get_post_meta($post->ID, '_xpressui_upload_debug', true);
        if ($debug_json) {
            echo '<pre style="white-space:pre-wrap;overflow:auto;max-height:240px;">' . esc_html((string) $debug_json) . '</pre>';
        }
        return;
    }

    echo '<ul style="margin:0;padding-left:18px;">';
    foreach ($files as $file) {
        $field = isset($file['field']) ? (string) $file['field'] : 'file';
        $url = isset($file['url']) ? (string) $file['url'] : '';
        $attachment_id = isset($file['attachmentId']) ? (int) $file['attachmentId'] : 0;
        echo '<li style="margin-bottom:8px;">';
        echo '<strong>' . esc_html($field) . '</strong><br />';
        if ($url !== '') {
            echo '<a href="' . esc_url($url) . '" target="_blank" rel="noreferrer">Open file</a>';
        }
        if ($attachment_id > 0) {
            echo '<br /><span style="opacity:0.7;">Attachment ID: ' . esc_html((string) $attachment_id) . '</span>';
        }
        echo '</li>';
    }
    echo '</ul>';
}

function xpressui_get_request_file_params(WP_REST_Request $request) {
    $request_files = $request->get_file_params();
    if (!is_array($request_files)) {
        $request_files = [];
    }

    $superglobal_files = is_array($_FILES) ? $_FILES : [];
    if (empty($request_files)) {
        return $superglobal_files;
    }

    return array_replace_recursive($superglobal_files, $request_files);
}

function xpressui_normalize_uploaded_files(array $file_params) {
    $normalized = [];
    foreach ($file_params as $field_name => $file_info) {
        if (!is_array($file_info) || !isset($file_info['name'])) {
            continue;
        }
        if (is_array($file_info['name'])) {
            $count = count($file_info['name']);
            for ($index = 0; $index < $count; $index += 1) {
                $normalized[] = [
                    'field' => $field_name,
                    'name' => $file_info['name'][$index] ?? '',
                    'type' => $file_info['type'][$index] ?? '',
                    'tmp_name' => $file_info['tmp_name'][$index] ?? '',
                    'error' => $file_info['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $file_info['size'][$index] ?? 0,
                ];
            }
            continue;
        }
        $normalized[] = [
            'field' => $field_name,
            'name' => $file_info['name'],
            'type' => $file_info['type'] ?? '',
            'tmp_name' => $file_info['tmp_name'] ?? '',
            'error' => $file_info['error'] ?? UPLOAD_ERR_NO_FILE,
            'size' => $file_info['size'] ?? 0,
        ];
    }
    return $normalized;
}

function xpressui_store_uploaded_files($post_id, WP_REST_Request $request) {
    $stored_files = [];
    $debug = [
        'requestFileKeys' => [],
        'superglobalFileKeys' => [],
        'normalizedFiles' => [],
        'errors' => [],
    ];
    $file_params = xpressui_get_request_file_params($request);
    $debug['requestFileKeys'] = array_keys((array) $request->get_file_params());
    $debug['superglobalFileKeys'] = array_keys(is_array($_FILES) ? $_FILES : []);
    foreach (xpressui_normalize_uploaded_files($file_params) as $index => $file) {
        $debug['normalizedFiles'][] = [
            'field' => $file['field'] ?? '',
            'name' => $file['name'] ?? '',
            'error' => $file['error'] ?? UPLOAD_ERR_NO_FILE,
            'size' => $file['size'] ?? 0,
            'hasTmpName' => !empty($file['tmp_name']),
        ];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
            $debug['errors'][] = [
                'field' => $file['field'] ?? '',
                'message' => 'Upload missing tmp_name or has PHP upload error.',
                'errorCode' => $file['error'] ?? UPLOAD_ERR_NO_FILE,
            ];
            continue;
        }

        $temporary_field = sprintf('xpressui_upload_%d', $index);
        $_FILES[$temporary_field] = [
            'name' => $file['name'],
            'type' => $file['type'],
            'tmp_name' => $file['tmp_name'],
            'error' => $file['error'],
            'size' => $file['size'],
        ];

        $attachment = media_handle_upload($temporary_field, $post_id, [], [
            'test_form' => false,
        ]);
        unset($_FILES[$temporary_field]);

        if (is_wp_error($attachment)) {
            $debug['errors'][] = [
                'field' => $file['field'] ?? '',
                'message' => $attachment->get_error_message(),
                'errorCode' => $attachment->get_error_code(),
            ];
            continue;
        }
        $stored_files[] = [
            'field' => $file['field'],
            'originalName' => $file['name'],
            'attachmentId' => $attachment,
            'url' => wp_get_attachment_url($attachment),
        ];
    }
    update_post_meta($post_id, '_xpressui_uploaded_files', wp_json_encode($stored_files));
    update_post_meta($post_id, '_xpressui_upload_debug', wp_json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return $stored_files;
}

function xpressui_attach_uploaded_file_references($payload, array $stored_files) {
    if (!is_array($payload) || empty($stored_files)) {
        return $payload;
    }

    foreach ($stored_files as $file) {
        $field_name = isset($file['field']) ? (string) $file['field'] : '';
        if ($field_name === '') {
            continue;
        }

        $payload[$field_name] = [
            'field' => $field_name,
            'kind' => 'uploaded-file',
            'originalName' => isset($file['originalName']) ? (string) $file['originalName'] : '',
            'attachmentId' => isset($file['attachmentId']) ? (int) $file['attachmentId'] : 0,
            'url' => isset($file['url']) ? (string) $file['url'] : '',
        ];
    }

    return $payload;
}

function xpressui_build_submission_title($project_slug, $submission_id, $payload) {
    $summary = '';

    if (is_array($payload)) {
        $full_name = trim((string) ($payload['fullName'] ?? ''));
        $first_name = trim((string) ($payload['firstName'] ?? $payload['firstname'] ?? ''));
        $last_name = trim((string) ($payload['lastName'] ?? $payload['lastname'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? $payload['phoneNumber'] ?? ''));

        if ($full_name !== '') {
            $summary = $full_name;
        } elseif ($first_name !== '' || $last_name !== '') {
            $summary = trim($first_name . ' ' . $last_name);
        } elseif ($email !== '') {
            $summary = $email;
        } elseif ($phone !== '') {
            $summary = $phone;
        }
    }

    if ($summary === '') {
        $summary = (string) ($submission_id ?: uniqid('submission_', true));
    }

    return sprintf(
        '%s · %s · %s',
        (string) $project_slug,
        $summary,
        wp_date('Y-m-d H:i'),
    );
}

function xpressui_handle_submission(WP_REST_Request $request) {
    $payload = $request->get_param('payload');
    $project_id = $request->get_param('projectId') ?: 'unknown-project';
    $project_slug = $request->get_param('projectSlug') ?: 'unknown-project';
    $project_config_version = $request->get_param('projectConfigVersion') ?: '';
    $submission_id = $request->get_param('submissionId') ?: uniqid('submission_', true);
    $project_config = xpressui_normalize_project_config_snapshot($request->get_param('projectConfigSnapshotJson'));

    if (empty($payload)) {
        $payload = $request->get_json_params();
        if (!is_array($payload) || empty($payload)) {
            $payload = $request->get_params();
        }
        if (is_array($payload)) {
            unset($payload['payload']);
            unset($payload['projectConfigSnapshotJson']);
        }
    }

    $post_id = wp_insert_post([
        'post_type' => 'xpressui_submission',
        'post_status' => 'private',
        'post_title' => xpressui_build_submission_title($project_slug, $submission_id, $payload),
    ]);

    if (is_wp_error($post_id)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Unable to create submission',
        ], 500);
    }

    update_post_meta($post_id, '_xpressui_project_id', $project_id);
    update_post_meta($post_id, '_xpressui_project_slug', $project_slug);
    update_post_meta($post_id, '_xpressui_project_config_version', $project_config_version);
    update_post_meta($post_id, '_xpressui_submission_id', $submission_id);
    xpressui_store_project_config_snapshot($project_id, $project_slug, $project_config_version, $project_config);
    xpressui_set_submission_status($post_id, 'new', 'Submission received');

    $stored_files = xpressui_store_uploaded_files($post_id, $request);
    $payload_with_files = xpressui_attach_uploaded_file_references($payload, $stored_files);
    update_post_meta($post_id, '_xpressui_payload_json', is_string($payload_with_files) ? $payload_with_files : wp_json_encode($payload_with_files));

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Submission received',
        'entryId' => $post_id,
        'submissionId' => $submission_id,
        'files' => $stored_files,
    ], 200);
}

register_activation_hook(__FILE__, function () {
    xpressui_register_submission_post_type();
    flush_rewrite_rules();
});
