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

	$view = isset( $_GET['xpressui_view'] ) ? sanitize_key( wp_unslash( (string) $_GET['xpressui_view'] ) ) : 'list';
	if ( 'detail' === $view ) {
		$slug = isset( $_GET['xpressui_slug'] ) ? sanitize_title( wp_unslash( (string) $_GET['xpressui_slug'] ) ) : '';
		if ( $slug !== '' && xpressui_is_installed_workflow( $slug ) ) {
			xpressui_render_workflow_detail_page( $slug );
			return;
		}
	}

	// Fetch Inbox rows for metrics overview and column display
	$inbox_rows = xpressui_get_project_inbox_rows();
	$inbox_by_slug = [];
	$total_projects = count( $inbox_rows );
	$total_submissions = 0;
	$total_new = 0;
	$total_in_review = 0;
	$total_pending_info = 0;
	$total_done = 0;
	$total_rejected = 0;
	foreach ( $inbox_rows as $row ) {
		$inbox_by_slug[ $row['projectSlug'] ] = $row;
		$total_submissions += (int) ( $row['total'] ?? 0 );
		$total_new += (int) ( $row['new'] ?? 0 );
		$total_in_review += (int) ( $row['in-review'] ?? 0 );
		$total_pending_info += (int) ( $row['pending_info'] ?? 0 );
		$total_done += (int) ( $row['done'] ?? 0 );
		$total_rejected += (int) ( $row['rejected'] ?? 0 );
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

	echo '<div class="wrap xpressui-wrap xpressui-wrap--workflows">';
	echo '<h1 class="wp-heading-inline">' . esc_html__( 'Workflows', 'xpressui-bridge' ) . '</h1>';
	if ( xpressui_pro_is_license_active() ) {
		echo '<button type="button" id="xpressui-global-sync-btn" class="page-title-action button button-primary" style="margin-left: 10px;">' . esc_html__( 'Sync from Console', 'xpressui-bridge' ) . '</button>';
	}
	echo '<hr class="wp-header-end">';
	echo '<p class="xpressui-page-intro">' . esc_html__( 'Manage your installed workflow packages and configure per-workflow settings.', 'xpressui-bridge' ) . '</p>';

	// Render Inbox metrics overview tiles
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

	echo '<div class="xpressui-inbox-overview" style="margin-top: 20px; margin-bottom: 25px;">';
	echo $stat_tile( $total_projects, __( 'Projects', 'xpressui-bridge' ), '', '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $stat_tile( $total_submissions, __( 'Submissions', 'xpressui-bridge' ), '', $status_filter_url( '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $stat_tile( $total_new, __( 'New', 'xpressui-bridge' ), ' xpressui-inbox-stat--new', $status_filter_url( 'new' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $stat_tile( $total_in_review, __( 'In review', 'xpressui-bridge' ), ' xpressui-inbox-stat--review', $status_filter_url( 'in-review' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $stat_tile( $total_pending_info, __( 'Pending info', 'xpressui-bridge' ), ' xpressui-inbox-stat--pending-info', $status_filter_url( 'pending_info' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $stat_tile( $total_done, __( 'Done', 'xpressui-bridge' ), ' xpressui-inbox-stat--done', $status_filter_url( 'done' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $stat_tile( $total_rejected, __( 'Rejected', 'xpressui-bridge' ), ' xpressui-inbox-stat--rejected', $status_filter_url( 'rejected' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</div>';

	if ( ! xpressui_pro_is_license_active() ) {
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

	$visible_installed_slugs = $installed_slugs;

	$render_workflow_table = function ( array $slugs ) use ( $inbox_by_slug ) {
		echo '<table class="wp-list-table widefat fixed striped xpressui-table xpressui-table--workflows">';
		echo '<thead><tr>';
		echo '<th class="column-title column-primary" style="font-weight: 700;">' . esc_html__( 'Workflow', 'xpressui-bridge' ) . '</th>';
		echo '<th style="font-weight: 700; width: 100px;">' . esc_html__( 'Version', 'xpressui-bridge' ) . '</th>';
		echo '<th style="font-weight: 700; width: 150px;">' . esc_html__( 'Steps / Fields', 'xpressui-bridge' ) . '</th>';
		echo '<th style="font-weight: 700; width: 150px;">' . esc_html__( 'Submissions', 'xpressui-bridge' ) . '</th>';
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
			$detail_url = add_query_arg(
				[
					'post_type'     => 'xpressui_submission',
					'page'          => 'xpressui-bridge',
					'xpressui_view' => 'detail',
					'xpressui_slug' => $slug,
				],
				admin_url( 'edit.php' )
			);
			$all_submissions_url = wp_nonce_url(
				add_query_arg( [ 'post_type' => 'xpressui_submission', 'xpressui_project' => $slug ], admin_url( 'edit.php' ) ),
				'xpressui_filter_submissions',
				'xpressui_filter_nonce'
			);
			
			echo '<tr>';
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
			if ( isset( $inbox_by_slug[ $slug ] ) && (int) $inbox_by_slug[ $slug ]['total'] > 0 ) {
				$actions_html[] = '<span class="submissions"><a href="' . esc_url( $all_submissions_url ) . '">' . esc_html__( 'Submissions', 'xpressui-bridge' ) . '</a></span>';
			}
			echo implode( ' | ', $actions_html );
			echo '</div>';
			echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__( 'Show more details', 'xpressui-bridge' ) . '</span></button>';
			echo '</td>';
			
			// Version Column
			$version = ! empty( $manifest_meta['runtimeVersion'] ) ? sanitize_text_field( $manifest_meta['runtimeVersion'] ) : '1.0.0';
			echo '<td style="vertical-align: middle;"><code>' . esc_html( $version ) . '</code></td>';
			
			// Steps / Fields Column
			$steps  = isset( $manifest_meta['stepCount'] ) ? (int) $manifest_meta['stepCount'] : 0;
			$fields = isset( $manifest_meta['fieldCount'] ) ? (int) $manifest_meta['fieldCount'] : 0;
			echo '<td style="vertical-align: middle; font-size: 13px;">';
			if ( $steps > 0 || $fields > 0 ) {
				$steps_text  = sprintf( _n( '%d step', '%d steps', $steps, 'xpressui-bridge' ), $steps );
				$fields_text = sprintf( _n( '%d field', '%d fields', $fields, 'xpressui-bridge' ), $fields );
				echo esc_html( $steps_text . ', ' . $fields_text );
			} else {
				echo '<span style="color: #888; font-style: italic;">&mdash;</span>';
			}
			echo '</td>';

			// Submissions Column
			echo '<td style="vertical-align: middle; font-size: 13px;">';
			if ( isset( $inbox_by_slug[ $slug ] ) && (int) $inbox_by_slug[ $slug ]['total'] > 0 ) {
				$total = (int) $inbox_by_slug[ $slug ]['total'];
				$new   = (int) $inbox_by_slug[ $slug ]['new'];
				echo '<a href="' . esc_url( $all_submissions_url ) . '" style="font-size: 14px; font-weight: 600; text-decoration: none;">' . esc_html( (string) $total ) . '</a>';
				if ( $new > 0 ) {
					echo ' <span class="xpressui-badge xpressui-badge--status-new" style="margin-left: 6px; font-size: 10px; padding: 1px 6px; vertical-align: middle;">' . sprintf( esc_html__( '%d new', 'xpressui-bridge' ), $new ) . '</span>';
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

	// --- Installed workflows table ---
	if ( empty( $visible_installed_slugs ) ) {
		echo '<div class="card xpressui-admin-card xpressui-empty-state"><p class="xpressui-empty-state__title">' . esc_html__( 'No workflows installed yet.', 'xpressui-bridge' ) . '</p><p class="xpressui-empty-state__body">' . esc_html__( 'Upload a workflow package below to get started, or use the bundled starter workflow already included with the plugin.', 'xpressui-bridge' ) . '</p></div>';
	} else {
		$render_workflow_table( $visible_installed_slugs );
	}

	// Inline javascript to handle the global sync button
	if ( xpressui_pro_is_license_active() ) {
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
			$page_title .= ' - ' . sanitize_text_field( $link_config['label'] );
		} else {
			$page_title .= ' - ' . sanitize_text_field( $link_id );
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
	echo '<p class="description">' . esc_html__( 'Configure the connection to your IntakeFlow Console.', 'xpressui-bridge' ) . '</p>';
	if ( function_exists( 'xpressui_render_console_connection_form' ) ) {
		xpressui_render_console_connection_form();
	}
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
	if ( xpressui_pro_is_license_active() ) {
		echo '<button type="button" id="xpressui-single-sync-btn" class="page-title-action button button-primary" style="margin-left: 10px;">' . esc_html__( 'Sync from Console', 'xpressui-bridge' ) . '</button>';
	}
	echo '<hr class="wp-header-end">';
	echo '<div id="xpressui-single-sync-container"></div>';

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
								'id'    => $link_id,
								'label' => $config_data['label'] ?? $config_data['id'] ?? $link_id,
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
	echo '<th style="font-weight: 700; width: 250px;">' . esc_html__( 'Target', 'xpressui-bridge' ) . '</th>';
	echo '<th style="font-weight: 700;">' . esc_html__( 'Shortcode', 'xpressui-bridge' ) . '</th>';
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
	echo '<td style="vertical-align: middle;">';
	if ( ! empty( $pure_legacy_pages ) ) {
		$page_links = [];
		foreach ( $pure_legacy_pages as $p_id ) {
			$p_title = get_the_title( $p_id ) ?: '#' . $p_id;
			$page_links[] = '<a href="' . esc_url( get_permalink( $p_id ) ) . '" target="_blank" rel="noreferrer"><strong>' . esc_html( $p_title ) . '</strong></a>';
		}
		echo implode( ', ', $page_links );
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

		echo '<tr>';
		echo '<td style="font-weight: 600; font-size: 13px; vertical-align: middle;">' . esc_html( $link['label'] ) . '</td>';
		echo '<td style="vertical-align: middle;"><code style="background: #f0f0f1; padding: 4px 8px; border-radius: 4px; font-size: 13px;">' . esc_html( $shortcode ) . '</code></td>';
		echo '<td style="vertical-align: middle;">';
		if ( ! empty( $link_pages ) ) {
			$page_links = [];
			foreach ( $link_pages as $p_id ) {
				$p_title = get_the_title( $p_id ) ?: '#' . $p_id;
				$page_links[] = '<a href="' . esc_url( get_permalink( $p_id ) ) . '" target="_blank" rel="noreferrer"><strong>' . esc_html( $p_title ) . '</strong></a>';
			}
			echo implode( ', ', $page_links );
		} else {
			echo '<a href="' . esc_url( $create_link_page_url ) . '" class="button button-small button-primary">' . esc_html__( 'Create page', 'xpressui-bridge' ) . '</a>';
		}
		echo '</td>';
		echo '</tr>';
	}

	echo '</tbody>';
	echo '</table>';

	echo '</div>'; // .wrap

	if ( xpressui_pro_is_license_active() ) {
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
