<?php
/**
 * Popup Campaign Analytics REST API
 * Provides endpoints for querying popup campaign analytics data
 *
 * @package Hashbar\Pro\API
 */

namespace Hashbar\Pro\API;

use WP_REST_Response;
use WP_REST_Request;

// Register REST API routes
add_action( 'rest_api_init', [ PopupAnalytics::class, 'register_routes' ] );

/**
 * Register all popup analytics endpoints
 */
class PopupAnalytics {

	public static function register_routes() {
		// Overview stats endpoint
		register_rest_route(
			'hashbar/v1',
			'/popup-analytics/overview',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_popup_analytics_overview' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Device breakdown endpoint
		register_rest_route(
			'hashbar/v1',
			'/popup-analytics/devices',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_popup_analytics_devices' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Page breakdown endpoint
		register_rest_route(
			'hashbar/v1',
			'/popup-analytics/pages',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_popup_analytics_pages' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Country breakdown endpoint
		register_rest_route(
			'hashbar/v1',
			'/popup-analytics/countries',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_popup_analytics_countries' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Timeline endpoint
		register_rest_route(
			'hashbar/v1',
			'/popup-analytics/timeline',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_popup_analytics_timeline' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// Export endpoint
		register_rest_route(
			'hashbar/v1',
			'/popup-analytics/export',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'export_popup_analytics' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);

		// A/B Test variants stats endpoint
		register_rest_route(
			'hashbar/v1',
			'/popup-analytics/variants',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_popup_ab_test_variants' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}

	/**
	 * Get overview statistics for popup analytics
	 *
	 * @param WP_REST_Request $request The request object
	 * @return WP_REST_Response
	 */
	public static function get_popup_analytics_overview( $request ) {
		global $wpdb;

		$popup_id   = $request->get_param( 'popup_id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );

		if ( ! $popup_id ) {
			return new WP_REST_Response(
				array( 'error' => 'popup_id is required' ),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_popup_analytics';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return new WP_REST_Response(
				array(
					'total_views'        => 0,
					'total_clicks'       => 0,
					'total_conversions'  => 0,
					'click_through_rate' => 0,
					'conversion_rate'    => 0,
					'unique_sessions'    => 0,
					'ab_test_enabled'    => false,
				)
			);
		}

		// Build query
		$where = $wpdb->prepare( 'WHERE campaign_id = %d AND campaign_type = %s', $popup_id, 'popup' );

		if ( $start_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) >= %s', $start_date );
		}

		if ( $end_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) <= %s', $end_date );
		}

		// Get view count (impressions)
		$views = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where} AND event_type = 'view'" );

		// Get click count
		$clicks = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where} AND event_type = 'click'" );

		// Get conversion count (form submissions)
		$conversions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where} AND event_type = 'conversion'" );

		// Get unique sessions
		$unique_sessions = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$table} {$where}" );

		// Calculate rates
		$ctr  = $views > 0 ? round( ( $clicks / $views ) * 100, 2 ) : 0;
		$conv = $views > 0 ? round( ( $conversions / $views ) * 100, 2 ) : 0;

		// Check if popup has A/B testing enabled
		$ab_test_enabled = get_post_meta( $popup_id, '_wphash_popup_ab_enabled', true );
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
	public static function get_popup_analytics_devices( $request ) {
		global $wpdb;

		$popup_id   = $request->get_param( 'popup_id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );

		if ( ! $popup_id ) {
			return new WP_REST_Response(
				array( 'error' => 'popup_id is required' ),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_popup_analytics';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return new WP_REST_Response( array() );
		}

		// Build where clause
		$where = $wpdb->prepare( 'WHERE campaign_id = %d AND campaign_type = %s', $popup_id, 'popup' );

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
			$views  = (int) $row->views;
			$clicks = (int) $row->clicks;
			$ctr    = $views > 0 ? round( ( $clicks / $views ) * 100, 2 ) : 0;

			$breakdown[] = array(
				'device' => $row->device_type,
				'views'  => $views,
				'clicks' => $clicks,
				'ctr'    => $ctr,
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
	public static function get_popup_analytics_pages( $request ) {
		global $wpdb;

		$popup_id   = $request->get_param( 'popup_id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );
		$limit      = (int) $request->get_param( 'limit' ) ?: 20;

		if ( ! $popup_id ) {
			return new WP_REST_Response(
				array( 'error' => 'popup_id is required' ),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_popup_analytics';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return new WP_REST_Response( array() );
		}

		// Build where clause
		$where = $wpdb->prepare( 'WHERE campaign_id = %d AND campaign_type = %s AND page_url IS NOT NULL', $popup_id, 'popup' );

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
			$views  = (int) $row->views;
			$clicks = (int) $row->clicks;
			$ctr    = $views > 0 ? round( ( $clicks / $views ) * 100, 2 ) : 0;

			$breakdown[] = array(
				'url'             => $row->page_url,
				'page_type'       => $row->page_type,
				'unique_visitors' => (int) $row->unique_visitors,
				'views'           => $views,
				'clicks'          => $clicks,
				'ctr'             => $ctr,
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
	public static function get_popup_analytics_countries( $request ) {
		global $wpdb;

		$popup_id   = $request->get_param( 'popup_id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );
		$limit      = (int) $request->get_param( 'limit' ) ?: 20;

		if ( ! $popup_id ) {
			return new WP_REST_Response(
				array( 'error' => 'popup_id is required' ),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_popup_analytics';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return new WP_REST_Response( array() );
		}

		// Build where clause
		$where = $wpdb->prepare( 'WHERE campaign_id = %d AND campaign_type = %s AND country IS NOT NULL AND country != %s', $popup_id, 'popup', '' );

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
			$views  = (int) $row->views;
			$clicks = (int) $row->clicks;
			$ctr    = $views > 0 ? round( ( $clicks / $views ) * 100, 2 ) : 0;

			$breakdown[] = array(
				'country'      => $row->country,
				'country_code' => $row->country_code,
				'views'        => $views,
				'clicks'       => $clicks,
				'ctr'          => $ctr,
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
	public static function get_popup_analytics_timeline( $request ) {
		global $wpdb;

		$popup_id    = $request->get_param( 'popup_id' );
		$start_date  = $request->get_param( 'start_date' );
		$end_date    = $request->get_param( 'end_date' );
		$granularity = $request->get_param( 'granularity' ) ?: 'daily';

		if ( ! $popup_id ) {
			return new WP_REST_Response(
				array( 'error' => 'popup_id is required' ),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_popup_analytics';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return new WP_REST_Response( array() );
		}

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
		$where = $wpdb->prepare( 'WHERE campaign_id = %d AND campaign_type = %s', $popup_id, 'popup' );

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
			$views  = (int) $row->views;
			$clicks = (int) $row->clicks;
			$ctr    = $views > 0 ? round( ( $clicks / $views ) * 100, 2 ) : 0;

			$timeline[] = array(
				'period'      => $row->period,
				'views'       => $views,
				'clicks'      => $clicks,
				'conversions' => (int) $row->conversions,
				'ctr'         => $ctr,
			);
		}

		return new WP_REST_Response( $timeline );
	}

	/**
	 * Export popup analytics as CSV
	 *
	 * @param WP_REST_Request $request The request object
	 * @return WP_REST_Response
	 */
	public static function export_popup_analytics( $request ) {
		global $wpdb;

		$popup_id   = $request->get_param( 'popup_id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );

		if ( ! $popup_id ) {
			return new WP_REST_Response(
				array( 'error' => 'popup_id is required' ),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_popup_analytics';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return new WP_REST_Response(
				array( 'error' => 'No data to export' ),
				404
			);
		}

		// Build where clause
		$where = $wpdb->prepare( 'WHERE campaign_id = %d AND campaign_type = %s', $popup_id, 'popup' );

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
			'Variant ID',
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
				$row->variant_id ?? '',
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

	/**
	 * Get A/B test variant statistics
	 *
	 * @param WP_REST_Request $request The request object
	 * @return WP_REST_Response
	 */
	public static function get_popup_ab_test_variants( $request ) {
		global $wpdb;

		$popup_id   = $request->get_param( 'popup_id' );
		$start_date = $request->get_param( 'start_date' );
		$end_date   = $request->get_param( 'end_date' );

		if ( ! $popup_id ) {
			return new WP_REST_Response(
				array( 'error' => 'popup_id is required' ),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_popup_analytics';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			return new WP_REST_Response( array( 'variants' => array() ) );
		}

		// Build where clause
		$where = $wpdb->prepare( 'WHERE campaign_id = %d AND campaign_type = %s', $popup_id, 'popup' );

		if ( $start_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) >= %s', $start_date );
		}

		if ( $end_date ) {
			$where .= $wpdb->prepare( ' AND DATE(event_timestamp) <= %s', $end_date );
		}

		// Get variant stats
		$results = $wpdb->get_results(
			"SELECT
				COALESCE(variant_id, 'control') as variant_id,
				SUM(CASE WHEN event_type = 'view' THEN 1 ELSE 0 END) as impressions,
				SUM(CASE WHEN event_type = 'click' THEN 1 ELSE 0 END) as clicks,
				SUM(CASE WHEN event_type = 'conversion' THEN 1 ELSE 0 END) as conversions
			FROM {$table}
			{$where}
			GROUP BY variant_id
			ORDER BY impressions DESC"
		);

		// Get variant names from popup meta
		$ab_variants = get_post_meta( $popup_id, '_wphash_popup_ab_variants', true );
		$variant_names = array( 'control' => 'Control (Original)' );

		if ( is_array( $ab_variants ) ) {
			foreach ( $ab_variants as $variant ) {
				if ( ! empty( $variant['id'] ) && ! empty( $variant['name'] ) ) {
					$variant_names[ $variant['id'] ] = $variant['name'];
				}
			}
		}

		// Format response
		$variants = array();
		foreach ( $results as $row ) {
			$impressions = (int) $row->impressions;
			$clicks      = (int) $row->clicks;
			$conversions = (int) $row->conversions;
			$ctr         = $impressions > 0 ? round( ( $clicks / $impressions ) * 100, 2 ) : 0;
			$conv_rate   = $impressions > 0 ? round( ( $conversions / $impressions ) * 100, 2 ) : 0;

			$variants[] = array(
				'variant_id'      => $row->variant_id,
				'variant_name'    => $variant_names[ $row->variant_id ] ?? 'Variant ' . $row->variant_id,
				'impressions'     => $impressions,
				'clicks'          => $clicks,
				'conversions'     => $conversions,
				'ctr'             => $ctr,
				'conversion_rate' => $conv_rate,
			);
		}

		return new WP_REST_Response( array( 'variants' => $variants ) );
	}
}
