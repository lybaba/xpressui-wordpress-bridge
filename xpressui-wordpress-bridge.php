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
    $submission_id = $request->get_param('submissionId') ?: uniqid('submission_', true);

    if (empty($payload)) {
        $payload = $request->get_json_params();
        if (!is_array($payload) || empty($payload)) {
            $payload = $request->get_params();
        }
        if (is_array($payload)) {
            unset($payload['payload']);
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
    update_post_meta($post_id, '_xpressui_submission_id', $submission_id);
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
