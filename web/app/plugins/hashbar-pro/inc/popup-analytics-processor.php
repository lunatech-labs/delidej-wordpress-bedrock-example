<?php
/**
 * Popup Campaign Analytics Processor
 * Handles data validation, storage, and processing for popup campaign analytics
 *
 * @package Hashbar\Pro\PopupAnalytics
 */

namespace Hashbar\Pro\PopupAnalytics;

/**
 * Popup Analytics Processor Class
 * Handles incoming analytics events and stores them in the database
 */
class Popup_Analytics_Processor {

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
	 * Register REST API routes for popup analytics
	 */
	public function register_routes() {
		// Batch tracking endpoint - no authentication required for frontend tracking
		register_rest_route(
			'hashbar/v1',
			'/popup-analytics/batch',
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

		// Try to get events from request params first
		$events = $request->get_param( 'events' );

		// If not found, try to parse from raw body (for sendBeacon)
		if ( empty( $events ) ) {
			$body = $request->get_body();
			if ( ! empty( $body ) ) {
				$data = json_decode( $body, true );
				if ( is_array( $data ) && isset( $data['events'] ) ) {
					$events = $data['events'];
				}
			}
		}

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

		$table = $wpdb->prefix . 'hashbar_popup_analytics';

		// Ensure table exists with correct schema
		$this->ensure_table_exists();

		$inserted  = 0;
		$failed    = 0;
		$duplicate = 0;

		foreach ( $events as $event ) {
			$data = $this->prepare_event_data( $event );
			if ( $data ) {
				// Check for duplicate: same campaign, session, event_type (only one per session)
				$existing = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT id FROM {$table}
						WHERE campaign_id = %d
						AND session_id = %s
						AND event_type = %s
						LIMIT 1",
						$data['campaign_id'],
						$data['session_id'],
						$data['event_type']
					)
				);

				if ( $existing ) {
					$duplicate++;
					continue; // Skip duplicate
				}

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
				'success'    => true,
				'recorded'   => $inserted,
				'failed'     => $failed,
				'duplicates' => $duplicate,
			),
			200
		);
	}

	/**
	 * Ensure analytics table exists with correct schema
	 */
	private function ensure_table_exists() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'hashbar_popup_analytics';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name;

		if ( ! $table_exists ) {
			$this->create_analytics_table();
		} else {
			// Check if we need to update schema (from ENUM to VARCHAR)
			$schema_version = get_option( 'hashbar_popup_analytics_schema_version', '1.0' );
			if ( version_compare( $schema_version, '1.1', '<' ) ) {
				// Drop and recreate with new schema (no data to preserve yet)
				$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
				$this->create_analytics_table();
				update_option( 'hashbar_popup_analytics_schema_version', '1.1' );
			}
		}
	}

	/**
	 * Create popup analytics table
	 */
	private function create_analytics_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'hashbar_popup_analytics';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id BIGINT(20) UNSIGNED NOT NULL,
			campaign_type VARCHAR(50) DEFAULT 'popup',
			variant_id VARCHAR(50) DEFAULT NULL,
			event_type VARCHAR(50) NOT NULL,
			event_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
			session_id VARCHAR(64) DEFAULT NULL,
			ip_address VARCHAR(45) DEFAULT NULL,
			country VARCHAR(100) DEFAULT NULL,
			country_code CHAR(2) DEFAULT NULL,
			device_type VARCHAR(20) DEFAULT 'unknown',
			browser VARCHAR(50) DEFAULT NULL,
			os VARCHAR(50) DEFAULT NULL,
			user_agent TEXT DEFAULT NULL,
			page_url TEXT DEFAULT NULL,
			page_id BIGINT(20) UNSIGNED DEFAULT NULL,
			page_type VARCHAR(50) DEFAULT NULL,
			referrer_url TEXT DEFAULT NULL,
			conversion_value DECIMAL(10,2) DEFAULT NULL,
			user_id BIGINT(20) UNSIGNED DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_campaign (campaign_id),
			INDEX idx_campaign_event (campaign_id, event_type, event_timestamp),
			INDEX idx_campaign_type (campaign_type, event_type),
			INDEX idx_session (session_id),
			INDEX idx_device (device_type),
			INDEX idx_country (country_code),
			INDEX idx_timestamp (event_timestamp),
			INDEX idx_variant (variant_id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
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

		// Validate event type (allow common event types)
		$valid_event_types = array( 'view', 'impression', 'click', 'conversion', 'close', 'form_submit', 'cta', 'secondary', 'submit' );
		$event_type = sanitize_text_field( $event['event_type'] );
		if ( ! in_array( $event_type, $valid_event_types, true ) ) {
			// Map unknown types to 'click' if they look like click events
			if ( in_array( $event_type, array( 'cta', 'secondary', 'submit' ), true ) ) {
				$event_type = 'click';
			} else {
				return false;
			}
		}

		// Get country from IP
		$country_data = $this->get_country_from_ip( $this->get_client_ip() );

		// Determine page ID from URL
		$page_id = $this->get_page_id_from_url( $event['page_url'] ?? '' );

		// Determine page type
		$page_type = $this->get_page_type_from_url( $event['page_url'] ?? '' );

		return array(
			'campaign_id'      => absint( $event['campaign_id'] ),
			'campaign_type'    => 'popup',
			'variant_id'       => ! empty( $event['variant_id'] ) ? sanitize_text_field( $event['variant_id'] ) : null,
			'event_type'       => $event_type,
			'session_id'       => ! empty( $event['session_id'] ) ? sanitize_text_field( $event['session_id'] ) : $this->generate_session_id(),
			'ip_address'       => $this->get_client_ip(),
			'country'          => $country_data ? sanitize_text_field( $country_data['country'] ?? '' ) : null,
			'country_code'     => $country_data ? sanitize_text_field( $country_data['country_code'] ?? '' ) : null,
			'device_type'      => ! empty( $event['device_type'] ) ? sanitize_text_field( $event['device_type'] ) : 'unknown',
			'browser'          => ! empty( $event['browser'] ) ? sanitize_text_field( $event['browser'] ) : null,
			'os'               => ! empty( $event['os'] ) ? sanitize_text_field( $event['os'] ) : null,
			'user_agent'       => ! empty( $event['user_agent'] ) ? sanitize_text_field( substr( $event['user_agent'], 0, 500 ) ) : null,
			'page_url'         => ! empty( $event['page_url'] ) ? esc_url_raw( $event['page_url'] ) : null,
			'page_id'          => $page_id,
			'page_type'        => $page_type,
			'referrer_url'     => ! empty( $event['referrer_url'] ) ? esc_url_raw( $event['referrer_url'] ) : null,
			'conversion_value' => isset( $event['conversion_value'] ) ? floatval( $event['conversion_value'] ) : null,
			'user_id'          => get_current_user_id() ?: null,
		);
	}

	/**
	 * Generate a unique session ID
	 *
	 * @return string Session ID
	 */
	private function generate_session_id() {
		return wp_generate_uuid4();
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
			return ip2long( $ip ) !== false && (
				( ip2long( $ip ) >= ip2long( '10.0.0.0' ) && ip2long( $ip ) <= ip2long( '10.255.255.255' ) ) ||
				( ip2long( $ip ) >= ip2long( '172.16.0.0' ) && ip2long( $ip ) <= ip2long( '172.31.255.255' ) ) ||
				( ip2long( $ip ) >= ip2long( '192.168.0.0' ) && ip2long( $ip ) <= ip2long( '192.168.255.255' ) ) ||
				( ip2long( $ip ) >= ip2long( '127.0.0.0' ) && ip2long( $ip ) <= ip2long( '127.255.255.255' ) )
			);
		}

		// Check for IPv6 private ranges
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
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
						'country'      => $country_name,
						'country_code' => strtoupper( $test_country ),
					);
				}
			}
		}

		// Try to get cached country data first
		$cache_key = 'hashbar_popup_geo_' . md5( $ip );
		$cached    = wp_cache_get( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// Fetch from API
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
Popup_Analytics_Processor::instance();

/**
 * Allow unauthenticated access to the popup analytics batch endpoint
 * This prevents nonce validation errors for public tracking
 */
add_filter( 'rest_authentication_errors', function( $result ) {
	// If this is a request to our tracking endpoint, allow it without authentication
	if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/hashbar/v1/popup-analytics/batch' ) !== false ) {
		// Allow the request to proceed without authentication
		return true;
	}
	return $result;
} );
