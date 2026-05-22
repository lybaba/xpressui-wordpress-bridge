<?php
/**
 * Submission list filters, query modification, and row actions.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function xpressui_render_submission_filters( $post_type ) {
	if ( $post_type !== 'xpressui_submission' ) {
		return;
	}

	wp_nonce_field( 'xpressui_filter_submissions', 'xpressui_filter_nonce', false );

	$selected_filters  = xpressui_get_submission_filter_values();
	$selected_status   = $selected_filters['status'];
	$selected_project  = $selected_filters['project'];
	$selected_assignee = $selected_filters['assignee'];

	$projects = xpressui_get_submission_project_filter_options();

	echo '<select name="xpressui_status">';
	echo '<option value="">' . esc_html__( 'All statuses', 'xpressui-bridge' ) . '</option>';
	foreach ( xpressui_get_status_options() as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '"' . selected( $selected_status, $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';

	echo '<select name="xpressui_project">';
	echo '<option value="">' . esc_html__( 'All projects', 'xpressui-bridge' ) . '</option>';
	foreach ( $projects as $value => $label ) {
		echo '<option value="' . esc_attr( $value ) . '"' . selected( $selected_project, $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';

	echo '<select name="xpressui_assignee">';
	echo '<option value="">' . esc_html__( 'All assignees', 'xpressui-bridge' ) . '</option>';
	foreach ( xpressui_get_assignable_users() as $user ) {
		$value = (string) $user->ID;
		$label = (string) ( $user->display_name ?: $user->user_login ?: ( 'User #' . $user->ID ) );
		echo '<option value="' . esc_attr( $value ) . '"' . selected( $selected_assignee, $value, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
}

function xpressui_apply_submission_filters( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( ( $query->get( 'post_type' ) ?: '' ) !== 'xpressui_submission' ) {
		return;
	}

	$selected_filters  = xpressui_get_submission_filter_values();
	$selected_status   = $selected_filters['status'];
	$selected_project  = $selected_filters['project'];
	$selected_assignee = $selected_filters['assignee'];

	if ( '' === $selected_status && '' === $selected_project && '' === $selected_assignee ) {
		return;
	}

	$query->set( 'post__in', xpressui_get_filtered_submission_ids( $selected_status, $selected_project, $selected_assignee ) ?: [ 0 ] );
}

function xpressui_get_submission_filter_values() {
	$empty = [
		'status'   => '',
		'project'  => '',
		'assignee' => '',
	];

	if ( ! isset( $_GET['xpressui_filter_nonce'] ) ) {
		return $empty;
	}

	if ( ! wp_verify_nonce(
		sanitize_text_field( wp_unslash( (string) $_GET['xpressui_filter_nonce'] ) ),
		'xpressui_filter_submissions'
	) ) {
		return $empty;
	}

	return [
		'status'   => isset( $_GET['xpressui_status'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['xpressui_status'] ) ) : '',
		'project'  => isset( $_GET['xpressui_project'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['xpressui_project'] ) ) : '',
		'assignee' => isset( $_GET['xpressui_assignee'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['xpressui_assignee'] ) ) : '',
	];
}

function xpressui_get_submission_project_filter_options() {
	$projects = [];
	foreach ( xpressui_get_all_submission_ids() as $id ) {
		$slug = sanitize_title( (string) get_post_meta( $id, '_xpressui_project_slug', true ) );
		if ( '' !== $slug ) {
			$projects[ $slug ] = $slug;
		}
	}
	ksort( $projects );
	return $projects;
}

function xpressui_get_all_submission_ids() {
	return get_posts( [
		'post_type'      => 'xpressui_submission',
		'post_status'    => 'private',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'orderby'        => 'date',
		'order'          => 'DESC',
	] );
}

function xpressui_get_filtered_submission_ids( $status, $project, $assignee ) {
	$status   = sanitize_text_field( (string) $status );
	$project  = sanitize_title( (string) $project );
	$assignee = absint( $assignee );
	$ids      = [];

	foreach ( xpressui_get_all_submission_ids() as $id ) {
		if ( '' !== $status && $status !== (string) get_post_meta( $id, '_xpressui_submission_status', true ) ) {
			continue;
		}
		if ( '' !== $project && $project !== (string) get_post_meta( $id, '_xpressui_project_slug', true ) ) {
			continue;
		}
		if ( $assignee > 0 && $assignee !== (int) get_post_meta( $id, '_xpressui_assignee_id', true ) ) {
			continue;
		}
		$ids[] = (int) $id;
	}

	return $ids;
}

function xpressui_add_submission_row_actions( $actions, $post ) {
	if ( ( $post->post_type ?? '' ) !== 'xpressui_submission' ) {
		return $actions;
	}
	$current_status = (string) get_post_meta( $post->ID, '_xpressui_submission_status', true );
	foreach ( xpressui_get_status_options() as $status => $label ) {
		if ( $status === $current_status ) {
			continue;
		}
		$url = wp_nonce_url(
			add_query_arg( [
				'post_type'            => 'xpressui_submission',
				'xpressui_submission_id' => $post->ID,
				'xpressui_mark_status' => $status,
			], admin_url( 'edit.php' ) ),
			'xpressui_mark_submission_status_' . $post->ID . '_' . $status
		);
		/* translators: %s: status label */
		$actions[ 'xpressui_mark_' . $status ] = '<a href="' . esc_url( $url ) . '">' . esc_html( sprintf( __( 'Mark %s', 'xpressui-bridge' ), $label ) ) . '</a>';
	}
	return $actions;
}

function xpressui_handle_submission_status_action() {
	if ( ! is_admin() || ! isset( $_GET['xpressui_submission_id'] ) || ! isset( $_GET['xpressui_mark_status'] ) ) {
		return;
	}

	$post_id = absint( wp_unslash( (string) $_GET['xpressui_submission_id'] ) );

	$status  = sanitize_text_field( wp_unslash( (string) $_GET['xpressui_mark_status'] ) );
	$options = xpressui_get_status_options();

	if ( $post_id <= 0 || ! isset( $options[ $status ] ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	check_admin_referer( 'xpressui_mark_submission_status_' . $post_id . '_' . $status );
	xpressui_set_submission_status( $post_id, $status );
	wp_safe_redirect( add_query_arg( [
		'post_type'               => 'xpressui_submission',
		'xpressui_status_updated' => 1,
	], admin_url( 'edit.php' ) ) );
	exit;
}
