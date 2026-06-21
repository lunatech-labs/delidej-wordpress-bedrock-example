<?php
namespace Hashbar\Pro\API;

use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Request;
use Exception;

/**
 * A/B Test REST API Endpoints
 *
 * Provides REST API endpoints for A/B test tracking, statistics, and winner determination.
 *
 * @package HashBar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include required WordPress REST API classes
if ( ! class_exists( 'WP_REST_Response' ) ) {
	require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-response.php';
	require_once ABSPATH . 'wp-includes/rest-api.php';
}

class ABTestAPI {

    /**
     * Register A/B test REST API routes
     */
    public static function register_routes() {
        // Track A/B test event - Public endpoint, no authentication required
        register_rest_route(
            'hashbar/v1',
            '/ab-test/track',
            array(
                'methods'             => 'POST',
                'callback'            => [ self::class, 'track_event' ],
                'permission_callback' => function() {
                    // Allow public access for tracking (no auth required)
                    return true;
                },
                'args'                => array(
                    'bar_id'     => array(
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
                            return in_array( $param, array( 'impression', 'click', 'conversion' ), true );
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
            '/ab-test/stats/(?P<bar_id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_stats' ],
                'permission_callback' => function() {
                    return current_user_can( 'manage_options' );
                },
                'args'                => array(
                    'bar_id' => array(
                        'type'     => 'integer',
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
            '/ab-test/winner/(?P<bar_id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_winner' ],
                'permission_callback' => function() {
                    return current_user_can( 'manage_options' );
                },
                'args'                => array(
                    'bar_id' => array(
                        'type'     => 'integer',
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param );
                        },
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
        try {
            $bar_id     = absint( $request->get_param( 'bar_id' ) );
            $variant_id = sanitize_text_field( $request->get_param( 'variant_id' ) );
            $event_type = sanitize_text_field( $request->get_param( 'event_type' ) );
            $event_value = $request->get_param( 'event_value' );
            if ( $event_value ) {
                $event_value = sanitize_text_field( $event_value );
            }

            // Verify bar exists and A/B test is enabled
            $post = get_post( $bar_id );
            if ( ! $post || $post->post_type !== 'wphash_announcement' ) {
                return new WP_REST_Response(
                    array(
                        'success' => false,
                        'message' => esc_html__( 'Announcement bar not found', 'hashbar' ),
                    ),
                    404
                );
            }

            $test_enabled = get_post_meta( $bar_id, '_wphash_ab_test_enabled', true );
            if ( ! $test_enabled || $test_enabled === 'false' || $test_enabled === '0' ) {
                return new WP_REST_Response(
                    array(
                        'success' => false,
                        'message' => esc_html__( 'A/B test is not enabled for this bar', 'hashbar' ),
                    ),
                    400
                );
            }

            // Get visitor identifier
            $visitor = \Hashbar\Pro\ABTest\AB_Test_Assignment::get_visitor_id();

            // Track event (using namespaced function)
            if ( function_exists( '\Hashbar\Pro\ABTest\hashbar_ab_test_track_event_internal' ) ) {
                $tracked = \Hashbar\Pro\ABTest\hashbar_ab_test_track_event_internal( $bar_id, $variant_id, $visitor['id'], $event_type, $event_value );
            } else {
                // Fallback if function not loaded
                $tracked = false;
            }

            if ( $tracked ) {
                return new WP_REST_Response(
                    array(
                        'success' => true,
                        'message' => esc_html__( 'Event tracked successfully', 'hashbar' ),
                    ),
                    200
                );
            } else {
                return new WP_REST_Response(
                    array(
                        'success' => false,
                        'message' => esc_html__( 'Failed to track event', 'hashbar' ),
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
        try {
            $bar_id = absint( $request['bar_id'] );

            // Verify bar exists
            $post = get_post( $bar_id );
            if ( ! $post || $post->post_type !== 'wphash_announcement' ) {
                return new WP_REST_Response(
                    array(
                        'success' => false,
                        'message' => esc_html__( 'Announcement bar not found', 'hashbar' ),
                    ),
                    404
                );
            }

            // Get statistics (will be implemented in ab-test-statistics.php)
            $stats = \Hashbar\Pro\ABTest\Statistics::get_statistics( $bar_id );

            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data'    => $stats,
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
        try {
            $bar_id = absint( $request['bar_id'] );

            // Verify bar exists
            $post = get_post( $bar_id );
            if ( ! $post || $post->post_type !== 'wphash_announcement' ) {
                return new WP_REST_Response(
                    array(
                        'success' => false,
                        'message' => esc_html__( 'Announcement bar not found', 'hashbar' ),
                    ),
                    404
                );
            }

            // Get winner recommendation using namespaced function
            if ( function_exists( '\Hashbar\Pro\hashbar_ab_test_determine_winner' ) ) {
                $winner = \Hashbar\Pro\hashbar_ab_test_determine_winner( $bar_id );
            } else if ( function_exists( 'hashbar_ab_test_determine_winner' ) ) {
                // Fallback to global function
                $winner = hashbar_ab_test_determine_winner( $bar_id );
            } else {
                return new WP_REST_Response(
                    array(
                        'success' => false,
                        'message' => esc_html__( 'Winner determination function not available', 'hashbar' ),
                    ),
                    500
                );
            }

            return new WP_REST_Response(
                array(
                    'success' => true,
                    'data'    => $winner,
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
}

// Register routes with default priority (10)
add_action( 'rest_api_init', [ ABTestAPI::class, 'register_routes' ] );

/**
 * Allow unauthenticated access to the A/B test tracking endpoint
 * This prevents nonce validation errors for public tracking
 */
add_filter( 'rest_authentication_errors', function( $result ) {
	// If this is a request to our tracking endpoint, allow it without authentication
	if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/hashbar/v1/ab-test/track' ) !== false ) {
		// Allow the request to proceed without authentication
		return true;
	}
	return $result;
} );
