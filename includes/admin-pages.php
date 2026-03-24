<?php
/**
 * Admin pages: Project Inbox, My Queue, Manage Workflows, and Project Settings.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Menu registration
// ---------------------------------------------------------------------------

function xpressui_register_submission_admin_pages() {
	add_submenu_page(
		'edit.php?post_type=xpressui_submission',
		__( 'Project Inbox', 'xpressui-bridge' ),
		__( 'Project Inbox', 'xpressui-bridge' ),
		'edit_posts',
		'xpressui-project-inbox',
		'xpressui_render_project_inbox_page'
	);
	add_submenu_page(
		'edit.php?post_type=xpressui_submission',
		__( 'My Queue', 'xpressui-bridge' ),
		__( 'My Queue', 'xpressui-bridge' ),
		'edit_posts',
		'xpressui-my-queue',
		'xpressui_render_my_queue_page'
	);
}

function xpressui_register_admin_page() {
	add_submenu_page(
		'edit.php?post_type=xpressui_submission',
		__( 'Manage Workflows', 'xpressui-bridge' ),
		__( 'Workflows', 'xpressui-bridge' ),
		'manage_options',
		'xpressui-bridge',
		'xpressui_render_workflows_page'
	);
}

// ---------------------------------------------------------------------------
// Project Inbox
// ---------------------------------------------------------------------------

function xpressui_get_project_inbox_rows() {
	$submission_ids = get_posts( [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );

	$rows = [];
	foreach ( $submission_ids as $id ) {
		$project_slug = (string) get_post_meta( $id, '_xpressui_project_slug', true );
		$status       = (string) get_post_meta( $id, '_xpressui_submission_status', true );
		if ( $project_slug === '' ) {
			$project_slug = 'unknown-project';
		}
		if ( $status === '' ) {
			$status = 'new';
		}
		if ( ! isset( $rows[ $project_slug ] ) ) {
			$rows[ $project_slug ] = [
				'projectSlug'        => $project_slug,
				'total'              => 0,
				'new'                => 0,
				'in-review'          => 0,
				'done'               => 0,
				'latestSubmissionId' => '',
				'latestDate'         => '',
			];
		}
		$rows[ $project_slug ]['total']++;
		if ( isset( $rows[ $project_slug ][ $status ] ) ) {
			$rows[ $project_slug ][ $status ]++;
		}
		if ( $rows[ $project_slug ]['latestSubmissionId'] === '' ) {
			$rows[ $project_slug ]['latestSubmissionId'] = (string) get_post_meta( $id, '_xpressui_submission_id', true );
			$rows[ $project_slug ]['latestDate']         = get_the_date( 'Y-m-d H:i', $id ) ?: '';
		}
	}
	ksort( $rows );
	return array_values( $rows );
}

function xpressui_render_project_inbox_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$rows = xpressui_get_project_inbox_rows();
	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Project Inbox', 'xpressui-bridge' ) . '</h1>';
	echo '<p>' . esc_html__( 'Review incoming submissions grouped by project, then jump into filtered queues.', 'xpressui-bridge' ) . '</p>';

	if ( empty( $rows ) ) {
		echo '<p>' . esc_html__( 'No submissions recorded yet.', 'xpressui-bridge' ) . '</p>';
		echo '</div>';
		return;
	}

	echo '<table class="widefat striped"><thead><tr>';
	echo '<th>' . esc_html__( 'Project', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Total', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'New', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'In review', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Done', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Latest submission', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Actions', 'xpressui-bridge' ) . '</th>';
	echo '</tr></thead><tbody>';

	foreach ( $rows as $row ) {
		$all_url = add_query_arg( [ 'post_type' => 'xpressui_submission', 'xpressui_project' => $row['projectSlug'] ], admin_url( 'edit.php' ) );
		$new_url = add_query_arg( [ 'post_type' => 'xpressui_submission', 'xpressui_project' => $row['projectSlug'], 'xpressui_status' => 'new' ], admin_url( 'edit.php' ) );
		$latest  = $row['latestSubmissionId'] !== ''
			? $row['latestSubmissionId'] . ' · ' . $row['latestDate']
			: __( 'No submissions yet', 'xpressui-bridge' );

		echo '<tr>';
		echo '<td><strong>' . esc_html( $row['projectSlug'] ) . '</strong></td>';
		echo '<td>' . esc_html( (string) $row['total'] ) . '</td>';
		echo '<td>' . esc_html( (string) $row['new'] ) . '</td>';
		echo '<td>' . esc_html( (string) $row['in-review'] ) . '</td>';
		echo '<td>' . esc_html( (string) $row['done'] ) . '</td>';
		echo '<td>' . esc_html( $latest ) . '</td>';
		echo '<td><a href="' . esc_url( $all_url ) . '">' . esc_html__( 'Open all', 'xpressui-bridge' ) . '</a> · <a href="' . esc_url( $new_url ) . '">' . esc_html__( 'Open new', 'xpressui-bridge' ) . '</a></td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	echo '</div>';
}

// ---------------------------------------------------------------------------
// My Queue
// ---------------------------------------------------------------------------

function xpressui_render_my_queue_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$current_user_id = get_current_user_id();
	$queue_url  = add_query_arg( [ 'post_type' => 'xpressui_submission', 'xpressui_assignee' => $current_user_id ], admin_url( 'edit.php' ) );
	$review_url = add_query_arg( [ 'post_type' => 'xpressui_submission', 'xpressui_assignee' => $current_user_id, 'xpressui_status' => 'in-review' ], admin_url( 'edit.php' ) );

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'My Queue', 'xpressui-bridge' ) . '</h1>';
	echo '<p>' . esc_html__( 'Open the submissions assigned to you, or jump straight into the ones already in review.', 'xpressui-bridge' ) . '</p>';
	echo '<p>';
	echo '<a class="button button-primary" href="' . esc_url( $queue_url ) . '">' . esc_html__( 'Open my submissions', 'xpressui-bridge' ) . '</a> ';
	echo '<a class="button" href="' . esc_url( $review_url ) . '">' . esc_html__( 'Open my in-review queue', 'xpressui-bridge' ) . '</a>';
	echo '</p></div>';
}

// ---------------------------------------------------------------------------
// Manage Workflows (zip upload + project settings)
// ---------------------------------------------------------------------------

function xpressui_render_workflows_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'xpressui-bridge' ) );
	}

	$notice_class   = '';
	$notice_message = '';

	// Handle zip upload.
	if ( isset( $_POST['xpressui_upload_pack'] ) && check_admin_referer( 'xpressui_upload_action', 'xpressui_nonce' ) ) {
		$result = xpressui_handle_zip_upload();
		if ( is_wp_error( $result ) ) {
			$notice_class   = 'notice-error';
			$notice_message = $result->get_error_message();
		} else {
			$notice_class   = 'notice-success';
			/* translators: %s: shortcode example */
			$notice_message = sprintf(
				__( '<strong>Success!</strong> The package has been installed. Embed it with: <code>[xpressui id="%s"]</code>', 'xpressui-bridge' ),
				esc_attr( $result )
			);
		}
	}

	// Handle project settings save.
	if ( isset( $_POST['xpressui_save_project_settings'] ) && check_admin_referer( 'xpressui_project_settings_action', 'xpressui_settings_nonce' ) ) {
		$slug           = sanitize_title( (string) ( $_POST['xpressui_settings_slug'] ?? '' ) );
		$notify_email   = sanitize_email( (string) ( $_POST['xpressui_notify_email'] ?? '' ) );
		$redirect_url   = esc_url_raw( (string) ( $_POST['xpressui_redirect_url'] ?? '' ) );

		if ( $slug !== '' ) {
			$all_settings             = get_option( 'xpressui_project_settings', [] );
			if ( ! is_array( $all_settings ) ) {
				$all_settings = [];
			}
			$all_settings[ $slug ] = [
				'notifyEmail' => $notify_email,
				'redirectUrl' => $redirect_url,
			];
			update_option( 'xpressui_project_settings', $all_settings );
			$notice_class   = 'notice-success';
			$notice_message = __( '<strong>Settings saved.</strong>', 'xpressui-bridge' );
		}
	}

	// Installed workflows.
	$upload_dir         = wp_get_upload_dir();
	$target_dir         = trailingslashit( $upload_dir['basedir'] ) . 'xpressui/';
	$installed_slugs    = [];
	if ( is_dir( $target_dir ) ) {
		foreach ( (array) scandir( $target_dir ) as $item ) {
			if ( $item !== '.' && $item !== '..' && is_dir( $target_dir . $item ) ) {
				$installed_slugs[] = $item;
			}
		}
	}

	$all_settings = get_option( 'xpressui_project_settings', [] );
	if ( ! is_array( $all_settings ) ) {
		$all_settings = [];
	}

	if ( $notice_message ) {
		echo '<div class="notice ' . esc_attr( $notice_class ) . ' is-dismissible"><p>' . wp_kses_post( $notice_message ) . '</p></div>';
	}

	echo '<div class="wrap">';
	echo '<h1>' . esc_html__( 'Manage XPressUI Workflows', 'xpressui-bridge' ) . '</h1>';

	// --- Installed workflows table ---
	echo '<div class="card xpressui-admin-card">';
	echo '<h2>' . esc_html__( 'Installed Workflows', 'xpressui-bridge' ) . '</h2>';
	if ( empty( $installed_slugs ) ) {
		echo '<p>' . esc_html__( 'No workflows installed yet. Upload a package below to get started.', 'xpressui-bridge' ) . '</p>';
	} else {
		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Project Slug', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Shortcode', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Notify email', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Redirect URL', 'xpressui-bridge' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $installed_slugs as $slug ) {
			$settings     = $all_settings[ $slug ] ?? [];
			$notify_email = (string) ( $settings['notifyEmail'] ?? '' );
			$redirect_url = (string) ( $settings['redirectUrl'] ?? '' );
			echo '<tr>';
			echo '<td><strong>' . esc_html( $slug ) . '</strong></td>';
			echo '<td><code>[xpressui id="' . esc_attr( $slug ) . '"]</code></td>';
			echo '<td>' . esc_html( $notify_email !== '' ? $notify_email : '—' ) . '</td>';
			echo '<td>' . ( $redirect_url !== '' ? '<a href="' . esc_url( $redirect_url ) . '" target="_blank" rel="noreferrer">' . esc_html( $redirect_url ) . '</a>' : '—' ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';

	// --- Install / update pack ---
	echo '<div class="card xpressui-admin-card">';
	echo '<h2>' . esc_html__( 'Install or Update a Pack', 'xpressui-bridge' ) . '</h2>';
	echo '<p>' . esc_html__( 'Upload the .zip file generated by the XPressUI console. The package will be extracted into your uploads directory.', 'xpressui-bridge' ) . '</p>';
	echo '<form method="post" enctype="multipart/form-data">';
	wp_nonce_field( 'xpressui_upload_action', 'xpressui_nonce' );
	echo '<input type="hidden" name="xpressui_upload_pack" value="1">';
	echo '<table class="form-table"><tbody><tr>';
	echo '<th><label for="xpressui_zip">' . esc_html__( 'Package File (.zip)', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><input type="file" name="xpressui_zip" id="xpressui_zip" accept=".zip" required></td>';
	echo '</tr></tbody></table>';
	echo '<p class="submit">';
	submit_button( __( 'Install workflow', 'xpressui-bridge' ), 'primary', 'submit', false );
	echo '</p></form></div>';

	// --- Project settings ---
	if ( ! empty( $installed_slugs ) ) {
		echo '<div class="card xpressui-admin-card">';
		echo '<h2>' . esc_html__( 'Project Settings', 'xpressui-bridge' ) . '</h2>';
		echo '<p>' . esc_html__( 'Configure email notifications and post-submit redirect URL per project.', 'xpressui-bridge' ) . '</p>';
		echo '<form method="post">';
		wp_nonce_field( 'xpressui_project_settings_action', 'xpressui_settings_nonce' );
		echo '<input type="hidden" name="xpressui_save_project_settings" value="1">';
		echo '<table class="form-table"><tbody>';

		echo '<tr><th><label for="xpressui_settings_slug">' . esc_html__( 'Project', 'xpressui-bridge' ) . '</label></th>';
		echo '<td><select id="xpressui_settings_slug" name="xpressui_settings_slug" class="regular-text">';
		foreach ( $installed_slugs as $slug ) {
			echo '<option value="' . esc_attr( $slug ) . '">' . esc_html( $slug ) . '</option>';
		}
		echo '</select></td></tr>';

		echo '<tr><th><label for="xpressui_notify_email">' . esc_html__( 'Notification email', 'xpressui-bridge' ) . '</label></th>';
		echo '<td><input type="email" id="xpressui_notify_email" name="xpressui_notify_email" class="regular-text" placeholder="' . esc_attr__( 'hello@example.com', 'xpressui-bridge' ) . '">';
		echo '<p class="description">' . esc_html__( 'Receive an email on each new submission for this project. Leave empty to disable.', 'xpressui-bridge' ) . '</p></td></tr>';

		echo '<tr><th><label for="xpressui_redirect_url">' . esc_html__( 'Post-submit redirect', 'xpressui-bridge' ) . '</label></th>';
		echo '<td><input type="url" id="xpressui_redirect_url" name="xpressui_redirect_url" class="regular-text" placeholder="https://">';
		echo '<p class="description">' . esc_html__( 'Redirect the client to this URL after a successful submission. Leave empty to show the success message.', 'xpressui-bridge' ) . '</p></td></tr>';

		echo '</tbody></table>';
		echo '<p class="submit">';
		submit_button( __( 'Save settings', 'xpressui-bridge' ), 'primary', 'submit', false );
		echo '</p></form></div>';
	}

	echo '</div>'; // .wrap
}

// ---------------------------------------------------------------------------
// ZIP upload handler
// ---------------------------------------------------------------------------

function xpressui_handle_zip_upload() {
	if ( empty( $_FILES['xpressui_zip']['tmp_name'] ) ) {
		return new WP_Error( 'no_file', __( 'Please select a file.', 'xpressui-bridge' ) );
	}

	$file      = $_FILES['xpressui_zip'];
	$file_type = wp_check_filetype( $file['name'] );

	if ( $file_type['ext'] !== 'zip' ) {
		return new WP_Error( 'invalid_file_type', __( 'Only .zip files are allowed.', 'xpressui-bridge' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();

	$upload_dir = wp_get_upload_dir();
	if ( ! empty( $upload_dir['error'] ) ) {
		return new WP_Error( 'upload_dir_error', $upload_dir['error'] );
	}

	$target_dir = trailingslashit( $upload_dir['basedir'] ) . 'xpressui/';

	if ( ! file_exists( $target_dir ) ) {
		wp_mkdir_p( $target_dir );
		global $wp_filesystem;
		$wp_filesystem->put_contents( $target_dir . 'index.php', '<?php' . PHP_EOL . '// Silence is golden.' . PHP_EOL, FS_CHMOD_FILE );
	}

	$tmp_zip = $target_dir . basename( $file['name'] );
	if ( ! move_uploaded_file( $file['tmp_name'], $tmp_zip ) ) {
		return new WP_Error( 'move_failed', __( 'Failed to save the uploaded file.', 'xpressui-bridge' ) );
	}

	$unzip_result = unzip_file( $tmp_zip, $target_dir );

	global $wp_filesystem;
	$wp_filesystem->delete( $tmp_zip );

	if ( is_wp_error( $unzip_result ) ) {
		return $unzip_result;
	}

	return pathinfo( $file['name'], PATHINFO_FILENAME );
}
