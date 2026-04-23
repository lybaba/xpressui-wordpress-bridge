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
			'menu_icon'    => 'dashicons-layout',
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
