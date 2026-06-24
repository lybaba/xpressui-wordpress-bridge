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
	$api_token = isset( $_SERVER['HTTP_X_API_TOKEN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_API_TOKEN'] ) ) : '';
    $configured_token = get_option( 'xpressui_api_token' );
    $is_api_request = ( ! empty( $configured_token ) && ! empty( $api_token ) && hash_equals( $configured_token, $api_token ) );
    if ( ! $is_api_request ) {
    if ( ! isset( $_GET['xpressui_pages_filter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( (string) $_GET['xpressui_pages_filter_nonce'] ) ), 'xpressui_filter_workflow_pages' ) ) {
		return;
	}
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


function xpressui_register_admin_page() {
	add_submenu_page(
		'edit.php?post_type=xpressui_submission',
		__( 'Manage Workflows', 'xpressui-bridge' ),
		__( 'Workflows', 'xpressui-bridge' ),
		'manage_options',
		'xpressui-bridge',
		'xpressui_render_workflows_page'
	);

	$settings_hook = add_submenu_page(
		'edit.php?post_type=xpressui_submission',
		__( 'Workflow Settings', 'xpressui-bridge' ),
		__( 'Workflow Settings', 'xpressui-bridge' ),
		'manage_options',
		'xpressui-workflow-settings',
		'xpressui_render_workflow_settings_page'
	);
	remove_submenu_page( 'edit.php?post_type=xpressui_submission', 'xpressui-workflow-settings' );

	if ( $settings_hook ) {
		add_action( 'load-' . $settings_hook, function () {
			global $title;
			$title = __( 'Workflow Settings', 'xpressui-bridge' );
		} );
	}
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

	// The "Details" links carry a nonce (see wp_nonce_url on $detail_url below); verify it
	// before using the routing params, and fall back to the list view when it is absent or
	// invalid (e.g. a stale bookmark). This satisfies nonce verification without an ignore.
	$view       = 'list';
	$view_nonce = isset( $_GET['xpressui_view_nonce'] ) ? sanitize_key( wp_unslash( $_GET['xpressui_view_nonce'] ) ) : '';
	if ( $view_nonce && wp_verify_nonce( $view_nonce, 'xpressui_view_workflow' ) ) {
		$view = isset( $_GET['xpressui_view'] ) ? sanitize_key( wp_unslash( (string) $_GET['xpressui_view'] ) ) : 'list';
	}
	if ( 'detail' === $view ) {
		$slug = isset( $_GET['xpressui_slug'] ) ? sanitize_title( wp_unslash( (string) $_GET['xpressui_slug'] ) ) : '';
		if ( $slug !== '' && xpressui_is_installed_workflow( $slug ) ) {
			xpressui_render_workflow_detail_page( $slug );
			return;
		}
	}

	$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'list';

	$notice_class   = '';
	$notice_message = '';

	$notice = xpressui_get_admin_notice();
	if ( $notice ) {
		$notice_message = $notice['message'];
		$notice_class   = ( $notice['type'] === 'error' ) ? 'notice-error' : 'notice-success';
	}

	if ( $notice_message ) {
		echo '<div class="notice ' . esc_attr( $notice_class ) . ' is-dismissible"><p>' . wp_kses_post( $notice_message ) . '</p></div>';
	}

	echo '<div class="wrap xpressui-wrap xpressui-wrap--workflows">';
	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Workflows', 'xpressui-bridge' ) . '</h1>';
	if ( 'list' === $current_tab && xpressui_is_saas_connected() ) {
		echo '<button type="button" id="xpressui-global-sync-btn" class="page-title-action button button-primary" style="margin-left: 10px;">' . esc_html__( 'Sync from Console', 'xpressui-bridge' ) . '</button>';
	}
	echo '<hr class="wp-header-end">';
	
	if ( 'list' === $current_tab ) {
		echo '<p class="xpressui-page-intro">' . esc_html__( 'Manage your installed workflow packages and configure per-workflow settings.', 'xpressui-bridge' ) . '</p>';
	} else {
		echo '<p class="xpressui-page-intro">' . esc_html__( 'Convert legacy contact forms from other plugins into modern multi-step IntakeFlow portal workflows.', 'xpressui-bridge' ) . '</p>';
	}

	// Tabbed navigation
	echo '<h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">';
	echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-bridge&tab=list' ) ) . '" class="nav-tab ' . ( 'list' === $current_tab ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Installed Workflows', 'xpressui-bridge' ) . '</a>';
	echo '<a href="' . esc_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-bridge&tab=import' ) ) . '" class="nav-tab ' . ( 'import' === $current_tab ? 'nav-tab-active' : '' ) . '">' . esc_html__( 'Import', 'xpressui-bridge' ) . '</a>';
	echo '</h2>';

	if ( 'import' === $current_tab ) {
		if ( function_exists( 'xpressui_render_form_importer_tab' ) ) {
			xpressui_render_form_importer_tab();
		}
		echo '</div>'; // .wrap
		return;
	}

	// Fetch Inbox rows for column display
	$inbox_rows = xpressui_get_project_inbox_rows();
	$inbox_by_slug = [];
	foreach ( $inbox_rows as $row ) {
		$inbox_by_slug[ $row['projectSlug'] ] = $row;
	}

	if ( ! xpressui_is_saas_connected() ) {
		echo '<div class="notice notice-warning inline" style="margin-top: 15px; max-width: 900px;"><p>';
		printf(
			/* translators: %s: Settings page URL */
			esc_html__( 'To sync workflows directly from your Console, please configure your API Token on the %s.', 'xpressui-bridge' ),
			'<a href="' . esc_url( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-settings' ) ) . '">' . esc_html__( 'Settings page', 'xpressui-bridge' ) . '</a>'
		);
		echo '</p></div>';
	} else {
		echo '<div id="xpressui-global-sync-container"></div>';
	}

	$installed_slugs = xpressui_get_installed_workflow_slugs();
	$bundled_slugs = xpressui_get_bundled_workflow_slugs();

	// --- List controls: search → sort → paginate -----------------------------
	// Query args (all on the Installed Workflows tab):
	//   wf_s       search string (title or slug substring)
	//   wf_orderby title | submissions  (whitelisted)
	//   wf_order   asc | desc
	//   wf_paged   1-based page number
	//   wf_status  all | active | archived
	$archived_slugs = get_option( 'xpressui_archived_workflows', [] );
	if ( ! is_array( $archived_slugs ) ) {
		$archived_slugs = [];
	}
	$all_count      = count( $installed_slugs );
	$archived_count = count( array_intersect( $installed_slugs, $archived_slugs ) );
	$active_count   = $all_count - $archived_count;

	$status_filter  = isset( $_GET['wf_status'] ) ? sanitize_key( wp_unslash( $_GET['wf_status'] ) ) : 'active';
	if ( ! in_array( $status_filter, [ 'all', 'active', 'archived' ], true ) ) {
		$status_filter = 'active';
	}

	$wf_search  = isset( $_GET['wf_s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['wf_s'] ) ) : '';
	$wf_orderby = isset( $_GET['wf_orderby'] ) ? sanitize_key( wp_unslash( (string) $_GET['wf_orderby'] ) ) : '';
	if ( ! in_array( $wf_orderby, [ 'title', 'submissions' ], true ) ) {
		$wf_orderby = '';
	}
	$wf_order = isset( $_GET['wf_order'] ) ? strtolower( sanitize_key( wp_unslash( (string) $_GET['wf_order'] ) ) ) : 'asc';
	if ( ! in_array( $wf_order, [ 'asc', 'desc' ], true ) ) {
		$wf_order = 'asc';
	}
	$wf_paged   = isset( $_REQUEST['wf_paged'] ) ? max( 1, absint( $_REQUEST['wf_paged'] ) ) : 1;
	$wf_per_page = 20;

	// Build a metadata row for every installed workflow (display name, submission
	// count, version) once. These values are needed for display anyway, so we
	// reuse them for filtering and sorting and avoid recomputing per page.
	$workflow_rows = [];
	foreach ( $installed_slugs as $slug ) {
		$meta         = xpressui_get_workflow_manifest_meta( $slug );
		$project_name = sanitize_text_field( (string) ( $meta['projectName'] ?? '' ) );
		$display_name = $project_name !== '' ? $project_name : $slug;
		$version      = ! empty( $meta['runtimeVersion'] ) ? sanitize_text_field( $meta['runtimeVersion'] ) : '1.0.0';
		$submissions  = ( isset( $inbox_by_slug[ $slug ] ) ) ? (int) $inbox_by_slug[ $slug ]['total'] : 0;
		$workflow_rows[] = [
			'slug'        => $slug,
			'title'       => $display_name,
			'version'     => $version,
			'submissions' => $submissions,
		];
	}

	// Filter based on status filter first
	$workflow_rows = array_values( array_filter(
		$workflow_rows,
		function ( $row ) use ( $status_filter, $archived_slugs ) {
			$is_archived = in_array( $row['slug'], $archived_slugs, true );
			if ( 'archived' === $status_filter ) {
				return $is_archived;
			} elseif ( 'active' === $status_filter ) {
				return ! $is_archived;
			}
			return true;
		}
	) );

	// Filter (search) on the full set, before sort + paginate.
	if ( $wf_search !== '' ) {
		$needle = strtolower( $wf_search );
		$workflow_rows = array_values( array_filter(
			$workflow_rows,
			function ( $row ) use ( $needle ) {
				return ( strpos( strtolower( $row['title'] ), $needle ) !== false )
					|| ( strpos( strtolower( $row['slug'] ), $needle ) !== false );
			}
		) );
	}

	// Sort the full (filtered) set, before paginate. No explicit sort keeps the
	// installed order; an explicit wf_orderby applies the requested comparator.
	if ( $wf_orderby !== '' ) {
		usort(
			$workflow_rows,
			function ( $a, $b ) use ( $wf_orderby ) {
				if ( 'submissions' === $wf_orderby ) {
					return $a['submissions'] <=> $b['submissions'];
				}
				return strcasecmp( (string) $a['title'], (string) $b['title'] );
			}
		);
		if ( 'desc' === $wf_order ) {
			$workflow_rows = array_reverse( $workflow_rows );
		}
	}

	$total_items = count( $workflow_rows );
	$total_pages = max( 1, (int) ceil( $total_items / $wf_per_page ) );
	if ( $wf_paged > $total_pages ) {
		$wf_paged = $total_pages;
	}
	$page_rows  = array_slice( $workflow_rows, ( $wf_paged - 1 ) * $wf_per_page, $wf_per_page );
	$visible_installed_slugs = wp_list_pluck( $page_rows, 'slug' );

	// Base URL for all list-control links (keeps tab + search + sort + page args).
	$wf_base_args = [
		'post_type' => 'xpressui_submission',
		'page'      => 'xpressui-bridge',
		'tab'       => 'list',
	];
	if ( $status_filter !== 'active' ) {
		$wf_base_args['wf_status'] = $status_filter;
	}
	if ( $wf_search !== '' ) {
		$wf_base_args['wf_s'] = $wf_search;
	}
	if ( $wf_orderby !== '' ) {
		$wf_base_args['wf_orderby'] = $wf_orderby;
		$wf_base_args['wf_order']   = $wf_order;
	}
	$wf_base_url = add_query_arg( $wf_base_args, admin_url( 'edit.php' ) );

	// Sortable column header helper — emits a <th> with the WP .sortable/.sorted
	// classes and an anchor that toggles asc/desc for that column.
	$render_sortable_th = function ( $column, $label, $extra_style = '', $extra_class = '' ) use ( $wf_orderby, $wf_order, $wf_base_args ) {
		$is_sorted   = ( $wf_orderby === $column );
		$cur_order   = $is_sorted ? $wf_order : '';
		// Next order when clicking: toggle if already sorted, else asc.
		$next_order  = ( $is_sorted && 'asc' === $wf_order ) ? 'desc' : 'asc';
		$indicator   = $is_sorted ? ( 'asc' === $wf_order ? 'asc' : 'desc' ) : 'desc';
		$th_class    = trim( 'manage-column sortable ' . ( $is_sorted ? 'sorted ' . $cur_order : $indicator ) . ( $extra_class ? ' ' . $extra_class : '' ) );
		$link_args   = $wf_base_args;
		$link_args['wf_orderby'] = $column;
		$link_args['wf_order']   = $next_order;
		unset( $link_args['wf_paged'] ); // Reset to page 1 on sort change.
		$link = add_query_arg( $link_args, admin_url( 'edit.php' ) );
		$style = 'font-weight: 700;' . ( $extra_style ? ' ' . $extra_style : '' );
		echo '<th scope="col" class="' . esc_attr( $th_class ) . '" style="' . esc_attr( $style ) . '">';
		echo '<a href="' . esc_url( $link ) . '"><span>' . esc_html( $label ) . '</span><span class="sorting-indicator"></span></a>';
		echo '</th>';
	};

	$render_workflow_table = function ( array $slugs ) use ( $inbox_by_slug, $render_sortable_th, $status_filter ) {
		echo '<table class="wp-list-table widefat fixed striped xpressui-table xpressui-table--workflows">';
		echo '<thead><tr>';
		echo '<th scope="col" id="cb" class="manage-column column-cb check-column" style="width: 30px; padding: 8px 10px; vertical-align: middle;"><input id="cb-select-all-1" type="checkbox" /></th>';
		$render_sortable_th( 'title', __( 'Workflow', 'xpressui-bridge' ), '', 'column-title column-primary' );
		$render_sortable_th( 'submissions', __( 'Submissions', 'xpressui-bridge' ), 'width: 150px;' );
		echo '</tr></thead><tbody>';
		foreach ( $slugs as $slug ) {
			$manifest_meta = xpressui_get_workflow_manifest_meta( $slug );
			$runtime_tier  = (string) ( $manifest_meta['runtimeTier'] ?? 'light' );
			$display_tier  = $runtime_tier !== '' ? $runtime_tier : 'light';
			$is_bundled    = ! empty( $manifest_meta['isBundled'] ) || xpressui_is_bundled_workflow( $slug );
			$update_available = $is_bundled && xpressui_is_bundled_workflow_update_available( $slug );
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
			$detail_url = wp_nonce_url(
				add_query_arg(
					[
						'post_type'     => 'xpressui_submission',
						'page'          => 'xpressui-bridge',
						'xpressui_view' => 'detail',
						'xpressui_slug' => $slug,
					],
					admin_url( 'edit.php' )
				),
				'xpressui_view_workflow',
				'xpressui_view_nonce'
			);
			$all_submissions_url = wp_nonce_url(
				add_query_arg( [ 'post_type' => 'xpressui_submission', 'xpressui_project' => $slug ], admin_url( 'edit.php' ) ),
				'xpressui_filter_submissions',
				'xpressui_filter_nonce'
			);
			
			echo '<tr class="xpressui-workflow-row" data-slug="' . esc_attr( $slug ) . '" data-bundled="' . ( $is_bundled ? '1' : '0' ) . '">';
			echo '<th scope="row" class="check-column" style="padding: 8px 10px; vertical-align: middle;"><input id="cb-select-' . esc_attr( $slug ) . '" type="checkbox" name="xpressui_workflow_checkboxes[]" value="' . esc_attr( $slug ) . '" /></th>';
			$project_name = sanitize_text_field( (string) ( $manifest_meta['projectName'] ?? '' ) );
			$display_name = $project_name !== '' ? $project_name : $slug;
			
			echo '<td class="column-title column-primary">';
			echo '<strong><a href="' . esc_url( $detail_url ) . '" style="font-size: 14px; text-decoration: none;">' . esc_html( $display_name ) . '</a></strong>';
			
			// Update available Badge
			if ( $update_available ) {
				echo ' <span class="xpressui-badge xpressui-badge--status-in-review" style="margin-left: 8px; vertical-align: middle;">' . esc_html__( 'Update available', 'xpressui-bridge' ) . '</span>';
			}

			// Show slug underneath
			echo '<div style="margin-top: 4px; font-size: 11px; color: #888;">';
			echo '<code>' . esc_html( $slug ) . '</code>';
			echo '</div>';
			
			// Native WordPress style row actions
			$extra_row_actions = apply_filters( 'xpressui_workflow_row_actions', [], $slug );
			echo '<div class="row-actions">';
			$actions_html = [];
			foreach ( $extra_row_actions as $action_html ) {
				$actions_html[] = '<span>' . $action_html . '</span>';
			}
			$actions_html[] = '<span class="view"><a href="' . esc_url( $detail_url ) . '">' . esc_html__( 'Details', 'xpressui-bridge' ) . '</a></span>';
			$customize_url = wp_nonce_url(
				add_query_arg(
					[
						'post_type'     => 'xpressui_submission',
						'page'          => 'xpressui-workflow-settings',
						'xpressui_slug' => $slug,
					],
					admin_url( 'edit.php' )
				),
				'xpressui_view_workflow',
				'xpressui_view_nonce'
			);
			$actions_html[] = '<span class="edit"><a href="' . esc_url( $customize_url ) . '">' . esc_html__( 'Customize', 'xpressui-bridge' ) . '</a></span>';
			// "Create on Console" — only for not-yet-synced (local-only) workflows, and
			// only when an active Console API token is configured. Wired to AJAX via
			// assets/admin-workflows.js (nonce in window.xpressuiBridgeAdmin).
			if ( xpressui_is_saas_connected() && xpressui_workflow_is_local_only( $slug ) ) {
				$actions_html[] = '<span class="xpressui-create-on-console"><a href="#" class="xpressui-create-on-console-link" data-slug="' . esc_attr( $slug ) . '">' . esc_html__( 'Create on Console', 'xpressui-bridge' ) . '</a></span>';
			}
			if ( xpressui_is_saas_connected() && ! xpressui_workflow_is_local_only( $slug ) ) {
				$sync_link = wp_nonce_url(
					add_query_arg( [ 'xpressui_action' => 'sync_workflow_item', 'xpressui_slug' => $slug ] ),
					'xpressui_sync_workflow_' . $slug
				);
				$actions_html[] = '<span class="xpressui-sync-from-console"><a href="' . esc_url( $sync_link ) . '">' . esc_html__( 'Sync from Console', 'xpressui-bridge' ) . '</a></span>';
			}
			if ( isset( $inbox_by_slug[ $slug ] ) && (int) $inbox_by_slug[ $slug ]['total'] > 0 ) {
				$actions_html[] = '<span class="submissions"><a href="' . esc_url( $all_submissions_url ) . '">' . esc_html__( 'Submissions', 'xpressui-bridge' ) . '</a></span>';
			}
			if ( 'archived' !== $status_filter ) {
				$archive_link = wp_nonce_url(
					add_query_arg( [ 'xpressui_action' => 'archive_workflow_item', 'xpressui_slug' => $slug ] ),
					'xpressui_archive_workflow_' . $slug
				);
				$actions_html[] = '<span class="archive"><a href="' . esc_url( $archive_link ) . '" style="color: #b32d2e;">' . esc_html__( 'Archive', 'xpressui-bridge' ) . '</a></span>';
			} else {
				$restore_link = wp_nonce_url(
					add_query_arg( [ 'xpressui_action' => 'restore_workflow_item', 'xpressui_slug' => $slug ] ),
					'xpressui_restore_workflow_' . $slug
				);
				$actions_html[] = '<span class="restore"><a href="' . esc_url( $restore_link ) . '">' . esc_html__( 'Restore', 'xpressui-bridge' ) . '</a></span>';
			}
			echo wp_kses_post( implode( ' | ', $actions_html ) );
			echo '</div>';
			echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__( 'Show more details', 'xpressui-bridge' ) . '</span></button>';
			echo '</td>';

			// Submissions Column
			echo '<td style="vertical-align: middle; font-size: 13px;">';
			if ( isset( $inbox_by_slug[ $slug ] ) && (int) $inbox_by_slug[ $slug ]['total'] > 0 ) {
				$total = (int) $inbox_by_slug[ $slug ]['total'];
				$new   = (int) $inbox_by_slug[ $slug ]['new'];
				echo '<a href="' . esc_url( $all_submissions_url ) . '" style="font-size: 14px; font-weight: 600; text-decoration: none;">' . esc_html( (string) $total ) . '</a>';
				if ( $new > 0 ) {
					/* translators: %d: number of new (unread) submissions */
					echo ' <span class="xpressui-badge xpressui-badge--status-new" style="margin-left: 6px; font-size: 10px; padding: 1px 6px; vertical-align: middle;">' . sprintf( esc_html__( '%d new', 'xpressui-bridge' ), (int) $new ) . '</span>';
				}
			} else {
				echo '<span style="color: #888; font-style: italic;">&mdash;</span>';
			}
			echo '</td>';
			
			echo '</tr>';
		}
		echo '</tbody></table>';
	};

	do_action( 'xpressui_workflows_page_sections' );

	// --- Pagination controls (WP .tablenav-pages) ----------------------------
	$render_wf_pagination = function ( $position ) use ( $total_items, $total_pages, $wf_paged, $wf_base_url, $wf_search ) {
		if ( $total_items < 1 ) {
			return;
		}
		echo '<div class="tablenav ' . esc_attr( $position ) . '">';

		// Bulk actions dropdown inside .tablenav
		$select_id = 'top' === $position ? 'bulk-action-selector-top' : 'bulk-action-selector-bottom';
		$select_name = 'top' === $position ? 'xpressui_bulk_action_select' : 'xpressui_bulk_action_select2';
		$submit_id = 'top' === $position ? 'doaction' : 'doaction2';

		echo '<div class="alignleft actions bulkactions">';
		echo '<label for="' . esc_attr( $select_id ) . '" class="screen-reader-text">' . esc_html__( 'Select bulk action', 'xpressui-bridge' ) . '</label>';
		echo '<select name="' . esc_attr( $select_name ) . '" id="' . esc_attr( $select_id ) . '">';
		echo '<option value="-1">' . esc_html__( 'Bulk actions', 'xpressui-bridge' ) . '</option>';
		if ( xpressui_is_saas_connected() ) {
			echo '<option value="sync">' . esc_html__( 'Sync from Console', 'xpressui-bridge' ) . '</option>';
		}
		echo '<option value="archive">' . esc_html__( 'Archive', 'xpressui-bridge' ) . '</option>';
		echo '<option value="restore">' . esc_html__( 'Restore', 'xpressui-bridge' ) . '</option>';
		echo '</select>';
		echo '<input type="submit" id="' . esc_attr( $submit_id ) . '" class="button action" value="' . esc_attr__( 'Apply', 'xpressui-bridge' ) . '" name="xpressui_bulk_workflows_action" />';
		echo '</div>';

		echo '<div class="tablenav-pages">';
		echo '<span class="displaying-num">' . esc_html( sprintf(
			/* translators: %s: number of workflow items */
			_n( '%s item', '%s items', $total_items, 'xpressui-bridge' ),
			number_format_i18n( $total_items )
		) ) . '</span>';
		if ( $total_pages > 1 ) {
			$disable_first = $disable_prev = $disable_next = $disable_last = false;
			if ( $wf_paged === 1 ) {
				$disable_first = true;
				$disable_prev  = true;
			}
			if ( $wf_paged === $total_pages ) {
				$disable_next = true;
				$disable_last = true;
			}

			$first_page_url = add_query_arg( 'wf_paged', 1, $wf_base_url );
			$prev_page_url  = add_query_arg( 'wf_paged', max( 1, $wf_paged - 1 ), $wf_base_url );
			$next_page_url  = add_query_arg( 'wf_paged', min( $total_pages, $wf_paged + 1 ), $wf_base_url );
			$last_page_url  = add_query_arg( 'wf_paged', $total_pages, $wf_base_url );

			echo '<span class="pagination-links">';
			if ( $disable_first ) {
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span> ';
			} else {
				echo '<a class="first-page button" title="' . esc_attr__( 'Go to the first page', 'xpressui-bridge' ) . '" href="' . esc_url( $first_page_url ) . '">&laquo;</a> ';
			}

			if ( $disable_prev ) {
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span> ';
			} else {
				echo '<a class="prev-page button" title="' . esc_attr__( 'Go to the previous page', 'xpressui-bridge' ) . '" href="' . esc_url( $prev_page_url ) . '">&lsaquo;</a> ';
			}

			echo '<span class="paging-input">';
			echo '<span class="tablenav-paging-text">';
			echo '<label for="' . esc_attr( $select_id ) . '-current-page" class="screen-reader-text">' . esc_html__( 'Current Page', 'xpressui-bridge' ) . '</label>';
			echo '<input class="current-page" id="' . esc_attr( $select_id ) . '-current-page" type="text" name="wf_paged" value="' . esc_attr( (string) $wf_paged ) . '" size="' . esc_attr( (string) strlen( (string) $total_pages ) ) . '" aria-describedby="table-paging" /> ';
			echo esc_html__( 'of', 'xpressui-bridge' ) . ' <span class="total-pages">' . esc_html( (string) $total_pages ) . '</span>';
			echo '</span>';
			echo '</span> ';

			if ( $disable_next ) {
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span> ';
			} else {
				echo '<a class="next-page button" title="' . esc_attr__( 'Go to the next page', 'xpressui-bridge' ) . '" href="' . esc_url( $next_page_url ) . '">&rsaquo;</a> ';
			}

			if ( $disable_last ) {
				echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
			} else {
				echo '<a class="last-page button" title="' . esc_attr__( 'Go to the last page', 'xpressui-bridge' ) . '" href="' . esc_url( $last_page_url ) . '">&raquo;</a>';
			}
			echo '</span>';
		}
		echo '</div>';
		echo '<br class="clear">';
		echo '</div>';
	};

	// --- Subsubsub filters ----------------------------------------------------
	$all_link_args = [
		'post_type' => 'xpressui_submission',
		'page'      => 'xpressui-bridge',
		'tab'       => 'list',
		'wf_status' => 'all',
	];
	if ( $wf_search !== '' ) {
		$all_link_args['wf_s'] = $wf_search;
	}
	if ( $wf_orderby !== '' ) {
		$all_link_args['wf_orderby'] = $wf_orderby;
		$all_link_args['wf_order']   = $wf_order;
	}
	$all_link = add_query_arg( $all_link_args, admin_url( 'edit.php' ) );

	$active_link_args = $all_link_args;
	$active_link_args['wf_status'] = 'active';
	$active_link = add_query_arg( $active_link_args, admin_url( 'edit.php' ) );

	$archived_link_args = $all_link_args;
	$archived_link_args['wf_status'] = 'archived';
	$archived_link = add_query_arg( $archived_link_args, admin_url( 'edit.php' ) );

	echo '<ul class="subsubsub">';
	echo '<li class="all"><a href="' . esc_url( $all_link ) . '" class="' . ( 'all' === $status_filter ? 'current' : '' ) . '">' . esc_html__( 'All', 'xpressui-bridge' ) . ' <span class="count">(' . (int) $all_count . ')</span></a> |</li>';
	echo '<li class="active"><a href="' . esc_url( $active_link ) . '" class="' . ( 'active' === $status_filter ? 'current' : '' ) . '">' . esc_html__( 'Active', 'xpressui-bridge' ) . ' <span class="count">(' . (int) $active_count . ')</span></a> |</li>';
	echo '<li class="archived"><a href="' . esc_url( $archived_link ) . '" class="' . ( 'archived' === $status_filter ? 'current' : '' ) . '">' . esc_html__( 'Archived', 'xpressui-bridge' ) . ' <span class="count">(' . (int) $archived_count . ')</span></a></li>';
	echo '</ul>';

	// --- Search box (WP .search-box) -----------------------------------------
	echo '<form method="get" class="search-form" style="margin: 12px 0;">';
	echo '<input type="hidden" name="post_type" value="xpressui_submission" />';
	echo '<input type="hidden" name="page" value="xpressui-bridge" />';
	echo '<input type="hidden" name="tab" value="list" />';
	echo '<input type="hidden" name="wf_status" value="' . esc_attr( $status_filter ) . '" />';
	if ( $wf_orderby !== '' ) {
		echo '<input type="hidden" name="wf_orderby" value="' . esc_attr( $wf_orderby ) . '" />';
		echo '<input type="hidden" name="wf_order" value="' . esc_attr( $wf_order ) . '" />';
	}
	echo '<p class="search-box">';
	echo '<label class="screen-reader-text" for="xpressui-wf-search-input">' . esc_html__( 'Search Workflows', 'xpressui-bridge' ) . '</label>';
	echo '<input type="search" id="xpressui-wf-search-input" name="wf_s" value="' . esc_attr( $wf_search ) . '" placeholder="' . esc_attr__( 'Search by title or slug', 'xpressui-bridge' ) . '" />';
	echo '<input type="submit" class="button" value="' . esc_attr__( 'Search Workflows', 'xpressui-bridge' ) . '" />';
	if ( $wf_search !== '' ) {
		$clear_args = [ 'post_type' => 'xpressui_submission', 'page' => 'xpressui-bridge', 'tab' => 'list' ];
		if ( $wf_orderby !== '' ) {
			$clear_args['wf_orderby'] = $wf_orderby;
			$clear_args['wf_order']   = $wf_order;
		}
		echo ' <a href="' . esc_url( add_query_arg( $clear_args, admin_url( 'edit.php' ) ) ) . '" class="button-link" style="margin-left: 8px;">' . esc_html__( 'Clear', 'xpressui-bridge' ) . '</a>';
	}
	echo '</p>';
	echo '</form>';

	if ( $wf_search !== '' ) {
		echo '<p class="xpressui-search-summary" style="color:#646970;">' . esc_html( sprintf(
			/* translators: 1: number of matching workflows, 2: search term */
			_n( '%1$d workflow matches "%2$s".', '%1$d workflows match "%2$s".', $total_items, 'xpressui-bridge' ),
			$total_items,
			$wf_search
		) ) . '</p>';
	}

	// --- Installed workflows table ---
	if ( empty( $installed_slugs ) ) {
		echo '<div class="card xpressui-admin-card xpressui-empty-state"><p class="xpressui-empty-state__title">' . esc_html__( 'No workflows installed yet.', 'xpressui-bridge' ) . '</p><p class="xpressui-empty-state__body">' . esc_html__( 'Upload a workflow package below to get started, or use the bundled starter workflow already included with the plugin.', 'xpressui-bridge' ) . '</p></div>';
	} elseif ( empty( $visible_installed_slugs ) ) {
		echo '<div class="card xpressui-admin-card xpressui-empty-state"><p class="xpressui-empty-state__title">' . esc_html__( 'No workflows match your search.', 'xpressui-bridge' ) . '</p></div>';
	} else {
		echo '<form id="xpressui-workflows-form" method="post">';
		wp_nonce_field( 'xpressui_bulk_workflows_nonce_action', 'xpressui_bulk_workflows_nonce' );
		$render_wf_pagination( 'top' );
		$render_workflow_table( $visible_installed_slugs );
		$render_wf_pagination( 'bottom' );
		echo '</form>';
	}

	// Inline javascript to handle the global sync button
	if ( xpressui_is_saas_connected() ) {
		$nonce = wp_create_nonce( 'xpressui_console_sync_nonce' );
		$sync_script = sprintf(
			<<<'JS'
(function () {
	var syncBtn = document.getElementById('xpressui-global-sync-btn');
	if (!syncBtn) return;
	
	var nonce = %1$s;
	var ajaxUrl = %2$s;
	var labels = %3$s;
	
	function escapeHtml(value) {
		return String(value || '').replace(/[&<>"']/g, function (char) {
			return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
		});
	}

	syncBtn.addEventListener('click', function () {
		syncBtn.disabled = true;
		
		var container = document.getElementById('xpressui-global-sync-container');
		if (!container) {
			container = document.createElement('div');
			container.id = 'xpressui-global-sync-container';
			container.style.margin = '15px 0';
			container.style.padding = '12px 16px';
			container.style.background = '#f0f9ff';
			container.style.border = '1px solid #bae6fd';
			container.style.color = '#0369a1';
			container.style.borderRadius = '6px';
			container.style.fontWeight = '500';
			var wrap = document.querySelector('.xpressui-wrap');
			wrap.insertBefore(container, wrap.querySelector('.card'));
		}
		
		container.style.background = '#f0f9ff';
		container.style.border = '1px solid #bae6fd';
		container.style.color = '#0369a1';
		container.innerHTML = '<div>' + escapeHtml(labels.loading) + '</div>';
		
		var data = new URLSearchParams();
		data.set('action', 'xpressui_console_list_projects');
		data.set('nonce', nonce);
		
		fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res.success) {
					container.style.background = '#fff5f5';
					container.style.border = '1px solid #fcc';
					container.style.color = '#c00';
					container.innerHTML = '<div>' + escapeHtml(res.data.message) + '</div>';
					syncBtn.disabled = false;
					return;
				}
				
				var projects = res.data.projects;
				if (!projects.length) {
					container.style.background = '#fffaf0';
					container.style.border = '1px solid #f6cc87';
					container.style.color = '#7b341e';
					container.innerHTML = '<div>' + escapeHtml(labels.noProjects) + '</div>';
					syncBtn.disabled = false;
					return;
				}
				
				var index = 0;
				var total = projects.length;
				
				function syncNext() {
					var percent = Math.round((index / total) * 100);
					if (index >= total) {
						container.style.background = '#f0fdf4';
						container.style.border = '1px solid #bbf7d0';
						container.style.color = '#15803d';
						container.innerHTML = '<strong>' + escapeHtml(labels.allSynced) + '</strong> ' + escapeHtml(labels.reloading)
							+ '<div style="width: 100%%; background: #dcfce7; border-radius: 9999px; height: 8px; margin-top: 10px; overflow: hidden;">'
							+ '  <div style="width: 100%%; background: #16a34a; height: 100%%; transition: width 0.3s ease-in-out;"></div>'
							+ '</div>';
						setTimeout(function () {
							window.location.reload();
						}, 1500);
						return;
					}
					
					var p = projects[index];
					container.innerHTML = '<div>' + escapeHtml(labels.syncingAll) + ' <strong>' + (index + 1) + '/' + total + '</strong> (' + escapeHtml(p.name) + ')...</div>'
						+ '<div style="width: 100%%; background: #e0f2fe; border-radius: 9999px; height: 8px; margin-top: 10px; overflow: hidden;">'
						+ '  <div style="width: ' + percent + '%%; background: #0284c7; height: 100%%; transition: width 0.3s ease-in-out;"></div>'
						+ '</div>';
						
					var syncData = new URLSearchParams();
					syncData.set('action', 'xpressui_console_sync_project');
					syncData.set('nonce', nonce);
					syncData.set('project_id', p.id);
					
					fetch(ajaxUrl, { method: 'POST', body: syncData, credentials: 'same-origin' })
						.then(function (r) { return r.json(); })
						.then(function (res) {
							index++;
							syncNext();
						})
						.catch(function () {
							index++;
							syncNext();
						});
				}
				
				syncNext();
			})
			.catch(function () {
				container.style.background = '#fff5f5';
				container.style.border = '1px solid #fcc';
				container.style.color = '#c00';
				container.innerHTML = '<div>' + escapeHtml(labels.networkConnectionError) + '</div>';
				syncBtn.disabled = false;
			});
	});
})();
JS,
			wp_json_encode( $nonce ),
			wp_json_encode( admin_url( 'admin-ajax.php' ) ),
			wp_json_encode(
				[
					'loading'                => __( 'Connecting to Console...', 'xpressui-bridge' ),
					'noProjects'             => __( 'No workflows found in your Console.', 'xpressui-bridge' ),
					'syncingAll'             => __( 'Syncing workflows:', 'xpressui-bridge' ),
					'allSynced'              => __( 'All workflows synchronized!', 'xpressui-bridge' ),
					'reloading'              => __( 'Reloading page...', 'xpressui-bridge' ),
					'networkConnectionError' => __( 'Network error. Check your connection.', 'xpressui-bridge' ),
				]
			)
		);
		wp_print_inline_script_tag( $sync_script );
	}

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
		$link_id = isset( $_GET['xpressui_link_id'] ) ? sanitize_file_name( wp_unslash( (string) $_GET['xpressui_link_id'] ) ) : '';
		if ( $link_id !== '' ) {
			check_admin_referer( 'xpressui_create_workflow_page_' . $slug . '_' . $link_id );
			$result = xpressui_create_workflow_page( $slug, $link_id );
		} else {
			check_admin_referer( 'xpressui_create_workflow_page_' . $slug );
			$result = xpressui_create_workflow_page( $slug );
		}
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

function xpressui_create_workflow_page( $slug, $link_id = '' ) {
	$slug = sanitize_title( (string) $slug );
	if ( $slug === '' || ! xpressui_is_installed_workflow( $slug ) ) {
		return new WP_Error( 'missing_workflow', __( 'The workflow could not be found.', 'xpressui-bridge' ) );
	}

	$manifest_meta = xpressui_get_workflow_manifest_meta( $slug );
	$page_title    = sanitize_text_field( (string) ( $manifest_meta['projectName'] ?? '' ) );
	if ( $page_title === '' ) {
		$page_title = ucwords( str_replace( '-', ' ', $slug ) );
	}

	if ( $link_id !== '' ) {
		$link_config = xpressui_get_hosted_link_config( $slug, $link_id );
		if ( $link_config && ! empty( $link_config['label'] ) ) {
			$page_title = sanitize_text_field( $link_config['label'] );
		} else {
			$page_title = sanitize_text_field( $link_id );
		}
		$existing_pages = xpressui_get_workflow_page_ids( $slug, [ 'draft', 'publish', 'pending', 'private' ], $link_id );
		$shortcode = '[xpressui id="' . $slug . '" link_id="' . esc_attr( $link_id ) . '"]';
	} else {
		$existing_pages = xpressui_get_workflow_page_ids( $slug );
		$shortcode = '[xpressui id="' . $slug . '"]';
	}

	if ( ! empty( $existing_pages ) ) {
		return new WP_Error( 'page_exists', __( 'A page using this workflow shortcode already exists.', 'xpressui-bridge' ) );
	}

	$page_id = wp_insert_post( [
		'post_type'    => 'page',
		'post_status'  => 'draft',
		'post_title'   => $page_title,
		'post_content' => $shortcode,
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

function xpressui_register_settings_page() {
	add_submenu_page(
		'edit.php?post_type=xpressui_submission',
		__( 'IntakeFlow Settings', 'xpressui-bridge' ),
		__( 'Settings', 'xpressui-bridge' ),
		'manage_options',
		'xpressui-settings',
		'xpressui_render_settings_page'
	);

	add_submenu_page(
		'edit.php?post_type=xpressui_submission',
		__( 'Sync Logs', 'xpressui-bridge' ),
		__( 'Sync Logs', 'xpressui-bridge' ),
		'manage_options',
		'xpressui-sync-logs',
		'xpressui_render_sync_logs_page'
	);
}

/**
 * Dedicated Sync Logs admin page (moved out of the Settings tab).
 *
 * Wraps the existing sync-logs render in the standard admin page chrome.
 */
function xpressui_render_sync_logs_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'xpressui-bridge' ) );
	}

	echo '<div class="wrap xpressui-wrap">';
	echo '<h1>' . esc_html__( 'Sync Logs', 'xpressui-bridge' ) . '</h1>';
	echo '<p class="xpressui-page-intro">' . esc_html__( 'Track how form submissions are backed up to the IntakeFlow Console, where any configured webhooks are then delivered. Retry failed syncs from here.', 'xpressui-bridge' ) . '</p>';

	if ( function_exists( 'xpressui_render_sync_logs_tab' ) ) {
		xpressui_render_sync_logs_tab();
	}

	echo '</div>'; // .wrap
}

function xpressui_render_settings_page() {
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

	if ( $notice_message ) {
		echo '<div class="notice ' . esc_attr( $notice_class ) . ' is-dismissible"><p>' . wp_kses_post( $notice_message ) . '</p></div>';
	}

	echo '<div class="wrap xpressui-wrap">';
	echo '<h1>' . esc_html__( 'Settings', 'xpressui-bridge' ) . '</h1>';
	echo '<p class="xpressui-page-intro">' . esc_html__( 'Configure your IntakeFlow Console settings and monitor runtime status.', 'xpressui-bridge' ) . '</p>';

	// 1. Runtime Health Card
	$runtime_health = xpressui_get_runtime_health_summary();
	$api_health     = xpressui_check_console_api_health();
	echo '<div class="card xpressui-admin-card">';
	echo '<h2>' . esc_html__( 'Runtime Health', 'xpressui-bridge' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'Shows which runtime the plugin shell will try to load, whether the bundled files are present, and the console connection status.', 'xpressui-bridge' ) . '</p>';
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
	echo '<tr>';
	echo '<td><strong>' . esc_html__( 'Console API health', 'xpressui-bridge' ) . '</strong></td>';
	$badge_class = 'xpressui-badge--status-rejected'; // default red / error
	if ( $api_health['status'] === 'connected' ) {
		$badge_class = 'xpressui-badge--status-done'; // green
	} elseif ( $api_health['status'] === 'reachable' ) {
		$badge_class = 'xpressui-badge--status-new'; // blue
	} elseif ( $api_health['status'] === 'not_configured' ) {
		$badge_class = 'xpressui-badge--status-in-review'; // yellow
	}
	echo '<td><span class="xpressui-badge ' . esc_attr( $badge_class ) . '">' . esc_html( ucfirst( str_replace( '_', ' ', $api_health['status'] ) ) ) . '</span></td>';
	echo '<td><code>' . esc_html( $api_health['message'] ) . '</code></td>';
	echo '</tr>';
	echo '</tbody></table>';
	echo '</div>';

	// 2. Console Sync / Connection Card
	echo '<div class="card xpressui-admin-card">';
	echo '<h2>' . esc_html__( 'Console Connection', 'xpressui-bridge' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'Connect your site to the IntakeFlow Console to synchronize workflows.', 'xpressui-bridge' ) . '</p>';

	// Generate a random state token to secure connection redirects against CSRF.
	$connect_state = wp_generate_password( 24, false );
	set_transient( 'xpressui_connect_state', $connect_state, 3600 );

	$conn = xpressui_get_console_connection();
	$console_url = ! empty( $conn['apiUrl'] ) ? $conn['apiUrl'] : 'https://app.intakeflow.dev';
	$connect_url = trailingslashit( $console_url ) . 'wordpress-connect?' . http_build_query([
		'site_url'   => site_url(),
		'site_name'  => get_bloginfo( 'name' ),
		'state'      => $connect_state,
		'return_url' => admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-settings' ),
		// Acquisition attribution: lets the Console measure how many sign-ups / connections
		// originate from the WordPress plugin (P0 funnel). Extra params are ignored by the
		// connect page if unused.
		'utm_source'     => 'wp-plugin',
		'utm_medium'     => 'plugin',
		'plugin_version' => XPRESSUI_BRIDGE_VERSION,
	]);

	echo '<div style="margin: 20px 0; padding: 15px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; max-width: 600px;">';
	echo '<p style="margin-top: 0; font-weight: 600; color: #0369a1;">' . esc_html__( 'Recommended: 1-Click Connection', 'xpressui-bridge' ) . '</p>';
	echo '<p class="description" style="margin-bottom: 12px;">' . esc_html__( 'Log in or sign up to your IntakeFlow Console to link this site automatically without copying tokens.', 'xpressui-bridge' ) . '</p>';
	echo '<a href="' . esc_url( $connect_url ) . '" class="button button-primary button-large" style="display: inline-flex; align-items: center; justify-content: center; height: 38px; border-radius: 6px; font-weight: 600;">' . esc_html__( 'Connect IntakeFlow Account', 'xpressui-bridge' ) . '</a>';
	echo '</div>';

	echo '<details style="margin-top: 15px; cursor: pointer;"><summary style="font-weight: 600; color: #64748b; margin-bottom: 10px; outline: none;">' . esc_html__( 'Or configure connection manually', 'xpressui-bridge' ) . '</summary>';
	if ( function_exists( 'xpressui_render_console_connection_form' ) ) {
		xpressui_render_console_connection_form();
	}
	echo '</details>';
	echo '</div>';

	// Connection saving script
	$connection_form_script = sprintf(
		<<<'JS'
(function () {
	var form = document.getElementById('xpressui-console-connection-form');
	if (!form) { return; }
	form.addEventListener('submit', function (e) {
		e.preventDefault();
		var statusEl  = form.querySelector('.xpressui-ajax-status');
		var submitBtn = form.querySelector('[type="submit"]');
		if (submitBtn) { submitBtn.disabled = true; }
		if (statusEl) { statusEl.textContent = %1$s; statusEl.style.color = ''; }

		var data = new FormData(form);
		data.set('action', 'xpressui_save_console_connection');

		fetch(%2$s, { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (submitBtn) { submitBtn.disabled = false; }
				if (statusEl) {
					statusEl.textContent = res.success ? %3$s : (res.data.message || 'Error.');
					statusEl.style.color = res.success ? '#3a3' : '#c00';
				}
				if (res.success) {
					setTimeout(function () { window.location.reload(); }, 1000);
				}
			})
			.catch(function () {
				if (submitBtn) { submitBtn.disabled = false; }
				if (statusEl) { statusEl.textContent = %4$s; statusEl.style.color = '#c00'; }
			});
	});
}());
JS,
		wp_json_encode( __( 'Saving…', 'xpressui-bridge' ) ),
		wp_json_encode( admin_url( 'admin-ajax.php' ) ),
		wp_json_encode( __( 'Saved. Reloading…', 'xpressui-bridge' ) ),
		wp_json_encode( __( 'Network error.', 'xpressui-bridge' ) )
	);
	wp_print_inline_script_tag( $connection_form_script );

	// 4. Cloud Sync & Backup Card
	$enable_cloud_sync = get_option( 'xpressui_enable_cloud_sync', '1' ) === '1';

	echo '<div class="card xpressui-admin-card" style="margin-top: 20px;">';
	echo '<h2>' . esc_html__( 'Cloud Sync & Backup Settings', 'xpressui-bridge' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'Enable or disable automatic cloud backup & sync to the IntakeFlow Console.', 'xpressui-bridge' ) . '</p>';

	echo '<form id="xpressui-cloud-sync-settings-form" method="post" style="margin-top: 15px;">';
	wp_nonce_field( 'xpressui_cloud_sync_settings_action', 'xpressui_cloud_sync_settings_nonce' );
	echo '<input type="hidden" name="xpressui_save_cloud_sync_settings" value="1">';

	echo '<label style="display: block; margin-bottom: 12px; font-weight: 500;">';
	echo '<input type="checkbox" name="xpressui_enable_cloud_sync" value="1" ' . checked( $enable_cloud_sync, true, false ) . ' />';
	echo ' ' . esc_html__( 'Enable automatic cloud synchronization', 'xpressui-bridge' );
	echo '</label>';

	echo '<p style="color: #475569; background: #f8fafc; padding: 10px; border-radius: 6px; font-size: 12px; margin-top: 5px; max-width: 500px;">' 
		. esc_html__( '💡 Active Cloud Sync allows you to backup submissions, receive automated reminders (chaser), and access advanced tools like PDF summaries and OCR receipt extraction.', 'xpressui-bridge' ) 
		. '</p>';

	submit_button( __( 'Save Cloud Sync Settings', 'xpressui-bridge' ), 'secondary', 'submit_cloud_sync' );
	echo '</form>';
	echo '</div>';

	echo '</div>'; // .wrap
}

/**
 * Renders the local settings/customization page for a workflow.
 */
function xpressui_render_workflow_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'xpressui-bridge' ) );
	}

	$view_nonce = isset( $_GET['xpressui_view_nonce'] ) ? sanitize_key( wp_unslash( $_GET['xpressui_view_nonce'] ) ) : '';
	if ( ! $view_nonce || ! wp_verify_nonce( $view_nonce, 'xpressui_view_workflow' ) ) {
		wp_die( esc_html__( 'Invalid security token.', 'xpressui-bridge' ) );
	}

	$slug = isset( $_GET['xpressui_slug'] ) ? sanitize_title( wp_unslash( (string) $_GET['xpressui_slug'] ) ) : '';
	if ( empty( $slug ) || ! xpressui_is_installed_workflow( $slug ) ) {
		wp_die( esc_html__( 'Invalid workflow.', 'xpressui-bridge' ) );
	}


	$manifest_meta = xpressui_get_workflow_manifest_meta( $slug );
	$project_name  = sanitize_text_field( (string) ( $manifest_meta['projectName'] ?? '' ) );
	$display_name  = $project_name !== '' ? $project_name : $slug;

	$back_url = add_query_arg(
		[
			'post_type' => 'xpressui_submission',
			'page'      => 'xpressui-bridge',
		],
		admin_url( 'edit.php' )
	);

	$notice = xpressui_get_admin_notice();
	$success_message = '';
	if ( $notice ) {
		if ( $notice['type'] === 'success' ) {
			$success_message = $notice['message'];
		} else {
			echo '<div class="notice ' . ( $notice['type'] === 'error' ? 'notice-error' : 'notice-success' ) . ' is-dismissible"><p>' . wp_kses_post( $notice['message'] ) . '</p></div>';
		}
	}

	echo '<div class="wrap xpressui-wrap xpressui-admin-wrap xpressui-active-tab-appearance">';

	// Header
	echo '<div class="xpressui-pro-header">';
	echo '<div class="xpressui-pro-header-left">';
	echo '<h1>' . esc_html( $display_name ) . '</h1>';
	echo '<p>' . esc_html__( 'Customize your workflow style, buttons, sections, and input fields. All overrides are saved locally.', 'xpressui-bridge' ) . '</p>';
	echo '</div>';
	echo '<div class="xpressui-pro-header-right">';
	echo '<span class="xpressui-pro-badge">' . esc_html__( 'Local Customizer', 'xpressui-bridge' ) . '</span>';
	echo '<a href="' . esc_url( $back_url ) . '" class="xpressui-pro-back">&larr; ' . esc_html__( 'Back to Workflows', 'xpressui-bridge' ) . '</a>';
	echo '</div>';
	echo '</div>';

	// Tabs navigation
	echo '<h2 class="nav-tab-wrapper xpressui-customizer-tabs" style="margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 0;">';
	echo '<a href="#tab-appearance" class="nav-tab nav-tab-active" data-tab="appearance">🎨 ' . esc_html__( 'Style & Appearance', 'xpressui-bridge' ) . '</a>';
	echo '<a href="#tab-navigation" class="nav-tab" data-tab="navigation">🗺️ ' . esc_html__( 'Navigation Buttons', 'xpressui-bridge' ) . '</a>';
	echo '<a href="#tab-fields" class="nav-tab" data-tab="fields">📝 ' . esc_html__( 'Form Fields & Overrides', 'xpressui-bridge' ) . '</a>';
	echo '<a href="#tab-settings" class="nav-tab" data-tab="settings">⚙️ ' . esc_html__( 'Settings', 'xpressui-bridge' ) . '</a>';
	echo '</h2>';

	// Fetch template context and overlays
	$template_context = xpressui_load_workflow_template_context( $slug );
	$sections         = isset( $template_context['rendered_form']['sections'] ) && is_array( $template_context['rendered_form']['sections'] )
		? $template_context['rendered_form']['sections']
		: [];
	$visible_fields   = [];
	foreach ( $sections as $sect ) {
		foreach ( (array) ( $sect['fields'] ?? [] ) as $fld ) {
			$fn = (string) ( $fld['name'] ?? '' );
			if ( $fn !== '' ) {
				$visible_fields[] = $fld;
			}
		}
	}
	$overlay = xpressui_pro_load_workflow_overlay( $slug );

	$section_count = count( $sections );
	$field_count = count( $visible_fields );
	$customized_fields = isset( $overlay['fields'] ) ? count( $overlay['fields'] ) : 0;

	// Summary chips
	echo '<div class="xpressui-pro-summary">';
	echo '<div class="xpressui-pro-summary-chip">';
	echo '<strong>' . (int) $section_count . '</strong>';
	echo '<span>' . esc_html__( 'Sections', 'xpressui-bridge' ) . '</span>';
	echo '</div>';
	echo '<div class="xpressui-pro-summary-chip">';
	echo '<strong>' . (int) $field_count . '</strong>';
	echo '<span>' . esc_html__( 'Fields', 'xpressui-bridge' ) . '</span>';
	echo '</div>';
	if ( $customized_fields > 0 ) {
		echo '<div class="xpressui-pro-summary-chip">';
		echo '<strong>' . (int) $customized_fields . '</strong>';
		echo '<span>' . esc_html__( 'Overrides', 'xpressui-bridge' ) . '</span>';
		echo '</div>';
	}
	echo '</div>';

	// Toolbar controls
	echo '<div class="xpressui-pro-toolbar">';
	echo '<button type="button" class="xpressui-pro-details-toggle" data-target="all">' . esc_html__( 'Expand all', 'xpressui-bridge' ) . '</button>';
	echo '<button type="button" class="xpressui-pro-details-toggle" data-target="none">' . esc_html__( 'Collapse all', 'xpressui-bridge' ) . '</button>';
	echo '<button type="button" class="xpressui-pro-filter-toggle">' . esc_html__( 'Customized Only', 'xpressui-bridge' ) . '</button>';
	echo '<div class="xpressui-pro-toolbar-search">';
	echo '<input type="search" placeholder="' . esc_attr__( 'Search fields...', 'xpressui-bridge' ) . '" data-xpressui-card-search />';
	echo '<div class="xpressui-pro-toolbar-meta">';
	echo '<span data-xpressui-visible-count>' . (int) ( $section_count + 2 ) . '</span> ' . esc_html__( 'blocks', 'xpressui-bridge' );
	echo '</div>';
	echo '</div>';
	echo '</div>';

	// Empty State card
	echo '<div class="xpressui-pro-empty-state" data-xpressui-empty-state style="display: none;">';
	echo esc_html__( 'No cards match your search criteria.', 'xpressui-bridge' );
	echo '</div>';

	// Form
	echo '<form method="post" action="">';
	wp_nonce_field( 'xpressui_workflow_settings_' . $slug, 'xpressui_workflow_settings_nonce' );

	// Sticky save bar
	$status_class = 'xpressui-sticky-status';
	$status_text  = __( 'No unsaved changes', 'xpressui-bridge' );
	$extra_attrs  = '';
	if ( ! empty( $success_message ) ) {
		$status_class .= ' is-saved';
		$status_text   = $success_message;
		$extra_attrs   = ' data-saved-message="' . esc_attr( $success_message ) . '"';
	}

	echo '<div class="xpressui-sticky-actions">';
	echo wp_kses( '<span class="' . esc_attr( $status_class ) . '" data-xpressui-dirty-status' . $extra_attrs . '>' . esc_html( $status_text ) . '</span>', xpressui_get_shell_allowed_html() );
	echo '<div class="xpressui-sticky-actions-buttons">';
	submit_button( __( 'Save Customizations', 'xpressui-bridge' ), 'primary', 'xpressui_save_workflow_settings', false );
	echo '</div>';
	echo '</div>';

	// Trigger the render action
	do_action( 'xpressui_workflow_settings_extra_sections', $slug, $visible_fields, $overlay );


	echo '</form>';
	echo '</div>'; // .wrap
}

function xpressui_render_workflow_detail_page( $slug ) {
	$manifest_meta = xpressui_get_workflow_manifest_meta( $slug );
	$project_name  = sanitize_text_field( (string) ( $manifest_meta['projectName'] ?? '' ) );
	$display_name  = $project_name !== '' ? $project_name : $slug;
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

	$back_url = add_query_arg(
		[
			'post_type' => 'xpressui_submission',
			'page'      => 'xpressui-bridge',
		],
		admin_url( 'edit.php' )
	);

	$workflow_version = sanitize_text_field( (string) ( $manifest_meta['runtimeVersion'] ?? '' ) );

	echo '<div class="wrap xpressui-wrap">';
	echo '<h1 class="wp-heading-inline">';
	echo '<a href="' . esc_url( $back_url ) . '" class="page-title-action" style="margin-right: 12px; display: inline-flex; align-items: center; gap: 4px; vertical-align: middle;">';
	echo '<span class="dashicons dashicons-arrow-left-alt2" style="font-size: 16px; width: 16px; height: 16px; display: inline-block; line-height: 1; margin-top: 1px;"></span>';
	echo esc_html__( 'Back to Workflows', 'xpressui-bridge' );
	echo '</a>';
	echo esc_html( $display_name );
	echo '</h1>';
	echo '<hr class="wp-header-end">';

	// Details Card
	echo '<div class="card" style="max-width: 600px; margin: 20px 0 30px 0; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">';
	echo '<h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">' . esc_html__( 'Workflow Details', 'xpressui-bridge' ) . '</h2>';
	echo '<table class="form-table" style="margin: 0; width: 100%;">';
	echo '<tr><th style="padding: 10px 0; font-weight: 600; width: 150px;">' . esc_html__( 'Slug / ID', 'xpressui-bridge' ) . '</th><td style="padding: 10px 0;"><code>' . esc_html( $slug ) . '</code></td></tr>';
	if ( $workflow_version !== '' ) {
		echo '<tr><th style="padding: 10px 0; font-weight: 600;">' . esc_html__( 'Version', 'xpressui-bridge' ) . '</th><td style="padding: 10px 0;"><code>' . esc_html( $workflow_version ) . '</code></td></tr>';
	}
	echo '</table>';


	echo '</div>';

	if ( xpressui_is_saas_connected() ) {
		echo '<div style="margin: 20px 0; display: flex; align-items: center; gap: 10px;">';
		echo '<button type="button" id="xpressui-single-sync-btn" class="button button-primary">' . esc_html__( 'Sync from Console', 'xpressui-bridge' ) . '</button>';
		echo '</div>';
		echo '<div id="xpressui-single-sync-container" style="display: none; max-width: 600px; margin: 15px 0; padding: 12px 16px; border-radius: 6px; font-weight: 500;"></div>';
	}

	// ---------------------------------------------------------
	// Linked Pages Table
	// ---------------------------------------------------------
	$hosted_links = [];
	$base_dir = xpressui_get_workflows_base_dir();
	if ( $base_dir !== '' ) {
		$hosted_links_dir = trailingslashit( $base_dir ) . $slug . '/hosted-links';
		if ( is_dir( $hosted_links_dir ) ) {
			$dirs = array_filter( glob( trailingslashit( $hosted_links_dir ) . '*' ), 'is_dir' );
			foreach ( $dirs as $dir_path ) {
				$link_id = basename( $dir_path );
				$config_file = trailingslashit( $dir_path ) . 'link.config.json';
				if ( file_exists( $config_file ) ) {
					$content = file_get_contents( $config_file );
					if ( $content ) {
						$config_data = json_decode( $content, true );
						if ( is_array( $config_data ) ) {
							$hosted_links[] = [
								'id'        => $link_id,
								'label'     => $config_data['label'] ?? $config_data['id'] ?? $link_id,
								'expiresAt' => $config_data['expiresAt'] ?? '',
							];
						}
					}
				}
			}
		}
	}

	echo '<h2 style="margin-top: 40px; border-bottom: 2px solid #2271b1; padding-bottom: 8px;">' . esc_html__( 'Linked Pages', 'xpressui-bridge' ) . '</h2>';
	echo '<p class="description" style="margin-bottom: 20px;">' . esc_html__( 'Manage WordPress pages connected to this workflow. A linked page can run either the legacy fallback workflow or a specific SaaS Hosted Link.', 'xpressui-bridge' ) . '</p>';

	echo '<table class="wp-list-table widefat fixed striped xpressui-table" style="box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-radius: 6px; overflow: hidden; margin-top: 15px;">';
	echo '<thead><tr>';
	echo '<th style="font-weight: 700; width: 350px;">' . esc_html__( 'Workflow / Hosted Link', 'xpressui-bridge' ) . '</th>';
	echo '<th style="font-weight: 700;">' . esc_html__( 'Shortcode', 'xpressui-bridge' ) . '</th>';
	echo '<th style="font-weight: 700; width: 180px;">' . esc_html__( 'Expiration Date', 'xpressui-bridge' ) . '</th>';
	echo '<th style="font-weight: 700; width: 350px;">' . esc_html__( 'WordPress Page', 'xpressui-bridge' ) . '</th>';
	echo '</tr></thead>';
	echo '<tbody>';

	// Row 1: Legacy Fallback
	$legacy_pages = xpressui_get_workflow_page_ids( $slug );
	$pure_legacy_pages = [];
	foreach ( $legacy_pages as $p_id ) {
		$p_post = get_post( $p_id );
		$p_content = $p_post ? $p_post->post_content : '';
		if ( false === strpos( $p_content, 'link_id=' ) && false === strpos( $p_content, 'link=' ) ) {
			$pure_legacy_pages[] = $p_id;
		}
	}

	$create_legacy_page_url = wp_nonce_url(
		add_query_arg(
			[
				'post_type'       => 'xpressui_submission',
				'page'            => 'xpressui-bridge',
				'xpressui_action' => 'create_workflow_page',
				'xpressui_slug'   => $slug,
			],
			admin_url( 'edit.php' )
		),
		'xpressui_create_workflow_page_' . $slug
	);

	echo '<tr>';
	echo '<td style="font-weight: 600; font-size: 13px; vertical-align: middle;">' . esc_html( $display_name ) . '</td>';
	echo '<td style="vertical-align: middle;"><code style="background: #f0f0f1; padding: 4px 8px; border-radius: 4px; font-size: 13px;">[xpressui id="' . esc_attr( $slug ) . '"]</code></td>';
	echo '<td style="vertical-align: middle; font-size: 13px; color: #888;">&mdash;</td>';
	echo '<td style="vertical-align: middle;">';
	if ( ! empty( $pure_legacy_pages ) ) {
		$page_links = [];
		foreach ( $pure_legacy_pages as $p_id ) {
			$p_title = get_the_title( $p_id ) ?: '#' . $p_id;
			$page_links[] = '<a href="' . esc_url( get_permalink( $p_id ) ) . '" target="_blank" rel="noreferrer"><strong>' . esc_html( $p_title ) . '</strong></a>';
		}
		echo wp_kses_post( implode( ', ', $page_links ) );
	} else {
		echo '<a href="' . esc_url( $create_legacy_page_url ) . '" class="button button-small button-secondary">' . esc_html__( 'Create page', 'xpressui-bridge' ) . '</a>';
	}
	echo '</td>';
	echo '</tr>';

	// Synced hosted link rows
	foreach ( $hosted_links as $link ) {
		$shortcode = '[xpressui id="' . esc_attr( $slug ) . '" link_id="' . esc_attr( $link['id'] ) . '"]';
		$link_pages = xpressui_get_workflow_page_ids( $slug, [ 'draft', 'publish', 'pending', 'private' ], $link['id'] );

		$create_link_page_url = wp_nonce_url(
			add_query_arg(
				[
					'post_type'        => 'xpressui_submission',
					'page'             => 'xpressui-bridge',
					'xpressui_action'  => 'create_workflow_page',
					'xpressui_slug'    => $slug,
					'xpressui_link_id' => $link['id'],
				],
				admin_url( 'edit.php' )
			),
			'xpressui_create_workflow_page_' . $slug . '_' . $link['id']
		);

		$edit_link_html = '';
		if ( xpressui_is_saas_connected() ) {
			$conn = xpressui_get_console_connection();
			$link_edit_url = xpressui_console_hosted_link_url( (string) ( $link['id'] ?? '' ) );
			$edit_link_html = ' <a href="' . esc_url( $link_edit_url ) . '" target="_blank" rel="noopener" style="text-decoration:none;" title="' . esc_attr__( 'Edit on IntakeFlow', 'xpressui-bridge' ) . '"><span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle; margin-left: 4px; color: #2271b1;"></span></a>';
		}

		echo '<tr>';
		echo '<td style="font-weight: 600; font-size: 13px; vertical-align: middle;">' . esc_html( $link['label'] ) . wp_kses_post( $edit_link_html ) . '</td>';
		echo '<td style="vertical-align: middle;"><code style="background: #f0f0f1; padding: 4px 8px; border-radius: 4px; font-size: 13px;">' . esc_html( $shortcode ) . '</code></td>';
		
		// Expiration Date column
		$expires_at = ! empty( $link['expiresAt'] ) ? (string) $link['expiresAt'] : '';
		$expires_html = '&mdash;';
		if ( $expires_at !== '' ) {
			$timestamp = strtotime( $expires_at );
			if ( $timestamp > 0 ) {
				$expires_html = esc_html( wp_date( get_option( 'date_format' ), $timestamp ) );
			}
		}
		echo '<td style="vertical-align: middle; font-size: 13px;">' . wp_kses_post( $expires_html ) . '</td>';

		echo '<td style="vertical-align: middle;">';
		if ( ! empty( $link_pages ) ) {
			$page_links = [];
			foreach ( $link_pages as $p_id ) {
				$p_title = get_the_title( $p_id ) ?: '#' . $p_id;
				$page_links[] = '<a href="' . esc_url( get_permalink( $p_id ) ) . '" target="_blank" rel="noreferrer"><strong>' . esc_html( $p_title ) . '</strong></a>';
			}
			echo wp_kses_post( implode( ', ', $page_links ) );
		} else {
			echo '<a href="' . esc_url( $create_link_page_url ) . '" class="button button-small button-primary">' . esc_html__( 'Create page', 'xpressui-bridge' ) . '</a>';
		}
		echo '</td>';
		echo '</tr>';
	}

	echo '</tbody>';
	echo '</table>';

	echo '</div>'; // .wrap

	if ( xpressui_is_saas_connected() ) {
		$single_sync_script = sprintf(
			<<<'JS'
(function () {
	var syncBtn = document.getElementById('xpressui-single-sync-btn');
	if (!syncBtn) return;
	
	syncBtn.addEventListener('click', function () {
		syncBtn.disabled = true;
		var container = document.getElementById('xpressui-single-sync-container');
		if (!container) {
			container = document.createElement('div');
			container.id = 'xpressui-single-sync-container';
			container.style.margin = '15px 0';
			container.style.padding = '12px 16px';
			container.style.background = '#f0f9ff';
			container.style.border = '1px solid #bae6fd';
			container.style.color = '#0369a1';
			container.style.borderRadius = '6px';
			container.style.fontWeight = '500';
			var wrap = document.querySelector('.xpressui-wrap');
			wrap.insertBefore(container, wrap.querySelector('.xpressui-detail-grid'));
		}
		container.style.display = 'block';
		container.style.background = '#f0f9ff';
		container.style.border = '1px solid #bae6fd';
		container.style.color = '#0369a1';
		container.innerHTML = '<div>Syncing workflow...</div>';

		var data = new URLSearchParams();
		data.set('action', 'xpressui_console_sync_project');
		data.set('nonce', %1$s);
		data.set('project_id', %2$s);

		fetch(%3$s, { method: 'POST', body: data, credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (res.success) {
					container.style.background = '#f0fdf4';
					container.style.border = '1px solid #bbf7d0';
					container.style.color = '#15803d';
					container.innerHTML = '<strong>Synchronized successfully!</strong> Reloading...';
					setTimeout(function () { window.location.reload(); }, 1000);
				} else {
					container.style.background = '#fef2f2';
					container.style.border = '1px solid #fca5a5';
					container.style.color = '#991b1b';
					container.innerHTML = '<strong>Error:</strong> ' + String(res.data.message || 'Sync failed.');
					syncBtn.disabled = false;
				}
			})
			.catch(function () {
				container.style.background = '#fef2f2';
				container.style.border = '1px solid #fca5a5';
				container.style.color = '#991b1b';
				container.innerHTML = '<strong>Error:</strong> Network error.';
				syncBtn.disabled = false;
			});
	});
}());
JS,
			wp_json_encode( wp_create_nonce( 'xpressui_console_sync_nonce' ) ),
			wp_json_encode( $slug ),
			wp_json_encode( admin_url( 'admin-ajax.php' ) )
		);
		wp_print_inline_script_tag( $single_sync_script );
	}
}

/**
 * Saves the Cloud Sync Settings.
 */
function xpressui_handle_save_cloud_sync_settings() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! isset( $_POST['xpressui_save_cloud_sync_settings'] ) ) {
		return;
	}
	check_admin_referer( 'xpressui_cloud_sync_settings_action', 'xpressui_cloud_sync_settings_nonce' );

	$enable = isset( $_POST['xpressui_enable_cloud_sync'] ) ? '1' : '0';
	update_option( 'xpressui_enable_cloud_sync', $enable );

	xpressui_set_admin_notice( __( 'Cloud Sync settings saved.', 'xpressui-bridge' ), 'success' );
	wp_safe_redirect( admin_url( 'edit.php?post_type=xpressui_submission&page=xpressui-settings' ) );
	exit;
}
add_action( 'admin_init', 'xpressui_handle_save_cloud_sync_settings' );

/**
 * Handles saving the workflow settings.
 */
function xpressui_handle_save_workflow_settings() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( ! isset( $_POST['xpressui_save_workflow_settings'] ) ) {
		return;
	}

	$slug = isset( $_GET['xpressui_slug'] ) ? sanitize_title( wp_unslash( (string) $_GET['xpressui_slug'] ) ) : '';
	if ( empty( $slug ) || ! xpressui_is_installed_workflow( $slug ) ) {
		wp_die( esc_html__( 'Invalid workflow.', 'xpressui-bridge' ) );
	}

	check_admin_referer( 'xpressui_workflow_settings_' . $slug, 'xpressui_workflow_settings_nonce' );

	// Fire the hook in overlay-admin.php to save the overlay settings
	do_action( 'xpressui_workflow_settings_extra_save', $slug );

	if ( ! get_transient( 'xpressui_notice_' . get_current_user_id() ) ) {
		xpressui_set_admin_notice( __( 'Workflow settings saved.', 'xpressui-bridge' ), 'success' );
	}

	$redirect_url = add_query_arg(
		[
			'post_type'           => 'xpressui_submission',
			'page'                => 'xpressui-workflow-settings',
			'xpressui_slug'       => $slug,
			'xpressui_view_nonce' => wp_create_nonce( 'xpressui_view_workflow' ),
		],
		admin_url( 'edit.php' )
	);

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_init', 'xpressui_handle_save_workflow_settings' );

/**
 * Handles workflow bulk and individual actions (Archive, Restore, Sync).
 */
function xpressui_handle_workflow_bulk_actions() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// 1) Individual actions via GET
	$action = isset( $_GET['xpressui_action'] ) ? sanitize_key( wp_unslash( $_GET['xpressui_action'] ) ) : '';
	$slug   = isset( $_GET['xpressui_slug'] ) ? sanitize_title( wp_unslash( (string) $_GET['xpressui_slug'] ) ) : '';

	if ( $action !== '' && $slug !== '' ) {
		if ( 'archive_workflow_item' === $action ) {
			check_admin_referer( 'xpressui_archive_workflow_' . $slug );
			$archived = get_option( 'xpressui_archived_workflows', [] );
			if ( ! is_array( $archived ) ) {
				$archived = [];
			}
			if ( ! in_array( $slug, $archived, true ) ) {
				$archived[] = $slug;
				update_option( 'xpressui_archived_workflows', $archived );
			}
			/* translators: %s: workflow slug */
			xpressui_set_admin_notice( sprintf( __( 'Workflow "%s" has been archived.', 'xpressui-bridge' ), $slug ), 'success' );
			wp_safe_redirect( remove_query_arg( [ 'xpressui_action', 'xpressui_slug', '_wpnonce' ] ) );
			exit;
		}

		if ( 'restore_workflow_item' === $action ) {
			check_admin_referer( 'xpressui_restore_workflow_' . $slug );
			$archived = get_option( 'xpressui_archived_workflows', [] );
			if ( is_array( $archived ) ) {
				$key = array_search( $slug, $archived, true );
				if ( $key !== false ) {
					unset( $archived[ $key ] );
					update_option( 'xpressui_archived_workflows', array_values( $archived ) );
				}
			}
			/* translators: %s: workflow slug */
			xpressui_set_admin_notice( sprintf( __( 'Workflow "%s" has been restored.', 'xpressui-bridge' ), $slug ), 'success' );
			wp_safe_redirect( remove_query_arg( [ 'xpressui_action', 'xpressui_slug', '_wpnonce' ] ) );
			exit;
		}
		if ( 'sync_workflow_item' === $action ) {
			check_admin_referer( 'xpressui_sync_workflow_' . $slug );
			if ( ! xpressui_is_saas_connected() ) {
				xpressui_set_admin_notice( __( 'Synchronization failed: Active Console Connection required.', 'xpressui-bridge' ), 'error' );
			} else {
				$project_id = xpressui_get_workflow_console_project_id( $slug );
				if ( $project_id === '' ) {
					/* translators: %s: workflow slug */
					xpressui_set_admin_notice( sprintf( __( 'Workflow "%s" is local-only and cannot be synced.', 'xpressui-bridge' ), $slug ), 'error' );
				} else {
					$sync_res = xpressui_sync_project( $project_id );
					if ( is_wp_error( $sync_res ) ) {
						/* translators: 1: workflow slug, 2: error message details */
						xpressui_set_admin_notice( sprintf( __( 'Failed to sync "%1$s": %2$s', 'xpressui-bridge' ), $slug, $sync_res->get_error_message() ), 'error' );
					} else {
						/* translators: %s: workflow slug */
						xpressui_set_admin_notice( sprintf( __( 'Workflow "%s" has been synced from the Console.', 'xpressui-bridge' ), $slug ), 'success' );
					}
				}
			}
			wp_safe_redirect( remove_query_arg( [ 'xpressui_action', 'xpressui_slug', '_wpnonce' ] ) );
			exit;
		}
	}

	// 2) Bulk actions via POST
	if ( ! isset( $_POST['xpressui_bulk_workflows_action'] ) ) {
		return;
	}

	check_admin_referer( 'xpressui_bulk_workflows_nonce_action', 'xpressui_bulk_workflows_nonce' );

	$bulk_action = sanitize_key( $_POST['xpressui_bulk_action_select'] ?? '' );
	if ( $bulk_action === '-1' || $bulk_action === '' ) {
		$bulk_action = sanitize_key( $_POST['xpressui_bulk_action_select2'] ?? '' );
	}
	$selected    = isset( $_POST['xpressui_workflow_checkboxes'] ) && is_array( $_POST['xpressui_workflow_checkboxes'] )
		? array_map( 'sanitize_title', wp_unslash( $_POST['xpressui_workflow_checkboxes'] ) )
		: [];

	if ( empty( $selected ) || ! in_array( $bulk_action, [ 'sync', 'archive', 'restore' ], true ) ) {
		return;
	}

	if ( 'archive' === $bulk_action ) {
		$archived = get_option( 'xpressui_archived_workflows', [] );
		if ( ! is_array( $archived ) ) {
			$archived = [];
		}
		$count = 0;
		foreach ( $selected as $s_slug ) {
			if ( ! in_array( $s_slug, $archived, true ) ) {
				$archived[] = $s_slug;
				$count++;
			}
		}
		update_option( 'xpressui_archived_workflows', $archived );
		/* translators: %d: number of archived workflows */
		xpressui_set_admin_notice( sprintf( _n( '%d workflow has been archived.', '%d workflows have been archived.', $count, 'xpressui-bridge' ), $count ), 'success' );
	} elseif ( 'restore' === $bulk_action ) {
		$archived = get_option( 'xpressui_archived_workflows', [] );
		if ( ! is_array( $archived ) ) {
			$archived = [];
		}
		$count = 0;
		foreach ( $selected as $s_slug ) {
			$key = array_search( $s_slug, $archived, true );
			if ( $key !== false ) {
				unset( $archived[ $key ] );
				$count++;
			}
		}
		update_option( 'xpressui_archived_workflows', array_values( $archived ) );
		/* translators: %d: number of restored workflows */
		xpressui_set_admin_notice( sprintf( _n( '%d workflow has been restored.', '%d workflows have been restored.', $count, 'xpressui-bridge' ), $count ), 'success' );
	} elseif ( 'sync' === $bulk_action ) {
		if ( ! xpressui_is_saas_connected() ) {
			xpressui_set_admin_notice( __( 'Synchronization failed: Active Console Connection required.', 'xpressui-bridge' ), 'error' );
		} else {
			$count  = 0;
			$errors = [];
			foreach ( $selected as $s_slug ) {
				$project_id = xpressui_get_workflow_console_project_id( $s_slug );
				if ( $project_id === '' ) {
					/* translators: %s: workflow slug */
					$errors[] = sprintf( __( 'Workflow "%s" is local-only and cannot be synced.', 'xpressui-bridge' ), $s_slug );
					continue;
				}
				$sync_res = xpressui_sync_project( $project_id );
				if ( is_wp_error( $sync_res ) ) {
					/* translators: 1: workflow slug, 2: error message details */
					$errors[] = sprintf( __( 'Failed to sync "%1$s": %2$s', 'xpressui-bridge' ), $s_slug, $sync_res->get_error_message() );
				} else {
					$count++;
				}
			}
			/* translators: %d: number of synced workflows */
			$msg = sprintf( _n( '%d workflow has been synced.', '%d workflows have been synced.', $count, 'xpressui-bridge' ), $count );
			if ( ! empty( $errors ) ) {
				$msg .= '<br />' . implode( '<br />', $errors );
				xpressui_set_admin_notice( $msg, 'warning' );
			} else {
				xpressui_set_admin_notice( $msg, 'success' );
			}
		}
	}

	wp_safe_redirect( remove_query_arg( [ 'xpressui_action', 'xpressui_slug', '_wpnonce' ] ) );
	exit;
}
add_action( 'admin_init', 'xpressui_handle_workflow_bulk_actions' );

function xpressui_submissions_trial_notice() {
	if ( ! function_exists( 'get_current_screen' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'edit-xpressui_submission' !== $screen->id ) {
		return;
	}

	if ( xpressui_is_saas_connected() ) {
		return;
	}

	// Only show if there is at least one workflow installed/imported
	$workflows = xpressui_get_installed_workflow_slugs();
	if ( empty( $workflows ) ) {
		return;
	}

	$connect_url = xpressui_get_wordpress_connect_url( admin_url( 'edit.php?post_type=xpressui_submission' ) );
	?>
	<div class="notice notice-info" style="border-left-color: #3b82f6; padding: 15px; border-radius: 8px; margin-top: 15px;">
		<h4 style="margin: 0 0 8px 0; color: #1e3a8a; font-weight: 700; font-size: 14px;"><?php esc_html_e( 'Trial Workflows: Local Storage Disabled', 'xpressui-bridge' ); ?></h4>
		<p style="margin: 0 0 12px 0; font-size: 13px; color: #475569; max-width: 800px; line-height: 1.5;">
			<?php esc_html_e( 'Submissions for trial (unconnected) workflows are sent directly to the administrator by email. To store, view, and manage submissions locally in this dashboard, connect your site to the IntakeFlow Console.', 'xpressui-bridge' ); ?>
		</p>
		<p style="margin: 0;">
			<a href="<?php echo esc_url( $connect_url ); ?>" class="button button-primary" style="background: #2563eb; border-color: #2563eb; box-shadow: none; text-shadow: none;">
				<?php esc_html_e( 'Connect to Console', 'xpressui-bridge' ); ?>
			</a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'xpressui_submissions_trial_notice' );