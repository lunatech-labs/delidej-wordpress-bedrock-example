<?php
namespace Hashbar\Pro\ABTest;

/**
 * A/B Test Variant Assignment
 *
 * Handles consistent variant assignment for visitors using
 * cookie-based (guests) and user ID-based (logged-in) methods.
 *
 * @package HashBar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A/B Test Assignment Manager
 */
class AB_Test_Assignment {

	/**
	 * Get visitor identifier
	 *
	 * Returns user ID for logged-in users, or generates/retrieves cookie ID for guests.
	 *
	 * @return array Array with 'id' and 'type' keys.
	 */
	public static function get_visitor_id() {
		// For logged-in users, use user ID
		if ( is_user_logged_in() ) {
			return array(
				'id'   => (string) get_current_user_id(),
				'type' => 'user_id',
			);
		}

		// For guests, use cookie-based identifier
		$cookie_name = 'hashbar_visitor_id';
		$visitor_id  = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : '';

		// Generate new visitor ID if not exists
		if ( empty( $visitor_id ) ) {
			$visitor_id = self::generate_visitor_id();
			// Set cookie (expires in 1 year) only if headers not yet sent
			if ( ! headers_sent() ) {
				setcookie( $cookie_name, $visitor_id, time() + YEAR_IN_SECONDS, '/', '', is_ssl(), true );
			}
			$_COOKIE[ $cookie_name ] = $visitor_id; // Set in current request too
		}

		return array(
			'id'   => $visitor_id,
			'type' => 'cookie',
		);
	}

	/**
	 * Generate unique visitor ID
	 *
	 * @return string Unique visitor identifier
	 */
	private static function generate_visitor_id() {
		// Use IP + User Agent + timestamp for uniqueness
		$ip         = self::get_client_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$seed       = $ip . $user_agent . time() . wp_rand( 1000, 9999 );

		return 'visitor_' . md5( $seed );
	}

	/**
	 * Get client IP address
	 *
	 * @return string Client IP address
	 */
	private static function get_client_ip() {
		// Check for IP from shared internet
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		}
		// Check for IP passed from proxy
		elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// Handle multiple IPs (take the first one)
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip  = trim( $ips[0] );
		}
		// Check for remote address
		else {
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		}

		// Validate IP
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = '0.0.0.0';
		}

		return $ip;
	}

	/**
	 * Assign variant to visitor using consistent hashing
	 *
	 * @param int   $bar_id The announcement bar ID.
	 * @param array $variants Array of variant objects with 'id' key.
	 * @param array $traffic_splits Array of traffic split percentages (must sum to 100).
	 * @return string|false Variant ID or false on error.
	 */
	public static function assign_variant( $bar_id, $variants, $traffic_splits ) {
		if ( empty( $variants ) || empty( $traffic_splits ) ) {
			return false;
		}

		// Normalize traffic splits to sum to 100
		$total_split = array_sum( $traffic_splits );
		if ( $total_split === 0 ) {
			return false;
		}

		// Normalize if not exactly 100
		if ( $total_split !== 100 ) {
			$traffic_splits = array_map(
				function( $split ) use ( $total_split ) {
					return round( ( $split / $total_split ) * 100, 2 );
				},
				$traffic_splits
			);
		}

		// Get visitor identifier
		$visitor = self::get_visitor_id();

		// Check if visitor already has an assignment
		$existing_assignment = self::get_existing_assignment( $bar_id, $visitor['id'], $visitor['type'] );
		if ( $existing_assignment !== false ) {
			return $existing_assignment;
		}

		// Create hash from bar_id + visitor_id for consistency
		$hash_string = $bar_id . '_' . $visitor['id'];
		$hash        = crc32( $hash_string );

		// Normalize to 0-100 range (use absolute value and modulo)
		$hash_percent = abs( $hash ) % 100;

		// Cumulative distribution for traffic splits
		$cumulative = 0;
		foreach ( $variants as $index => $variant ) {
			$variant_id = is_array( $variant ) ? $variant['id'] : $variant;
			if ( ! isset( $traffic_splits[ $index ] ) ) {
				continue;
			}

			$cumulative += $traffic_splits[ $index ];
			if ( $hash_percent < $cumulative ) {
				// Store assignment in database
				self::store_assignment( $bar_id, $variant_id, $visitor['id'], $visitor['type'] );
				return $variant_id;
			}
		}

		// Fallback to first variant
		$first_variant = is_array( $variants[0] ) ? $variants[0]['id'] : $variants[0];
		self::store_assignment( $bar_id, $first_variant, $visitor['id'], $visitor['type'] );
		return $first_variant;
	}

	/**
	 * Get existing variant assignment for visitor
	 *
	 * @param int    $bar_id The announcement bar ID.
	 * @param string $visitor_id Visitor identifier.
	 * @param string $visitor_type Visitor type ('cookie' or 'user_id').
	 * @return string|false Variant ID or false if not found.
	 */
	public static function get_existing_assignment( $bar_id, $visitor_id, $visitor_type ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hthb_ab_tests';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return false;
		}

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT variant_id FROM $table_name 
				WHERE bar_id = %d 
				AND visitor_id = %s 
				AND visitor_type = %s 
				LIMIT 1",
				$bar_id,
				$visitor_id,
				$visitor_type
			)
		);

		if ( $result ) {
			// Update last_seen_at
			$wpdb->update(
				$table_name,
				array( 'last_seen_at' => current_time( 'mysql' ) ),
				array(
					'bar_id'      => $bar_id,
					'visitor_id'  => $visitor_id,
					'visitor_type' => $visitor_type,
				),
				array( '%s' ),
				array( '%d', '%s', '%s' )
			);
		}

		return $result !== null ? $result : false;
	}

	/**
	 * Store variant assignment in database
	 *
	 * @param int    $bar_id The announcement bar ID.
	 * @param string $variant_id Variant identifier.
	 * @param string $visitor_id Visitor identifier.
	 * @param string $visitor_type Visitor type ('cookie' or 'user_id').
	 * @return bool True on success, false on failure.
	 */
	private static function store_assignment( $bar_id, $variant_id, $visitor_id, $visitor_type ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hthb_ab_tests';

		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return false;
		}

		// Use INSERT ... ON DUPLICATE KEY UPDATE to handle race conditions
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $table_name (bar_id, variant_id, visitor_id, visitor_type, assigned_at, last_seen_at)
				VALUES (%d, %s, %s, %s, %s, %s)
				ON DUPLICATE KEY UPDATE
				last_seen_at = VALUES(last_seen_at)",
				$bar_id,
				$variant_id,
				$visitor_id,
				$visitor_type,
				current_time( 'mysql' ),
				current_time( 'mysql' )
			)
		);

		return $result !== false;
	}

	/**
	 * Get assigned variant for current visitor
	 *
	 * @param int $bar_id The announcement bar ID.
	 * @return string|false Variant ID or false if not assigned or A/B test not enabled.
	 */
	public static function get_assigned_variant( $bar_id ) {
		// Check if A/B test is enabled
		$test_enabled = get_post_meta( $bar_id, '_wphash_ab_test_enabled', true );
		if ( ! $test_enabled || $test_enabled === 'false' || $test_enabled === '0' ) {
			return false;
		}

		// Get variants
		$variants_raw = get_post_meta( $bar_id, '_wphash_ab_test_variants', true );
		if ( empty( $variants_raw ) ) {
			return false;
		}

		// Parse variants
		$variants = array();
		if ( is_string( $variants_raw ) ) {
			$variants = json_decode( $variants_raw, true );
		} elseif ( is_array( $variants_raw ) ) {
			$variants = $variants_raw;
		}

		if ( empty( $variants ) || ! is_array( $variants ) ) {
			return false;
		}

		// Extract traffic splits
		$traffic_splits = array();
		foreach ( $variants as $variant ) {
			$traffic = isset( $variant['traffic'] ) ? (int) $variant['traffic'] : ( isset( $variant['traffic_split'] ) ? (int) $variant['traffic_split'] : 0 );
			$traffic_splits[] = $traffic;
		}

		// Get visitor identifier
		$visitor = self::get_visitor_id();

		// Check existing assignment first
		$existing = self::get_existing_assignment( $bar_id, $visitor['id'], $visitor['type'] );
		if ( $existing !== false ) {
			return $existing;
		}

		// Assign new variant
		return self::assign_variant( $bar_id, $variants, $traffic_splits );
	}
}

