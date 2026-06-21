<?php
/**
 * Popup Campaign A/B Test REST API Endpoints
 *
 * Provides REST API endpoints for popup A/B test tracking, statistics, and winner determination.
 *
 * @package Hashbar\Pro\API
 */

namespace Hashbar\Pro\API;

use WP_REST_Response;
use WP_REST_Request;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PopupABTestAPI {

	/**
	 * Register A/B test REST API routes
	 */
	public static function register_routes() {
		// Track A/B test event - Public endpoint, no authentication required
		register_rest_route(
			'hashbar/v1',
			'/popup-ab-test/track',
			array(
				'methods'             => 'POST',
				'callback'            => [ self::class, 'track_event' ],
				'permission_callback' => '__return_true',
				'args'                => array(
					'popup_id'   => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
					'variant_id' => array(
						'required' => true,
						'type'     => 'string',
					),
					'event_type' => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							// Accept all event types that the analytics processor accepts
							$valid_types = array( 'view', 'impression', 'click', 'conversion', 'close', 'form_submit', 'cta', 'secondary', 'submit', 'interaction', 'submission' );
							return in_array( $param, $valid_types, true );
						},
					),
					'event_value' => array(
						'required' => false,
						'type'     => 'string',
					),
				),
			)
		);

		// Get A/B test statistics
		register_rest_route(
			'hashbar/v1',
			'/popup-ab-test/stats/(?P<popup_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_stats' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'popup_id' => array(
						'type'              => 'integer',
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Get A/B test winner recommendation
		register_rest_route(
			'hashbar/v1',
			'/popup-ab-test/winner/(?P<popup_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get_winner' ],
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
				'args'                => array(
					'popup_id' => array(
						'type'              => 'integer',
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Assign visitor to variant
		register_rest_route(
			'hashbar/v1',
			'/popup-ab-test/assign',
			array(
				'methods'             => 'POST',
				'callback'            => [ self::class, 'assign_variant' ],
				'permission_callback' => '__return_true',
				'args'                => array(
					'popup_id' => array(
						'required'          => true,
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
					'visitor_id' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);
	}

	/**
	 * Track A/B test event
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function track_event( $request ) {
		global $wpdb;

		try {
			$popup_id    = absint( $request->get_param( 'popup_id' ) );
			$variant_id  = sanitize_text_field( $request->get_param( 'variant_id' ) );
			$event_type  = sanitize_text_field( $request->get_param( 'event_type' ) );
			$event_value = $request->get_param( 'event_value' );
			if ( $event_value ) {
				$event_value = sanitize_text_field( $event_value );
			}

			// Verify popup exists and A/B test is enabled
			$post = get_post( $popup_id );
			if ( ! $post || $post->post_type !== 'wphash_popup' ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Popup campaign not found',
					),
					404
				);
			}

			$test_enabled = get_post_meta( $popup_id, '_wphash_popup_ab_enabled', true );
			if ( ! $test_enabled || $test_enabled === 'false' || $test_enabled === '0' ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => 'A/B test is not enabled for this popup',
					),
					400
				);
			}

			// Get session ID from request or generate one
			$session_id = sanitize_text_field( $request->get_param( 'session_id' ) ?: wp_generate_uuid4() );

			// Prepare event data
			$table = $wpdb->prefix . 'hashbar_popup_analytics';

			// Check if table exists
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Analytics table does not exist',
					),
					500
				);
			}

			// Check for duplicate: same campaign, session, event_type (only one per session)
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table}
					WHERE campaign_id = %d
					AND session_id = %s
					AND event_type = %s
					LIMIT 1",
					$popup_id,
					$session_id,
					$event_type
				)
			);

			if ( $existing ) {
				return new WP_REST_Response(
					array(
						'success' => true,
						'message' => 'Event already tracked (duplicate)',
					),
					200
				);
			}

			// Insert event - use campaign_id to match analytics processor schema
			$result = $wpdb->insert(
				$table,
				array(
					'campaign_id'   => $popup_id,
					'campaign_type' => 'popup',
					'variant_id'    => $variant_id,
					'event_type'    => $event_type,
					'session_id'    => $session_id,
					'ip_address'    => self::get_client_ip(),
					'device_type'   => sanitize_text_field( $request->get_param( 'device_type' ) ?: 'unknown' ),
					'page_url'      => esc_url_raw( $request->get_param( 'page_url' ) ?: '' ),
				)
			);

			if ( $result ) {
				return new WP_REST_Response(
					array(
						'success' => true,
						'message' => 'Event tracked successfully',
					),
					200
				);
			} else {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Failed to track event',
					),
					500
				);
			}
		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Get A/B test statistics
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function get_stats( $request ) {
		global $wpdb;

		try {
			$popup_id = absint( $request['popup_id'] );

			// Verify popup exists
			$post = get_post( $popup_id );
			if ( ! $post || $post->post_type !== 'wphash_popup' ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Popup campaign not found',
					),
					404
				);
			}

			$table = $wpdb->prefix . 'hashbar_popup_analytics';

			// Check if table exists
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
				return new WP_REST_Response(
					array(
						'success' => true,
						'data'    => array(
							'total_visitors'    => 0,
							'total_impressions' => 0,
							'total_clicks'      => 0,
							'total_conversions' => 0,
							'variants'          => array(),
							'winner'            => null,
						),
					),
					200
				);
			}

			// Get variant stats - use campaign_id to match analytics processor schema
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT
						COALESCE(variant_id, 'control') as variant_id,
						COUNT(DISTINCT session_id) as visitors,
						SUM(CASE WHEN event_type IN ('view', 'impression') THEN 1 ELSE 0 END) as impressions,
						SUM(CASE WHEN event_type IN ('click', 'interaction', 'cta', 'secondary', 'submit') THEN 1 ELSE 0 END) as clicks,
						SUM(CASE WHEN event_type IN ('conversion', 'submission', 'form_submit') THEN 1 ELSE 0 END) as conversions
					FROM {$table}
					WHERE campaign_id = %d AND campaign_type = 'popup'
					GROUP BY variant_id
					ORDER BY impressions DESC",
					$popup_id
				)
			);

			// Get variant names from popup meta
			$ab_variants   = get_post_meta( $popup_id, '_wphash_popup_ab_variants', true );
			$variant_names = array( 'control' => 'Control (Original)' );

			if ( is_array( $ab_variants ) ) {
				foreach ( $ab_variants as $variant ) {
					if ( ! empty( $variant['id'] ) && ! empty( $variant['name'] ) ) {
						$variant_names[ $variant['id'] ] = $variant['name'];
					}
				}
			}

			// Format variants data
			$variants           = array();
			$total_visitors     = 0;
			$total_impressions  = 0;
			$total_clicks       = 0;
			$total_conversions  = 0;
			$best_variant       = null;
			$best_conversion    = 0;

			foreach ( $results as $row ) {
				$visitors    = (int) $row->visitors;
				$impressions = (int) $row->impressions;
				$clicks      = (int) $row->clicks;
				$conversions = (int) $row->conversions;
				$ctr         = $impressions > 0 ? round( ( $clicks / $impressions ) * 100, 2 ) : 0;
				$conv_rate   = $impressions > 0 ? round( ( $conversions / $impressions ) * 100, 2 ) : 0;

				$variants[] = array(
					'variant_id'      => $row->variant_id,
					'variant_name'    => $variant_names[ $row->variant_id ] ?? 'Variant ' . $row->variant_id,
					'visitors'        => $visitors,
					'impressions'     => $impressions,
					'clicks'          => $clicks,
					'conversions'     => $conversions,
					'ctr'             => $ctr,
					'conversion_rate' => $conv_rate,
				);

				$total_visitors    += $visitors;
				$total_impressions += $impressions;
				$total_clicks      += $clicks;
				$total_conversions += $conversions;

				// Track best performing variant
				if ( $conv_rate > $best_conversion && $impressions >= 100 ) {
					$best_conversion = $conv_rate;
					$best_variant    = $row->variant_id;
				}
			}

			// Determine winner (need at least 100 impressions per variant for statistical significance)
			$winner = null;
			if ( $best_variant && count( $variants ) >= 2 ) {
				$winner = array(
					'variant_id'      => $best_variant,
					'variant_name'    => $variant_names[ $best_variant ] ?? 'Variant ' . $best_variant,
					'conversion_rate' => $best_conversion,
					'confidence'      => $total_impressions >= 1000 ? 'high' : ( $total_impressions >= 500 ? 'medium' : 'low' ),
				);
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => array(
						'total_visitors'    => $total_visitors,
						'total_impressions' => $total_impressions,
						'total_clicks'      => $total_clicks,
						'total_conversions' => $total_conversions,
						'variants'          => $variants,
						'winner'            => $winner,
					),
				),
				200
			);
		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Get A/B test winner recommendation
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function get_winner( $request ) {
		// Use the same stats method and extract winner
		$stats_response = self::get_stats( $request );
		$stats_data     = $stats_response->get_data();

		if ( ! $stats_data['success'] ) {
			return $stats_response;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $stats_data['data']['winner'],
			),
			200
		);
	}

	/**
	 * Assign visitor to a variant
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function assign_variant( $request ) {
		$popup_id   = absint( $request->get_param( 'popup_id' ) );
		$visitor_id = sanitize_text_field( $request->get_param( 'visitor_id' ) );

		// Verify popup exists and A/B test is enabled
		$post = get_post( $popup_id );
		if ( ! $post || $post->post_type !== 'wphash_popup' ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Popup campaign not found',
				),
				404
			);
		}

		$test_enabled = get_post_meta( $popup_id, '_wphash_popup_ab_enabled', true );
		if ( ! $test_enabled || $test_enabled === 'false' || $test_enabled === '0' ) {
			return new WP_REST_Response(
				array(
					'success'    => true,
					'variant_id' => 'control',
					'message'    => 'A/B test not enabled, using control',
				),
				200
			);
		}

		// Get variants
		$ab_variants = get_post_meta( $popup_id, '_wphash_popup_ab_variants', true );
		if ( ! is_array( $ab_variants ) || empty( $ab_variants ) ) {
			return new WP_REST_Response(
				array(
					'success'    => true,
					'variant_id' => 'control',
					'message'    => 'No variants configured, using control',
				),
				200
			);
		}

		// Check if visitor already has an assigned variant (stored in transient)
		$transient_key      = 'hashbar_popup_ab_' . $popup_id . '_' . md5( $visitor_id );
		$assigned_variant   = get_transient( $transient_key );

		if ( $assigned_variant ) {
			return new WP_REST_Response(
				array(
					'success'    => true,
					'variant_id' => $assigned_variant,
					'message'    => 'Existing assignment',
				),
				200
			);
		}

		// Assign variant based on traffic split
		$rand = mt_rand( 1, 100 );
		$cumulative = 0;
		$selected_variant = 'control';

		// Add control variant at the start
		$all_variants = array_merge(
			array( array( 'id' => 'control', 'traffic' => 100 - array_sum( array_column( $ab_variants, 'traffic' ) ) ) ),
			$ab_variants
		);

		foreach ( $all_variants as $variant ) {
			$cumulative += (int) ( $variant['traffic'] ?? 0 );
			if ( $rand <= $cumulative ) {
				$selected_variant = $variant['id'];
				break;
			}
		}

		// Store assignment (expires in 30 days)
		set_transient( $transient_key, $selected_variant, 30 * DAY_IN_SECONDS );

		return new WP_REST_Response(
			array(
				'success'    => true,
				'variant_id' => $selected_variant,
				'message'    => 'New assignment',
			),
			200
		);
	}

	/**
	 * Get client IP address
	 *
	 * @return string Client IP address
	 */
	private static function get_client_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$ip  = trim( $ips[0] );
		} else {
			$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		}

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = '0.0.0.0';
		}

		return $ip;
	}
}

// Register routes
add_action( 'rest_api_init', [ __NAMESPACE__ . '\PopupABTestAPI', 'register_routes' ] );

/**
 * Allow unauthenticated access to the popup A/B test tracking endpoint
 */
add_filter( 'rest_authentication_errors', function( $result ) {
	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$uri = $_SERVER['REQUEST_URI'];
		if ( strpos( $uri, '/hashbar/v1/popup-ab-test/track' ) !== false ||
			 strpos( $uri, '/hashbar/v1/popup-ab-test/assign' ) !== false ) {
			return true;
		}
	}
	return $result;
} );
