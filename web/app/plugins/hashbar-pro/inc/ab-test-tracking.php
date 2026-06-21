<?php
namespace Hashbar\Pro\ABTest;

/**
 * A/B Test Event Tracking
 *
 * Handles tracking of A/B test events (impressions, clicks, conversions).
 *
 * @package HashBar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Track A/B test event
 *
 * @param int    $bar_id Announcement bar ID.
 * @param string $variant_id Variant ID.
 * @param string $visitor_id Visitor identifier.
 * @param string $event_type Event type ('impression', 'click', 'conversion').
 * @param string $event_value Optional event value.
 * @return bool True on success, false on failure.
 */
function hashbar_ab_test_track_event_internal( $bar_id, $variant_id, $visitor_id, $event_type, $event_value = null ) {
	global $wpdb;

	$table_name = $wpdb->prefix . 'hthb_ab_events';

	// Check if table exists
	if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
		return false;
	}

	// Prevent duplicate tracking for impressions (same visitor, same variant, same page view)
	// Page views are differentiated by page URL + time window (5 minutes)
	if ( $event_type === 'impression' ) {
		// Check if this impression was tracked in the last 5 minutes
		// This prevents duplicate impressions from rapid page reloads but allows new page views
		$five_minutes_ago = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 300 );
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table_name
				WHERE bar_id = %d
				AND variant_id = %s
				AND visitor_id = %s
				AND event_type = 'impression'
				AND created_at > %s
				LIMIT 1",
				$bar_id,
				$variant_id,
				$visitor_id,
				$five_minutes_ago
			)
		);

		if ( $existing ) {
			// Already tracked in the last 5 minutes, skip (rapid reload)
			return true;
		}
	}

	// Insert event
	$result = $wpdb->insert(
		$table_name,
		array(
			'bar_id'     => $bar_id,
			'variant_id' => sanitize_text_field( $variant_id ),
			'visitor_id' => sanitize_text_field( $visitor_id ),
			'event_type' => sanitize_text_field( $event_type ),
			'event_value' => $event_value ? sanitize_text_field( $event_value ) : null,
			'created_at' => current_time( 'mysql' ),
		),
		array( '%d', '%s', '%s', '%s', '%s', '%s' )
	);

	return $result !== false;
}

