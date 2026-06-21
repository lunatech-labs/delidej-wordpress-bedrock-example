<?php
namespace Hashbar\Pro;

/**
 * Frontend Announcement Bar Display and Functionality
 *
 * Handles rendering of announcement bars on the frontend with all
 * targeting, animations, and interactive features.
 *
 * @package HashBar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize frontend announcement bar rendering
 */
function hashbar_init_announcement_bars() {
	if ( is_admin() ) {
		return;
	}

	// Check if any active announcement bars exist
	$active_bars = get_posts( array(
		'post_type'      => 'wphash_announcement',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'     => '_wphash_ab_status',
				'value'   => 'active',
				'compare' => '=',
			),
		),
	) );

	if ( empty( $active_bars ) ) {
		return;
	}

	// Enqueue frontend assets
	hashbar_enqueue_frontend_assets();

	// Add announcement bars to header (top) or footer based on position
	$first_bar = reset( $active_bars );
	if ( $first_bar ) {
		$position = get_post_meta( $first_bar->ID, '_wphash_ab_position', true ) ?: 'top';
		$hook = ( $position === 'top' ) ? 'wp_head' : 'wp_footer';
		add_action( $hook, __NAMESPACE__ . '\hashbar_render_announcement_bars', 999 );
	}
}
add_action( 'wp', __NAMESPACE__ . '\hashbar_init_announcement_bars' );

/**
 * Enqueue frontend announcement bar assets
 */
function hashbar_enqueue_frontend_assets() {
	$plugin_dir = HASHBAR_WPNBP_DIR;

	// Styles
	wp_enqueue_style(
		'hashbar-announcement-frontend',
		HASHBAR_WPNBP_URI . '/assets/css/announcement-bar-frontend.css',
		array(),
		HASHBAR_WPNBP_VERSION
	);

	// Scripts
	wp_enqueue_script(
		'hashbar-announcement-frontend',
		HASHBAR_WPNBP_URI . '/assets/js/announcement-bar-frontend.js',
		array( 'jquery' ),
		HASHBAR_WPNBP_VERSION,
		true
	);

	// Enqueue pro features script
	wp_enqueue_script(
		'hashbar-announcement-pro',
		HASHBAR_WPNBP_URI . '/assets/js/announcement-bar-pro.js',
		array( 'jquery' ),
		HASHBAR_WPNBP_VERSION,
		true
	);

	// Enqueue announcement analytics tracker
	wp_enqueue_script(
		'hashbar-announcement-analytics',
		HASHBAR_WPNBP_URI . '/assets/js/announcement-analytics.js',
		array(),
		HASHBAR_WPNBP_VERSION,
		true
	);

	// Get site timezone offset in hours for countdown calculations
	$site_timezone = wp_timezone();
	$now = new \DateTime( 'now', $site_timezone );
	$site_timezone_offset = $now->getOffset() / 3600; // Convert seconds to hours

	// Localize script with data
	wp_localize_script(
		'hashbar-announcement-frontend',
		'HashbarAnnouncementData',
		array(
			'restUrl'            => rest_url( 'hashbar/v1/' ),
			'nonce'              => wp_create_nonce( 'hashbar_announcement_nonce' ),
			'siteTimezoneOffset' => $site_timezone_offset,
		)
	);

	// Localize analytics script with configuration
	wp_localize_script(
		'hashbar-announcement-analytics',
		'HashbarAnalyticsConfig',
		array(
			'restUrl' => rest_url( 'hashbar/v1/' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		)
	);
}

/**
 * Render all active announcement bars
 */
function hashbar_render_announcement_bars() {
	$active_bars = get_posts( array(
		'post_type'      => 'wphash_announcement',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key'     => '_wphash_ab_status',
				'value'   => 'active',
				'compare' => '=',
			),
		),
	) );

	if ( empty( $active_bars ) ) {
		return;
	}

	// display:contents removes the container from the layout tree so its children resolve
	// their containing block to <body>. This is required for top-sticky bars (position:sticky)
	// to stick across the full page scroll instead of only while this small container is in view.
	echo '<div class="hashbar-announcement-bars-container" style="display:contents">';

	foreach ( $active_bars as $bar ) {
		// Check if bar should be displayed based on targeting rules
		if ( hashbar_should_display_bar( $bar->ID ) ) {
			hashbar_render_single_bar( $bar );
		}
	}

	echo '</div>';
}

/**
 * Check if announcement bar should be displayed based on targeting rules
 *
 * @param int $bar_id The announcement bar post ID.
 * @return bool Whether the bar should be displayed.
 */
function hashbar_should_display_bar( $bar_id ) {
	// Check schedule (if pro enabled)
	if ( ! hashbar_check_schedule( $bar_id ) ) {
		return false;
	}

	// Check device targeting
	if ( ! hashbar_check_device_targeting( $bar_id ) ) {
		return false;
	}

	// Check page targeting
	if ( ! hashbar_check_page_targeting( $bar_id ) ) {
		return false;
	}

	// Check geographic targeting
	if ( ! hashbar_check_geographic_targeting( $bar_id ) ) {
		return false;
	}

	// Check customer segmentation
	if ( ! hashbar_check_customer_segmentation( $bar_id ) ) {
		return false;
	}

	// Check behavioral targeting (time on site)
	if ( ! hashbar_check_behavioral_targeting( $bar_id ) ) {
		return false;
	}

	// Check if user has closed this bar (check cookie)
	if ( hashbar_is_bar_closed( $bar_id ) ) {
		return false;
	}

	return true;
}

/**
 * Check if announcement bar schedule allows display
 *
 * @param int $bar_id The announcement bar post ID.
 * @return bool Whether the schedule allows display.
 */
function hashbar_check_schedule( $bar_id ) {
	$schedule_enabled = get_post_meta( $bar_id, '_wphash_ab_schedule_enabled', true );

	if ( ! $schedule_enabled || $schedule_enabled === 'false' ) {
		return true; // Schedule not enabled, always display
	}

	// Get timezone setting early - if visitor timezone, skip all server-side checks
	// JavaScript will handle visitor timezone schedule checks on the frontend
	$timezone_setting = get_post_meta( $bar_id, '_wphash_ab_schedule_timezone', true ) ?: 'site';
	if ( $timezone_setting === 'visitor' ) {
		return true; // Allow display, JavaScript will handle visitor timezone schedule checks
	}

	$start_date = get_post_meta( $bar_id, '_wphash_ab_schedule_start', true );
	$end_date   = get_post_meta( $bar_id, '_wphash_ab_schedule_end', true );

	$current_time = current_time( 'timestamp' );

	// If start date is set and not reached yet, don't display
	if ( ! empty( $start_date ) ) {
		$start_timestamp = strtotime( $start_date );
		if ( $current_time < $start_timestamp ) {
			return false;
		}
	}

	// If end date is set and has passed, don't display
	if ( ! empty( $end_date ) ) {
		$end_timestamp = strtotime( $end_date );
		if ( $current_time > $end_timestamp ) {
			return false;
		}
	}

	// Check recurring schedule (if enabled)
	$schedule_recurring = get_post_meta( $bar_id, '_wphash_ab_schedule_recurring', true );
	if ( $schedule_recurring && $schedule_recurring !== 'false' ) {
		$recurring_days = get_post_meta( $bar_id, '_wphash_ab_schedule_recurring_days', true );
		// If recurring is enabled but no days are selected, don't display
		if ( empty( $recurring_days ) ) {
			return false;
		}
		// Get current day name in lowercase (monday, tuesday, etc.)
		$current_day = strtolower( date( 'l' ) );
		// Normalize array values to lowercase
		$recurring_days_normalized = array_map( 'strtolower', (array) $recurring_days );
		if ( ! in_array( $current_day, $recurring_days_normalized, true ) ) {
			return false; // Not an active day
		}
	}

	// Check time targeting (if enabled)
	$time_targeting = get_post_meta( $bar_id, '_wphash_ab_schedule_time_targeting', true );
	if ( $time_targeting && $time_targeting !== 'false' ) {
		$time_start = get_post_meta( $bar_id, '_wphash_ab_schedule_time_start', true );
		$time_end   = get_post_meta( $bar_id, '_wphash_ab_schedule_time_end', true );

		if ( ! empty( $time_start ) || ! empty( $time_end ) ) {
			try {
				// Use site timezone for time comparison (visitor timezone is handled by JS)
				$tz = wp_timezone();
				$current_datetime = new \DateTime( 'now', $tz );
				$current_time_str = $current_datetime->format( 'H:i' );

				// Set default times if not provided
				$time_start = ! empty( $time_start ) ? $time_start : '00:00';
				$time_end = ! empty( $time_end ) ? $time_end : '23:59';

				// Check against start time
				if ( $current_time_str < $time_start ) {
					return false; // Before start time
				}

				// Check against end time
				if ( $current_time_str > $time_end ) {
					return false; // After end time
				}
			} catch ( Exception $e ) {
				// If timezone is invalid, allow display
				return true;
			}
		}
	}

	return true;
}

/**
 * Check device targeting
 *
 * @param int $bar_id The announcement bar post ID.
 * @return bool Whether the current device is targeted.
 */
function hashbar_check_device_targeting( $bar_id ) {
	$devices = get_post_meta( $bar_id, '_wphash_ab_target_devices', true );

	if ( empty( $devices ) ) {
		return true; // No device targeting, display on all
	}

	// Handle different array formats
	if ( is_string( $devices ) ) {
		// Try JSON first (new format)
		$decoded = json_decode( $devices, true );
		if ( is_array( $decoded ) ) {
			$devices = $decoded;
		} elseif ( strpos( $devices, 'a:' ) === 0 ) {
			// Try PHP serialized (legacy format)
			$devices = unserialize( $devices );
		} else {
			// Fallback to comma-separated
			$devices = explode( ',', $devices );
		}
	}

	if ( ! is_array( $devices ) ) {
		$devices = array();
	}

	// Detect device type
	$current_device = hashbar_detect_device();

	return in_array( $current_device, $devices, true );
}

/**
 * Detect current device type (mobile, tablet, desktop)
 *
 * @return string Device type.
 */
function hashbar_detect_device() {
	// Check for mobile device
	if ( wp_is_mobile() ) {
		// Further check for tablet
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		// Tablet detection patterns
		$tablet_patterns = array(
			'ipad',
			'android(?!.*mobile)',
			'tablet',
		);

		foreach ( $tablet_patterns as $pattern ) {
			if ( preg_match( '/' . $pattern . '/i', $user_agent ) ) {
				return 'tablet';
			}
		}

		return 'mobile';
	}

	return 'desktop';
}

/**
 * Normalize page/post IDs from meta (JSON arrays often contain string IDs).
 *
 * @param mixed $ids Raw array after JSON decode or legacy formats.
 * @return int[] Unique positive integers.
 */
function hashbar_normalize_target_page_ids( $ids ) {
	if ( empty( $ids ) || ! is_array( $ids ) ) {
		return array();
	}
	$normalized = array_map( 'absint', $ids );
	return array_values( array_unique( array_filter( $normalized ) ) );
}

/**
 * Check page targeting
 *
 * @param int $bar_id The announcement bar post ID.
 * @return bool Whether the current page is targeted.
 */
function hashbar_check_page_targeting( $bar_id ) {
	$targeting_type = get_post_meta( $bar_id, '_wphash_ab_target_pages', true );

	// Default: show on all pages
	if ( empty( $targeting_type ) || $targeting_type === 'all' ) {
		return true;
	}

	if ( $targeting_type === 'homepage' ) {
		return is_front_page();
	}

	if ( $targeting_type === 'specific' ) {
		$page_ids = get_post_meta( $bar_id, '_wphash_ab_target_page_ids', true );

		// Handle different array formats
		if ( is_string( $page_ids ) ) {
			// Try JSON first (new format)
			$decoded = json_decode( $page_ids, true );
			if ( is_array( $decoded ) ) {
				$page_ids = $decoded;
			} elseif ( strpos( $page_ids, 'a:' ) === 0 ) {
				// Try PHP serialized (legacy format)
				$page_ids = unserialize( $page_ids );
			}
		}

		if ( empty( $page_ids ) || ! is_array( $page_ids ) ) {
			return false;
		}

		$page_ids        = hashbar_normalize_target_page_ids( $page_ids );
		$current_page_id = absint( get_queried_object_id() );

		return $current_page_id > 0 && in_array( $current_page_id, $page_ids, true );
	}

	if ( $targeting_type === 'exclude' ) {
		$excluded_ids = get_post_meta( $bar_id, '_wphash_ab_exclude_page_ids', true );

		// Handle different array formats
		if ( is_string( $excluded_ids ) ) {
			// Try JSON first (new format)
			$decoded = json_decode( $excluded_ids, true );
			if ( is_array( $decoded ) ) {
				$excluded_ids = $decoded;
			} elseif ( strpos( $excluded_ids, 'a:' ) === 0 ) {
				// Try PHP serialized (legacy format)
				$excluded_ids = unserialize( $excluded_ids );
			}
		}

		if ( empty( $excluded_ids ) || ! is_array( $excluded_ids ) ) {
			return true;
		}

		$excluded_ids    = hashbar_normalize_target_page_ids( $excluded_ids );
		$current_page_id = absint( get_queried_object_id() );

		return ! ( $current_page_id > 0 && in_array( $current_page_id, $excluded_ids, true ) );
	}

	return true;
}

/**
 * Check geographic targeting
 *
 * @param int $bar_id The announcement bar post ID.
 * @return bool Whether the current country is targeted.
 */
function hashbar_check_geographic_targeting( $bar_id ) {
	$target_countries = get_post_meta( $bar_id, '_wphash_ab_target_countries', true );

	if ( empty( $target_countries ) ) {
		return true; // No geographic targeting, display on all
	}

	// Handle different array formats
	if ( is_string( $target_countries ) ) {
		// Try JSON first (new format)
		$decoded = json_decode( $target_countries, true );
		if ( is_array( $decoded ) ) {
			$target_countries = $decoded;
		} elseif ( strpos( $target_countries, 'a:' ) === 0 ) {
			// Try PHP serialized (legacy format)
			$target_countries = unserialize( $target_countries );
		} else {
			// Fallback to comma-separated
			$target_countries = explode( ',', $target_countries );
		}
	}

	if ( ! is_array( $target_countries ) || empty( $target_countries ) ) {
		return true; // No valid targeting, display on all
	}

	// Ensure all country codes are uppercase for consistent comparison
	$target_countries = array_map( 'strtoupper', $target_countries );

	// Get current visitor's country
	$current_country = hashbar_get_visitor_country();

	// If country detection failed, allow display (better to show than not show)
	if ( empty( $current_country ) ) {
		return true;
	}

	$current_country = strtoupper( $current_country );
	$result = in_array( $current_country, $target_countries, true );

	// Check if current country is in the target list
	return $result;
}

/**
 * Get visitor's country based on IP address
 *
 * @return string|null Country code or null if not available
 */
function hashbar_get_visitor_country() {
	// Check if we already have this in a transient (cache for 1 hour)
	$ip = hashbar_get_client_ip();

	if ( empty( $ip ) ) {
		return null;
	}

	// Allow test override via URL parameter (for local development on private IPs)
	if ( isset( $_GET['hashbar_test_country'] ) && defined( 'WP_DEBUG' ) && WP_DEBUG && hashbar_is_private_ip( $ip ) ) {
		$test_country = sanitize_text_field( $_GET['hashbar_test_country'] );
		if ( strlen( $test_country ) === 2 ) {
			return strtoupper( $test_country );
		}
	}

	// Allow clearing cache via URL parameter for debugging
	if ( isset( $_GET['hashbar_clear_geo_cache'] ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		delete_transient( $cache_key );
	}

	$cache_key = 'hashbar_visitor_country_' . md5( $ip );
	$cached = get_transient( $cache_key );

	if ( false !== $cached ) {
		return $cached;
	}

	// Fetch from GeoIP API - Call without IP to use visitor's actual IP (ip-api.com automatically detects visitor IP)
	$response = wp_remote_get(
		'http://ip-api.com/json/',
		array(
			'timeout'   => 3,
			'sslverify' => false,
		)
	);

	if ( is_wp_error( $response ) ) {
		return null;
	}

	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );

	if ( ! is_array( $data ) || empty( $data['countryCode'] ) ) {
		return null;
	}

	$country_code = strtoupper( $data['countryCode'] );

	// Cache for 1 hour
	set_transient( $cache_key, $country_code, HOUR_IN_SECONDS );

	return $country_code;
}

/**
 * Get client IP address
 *
 * @return string Client IP address
 */
function hashbar_get_client_ip() {
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
		$ip = '';
	}

	return $ip;
}

/**
 * Check if IP address is private or localhost
 *
 * @param string $ip The IP address
 * @return bool True if IP is private or localhost
 */
function hashbar_is_private_ip( $ip ) {
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
 * Check if user has closed this announcement bar (via cookie)
 *
 * @param int $bar_id The announcement bar post ID.
 * @return bool Whether the bar has been closed by user.
 */
function hashbar_is_bar_closed( $bar_id ) {
	$cookie_name = 'hashbar_announcement_closed_' . $bar_id;

	if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
		return false;
	}

	// Get current duration setting
	$cookie_preset = get_post_meta( $bar_id, '_wphash_ab_cookie_expire_after_close', true ) ?: 'show_on_reload';

	// If set to "show on reload", ignore any existing cookie
	if ( $cookie_preset === 'show_on_reload' ) {
		return false;
	}

	// If set to "never", always stay closed
	if ( $cookie_preset === 'never' ) {
		return true;
	}

	// "session only" - always render the bar, let inline JS handle visibility
	// "session only" - cookie exists means bar was closed in this session
	if ( $cookie_preset === 'session_only' ) {
		return true;
	}

	// For time-based durations, compare close timestamp against setting
	$closed_time = intval( $_COOKIE[ $cookie_name ] );

	// Legacy cookies stored '1' - treat as closed (browser expiry will handle cleanup)
	if ( $closed_time <= 1 ) {
		return true;
	}

	// Convert preset to seconds and check if enough time has passed
	$durations_in_seconds = array(
		'1_hour'  => 3600,
		'6_hours' => 21600,
		'1_day'   => 86400,
		'7_days'  => 604800,
		'2_weeks' => 1209600,
		'1_month' => 2592000,
	);

	if ( ! isset( $durations_in_seconds[ $cookie_preset ] ) ) {
		return true;
	}

	// Cookie stores JS Date.now() in milliseconds - convert to seconds
	$closed_timestamp = $closed_time / 1000;
	$elapsed = time() - $closed_timestamp;

	return $elapsed < $durations_in_seconds[ $cookie_preset ];
}

/**
 * Render a single announcement bar
 *
 * @param WP_Post $bar The announcement bar post object.
 */
function hashbar_render_single_bar( $bar ) {
	$bar_id = $bar->ID;

	// Check A/B test and get assigned variant
	$variant_id = false;
	$variant_settings = array();
	$pending_variant_settings = array();
	$test_enabled = get_post_meta( $bar_id, '_wphash_ab_test_enabled', true );
	if ( $test_enabled && $test_enabled !== 'false' && $test_enabled !== '0' ) {
		$variant_id = \Hashbar\Pro\ABTest\AB_Test_Assignment::get_assigned_variant( $bar_id );
		if ( $variant_id ) {
			// Get variant settings
			$variants_raw = get_post_meta( $bar_id, '_wphash_ab_test_variants', true );
			$variants = array();
			if ( is_string( $variants_raw ) ) {
				$variants = json_decode( $variants_raw, true );
			} elseif ( is_array( $variants_raw ) ) {
				$variants = $variants_raw;
			}

			// Find the variant and get its settings
			foreach ( $variants as $variant ) {
				if ( isset( $variant['id'] ) && $variant['id'] === $variant_id ) {
					$variant_settings = isset( $variant['settings'] ) ? $variant['settings'] : ( isset( $variant['content'] ) ? $variant['content'] : array() );
					break;
				}
			}
		}
	}

	// Get all bar settings
	$status            = get_post_meta( $bar_id, '_wphash_ab_status', true );
	$position          = get_post_meta( $bar_id, '_wphash_ab_position', true ) ?: 'top';

	// Handle both string ('1', 'true') and boolean values from React
	// When saved from React, true becomes '1', false becomes empty string
	$sticky_raw        = get_post_meta( $bar_id, '_wphash_ab_sticky', true );
	$sticky            = ! empty( $sticky_raw ) && $sticky_raw !== '0' && $sticky_raw !== 'false';

	$z_index           = get_post_meta( $bar_id, '_wphash_ab_z_index', true ) ?: 9999;

	// Get message content - support both old single message and new messages array
	$message = '';
	$first_message_data = array();
	$messages_data = get_post_meta( $bar_id, '_wphash_ab_messages', true );

	if ( ! empty( $messages_data ) ) {
		// New messages array format
		if ( is_string( $messages_data ) ) {
			// Try to decode if it's JSON
			$decoded = json_decode( $messages_data, true );
			if ( is_array( $decoded ) && ! empty( $decoded[0]['text'] ) ) {
				$message = $decoded[0]['text'];
				$first_message_data = $decoded[0];
			}
		} elseif ( is_array( $messages_data ) && ! empty( $messages_data[0]['text'] ) ) {
			$message = $messages_data[0]['text'];
			$first_message_data = $messages_data[0];
		}
	}

	// Fallback to old single message field if new format is empty
	if ( empty( $message ) ) {
		$message = get_post_meta( $bar_id, '_wphash_ab_message', true );
	}

	// Apply variant settings override if variant is assigned
	if ( $variant_id && ! empty( $variant_settings ) ) {
		// Override bar settings with variant settings
		$bg_color    = isset( $variant_settings['_wphash_ab_bg_color'] ) ? $variant_settings['_wphash_ab_bg_color'] : ( get_post_meta( $bar_id, '_wphash_ab_bg_color', true ) ?: '#000000' );
		$text_color  = isset( $variant_settings['_wphash_ab_text_color'] ) ? $variant_settings['_wphash_ab_text_color'] : ( get_post_meta( $bar_id, '_wphash_ab_text_color', true ) ?: '#ffffff' );
		$font_family = isset( $variant_settings['_wphash_ab_font_family'] ) ? $variant_settings['_wphash_ab_font_family'] : ( get_post_meta( $bar_id, '_wphash_ab_font_family', true ) ?: 'Arial' );
		$font_size   = isset( $variant_settings['_wphash_ab_font_size'] ) ? $variant_settings['_wphash_ab_font_size'] : ( get_post_meta( $bar_id, '_wphash_ab_font_size', true ) ?: 14 );
		$font_weight = isset( $variant_settings['_wphash_ab_font_weight'] ) ? $variant_settings['_wphash_ab_font_weight'] : ( get_post_meta( $bar_id, '_wphash_ab_font_weight', true ) ?: 400 );
		$text_align  = isset( $variant_settings['_wphash_ab_text_align'] ) ? $variant_settings['_wphash_ab_text_align'] : ( get_post_meta( $bar_id, '_wphash_ab_text_align', true ) ?: 'center' );
		$height      = isset( $variant_settings['_wphash_ab_height'] ) ? $variant_settings['_wphash_ab_height'] : ( get_post_meta( $bar_id, '_wphash_ab_height', true ) ?: 60 );
		$padding     = isset( $variant_settings['_wphash_ab_padding'] ) ? $variant_settings['_wphash_ab_padding'] : ( get_post_meta( $bar_id, '_wphash_ab_padding', true ) ?: array( 10, 10, 10, 10 ) );

		// Override messages if variant has custom messages
		if ( isset( $variant_settings['_wphash_ab_messages'] ) && ! empty( $variant_settings['_wphash_ab_messages'] ) ) {
			$variant_messages = $variant_settings['_wphash_ab_messages'];
			if ( is_array( $variant_messages ) && ! empty( $variant_messages[0] ) ) {
				$first_variant_message = $variant_messages[0];

				// Override message text if provided
				if ( ! empty( $first_variant_message['text'] ) ) {
					$message = $first_variant_message['text'];
				}

				// Override CTA settings from variant message
				if ( isset( $first_variant_message['cta_enabled'] ) ) {
					$cta_enabled_raw = $first_variant_message['cta_enabled'];
					$cta_enabled = ! empty( $cta_enabled_raw ) && $cta_enabled_raw !== '0' && $cta_enabled_raw !== 'false';
				}
				if ( ! empty( $first_variant_message['cta_text'] ) ) {
					$cta_text = $first_variant_message['cta_text'];
				}
				if ( ! empty( $first_variant_message['cta_url'] ) ) {
					$cta_url = $first_variant_message['cta_url'];
				}
				if ( ! empty( $first_variant_message['cta_target'] ) ) {
					$cta_target = $first_variant_message['cta_target'];
				}

				// Store the variant message data for later use
				$first_message_data = $first_variant_message;

				// CRITICAL: Update $messages_data with variant messages so JavaScript receives the correct data
				// This ensures frontend displays variant content and doesn't revert to control content
				$messages_data = $variant_messages;
			}
		}

		// Also support direct CTA overrides (for backwards compatibility)
		if ( isset( $variant_settings['_wphash_ab_cta_text'] ) && ! empty( $variant_settings['_wphash_ab_cta_text'] ) ) {
			$cta_text = $variant_settings['_wphash_ab_cta_text'];
		}
		if ( isset( $variant_settings['_wphash_ab_cta_url'] ) && ! empty( $variant_settings['_wphash_ab_cta_url'] ) ) {
			$cta_url = $variant_settings['_wphash_ab_cta_url'];
		}
		if ( isset( $variant_settings['_wphash_ab_cta_target'] ) && ! empty( $variant_settings['_wphash_ab_cta_target'] ) ) {
			$cta_target = $variant_settings['_wphash_ab_cta_target'];
		}
		if ( isset( $variant_settings['_wphash_ab_cta_enabled'] ) ) {
			$cta_enabled_raw = $variant_settings['_wphash_ab_cta_enabled'];
			$cta_enabled = ! empty( $cta_enabled_raw ) && $cta_enabled_raw !== '0' && $cta_enabled_raw !== 'false';
		}

		// Store variant settings for later use (countdown/coupon overrides applied after default values loaded)
		$pending_variant_settings = $variant_settings;
	} else {
		// Use default bar settings
		$bg_color          = get_post_meta( $bar_id, '_wphash_ab_bg_color', true ) ?: '#000000';
		$text_color        = get_post_meta( $bar_id, '_wphash_ab_text_color', true ) ?: '#ffffff';
		$font_family       = get_post_meta( $bar_id, '_wphash_ab_font_family', true ) ?: 'Arial';
		$font_size         = get_post_meta( $bar_id, '_wphash_ab_font_size', true ) ?: 14;
		$font_weight       = get_post_meta( $bar_id, '_wphash_ab_font_weight', true ) ?: 400;
		$text_align        = get_post_meta( $bar_id, '_wphash_ab_text_align', true ) ?: 'center';
		$height            = get_post_meta( $bar_id, '_wphash_ab_height', true ) ?: 60;
		$padding           = get_post_meta( $bar_id, '_wphash_ab_padding', true ) ?: array( 10, 10, 10, 10 );
	}

	// Handle both string ('1', 'true') and boolean values from React
	// When saved from React, true becomes '1', false becomes empty string
	$close_enabled_raw = get_post_meta( $bar_id, '_wphash_ab_close_enabled', true );
	$close_enabled     = ! empty( $close_enabled_raw ) && $close_enabled_raw !== '0' && $close_enabled_raw !== 'false';

	$close_text        = get_post_meta( $bar_id, '_wphash_ab_close_text', true ) ?: '✕';

	// Handle both string ('1', 'true') and boolean values from React
	// When saved from React, true becomes '1', false becomes empty string
	$reopen_enabled_raw = get_post_meta( $bar_id, '_wphash_ab_reopen_enabled', true );
	$reopen_enabled     = ! empty( $reopen_enabled_raw ) && $reopen_enabled_raw !== '0' && $reopen_enabled_raw !== 'false';
	$reopen_text       = get_post_meta( $bar_id, '_wphash_ab_reopen_text', true ) ?: 'Show';

	// Cookie Duration Settings
	// Helper function to convert preset values to days
	$convert_preset_to_days = function( $preset ) {
		$presets = array(
			'show_on_reload' => -1,   // Special value: don't set cookie
			'session_only' => 0,      // Session only (will be handled separately in JS)
			'1_hour'       => 1 / 24,
			'6_hours'      => 6 / 24,
			'1_day'        => 1,
			'7_days'       => 7,
			'2_weeks'      => 14,
			'1_month'      => 30,
			'never'        => 3650,   // 10 years
		);
		return isset( $presets[ $preset ] ) ? $presets[ $preset ] : -1; // Default to show_on_reload
	};

	// Get cookie duration from per-bar settings (default: show_on_reload)
	$cookie_preset = get_post_meta( $bar_id, '_wphash_ab_cookie_expire_after_close', true ) ?: 'show_on_reload';
	$cookie_duration = $convert_preset_to_days( $cookie_preset );

	// Handle both string ('1', 'true') and boolean values from React
	// When saved from React, true becomes '1', false becomes empty string
	// Check if CTA data is in the new messages array format first
	if ( ! empty( $first_message_data['cta_enabled'] ) || ! empty( $first_message_data['cta_text'] ) ) {
		// Use CTA data from messages array
		$cta_enabled_raw = $first_message_data['cta_enabled'] ?? false;
		$cta_enabled     = ! empty( $cta_enabled_raw ) && $cta_enabled_raw !== '0' && $cta_enabled_raw !== 'false';
		$cta_text        = $first_message_data['cta_text'] ?? 'Learn More';
		$cta_url         = $first_message_data['cta_url'] ?? '';
		$cta_target      = $first_message_data['cta_target'] ?? '_self';
	} else {
		// Fallback to old meta fields for backward compatibility
		$cta_enabled_raw = get_post_meta( $bar_id, '_wphash_ab_cta_enabled', true );
		$cta_enabled     = ! empty( $cta_enabled_raw ) && $cta_enabled_raw !== '0' && $cta_enabled_raw !== 'false';
		$cta_text        = get_post_meta( $bar_id, '_wphash_ab_cta_text', true ) ?: 'Learn More';
		$cta_url         = get_post_meta( $bar_id, '_wphash_ab_cta_url', true );
		$cta_target      = get_post_meta( $bar_id, '_wphash_ab_cta_target', true ) ?: '_self';
	}

	// Button styling: CTA
	$cta_color         = get_post_meta( $bar_id, '_wphash_ab_cta_color', true );
	$cta_bg_color      = get_post_meta( $bar_id, '_wphash_ab_cta_bg_color', true );
	$cta_hover_color   = get_post_meta( $bar_id, '_wphash_ab_cta_hover_color', true );
	$cta_hover_bg      = get_post_meta( $bar_id, '_wphash_ab_cta_hover_bg_color', true );
	$cta_font_size     = get_post_meta( $bar_id, '_wphash_ab_cta_font_size', true ) ?: 14;
	$cta_font_weight   = get_post_meta( $bar_id, '_wphash_ab_cta_font_weight', true ) ?: 500;
	$cta_border_radius = get_post_meta( $bar_id, '_wphash_ab_cta_border_radius', true ) ?: 4;

	// Button styling: Close
	$close_color       = get_post_meta( $bar_id, '_wphash_ab_close_color', true );
	$close_bg_color    = get_post_meta( $bar_id, '_wphash_ab_close_bg_color', true );
	$close_hover_color = get_post_meta( $bar_id, '_wphash_ab_close_hover_color', true );
	$close_hover_bg    = get_post_meta( $bar_id, '_wphash_ab_close_hover_bg_color', true );
	$close_font_size   = get_post_meta( $bar_id, '_wphash_ab_close_font_size', true ) ?: 14;
	$close_font_weight = get_post_meta( $bar_id, '_wphash_ab_close_font_weight', true ) ?: 500;
	$close_border_radius = get_post_meta( $bar_id, '_wphash_ab_close_border_radius', true ) ?: 4;

	// Countdown features (Free + Pro)
	// Handle both string ('1', 'true') and boolean values from React
	// When saved from React, true becomes '1', false becomes empty string
	$countdown_enabled_raw = get_post_meta( $bar_id, '_wphash_ab_countdown_enabled', true );
	$countdown_enabled     = ! empty( $countdown_enabled_raw ) && $countdown_enabled_raw !== '0' && $countdown_enabled_raw !== 'false';

	$countdown_type    = get_post_meta( $bar_id, '_wphash_ab_countdown_type', true );
	$countdown_date    = get_post_meta( $bar_id, '_wphash_ab_countdown_date', true );
	$countdown_style   = get_post_meta( $bar_id, '_wphash_ab_countdown_style', true ) ?: 'simple';
	$countdown_position = get_post_meta( $bar_id, '_wphash_ab_countdown_position', true ) ?: 'inline';
	// Use center alignment for box/circular styles, baseline for simple/digital
	$countdown_align   = in_array( $countdown_style, array( 'box', 'circular' ), true ) ? 'center' : 'baseline';
	$countdown_text_before = get_post_meta( $bar_id, '_wphash_ab_countdown_text_before', true ) ?: 'Offer ends in:';
	$countdown_text_after = get_post_meta( $bar_id, '_wphash_ab_countdown_text_after', true ) ?: '';

	// Handle both string ('1', 'true') and boolean values from React
	$show_days_raw = get_post_meta( $bar_id, '_wphash_ab_countdown_show_days', true );
	$show_hours_raw = get_post_meta( $bar_id, '_wphash_ab_countdown_show_hours', true );
	$show_minutes_raw = get_post_meta( $bar_id, '_wphash_ab_countdown_show_minutes', true );
	$show_seconds_raw = get_post_meta( $bar_id, '_wphash_ab_countdown_show_seconds', true );

	$countdown_show_days = in_array( $show_days_raw, array( '1', 'true', true ), true );
	$countdown_show_hours = in_array( $show_hours_raw, array( '1', 'true', true ), true );
	$countdown_show_minutes = in_array( $show_minutes_raw, array( '1', 'true', true ), true );
	$countdown_show_seconds = in_array( $show_seconds_raw, array( '1', 'true', true ), true );

	// Recurring countdown fields
	$countdown_reset_time = get_post_meta( $bar_id, '_wphash_ab_countdown_reset_time', true ) ?: '00:00';
	$countdown_reset_days_raw = get_post_meta( $bar_id, '_wphash_ab_countdown_reset_days', true );
	$countdown_reset_days = array();
	if ( ! empty( $countdown_reset_days_raw ) ) {
		if ( is_string( $countdown_reset_days_raw ) ) {
			$countdown_reset_days = json_decode( $countdown_reset_days_raw, true );
		} elseif ( is_array( $countdown_reset_days_raw ) ) {
			$countdown_reset_days = $countdown_reset_days_raw;
		}
		if ( ! is_array( $countdown_reset_days ) ) {
			$countdown_reset_days = array();
		}
	}
	$countdown_timezone = get_post_meta( $bar_id, '_wphash_ab_countdown_timezone', true ) ?: 'site';
	$countdown_duration = get_post_meta( $bar_id, '_wphash_ab_countdown_duration', true ) ?: 24;

	// Pro features
	// Handle both string ('1', 'true') and boolean values from React
	// When saved from React, true becomes '1', false becomes empty string
	$coupon_enabled_raw   = get_post_meta( $bar_id, '_wphash_ab_coupon_enabled', true );
	$coupon_enabled       = ! empty( $coupon_enabled_raw ) && $coupon_enabled_raw !== '0' && $coupon_enabled_raw !== 'false';

	$coupon_code          = get_post_meta( $bar_id, '_wphash_ab_coupon_code', true );

	// Handle both string ('1', 'true') and boolean values from React
	// When saved from React, true becomes '1', false becomes empty string
	$coupon_show_button_raw = get_post_meta( $bar_id, '_wphash_ab_coupon_show_copy_button', true );
	$coupon_show_button     = ! empty( $coupon_show_button_raw ) && $coupon_show_button_raw !== '0' && $coupon_show_button_raw !== 'false';

	$coupon_copy_button_text = get_post_meta( $bar_id, '_wphash_ab_coupon_copy_button_text', true ) ?: __( 'Copy', 'hashbar' );
	$coupon_copied_button_text = get_post_meta( $bar_id, '_wphash_ab_coupon_copied_button_text', true ) ?: __( 'Copied!', 'hashbar' );

	// Handle both string ('1', 'true') and boolean values from React
	// When saved from React, true becomes '1', false becomes empty string
	$coupon_autocopy_on_click_raw = get_post_meta( $bar_id, '_wphash_ab_coupon_autocopy_on_click', true );
	$coupon_autocopy_on_click     = ! empty( $coupon_autocopy_on_click_raw ) && $coupon_autocopy_on_click_raw !== '0' && $coupon_autocopy_on_click_raw !== 'false';

	// Apply variant overrides for countdown and coupon settings (SIMPLIFIED - only 2 fields each)
	if ( ! empty( $pending_variant_settings ) ) {
		// Countdown overrides
		if ( isset( $pending_variant_settings['_wphash_ab_countdown_enabled'] ) ) {
			$countdown_enabled_raw = $pending_variant_settings['_wphash_ab_countdown_enabled'];
			$countdown_enabled = ! empty( $countdown_enabled_raw ) && $countdown_enabled_raw !== '0' && $countdown_enabled_raw !== 'false';
		}
		if ( isset( $pending_variant_settings['_wphash_ab_countdown_style'] ) ) {
			$countdown_style = $pending_variant_settings['_wphash_ab_countdown_style'];
		}

		// Coupon overrides
		if ( isset( $pending_variant_settings['_wphash_ab_coupon_enabled'] ) ) {
			$coupon_enabled_raw = $pending_variant_settings['_wphash_ab_coupon_enabled'];
			$coupon_enabled = ! empty( $coupon_enabled_raw ) && $coupon_enabled_raw !== '0' && $coupon_enabled_raw !== 'false';
		}
		if ( isset( $pending_variant_settings['_wphash_ab_coupon_display_style'] ) ) {
			$coupon_display_style = $pending_variant_settings['_wphash_ab_coupon_display_style'];
		}
	}

	$animation_entry   = get_post_meta( $bar_id, '_wphash_ab_animation_entry', true );
	$animation_exit    = get_post_meta( $bar_id, '_wphash_ab_animation_exit', true );
	$animation_duration = get_post_meta( $bar_id, '_wphash_ab_animation_duration', true ) ?: 500;

	// Parse padding - handle both array format [top, right, bottom, left] and object format {top, right, bottom, left}
	$padding_top = 10;
	$padding_right = 10;
	$padding_bottom = 10;
	$padding_left = 10;

	if ( is_string( $padding ) ) {
		$padding = json_decode( $padding, true );
	}

	if ( is_array( $padding ) ) {
		// Handle array format: [top, right, bottom, left] or numeric indices
		if ( isset( $padding[0] ) ) {
			// Numeric array format
			$padding_top = isset( $padding[0] ) ? (int) $padding[0] : 10;
			$padding_right = isset( $padding[1] ) ? (int) $padding[1] : 10;
			$padding_bottom = isset( $padding[2] ) ? (int) $padding[2] : 10;
			$padding_left = isset( $padding[3] ) ? (int) $padding[3] : 10;
		} elseif ( isset( $padding['top'] ) || isset( $padding['right'] ) || isset( $padding['bottom'] ) || isset( $padding['left'] ) ) {
			// Object/associative array format: {top, right, bottom, left}
			$padding_top = isset( $padding['top'] ) ? (int) $padding['top'] : 10;
			$padding_right = isset( $padding['right'] ) ? (int) $padding['right'] : 10;
			$padding_bottom = isset( $padding['bottom'] ) ? (int) $padding['bottom'] : 10;
			$padding_left = isset( $padding['left'] ) ? (int) $padding['left'] : 10;
		}
	}

	// Determine bar color (for text contrast)
	$is_dark_bg = hashbar_is_dark_color( $bg_color );
	$button_color = $is_dark_bg ? '#ffffff' : '#000000';
	$button_bg_color = $is_dark_bg ? 'rgba(0, 0, 0, 0.1)' : 'rgba(255, 255, 255, 0.2)';

	// Build inline styles
	$padding_str = "{$padding_top}px {$padding_right}px {$padding_bottom}px {$padding_left}px";

	$is_sticky = $sticky === 'true' || $sticky === true || $sticky === '1';

	// Map text-align to justify-content for flex layout
	$justify_content = 'space-between'; // default
	if ( $text_align === 'left' ) {
		$justify_content = 'flex-start';
	} elseif ( $text_align === 'center' ) {
		$justify_content = 'center';
	} elseif ( $text_align === 'right' ) {
		$justify_content = 'flex-end';
	}

	// Generate background CSS based on background type
	$bg_type = get_post_meta( $bar_id, '_wphash_ab_bg_type', true ) ?: 'solid';
	$background_css = $bg_color; // Default solid color
	$bg_image = null; // Initialize for use in both places
	$image_size = 'cover'; // Default image size

	if ( $bg_type === 'gradient' ) {
		$gradient_color = get_post_meta( $bar_id, '_wphash_ab_gradient_color', true );
		$gradient_direction = get_post_meta( $bar_id, '_wphash_ab_gradient_direction', true );
		if ( $gradient_color ) {
			$direction = $gradient_direction ? $gradient_direction : 'to_bottom';
			// Convert underscores to spaces for CSS (to_bottom -> to bottom)
			$direction_css = str_replace( '_', ' ', $direction );
			$background_css = "linear-gradient({$direction_css}, {$bg_color}, {$gradient_color})";
		}
	} elseif ( $bg_type === 'image' ) {
		$bg_image = get_post_meta( $bar_id, '_wphash_ab_bg_image', true );

		// Handle JSON-encoded image data
		if ( is_string( $bg_image ) ) {
			$bg_image = json_decode( $bg_image, true );
		}

		if ( is_array( $bg_image ) && ! empty( $bg_image['url'] ) ) {
			$image_url = esc_url( $bg_image['url'] );
			$image_size = isset( $bg_image['size'] ) ? $bg_image['size'] : 'cover';
			$background_css = "url('{$image_url}')";
			// For images, we need to add additional properties
			// We'll do this separately since background is split into multiple CSS properties
		}
	}

	// Top sticky uses position:sticky so the bar stays in document flow — closing it
	// shrinks layout naturally and the header rises in lockstep with the bar (no separate
	// body-padding animation needed). Bottom sticky stays position:fixed because sticky
	// would render off-screen on initial load when body is taller than viewport.
	$sticky_position = $is_sticky ? ( $position === 'top' ? 'sticky' : 'fixed' ) : 'static';
	$bar_styles = array(
		'position'       => $sticky_position,
		'top'            => $is_sticky && $position === 'top' ? '0' : 'auto',
		'bottom'         => $is_sticky && $position === 'bottom' ? '0' : 'auto',
		'left'           => $sticky_position === 'fixed' ? '0' : 'auto',
		'right'          => $sticky_position === 'fixed' ? '0' : 'auto',
		'background'     => $background_css,
		'color'          => $text_color,
		'font-family'    => $font_family,
		'font-size'      => $font_size . 'px',
		'font-weight'    => $font_weight,
		'text-align'     => $text_align,
		'min-height'     => $height . 'px',
		'padding'        => $padding_str,
		'z-index'        => $z_index,
		'width'          => '100%',
		'box-sizing'     => 'border-box',
		'display'        => 'flex',
		'align-items'    => 'center',
		'justify-content' => $justify_content,
		'gap'            => '16px',
		'animation'      => $animation_entry ? $animation_entry . ' ' . ( $animation_duration / 1000 ) . 's ease-out' : 'none',
	);

	if ( $animation_entry && defined( 'HASHBAR_WPNBP_VERSION' ) ) {
		// Pro animation support
		$bar_styles['animation'] = $animation_entry . ' ' . ( $animation_duration / 1000 ) . 's ease-out forwards';
	}

	// For top-sticky bars, the CSS translate-style entry animation only moves the bar
	// visually — layout space is reserved upfront, so a blank strip appears before the
	// bar arrives. Override to a height-grow entry instead: bar is rendered collapsed,
	// inline script grows it to its natural height. Layout space and bar appear together.
	if ( $is_sticky && $position === 'top' ) {
		$bar_styles['animation']   = 'none';
		$bar_styles['height']      = '0';
		$bar_styles['min-height']  = '0';
		$bar_styles['overflow']    = 'hidden';
		$bar_styles['opacity']     = '0';
	}

	// Add image background properties if image background is used
	if ( $bg_type === 'image' && is_array( $bg_image ) && ! empty( $bg_image['url'] ) ) {
		$bar_styles['background-size'] = $image_size;
		$bar_styles['background-position'] = 'center';
		$bar_styles['background-repeat'] = 'no-repeat';
	}

	// Typographic tokens for rich message HTML — nested `p`, lists, links inherit these so
	// theme stylesheet rules do not override the bar's Design settings. Inline styles in user
	// content still win (normal cascade). See announcement-bar-frontend.css.
	$bar_styles['--hashbar-announcement-color']       = $text_color;
	$bar_styles['--hashbar-announcement-font-family'] = $font_family;
	$bar_styles['--hashbar-announcement-font-size']   = is_numeric( $font_size ) ? ( (string) (int) $font_size ) . 'px' : $font_size;
	$bar_styles['--hashbar-announcement-font-weight'] = is_numeric( $font_weight ) ? (string) (int) $font_weight : $font_weight;

	// Build style string
	$style_string = '';
	foreach ( $bar_styles as $property => $value ) {
		$style_string .= $property . ': ' . $value . '; ';
	}

	// Build CTA button style string with CSS variables for hover states
	// Use same fallbacks as React LivePreview component
	$cta_btn_color = ! empty( $cta_color ) ? $cta_color : $bg_color;
	$cta_btn_bg_color = ! empty( $cta_bg_color ) ? $cta_bg_color : $button_color;
	$cta_hover_text = ! empty( $cta_hover_color ) ? $cta_hover_color : $cta_btn_color;
	$cta_hover_bg_val = ! empty( $cta_hover_bg ) ? $cta_hover_bg : $cta_btn_bg_color;
	$cta_button_styles = "display: inline-block; background: {$cta_btn_bg_color}; color: {$cta_btn_color}; padding: 8px 16px; border-radius: {$cta_border_radius}px; border: none; cursor: pointer; font-size: {$cta_font_size}px; font-weight: {$cta_font_weight}; text-decoration: none; white-space: nowrap; transition: all 0.2s ease; flex-shrink: 0; --cta-hover-color: {$cta_hover_text}; --cta-hover-bg: {$cta_hover_bg_val};";


	// Build close button style string with CSS variables for hover states
	$close_btn_color = ! empty( $close_color ) ? $close_color : $button_color;
	$close_btn_bg_color = ! empty( $close_bg_color ) ? $close_bg_color : $button_bg_color;
	$close_hover_text = ! empty( $close_hover_color ) ? $close_hover_color : $close_btn_color;
	$close_hover_bg_val = ! empty( $close_hover_bg ) ? $close_hover_bg : $close_btn_bg_color;
	$close_button_styles = "display: inline-block; background: {$close_btn_bg_color}; color: {$close_btn_color}; padding: 8px 16px; border-radius: {$close_border_radius}px; border: none; cursor: pointer; font-size: {$close_font_size}px; font-weight: {$close_font_weight}; text-decoration: none; white-space: nowrap; transition: all 0.2s ease; flex-shrink: 0; --close-hover-color: {$close_hover_text}; --close-hover-bg: {$close_hover_bg_val};";

	// Reopen button styles
	$reopen_color = get_post_meta( $bar_id, '_wphash_ab_reopen_color', true ) ?: '#ffffff';
	$reopen_bg_color = get_post_meta( $bar_id, '_wphash_ab_reopen_bg_color', true ) ?: '#667eea';
	$reopen_hover_color = get_post_meta( $bar_id, '_wphash_ab_reopen_hover_color', true ) ?: '#ffffff';
	$reopen_hover_bg = get_post_meta( $bar_id, '_wphash_ab_reopen_hover_bg_color', true ) ?: '#764ba2';
	$reopen_font_size = (int) ( get_post_meta( $bar_id, '_wphash_ab_reopen_font_size', true ) ?: 14 );
	$reopen_font_weight = (int) ( get_post_meta( $bar_id, '_wphash_ab_reopen_font_weight', true ) ?: 500 );
	$reopen_border_radius = (int) ( get_post_meta( $bar_id, '_wphash_ab_reopen_border_radius', true ) ?: 4 );
	$reopen_button_styles = "background: {$reopen_bg_color}; color: {$reopen_color}; padding: 12px 20px; border-radius: {$reopen_border_radius}px; border: none; cursor: pointer; font-size: {$reopen_font_size}px; font-weight: {$reopen_font_weight}; text-decoration: none; white-space: nowrap; transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); --reopen-hover-color: {$reopen_hover_color}; --reopen-hover-bg: {$reopen_hover_bg};";

	// Get behavioral targeting settings
	$time_on_site_enabled = get_post_meta( $bar_id, '_wphash_ab_show_after_time_on_site', true );
	$minimum_time_on_site = (int) ( get_post_meta( $bar_id, '_wphash_ab_minimum_time_on_site', true ) ?: 0 );

	// Get message rotation settings
	$message_rotation_enabled = get_post_meta( $bar_id, '_wphash_ab_message_rotation_enabled', true );
	$message_rotation_interval = (int) ( get_post_meta( $bar_id, '_wphash_ab_message_rotation_interval', true ) ?: 5 );

	// Get schedule settings (used for both inline style and data attributes)
	$schedule_enabled_raw = get_post_meta( $bar_id, '_wphash_ab_schedule_enabled', true );
	$schedule_enabled = ! empty( $schedule_enabled_raw ) && $schedule_enabled_raw !== '0' && $schedule_enabled_raw !== 'false';
	$schedule_timezone = get_post_meta( $bar_id, '_wphash_ab_schedule_timezone', true ) ?: 'site';
	$schedule_start = get_post_meta( $bar_id, '_wphash_ab_schedule_start', true ) ?: '';
	$schedule_end = get_post_meta( $bar_id, '_wphash_ab_schedule_end', true ) ?: '';
	$schedule_time_targeting_raw = get_post_meta( $bar_id, '_wphash_ab_schedule_time_targeting', true );
	$schedule_time_targeting = ! empty( $schedule_time_targeting_raw ) && $schedule_time_targeting_raw !== '0' && $schedule_time_targeting_raw !== 'false';
	$schedule_time_start = get_post_meta( $bar_id, '_wphash_ab_schedule_time_start', true ) ?: '00:00';
	$schedule_time_end = get_post_meta( $bar_id, '_wphash_ab_schedule_time_end', true ) ?: '23:59';
	$schedule_recurring_raw = get_post_meta( $bar_id, '_wphash_ab_schedule_recurring', true );
	$schedule_recurring = ! empty( $schedule_recurring_raw ) && $schedule_recurring_raw !== '0' && $schedule_recurring_raw !== 'false';
	$schedule_recurring_days = get_post_meta( $bar_id, '_wphash_ab_schedule_recurring_days', true ) ?: array();
	if ( is_string( $schedule_recurring_days ) ) {
		$schedule_recurring_days = json_decode( $schedule_recurring_days, true ) ?: array();
	}

	// If visitor timezone schedule is enabled, hide by default (JS will show when schedule is valid)
	if ( $schedule_enabled && $schedule_timezone === 'visitor' ) {
		$style_string .= 'display: none; ';
	}

	// Get all messages for rotation support
	$all_messages = array();
	if ( ! empty( $messages_data ) ) {
		if ( is_string( $messages_data ) ) {
			$decoded = json_decode( $messages_data, true );
			if ( is_array( $decoded ) ) {
				$all_messages = $decoded;
			}
		} elseif ( is_array( $messages_data ) ) {
			$all_messages = $messages_data;
		}
	}

	// Data attributes for JavaScript
	$data_attrs = array(
		'data-bar-id'              => $bar_id,
		'data-position'            => $position,
		'data-sticky'              => $sticky,
		'data-close-enabled'       => $close_enabled ? 'true' : 'false',
		'data-reopen-enabled'      => $reopen_enabled ? 'true' : 'false',
		'data-cookie-duration'     => $cookie_duration,
		'data-cta-enabled'         => $cta_enabled ? 'true' : 'false',
		'data-cta-text'            => $cta_text,
		'data-cta-url'             => $cta_url,
		'data-cta-target'          => $cta_target,
		'data-animation-entry'     => $animation_entry ?: 'none',
		'data-animation-exit'      => $animation_exit ?: 'none',
		'data-animation-duration'  => $animation_duration,
		'data-countdown-enabled'   => $countdown_enabled ? 'true' : 'false',
		'data-countdown-type'      => $countdown_type ?: 'fixed',
		'data-countdown-date'      => ( $countdown_type === 'recurring' || $countdown_type === 'evergreen' ) ? '' : ( $countdown_date ?: '' ),
		'data-countdown-style'     => $countdown_style,
		'data-countdown-position'  => $countdown_position,
		'data-countdown-text-before' => $countdown_text_before,
		'data-countdown-text-after' => $countdown_text_after,
		'data-show-days'           => $countdown_show_days ? 'true' : 'false',
		'data-show-hours'          => $countdown_show_hours ? 'true' : 'false',
		'data-show-minutes'        => $countdown_show_minutes ? 'true' : 'false',
		'data-show-seconds'        => $countdown_show_seconds ? 'true' : 'false',
		'data-countdown-reset-time' => $countdown_reset_time,
		'data-countdown-reset-days' => wp_json_encode( $countdown_reset_days ),
		'data-countdown-timezone'  => $countdown_timezone,
		'data-countdown-duration'  => (int) $countdown_duration,
		'data-coupon-enabled'      => $coupon_enabled ? 'true' : 'false',
		'data-coupon-code'         => $coupon_code ?: '',
		'data-coupon-show-button'  => $coupon_show_button ? 'true' : 'false',
		'data-coupon-auto-copy'    => $coupon_autocopy_on_click ? 'true' : 'false',
		'data-ab-test-enabled'     => ( $variant_id !== false ) ? 'true' : 'false',
		'data-ab-test-variant-id'  => $variant_id ? esc_attr( $variant_id ) : '',
		'data-time-on-site-enabled' => $time_on_site_enabled ? 'true' : 'false',
		'data-minimum-time-on-site' => (int) $minimum_time_on_site,
		'data-message-rotation-enabled' => $message_rotation_enabled && count( $all_messages ) > 1 ? 'true' : 'false',
		'data-message-rotation-interval' => (int) $message_rotation_interval,
		'data-messages'            => wp_json_encode( $all_messages ),
		'data-schedule-enabled'    => $schedule_enabled ? 'true' : 'false',
		'data-schedule-timezone'   => $schedule_timezone,
		'data-schedule-start'      => $schedule_start,
		'data-schedule-end'        => $schedule_end,
		'data-schedule-time-targeting' => $schedule_time_targeting ? 'true' : 'false',
		'data-schedule-time-start' => $schedule_time_start,
		'data-schedule-time-end'   => $schedule_time_end,
		'data-schedule-recurring'  => $schedule_recurring ? 'true' : 'false',
		'data-schedule-recurring-days' => wp_json_encode( $schedule_recurring_days ),
	);

	$data_attr_string = '';
	foreach ( $data_attrs as $attr => $value ) {
		$data_attr_string .= ' ' . $attr . '="' . esc_attr( $value ) . '"';
	}

	// Get close button position (left or right)
	$close_position = get_post_meta( $bar_id, '_wphash_ab_close_position', true ) ?: 'right';

	// Landmark label for assistive tech (does not affect layout). Avoid aria-live on the whole bar — countdown updates would be noisy.
	$announcement_region_label = trim( wp_strip_all_tags( $bar->post_title ) );
	if ( '' === $announcement_region_label ) {
		$announcement_region_label = __( 'Site announcement', 'hashbar' );
	}

	// Get Custom CSS
	$custom_css = get_post_meta( $bar_id, '_wphash_ab_custom_css', true );

	// Pre-paint body spacer only needed for bottom-sticky (position:fixed, out of flow).
	// Top-sticky uses position:sticky which is in flow, so layout already reserves space.
	$needs_body_spacer = $is_sticky && $position === 'bottom';
	$pre_spacer_dur    = ( $needs_body_spacer && $animation_entry && $animation_entry !== 'none' ) ? max( 0, (int) $animation_duration ) : 0;
	?>


	<?php
	// Wrapper styling:
	// - non-sticky: needs box for the entry-animation height transition (overflow:hidden;height:0).
	// - top-sticky: display:contents so the bar's containing block escapes to <body>; otherwise
	//   position:sticky would only stick within this short wrapper's bounds.
	// - bottom-sticky: position:fixed bar, wrapper inert, no special styling.
	if ( ! $is_sticky ) {
		$wrapper_inline_style = ' style="overflow:hidden;height:0"';
	} elseif ( $is_sticky && $position === 'top' ) {
		$wrapper_inline_style = ' style="display:contents"';
	} else {
		$wrapper_inline_style = '';
	}
	?>
	<div class="hashbar-announcement-bar-wrapper" data-bar-id="<?php echo esc_attr( $bar_id ); ?>"<?php echo $wrapper_inline_style; // phpcs:ignore ?>>
		<?php if ( ! empty( $custom_css ) ) : ?>
			<style>
				<?php echo wp_strip_all_tags( $custom_css ); ?>
			</style>
		<?php endif; ?>

	<div class="hashbar-announcement-bar" id="hashbar-bar-<?php echo esc_attr( $bar_id ); ?>" role="region" aria-label="<?php echo esc_attr( $announcement_region_label ); ?>" style="<?php echo esc_attr( $style_string ); ?>" data-hashbar-announcement="<?php echo esc_attr( $bar_id ); ?>" <?php echo $data_attr_string; // phpcs:ignore ?>>
		<?php if ( $close_enabled && $close_position === 'left' ) : ?>
			<button type="button" class="hashbar-announcement-close" style="<?php echo esc_attr( $close_button_styles ); ?>" aria-label="<?php echo esc_attr__( 'Close announcement', 'hashbar' ); ?>" title="<?php echo esc_attr__( 'Close', 'hashbar' ); ?>" data-hashbar-close="true">
				<?php echo esc_html( $close_text ); ?>
			</button>
		<?php endif; ?>

		<!-- Message and Countdown Wrapper -->
		<?php
		// Determine content justify-content based on countdown position and text alignment
		$content_justify = 'center'; // default
		if ( $countdown_enabled && ( $countdown_position === 'before' || $countdown_position === 'top' ) ) {
			$content_justify = 'flex-start';
		} else {
			// Use text alignment mapping for other cases
			if ( $text_align === 'left' ) {
				$content_justify = 'flex-start';
			} elseif ( $text_align === 'right' ) {
				$content_justify = 'flex-end';
			} elseif ( $text_align === 'center' ) {
				$content_justify = 'center';
			}
		}
		?>
		<div class="hashbar-announcement-content" style="flex: 1; display: flex; flex-direction: <?php echo ( ( $countdown_enabled && ( $countdown_position === 'left' || $countdown_position === 'right' ) ) && !( $countdown_position === 'top' || $countdown_position === 'below' ) ) || ( $coupon_enabled && $countdown_position !== 'inline' && !( $countdown_position === 'top' || $countdown_position === 'below' ) ) ? 'row' : 'column'; ?>; align-items: center; justify-content: <?php echo esc_attr( $content_justify ); ?>; gap: 8px; flex-wrap: wrap;">
			<!-- Before/Top Countdown (above message) -->
			<?php if ( $countdown_enabled && ( $countdown_position === 'before' || $countdown_position === 'top' ) ) : ?>
				<div style="padding: 4px 0; display: flex; align-items: <?php echo esc_attr( $countdown_align ); ?>; gap: 8px; flex-wrap: wrap;">
					<?php if ( ! empty( $countdown_text_before ) ) : ?>
						<span style="font-size: 0.9em;"><?php echo esc_html( $countdown_text_before ); ?></span>
					<?php endif; ?>
					<?php echo hashbar_generate_countdown_timer( $countdown_style, $countdown_show_days, $countdown_show_hours, $countdown_show_minutes, $countdown_show_seconds ); // phpcs:ignore ?>
					<?php if ( ! empty( $countdown_text_after ) ) : ?>
						<span style="font-size: 0.9em;"><?php echo esc_html( $countdown_text_after ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Left/Right/Inline Wrapper - Contains left countdown + message + right countdown on same line -->
				<div class="hashbar-announcement-message" style="display: flex; align-items: <?php echo esc_attr( $countdown_align ); ?>; justify-content: center; gap: 8px; flex-wrap: wrap;">
				<!-- Left Countdown -->
				<?php if ( $countdown_enabled && $countdown_position === 'left' ) : ?>
					<div style="display: flex; align-items: <?php echo esc_attr( $countdown_align ); ?>; gap: 4px; white-space: nowrap; flex-shrink: 0;">
						<?php if ( ! empty( $countdown_text_before ) ) : ?>
							<span style="font-size: 0.9em; white-space: nowrap;"><?php echo esc_html( $countdown_text_before ); ?></span>
						<?php endif; ?>
						<?php echo hashbar_generate_countdown_timer( $countdown_style, $countdown_show_days, $countdown_show_hours, $countdown_show_minutes, $countdown_show_seconds ); // phpcs:ignore ?>
						<?php if ( ! empty( $countdown_text_after ) ) : ?>
							<span style="font-size: 0.9em; white-space: nowrap;"><?php echo esc_html( $countdown_text_after ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<!-- Message Container -->
				<div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
					<?php if ( ! empty( $message ) ) : ?>
						<p style="margin: 0;" class="hashbar-no-emoji-convert" data-no-emoji="true">
							<?php
							// Allow inline styles and classes for custom design
							$allowed_html = wp_kses_allowed_html( 'post' );
							foreach ( $allowed_html as $tag => $attributes ) {
								$allowed_html[ $tag ]['style'] = true;
								$allowed_html[ $tag ]['class'] = true;
							}
							echo wp_kses( $message, $allowed_html ); 
							?>
							<?php if ( $countdown_enabled && $countdown_position === 'inline' ) : ?>
								<?php echo hashbar_generate_countdown_timer( $countdown_style, $countdown_show_days, $countdown_show_hours, $countdown_show_minutes, $countdown_show_seconds, 'margin-left: 12px;' ); // phpcs:ignore ?>
							<?php endif; ?>
							<?php if ( $coupon_enabled && ! empty( $coupon_code ) && $countdown_position === 'inline' ) : ?>
								<span style="margin-left: 12px; padding: 8px 16px; background: rgba(255,255,255,0.2); border: 2px dashed rgba(255,255,255,0.5); border-radius: 6px; cursor: pointer; font-family: 'Courier New', monospace; font-weight: bold; font-size: 14px; display: inline-flex; align-items: center; gap: 12px; transition: all 0.2s ease; color: inherit;" class="hashbar-coupon-inline" data-coupon-code="<?php echo esc_attr( $coupon_code ); ?>" data-coupon-show-button="<?php echo $coupon_show_button ? 'true' : 'false'; ?>" data-copy-text="<?php echo esc_attr( $coupon_copy_button_text ); ?>" data-copied-text="<?php echo esc_attr( $coupon_copied_button_text ); ?>" data-autocopy-on-click="<?php echo $coupon_autocopy_on_click ? 'true' : 'false'; ?>" title="Click to copy" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'"><?php echo esc_html( $coupon_code ); ?><?php if ( $coupon_show_button ) : ?><span style="display: inline-flex; align-items: center; gap: 4px; font-family: system-ui, -apple-system, sans-serif; font-size: 12px; font-weight: normal;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; opacity: 0.8; transition: opacity 0.2s ease; flex-shrink: 0;">
									<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
									<rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
								</svg><?php echo esc_html( $coupon_copy_button_text ); ?></span><?php endif; ?></span>
							<?php endif; ?>
						</p>
					<?php endif; ?>
				</div>

				<!-- Right Countdown -->
				<?php if ( $countdown_enabled && $countdown_position === 'right' ) : ?>
					<div style="display: flex; align-items: <?php echo esc_attr( $countdown_align ); ?>; justify-content: center; gap: 4px;">
						<?php if ( ! empty( $countdown_text_before ) ) : ?>
							<span style="font-size: 0.9em; white-space: nowrap;"><?php echo esc_html( $countdown_text_before ); ?></span>
						<?php endif; ?>
						<?php echo hashbar_generate_countdown_timer( $countdown_style, $countdown_show_days, $countdown_show_hours, $countdown_show_minutes, $countdown_show_seconds ); // phpcs:ignore ?>
						<?php if ( ! empty( $countdown_text_after ) ) : ?>
							<span style="font-size: 0.9em; white-space: nowrap;"><?php echo esc_html( $countdown_text_after ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

		<!-- After Countdown (below message) -->
		<?php if ( $countdown_enabled && $countdown_position === 'after' ) : ?>
			<div style="padding: 4px 0; display: flex; align-items: <?php echo esc_attr( $countdown_align ); ?>; gap: 8px; flex-wrap: wrap;">
				<?php if ( ! empty( $countdown_text_before ) ) : ?>
					<span style="font-size: 0.9em;"><?php echo esc_html( $countdown_text_before ); ?></span>
				<?php endif; ?>
				<?php echo hashbar_generate_countdown_timer( $countdown_style, $countdown_show_days, $countdown_show_hours, $countdown_show_minutes, $countdown_show_seconds ); // phpcs:ignore ?>
				<?php if ( ! empty( $countdown_text_after ) ) : ?>
					<span style="font-size: 0.9em;"><?php echo esc_html( $countdown_text_after ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<!-- Below Countdown (below message with border) -->
		<?php if ( $countdown_enabled && $countdown_position === 'below' ) : ?>
			<div style="padding: 4px 0; display: flex; align-items: <?php echo esc_attr( $countdown_align ); ?>; justify-content: center; gap: 8px; width: 100%; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 8px; flex-wrap: wrap;">
				<?php if ( ! empty( $countdown_text_before ) ) : ?>
					<span style="font-size: 0.9em;"><?php echo esc_html( $countdown_text_before ); ?></span>
				<?php endif; ?>
				<?php echo hashbar_generate_countdown_timer( $countdown_style, $countdown_show_days, $countdown_show_hours, $countdown_show_minutes, $countdown_show_seconds ); // phpcs:ignore ?>
				<?php if ( ! empty( $countdown_text_after ) ) : ?>
					<span style="font-size: 0.9em;"><?php echo esc_html( $countdown_text_after ); ?></span>
				<?php endif; ?>
			</div>
		<?php endif; ?>

			<!-- Coupon Display (only when not inline with countdown) -->
			<?php if ( $coupon_enabled && ! empty( $coupon_code ) && $countdown_position !== 'inline' ) : ?>
				<div class="hashbar-coupon-display" style="margin-top: 8px; padding: 8px 16px; background: rgba(255,255,255,0.2); border: 2px dashed rgba(255,255,255,0.5); border-radius: 6px; display: flex; align-items: center; justify-content: center; gap: 12px; cursor: pointer; transition: all 0.2s ease;" data-coupon-code="<?php echo esc_attr( $coupon_code ); ?>" data-copy-text="<?php echo esc_attr( $coupon_copy_button_text ); ?>" data-copied-text="<?php echo esc_attr( $coupon_copied_button_text ); ?>" data-autocopy-on-click="<?php echo $coupon_autocopy_on_click ? 'true' : 'false'; ?>" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
					<code style="font-weight: bold; font-family: 'Courier New', monospace; font-size: 14px; color: inherit;" title="Click to copy"><?php echo esc_html( $coupon_code ); ?></code>
					<?php if ( $coupon_show_button ) : ?>
						<span class="hashbar-coupon-copy" title="Click to copy" style="cursor: pointer; display: inline-flex; align-items: center; gap: 4px; opacity: 0.8; transition: opacity 0.2s ease; flex-shrink: 0; font-family: system-ui, -apple-system, sans-serif; font-size: 12px; font-weight: normal;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle;">
								<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
								<rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
							</svg><?php echo esc_html( $coupon_copy_button_text ); ?>
						</span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $cta_enabled ) : ?>
			<!-- Debug: CTA Button Styles: <?php echo esc_attr( $cta_button_styles ); ?> -->
			<a href="<?php echo esc_attr( $cta_url ?: '#' ); ?>" class="hashbar-announcement-cta" style="<?php echo esc_attr( $cta_button_styles ); ?>" target="<?php echo esc_attr( $cta_target ?: '_self' ); ?>" data-hashbar-cta="true">
				<?php echo esc_html( $cta_text ?: 'Learn More' ); ?>
			</a>
		<?php endif; ?>

		<?php if ( $close_enabled && $close_position === 'right' ) : ?>
			<!-- Debug: Close Button Styles: <?php echo esc_attr( $close_button_styles ); ?> -->
			<button type="button" class="hashbar-announcement-close" style="<?php echo esc_attr( $close_button_styles ); ?>" aria-label="<?php echo esc_attr__( 'Close announcement', 'hashbar' ); ?>" title="<?php echo esc_attr__( 'Close', 'hashbar' ); ?>" data-hashbar-close="true">
				<?php echo esc_html( $close_text ); ?>
			</button>
		<?php endif; ?>
	</div>
	</div>
	<?php if ( $needs_body_spacer ) : ?>
	<style id="hashbar-pre-spacer-<?php echo esc_attr( $bar_id ); ?>"></style>
	<script>(function(){
		var dur = <?php echo (int) $pre_spacer_dur; ?>;
		var bar = document.getElementById('hashbar-bar-<?php echo esc_js( $bar_id ); ?>');
		if (!bar) return;
		var h = bar.offsetHeight;
		if (!h) return;
		window.hashbarPreSpacers = window.hashbarPreSpacers || { bottom: {} };
		window.hashbarPreSpacers.bottom['<?php echo esc_js( $bar_id ); ?>'] = h;
		var total = 0, reg = window.hashbarPreSpacers.bottom;
		for (var k in reg) total += reg[k];
		var style = document.getElementById('hashbar-pre-spacer-<?php echo esc_js( $bar_id ); ?>');
		var css;
		if (dur > 0) {
			css = '@keyframes hashbar-pre-spacer-bottom{from{padding-bottom:0}to{padding-bottom:' + total + 'px}}body{padding-bottom:' + total + 'px;animation:hashbar-pre-spacer-bottom ' + dur + 'ms ease-out}';
		} else {
			css = 'body{padding-bottom:' + total + 'px}';
		}
		style.textContent = css;
	})();</script>
	<?php elseif ( ! $is_sticky ) : ?>
	<script>(function(){var w=document.currentScript.previousElementSibling;w.style.height='auto';var h=w.offsetHeight;w.style.height='0px';w.offsetHeight;w.style.transition='height <?php echo esc_js( $animation_duration / 1000 ); ?>s ease';w.style.height=h+'px';setTimeout(function(){w.style.overflow='';w.style.height='';w.style.transition='';},<?php echo esc_js( $animation_duration ); ?>);})();</script>
	<?php elseif ( $is_sticky && $position === 'top' ) : ?>
	<script>(function(){
		var bar = document.getElementById('hashbar-bar-<?php echo esc_js( $bar_id ); ?>');
		if (!bar) return;
		// Measure target height by temporarily restoring min-height + auto height, then snap back to 0.
		bar.style.height = 'auto';
		bar.style.minHeight = '<?php echo (int) $height; ?>px';
		var h = bar.offsetHeight;
		bar.style.height = '0px';
		bar.style.minHeight = '0';
		bar.offsetHeight; // force reflow before applying transition
		var dur = <?php echo (int) $animation_duration; ?>;
		var durSec = dur / 1000;
		bar.style.transition = 'height ' + durSec + 's ease-out, opacity ' + durSec + 's ease-out';
		bar.style.height = h + 'px';
		bar.style.opacity = '1';
		setTimeout(function(){
			bar.style.transition = '';
			bar.style.height = '';
			bar.style.minHeight = '<?php echo (int) $height; ?>px';
			bar.style.overflow = '';
		}, dur);
	})();</script>
	<?php endif; ?>

	<?php if ( $reopen_enabled ) : ?>
		<div class="hashbar-reopen-button" data-bar-id="<?php echo esc_attr( $bar_id ); ?>" style="position: fixed; bottom: 20px; right: 20px; z-index: 99999; display: none;">
			<button type="button" class="hashbar-reopen-btn" style="<?php echo esc_attr( $reopen_button_styles ); ?>">
				<?php echo esc_html( $reopen_text ); ?>
			</button>
		</div>
	<?php endif; ?>
	<?php
}

/**
 * Generate countdown timer HTML based on style
 *
 * @param string $style The countdown style (simple, digital, or circular).
 * @param bool   $show_days Show days in countdown.
 * @param bool   $show_hours Show hours in countdown.
 * @param bool   $show_minutes Show minutes in countdown.
 * @param bool   $show_seconds Show seconds in countdown.
 * @param string $extra_style Optional extra inline styles.
 * @return string HTML markup for the countdown timer.
 */
function hashbar_generate_countdown_timer( $style, $show_days, $show_hours, $show_minutes, $show_seconds, $extra_style = '' ) {
	$data_attrs = array(
		'data-countdown-style' => $style,
		'data-show-days' => $show_days ? 'true' : 'false',
		'data-show-hours' => $show_hours ? 'true' : 'false',
		'data-show-minutes' => $show_minutes ? 'true' : 'false',
		'data-show-seconds' => $show_seconds ? 'true' : 'false',
	);

	$data_attr_string = '';
	foreach ( $data_attrs as $attr => $value ) {
		$data_attr_string .= ' ' . $attr . '="' . esc_attr( $value ) . '"';
	}

	$base_style = 'font-weight: bold;' . ( $extra_style ? ' ' . $extra_style : '' );

	// Box styles with multiple units
	if ( in_array( $style, array( 'circular', 'box' ), true ) ) {
		$output = '<div class="hashbar-countdown-timer-wrapper" style="display: flex; align-items: flex-start; gap: 8px; flex-wrap: wrap; justify-content: center;"' . $data_attr_string . '>';

		if ( 'circular' === $style ) {
			// Circular style with CSS border-radius 100%
			if ( $show_days ) {
				$output .= '<div class="hb-countdown-unit hb-countdown-days hb-countdown-circular">
					<div class="countdown-number">00</div>
					<div class="countdown-label">D</div>
				</div>';
			}

			if ( $show_hours ) {
				$output .= '<div class="hb-countdown-unit hb-countdown-hours hb-countdown-circular">
					<div class="countdown-number">00</div>
					<div class="countdown-label">H</div>
				</div>';
			}

			if ( $show_minutes ) {
				$output .= '<div class="hb-countdown-unit hb-countdown-minutes hb-countdown-circular">
					<div class="countdown-number">00</div>
					<div class="countdown-label">M</div>
				</div>';
			}

			if ( $show_seconds ) {
				$output .= '<div class="hb-countdown-unit hb-countdown-seconds hb-countdown-circular">
					<div class="countdown-number">00</div>
					<div class="countdown-label">S</div>
				</div>';
			}
		} else {
			// Box style with regular divs
			if ( $show_days ) {
				$output .= '<div class="hb-countdown-unit hb-countdown-days">
					<div class="hb-countdown-box">
						<div class="countdown-number">00</div>
					</div>
					<div class="countdown-label">Day</div>
				</div>';
			}

			if ( $show_hours ) {
				$output .= '<div class="hb-countdown-unit hb-countdown-hours">
					<div class="hb-countdown-box">
						<div class="countdown-number">00</div>
					</div>
					<div class="countdown-label">Hour</div>
				</div>';
			}

			if ( $show_minutes ) {
				$output .= '<div class="hb-countdown-unit hb-countdown-minutes">
					<div class="hb-countdown-box">
						<div class="countdown-number">00</div>
					</div>
					<div class="countdown-label">Minute</div>
				</div>';
			}

			if ( $show_seconds ) {
				$output .= '<div class="hb-countdown-unit hb-countdown-seconds">
					<div class="hb-countdown-box">
						<div class="countdown-number">00</div>
					</div>
					<div class="countdown-label">Second</div>
				</div>';
			}
		}

		$output .= '</div>';
		return $output;
	} else {
		return '<span class="countdown-' . esc_attr( $style ) . ' hashbar-countdown-timer-text"' . $data_attr_string . ' style="' . esc_attr( $base_style ) . '"></span>';
	}
}

/**
 * Check customer segmentation targeting
 *
 * @param int $bar_id The announcement bar post ID.
 * @return bool Whether the current user matches customer segmentation rules.
 */
function hashbar_check_customer_segmentation( $bar_id ) {
	$segmentation_enabled = get_post_meta( $bar_id, '_wphash_ab_enable_customer_segmentation', true );

	// If segmentation is not enabled, show to all users
	if ( ! $segmentation_enabled || $segmentation_enabled === 'false' ) {
		return true;
	}

	$target_logged_in = get_post_meta( $bar_id, '_wphash_ab_target_logged_in_customers', true );
	$target_guests = get_post_meta( $bar_id, '_wphash_ab_target_guest_visitors', true );

	// If segmentation is enabled but neither option is selected, show to all users
	if ( ! $target_logged_in && ! $target_guests ) {
		return true;
	}

	$is_logged_in = is_user_logged_in();

	// If targeting logged-in customers and user is logged in, show
	if ( $target_logged_in && $is_logged_in ) {
		return true;
	}

	// If targeting guest visitors and user is not logged in, show
	if ( $target_guests && ! $is_logged_in ) {
		return true;
	}

	// User doesn't match any segmentation rules
	return false;
}

/**
 * Check behavioral targeting (time on site)
 *
 * NOTE: The actual time-on-site check is done client-side via JavaScript.
 * This function only checks if the feature is enabled and validates the configuration.
 * The bar will be shown/hidden by JavaScript based on elapsed time.
 *
 * @param int $bar_id The announcement bar post ID.
 * @return bool Whether behavioral targeting is properly configured.
 */
function hashbar_check_behavioral_targeting( $bar_id ) {
	$time_on_site_enabled = get_post_meta( $bar_id, '_wphash_ab_show_after_time_on_site', true );

	// If feature is not enabled, always show
	if ( ! $time_on_site_enabled || $time_on_site_enabled === 'false' ) {
		return true;
	}

	// Get the minimum time threshold
	$minimum_time = get_post_meta( $bar_id, '_wphash_ab_minimum_time_on_site', true );

	// If no minimum time set or time is 0, show immediately
	if ( empty( $minimum_time ) || (int) $minimum_time === 0 ) {
		return true;
	}

	// Feature is enabled and properly configured
	// Actual time checking is handled by client-side JavaScript
	return true;
}

/**
 * Check if a color is dark or light
 *
 * @param string $hex_color The hex color code.
 * @return bool True if color is dark, false if light.
 */
function hashbar_is_dark_color( $hex_color ) {
	$hex_color = ltrim( $hex_color, '#' );

	// Convert hex to RGB
	if ( strlen( $hex_color ) === 6 ) {
		$r = hexdec( substr( $hex_color, 0, 2 ) );
		$g = hexdec( substr( $hex_color, 2, 2 ) );
		$b = hexdec( substr( $hex_color, 4, 2 ) );
	} else {
		return true; // Default to dark
	}

	// Calculate brightness (matching React's getButtonColor formula)
	// Returns true if dark, false if bright
	$brightness = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 1000;

	return $brightness <= 0.155;
}
