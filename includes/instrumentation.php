<?php
/**
 * Submission instrumentation helpers.
 *
 * @package XPressUI_Bridge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return stored submission events.
 *
 * @param int $post_id Submission post ID.
 * @return array<int, array<string, mixed>>
 */
function xpressui_get_submission_events( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return [];
	}

	$raw    = (string) get_post_meta( $post_id, '_xpressui_submission_events_json', true );
	$events = $raw !== '' ? json_decode( $raw, true ) : [];
	return is_array( $events ) ? array_values( $events ) : [];
}

/**
 * Append an instrumentation event to a submission.
 *
 * @param int    $post_id   Submission post ID.
 * @param string $event_type Event type, e.g. submit.completed.
 * @param string $source     Event source: runtime|bridge|api.
 * @param array  $metrics    Numeric metrics payload.
 * @param array  $context    Small string context payload.
 * @return void
 */
function xpressui_record_submission_event( $post_id, $event_type, $source = 'bridge', $metrics = [], $context = [] ) {
	$post_id    = (int) $post_id;
	$event_type = sanitize_key( str_replace( '.', '_', (string) $event_type ) );
	$source     = sanitize_key( (string) $source );
	if ( $post_id <= 0 || $event_type === '' ) {
		return;
	}

	$metrics_in  = is_array( $metrics ) ? $metrics : [];
	$context_in  = is_array( $context ) ? $context : [];
	$clean_metrics = [];
	$clean_context = [];

	foreach ( $metrics_in as $k => $v ) {
		$key = sanitize_key( (string) $k );
		if ( $key === '' || ! is_numeric( $v ) ) {
			continue;
		}
		$clean_metrics[ $key ] = (float) $v;
	}

	foreach ( $context_in as $k => $v ) {
		$key = sanitize_key( (string) $k );
		if ( $key === '' ) {
			continue;
		}
		$clean_context[ $key ] = sanitize_text_field( is_scalar( $v ) ? (string) $v : wp_json_encode( $v ) );
	}

	$events   = xpressui_get_submission_events( $post_id );
	$events[] = [
		'eventId'    => wp_generate_uuid4(),
		'occurredAt' => gmdate( 'Y-m-d\TH:i:s\Z' ),
		'source'     => $source !== '' ? $source : 'bridge',
		'eventType'  => str_replace( '_', '.', $event_type ),
		'metrics'    => $clean_metrics,
		'context'    => $clean_context,
	];

	// Keep bounded history per submission.
	$max_events = 120;
	if ( count( $events ) > $max_events ) {
		$events = array_slice( $events, -1 * $max_events );
	}

	update_post_meta( $post_id, '_xpressui_submission_events_json', wp_json_encode( $events ) );

	/**
	 * Fires after a submission event is recorded.
	 *
	 * @param int   $post_id Submission post ID.
	 * @param array $event   Event payload.
	 */
	do_action( 'xpressui_submission_event_recorded', $post_id, end( $events ) );
}

