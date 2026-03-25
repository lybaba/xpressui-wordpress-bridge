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
// 'Find pages' filter: translate ?xpressui_workflow_slug= into post__in so
// the admin pages list shows only pages embedding that workflow's shortcode.
// Using pre_get_posts avoids WP_Query's search tokenisation which mishandles
// the brackets and quotes in [xpressui id="slug"].
// ---------------------------------------------------------------------------

add_action( 'pre_get_posts', 'xpressui_filter_pages_by_workflow_slug' );

function xpressui_filter_pages_by_workflow_slug( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$slug = sanitize_title( (string) ( $_GET['xpressui_workflow_slug'] ?? '' ) );
	if ( '' === $slug ) {
		return;
	}
	$page_ids = xpressui_get_workflow_page_ids( $slug );
	// Set post__in to the real IDs, or [0] to return an empty list gracefully.
	$query->set( 'post__in', ! empty( $page_ids ) ? $page_ids : [ 0 ] );
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

	if ( isset( $_GET['xpressui_notice'] ) ) {
		$notice_message = sanitize_text_field( wp_unslash( (string) $_GET['xpressui_notice'] ) );
		$notice_class   = ( isset( $_GET['xpressui_notice_type'] ) && sanitize_key( (string) $_GET['xpressui_notice_type'] ) === 'error' )
			? 'notice-error'
			: 'notice-success';
	}

	// Handle zip upload (pro only).
	if ( isset( $_POST['xpressui_upload_pack'] ) && check_admin_referer( 'xpressui_upload_action', 'xpressui_nonce' ) ) {
		if ( ! xpressui_is_pro_extension_active() ) {
			$result = new WP_Error( 'pro_required', __( 'Installing custom workflow packs requires the XPressUI Pro extension (xpressui-wordpress-bridge-pro).', 'xpressui-bridge' ) );
		} else {
			$result = xpressui_handle_zip_upload();
		}
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

	if ( isset( $_POST['xpressui_save_license_settings'] ) && check_admin_referer( 'xpressui_license_settings_action', 'xpressui_license_nonce' ) ) {
		$license_key = sanitize_text_field( (string) ( $_POST['xpressui_license_key'] ?? '' ) );
		$settings    = xpressui_get_license_settings();
		$settings['licenseKey']  = $license_key;
		$settings['updatedAt']   = current_time( 'mysql' );
		$settings['maskedKey']   = xpressui_get_masked_license_key();
		xpressui_update_license_settings( $settings );
		$notice_class   = 'notice-success';
		$notice_message = __( '<strong>License settings saved.</strong>', 'xpressui-bridge' );
	}

	// Installed workflows.
	$installed_slugs = xpressui_get_installed_workflow_slugs();

	$all_settings = get_option( 'xpressui_project_settings', [] );
	if ( ! is_array( $all_settings ) ) {
		$all_settings = [];
	}
	$license_settings  = xpressui_get_license_settings();
	$license_key       = sanitize_text_field( (string) ( $license_settings['licenseKey'] ?? '' ) );
	$masked_license    = xpressui_get_masked_license_key();
	$pro_active        = xpressui_is_pro_extension_active();
	$license_valid     = xpressui_has_valid_pro_license();
	$current_tier      = xpressui_get_current_runtime_tier();

	if ( $notice_message ) {
		echo '<div class="notice ' . esc_attr( $notice_class ) . ' is-dismissible"><p>' . wp_kses_post( $notice_message ) . '</p></div>';
	}

	echo '<div class="wrap xpressui-wrap">';
	echo '<h1>' . esc_html__( 'XPressUI Workflows', 'xpressui-bridge' ) . '</h1>';
	echo '<p class="xpressui-page-intro">' . esc_html__( 'Manage your installed workflow packages and configure per-project settings.', 'xpressui-bridge' ) . '</p>';

	echo '<div class="card xpressui-admin-card">';
	echo '<h2>' . esc_html__( 'Pro Extension', 'xpressui-bridge' ) . '</h2>';
	echo '<div class="xpressui-status-row">';
	echo '<span class="xpressui-status-item"><span class="xpressui-status-dot ' . ( $pro_active ? 'xpressui-status-dot--on' : 'xpressui-status-dot--off' ) . '"></span>';
	echo esc_html__( 'Pro extension', 'xpressui-bridge' ) . ' — <strong>' . esc_html( $pro_active ? __( 'Active', 'xpressui-bridge' ) : __( 'Not installed', 'xpressui-bridge' ) ) . '</strong></span>';
	echo '<span class="xpressui-status-item"><span class="xpressui-status-dot ' . ( $license_valid ? 'xpressui-status-dot--on' : 'xpressui-status-dot--off' ) . '"></span>';
	echo esc_html__( 'License', 'xpressui-bridge' ) . ' — <strong>' . esc_html( $license_valid ? __( 'Valid', 'xpressui-bridge' ) : __( 'Not validated', 'xpressui-bridge' ) ) . '</strong></span>';
	echo '</div>';
	if ( ! $pro_active || ! $license_valid ) {
		echo '<p class="description">' . esc_html__( 'Install and activate xpressui-wordpress-bridge-pro to unlock custom workflow packs and other Pro features.', 'xpressui-bridge' ) . '</p>';
	}
	echo '<form method="post">';
	wp_nonce_field( 'xpressui_license_settings_action', 'xpressui_license_nonce' );
	echo '<input type="hidden" name="xpressui_save_license_settings" value="1">';
	echo '<table class="form-table"><tbody>';
	echo '<tr><th><label for="xpressui_license_key">' . esc_html__( 'License key', 'xpressui-bridge' ) . '</label></th>';
	echo '<td><input type="text" id="xpressui_license_key" name="xpressui_license_key" class="regular-text" value="' . esc_attr( $license_key ) . '" autocomplete="off" />';
	if ( '' !== $masked_license ) {
		echo '<p class="description">' . esc_html__( 'Stored key:', 'xpressui-bridge' ) . ' ' . esc_html( $masked_license ) . '</p>';
	}
	echo '<p class="description">' . esc_html__( 'Enter your XPressUI Pro license key. The Pro extension will use this value to validate your license.', 'xpressui-bridge' ) . '</p></td></tr>';
	echo '</tbody></table>';
	echo '<p class="submit">';
	submit_button( __( 'Save license key', 'xpressui-bridge' ), 'secondary', 'submit', false );
	echo '</p></form></div>';

	$bundled_slugs = xpressui_get_bundled_workflow_slugs();

	$starter_slug    = 'document-intake';
	$starter_page_id = xpressui_get_workflow_primary_page_id( $starter_slug );
	if ( xpressui_is_installed_workflow( $starter_slug ) ) {
		$starter_create_url = wp_nonce_url(
			add_query_arg(
				[
					'post_type'       => 'xpressui_submission',
					'page'            => 'xpressui-bridge',
					'xpressui_action' => 'create_workflow_page',
					'xpressui_slug'   => $starter_slug,
				],
				admin_url( 'edit.php' )
			),
			'xpressui_create_workflow_page_' . $starter_slug
		);
		echo '<div class="card xpressui-admin-card">';
		echo '<h2>' . esc_html__( 'Quick Start', 'xpressui-bridge' ) . '</h2>';
		echo '<p>' . esc_html__( 'The Document Intake workflow is bundled and ready to use. Add it to any page with the shortcode below, or create a dedicated page in one click.', 'xpressui-bridge' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Workflow:', 'xpressui-bridge' ) . '</strong> <code>[xpressui id="document-intake"]</code></p>';
		if ( $starter_page_id > 0 ) {
			$edit_url = get_edit_post_link( $starter_page_id, '' );
			$view_url = get_permalink( $starter_page_id );
			echo '<p><strong>' . esc_html__( 'Starter page:', 'xpressui-bridge' ) . '</strong> ' . esc_html( get_the_title( $starter_page_id ) ?: __( 'Untitled page', 'xpressui-bridge' ) ) . '</p>';
			echo '<p><a class="button button-primary" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit starter page', 'xpressui-bridge' ) . '</a> ';
			if ( $view_url ) {
				echo '<a class="button" href="' . esc_url( $view_url ) . '" target="_blank" rel="noreferrer">' . esc_html__( 'Preview starter page', 'xpressui-bridge' ) . '</a>';
			}
			echo '</p>';
		} else {
			echo '<p><a class="button button-primary" href="' . esc_url( $starter_create_url ) . '">' . esc_html__( 'Create starter page', 'xpressui-bridge' ) . '</a></p>';
		}
		echo '</div>';
	}

	// --- Installed workflows table ---
	echo '<div class="card xpressui-admin-card">';
	echo '<h2>' . esc_html__( 'Installed Workflows', 'xpressui-bridge' ) . '</h2>';
	if ( empty( $installed_slugs ) ) {
		echo '<p>' . esc_html__( 'No workflows installed yet. Upload a package below to get started.', 'xpressui-bridge' ) . '</p>';
	} else {
		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Workflow', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Tier', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Source', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Shortcode', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Notify email', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Redirect URL', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'xpressui-bridge' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $installed_slugs as $slug ) {
			$settings      = $all_settings[ $slug ] ?? [];
			$manifest_meta = xpressui_get_workflow_manifest_meta( $slug );
			$notify_email  = (string) ( $settings['notifyEmail'] ?? '' );
			$redirect_url  = (string) ( $settings['redirectUrl'] ?? '' );
			$runtime_tier  = (string) ( $manifest_meta['runtimeTier'] ?? 'light' );
			$bridge_mode   = (string) ( $manifest_meta['bridgeMode'] ?? 'legacy-shell' );
			$shortcode_mode = (string) ( $manifest_meta['shortcodeMode'] ?? 'legacy-template' );
			$template_profile = (string) ( $manifest_meta['templateProfile'] ?? 'light' );
			$legacy_shell  = ! empty( $manifest_meta['usesLegacyShellArtifacts'] );
			$is_bundled    = ! empty( $manifest_meta['isBundled'] );
			$update_available = $is_bundled && xpressui_is_bundled_workflow_update_available( $slug );
			$generated_at  = (string) ( $manifest_meta['generatedAt'] ?? '' );
			$runtime_version = (string) ( $manifest_meta['runtimeVersion'] ?? '' );
			$compiled_shell_ready = xpressui_can_render_compiled_workflow_shell( $slug );
			$source_label  = $is_bundled ? __( 'Bundled starter', 'xpressui-bridge' ) : __( 'Uploaded pack', 'xpressui-bridge' );
			$reinstall_url = wp_nonce_url(
				add_query_arg(
					[
						'post_type'        => 'xpressui_submission',
						'page'             => 'xpressui-bridge',
						'xpressui_action'  => 'reinstall_bundled_workflow',
						'xpressui_slug'    => $slug,
					],
					admin_url( 'edit.php' )
				),
				'xpressui_reinstall_bundled_workflow_' . $slug
			);
			$delete_url = wp_nonce_url(
				add_query_arg(
					[
						'post_type'        => 'xpressui_submission',
						'page'             => 'xpressui-bridge',
						'xpressui_action'  => 'delete_workflow',
						'xpressui_slug'    => $slug,
					],
					admin_url( 'edit.php' )
				),
				'xpressui_delete_workflow_' . $slug
			);
			$create_page_url = wp_nonce_url(
				add_query_arg(
					[
						'post_type'        => 'xpressui_submission',
						'page'             => 'xpressui-bridge',
						'xpressui_action'  => 'create_workflow_page',
						'xpressui_slug'    => $slug,
					],
					admin_url( 'edit.php' )
				),
				'xpressui_create_workflow_page_' . $slug
			);
			$page_ids        = xpressui_get_workflow_page_ids( $slug );
			$primary_page_id = ! empty( $page_ids ) ? (int) $page_ids[0] : 0;
			$edit_page_url   = $primary_page_id > 0 ? get_edit_post_link( $primary_page_id, '' ) : '';
			$view_page_url   = $primary_page_id > 0 ? get_permalink( $primary_page_id ) : '';
			// 'Find pages' passes a custom param; pre_get_posts translates it to
			// post__in so WP_Query never tokenises the shortcode string.
			$open_page_url = add_query_arg(
				[
					'post_type'               => 'page',
					'post_status'             => 'all',
					'xpressui_workflow_slug'  => $slug,
				],
				admin_url( 'edit.php' )
			);
			echo '<tr>';
			echo '<td><strong>' . esc_html( $slug ) . '</strong>';
			if ( $slug === 'document-intake' ) {
				echo '<br /><span class="xpressui-muted">' . esc_html__( 'Document Intake', 'xpressui-bridge' ) . '</span>';
			}
			echo '</td>';
			echo '<td><span class="xpressui-badge xpressui-badge--muted">' . esc_html( $runtime_tier !== '' ? $runtime_tier : 'light' ) . '</span></td>';
			echo '<td>';
			if ( $is_bundled ) {
				echo '<span class="xpressui-badge">' . esc_html__( 'Bundled', 'xpressui-bridge' ) . '</span>';
			} else {
				echo '<span class="xpressui-badge xpressui-badge--muted">' . esc_html__( 'Uploaded', 'xpressui-bridge' ) . '</span>';
			}
			if ( $update_available ) {
				echo ' <span class="xpressui-badge xpressui-badge--status-in-review">' . esc_html__( 'Update available', 'xpressui-bridge' ) . '</span>';
			}
			echo '</td>';
			echo '<td><code>[xpressui id="' . esc_attr( $slug ) . '"]</code></td>';
			echo '<td>' . esc_html( $notify_email !== '' ? $notify_email : '—' ) . '</td>';
			echo '<td>' . ( $redirect_url !== '' ? '<a href="' . esc_url( $redirect_url ) . '" target="_blank" rel="noreferrer">' . esc_html( $redirect_url ) . '</a>' : '—' ) . '</td>';
			echo '<td>';
			echo '<a href="' . esc_url( $create_page_url ) . '">' . esc_html__( 'Create page', 'xpressui-bridge' ) . '</a> · ';
			echo '<a href="' . esc_url( $open_page_url ) . '">' . esc_html__( 'Find pages', 'xpressui-bridge' ) . '</a>';
			if ( $edit_page_url ) {
				echo ' · <a href="' . esc_url( $edit_page_url ) . '">' . esc_html__( 'Edit page', 'xpressui-bridge' ) . '</a>';
			}
			if ( $view_page_url ) {
				echo ' · <a href="' . esc_url( $view_page_url ) . '" target="_blank" rel="noreferrer">' . esc_html__( 'View page', 'xpressui-bridge' ) . '</a>';
			}
			if ( $is_bundled ) {
				echo ' · <a href="' . esc_url( $reinstall_url ) . '">' . esc_html( $update_available ? __( 'Update', 'xpressui-bridge' ) : __( 'Reinstall', 'xpressui-bridge' ) ) . '</a>';
				echo ' · <span class="xpressui-muted" title="' . esc_attr__( 'Bundled starter workflows cannot be deleted.', 'xpressui-bridge' ) . '">' . esc_html__( 'Delete', 'xpressui-bridge' ) . '</span>';
			} else {
				echo ' · <a href="' . esc_url( $delete_url ) . '">' . esc_html__( 'Delete', 'xpressui-bridge' ) . '</a>';
			}
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';

	// --- Install / update pack ---
	echo '<div class="card xpressui-admin-card">';
	echo '<h2>' . esc_html__( 'Custom Workflow Packs', 'xpressui-bridge' ) . '</h2>';
	if ( ! xpressui_is_pro_extension_active() ) {
		echo '<div class="xpressui-pro-gate">';
		echo '<span class="dashicons dashicons-lock xpressui-pro-gate__icon"></span>';
		echo '<div class="xpressui-pro-gate__body">';
		echo '<p>' . esc_html__( 'Upload and install workflow packages exported from the XPressUI console. Requires the Pro extension.', 'xpressui-bridge' ) . '</p>';
		echo '<a href="https://lybaba.github.io/iakpress-console/" target="_blank" rel="noreferrer" class="button button-primary">' . esc_html__( 'Get XPressUI Pro', 'xpressui-bridge' ) . '</a>';
		echo '</div></div>';
	} else {
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
		echo '</p></form>';
	}
	echo '</div>';

	// --- Project settings ---
	if ( ! empty( $installed_slugs ) ) {
		echo '<div class="card xpressui-admin-card">';
		echo '<h2>' . esc_html__( 'Project Settings', 'xpressui-bridge' ) . '</h2>';
		echo '<p>' . esc_html__( 'Configure notifications and the post-submission redirect for each workflow.', 'xpressui-bridge' ) . '</p>';
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

function xpressui_handle_workflow_admin_actions() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$action = isset( $_GET['xpressui_action'] ) ? sanitize_key( (string) $_GET['xpressui_action'] ) : '';
	$slug   = isset( $_GET['xpressui_slug'] ) ? sanitize_title( (string) $_GET['xpressui_slug'] ) : '';
	if ( $action === '' || $slug === '' ) {
		return;
	}

	if ( $action === 'reinstall_bundled_workflow' ) {
		check_admin_referer( 'xpressui_reinstall_bundled_workflow_' . $slug );
		$result = xpressui_reinstall_bundled_workflow( $slug );
		$status = is_wp_error( $result ) ? 'error' : 'success';
		$message = is_wp_error( $result )
			? $result->get_error_message()
			: __( 'The bundled workflow was reinstalled successfully.', 'xpressui-bridge' );
		wp_safe_redirect(
			add_query_arg(
				[
					'post_type'          => 'xpressui_submission',
					'page'               => 'xpressui-bridge',
					'xpressui_notice'    => rawurlencode( $message ),
					'xpressui_notice_type' => $status,
				],
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	if ( $action === 'delete_workflow' ) {
		check_admin_referer( 'xpressui_delete_workflow_' . $slug );
		if ( xpressui_is_bundled_workflow( $slug ) ) {
			$result = new WP_Error( 'bundled_workflow_protected', __( 'Bundled starter workflows cannot be deleted. Use Reinstall to restore the original.', 'xpressui-bridge' ) );
		} else {
			$result = xpressui_delete_workflow( $slug );
		}
		$status = is_wp_error( $result ) ? 'error' : 'success';
		$message = is_wp_error( $result )
			? $result->get_error_message()
			: __( 'The workflow was deleted successfully.', 'xpressui-bridge' );
		wp_safe_redirect(
			add_query_arg(
				[
					'post_type'          => 'xpressui_submission',
					'page'               => 'xpressui-bridge',
					'xpressui_notice'    => rawurlencode( $message ),
					'xpressui_notice_type' => $status,
				],
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	if ( $action === 'create_workflow_page' ) {
		check_admin_referer( 'xpressui_create_workflow_page_' . $slug );
		$result = xpressui_create_workflow_page( $slug );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				add_query_arg(
					[
						'post_type'            => 'xpressui_submission',
						'page'                 => 'xpressui-bridge',
						'xpressui_notice'      => rawurlencode( $result->get_error_message() ),
						'xpressui_notice_type' => 'error',
					],
					admin_url( 'edit.php' )
				)
			);
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'post'   => (int) $result,
					'action' => 'edit',
				],
				admin_url( 'post.php' )
			)
		);
		exit;
	}
}

function xpressui_create_workflow_page( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( $slug === '' || ! xpressui_is_installed_workflow( $slug ) ) {
		return new WP_Error( 'missing_workflow', __( 'The workflow could not be found.', 'xpressui-bridge' ) );
	}

	$manifest_meta = xpressui_get_workflow_manifest_meta( $slug );
	$page_title    = sanitize_text_field( (string) ( $manifest_meta['projectName'] ?? '' ) );
	if ( $page_title === '' ) {
		$page_title = ucwords( str_replace( '-', ' ', $slug ) );
	}

	$existing_pages = xpressui_get_workflow_page_ids( $slug );
	if ( ! empty( $existing_pages ) ) {
		return new WP_Error( 'page_exists', __( 'A page using this workflow shortcode already exists.', 'xpressui-bridge' ) );
	}

	$page_id = wp_insert_post( [
		'post_type'    => 'page',
		'post_status'  => 'draft',
		'post_title'   => $page_title,
		'post_content' => '[xpressui id="' . $slug . '"]',
	] );

	if ( is_wp_error( $page_id ) ) {
		return $page_id;
	}

	return (int) $page_id;
}

// ---------------------------------------------------------------------------
// ZIP upload handler
// ---------------------------------------------------------------------------

function xpressui_handle_zip_upload() {
	if ( empty( $_FILES['xpressui_zip']['tmp_name'] ) ) {
		return new WP_Error( 'no_file', __( 'Please select a file.', 'xpressui-bridge' ) );
	}

	$file      = wp_unslash( $_FILES['xpressui_zip'] );
	$file_name = isset( $file['name'] ) ? sanitize_file_name( (string) $file['name'] ) : '';
	$file_type = wp_check_filetype( $file_name );

	if ( $file_type['ext'] !== 'zip' ) {
		return new WP_Error( 'invalid_file_type', __( 'Only .zip files are allowed.', 'xpressui-bridge' ) );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	$upload_overrides = [
		'test_form' => false,
		'mimes'     => [ 'zip' => 'application/zip' ],
	];
	$handled_upload = wp_handle_upload( $file, $upload_overrides );
	if ( ! is_array( $handled_upload ) || ! empty( $handled_upload['error'] ) ) {
		return new WP_Error(
			'upload_failed',
			isset( $handled_upload['error'] ) ? (string) $handled_upload['error'] : __( 'Failed to save the uploaded file.', 'xpressui-bridge' )
		);
	}

	$tmp_zip = isset( $handled_upload['file'] ) ? (string) $handled_upload['file'] : '';
	if ( $tmp_zip === '' || ! file_exists( $tmp_zip ) ) {
		return new WP_Error( 'upload_missing', __( 'Uploaded ZIP file could not be located.', 'xpressui-bridge' ) );
	}

	$inspection = xpressui_validate_workflow_zip( $tmp_zip, $file_name );
	if ( is_wp_error( $inspection ) ) {
		wp_delete_file( $tmp_zip );
		return $inspection;
	}

	$target_dir = xpressui_get_workflows_base_dir();
	if ( $target_dir === '' ) {
		wp_delete_file( $tmp_zip );
		return new WP_Error( 'upload_dir_error', __( 'The uploads directory is not available.', 'xpressui-bridge' ) );
	}

	if ( ! file_exists( $target_dir ) ) {
		wp_mkdir_p( $target_dir );
		global $wp_filesystem;
		$wp_filesystem->put_contents( $target_dir . 'index.php', '<?php' . PHP_EOL . '// Silence is golden.' . PHP_EOL, FS_CHMOD_FILE );
	}

	$slug       = (string) $inspection['slug'];
	$manifest   = is_array( $inspection['manifest'] ?? null ) ? $inspection['manifest'] : [];
	$slug_dir   = trailingslashit( $target_dir ) . $slug . '/';
	$manifest_file = $slug_dir . 'manifest.json';

	global $wp_filesystem;
	if ( file_exists( $slug_dir ) ) {
		$wp_filesystem->delete( $slug_dir, true );
	}
	$unzip_result = unzip_file( $tmp_zip, $target_dir );
	wp_delete_file( $tmp_zip );

	if ( is_wp_error( $unzip_result ) ) {
		return $unzip_result;
	}

	if ( ! file_exists( $manifest_file ) ) {
		$wp_filesystem->delete( $slug_dir, true );
		return new WP_Error( 'missing_manifest', __( 'The workflow package must contain a top-level manifest.json file.', 'xpressui-bridge' ) );
	}

	foreach ( xpressui_get_required_manifest_artifacts( $manifest ) as $required_file ) {
		$required_path = $slug_dir . ltrim( $required_file, '/' );
		if ( ! file_exists( $required_path ) ) {
			$wp_filesystem->delete( $slug_dir, true );
			return new WP_Error(
				'missing_manifest_artifact',
				sprintf(
					/* translators: %s: artifact path */
					__( 'The workflow package is missing a required artifact: %s', 'xpressui-bridge' ),
					$required_file
				)
			);
		}
	}

	$wp_filesystem->put_contents( $slug_dir . 'index.php', '<?php' . PHP_EOL . '// Silence is golden.' . PHP_EOL, FS_CHMOD_FILE );
	xpressui_store_workflow_manifest_meta( $slug, $manifest );

	return $slug;
}

function xpressui_validate_workflow_zip( $zip_path, $original_name ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'zip_extension_missing', __( 'The ZIP extension is required to inspect workflow packages.', 'xpressui-bridge' ) );
	}

	$archive = new ZipArchive();
	if ( true !== $archive->open( $zip_path ) ) {
		return new WP_Error( 'zip_open_failed', __( 'The workflow package could not be opened.', 'xpressui-bridge' ) );
	}

	$allowed_extensions = [
		'html',
		'css',
		'js',
		'json',
		'txt',
		'png',
		'jpg',
		'jpeg',
		'gif',
		'svg',
		'webp',
		'ico',
		'woff',
		'woff2',
		'ttf',
		'eot',
		'map',
	];
	$blocked_extensions = [ 'php', 'phtml', 'phar', 'cgi', 'pl', 'py', 'rb', 'sh', 'exe', 'dll', 'bin' ];
	$root_slug          = '';
	$has_manifest       = false;
	$manifest           = [];
	$required_files     = [];

	for ( $i = 0; $i < $archive->numFiles; $i++ ) {
		$entry_name = (string) $archive->getNameIndex( $i );
		if ( $entry_name === '' ) {
			continue;
		}

		$normalized_entry = str_replace( '\\', '/', $entry_name );
		$normalized_entry = ltrim( $normalized_entry, '/' );

		if ( $normalized_entry === '' || strpos( $normalized_entry, '../' ) !== false ) {
			$archive->close();
			return new WP_Error( 'invalid_zip_path', __( 'The workflow package contains an invalid file path.', 'xpressui-bridge' ) );
		}

		$segments = array_values( array_filter( explode( '/', $normalized_entry ), 'strlen' ) );
		if ( empty( $segments ) ) {
			continue;
		}

		$current_root = sanitize_title( (string) $segments[0] );
		if ( $current_root === '' || $current_root !== $segments[0] ) {
			$archive->close();
			return new WP_Error( 'invalid_zip_slug', __( 'The workflow package folder name must be a valid slug.', 'xpressui-bridge' ) );
		}

		if ( $root_slug === '' ) {
			$root_slug = $current_root;
		} elseif ( $root_slug !== $current_root ) {
			$archive->close();
			return new WP_Error( 'multiple_roots', __( 'The workflow package must contain exactly one top-level folder.', 'xpressui-bridge' ) );
		}

		$is_directory = substr( $normalized_entry, -1 ) === '/';
		if ( $is_directory ) {
			continue;
		}

		$basename = basename( $normalized_entry );
		$ext      = strtolower( (string) pathinfo( $basename, PATHINFO_EXTENSION ) );

		if ( in_array( $ext, $blocked_extensions, true ) ) {
			$archive->close();
			return new WP_Error( 'blocked_file_type', __( 'The workflow package contains a blocked file type.', 'xpressui-bridge' ) );
		}

		if ( $ext !== '' && ! in_array( $ext, $allowed_extensions, true ) ) {
			$archive->close();
			return new WP_Error( 'disallowed_file_type', __( 'The workflow package contains a file type that is not allowed.', 'xpressui-bridge' ) );
		}

		if ( count( $segments ) === 2 && $segments[1] === 'manifest.json' ) {
			$has_manifest = true;
			$manifest_raw = $archive->getFromName( $entry_name );
			$decoded      = is_string( $manifest_raw ) ? json_decode( $manifest_raw, true ) : null;
			if ( ! is_array( $decoded ) ) {
				$archive->close();
				return new WP_Error( 'invalid_manifest', __( 'The workflow manifest must be valid JSON.', 'xpressui-bridge' ) );
			}
			$manifest = $decoded;
		}
	}

	if ( $root_slug === '' ) {
		$archive->close();
		return new WP_Error( 'empty_zip', __( 'The workflow package is empty.', 'xpressui-bridge' ) );
	}

	if ( ! $has_manifest ) {
		$archive->close();
		return new WP_Error( 'missing_manifest', __( 'The workflow package must contain a top-level manifest.json file.', 'xpressui-bridge' ) );
	}

	$manifest_check = xpressui_validate_workflow_manifest( $manifest, $root_slug );
	if ( is_wp_error( $manifest_check ) ) {
		$archive->close();
		return $manifest_check;
	}

	$required_files = xpressui_get_required_manifest_artifacts( $manifest );
	foreach ( $required_files as $required_file ) {
		$relative_path = $root_slug . '/' . ltrim( $required_file, '/' );
		if ( false === $archive->locateName( $relative_path, ZipArchive::FL_NOCASE ) ) {
			$archive->close();
			return new WP_Error(
				'missing_manifest_artifact',
				sprintf(
					/* translators: %s: artifact path */
					__( 'The workflow package is missing a required artifact: %s', 'xpressui-bridge' ),
					$required_file
				)
			);
		}
	}
	$archive->close();

	$fallback_slug = sanitize_title( (string) pathinfo( $original_name, PATHINFO_FILENAME ) );
	return [
		'slug' => $root_slug !== '' ? $root_slug : $fallback_slug,
		'manifest' => $manifest,
	];
}

function xpressui_validate_workflow_manifest( array $manifest, $root_slug ) {
	$schema_version = sanitize_text_field( (string) ( $manifest['schemaVersion'] ?? '' ) );
	if ( ! in_array( $schema_version, [ 'console.export/v1', 'console.export/v2' ], true ) ) {
		return new WP_Error( 'unsupported_manifest_schema', __( 'This workflow manifest schema is not supported.', 'xpressui-bridge' ) );
	}

	$project_slug = sanitize_title( (string) ( $manifest['projectSlug'] ?? '' ) );
	if ( $project_slug === '' || $project_slug !== $root_slug ) {
		return new WP_Error( 'manifest_slug_mismatch', __( 'The workflow manifest project slug must match the package folder name.', 'xpressui-bridge' ) );
	}

	$runtime_requirements = is_array( $manifest['runtimeRequirements'] ?? null ) ? $manifest['runtimeRequirements'] : [];
	$compatibility        = is_array( $manifest['wordpressCompatibility'] ?? null ) ? $manifest['wordpressCompatibility'] : [];
	$tier                 = sanitize_key( (string) ( $runtime_requirements['tier'] ?? 'light' ) );
	$requires_license     = ! empty( $compatibility['requiresLicense'] );

	if ( ! xpressui_runtime_supports_workflow( $tier, $requires_license ) ) {
		return new WP_Error(
			'xpressui_pro_workflow',
			__( 'This workflow requires XPressUI Pro capabilities and cannot run in the current bridge installation.', 'xpressui-bridge' )
		);
	}

	return true;
}

function xpressui_get_required_manifest_artifacts( array $manifest ) {
	$artifacts         = is_array( $manifest['artifacts'] ?? null ) ? $manifest['artifacts'] : [];
	$compatibility     = is_array( $manifest['wordpressCompatibility'] ?? null ) ? $manifest['wordpressCompatibility'] : [];
	$wordpress_artifacts = is_array( $artifacts['wordpress'] ?? null ) ? $artifacts['wordpress'] : [];
	$bridge_mode       = sanitize_key( (string) ( $compatibility['bridgeMode'] ?? 'legacy-shell' ) );
	$required          = [ 'manifest.json' ];

	$config_path = isset( $artifacts['config'] ) ? sanitize_text_field( (string) $artifacts['config'] ) : '';
	if ( $config_path !== '' ) {
		$required[] = $config_path;
	} else {
		$required[] = 'form.config.json';
	}

	$template_context_path = isset( $artifacts['templateContext'] ) ? sanitize_text_field( (string) $artifacts['templateContext'] ) : '';
	if ( $template_context_path !== '' ) {
		$required[] = $template_context_path;
	} else {
		$required[] = 'template.context.json';
	}

	$html_path = isset( $artifacts['html'] ) ? sanitize_text_field( (string) $artifacts['html'] ) : '';
	if ( 'legacy-shell' === $bridge_mode ) {
		$required[] = $html_path !== '' ? $html_path : 'index.html';
	}

	$init_path = isset( $artifacts['initJs'] ) ? sanitize_text_field( (string) $artifacts['initJs'] ) : '';
	if ( 'legacy-shell' === $bridge_mode ) {
		$required[] = $init_path !== '' ? $init_path : 'init.js';
	}

	$runtime_path = isset( $wordpress_artifacts['runtime'] ) ? sanitize_text_field( (string) $wordpress_artifacts['runtime'] ) : '';
	if ( $runtime_path !== '' ) {
		$required[] = $runtime_path;
	}

	return array_values( array_unique( $required ) );
}
