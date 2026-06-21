<?php
namespace Hashbar\Pro\ABTest;

/**
 * A/B Test Statistics Calculation
 *
 * Calculates statistics for A/B tests including impressions, clicks, conversions, rates, and significance.
 *
 * @package HashBar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get A/B test statistics for a bar
 *
 * @param int $bar_id Announcement bar ID.
 * @return array Statistics array with variant data.
 */


class Statistics {
	/**
	 * Get A/B test statistics for a bar
	 *
	 * @param int $bar_id Announcement bar ID.
	 * @return array Statistics array with variant data.
	 */
	public static function get_statistics( $bar_id ) {
		global $wpdb;

		$table_name_events = $wpdb->prefix . 'hthb_ab_events';
		$table_name_assignments = $wpdb->prefix . 'hthb_ab_tests';

		// Check if tables exist
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name_events ) ) !== $table_name_events ) {
			return array(
				'variants' => array(),
				'total_visitors' => 0,
			);
		}

		// Get variants from bar meta
		$variants_raw = get_post_meta( $bar_id, '_wphash_ab_test_variants', true );
		$variants_config = array();
		if ( is_string( $variants_raw ) ) {
			$decoded = json_decode( $variants_raw, true );
			if ( is_array( $decoded ) ) {
				$variants_config = $decoded;
			}
		} elseif ( is_array( $variants_raw ) ) {
			$variants_config = $variants_raw;
		}

		// Get statistics for each variant
		$stats = array();
		$total_visitors = 0;

		// Get unique visitors per variant from assignments table
		$visitor_counts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT variant_id, COUNT(DISTINCT visitor_id) as unique_visitors
				FROM $table_name_assignments
				WHERE bar_id = %d
				GROUP BY variant_id",
				$bar_id
			),
			ARRAY_A
		);

		$visitor_map = array();
		foreach ( $visitor_counts as $count ) {
			$visitor_map[ $count['variant_id'] ] = (int) $count['unique_visitors'];
			$total_visitors += (int) $count['unique_visitors'];
		}

		// Get event counts per variant
		$event_counts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT variant_id, event_type, COUNT(*) as event_count
				FROM $table_name_events
				WHERE bar_id = %d
				GROUP BY variant_id, event_type",
				$bar_id
			),
			ARRAY_A
		);

		// Organize events by variant
		$events_map = array();
		foreach ( $event_counts as $event ) {
			if ( ! isset( $events_map[ $event['variant_id'] ] ) ) {
				$events_map[ $event['variant_id'] ] = array(
					'impression' => 0,
					'click'      => 0,
					'conversion' => 0,
					'countdown_view' => 0,
					'coupon_copy'    => 0,
				);
			}
			$events_map[ $event['variant_id'] ][ $event['event_type'] ] = (int) $event['event_count'];
		}

		// Build statistics for each variant
		foreach ( $variants_config as $variant ) {
			$variant_id = isset( $variant['id'] ) ? $variant['id'] : '';
			if ( empty( $variant_id ) ) {
				continue;
			}

			$impressions = isset( $events_map[ $variant_id ]['impression'] ) ? $events_map[ $variant_id ]['impression'] : 0;
			$clicks      = isset( $events_map[ $variant_id ]['click'] ) ? $events_map[ $variant_id ]['click'] : 0;
			$conversions = isset( $events_map[ $variant_id ]['conversion'] ) ? $events_map[ $variant_id ]['conversion'] : 0;
			$countdown_views = isset( $events_map[ $variant_id ]['countdown_view'] ) ? $events_map[ $variant_id ]['countdown_view'] : 0;
			$coupon_copies = isset( $events_map[ $variant_id ]['coupon_copy'] ) ? $events_map[ $variant_id ]['coupon_copy'] : 0;
			$unique_visitors = isset( $visitor_map[ $variant_id ] ) ? $visitor_map[ $variant_id ] : 0;

			// Calculate rates
			$ctr = $impressions > 0 ? ( $clicks / $impressions ) * 100 : 0;
			$conversion_rate = $impressions > 0 ? ( $conversions / $impressions ) * 100 : 0;

			$stats[] = array(
				'variant_id'     => $variant_id,
				'variant_name'   => isset( $variant['name'] ) ? $variant['name'] : $variant_id,
				'unique_visitors' => $unique_visitors,
				'impressions'    => $impressions,
				'clicks'         => $clicks,
				'conversions'    => $conversions,
				'countdown_views' => $countdown_views,
				'coupon_copies'   => $coupon_copies,
				'ctr'            => round( $ctr, 2 ),
				'conversion_rate' => round( $conversion_rate, 2 ),
			);
		}

		// Also include control variant (no variant_id)
		$control_impressions = isset( $events_map[''] ) ? $events_map['']['impression'] : ( isset( $events_map['control'] ) ? $events_map['control']['impression'] : 0 );
		$control_clicks = isset( $events_map[''] ) ? $events_map['']['click'] : ( isset( $events_map['control'] ) ? $events_map['control']['click'] : 0 );
		$control_conversions = isset( $events_map[''] ) ? $events_map['']['conversion'] : ( isset( $events_map['control'] ) ? $events_map['control']['conversion'] : 0 );
		$control_countdown_views = isset( $events_map[''] ) ? $events_map['']['countdown_view'] : ( isset( $events_map['control'] ) ? $events_map['control']['countdown_view'] : 0 );
		$control_coupon_copies = isset( $events_map[''] ) ? $events_map['']['coupon_copy'] : ( isset( $events_map['control'] ) ? $events_map['control']['coupon_copy'] : 0 );
		$control_visitors = isset( $visitor_map[''] ) ? $visitor_map[''] : ( isset( $visitor_map['control'] ) ? $visitor_map['control'] : 0 );

		if ( $control_impressions > 0 || $control_clicks > 0 || $control_conversions > 0 ) {
			$control_ctr = $control_impressions > 0 ? ( $control_clicks / $control_impressions ) * 100 : 0;
			$control_conversion_rate = $control_impressions > 0 ? ( $control_conversions / $control_impressions ) * 100 : 0;

			array_unshift(
				$stats,
				array(
					'variant_id'     => 'control',
					'variant_name'   => __( 'Control (Original)', 'hashbar' ),
					'unique_visitors' => $control_visitors,
					'impressions'    => $control_impressions,
					'clicks'         => $control_clicks,
					'conversions'    => $control_conversions,
					'countdown_views' => $control_countdown_views,
					'coupon_copies'   => $control_coupon_copies,
					'ctr'            => round( $control_ctr, 2 ),
					'conversion_rate' => round( $control_conversion_rate, 2 ),
				)
			);
		}

		return array(
			'variants' => $stats,
			'total_visitors' => $total_visitors,
		);
	}
}



