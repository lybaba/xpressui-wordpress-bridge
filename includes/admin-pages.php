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
	if ( ! isset( $_GET['xpressui_pages_filter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_GET['xpressui_pages_filter_nonce'] ) ), 'xpressui_filter_workflow_pages' ) ) {
		return;
	}

	$slug = sanitize_title( wp_unslash( (string) ( $_GET['xpressui_workflow_slug'] ?? '' ) ) );
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
			$manifest_meta = xpressui_get_workflow_manifest_meta( $project_slug );
			$project_title = sanitize_text_field( (string) ( $manifest_meta['projectName'] ?? '' ) );
			$rows[ $project_slug ] = [
				'projectTitle'       => $project_title,
				'projectSlug'        => $project_slug,
				'total'              => 0,
				'new'                => 0,
				'in-review'          => 0,
				'pending_info'       => 0,
				'done'               => 0,
				'rejected'           => 0,
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
	$total_projects = count( $rows );
	$total_submissions = 0;
	$total_new = 0;
	$total_in_review = 0;
	$total_pending_info = 0;
	$total_done = 0;
	$total_rejected = 0;
	foreach ( $rows as $row ) {
		$total_submissions += (int) ( $row['total'] ?? 0 );
		$total_new += (int) ( $row['new'] ?? 0 );
		$total_in_review += (int) ( $row['in-review'] ?? 0 );
		$total_pending_info += (int) ( $row['pending_info'] ?? 0 );
		$total_done += (int) ( $row['done'] ?? 0 );
		$total_rejected += (int) ( $row['rejected'] ?? 0 );
	}

	echo '<div class="wrap xpressui-wrap xpressui-wrap--project-inbox">';
	echo '<h1>' . esc_html__( 'Project Inbox', 'xpressui-bridge' ) . '</h1>';
	echo '<p class="xpressui-page-intro">' . esc_html__( 'Review incoming submissions grouped by project, then jump into filtered queues.', 'xpressui-bridge' ) . '</p>';

	if ( empty( $rows ) ) {
		echo '<div class="card xpressui-admin-card xpressui-admin-card--project-inbox xpressui-empty-state">';
		echo '<p class="xpressui-empty-state__title">' . esc_html__( 'No submissions recorded yet.', 'xpressui-bridge' ) . '</p>';
		echo '<p class="xpressui-empty-state__body">' . esc_html__( 'Incoming submissions will appear here once visitors complete one of your installed workflows.', 'xpressui-bridge' ) . '</p>';
		echo '</div>';
		echo '</div>';
		return;
	}

	$status_filter_url = static function ( $status ) {
		$args = array( 'post_type' => 'xpressui_submission' );
		if ( '' !== $status ) {
			$args['xpressui_status'] = $status;
		}
		return wp_nonce_url( add_query_arg( $args, admin_url( 'edit.php' ) ), 'xpressui_filter_submissions', 'xpressui_filter_nonce' );
	};
	$stat_tile = static function ( $value, $label, $modifier, $url ) {
		$zero  = 0 === (int) $value ? ' is-zero' : '';
		$inner = '<span class="xpressui-inbox-stat__value">' . esc_html( (string) $value ) . '</span>'
			. '<span class="xpressui-inbox-stat__label">' . esc_html( $label ) . '</span>';
		if ( '' === $url ) {
			return '<div class="xpressui-inbox-stat' . esc_attr( $modifier . $zero ) . '">' . $inner . '</div>';
		}
		return '<a class="xpressui-inbox-stat xpressui-inbox-stat--link' . esc_attr( $modifier . $zero ) . '" href="' . esc_url( $url ) . '">' . $inner . '</a>';
	};

	echo '<div class="xpressui-inbox-overview">';
	echo $stat_tile( $total_projects, __( 'Projects', 'xpressui-bridge' ), '', '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside helper
	echo $stat_tile( $total_submissions, __( 'Submissions', 'xpressui-bridge' ), '', $status_filter_url( '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $stat_tile( $total_new, __( 'New', 'xpressui-bridge' ), ' xpressui-inbox-stat--new', $status_filter_url( 'new' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $stat_tile( $total_in_review, __( 'In review', 'xpressui-bridge' ), ' xpressui-inbox-stat--review', $status_filter_url( 'in-review' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $stat_tile( $total_pending_info, __( 'Pending info', 'xpressui-bridge' ), ' xpressui-inbox-stat--pending-info', $status_filter_url( 'pending_info' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $stat_tile( $total_done, __( 'Done', 'xpressui-bridge' ), ' xpressui-inbox-stat--done', $status_filter_url( 'done' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $stat_tile( $total_rejected, __( 'Rejected', 'xpressui-bridge' ), ' xpressui-inbox-stat--rejected', $status_filter_url( 'rejected' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';

	echo '<div class="card xpressui-admin-card xpressui-admin-card--project-inbox">';
	echo '<table class="wp-list-table widefat fixed striped xpressui-table xpressui-table--project-inbox">';
	echo '<colgroup>';
	echo '<col class="xpressui-col-project" />';
	echo '<col class="xpressui-col-total" />';
	echo '<col class="xpressui-col-new" />';
	echo '<col class="xpressui-col-review" />';
	echo '<col class="xpressui-col-pending-info" />';
	echo '<col class="xpressui-col-done" />';
	echo '<col class="xpressui-col-rejected" />';
	echo '<col class="xpressui-col-latest" />';
	echo '<col class="xpressui-col-actions" />';
	echo '</colgroup>';
	echo '<thead><tr>';
	echo '<th>' . esc_html__( 'Project', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Total', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'New', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'In review', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Pending info', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Done', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Rejected', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Latest submission', 'xpressui-bridge' ) . '</th>';
	echo '<th>' . esc_html__( 'Actions', 'xpressui-bridge' ) . '</th>';
	echo '</tr></thead><tbody>';

	foreach ( $rows as $row ) {
		$all_url = wp_nonce_url(
			add_query_arg( [ 'post_type' => 'xpressui_submission', 'xpressui_project' => $row['projectSlug'] ], admin_url( 'edit.php' ) ),
			'xpressui_filter_submissions',
			'xpressui_filter_nonce'
		);
		$new_url = wp_nonce_url(
			add_query_arg( [ 'post_type' => 'xpressui_submission', 'xpressui_project' => $row['projectSlug'], 'xpressui_status' => 'new' ], admin_url( 'edit.php' ) ),
			'xpressui_filter_submissions',
			'xpressui_filter_nonce'
		);

		echo '<tr>';
		$project_title = sanitize_text_field( (string) ( $row['projectTitle'] ?? '' ) );
		$project_slug  = sanitize_title( (string) ( $row['projectSlug'] ?? '' ) );
		echo '<td class="xpressui-cell-project"><strong>' . esc_html( $project_title !== '' ? $project_title : $project_slug ) . '</strong>';
		if ( $project_title !== '' && $project_title !== $project_slug ) {
			echo '<div class="xpressui-muted">' . esc_html( $project_slug ) . '</div>';
		}
		echo '</td>';
		echo '<td><span class="xpressui-badge xpressui-badge--count">' . esc_html( (string) $row['total'] ) . '</span></td>';
		echo '<td><span class="xpressui-badge xpressui-badge--status-new">' . esc_html( (string) $row['new'] ) . '</span></td>';
		echo '<td><span class="xpressui-badge xpressui-badge--status-in-review">' . esc_html( (string) $row['in-review'] ) . '</span></td>';
		echo '<td><span class="xpressui-badge xpressui-badge--status-pending-info">' . esc_html( (string) $row['pending_info'] ) . '</span></td>';
		echo '<td><span class="xpressui-badge xpressui-badge--status-done">' . esc_html( (string) $row['done'] ) . '</span></td>';
		echo '<td><span class="xpressui-badge xpressui-badge--status-rejected">' . esc_html( (string) $row['rejected'] ) . '</span></td>';
		echo '<td class="xpressui-cell-latest-submission">';
		if ( $row['latestSubmissionId'] !== '' ) {
			echo '<code class="xpressui-inline-code">' . esc_html( $row['latestSubmissionId'] ) . '</code>';
			if ( $row['latestDate'] !== '' ) {
				echo '<div class="xpressui-muted">' . esc_html( $row['latestDate'] ) . '</div>';
			}
		} else {
			echo '<span class="xpressui-empty">' . esc_html__( 'No submissions yet', 'xpressui-bridge' ) . '</span>';
		}
		echo '</td>';
		echo '<td class="column-actions"><div class="xpressui-row-actions"><a href="' . esc_url( $all_url ) . '">' . esc_html__( 'Open all', 'xpressui-bridge' ) . '</a><span class="xpressui-row-actions__sep">·</span><a href="' . esc_url( $new_url ) . '">' . esc_html__( 'Open new', 'xpressui-bridge' ) . '</a></div></td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
	echo '</div>';
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
	$queue_url  = wp_nonce_url(
		add_query_arg( [ 'post_type' => 'xpressui_submission', 'xpressui_assignee' => $current_user_id ], admin_url( 'edit.php' ) ),
		'xpressui_filter_submissions',
		'xpressui_filter_nonce'
	);
	$review_url = wp_nonce_url(
		add_query_arg( [ 'post_type' => 'xpressui_submission', 'xpressui_assignee' => $current_user_id, 'xpressui_status' => 'in-review' ], admin_url( 'edit.php' ) ),
		'xpressui_filter_submissions',
		'xpressui_filter_nonce'
	);

	echo '<div class="wrap xpressui-wrap">';
	echo '<h1>' . esc_html__( 'My Queue', 'xpressui-bridge' ) . '</h1>';
	echo '<p class="xpressui-page-intro">' . esc_html__( 'Open the submissions assigned to you, or jump straight into the ones already in review.', 'xpressui-bridge' ) . '</p>';
	echo '<div class="card xpressui-admin-card xpressui-admin-card--compact">';
	echo '<p class="xpressui-card-intro">' . esc_html__( 'Use these shortcuts to open your current workload in the standard WordPress submission list.', 'xpressui-bridge' ) . '</p>';
	echo '<p class="xpressui-button-row">';
	echo '<a class="button button-primary" href="' . esc_url( $queue_url ) . '">' . esc_html__( 'Open my submissions', 'xpressui-bridge' ) . '</a> ';
	echo '<a class="button" href="' . esc_url( $review_url ) . '">' . esc_html__( 'Open my in-review queue', 'xpressui-bridge' ) . '</a>';
	echo '</p></div></div>';
}

// ---------------------------------------------------------------------------
// Manage Workflows (zip upload + project settings)
// ---------------------------------------------------------------------------

function xpressui_set_admin_notice( $message, $type = 'success' ) {
	set_transient( 'xpressui_notice_' . get_current_user_id(), [
		'message' => $message,
		'type'    => $type,
	], 30 );
}

function xpressui_get_admin_notice() {
	$user_id   = get_current_user_id();
	$transient = 'xpressui_notice_' . $user_id;
	$notice    = get_transient( $transient );
	if ( $notice ) {
		delete_transient( $transient );
		return $notice;
	}
	return null;
}

function xpressui_render_workflows_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'xpressui-bridge' ) );
	}

	$notice_class   = '';
	$notice_message = '';

	$notice = xpressui_get_admin_notice();
	if ( $notice ) {
		$notice_message = $notice['message'];
		$notice_class   = ( $notice['type'] === 'error' ) ? 'notice-error' : 'notice-success';
	}

	// Project settings are now managed per-workflow on the Workflow Settings page (Settings link in each row).

	if ( $notice_message ) {
		echo '<div class="notice ' . esc_attr( $notice_class ) . ' is-dismissible"><p>' . wp_kses_post( $notice_message ) . '</p></div>';
	}

	echo '<div class="wrap xpressui-wrap">';
	echo '<h1>' . esc_html__( 'Workflows', 'xpressui-bridge' ) . '</h1>';
	echo '<p class="xpressui-page-intro">' . esc_html__( 'Manage your installed workflow packages and configure per-project settings.', 'xpressui-bridge' ) . '</p>';

	$runtime_health = xpressui_get_runtime_health_summary();

	echo '<div class="card xpressui-admin-card">';
	echo '<h2>' . esc_html__( 'Runtime Health', 'xpressui-bridge' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'Shows which runtime the plugin shell will try to load and whether the bundled files are present.', 'xpressui-bridge' ) . '</p>';
	echo '<table class="widefat striped"><tbody>';
	echo '<tr>';
	echo '<td><strong>' . esc_html__( 'Active shell runtime', 'xpressui-bridge' ) . '</strong></td>';
	echo '<td><code>' . esc_html( (string) ( $runtime_health['activeRuntimeSource'] ?? '' ) ) . '</code></td>';
	echo '<td><code>' . esc_html( (string) ( $runtime_health['activeRuntimeUrl'] ?? '' ) ) . '</code></td>';
	echo '</tr>';
	echo '<tr>';
	echo '<td><strong>' . esc_html__( 'Bundled standard runtime', 'xpressui-bridge' ) . '</strong></td>';
	echo '<td>' . ( ! empty( $runtime_health['bridge']['exists'] ) ? '<span class="xpressui-badge">' . esc_html__( 'Present', 'xpressui-bridge' ) . '</span>' : '<span class="xpressui-badge xpressui-badge--status-new">' . esc_html__( 'Missing', 'xpressui-bridge' ) . '</span>' ) . '</td>';
	echo '<td><code>' . esc_html( (string) ( $runtime_health['bridge']['url'] ?? '' ) ) . '</code></td>';
	echo '</tr>';
	echo '</tbody></table>';
	echo '</div>';

	$installed_slugs = xpressui_get_installed_workflow_slugs();
	$bundled_slugs = xpressui_get_bundled_workflow_slugs();

	$starter_slug    = 'file-request';
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
		echo '<p>' . esc_html__( 'The File Request workflow is bundled and ready to use. Add it to any page with the shortcode below, or create a dedicated page in one click.', 'xpressui-bridge' ) . '</p>';
		echo '<p><strong>' . esc_html__( 'Workflow:', 'xpressui-bridge' ) . '</strong> <code>[xpressui id="file-request"]</code></p>';
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

	$visible_installed_slugs = $installed_slugs;

	$all_settings = get_option( 'xpressui_project_settings', [] );
	$render_workflow_table = static function ( array $slugs ) use ( $all_settings ) {
		echo '<table class="wp-list-table widefat fixed striped xpressui-table xpressui-table--workflows">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Workflow', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Tier', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Source', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Shortcode', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Notify email', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Redirect URL', 'xpressui-bridge' ) . '</th>';
		echo '<th>' . esc_html__( 'Actions', 'xpressui-bridge' ) . '</th>';
		echo '</tr></thead><tbody>';
		foreach ( $slugs as $slug ) {
			$settings      = $all_settings[ $slug ] ?? [];
			$manifest_meta = xpressui_get_workflow_manifest_meta( $slug );
			$notify_email  = (string) ( $settings['notifyEmail'] ?? '' );
			$redirect_url  = (string) ( $settings['redirectUrl'] ?? '' );
			$runtime_tier  = (string) ( $manifest_meta['runtimeTier'] ?? 'light' );
			$display_tier  = $runtime_tier !== '' ? $runtime_tier : 'light';
			$is_bundled    = ! empty( $manifest_meta['isBundled'] ) || xpressui_is_bundled_workflow( $slug );
			$update_available = $is_bundled && xpressui_is_bundled_workflow_update_available( $slug );
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
			$open_page_url = wp_nonce_url(
				add_query_arg(
					[
						'post_type'              => 'page',
						'post_status'            => 'all',
						'xpressui_workflow_slug' => $slug,
					],
					admin_url( 'edit.php' )
				),
				'xpressui_filter_workflow_pages',
				'xpressui_pages_filter_nonce'
			);
			echo '<tr>';
			$project_name = sanitize_text_field( (string) ( $manifest_meta['projectName'] ?? '' ) );
			echo '<td class="xpressui-cell-project"><strong>' . esc_html( $slug ) . '</strong>';
			if ( '' !== $project_name ) {
				echo '<br /><span class="xpressui-muted">' . esc_html( $project_name ) . '</span>';
			}
			echo '</td>';
			echo '<td><span class="xpressui-badge xpressui-badge--muted">' . esc_html( $display_tier ) . '</span></td>';
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
			echo '<td class="xpressui-cell-shortcode"><code class="xpressui-inline-code">[xpressui id="' . esc_attr( $slug ) . '"]</code></td>';
			echo '<td class="xpressui-cell-email">' . ( $notify_email !== '' ? '<a href="mailto:' . esc_attr( antispambot( $notify_email ) ) . '">' . esc_html( antispambot( $notify_email ) ) . '</a>' : '—' ) . '</td>';
			echo '<td class="xpressui-cell-url">' . ( $redirect_url !== '' ? '<a href="' . esc_url( $redirect_url ) . '" target="_blank" rel="noreferrer">' . esc_html( $redirect_url ) . '</a>' : '—' ) . '</td>';
			echo '<td class="column-actions">';
			// Allow extensions to inject extra action links (e.g. "Customize" from the pro plugin).
			$extra_row_actions = apply_filters( 'xpressui_workflow_row_actions', [], $slug );
			echo '<div class="xpressui-row-actions">';
			foreach ( $extra_row_actions as $action_html ) {
				echo wp_kses_post( $action_html );
				echo '<span class="xpressui-row-actions__sep">·</span>';
			}
			echo '<a href="' . esc_url( $create_page_url ) . '">' . esc_html__( 'Create page', 'xpressui-bridge' ) . '</a><span class="xpressui-row-actions__sep">·</span>';
			echo '<a href="' . esc_url( $open_page_url ) . '">' . esc_html__( 'Find pages', 'xpressui-bridge' ) . '</a>';
			if ( $edit_page_url ) {
				echo '<span class="xpressui-row-actions__sep">·</span><a href="' . esc_url( $edit_page_url ) . '">' . esc_html__( 'Edit page', 'xpressui-bridge' ) . '</a>';
			}
			if ( $view_page_url ) {
				echo '<span class="xpressui-row-actions__sep">·</span><a href="' . esc_url( $view_page_url ) . '" target="_blank" rel="noreferrer">' . esc_html__( 'View page', 'xpressui-bridge' ) . '</a>';
			}
			if ( $is_bundled ) {
				echo '<span class="xpressui-row-actions__sep">·</span><a href="' . esc_url( $reinstall_url ) . '">' . esc_html( $update_available ? __( 'Update', 'xpressui-bridge' ) : __( 'Reinstall', 'xpressui-bridge' ) ) . '</a>';
				echo '<span class="xpressui-row-actions__sep">·</span><span class="xpressui-muted" title="' . esc_attr__( 'Bundled starter workflows cannot be deleted.', 'xpressui-bridge' ) . '">' . esc_html__( 'Delete', 'xpressui-bridge' ) . '</span>';
			} else {
				echo '<span class="xpressui-row-actions__sep">·</span><a href="' . esc_url( $delete_url ) . '">' . esc_html__( 'Delete', 'xpressui-bridge' ) . '</a>';
			}
			echo '</div>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	};

	do_action( 'xpressui_workflows_page_sections' );

	// --- Installed workflows table ---
	echo '<div class="card xpressui-admin-card">';
	echo '<h2>' . esc_html__( 'Installed Workflows', 'xpressui-bridge' ) . '</h2>';
	if ( empty( $visible_installed_slugs ) ) {
		echo '<div class="xpressui-empty-state"><p class="xpressui-empty-state__title">' . esc_html__( 'No workflows installed yet.', 'xpressui-bridge' ) . '</p><p class="xpressui-empty-state__body">' . esc_html__( 'Upload a workflow package below to get started, or use the bundled starter workflow already included with the plugin.', 'xpressui-bridge' ) . '</p></div>';
	} else {
		$render_workflow_table( $visible_installed_slugs );
	}
	echo '</div>';

	// Project settings have moved to the per-workflow Settings page (Settings link in each row).

	echo '</div>'; // .wrap
}

function xpressui_handle_workflow_admin_actions() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}


	$action = isset( $_GET['xpressui_action'] ) ? sanitize_key( wp_unslash( (string) $_GET['xpressui_action'] ) ) : '';

	$slug   = isset( $_GET['xpressui_slug'] ) ? sanitize_title( wp_unslash( (string) $_GET['xpressui_slug'] ) ) : '';
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
		xpressui_set_admin_notice( $message, $status );
		wp_safe_redirect(
			add_query_arg(
				[
					'post_type'          => 'xpressui_submission',
					'page'               => 'xpressui-bridge',
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
		xpressui_set_admin_notice( $message, $status );
		wp_safe_redirect(
			add_query_arg(
				[
					'post_type'          => 'xpressui_submission',
					'page'               => 'xpressui-bridge',
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
			xpressui_set_admin_notice( $result->get_error_message(), 'error' );
			wp_safe_redirect(
				add_query_arg(
					[
						'post_type'            => 'xpressui_submission',
						'page'                 => 'xpressui-bridge',
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


function xpressui_validate_workflow_zip( $zip_path, $original_name ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error( 'zip_extension_missing', __( 'The ZIP extension is required to inspect workflow packages.', 'xpressui-bridge' ) );
	}

	$archive = new ZipArchive();
	if ( true !== $archive->open( $zip_path ) ) {
		return new WP_Error( 'zip_open_failed', __( 'The workflow package could not be opened.', 'xpressui-bridge' ) );
	}

	$allowed_extensions = [
		'json',
		'md',
		'txt',
		'png',
		'jpg',
		'jpeg',
		'gif',
		'webp',
		'ico',
		'woff',
		'woff2',
		'ttf',
		'eot',
	];
	$blocked_extensions = [
		// PHP variants
		'php', 'php3', 'php4', 'php5', 'php6', 'php7', 'php8', 'phtml', 'phar',
		// Server config that can re-enable PHP execution in uploads
		'htaccess', 'htpasswd', 'user.ini',
		// Other server-side / executable types
		'cgi', 'pl', 'py', 'rb', 'sh', 'bash', 'asp', 'aspx', 'jsp', 'cfm',
		'exe', 'dll', 'bin', 'bat', 'cmd', 'msi',
	];
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

		// Reject files with no extension (e.g. .htaccess, .env, Makefile).
		// No legitimate workflow asset omits a file extension.
		if ( $ext === '' ) {
			$archive->close();
			return new WP_Error( 'disallowed_file_type', __( 'The workflow package contains a file type that is not allowed.', 'xpressui-bridge' ) );
		}

		if ( ! in_array( $ext, $allowed_extensions, true ) ) {
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

	return true;
}

function xpressui_get_required_manifest_artifacts( array $manifest ) {
	$artifacts         = is_array( $manifest['artifacts'] ?? null ) ? $manifest['artifacts'] : [];
	$required          = [ 'manifest.json' ];

	$config_path = isset( $artifacts['config'] ) ? sanitize_text_field( (string) $artifacts['config'] ) : '';
	if ( $config_path !== '' ) {
		$required[] = $config_path;
	} else {
		$required[] = 'form.config.json';
	}

	if ( isset( $artifacts['templateContext'] ) ) {
		$template_context_path = sanitize_text_field( (string) $artifacts['templateContext'] );
		if ( '' !== $template_context_path ) {
			$required[] = $template_context_path;
		}
	}

	return array_values( array_unique( $required ) );
}

// Project settings (AJAX handler removed — settings are now saved via the Workflow Settings page).
