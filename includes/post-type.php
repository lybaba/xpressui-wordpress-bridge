<?php
/**
 * Custom post type registration and list columns for XPressUI submissions.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function xpressui_register_submission_post_type() {
	register_post_type(
		'xpressui_submission',
		[
			'public'   => false,
			'show_ui'  => true,
			'labels'   => [
				'name'          => __( 'Submissions', 'xpressui-bridge' ),
				'singular_name' => __( 'Submission', 'xpressui-bridge' ),
				'menu_name'     => __( 'XPressUI', 'xpressui-bridge' ),
				'all_items'     => __( 'All Submissions', 'xpressui-bridge' ),
			],
			'menu_icon'    => 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCI+PGcgZmlsbD0iY3VycmVudENvbG9yIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiIHRyYW5zZm9ybT0ic2NhbGUoMC4yMTE1MykgdHJhbnNsYXRlKC01NC43NSwgLTEzOC41NCkgc2NhbGUoMSwgLTEpIHRyYW5zbGF0ZSgwLCAtMzc1KSI+PHBhdGggZD0iTSA1NC43NSwyMDUuOTQ1IEwgNTQuNzUsMjEzLjExMyBMIDYyLjMwMywyMTMuMTEzIEwgNjIuMzAzLDIwNS45NDUgTCA1NC43NSwyMDUuOTQ1IFoiIC8+PHBhdGggZD0iTSAxNDUuMDk5LDE4Ny45MjkgQyAxNDQuMDIyLDE4OC40NTQgMTQyLjg5NywxODguOTMgMTQxLjczNCwxODkuMzY0IEwgMTQxLjczNCwxOTEuMDMxIEMgMTQyLjg5MiwxOTEuNDY0IDE0NC4wMTYsMTkxLjkzOSAxNDUuMDk5LDE5Mi40NyBDIDE0Ni41OTcsMTkzLjIwMyAxNDguMDA3LDE5NC4wNDQgMTQ5LjI4NywxOTUuMDExIEwgMTQ5LjI4NywxODUuMzgyIEMgMTQ4LjAwNywxODYuMzUgMTQ2LjU5NywxODcuMTk0IDE0NS4wOTksMTg3LjkyOSBaIiAvPjxwYXRoIGQ9Ik0gNTQuNzUsMTU4LjAwMyBMIDU0Ljc1LDE5OS44NDcgTCA2Mi4zMDMsMTk5Ljg0NyBMIDYyLjMwMywxNjguMDMgTCA3OC4yMTMsMjAzLjg1MyBDIDc5LjA3LDIwNS43NzIgODAuMjY3LDIwNy4xMTIgODEuODE0LDIwNy44OTQgQyA4My4zNjUsMjA4LjY4NCA4NS4zNjMsMjA5LjA2NSA4Ny43OCwyMDkuMDY1IEMgOTAuMTY5LDIwOS4wNjUgOTIuMTQxLDIwOC42ODQgOTMuNjk1LDIwNy44OTQgQyA5NS4yNDQsMjA3LjExMiA5Ni40MjIsMjA1Ljc3MiA5Ny4yMjQsMjAzLjg1MyBMIDExMS45MjQsMTY5LjQ3IEwgMTExLjkyNCwyMTMuMjkxIEwgMTE5LjQ2OSwyMTMuMjkxIEwgMTE5LjQ2OSwxOTUuMTY1IEMgMTI1LjExNSwxOTYuNTM5IDEzMC42MTgsMTk3LjcyNCAxMzQuODM1LDE5OS44OCBDIDEzOC4zODgsMjAxLjY5NSAxNDAuOTYyLDIwNC4xMSAxNDEuNzM0LDIwOC4zNjMgTCAxNDEuNzM0LDIyMC4zOCBDIDE0MS43MzQsMjI1LjA5NyAxMzcuOTIzLDIyOC45MDYgMTMzLjIxNSwyMjguOTA2IEwgNzAuODMsMjI4LjkwNiBDIDY2LjEyLDIyOC45MDYgNjIuMzAzLDIyNS4wOTcgNjIuMzAzLDIyMC4zOCBMIDYyLjMwMywyMTkuMjE3IEwgNTQuNzUsMjE5LjIxNyBMIDU0Ljc1LDIyMC4zOCBDIDU0Ljc1LDIyOS4yNjggNjEuOTQzLDIzNi40NTggNzAuODMsMjM2LjQ1OCBMIDEzMy4yMTUsMjM2LjQ1OCBDIDE0Mi4wODIsMjM2LjQ1OCAxNDkuMjg3LDIyOS4yNjggMTQ5LjI4NywyMjAuMzggTCAxNDkuMjg3LDIwNy45NzcgQyAxNDkuMTQ1LDIwNi45ODYgMTQ4LjkwOSwyMDYuMDkxIDE0OC41ODEsMjA1LjI3NCBDIDE0OC4xMzIsMjAzLjc5OCAxNDcuNTE5LDIwMi40NzMgMTQ2LjE2MiwyMDAuNDI5IEMgMTQ0LjI4MSwxOTcuNDk0IDE0MS40MzEsMTk1LjIyNiAxMzguMDg2LDE5My41MTcgQyAxMzUuMzE5LDE5Mi4xMDMgMTMyLjI1NSwxOTEuMDc2IDEyOS4wODEsMTkwLjIwMSBDIDEzMi4yNTUsMTg5LjMyIDEzNS4zNDEsMTg4LjI4MiAxMzguMDg2LDE4Ni44ODEgQyAxNDAuODYyLDE4NS40NiAxNDcuNTU1LDE3OC45MSAxNDguNzAzLDE3NC44MDcgQyAxNDguOTcsMTc0LjA2NyAxNDkuMTY0LDE3My4yNzcgMTQ5LjI4NywxNzIuNDE4IEwgMTQ5LjI4NywxNjcuODg1IEwgMTQxLjczNCwxNjcuODg1IEwgMTQxLjczNCwxNzIuMDM3IEMgMTQwLjk2MiwxNzYuMjg3IDEzOC4zODgsMTc4LjcwMiAxMzQuODM1LDE4MC41MTkgQyAxMzAuNjM2LDE4Mi42NjQgMTI0Ljk5MSwxODMuOSAxMTkuNDY4LDE4NS4yMzkgTCAxMTkuNDY4LDE2Ny44ODUgTCAxMDMuNzY4LDE2Ny44ODUgTCA4OS45NDMsMjAwLjQyMiBDIDg5LjU0MiwyMDEuMzQ4IDg4Ljc5NCwyMDEuODA0IDg3LjcxOCwyMDEuODA0IEMgODYuNjM3LDIwMS44MDQgODUuODgxLDIwMS4zNDggODUuNDI1LDIwMC40MjIgTCA3MS4wMDEsMTY3Ljg2MSBMIDYyLjMwMywxNjcuODYxIEwgNjIuMzAzLDE1OC4wMDMgQyA2Mi4zMDMsMTUzLjI5NSA2Ni4xMiwxNDkuNDc2IDcwLjgzLDE0OS40NzYgTCAxMzMuMjE1LDE0OS40NzYgQyAxMzcuOTIzLDE0OS40NzYgMTQxLjczNCwxNTMuMjk1IDE0MS43MzQsMTU4LjAwMyBMIDE0MS43MzQsMTYxLjUyNCBMIDE0OS4yODcsMTYxLjUyNCBMIDE0OS4yODcsMTU4LjAwMyBDIDE0OS4yODcsMTQ5LjEzNiAxNDIuMDgyLDE0MS45MjMgMTMzLjIxNSwxNDEuOTIzIEwgNzAuODMsMTQxLjkyMyBDIDYxLjk0MywxNDEuOTIzIDU0Ljc1LDE0OS4xMzYgNTQuNzUsMTU4LjAwMyBaIE0gODMuNDIsMTg0LjQ1MyBDIDgzLjQyLDE4Ni43MDYgODUuMjQ5LDE4OC41MzggODcuNSwxODguNTM4IEMgODkuNzUzLDE4OC41MzggOTEuNTgyLDE4Ni43MDYgOTEuNTgyLDE4NC40NTMgQyA5MS41ODIsMTgyLjE5OCA4OS43NTMsMTgwLjM2OCA4Ny41LDE4MC4zNjggQyA4NS4yNDksMTgwLjM2OCA4My40MiwxODIuMTk4IDgzLjQyLDE4NC40NTMgWiIgLz48L2c+PC9zdmc+',
			'menu_position' => 80,
			'label'        => __( 'XPressUI Submissions', 'xpressui-bridge' ),
			'supports'     => [ 'title' ],
			'capabilities' => [
				'create_posts' => 'do_not_allow',
			],
			'map_meta_cap' => true,
		]
	);
}

function xpressui_submission_columns( $columns ) {
	$columns['xpressui_project_slug']        = __( 'Project', 'xpressui-bridge' );
	$columns['xpressui_submission_status']   = __( 'Status', 'xpressui-bridge' );
	$columns['xpressui_submission_contact']  = __( 'Contact', 'xpressui-bridge' );
	$columns['xpressui_submission_assignee'] = __( 'Assignee', 'xpressui-bridge' );
	$columns['xpressui_submission_files']    = __( 'Files', 'xpressui-bridge' );
	$columns['xpressui_submission_id']       = __( 'Submission ID', 'xpressui-bridge' );
	return $columns;
}

function xpressui_submission_column_content( $column, $post_id ) {
	switch ( $column ) {
		case 'xpressui_project_slug':
			echo '<strong>' . esc_html( (string) get_post_meta( $post_id, '_xpressui_project_slug', true ) ) . '</strong>';
			break;

		case 'xpressui_submission_status':
			$status = (string) get_post_meta( $post_id, '_xpressui_submission_status', true );
			$badge_class = 'xpressui-badge xpressui-badge--status-new';
			if ( 'in-review' === $status ) {
				$badge_class = 'xpressui-badge xpressui-badge--status-in-review';
			} elseif ( 'pending_info' === $status ) {
				$badge_class = 'xpressui-badge xpressui-badge--status-pending-info';
			} elseif ( 'done' === $status ) {
				$badge_class = 'xpressui-badge xpressui-badge--status-done';
			} elseif ( 'rejected' === $status ) {
				$badge_class = 'xpressui-badge xpressui-badge--status-rejected';
			}
			echo '<span class="' . esc_attr( $badge_class ) . '">' . esc_html( xpressui_get_status_label( $status ) ) . '</span>';
			break;

		case 'xpressui_submission_assignee':
			$assignee = xpressui_get_assignee_display( $post_id );
			if ( $assignee !== '' ) {
				echo esc_html( $assignee );
			} else {
				echo '<span class="xpressui-muted">' . esc_html__( 'Unassigned', 'xpressui-bridge' ) . '</span>';
			}
			break;

		case 'xpressui_submission_contact':
			$payload = xpressui_get_submission_payload( $post_id );
			$summary = xpressui_get_contact_summary( $payload );
			if ( $summary !== '' ) {
				echo esc_html( $summary );
			} else {
				echo '<span class="xpressui-muted">' . esc_html__( 'No contact details', 'xpressui-bridge' ) . '</span>';
			}
			break;

		case 'xpressui_submission_files':
			echo '<span class="xpressui-badge xpressui-badge--count">' . esc_html( (string) xpressui_get_uploaded_file_count( $post_id ) ) . '</span>';
			break;

		case 'xpressui_submission_id':
			echo '<code>' . esc_html( (string) get_post_meta( $post_id, '_xpressui_submission_id', true ) ) . '</code>';
			break;
	}
}

function xpressui_delete_submission_media_on_post_delete( $post_id, $post ) {
	if ( ! $post instanceof WP_Post ) {
		$post = get_post( $post_id );
	}

	if ( ! $post instanceof WP_Post || $post->post_type !== 'xpressui_submission' ) {
		return;
	}

	xpressui_delete_submission_attachments( $post_id );
}
