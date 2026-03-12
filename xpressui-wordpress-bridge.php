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
    if ($column === 'xpressui_submission_id') {
        echo esc_html((string) get_post_meta($post_id, '_xpressui_submission_id', true));
    }
}, 10, 2);

add_action('add_meta_boxes', function () {
    add_meta_box('xpressui_submission_payload', 'Submission Payload', 'xpressui_render_submission_payload_metabox', 'xpressui_submission', 'normal', 'default');
    add_meta_box('xpressui_submission_files', 'Uploaded Files', 'xpressui_render_submission_files_metabox', 'xpressui_submission', 'side', 'default');
});

function xpressui_render_submission_payload_metabox($post) {
    $payload_json = get_post_meta($post->ID, '_xpressui_payload_json', true);
    if (!$payload_json) {
        echo '<p>No submission payload recorded.</p>';
        return;
    }

    $payload = json_decode($payload_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo '<pre style="white-space:pre-wrap;overflow:auto;">' . esc_html((string) $payload_json) . '</pre>';
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
            'attachmentId' => $attachment,
            'url' => wp_get_attachment_url($attachment),
        ];
    }
    update_post_meta($post_id, '_xpressui_uploaded_files', wp_json_encode($stored_files));
    update_post_meta($post_id, '_xpressui_upload_debug', wp_json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return $stored_files;
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
            unset($payload['projectId'], $payload['projectSlug'], $payload['submissionId'], $payload['payload']);
        }
    }

    $post_id = wp_insert_post([
        'post_type' => 'xpressui_submission',
        'post_status' => 'private',
        'post_title' => sprintf('%s %s', $project_slug, $submission_id),
    ]);

    if (is_wp_error($post_id)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Unable to create submission',
        ], 500);
    }

    update_post_meta($post_id, '_xpressui_payload_json', is_string($payload) ? $payload : wp_json_encode($payload));
    update_post_meta($post_id, '_xpressui_project_id', $project_id);
    update_post_meta($post_id, '_xpressui_project_slug', $project_slug);
    update_post_meta($post_id, '_xpressui_submission_id', $submission_id);

    $stored_files = xpressui_store_uploaded_files($post_id, $request);

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
