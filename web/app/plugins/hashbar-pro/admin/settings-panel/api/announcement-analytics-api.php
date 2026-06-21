<?php
/**
 * Announcement Analytics REST API
 * Provides endpoints for querying announcement bar analytics data
 * Separate from notification bar analytics
 *
 * @package Hashbar\Pro\Api
 */

namespace Hashbar\Pro\API;

use WP_REST_Response;
use WP_REST_Request;

// Register REST API routes
add_action( 'rest_api_init', [ AnnouncementAnalytics::class, 'register_routes' ] );

/**
 * Register all announcement analytics endpoints
 */
class AnnouncementAnalytics {

	public static function register_routes() {
		// Overview stats endpoint
		register_rest_route(
			'hashbar/v1',
			'/announcement-analytics/overview',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_announcement_analytics_overview' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Device breakdown endpoint
		register_rest_route(
			'hashbar/v1',
			'/announcement-analytics/devices',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_announcement_analytics_devices' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Page breakdown endpoint
		register_rest_route(
			'hashbar/v1',
			'/announcement-analytics/pages',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_announcement_analytics_pages' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Country breakdown endpoint
		register_rest_route(
			'hashbar/v1',
			'/announcement-analytics/countries',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_announcement_analytics_countries' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Timeline endpoint
		register_rest_route(
			'hashbar/v1',
			'/announcement-analytics/timeline',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_announcement_analytics_timeline' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Export endpoint
		register_rest_route(
			'hashbar/v1',
			'/announcement-analytics/export',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'export_announcement_analytics' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get overview statistics for announcement analytics
	 *
	 * @param WP_REST_Request $request The request object
	 * @return WP_REST_Response
	 */
	public static function get_announcement_analytics_overview( $request ) {
		global $wpdb;

		$bar_id     = $request->get_param( 'bar_id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );

		if ( ! $bar_id ) {
			return new WP_REST_Response(
				array( 'error' => 'bar_id is required' ),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_announcement_analytics';

		// Build query
		$where = $wpdb->prepare( 'WHERE campaign_id = %d AND campaign_type = %s', $bar_id, 'announcement' );

		if ( $start_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) >= %s', $start_date );
		}

		if ( $end_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) <= %s', $end_date );
		}

		// Get view count
		$views = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where} AND event_type = 'view'" );

		// Get click count
		$clicks = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where} AND event_type = 'click'" );

		// Get conversion count
		$conversions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where} AND event_type = 'conversion'" );

		// Get unique sessions
		$unique_sessions = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$table} {$where}" );

		// Calculate rates
		$ctr  = $views > 0 ? round( ( $clicks / $views ) * 100, 2 ) : 0;
		$conv = $clicks > 0 ? round( ( $conversions / $clicks ) * 100, 2 ) : 0;

		// Check if bar has A/B testing enabled
		$ab_test_enabled = get_post_meta( $bar_id, '_wphash_ab_test_enabled', true );
		$is_ab_test_enabled = ! empty( $ab_test_enabled ) && $ab_test_enabled !== 'false' && $ab_test_enabled !== '0';

		return new WP_REST_Response(
			array(
				'total_views'        => $views,
				'total_clicks'       => $clicks,
				'total_conversions'  => $conversions,
				'click_through_rate' => $ctr,
				'conversion_rate'    => $conv,
				'unique_sessions'    => $unique_sessions,
				'ab_test_enabled'    => $is_ab_test_enabled,
			)
		);
	}

	/**
	 * Get device breakdown statistics
	 *
	 * @param WP_REST_Request $request The request object
	 * @return WP_REST_Response
	 */
	public static function get_announcement_analytics_devices( $request ) {
		global $wpdb;

		$bar_id     = $request->get_param( 'bar_id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );

		if ( ! $bar_id ) {
			return new WP_REST_Response(
				array( 'error' => 'bar_id is required' ),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_announcement_analytics';

		// Build where clause
		$where = $wpdb->prepare( 'WHERE campaign_id = %d AND campaign_type = %s', $bar_id, 'announcement' );

		if ( $start_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) >= %s', $start_date );
		}

		if ( $end_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) <= %s', $end_date );
		}

		// Get data by device type
		$results = $wpdb->get_results(
			"SELECT
				device_type,
				COUNT(*) as total_events,
				SUM(CASE WHEN event_type = 'view' THEN 1 ELSE 0 END) as views,
				SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks
			FROM {$table}
			{$where}
			GROUP BY device_type
			ORDER BY total_events DESC"
		);

		// Format response
		$breakdown = array();
		foreach ( $results as $row ) {
			$views = (int) $row->views;
			$clicks = (int) $row->clicks;
			$ctr    = $views > 0 ? round( ( $clicks / $views ) * 100, 2 ) : 0;

			$breakdown[] = array(
				'device'  => $row->device_type,
				'views'   => $views,
				'clicks'  => $clicks,
				'ctr'     => $ctr,
			);
		}

		return new WP_REST_Response( $breakdown );
	}

	/**
	 * Get page performance breakdown
	 *
	 * @param WP_REST_Request $request The request object
	 * @return WP_REST_Response
	 */
	public static function get_announcement_analytics_pages( $request ) {
		global $wpdb;

		$bar_id     = $request->get_param( 'bar_id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );
		$limit      = (int) $request->get_param( 'limit' ) ?: 20;

		if ( ! $bar_id ) {
			return new WP_REST_Response(
				array( 'error' => 'bar_id is required' ),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_announcement_analytics';

		// Build where clause
		$where = $wpdb->prepare( 'WHERE campaign_id = %d AND campaign_type = %s AND page_url IS NOT NULL', $bar_id, 'announcement' );

		if ( $start_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) >= %s', $start_date );
		}

		if ( $end_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) <= %s', $end_date );
		}

		// Get page performance data
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					page_url,
					page_type,
					COUNT(DISTINCT session_id) as unique_visitors,
					SUM(CASE WHEN event_type = 'view' THEN 1 ELSE 0 END) as views,
					SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks
				FROM {$table}
				{$where}
				GROUP BY page_url
				ORDER BY views DESC
				LIMIT %d",
				$limit
			)
		);

		// Format response
		$breakdown = array();
		foreach ( $results as $row ) {
			$views = (int) $row->views;
			$clicks = (int) $row->clicks;
			$ctr    = $views > 0 ? round( ( $clicks / $views ) * 100, 2 ) : 0;

			$breakdown[] = array(
				'url'              => $row->page_url,
				'page_type'        => $row->page_type,
				'unique_visitors'  => (int) $row->unique_visitors,
				'views'            => $views,
				'clicks'           => $clicks,
				'ctr'              => $ctr,
			);
		}

		return new WP_REST_Response( $breakdown );
	}

	/**
	 * Get country/geographic breakdown
	 *
	 * @param WP_REST_Request $request The request object
	 * @return WP_REST_Response
	 */
	public static function get_announcement_analytics_countries( $request ) {
		global $wpdb;

		$bar_id     = $request->get_param( 'bar_id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );
		$limit      = (int) $request->get_param( 'limit' ) ?: 20;

		if ( ! $bar_id ) {
			return new WP_REST_Response(
				array( 'error' => 'bar_id is required' ),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_announcement_analytics';

		// Build where clause
		$where = $wpdb->prepare( 'WHERE campaign_id = %d AND campaign_type = %s AND country IS NOT NULL AND country != %s', $bar_id, 'announcement', '' );

		if ( $start_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) >= %s', $start_date );
		}

		if ( $end_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) <= %s', $end_date );
		}

		// Get country data
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					country,
					country_code,
					COUNT(*) as total_events,
					SUM(CASE WHEN event_type = 'view' THEN 1 ELSE 0 END) as views,
					SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks
				FROM {$table}
				{$where}
				GROUP BY country
				ORDER BY total_events DESC
				LIMIT %d",
				$limit
			)
		);

		// Format response
		$breakdown = array();
		foreach ( $results as $row ) {
			$views = (int) $row->views;
			$clicks = (int) $row->clicks;
			$ctr    = $views > 0 ? round( ( $clicks / $views ) * 100, 2 ) : 0;

			$breakdown[] = array(
				'country'       => $row->country,
				'country_code'  => $row->country_code,
				'views'         => $views,
				'clicks'        => $clicks,
				'ctr'           => $ctr,
			);
		}

		return new WP_REST_Response( $breakdown );
	}

	/**
	 * Get time-series analytics data
	 *
	 * @param WP_REST_Request $request The request object
	 * @return WP_REST_Response
	 */
	public static function get_announcement_analytics_timeline( $request ) {
		global $wpdb;

		$bar_id     = $request->get_param( 'bar_id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );
		$granularity = $request->get_param( 'granularity' ) ?: 'daily';

		if ( ! $bar_id ) {
			return new WP_REST_Response(
				array( 'error' => 'bar_id is required' ),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_announcement_analytics';

		// Determine date format based on granularity
		switch ( $granularity ) {
			case 'weekly':
				$date_format = '%Y-W%v';
				break;
			case 'monthly':
				$date_format = '%Y-%m';
				break;
			case 'daily':
			default:
				$date_format = '%Y-%m-%d';
		}

		// Build where clause
		$where = $wpdb->prepare( 'WHERE campaign_id = %d AND campaign_type = %s', $bar_id, 'announcement' );

		if ( $start_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) >= %s', $start_date );
		}

		if ( $end_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) <= %s', $end_date );
		}

		// Get timeline data
		$results = $wpdb->get_results(
			"SELECT
				DATE_FORMAT(event_timestamp, '{$date_format}') as period,
				SUM(CASE WHEN event_type = 'view' THEN 1 ELSE 0 END) as views,
				SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks,
				SUM(CASE WHEN event_type = 'conversion' THEN 1 ELSE 0 END) as conversions
			FROM {$table}
			{$where}
			GROUP BY period
			ORDER BY period ASC"
		);

		// Format response
		$timeline = array();
		foreach ( $results as $row ) {
			$views = (int) $row->views;
			$clicks = (int) $row->clicks;
			$ctr    = $views > 0 ? round( ( $clicks / $views ) * 100, 2 ) : 0;

			$timeline[] = array(
				'period'       => $row->period,
				'views'        => $views,
				'clicks'       => $clicks,
				'conversions'  => (int) $row->conversions,
				'ctr'          => $ctr,
			);
		}

		return new WP_REST_Response( $timeline );
	}

	/**
	 * Export announcement analytics as CSV
	 *
	 * @param WP_REST_Request $request The request object
	 * @return WP_REST_Response
	 */
	public static function export_announcement_analytics( $request ) {
		global $wpdb;

		$bar_id     = $request->get_param( 'bar_id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );

		if ( ! $bar_id ) {
			return new WP_REST_Response(
				array( 'error' => 'bar_id is required' ),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_announcement_analytics';

		// Build where clause
		$where = $wpdb->prepare( 'WHERE campaign_id = %d AND campaign_type = %s', $bar_id, 'announcement' );

		if ( $start_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) >= %s', $start_date );
		}

		if ( $end_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) <= %s', $end_date );
		}

		// Get all events for export
		$results = $wpdb->get_results(
			"SELECT * FROM {$table} {$where} ORDER BY event_timestamp DESC LIMIT 10000"
		);

		if ( empty( $results ) ) {
			return new WP_REST_Response(
				array( 'error' => 'No data to export' ),
				404
			);
		}

		// Prepare CSV data
		$csv_data = array();
		$headers  = array(
			'Event ID',
			'Campaign ID',
			'Campaign Type',
			'Event Type',
			'Date',
			'Device',
			'Browser',
			'OS',
			'Country',
			'Page URL',
			'Page Type',
			'Session ID',
		);

		$csv_data[] = $headers;

		foreach ( $results as $row ) {
			$csv_data[] = array(
				$row->id,
				$row->campaign_id,
				$row->campaign_type,
				$row->event_type,
				$row->event_timestamp,
				$row->device_type,
				$row->browser,
				$row->os,
				$row->country,
				$row->page_url,
				$row->page_type,
				$row->session_id,
			);
		}

		// Generate CSV content
		$csv_content = '';
		foreach ( $csv_data as $row ) {
			$csv_content .= '"' . implode( '","', array_map( function( $item ) {
				return str_replace( '"', '""', $item ?? '' );
			}, $row ) ) . '"' . "\n";
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $csv_content,
				'count'   => count( $results ),
			)
		);
	}
}
