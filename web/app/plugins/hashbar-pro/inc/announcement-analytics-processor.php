<?php
/**
 * Announcement Analytics Processor
 * Handles data validation, storage, and processing for announcement bar analytics
 * Separate from notification bar analytics (analytical-store.php)
 *
 * @package Hashbar\Pro\AnnouncementAnalytics
 */

namespace Hashbar\Pro\AnnouncementAnalytics;

/**
 * Announcement Analytics Processor Class
 * Handles incoming analytics events and stores them in the database
 */
class Analytics_Processor {

	/**
	 * Singleton instance
	 */
	private static $_instance = null;

	/**
	 * Get singleton instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor - register REST API routes
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes for announcement analytics
	 */
	public function register_routes() {
		// Batch tracking endpoint - no authentication required for frontend tracking
		register_rest_route(
			'hashbar/v1',
			'/announcement-analytics/batch',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'track_batch' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Handle batch analytics tracking
	 *
	 * @param WP_REST_Request $request The REST request object
	 * @return WP_REST_Response
	 */
	public function track_batch( $request ) {
		global $wpdb;

		$events = $request->get_param( 'events' );

		// Validate input
		if ( ! is_array( $events ) || empty( $events ) ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'No events provided',
				),
				400
			);
		}

		// Limit batch size to prevent abuse
		if ( count( $events ) > 50 ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Too many events in batch',
				),
				400
			);
		}

		$table = $wpdb->prefix . 'hashbar_announcement_analytics';

		// Check if table exists, create if not
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
			// Try to create the table
			if ( class_exists( '\Hashbar\Pro\DatabaseInstaller\Database_Installer' ) ) {
				// Reset the option to force table creation
				delete_option( 'hthb_announcement_analyticstbl_exist' );
				\Hashbar\Pro\DatabaseInstaller\Database_Installer::create_announcement_analytics_table();
			}

			// Check again after attempting to create
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) !== $table ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Analytics table does not exist and could not be created',
					),
					500
				);
			}
		}

		$inserted  = 0;
		$failed    = 0;

		foreach ( $events as $event ) {
			$data = $this->prepare_event_data( $event );
			if ( $data ) {
				$result = $wpdb->insert( $table, $data );
				if ( $result ) {
					$inserted++;
				} else {
					$failed++;
				}
			} else {
				$failed++;
			}
		}

		return new \WP_REST_Response(
			array(
				'success'  => true,
				'recorded' => $inserted,
				'failed'   => $failed,
			),
			200
		);
	}

	/**
	 * Prepare and validate event data before storage
	 *
	 * @param array $event The raw event data
	 * @return array|false Prepared data or false if invalid
	 */
	private function prepare_event_data( $event ) {
		// Validate required fields
		if ( empty( $event['campaign_id'] ) || empty( $event['event_type'] ) ) {
			return false;
		}

		// Get country from IP
		$country_data = $this->get_country_from_ip( $this->get_client_ip() );

		// Determine page ID from URL
		$page_id = $this->get_page_id_from_url( $event['page_url'] ?? '' );

		// Determine page type
		$page_type = $this->get_page_type_from_url( $event['page_url'] ?? '' );

		return array(
			'campaign_id'      => absint( $event['campaign_id'] ),
			'campaign_type'    => sanitize_text_field( $event['campaign_type'] ?? 'announcement' ),
			'variant_id'       => sanitize_text_field( $event['variant_id'] ?? '' ),
			'event_type'       => sanitize_text_field( $event['event_type'] ),
			'session_id'       => sanitize_text_field( $event['session_id'] ),
			'ip_address'       => $this->get_client_ip(),
			'country'          => $country_data ? sanitize_text_field( $country_data['country'] ?? '' ) : null,
			'country_code'     => $country_data ? sanitize_text_field( $country_data['country_code'] ?? '' ) : null,
			'device_type'      => sanitize_text_field( $event['device_type'] ?? 'unknown' ),
			'browser'          => sanitize_text_field( $event['browser'] ?? '' ),
			'os'               => sanitize_text_field( $event['os'] ?? '' ),
			'user_agent'       => sanitize_text_field( substr( $event['user_agent'] ?? '', 0, 500 ) ),
			'page_url'         => esc_url_raw( $event['page_url'] ?? '' ),
			'page_id'          => $page_id,
			'page_type'        => $page_type,
			'referrer_url'     => esc_url_raw( $event['referrer_url'] ?? '' ),
			'conversion_value' => isset( $event['conversion_value'] ) ? floatval( $event['conversion_value'] ) : null,
			'user_id'          => get_current_user_id() ?: null,
		);
	}

	/**
	 * Check if IP address is private or localhost
	 *
	 * @param string $ip The IP address
	 * @return bool True if IP is private or localhost
	 */
	private function is_private_ip( $ip ) {
		// IPv4 localhost
		if ( $ip === '127.0.0.1' ) {
			return true;
		}

		// IPv6 localhost
		if ( $ip === '::1' || $ip === '::ffff:127.0.0.1' ) {
			return true;
		}

		// Check for IPv4 private ranges
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			// Private ranges: 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16
			return ip2long( $ip ) !== false && (
				( ip2long( $ip ) >= ip2long( '10.0.0.0' ) && ip2long( $ip ) <= ip2long( '10.255.255.255' ) ) ||
				( ip2long( $ip ) >= ip2long( '172.16.0.0' ) && ip2long( $ip ) <= ip2long( '172.31.255.255' ) ) ||
				( ip2long( $ip ) >= ip2long( '192.168.0.0' ) && ip2long( $ip ) <= ip2long( '192.168.255.255' ) ) ||
				( ip2long( $ip ) >= ip2long( '127.0.0.0' ) && ip2long( $ip ) <= ip2long( '127.255.255.255' ) )
			);
		}

		// Check for IPv6 private ranges
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			// Private ranges: fc00::/7, fe80::/10
			$hex = bin2hex( inet_pton( $ip ) );
			return strpos( $hex, 'fc' ) === 0 || strpos( $hex, 'fe80' ) === 0;
		}

		return false;
	}

	/**
	 * Get country information from IP address using ip-api.com
	 *
	 * @param string $ip The IP address
	 * @return array|null Country info with 'country' and 'country_code' keys, or null if not available
	 */
	private function get_country_from_ip( $ip ) {
		// Validate IP format
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return null;
		}

		// Check for test country override via URL parameter (localhost only)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && $this->is_private_ip( $ip ) ) {
			if ( isset( $_GET['hashbar_test_country'] ) ) {
				$test_country = sanitize_text_field( $_GET['hashbar_test_country'] );
				if ( strlen( $test_country ) === 2 ) {
					// Map country codes to country names
					$country_names = array(
						'US' => 'United States',
						'GB' => 'United Kingdom',
						'DE' => 'Germany',
						'FR' => 'France',
						'CA' => 'Canada',
						'AU' => 'Australia',
						'BD' => 'Bangladesh',
					);
					$country_name = $country_names[ strtoupper( $test_country ) ] ?? ucfirst( $test_country );
					return array(
						'country' => $country_name,
						'country_code' => strtoupper( $test_country ),
					);
				}
			}
		}

		// Try to get cached country data first
		$cache_key = 'hashbar_geo_' . md5( $ip );
		$cached     = wp_cache_get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// Fetch from API - NOTE: Call without IP to use visitor's actual IP (ip-api.com automatically detects visitor IP)
		$response = wp_remote_get(
			'http://ip-api.com/json/',
			array(
				'timeout'   => 5,
				'sslverify' => false,
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['country'] ) ) {
			return null;
		}

		$result = array(
			'country'      => $data['country'] ?? '',
			'country_code' => $data['countryCode'] ?? '',
		);

		// Cache for 24 hours
		wp_cache_set( $cache_key, $result, '', DAY_IN_SECONDS );

		return $result;
	}

	/**
	 * Get page ID from URL
	 *
	 * @param string $url The page URL
	 * @return int|null Page ID or null
	 */
	private function get_page_id_from_url( $url ) {
		if ( empty( $url ) ) {
			return null;
		}

		$page_id = url_to_postid( $url );
		return $page_id ? (int) $page_id : null;
	}

	/**
	 * Determine page type from URL
	 *
	 * @param string $url The page URL
	 * @return string Page type
	 */
	private function get_page_type_from_url( $url ) {
		if ( empty( $url ) ) {
			return 'other';
		}

		$page_id = $this->get_page_id_from_url( $url );
		if ( ! $page_id ) {
			return 'other';
		}

		$post_type = get_post_type( $page_id );

		if ( $page_id === (int) get_option( 'page_on_front' ) ) {
			return 'home';
		} elseif ( 'post' === $post_type ) {
			return 'post';
		} elseif ( 'page' === $post_type ) {
			return 'page';
		} elseif ( 'product' === $post_type ) {
			return 'product';
		} else {
			return 'other';
		}
	}

	/**
	 * Get client IP address
	 *
	 * @return string Client IP address
	 */
	private function get_client_ip() {
		// Check for IP from share internet
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		// Check for IP passed from proxy
		elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// Handle multiple IPs (take the first one)
			$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			$ip  = trim( $ips[0] );
		}
		// Check for remote address
		else {
			$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		}

		// Validate IP
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = '0.0.0.0';
		}

		return $ip;
	}
}

// Initialize the processor
Analytics_Processor::instance();

/**
 * Allow unauthenticated access to the announcement analytics batch endpoint
 * This prevents nonce validation errors for public tracking (same as A/B test tracking)
 */
add_filter( 'rest_authentication_errors', function( $result ) {
	// If this is a request to our tracking endpoint, allow it without authentication
	if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/hashbar/v1/announcement-analytics/batch' ) !== false ) {
		// Allow the request to proceed without authentication
		return true;
	}
	return $result;
} );
