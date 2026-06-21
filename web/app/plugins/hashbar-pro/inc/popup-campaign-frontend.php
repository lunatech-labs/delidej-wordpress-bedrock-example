<?php
namespace Hashbar\Pro\PopupCampaign;

/**
 * Frontend Popup Campaign Display and Functionality
 *
 * Handles rendering of popup campaigns on the frontend with all
 * targeting, scheduling, triggers, and interactive features.
 *
 * @package HashBar Pro
 * @since 2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Popup Campaign Frontend Class
 *
 * Manages the display and rendering of popup campaigns on the frontend.
 * This class can be extended in Pro version for additional features.
 */
class Frontend {

	/**
	 * Singleton instance
	 *
	 * @var Frontend|null
	 */
	private static $instance = null;

	/**
	 * Active popups cache
	 *
	 * @var array|null
	 */
	protected $active_popups = null;

	/**
	 * Get singleton instance
	 *
	 * @return Frontend
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	protected function init_hooks() {
		add_action( 'wp', array( $this, 'init' ) );
	}

	/**
	 * Initialize frontend popup campaign rendering
	 */
	public function init() {
		if ( is_admin() ) {
			return;
		}

		// Get active popups
		$this->active_popups = $this->get_active_popups();

		if ( empty( $this->active_popups ) ) {
			return;
		}

		// Enqueue frontend assets
		$this->enqueue_assets();

		// Add popups to footer
		add_action( 'wp_footer', array( $this, 'render_popups' ), 999 );
	}

	/**
	 * Get active popup campaigns
	 *
	 * @return array Array of popup post objects.
	 */
	protected function get_active_popups() {
		$popups = get_posts( array(
			'post_type'      => 'wphash_popup',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => array(
				array(
					'key'     => '_wphash_popup_status',
					'value'   => 'active',
					'compare' => '=',
				),
			),
		) );

		// Filter out popups with expired countdowns (when action is hide_popup)
		$popups = array_filter( $popups, array( $this, 'check_countdown_expiry' ) );

		return array_values( $popups ); // Re-index array
	}

	/**
	 * Check countdown expiry for array_filter (accepts WP_Post object)
	 *
	 * @param \WP_Post|int $popup The popup post object or ID.
	 * @return bool True if popup should be shown, false if expired and should be hidden.
	 */
	protected function check_countdown_expiry( $popup ) {
		$popup_id = is_object( $popup ) ? $popup->ID : $popup;

		// Check if countdown is enabled
		$countdown_enabled = get_post_meta( $popup_id, '_wphash_popup_countdown_enabled', true );
		if ( ! $this->is_truthy( $countdown_enabled ) ) {
			return true; // Countdown not enabled, allow display
		}

		// Check if expired action is hide_popup
		$expired_action = get_post_meta( $popup_id, '_wphash_popup_countdown_expired_action', true ) ?: 'hide_popup';
		if ( 'hide_popup' !== $expired_action ) {
			return true; // Expired action is not hide, allow display (JS will handle message/redirect)
		}

		// Check countdown type - only check fixed_date on server
		// Evergreen and daily types are user-specific and handled by JavaScript
		$countdown_type = get_post_meta( $popup_id, '_wphash_popup_countdown_type', true ) ?: 'fixed_date';
		// Handle both 'fixed_date' and 'fixed' values (JavaScript accepts both)
		if ( 'fixed_date' !== $countdown_type && 'fixed' !== $countdown_type ) {
			return true; // Not fixed date, let JavaScript handle it
		}

		// Get the end date (stored in ISO format: 2026-01-27T10:00:00.000Z)
		$end_date = get_post_meta( $popup_id, '_wphash_popup_countdown_end_date', true );
		if ( empty( $end_date ) ) {
			return true; // No end date set, allow display
		}

		// Get countdown timezone setting
		$countdown_timezone = get_post_meta( $popup_id, '_wphash_popup_countdown_timezone', true ) ?: 'site';

		// If visitor timezone, we can't check on server - let JavaScript handle it
		if ( 'visitor' === $countdown_timezone ) {
			return true;
		}

		try {
			// Current UTC timestamp
			$current_timestamp = time();

			// Parse the end date - ISO 8601 format: 2026-01-27T10:00:00.000Z
			// strtotime handles ISO 8601 format and returns UTC timestamp
			$end_timestamp = strtotime( $end_date );

			// Fallback: try DateTime if strtotime fails
			if ( false === $end_timestamp || -1 === $end_timestamp ) {
				$end_datetime = new \DateTime( $end_date, new \DateTimeZone( 'UTC' ) );
				$end_timestamp = $end_datetime->getTimestamp();
			}

			// If we still can't get a valid timestamp, allow display
			if ( ! $end_timestamp || $end_timestamp <= 0 ) {
				return true;
			}

			// If countdown has expired, don't display popup
			if ( $current_timestamp > $end_timestamp ) {
				return false;
			}
		} catch ( \Exception $e ) {
			// If there's any error parsing dates, allow display and let JS handle it
			return true;
		}

		return true;
	}

	/**
	 * Enqueue frontend popup campaign assets
	 */
	protected function enqueue_assets() {
		// Styles
		wp_enqueue_style(
			'hashbar-popup-frontend',
			HASHBAR_WPNBP_URI . '/assets/css/popup-campaign-frontend.css',
			array(),
			HASHBAR_WPNBP_VERSION
		);

		// Scripts
		wp_enqueue_script(
			'hashbar-popup-frontend',
			HASHBAR_WPNBP_URI . '/assets/js/popup-campaign-frontend.js',
			array( 'jquery' ),
			HASHBAR_WPNBP_VERSION,
			true
		);

		// Get site timezone offset in hours for countdown calculations
		$site_timezone = wp_timezone();
		$now = new \DateTime( 'now', $site_timezone );
		$site_timezone_offset = $now->getOffset() / 3600; // Convert seconds to hours

		// Localize script with data
		wp_localize_script(
			'hashbar-popup-frontend',
			'HashbarPopupData',
			array(
				'restUrl'            => rest_url( 'hashbar/v1/' ),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
				'siteTimezoneOffset' => $site_timezone_offset,
			)
		);

		// Enqueue external form plugin styles if used in any popup
		$this->enqueue_external_form_styles();
	}

	/**
	 * Enqueue styles and scripts for external form plugins used in popups
	 */
	protected function enqueue_external_form_styles() {
		foreach ( $this->active_popups as $popup ) {
			$content_type = get_post_meta( $popup->ID, '_wphash_popup_content_type', true );

			// HT Contact Form
			if ( 'ht_form' === $content_type ) {
				// Enqueue styles
				if ( wp_style_is( 'ht-form', 'registered' ) ) {
					wp_enqueue_style( 'ht-form' );
				}
				if ( wp_style_is( 'ht-select', 'registered' ) ) {
					wp_enqueue_style( 'ht-select' );
				}
				// Enqueue axios (required for HT Form AJAX)
				if ( wp_script_is( 'ht-axios', 'registered' ) ) {
					wp_enqueue_script( 'ht-axios' );
				}
				// Enqueue main form script for AJAX submission
				if ( wp_script_is( 'ht-form', 'registered' ) ) {
					wp_enqueue_script( 'ht-form' );
					// Localize script with required data for AJAX submission
					wp_localize_script(
						'ht-form',
						'ht_form',
						array(
							'ajaxurl'    => admin_url( 'admin-ajax.php' ),
							'nonce'      => wp_create_nonce( 'ht_form_ajax_nonce' ),
							'rest_url'   => rest_url(),
							'rest_nonce' => wp_create_nonce( 'wp_rest' ),
							'plugin_url' => defined( 'HTCONTACTFORM_PL_URL' ) ? HTCONTACTFORM_PL_URL : '',
							'i18n'       => array(
								'required' => __( 'This field is required.', 'hashbar' ),
								'email'    => __( 'Please enter a valid email address.', 'hashbar' ),
							),
						)
					);
				}
			}

			// Contact Form 7
			if ( 'cf7' === $content_type ) {
				if ( wp_style_is( 'contact-form-7', 'registered' ) ) {
					wp_enqueue_style( 'contact-form-7' );
				}
				if ( wp_script_is( 'contact-form-7', 'registered' ) ) {
					wp_enqueue_script( 'contact-form-7' );
				}
			}

			// WPForms
			if ( 'wpforms' === $content_type ) {
				if ( wp_style_is( 'wpforms-full', 'registered' ) ) {
					wp_enqueue_style( 'wpforms-full' );
				}
				if ( wp_script_is( 'wpforms', 'registered' ) ) {
					wp_enqueue_script( 'wpforms' );
				}
			}

			// Fluent Forms
			if ( 'fluent_forms' === $content_type ) {
				if ( wp_style_is( 'fluentform-public-default', 'registered' ) ) {
					wp_enqueue_style( 'fluentform-public-default' );
				}
				if ( wp_script_is( 'fluent-form-submission', 'registered' ) ) {
					wp_enqueue_script( 'fluent-form-submission' );
				}
			}
		}
	}

	/**
	 * Render all active popup campaigns
	 */
	public function render_popups() {
		if ( empty( $this->active_popups ) ) {
			return;
		}

		echo '<div class="hashbar-popup-campaigns-container">';

		foreach ( $this->active_popups as $popup ) {
			// Check if popup should be displayed based on targeting rules
			if ( $this->should_display( $popup->ID ) ) {
				$this->render_single_popup( $popup );
			}
		}

		echo '</div>';
	}

	/**
	 * Check if popup campaign should be displayed based on targeting rules
	 *
	 * @param int $popup_id The popup campaign post ID.
	 * @return bool Whether the popup should be displayed.
	 */
	public function should_display( $popup_id ) {
		// Check schedule
		if ( ! $this->check_schedule( $popup_id ) ) {
			return false;
		}

		// Note: Countdown expiry check moved to get_active_popups() for earlier filtering

		// Check device targeting
		if ( ! $this->check_device_targeting( $popup_id ) ) {
			return false;
		}

		// Check page targeting
		if ( ! $this->check_page_targeting( $popup_id ) ) {
			return false;
		}

		// Check user status targeting
		if ( ! $this->check_user_targeting( $popup_id ) ) {
			return false;
		}

		// Check referrer targeting
		if ( ! $this->check_referrer_targeting( $popup_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if popup schedule allows display
	 *
	 * @param int $popup_id The popup campaign post ID.
	 * @return bool Whether the schedule allows display.
	 */
	protected function check_schedule( $popup_id ) {
		$schedule_enabled = get_post_meta( $popup_id, '_wphash_popup_schedule_enabled', true );

		if ( ! $this->is_truthy( $schedule_enabled ) ) {
			return true; // Schedule not enabled, always display
		}

		// Get timezone setting
		$timezone_setting = get_post_meta( $popup_id, '_wphash_popup_schedule_timezone', true ) ?: 'site';

		// If visitor timezone is selected, skip server-side day/time checks
		// These will be handled by JavaScript on the client side
		$use_visitor_timezone = ( $timezone_setting === 'visitor' );

		// Get WordPress site timezone for server-side checks
		$timezone = wp_timezone_string();

		try {
			$tz = new \DateTimeZone( $timezone );
			$now = new \DateTime( 'now', $tz );
		} catch ( \Exception $e ) {
			$now = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
			$tz = new \DateTimeZone( 'UTC' );
		}

		// For visitor timezone, skip server-side date/time checks - handled by JavaScript
		if ( $use_visitor_timezone ) {
			return true;
		}

		// Check start date & time (site timezone)
		$start_date = get_post_meta( $popup_id, '_wphash_popup_schedule_start_date', true );
		if ( ! empty( $start_date ) ) {
			try {
				$start_datetime = new \DateTime( $start_date, $tz );
				if ( $now < $start_datetime ) {
					return false; // Before start date/time
				}
			} catch ( \Exception $e ) {
				// Invalid date format, skip this check
			}
		}

		// Check end date & time (site timezone)
		$end_date = get_post_meta( $popup_id, '_wphash_popup_schedule_end_date', true );
		if ( ! empty( $end_date ) ) {
			try {
				$end_datetime = new \DateTime( $end_date, $tz );
				if ( $now > $end_datetime ) {
					return false; // After end date/time
				}
			} catch ( \Exception $e ) {
				// Invalid date format, skip this check
			}
		}

		// Check days of week (only if days restriction is enabled) - Site timezone
		$days_enabled = get_post_meta( $popup_id, '_wphash_popup_schedule_days_enabled', true );
		if ( $this->is_truthy( $days_enabled ) ) {
			$schedule_days = get_post_meta( $popup_id, '_wphash_popup_schedule_days', true );

			if ( ! empty( $schedule_days ) ) {
				if ( is_string( $schedule_days ) ) {
					$schedule_days = json_decode( $schedule_days, true );
				}

				if ( is_array( $schedule_days ) && ! empty( $schedule_days ) ) {
					$current_day = strtolower( $now->format( 'l' ) );
					$schedule_days_lower = array_map( 'strtolower', $schedule_days );

					if ( ! in_array( $current_day, $schedule_days_lower, true ) ) {
						return false; // Not an active day
					}
				}
			} else {
				// Days restriction enabled but no days selected - don't display
				return false;
			}
		}

		// Check time of day (only if time restriction is enabled) - Site timezone
		$time_enabled = get_post_meta( $popup_id, '_wphash_popup_schedule_time_enabled', true );
		if ( $this->is_truthy( $time_enabled ) ) {
			$time_start = get_post_meta( $popup_id, '_wphash_popup_schedule_time_start', true );
			$time_end = get_post_meta( $popup_id, '_wphash_popup_schedule_time_end', true );

			$current_time_str = $now->format( 'H:i' );
			$time_start = ! empty( $time_start ) ? $time_start : '00:00';
			$time_end = ! empty( $time_end ) ? $time_end : '23:59';

			// Simple 24-hour check: current time must be between start and end
			if ( $current_time_str < $time_start || $current_time_str > $time_end ) {
				return false; // Outside time window
			}
		}

		return true;
	}

	/**
	 * Check popup device targeting
	 *
	 * @param int $popup_id The popup campaign post ID.
	 * @return bool Whether the current device is targeted.
	 */
	protected function check_device_targeting( $popup_id ) {
		$devices = get_post_meta( $popup_id, '_wphash_popup_target_devices', true );

		if ( empty( $devices ) ) {
			return true; // No device targeting, display on all
		}

		// Handle different array formats
		if ( is_string( $devices ) ) {
			$decoded = json_decode( $devices, true );
			if ( is_array( $decoded ) ) {
				$devices = $decoded;
			} else {
				$devices = explode( ',', $devices );
			}
		}

		if ( ! is_array( $devices ) || empty( $devices ) ) {
			return true;
		}

		// Detect device type
		$current_device = $this->detect_device();

		return in_array( $current_device, $devices, true );
	}

	/**
	 * Detect current device type (mobile, tablet, desktop)
	 *
	 * @return string Device type.
	 */
	protected function detect_device() {
		if ( wp_is_mobile() ) {
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

			// Tablet detection patterns
			$tablet_patterns = array( 'ipad', 'android(?!.*mobile)', 'tablet' );

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
	 * Check popup page targeting
	 *
	 * @param int $popup_id The popup campaign post ID.
	 * @return bool Whether the current page is targeted.
	 */
	protected function check_page_targeting( $popup_id ) {
		$targeting_type = get_post_meta( $popup_id, '_wphash_popup_target_pages', true );

		// Default: show on all pages
		if ( empty( $targeting_type ) || $targeting_type === 'all' ) {
			return true;
		}

		if ( $targeting_type === 'homepage' ) {
			return is_front_page();
		}

		$current_page_id = get_queried_object_id();

		if ( $targeting_type === 'specific' ) {
			$page_ids = get_post_meta( $popup_id, '_wphash_popup_target_page_ids', true );

			if ( is_string( $page_ids ) ) {
				$page_ids = json_decode( $page_ids, true );
			}

			if ( empty( $page_ids ) || ! is_array( $page_ids ) ) {
				return false;
			}

			return in_array( $current_page_id, array_map( 'intval', $page_ids ), true );
		}

		if ( $targeting_type === 'exclude' ) {
			$excluded_ids = get_post_meta( $popup_id, '_wphash_popup_exclude_page_ids', true );

			if ( is_string( $excluded_ids ) ) {
				$excluded_ids = json_decode( $excluded_ids, true );
			}

			if ( empty( $excluded_ids ) || ! is_array( $excluded_ids ) ) {
				return true;
			}

			return ! in_array( $current_page_id, array_map( 'intval', $excluded_ids ), true );
		}

		return true;
	}

	/**
	 * Check popup user status targeting
	 *
	 * @param int $popup_id The popup campaign post ID.
	 * @return bool Whether the current user matches targeting rules.
	 */
	protected function check_user_targeting( $popup_id ) {
		$user_status = get_post_meta( $popup_id, '_wphash_popup_target_user_status', true );

		if ( empty( $user_status ) || $user_status === 'all' ) {
			return true;
		}

		$is_logged_in = is_user_logged_in();

		if ( $user_status === 'logged_in' && ! $is_logged_in ) {
			return false;
		}

		if ( ( $user_status === 'guests' || $user_status === 'logged_out' ) && $is_logged_in ) {
			return false;
		}

		// Note: New/Returning visitor checks are handled client-side via JavaScript
		// because they require cookie-based detection that happens after page load

		return true;
	}

	/**
	 * Check popup referrer targeting
	 *
	 * @param int $popup_id The popup campaign post ID.
	 * @return bool Whether the referrer matches targeting rules.
	 */
	protected function check_referrer_targeting( $popup_id ) {
		$referrer_enabled = get_post_meta( $popup_id, '_wphash_popup_target_referrer_enabled', true );

		if ( ! $referrer_enabled || $referrer_enabled === 'false' || $referrer_enabled === '0' ) {
			return true; // Referrer targeting not enabled
		}

		$referrer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		if ( empty( $referrer ) ) {
			return true; // No referrer, allow display
		}

		$referrer_type = get_post_meta( $popup_id, '_wphash_popup_target_referrer_type', true ) ?: 'include';
		$referrer_sources = get_post_meta( $popup_id, '_wphash_popup_target_referrer_sources', true );

		if ( empty( $referrer_sources ) ) {
			return true;
		}

		// Parse referrer sources (comma-separated or newline-separated)
		$sources = preg_split( '/[\r\n,]+/', $referrer_sources );
		$sources = array_map( 'trim', $sources );
		$sources = array_filter( $sources );

		if ( empty( $sources ) ) {
			return true;
		}

		$referrer_matches = false;
		foreach ( $sources as $source ) {
			if ( stripos( $referrer, $source ) !== false ) {
				$referrer_matches = true;
				break;
			}
		}

		if ( $referrer_type === 'include' ) {
			return $referrer_matches;
		} else { // exclude
			return ! $referrer_matches;
		}
	}

	/**
	 * Render a single popup campaign
	 *
	 * @param \WP_Post $popup The popup campaign post object.
	 */
	protected function render_single_popup( $popup ) {
		$popup_id = $popup->ID;

		// Get popup settings
		$settings = $this->get_popup_settings( $popup_id );

		// Check for A/B testing and apply variant settings if applicable
		$variant_data = $this->get_ab_test_variant( $popup_id, $settings );
		$settings = $variant_data['settings'];
		$variant_id = $variant_data['variant_id'];

		// Get data attributes for JavaScript
		$data_attrs = $this->get_data_attributes( $popup_id, $settings );

		// Add variant ID to data attributes for analytics tracking
		if ( ! empty( $variant_id ) ) {
			$data_attrs .= ' data-variant-id="' . esc_attr( $variant_id ) . '"';
		}

		// Render popup HTML
		$this->render_popup_html( $popup, $settings, $data_attrs );
	}

	/**
	 * Get A/B test variant for visitor and apply settings
	 *
	 * @param int   $popup_id The popup ID.
	 * @param array $settings Original popup settings.
	 * @return array Array with 'settings' and 'variant_id'.
	 */
	protected function get_ab_test_variant( $popup_id, $settings ) {
		// Check if A/B testing is enabled
		$ab_enabled = get_post_meta( $popup_id, '_wphash_popup_ab_enabled', true );

		if ( ! $this->is_truthy( $ab_enabled ) ) {
			return array(
				'settings'   => $settings,
				'variant_id' => 'control',
			);
		}

		// Get variants
		$variants = get_post_meta( $popup_id, '_wphash_popup_ab_variants', true );

		if ( empty( $variants ) || ! is_array( $variants ) ) {
			return array(
				'settings'   => $settings,
				'variant_id' => 'control',
			);
		}

		// Check if visitor already has a variant assigned (stored in cookie)
		$cookie_name = 'hashbar_popup_ab_' . $popup_id;
		$assigned_variant_id = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) ) : null;

		// If variant is assigned and still exists, use it
		if ( $assigned_variant_id ) {
			if ( $assigned_variant_id === 'control' ) {
				return array(
					'settings'   => $settings,
					'variant_id' => 'control',
				);
			}

			// Find the assigned variant
			foreach ( $variants as $variant ) {
				if ( isset( $variant['id'] ) && $variant['id'] === $assigned_variant_id ) {
					$modified_settings = $this->apply_variant_settings( $settings, $variant );
					return array(
						'settings'   => $modified_settings,
						'variant_id' => $assigned_variant_id,
					);
				}
			}
		}

		// No valid variant assigned, select one based on traffic split
		$selected_variant = $this->select_variant_by_traffic( $variants );

		// Set cookie for consistent variant assignment (30 days)
		$variant_id = $selected_variant ? $selected_variant['id'] : 'control';
		$cookie_expiry = time() + ( 30 * DAY_IN_SECONDS );

		// Set cookie via JavaScript since headers might already be sent
		add_action( 'wp_footer', function() use ( $cookie_name, $variant_id, $cookie_expiry ) {
			?>
			<script>
				document.cookie = "<?php echo esc_js( $cookie_name ); ?>=<?php echo esc_js( $variant_id ); ?>;expires=<?php echo esc_js( gmdate( 'D, d M Y H:i:s', $cookie_expiry ) ); ?> GMT;path=/";
			</script>
			<?php
		}, 1000 );

		if ( $selected_variant ) {
			$modified_settings = $this->apply_variant_settings( $settings, $selected_variant );
			return array(
				'settings'   => $modified_settings,
				'variant_id' => $selected_variant['id'],
			);
		}

		return array(
			'settings'   => $settings,
			'variant_id' => 'control',
		);
	}

	/**
	 * Select a variant based on traffic split
	 *
	 * @param array $variants Array of variants.
	 * @return array|null Selected variant or null for control.
	 */
	protected function select_variant_by_traffic( $variants ) {
		// Calculate total variant traffic
		$total_variant_traffic = 0;
		foreach ( $variants as $variant ) {
			$total_variant_traffic += isset( $variant['traffic'] ) ? intval( $variant['traffic'] ) : 0;
		}

		// Control gets the remaining traffic
		$control_traffic = max( 0, 100 - $total_variant_traffic );

		// Generate random number between 1 and 100 (inclusive)
		$random = mt_rand( 1, 100 );

		// Check if control is selected (1 to control_traffic)
		if ( $random <= $control_traffic ) {
			return null; // Control
		}

		// Select variant based on cumulative traffic
		$cumulative = $control_traffic;
		foreach ( $variants as $variant ) {
			$traffic = isset( $variant['traffic'] ) ? intval( $variant['traffic'] ) : 0;
			$cumulative += $traffic;

			if ( $random <= $cumulative ) {
				return $variant;
			}
		}

		// Fallback to control
		return null;
	}

	/**
	 * Apply variant settings to base settings
	 *
	 * @param array $settings Base popup settings.
	 * @param array $variant Variant data with settings overrides.
	 * @return array Modified settings.
	 */
	protected function apply_variant_settings( $settings, $variant ) {
		if ( empty( $variant['settings'] ) || ! is_array( $variant['settings'] ) ) {
			return $settings;
		}

		// Map variant setting keys to popup setting keys
		$setting_map = array(
			'_wphash_popup_heading'         => 'heading',
			'_wphash_popup_subheading'      => 'subheading',
			'_wphash_popup_description'     => 'description',
			'_wphash_popup_btn_text'        => 'cta_text',
			'_wphash_popup_cta_url'         => 'cta_url',
			'_wphash_popup_cta_target'      => 'cta_target',
			'_wphash_popup_image'           => 'image',
			'_wphash_popup_btn_bg_color'    => 'btn_bg_color',
			'_wphash_popup_btn_text_color'  => 'btn_text_color',
			'_wphash_popup_bg_color'        => 'bg_color',
			'_wphash_popup_text_color'      => 'text_color',
			'_wphash_popup_heading_color'   => 'heading_color',
			'_wphash_popup_overlay_color'   => 'overlay_color',
		);

		// Apply variant overrides
		foreach ( $variant['settings'] as $key => $value ) {
			if ( isset( $setting_map[ $key ] ) && ! empty( $value ) ) {
				$settings[ $setting_map[ $key ] ] = $value;
			}
		}

		return $settings;
	}

	/**
	 * Get all popup settings
	 *
	 * @param int $popup_id The popup ID.
	 * @return array Settings array.
	 */
	protected function get_popup_settings( $popup_id ) {
		$settings = array();

		// Position and type
		$settings['position'] = get_post_meta( $popup_id, '_wphash_popup_position', true ) ?: 'center';
		$settings['content_type'] = get_post_meta( $popup_id, '_wphash_popup_content_type', true ) ?: 'custom';

		// Overlay settings
		$settings['overlay_enabled'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_overlay_enabled', true ) );
		$settings['overlay_close'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_overlay_close', true ) );

		// Close button settings
		$settings['close_enabled'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_close_enabled', true ) );
		$settings['close_position'] = get_post_meta( $popup_id, '_wphash_popup_close_position', true ) ?: 'top_right';
		$settings['esc_close'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_esc_close', true ) );

		// Design settings
		$settings['width'] = get_post_meta( $popup_id, '_wphash_popup_width', true ) ?: 500;
		$settings['max_width'] = get_post_meta( $popup_id, '_wphash_popup_max_width', true ) ?: 90;
		$settings['padding'] = get_post_meta( $popup_id, '_wphash_popup_padding', true ) ?: array( 'top' => 30, 'right' => 30, 'bottom' => 30, 'left' => 30 );
		$settings['bg_type'] = get_post_meta( $popup_id, '_wphash_popup_bg_type', true ) ?: 'solid';
		$settings['bg_color'] = get_post_meta( $popup_id, '_wphash_popup_bg_color', true ) ?: '#ffffff';
		$settings['gradient_color'] = get_post_meta( $popup_id, '_wphash_popup_gradient_color', true ) ?: '#f0f0f0';
		$settings['gradient_direction'] = get_post_meta( $popup_id, '_wphash_popup_gradient_direction', true ) ?: 'to_bottom';
		$settings['bg_image'] = get_post_meta( $popup_id, '_wphash_popup_bg_image', true );
		$settings['text_color'] = get_post_meta( $popup_id, '_wphash_popup_text_color', true ) ?: '#1a1a1a';
		$settings['heading_color'] = get_post_meta( $popup_id, '_wphash_popup_heading_color', true ) ?: '#1a1a1a';
		$settings['subheading_color'] = get_post_meta( $popup_id, '_wphash_popup_subheading_color', true ) ?: '#666666';
		$settings['overlay_color'] = get_post_meta( $popup_id, '_wphash_popup_overlay_color', true ) ?: 'rgba(0, 0, 0, 0.5)';
		$settings['font_family'] = get_post_meta( $popup_id, '_wphash_popup_font_family', true ) ?: 'system-ui';
		$settings['heading_size'] = get_post_meta( $popup_id, '_wphash_popup_heading_size', true ) ?: 24;
		$settings['heading_weight'] = get_post_meta( $popup_id, '_wphash_popup_heading_weight', true ) ?: 600;
		$settings['subheading_size'] = get_post_meta( $popup_id, '_wphash_popup_subheading_size', true ) ?: 16;
		$settings['subheading_weight'] = get_post_meta( $popup_id, '_wphash_popup_subheading_weight', true ) ?: 500;
		$settings['text_size'] = get_post_meta( $popup_id, '_wphash_popup_text_size', true ) ?: 16;
		$settings['text_align'] = get_post_meta( $popup_id, '_wphash_popup_text_align', true ) ?: 'center';
		$settings['text_transform'] = get_post_meta( $popup_id, '_wphash_popup_text_transform', true ) ?: 'none';
		$settings['border_radius'] = get_post_meta( $popup_id, '_wphash_popup_border_radius', true ) ?: 12;
		$settings['border_width'] = get_post_meta( $popup_id, '_wphash_popup_border_width', true ) ?: 0;
		$settings['border_color'] = get_post_meta( $popup_id, '_wphash_popup_border_color', true ) ?: '#e8e8e8';
		$settings['shadow'] = get_post_meta( $popup_id, '_wphash_popup_shadow', true ) ?: 'large';

		// Button styling
		$settings['btn_bg_color'] = get_post_meta( $popup_id, '_wphash_popup_btn_bg_color', true ) ?: '#1890ff';
		$settings['btn_text_color'] = get_post_meta( $popup_id, '_wphash_popup_btn_text_color', true ) ?: '#ffffff';
		$settings['btn_hover_bg_color'] = get_post_meta( $popup_id, '_wphash_popup_btn_hover_bg_color', true ) ?: '#40a9ff';
		$settings['btn_hover_text_color'] = get_post_meta( $popup_id, '_wphash_popup_btn_hover_text_color', true ) ?: '#ffffff';
		$settings['btn_border_radius'] = get_post_meta( $popup_id, '_wphash_popup_btn_border_radius', true ) ?: 6;
		$settings['btn_font_size'] = get_post_meta( $popup_id, '_wphash_popup_btn_font_size', true ) ?: 16;
		$settings['btn_width_type'] = get_post_meta( $popup_id, '_wphash_popup_btn_width_type', true ) ?: 'auto';
		$settings['btn_custom_width'] = get_post_meta( $popup_id, '_wphash_popup_btn_custom_width', true ) ?: 200;

		// Close button styling
		$settings['close_color'] = get_post_meta( $popup_id, '_wphash_popup_close_color', true ) ?: '#8c8c8c';
		$settings['close_bg_color'] = get_post_meta( $popup_id, '_wphash_popup_close_bg_color', true ) ?: 'rgba(0, 0, 0, 0.08)';
		$settings['close_hover_color'] = get_post_meta( $popup_id, '_wphash_popup_close_hover_color', true ) ?: '#1a1a1a';
		$settings['close_size'] = get_post_meta( $popup_id, '_wphash_popup_close_size', true ) ?: 24;
		$settings['close_border_radius'] = get_post_meta( $popup_id, '_wphash_popup_close_border_radius', true ) ?: 50;

		// Content
		$settings['image'] = get_post_meta( $popup_id, '_wphash_popup_image', true );
		$settings['image_position'] = get_post_meta( $popup_id, '_wphash_popup_image_position', true ) ?: 'top';
		$settings['image_width'] = get_post_meta( $popup_id, '_wphash_popup_image_width', true ) ?: 100;
		$settings['image_width_unit'] = get_post_meta( $popup_id, '_wphash_popup_image_width_unit', true ) ?: '%';
		$settings['image_alignment'] = get_post_meta( $popup_id, '_wphash_popup_image_alignment', true ) ?: 'center';
		$settings['image_border_radius'] = get_post_meta( $popup_id, '_wphash_popup_image_border_radius', true );
		if ( ! is_array( $settings['image_border_radius'] ) ) {
			$settings['image_border_radius'] = array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 );
		}
		$settings['content_padding'] = get_post_meta( $popup_id, '_wphash_popup_content_padding', true );
		if ( ! is_array( $settings['content_padding'] ) ) {
			$settings['content_padding'] = array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 );
		}
		$settings['content_border_radius'] = get_post_meta( $popup_id, '_wphash_popup_content_border_radius', true );
		if ( ! is_array( $settings['content_border_radius'] ) ) {
			$settings['content_border_radius'] = array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 );
		}
		$settings['content_bg_color'] = get_post_meta( $popup_id, '_wphash_popup_content_bg_color', true ) ?: 'transparent';
		$settings['content_valign'] = get_post_meta( $popup_id, '_wphash_popup_content_valign', true ) ?: 'middle';
		$settings['content_gap'] = intval( get_post_meta( $popup_id, '_wphash_popup_content_gap', true ) );
		$settings['heading'] = get_post_meta( $popup_id, '_wphash_popup_heading', true );
		$settings['subheading'] = get_post_meta( $popup_id, '_wphash_popup_subheading', true );
		$settings['description'] = get_post_meta( $popup_id, '_wphash_popup_description', true );

		// CTA Button
		$settings['cta_enabled'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_cta_enabled', true ) );
		$settings['cta_text'] = get_post_meta( $popup_id, '_wphash_popup_cta_text', true ) ?: 'Get Started';
		$settings['cta_url'] = get_post_meta( $popup_id, '_wphash_popup_cta_url', true ) ?: '#';
		$settings['cta_target'] = get_post_meta( $popup_id, '_wphash_popup_cta_target', true ) ?: '_self';
		$settings['cta_close_on_click'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_cta_close_on_click', true ) );

		// Secondary Button
		$settings['secondary_enabled'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_secondary_enabled', true ) );
		$settings['secondary_text'] = get_post_meta( $popup_id, '_wphash_popup_secondary_text', true ) ?: 'No Thanks';
		$settings['secondary_action'] = get_post_meta( $popup_id, '_wphash_popup_secondary_action', true ) ?: 'close';

		// Form settings
		$settings['form_enabled'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_form_enabled', true ) );
		$settings['form_fields'] = get_post_meta( $popup_id, '_wphash_popup_form_fields', true ) ?: array();
		$settings['form_submit_text'] = get_post_meta( $popup_id, '_wphash_popup_form_submit_text', true ) ?: 'Subscribe';
		$settings['form_submitting_text'] = get_post_meta( $popup_id, '_wphash_popup_form_submitting_text', true ) ?: 'Submitting...';
		$settings['form_success_action'] = get_post_meta( $popup_id, '_wphash_popup_form_success_action', true ) ?: 'message';
		$settings['form_success_message'] = get_post_meta( $popup_id, '_wphash_popup_form_success_message', true ) ?: 'Thank you! You have successfully subscribed.';
		$settings['form_success_redirect'] = get_post_meta( $popup_id, '_wphash_popup_form_success_redirect_url', true );
		$settings['form_close_delay'] = get_post_meta( $popup_id, '_wphash_popup_form_close_delay', true ) ?: 3;
		$settings['form_alignment'] = get_post_meta( $popup_id, '_wphash_popup_form_alignment', true ) ?: 'center';

		// Form Input Styling
		$settings['form_input_bg_color'] = get_post_meta( $popup_id, '_wphash_popup_form_input_bg_color', true ) ?: '#ffffff';
		$settings['form_input_text_color'] = get_post_meta( $popup_id, '_wphash_popup_form_input_text_color', true ) ?: '#333333';
		$settings['form_input_placeholder_color'] = get_post_meta( $popup_id, '_wphash_popup_form_input_placeholder_color', true ) ?: '#999999';
		$settings['form_input_border_color'] = get_post_meta( $popup_id, '_wphash_popup_form_input_border_color', true ) ?: '#d9d9d9';
		$settings['form_input_focus_color'] = get_post_meta( $popup_id, '_wphash_popup_form_input_focus_color', true ) ?: '#1890ff';
		$settings['form_input_border_radius'] = get_post_meta( $popup_id, '_wphash_popup_form_input_border_radius', true ) ?: 6;
		$settings['form_input_font_size'] = get_post_meta( $popup_id, '_wphash_popup_form_input_font_size', true ) ?: 14;
		$settings['form_input_height'] = get_post_meta( $popup_id, '_wphash_popup_form_input_height', true ) ?: 40;
		$settings['form_label_color'] = get_post_meta( $popup_id, '_wphash_popup_form_label_color', true ) ?: '#333333';
		$settings['form_label_font_size'] = get_post_meta( $popup_id, '_wphash_popup_form_label_font_size', true ) ?: 12;
		$settings['form_checkbox_accent_color'] = get_post_meta( $popup_id, '_wphash_popup_form_checkbox_accent_color', true ) ?: '#1890ff';

		// Trigger settings
		$settings['trigger_type'] = get_post_meta( $popup_id, '_wphash_popup_trigger_type', true ) ?: 'time_delay';
		$settings['trigger_delay'] = get_post_meta( $popup_id, '_wphash_popup_trigger_delay', true ) ?: 5;
		$settings['trigger_scroll_percent'] = get_post_meta( $popup_id, '_wphash_popup_trigger_scroll_percent', true ) ?: 50;
		$settings['trigger_click_selector'] = get_post_meta( $popup_id, '_wphash_popup_trigger_click_selector', true );
		$settings['trigger_click_delay'] = (int) ( get_post_meta( $popup_id, '_wphash_popup_trigger_click_delay', true ) ?: 0 );
		$settings['trigger_inactivity_time'] = get_post_meta( $popup_id, '_wphash_popup_trigger_inactivity_time', true ) ?: 30;
		$settings['trigger_element_selector'] = get_post_meta( $popup_id, '_wphash_popup_trigger_element_selector', true );
		$settings['trigger_page_views_count'] = get_post_meta( $popup_id, '_wphash_popup_trigger_page_views_count', true ) ?: 3;
		$settings['exit_sensitivity'] = get_post_meta( $popup_id, '_wphash_popup_exit_sensitivity', true ) ?: 'medium';
		$settings['exit_mobile_enabled'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_exit_mobile_enabled', true ) );

		// Targeting - Visitor type settings (handled by JavaScript via cookies)
		$settings['target_new_visitors'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_target_new_visitors', true ) );
		$settings['target_returning_visitors'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_target_returning_visitors', true ) );

		// Schedule settings for visitor timezone (handled by JavaScript)
		$settings['schedule_timezone'] = get_post_meta( $popup_id, '_wphash_popup_schedule_timezone', true ) ?: 'site';
		$settings['schedule_start_date'] = get_post_meta( $popup_id, '_wphash_popup_schedule_start_date', true ) ?: '';
		$settings['schedule_end_date'] = get_post_meta( $popup_id, '_wphash_popup_schedule_end_date', true ) ?: '';
		$settings['schedule_days_enabled'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_schedule_days_enabled', true ) );
		$schedule_days = get_post_meta( $popup_id, '_wphash_popup_schedule_days', true );
		// Handle various storage formats
		if ( is_array( $schedule_days ) ) {
			// Already an array
			$settings['schedule_days'] = $schedule_days;
		} elseif ( is_string( $schedule_days ) && ! empty( $schedule_days ) ) {
			// JSON encoded string
			$decoded = json_decode( $schedule_days, true );
			$settings['schedule_days'] = is_array( $decoded ) ? $decoded : array();
		} else {
			$settings['schedule_days'] = array();
		}
		$settings['schedule_time_enabled'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_schedule_time_enabled', true ) );
		$settings['schedule_time_start'] = get_post_meta( $popup_id, '_wphash_popup_schedule_time_start', true ) ?: '00:00';
		$settings['schedule_time_end'] = get_post_meta( $popup_id, '_wphash_popup_schedule_time_end', true ) ?: '23:59';

		// Frequency settings
		$settings['frequency_type'] = get_post_meta( $popup_id, '_wphash_popup_frequency_type', true ) ?: 'always';
		$settings['frequency_days'] = get_post_meta( $popup_id, '_wphash_popup_frequency_days', true ) ?: 7;
		$settings['frequency_times'] = get_post_meta( $popup_id, '_wphash_popup_frequency_times', true ) ?: 3;
		$settings['after_close'] = get_post_meta( $popup_id, '_wphash_popup_after_close', true ) ?: 'respect_frequency';
		$settings['after_close_days'] = get_post_meta( $popup_id, '_wphash_popup_after_close_days', true ) ?: 7;
		$settings['after_convert'] = get_post_meta( $popup_id, '_wphash_popup_after_convert', true ) ?: 'never_show';
		$settings['after_convert_days'] = get_post_meta( $popup_id, '_wphash_popup_after_convert_days', true ) ?: 30;

		// Animation settings
		$settings['animation_entry'] = get_post_meta( $popup_id, '_wphash_popup_animation_entry', true ) ?: 'fadeIn';
		$settings['animation_exit'] = get_post_meta( $popup_id, '_wphash_popup_animation_exit', true ) ?: 'fadeOut';
		$settings['animation_duration'] = get_post_meta( $popup_id, '_wphash_popup_animation_duration', true ) ?: 300;
		$settings['animation_delay'] = get_post_meta( $popup_id, '_wphash_popup_animation_delay', true ) ?: 0;

		// Countdown settings
		$settings['countdown_enabled'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_countdown_enabled', true ) );
		$settings['countdown_type'] = get_post_meta( $popup_id, '_wphash_popup_countdown_type', true ) ?: 'fixed_date';
		$settings['countdown_end_date'] = get_post_meta( $popup_id, '_wphash_popup_countdown_end_date', true );
		$settings['countdown_duration'] = get_post_meta( $popup_id, '_wphash_popup_countdown_duration', true ) ?: 24;
		$settings['countdown_daily_time'] = get_post_meta( $popup_id, '_wphash_popup_countdown_daily_time', true );
		$settings['countdown_recurring_days'] = get_post_meta( $popup_id, '_wphash_popup_countdown_recurring_days', true );
		$settings['countdown_timezone'] = get_post_meta( $popup_id, '_wphash_popup_countdown_timezone', true ) ?: 'site';
		$settings['countdown_show_days'] = $this->get_bool_meta( $popup_id, '_wphash_popup_countdown_show_days', true );
		$settings['countdown_show_hours'] = $this->get_bool_meta( $popup_id, '_wphash_popup_countdown_show_hours', true );
		$settings['countdown_show_minutes'] = $this->get_bool_meta( $popup_id, '_wphash_popup_countdown_show_minutes', true );
		$settings['countdown_show_seconds'] = $this->get_bool_meta( $popup_id, '_wphash_popup_countdown_show_seconds', true );
		$settings['countdown_label_days'] = get_post_meta( $popup_id, '_wphash_popup_countdown_label_days', true ) ?: __( 'Days', 'hashbar' );
		$settings['countdown_label_hours'] = get_post_meta( $popup_id, '_wphash_popup_countdown_label_hours', true ) ?: __( 'Hours', 'hashbar' );
		$settings['countdown_label_minutes'] = get_post_meta( $popup_id, '_wphash_popup_countdown_label_minutes', true ) ?: __( 'Minutes', 'hashbar' );
		$settings['countdown_label_seconds'] = get_post_meta( $popup_id, '_wphash_popup_countdown_label_seconds', true ) ?: __( 'Seconds', 'hashbar' );
		$settings['countdown_style'] = get_post_meta( $popup_id, '_wphash_popup_countdown_style', true ) ?: 'boxes';
		$settings['countdown_size'] = get_post_meta( $popup_id, '_wphash_popup_countdown_size', true ) ?: 'medium';
		$settings['countdown_expired_action'] = get_post_meta( $popup_id, '_wphash_popup_countdown_expired_action', true ) ?: 'hide_popup';
		$settings['countdown_expired_message'] = get_post_meta( $popup_id, '_wphash_popup_countdown_expired_message', true ) ?: __( 'This offer has expired!', 'hashbar' );
		$settings['countdown_expired_redirect'] = get_post_meta( $popup_id, '_wphash_popup_countdown_expired_redirect', true );
		$settings['countdown_bg_color'] = get_post_meta( $popup_id, '_wphash_popup_countdown_bg_color', true ) ?: '#1890ff';
		$settings['countdown_text_color'] = get_post_meta( $popup_id, '_wphash_popup_countdown_text_color', true ) ?: '#ffffff';
		$settings['countdown_label_color'] = get_post_meta( $popup_id, '_wphash_popup_countdown_label_color', true ) ?: '#666666';

		// Coupon settings
		$settings['coupon_enabled'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_coupon_enabled', true ) );
		$settings['coupon_code'] = get_post_meta( $popup_id, '_wphash_popup_coupon_code', true );
		$settings['coupon_label'] = get_post_meta( $popup_id, '_wphash_popup_coupon_label', true );
		$settings['coupon_description'] = get_post_meta( $popup_id, '_wphash_popup_coupon_description', true );
		$settings['coupon_style'] = get_post_meta( $popup_id, '_wphash_popup_coupon_style', true ) ?: 'dashed';
		$settings['coupon_bg_color'] = get_post_meta( $popup_id, '_wphash_popup_coupon_bg_color', true ) ?: '#f5f5f5';
		$settings['coupon_text_color'] = get_post_meta( $popup_id, '_wphash_popup_coupon_text_color', true ) ?: '#1a1a1a';
		$settings['coupon_border_color'] = get_post_meta( $popup_id, '_wphash_popup_coupon_border_color', true ) ?: '#d9d9d9';
		$settings['coupon_font_size'] = get_post_meta( $popup_id, '_wphash_popup_coupon_font_size', true ) ?: 18;
		$settings['coupon_label_font_size'] = get_post_meta( $popup_id, '_wphash_popup_coupon_label_font_size', true ) ?: 14;
		$settings['coupon_label_color'] = get_post_meta( $popup_id, '_wphash_popup_coupon_label_color', true ) ?: '#666666';
		$settings['coupon_description_color'] = get_post_meta( $popup_id, '_wphash_popup_coupon_description_color', true ) ?: '#888888';
		$settings['coupon_button_bg_color'] = get_post_meta( $popup_id, '_wphash_popup_coupon_button_bg_color', true ) ?: '#1890ff';
		$settings['coupon_button_text_color'] = get_post_meta( $popup_id, '_wphash_popup_coupon_button_text_color', true ) ?: '#ffffff';
		$settings['coupon_button_font_size'] = get_post_meta( $popup_id, '_wphash_popup_coupon_button_font_size', true ) ?: 14;
		$settings['coupon_copy_button'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_coupon_copy_button', true ) );
		$settings['coupon_copy_text'] = get_post_meta( $popup_id, '_wphash_popup_coupon_copy_text', true ) ?: 'Copy';
		$settings['coupon_copied_text'] = get_post_meta( $popup_id, '_wphash_popup_coupon_copied_text', true ) ?: 'Copied!';
		$settings['coupon_copied_bg_color'] = get_post_meta( $popup_id, '_wphash_popup_coupon_copied_bg_color', true ) ?: '#52c41a';
		$settings['coupon_autocopy_on_click'] = $this->is_truthy( get_post_meta( $popup_id, '_wphash_popup_coupon_autocopy_on_click', true ) );
		$settings['coupon_click_to_copy_text'] = get_post_meta( $popup_id, '_wphash_popup_coupon_click_to_copy_text', true ) ?: __( 'Click to copy', 'hashbar' );
		$settings['coupon_gradient_start'] = get_post_meta( $popup_id, '_wphash_popup_coupon_gradient_start', true ) ?: '#667eea';
		$settings['coupon_gradient_end'] = get_post_meta( $popup_id, '_wphash_popup_coupon_gradient_end', true ) ?: '#764ba2';

		// Custom CSS
		$settings['custom_css'] = get_post_meta( $popup_id, '_wphash_popup_custom_css', true );

		// Content order
		$content_order = get_post_meta( $popup_id, '_wphash_popup_content_order', true );
		$settings['content_order'] = ! empty( $content_order ) && is_array( $content_order )
			? $content_order
			: array( 'heading', 'subheading', 'description', 'countdown', 'coupon', 'form_or_buttons' );

		// Element spacing
		$element_spacing = get_post_meta( $popup_id, '_wphash_popup_element_spacing', true );
		$settings['element_spacing'] = ! empty( $element_spacing ) && is_array( $element_spacing )
			? $element_spacing
			: array( 'heading' => 6, 'subheading' => 8, 'description' => 16, 'countdown' => 16, 'coupon' => 16, 'form_or_buttons' => 0 );

		return $settings;
	}

	/**
	 * Get data attributes for JavaScript
	 *
	 * @param int   $popup_id The popup ID.
	 * @param array $settings Popup settings.
	 * @return string Data attributes string.
	 */
	protected function get_data_attributes( $popup_id, $settings ) {
		$data_attrs = array(
			'data-popup-id'                  => $popup_id,
			'data-position'                  => $settings['position'],
			'data-overlay-enabled'           => $settings['overlay_enabled'] ? 'true' : 'false',
			'data-overlay-close'             => $settings['overlay_close'] ? 'true' : 'false',
			'data-close-enabled'             => $settings['close_enabled'] ? 'true' : 'false',
			'data-esc-close'                 => $settings['esc_close'] ? 'true' : 'false',
			'data-trigger-type'              => $settings['trigger_type'],
			'data-trigger-delay'             => $settings['trigger_delay'],
			'data-trigger-scroll-percent'    => $settings['trigger_scroll_percent'],
			'data-trigger-click-selector'    => $settings['trigger_click_selector'],
			'data-trigger-click-delay'       => $settings['trigger_click_delay'],
			'data-trigger-inactivity-time'   => $settings['trigger_inactivity_time'],
			'data-trigger-element-selector'  => $settings['trigger_element_selector'],
			'data-trigger-page-views-count'  => $settings['trigger_page_views_count'],
			'data-exit-sensitivity'          => $settings['exit_sensitivity'],
			'data-exit-mobile-enabled'       => $settings['exit_mobile_enabled'] ? 'true' : 'false',
			'data-target-new-visitors'       => $settings['target_new_visitors'] ? 'true' : 'false',
			'data-target-returning-visitors' => $settings['target_returning_visitors'] ? 'true' : 'false',
			'data-frequency-type'            => $settings['frequency_type'],
			'data-frequency-days'            => $settings['frequency_days'],
			'data-frequency-times'           => $settings['frequency_times'],
			'data-after-close'               => $settings['after_close'],
			'data-after-close-days'          => $settings['after_close_days'],
			'data-after-convert'             => $settings['after_convert'],
			'data-after-convert-days'        => $settings['after_convert_days'],
			'data-animation-entry'           => $settings['animation_entry'],
			'data-animation-exit'            => $settings['animation_exit'],
			'data-animation-duration'        => $settings['animation_duration'],
			'data-animation-delay'           => $settings['animation_delay'],
			'data-cta-close-on-click'        => $settings['cta_close_on_click'] ? 'true' : 'false',
			'data-form-enabled'              => ( $settings['content_type'] === 'custom' && $settings['form_enabled'] ) ? 'true' : 'false',
			'data-form-success-action'       => $settings['form_success_action'],
			'data-form-success-message'      => esc_attr( $settings['form_success_message'] ),
			'data-form-success-redirect'     => $settings['form_success_redirect'],
			'data-form-close-delay'          => $settings['form_close_delay'],
			'data-countdown-enabled'         => $settings['countdown_enabled'] ? 'true' : 'false',
			'data-countdown-type'            => $settings['countdown_type'],
			'data-countdown-end-date'        => $settings['countdown_end_date'],
			'data-countdown-duration'        => $settings['countdown_duration'],
			'data-countdown-daily-time'      => $settings['countdown_daily_time'],
			'data-countdown-recurring-days'  => is_array( $settings['countdown_recurring_days'] ) ? wp_json_encode( $settings['countdown_recurring_days'] ) : $settings['countdown_recurring_days'],
			'data-countdown-timezone'        => $settings['countdown_timezone'],
			'data-countdown-show-days'       => $settings['countdown_show_days'] ? 'true' : 'false',
			'data-countdown-show-hours'      => $settings['countdown_show_hours'] ? 'true' : 'false',
			'data-countdown-show-minutes'    => $settings['countdown_show_minutes'] ? 'true' : 'false',
			'data-countdown-show-seconds'    => $settings['countdown_show_seconds'] ? 'true' : 'false',
			'data-countdown-expired-action'  => $settings['countdown_expired_action'],
			'data-countdown-expired-message' => $settings['countdown_expired_message'],
			'data-countdown-expired-redirect' => $settings['countdown_expired_redirect'],
			'data-coupon-enabled'            => $settings['coupon_enabled'] ? 'true' : 'false',
			'data-coupon-code'               => $settings['coupon_code'],
			'data-coupon-copied-text'        => $settings['coupon_copied_text'],
			'data-coupon-auto-copy'          => $settings['coupon_autocopy_on_click'] ? 'true' : 'false',
			'data-coupon-click-to-copy-text' => $settings['coupon_click_to_copy_text'],

			// Schedule settings for visitor timezone (handled by JavaScript)
			'data-schedule-timezone'         => $settings['schedule_timezone'],
			'data-schedule-start-date'       => $settings['schedule_start_date'],
			'data-schedule-end-date'         => $settings['schedule_end_date'],
			'data-schedule-days-enabled'     => $settings['schedule_days_enabled'] ? 'true' : 'false',
			'data-schedule-days'             => ! empty( $settings['schedule_days'] ) ? wp_json_encode( $settings['schedule_days'] ) : '',
			'data-schedule-time-enabled'     => $settings['schedule_time_enabled'] ? 'true' : 'false',
			'data-schedule-time-start'       => $settings['schedule_time_start'],
			'data-schedule-time-end'         => $settings['schedule_time_end'],
		);

		$data_attr_string = '';
		foreach ( $data_attrs as $attr => $value ) {
			if ( $value !== null && $value !== '' ) {
				$data_attr_string .= ' ' . $attr . '="' . esc_attr( $value ) . '"';
			}
		}

		return $data_attr_string;
	}

	/**
	 * Render popup HTML
	 *
	 * @param \WP_Post $popup The popup post object.
	 * @param array    $settings Popup settings.
	 * @param string   $data_attrs Data attributes string.
	 */
	protected function render_popup_html( $popup, $settings, $data_attrs ) {
		$popup_id = $popup->ID;

		// Parse values
		$padding_str = $this->parse_padding( $settings['padding'] );
		$background_css = $this->generate_background( $settings['bg_type'], $settings['bg_color'], $settings['gradient_color'], $settings['gradient_direction'], $settings['bg_image'] );
		$shadow_css = $this->get_shadow( $settings['shadow'] );

		// Position and close position classes
		$position_class = 'hashbar-popup-' . str_replace( '_', '-', $settings['position'] );
		$close_position_class = 'hashbar-popup-close-' . str_replace( '_', '-', $settings['close_position'] );

		// Image URL
		$image_url = $this->get_image_url( $settings['image'] );

		// Parse form fields
		$form_fields = $settings['form_fields'];
		if ( is_string( $form_fields ) ) {
			$form_fields = json_decode( $form_fields, true );
		}
		if ( ! is_array( $form_fields ) ) {
			$form_fields = array();
		}

		// Check if popup should be hidden initially (countdown enabled with hide_popup action)
		$initial_hidden = false;
		if ( $settings['countdown_enabled'] && 'hide_popup' === $settings['countdown_expired_action'] ) {
			$initial_hidden = true;
		}
		$hidden_style = $initial_hidden ? 'visibility: hidden; opacity: 0;' : '';

		$popup_dialog_label = trim( wp_strip_all_tags( $popup->post_title ) );
		if ( '' === $popup_dialog_label ) {
			$popup_dialog_label = __( 'Promotion', 'hashbar' );
		}
		$popup_aria_modal = ! empty( $settings['overlay_enabled'] ) ? 'true' : 'false';
		?>

		<?php if ( ! empty( $settings['custom_css'] ) ) : ?>
			<style>
				<?php
				$custom_css = wp_strip_all_tags( $settings['custom_css'] );
				$scope_id   = '#hashbar-popup-' . intval( $popup_id );
				$css_rules  = explode( '}', $custom_css );
				$scoped_css = '';

				foreach ( $css_rules as $rule ) {
					$rule = trim( $rule );
					if ( empty( $rule ) ) {
						continue;
					}

					$brace_pos = strpos( $rule, '{' );
					if ( false === $brace_pos ) {
						continue;
					}

					$selector     = trim( substr( $rule, 0, $brace_pos ) );
					$declarations = trim( substr( $rule, $brace_pos + 1 ) );

					if ( empty( $selector ) || empty( $declarations ) ) {
						continue;
					}

					// Prefix each comma-separated selector with the unique scope.
					$selectors = array_map( 'trim', explode( ',', $selector ) );
					$prefixed  = array_map(
						function ( $sel ) use ( $scope_id ) {
							if ( empty( $sel ) ) {
								return $sel;
							}
							// Replace .hashbar-popup-campaign with unique ID.
							if ( '.hashbar-popup-campaign' === $sel ) {
								return $scope_id;
							}
							if ( 0 === strpos( $sel, '.hashbar-popup-campaign' ) ) {
								return $scope_id . substr( $sel, strlen( '.hashbar-popup-campaign' ) );
							}
							return $scope_id . ' ' . $sel;
						},
						$selectors
					);

					$scoped_css .= implode( ', ', $prefixed ) . ' { ' . $declarations . " }\n";
				}

				echo $scoped_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already sanitized by wp_strip_all_tags.
				?>
			</style>
		<?php endif; ?>

		<?php if ( $settings['overlay_enabled'] ) : ?>
			<!-- Overlay for Popup: <?php echo esc_html( $popup->post_title ); ?> -->
			<div class="hashbar-popup-overlay" data-popup-id="<?php echo esc_attr( $popup_id ); ?>" style="background-color: <?php echo esc_attr( $settings['overlay_color'] ); ?>; <?php echo esc_attr( $hidden_style ); ?>"></div>
		<?php endif; ?>

		<!-- Popup: <?php echo esc_html( $popup->post_title ); ?> -->
		<div class="hashbar-popup-campaign <?php echo esc_attr( $position_class ); ?><?php echo $initial_hidden ? ' hashbar-popup-countdown-hidden' : ''; ?>" id="hashbar-popup-<?php echo esc_attr( $popup_id ); ?>" role="dialog" aria-modal="<?php echo esc_attr( $popup_aria_modal ); ?>" aria-label="<?php echo esc_attr( $popup_dialog_label ); ?>" data-popup-id="<?php echo esc_attr( $popup_id ); ?>" <?php echo $data_attrs; // phpcs:ignore ?> style="<?php echo esc_attr( $hidden_style ); ?>">

			<div class="hashbar-popup-container" style="
				<?php echo $settings['close_position'] !== 'outside' ? 'overflow: hidden;' : ''; ?>
				width: <?php echo esc_attr( $settings['width'] ); ?>px;
				max-width: <?php echo esc_attr( $settings['max_width'] ); ?>%;
				padding: <?php echo esc_attr( $padding_str ); ?>;
				background: <?php echo esc_attr( $background_css ); ?>;
				color: <?php echo esc_attr( $settings['text_color'] ); ?>;
				font-family: <?php echo esc_attr( $settings['font_family'] ); ?>;
				font-size: <?php echo esc_attr( $settings['text_size'] ); ?>px;
				text-align: <?php echo esc_attr( $settings['text_align'] ); ?>;
				border-radius: <?php echo esc_attr( $settings['border_radius'] ); ?>px;
				border: <?php echo esc_attr( $settings['border_width'] ); ?>px solid <?php echo esc_attr( $settings['border_color'] ); ?>;
				box-shadow: <?php echo esc_attr( $shadow_css ); ?>;
				--btn-bg-color: <?php echo esc_attr( $settings['btn_bg_color'] ); ?>;
				--btn-text-color: <?php echo esc_attr( $settings['btn_text_color'] ); ?>;
				--btn-hover-bg-color: <?php echo esc_attr( $settings['btn_hover_bg_color'] ); ?>;
				--btn-hover-text-color: <?php echo esc_attr( $settings['btn_hover_text_color'] ); ?>;
				--btn-border-radius: <?php echo esc_attr( $settings['btn_border_radius'] ); ?>px;
				--btn-font-size: <?php echo esc_attr( $settings['btn_font_size'] ); ?>px;
				--btn-width: <?php
					if ( $settings['btn_width_type'] === 'full_width' ) {
						echo '100%';
					} elseif ( $settings['btn_width_type'] === 'custom' ) {
						echo esc_attr( $settings['btn_custom_width'] ) . 'px';
					} else {
						echo 'auto';
					}
				?>;
				--btn-display: <?php echo $settings['btn_width_type'] === 'full_width' ? 'block' : 'inline-block'; ?>;
				--countdown-bg: <?php echo esc_attr( $settings['countdown_bg_color'] ); ?>;
				--countdown-text: <?php echo esc_attr( $settings['countdown_text_color'] ); ?>;
				--countdown-label: <?php echo esc_attr( $settings['countdown_label_color'] ); ?>;
				--coupon-bg: <?php echo esc_attr( $settings['coupon_bg_color'] ); ?>;
				--coupon-text: <?php echo esc_attr( $settings['coupon_text_color'] ); ?>;
				--coupon-border: <?php echo esc_attr( $settings['coupon_border_color'] ); ?>;
				--coupon-font-size: <?php echo esc_attr( $settings['coupon_font_size'] ); ?>px;
				--coupon-label-font-size: <?php echo esc_attr( $settings['coupon_label_font_size'] ); ?>px;
				--coupon-label-color: <?php echo esc_attr( $settings['coupon_label_color'] ); ?>;
				--coupon-description-color: <?php echo esc_attr( $settings['coupon_description_color'] ); ?>;
				--coupon-btn-bg: <?php echo esc_attr( $settings['coupon_button_bg_color'] ); ?>;
				--coupon-btn-text: <?php echo esc_attr( $settings['coupon_button_text_color'] ); ?>;
				--coupon-btn-font-size: <?php echo esc_attr( $settings['coupon_button_font_size'] ); ?>px;
				--coupon-gradient-start: <?php echo esc_attr( $settings['coupon_gradient_start'] ); ?>;
				--coupon-gradient-end: <?php echo esc_attr( $settings['coupon_gradient_end'] ); ?>;
				--coupon-copied-bg: <?php echo esc_attr( $settings['coupon_copied_bg_color'] ); ?>;
				--heading-size: <?php echo esc_attr( $settings['heading_size'] ); ?>px;
				--subheading-size: <?php echo esc_attr( $settings['subheading_size'] ); ?>px;
				--text-size: <?php echo esc_attr( $settings['text_size'] ); ?>px;
				--content-align: <?php echo esc_attr( $settings['text_align'] ); ?>;
				--content-justify: <?php echo esc_attr( $settings['text_align'] === 'left' ? 'flex-start' : ( $settings['text_align'] === 'right' ? 'flex-end' : 'center' ) ); ?>;
				--form-align: <?php echo esc_attr( $settings['form_alignment'] ); ?>;
				--form-justify: <?php echo esc_attr( $settings['form_alignment'] === 'left' ? 'flex-start' : ( $settings['form_alignment'] === 'right' ? 'flex-end' : 'center' ) ); ?>;
				--form-input-bg: <?php echo esc_attr( $settings['form_input_bg_color'] ); ?>;
				--form-input-text: <?php echo esc_attr( $settings['form_input_text_color'] ); ?>;
				--form-input-placeholder: <?php echo esc_attr( $settings['form_input_placeholder_color'] ); ?>;
				--form-input-border: <?php echo esc_attr( $settings['form_input_border_color'] ); ?>;
				--form-input-focus: <?php echo esc_attr( $settings['form_input_focus_color'] ); ?>;
				--form-input-radius: <?php echo esc_attr( $settings['form_input_border_radius'] ); ?>px;
				--form-input-size: <?php echo esc_attr( $settings['form_input_font_size'] ); ?>px;
				--form-input-height: <?php echo esc_attr( $settings['form_input_height'] ); ?>px;
				--form-label-color: <?php echo esc_attr( $settings['form_label_color'] ); ?>;
				--form-label-size: <?php echo esc_attr( $settings['form_label_font_size'] ); ?>px;
				--form-checkbox-accent: <?php echo esc_attr( $settings['form_checkbox_accent_color'] ); ?>;
				<?php if ( isset( $settings['image_width_unit'] ) && $settings['image_width_unit'] === 'px' ) : ?>--popup-image-width: <?php echo intval( $settings['image_width'] ); ?>px;<?php endif; ?>
			">

				<?php if ( $settings['close_enabled'] ) : ?>
					<?php $this->render_close_button( $settings, $close_position_class ); ?>
				<?php endif; ?>

				<?php
				$content_styles = array();
				if ( ! empty( $image_url ) ) {
					$content_styles[] = 'gap: ' . intval( $settings['content_gap'] ) . 'px';
				}
				$content_style_attr = ! empty( $content_styles ) ? ' style="' . esc_attr( implode( '; ', $content_styles ) ) . '"' : '';
				$content_classes = 'hashbar-popup-content';
				if ( ! empty( $image_url ) && in_array( $settings['image_position'], array( 'left', 'right' ), true ) ) {
					$content_classes .= ' hashbar-popup-split';
				}
				?>
				<div class="<?php echo esc_attr( $content_classes ); ?>"<?php echo $content_style_attr; ?>>
					<?php if ( $settings['content_type'] === 'custom' ) : ?>
						<?php $this->render_custom_content( $popup_id, $settings, $image_url, $form_fields ); ?>
					<?php else : ?>
						<?php $this->render_external_content( $popup_id, $settings ); ?>
					<?php endif; ?>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Render close button
	 *
	 * @param array  $settings Popup settings.
	 * @param string $close_position_class Close position CSS class.
	 */
	protected function render_close_button( $settings, $close_position_class ) {
		$icon_size = max( 12, intval( $settings['close_size'] ) - 8 );
		?>
		<button type="button" class="hashbar-popup-close <?php echo esc_attr( $close_position_class ); ?>" data-popup-close aria-label="<?php echo esc_attr__( 'Close popup', 'hashbar' ); ?>" style="
			color: <?php echo esc_attr( $settings['close_color'] ); ?>;
			background-color: <?php echo esc_attr( $settings['close_bg_color'] ); ?>;
			--close-hover-color: <?php echo esc_attr( $settings['close_hover_color'] ); ?>;
			width: <?php echo esc_attr( $settings['close_size'] ); ?>px;
			height: <?php echo esc_attr( $settings['close_size'] ); ?>px;
			border-radius: <?php echo esc_attr( $settings['close_border_radius'] ); ?>%;
		">
			<svg width="<?php echo esc_attr( $icon_size ); ?>" height="<?php echo esc_attr( $icon_size ); ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<line x1="18" y1="6" x2="6" y2="18"></line>
				<line x1="6" y1="6" x2="18" y2="18"></line>
			</svg>
		</button>
		<?php
	}

	/**
	 * Render custom popup content
	 *
	 * @param int    $popup_id The popup ID.
	 * @param array  $settings Popup settings.
	 * @param string $image_url Image URL.
	 * @param array  $form_fields Form fields array.
	 */
	protected function render_custom_content( $popup_id, $settings, $image_url, $form_fields ) {
		// Build image wrapper inline styles
		$image_styles = array();
		$image_width = isset( $settings['image_width'] ) ? intval( $settings['image_width'] ) : 100;
		$image_width_unit = isset( $settings['image_width_unit'] ) ? $settings['image_width_unit'] : '%';
		if ( $image_width > 0 ) {
			if ( $image_width_unit === 'px' ) {
				$image_styles[] = 'width: ' . $image_width . 'px';
			} elseif ( $image_width < 100 ) {
				$image_styles[] = 'width: ' . $image_width . '%';
			}
		}
		$img_br = isset( $settings['image_border_radius'] ) ? $settings['image_border_radius'] : array( 'top' => 0, 'right' => 0, 'bottom' => 0, 'left' => 0 );
		$br_top = intval( $img_br['top'] ?? 0 );
		$br_right = intval( $img_br['right'] ?? 0 );
		$br_bottom = intval( $img_br['bottom'] ?? 0 );
		$br_left = intval( $img_br['left'] ?? 0 );
		if ( $br_top || $br_right || $br_bottom || $br_left ) {
			$image_styles[] = 'border-radius: ' . $br_top . 'px ' . $br_right . 'px ' . $br_bottom . 'px ' . $br_left . 'px';
			$image_styles[] = 'overflow: hidden';
		}
		// Image alignment
		$img_align = isset( $settings['image_alignment'] ) ? $settings['image_alignment'] : 'center';
		$img_pos = isset( $settings['image_position'] ) ? $settings['image_position'] : 'top';
		if ( in_array( $img_pos, array( 'top', 'bottom' ), true ) ) {
			if ( $img_align === 'left' ) {
				$image_styles[] = 'margin-right: auto';
				$image_styles[] = 'margin-left: 0';
			} elseif ( $img_align === 'right' ) {
				$image_styles[] = 'margin-left: auto';
				$image_styles[] = 'margin-right: 0';
			} else {
				$image_styles[] = 'margin-left: auto';
				$image_styles[] = 'margin-right: auto';
			}
		} elseif ( in_array( $img_pos, array( 'left', 'right' ), true ) ) {
			if ( $img_align === 'top' ) {
				$image_styles[] = 'align-self: flex-start';
			} elseif ( $img_align === 'bottom' ) {
				$image_styles[] = 'align-self: flex-end';
			} else {
				$image_styles[] = 'align-self: center';
			}
		}
		// Add image spacing margin for top/bottom positions
		if ( in_array( $img_pos, array( 'top', 'bottom' ), true ) ) {
			$gap_margin = intval( $settings['content_gap'] );
			if ( $img_pos === 'top' ) {
				$image_styles[] = 'margin-bottom: ' . $gap_margin . 'px';
			} else {
				$image_styles[] = 'margin-top: ' . $gap_margin . 'px';
			}
		}
		$image_style_attr = ! empty( $image_styles ) ? ' style="' . esc_attr( implode( '; ', $image_styles ) ) . '"' : '';
		?>

		<?php if ( ! empty( $image_url ) && in_array( $settings['image_position'], array( 'top', 'left' ), true ) ) : ?>
			<div class="hashbar-popup-image hashbar-popup-image--<?php echo esc_attr( $settings['image_position'] ); ?>"<?php echo $image_style_attr; ?>>
				<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $settings['heading'] ); ?>" />
			</div>
		<?php endif; ?>

		<?php
		$body_styles = array();
		if ( ! empty( $image_url ) ) {
			$cp = $settings['content_padding'];
			if ( ! empty( $cp['top'] ) || ! empty( $cp['right'] ) || ! empty( $cp['bottom'] ) || ! empty( $cp['left'] ) ) {
				$body_styles[] = sprintf( 'padding: %dpx %dpx %dpx %dpx', intval( $cp['top'] ), intval( $cp['right'] ), intval( $cp['bottom'] ), intval( $cp['left'] ) );
			}
			if ( ! empty( $settings['content_bg_color'] ) && 'transparent' !== $settings['content_bg_color'] ) {
				$body_styles[] = 'background-color: ' . $settings['content_bg_color'];
			}
			$cbr = $settings['content_border_radius'];
			if ( ! empty( $cbr['top'] ) || ! empty( $cbr['right'] ) || ! empty( $cbr['bottom'] ) || ! empty( $cbr['left'] ) ) {
				$body_styles[] = sprintf( 'border-radius: %dpx %dpx %dpx %dpx', intval( $cbr['top'] ), intval( $cbr['right'] ), intval( $cbr['bottom'] ), intval( $cbr['left'] ) );
				$body_styles[] = 'overflow: hidden';
			}
			$valign_map = array( 'top' => 'flex-start', 'middle' => 'center', 'bottom' => 'flex-end' );
			$body_styles[] = 'display: flex';
			$body_styles[] = 'flex-direction: column';
			$body_styles[] = 'justify-content: ' . ( isset( $valign_map[ $settings['content_valign'] ] ) ? $valign_map[ $settings['content_valign'] ] : 'center' );
			$body_styles[] = 'align-self: stretch';
		}
		$body_style_attr = ! empty( $body_styles ) ? ' style="' . esc_attr( implode( '; ', $body_styles ) ) . '"' : '';
		?>
		<div class="hashbar-popup-body"<?php echo $body_style_attr; ?>>
			<?php
			$content_order = isset( $settings['content_order'] ) && is_array( $settings['content_order'] )
				? $settings['content_order']
				: array( 'heading', 'subheading', 'description', 'countdown', 'coupon', 'form_or_buttons' );

			$default_spacing = array( 'heading' => 6, 'subheading' => 8, 'description' => 16, 'countdown' => 16, 'coupon' => 16, 'form_or_buttons' => 0 );
			$element_spacing = isset( $settings['element_spacing'] ) && is_array( $settings['element_spacing'] )
				? $settings['element_spacing']
				: $default_spacing;

			foreach ( $content_order as $element ) :
				$mb = isset( $element_spacing[ $element ] ) ? intval( $element_spacing[ $element ] ) : ( isset( $default_spacing[ $element ] ) ? $default_spacing[ $element ] : 0 );
				switch ( $element ) :
					case 'heading':
						if ( ! empty( $settings['heading'] ) ) : ?>
							<h2 class="hashbar-popup-heading" style="color: <?php echo esc_attr( $settings['heading_color'] ); ?>; font-size: <?php echo esc_attr( $settings['heading_size'] ); ?>px; font-weight: <?php echo esc_attr( $settings['heading_weight'] ); ?>; text-transform: <?php echo esc_attr( $settings['text_transform'] ); ?>; margin-bottom: <?php echo esc_attr( $mb ); ?>px;">
								<?php echo wp_kses_post( $settings['heading'] ); ?>
							</h2>
						<?php endif;
						break;

					case 'subheading':
						if ( ! empty( $settings['subheading'] ) ) : ?>
							<h3 class="hashbar-popup-subheading" style="color: <?php echo esc_attr( $settings['subheading_color'] ); ?>; font-size: <?php echo esc_attr( $settings['subheading_size'] ); ?>px; font-weight: <?php echo esc_attr( $settings['subheading_weight'] ); ?>; text-transform: <?php echo esc_attr( $settings['text_transform'] ); ?>; margin-bottom: <?php echo esc_attr( $mb ); ?>px;">
								<?php echo wp_kses_post( $settings['subheading'] ); ?>
							</h3>
						<?php endif;
						break;

					case 'description':
						if ( ! empty( $settings['description'] ) ) : ?>
							<div class="hashbar-popup-description" style="color: <?php echo esc_attr( $settings['text_color'] ); ?>; font-size: <?php echo esc_attr( $settings['text_size'] ); ?>px; margin-bottom: <?php echo esc_attr( $mb ); ?>px;">
								<?php echo wp_kses_post( $settings['description'] ); ?>
							</div>
						<?php endif;
						break;

					case 'countdown':
						if ( $settings['countdown_enabled'] ) : ?>
							<div style="margin-bottom: <?php echo esc_attr( $mb ); ?>px;">
								<?php $this->render_countdown( $settings ); ?>
							</div>
						<?php endif;
						break;

					case 'coupon':
						if ( $settings['coupon_enabled'] && ! empty( $settings['coupon_code'] ) ) : ?>
							<div style="margin-bottom: <?php echo esc_attr( $mb ); ?>px;">
								<?php $this->render_coupon( $settings ); ?>
							</div>
						<?php endif;
						break;

					case 'form_or_buttons':
						if ( $settings['form_enabled'] && ! empty( $form_fields ) ) : ?>
							<div style="margin-bottom: <?php echo esc_attr( $mb ); ?>px;">
								<?php $this->render_form( $popup_id, $settings, $form_fields ); ?>
							</div>
						<?php elseif ( $settings['cta_enabled'] || $settings['secondary_enabled'] ) : ?>
							<div style="margin-bottom: <?php echo esc_attr( $mb ); ?>px;">
								<?php $this->render_buttons( $settings ); ?>
							</div>
						<?php endif;
						break;
				endswitch;
			endforeach;
			?>
		</div>

		<?php if ( ! empty( $image_url ) && in_array( $settings['image_position'], array( 'bottom', 'right' ), true ) ) : ?>
			<div class="hashbar-popup-image hashbar-popup-image--<?php echo esc_attr( $settings['image_position'] ); ?>"<?php echo $image_style_attr; ?>>
				<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $settings['heading'] ); ?>" />
			</div>
		<?php endif; ?>

		<?php
	}

	/**
	 * Render countdown timer
	 *
	 * @param array $settings Popup settings.
	 */
	protected function render_countdown( $settings ) {
		$style = $settings['countdown_style'];
		$size = $settings['countdown_size'];
		?>
		<div class="hashbar-popup-countdown hashbar-popup-countdown--<?php echo esc_attr( $style ); ?> hashbar-popup-countdown--<?php echo esc_attr( $size ); ?>">
			<?php if ( in_array( $style, array( 'boxes', 'circles' ), true ) ) : ?>
				<div class="hashbar-popup-countdown-units">
					<?php if ( $settings['countdown_show_days'] ) : ?>
						<div class="hashbar-popup-countdown-unit hashbar-countdown-days">
							<span class="hashbar-popup-countdown-number" data-countdown-days>00</span>
							<span class="hashbar-popup-countdown-label"><?php echo esc_html( $settings['countdown_label_days'] ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $settings['countdown_show_hours'] ) : ?>
						<div class="hashbar-popup-countdown-unit hashbar-countdown-hours">
							<span class="hashbar-popup-countdown-number" data-countdown-hours>00</span>
							<span class="hashbar-popup-countdown-label"><?php echo esc_html( $settings['countdown_label_hours'] ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $settings['countdown_show_minutes'] ) : ?>
						<div class="hashbar-popup-countdown-unit hashbar-countdown-minutes">
							<span class="hashbar-popup-countdown-number" data-countdown-minutes>00</span>
							<span class="hashbar-popup-countdown-label"><?php echo esc_html( $settings['countdown_label_minutes'] ); ?></span>
						</div>
					<?php endif; ?>
					<?php if ( $settings['countdown_show_seconds'] ) : ?>
						<div class="hashbar-popup-countdown-unit hashbar-countdown-seconds">
							<span class="hashbar-popup-countdown-number" data-countdown-seconds>00</span>
							<span class="hashbar-popup-countdown-label"><?php echo esc_html( $settings['countdown_label_seconds'] ); ?></span>
						</div>
					<?php endif; ?>
				</div>
			<?php elseif ( 'digital' === $style ) : ?>
				<div class="hashbar-popup-countdown-digital">
					<span class="hashbar-popup-countdown-digital-display">
						<?php if ( $settings['countdown_show_days'] ) : ?>
							<span data-countdown-days>00</span><span class="hashbar-popup-countdown-digital-sep">:</span>
						<?php endif; ?>
						<?php if ( $settings['countdown_show_hours'] ) : ?>
							<span data-countdown-hours>00</span><span class="hashbar-popup-countdown-digital-sep">:</span>
						<?php endif; ?>
						<?php if ( $settings['countdown_show_minutes'] ) : ?>
							<span data-countdown-minutes>00</span>
							<?php if ( $settings['countdown_show_seconds'] ) : ?>
								<span class="hashbar-popup-countdown-digital-sep">:</span>
							<?php endif; ?>
						<?php endif; ?>
						<?php if ( $settings['countdown_show_seconds'] ) : ?>
							<span data-countdown-seconds>00</span>
						<?php endif; ?>
					</span>
				</div>
			<?php else : ?>
				<!-- Inline style -->
				<div class="hashbar-popup-countdown-inline-wrapper">
					<span class="hashbar-popup-countdown-inline" data-countdown-inline>
						<?php if ( $settings['countdown_show_days'] ) : ?>
							<span class="hashbar-countdown-inline-unit">
								<span data-countdown-days>00</span>
								<span class="hashbar-popup-countdown-inline-label"><?php echo esc_html( $settings['countdown_label_days'] ); ?></span>
							</span>
							<span class="hashbar-popup-countdown-sep">:</span>
						<?php endif; ?>
						<?php if ( $settings['countdown_show_hours'] ) : ?>
							<span class="hashbar-countdown-inline-unit">
								<span data-countdown-hours>00</span>
								<span class="hashbar-popup-countdown-inline-label"><?php echo esc_html( $settings['countdown_label_hours'] ); ?></span>
							</span>
							<span class="hashbar-popup-countdown-sep">:</span>
						<?php endif; ?>
						<?php if ( $settings['countdown_show_minutes'] ) : ?>
							<span class="hashbar-countdown-inline-unit">
								<span data-countdown-minutes>00</span>
								<span class="hashbar-popup-countdown-inline-label"><?php echo esc_html( $settings['countdown_label_minutes'] ); ?></span>
							</span>
							<?php if ( $settings['countdown_show_seconds'] ) : ?>
								<span class="hashbar-popup-countdown-sep">:</span>
							<?php endif; ?>
						<?php endif; ?>
						<?php if ( $settings['countdown_show_seconds'] ) : ?>
							<span class="hashbar-countdown-inline-unit">
								<span data-countdown-seconds>00</span>
								<span class="hashbar-popup-countdown-inline-label"><?php echo esc_html( $settings['countdown_label_seconds'] ); ?></span>
							</span>
						<?php endif; ?>
					</span>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render coupon display
	 *
	 * @param array $settings Popup settings.
	 */
	protected function render_coupon( $settings ) {
		$autocopy = $settings['coupon_autocopy_on_click'] ? 'true' : 'false';
		?>
		<div class="hashbar-popup-coupon hashbar-popup-coupon--<?php echo esc_attr( $settings['coupon_style'] ); ?>" data-autocopy="<?php echo esc_attr( $autocopy ); ?>">
			<?php if ( ! empty( $settings['coupon_label'] ) ) : ?>
				<div class="hashbar-popup-coupon-label"><?php echo esc_html( $settings['coupon_label'] ); ?></div>
			<?php endif; ?>
			<?php if ( ! empty( $settings['coupon_description'] ) ) : ?>
				<div class="hashbar-popup-coupon-description"><?php echo esc_html( $settings['coupon_description'] ); ?></div>
			<?php endif; ?>
			<div class="hashbar-popup-coupon-code" data-coupon-code="<?php echo esc_attr( $settings['coupon_code'] ); ?>">
				<code><?php echo esc_html( $settings['coupon_code'] ); ?></code>
				<?php if ( $settings['coupon_copy_button'] ) : ?>
					<button type="button" class="hashbar-popup-coupon-copy" data-copy-text="<?php echo esc_attr( $settings['coupon_copy_text'] ); ?>" data-copied-text="<?php echo esc_attr( $settings['coupon_copied_text'] ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
							<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
							<rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
						</svg>
						<span><?php echo esc_html( $settings['coupon_copy_text'] ); ?></span>
					</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render popup form
	 *
	 * @param int   $popup_id The popup ID.
	 * @param array $settings Popup settings.
	 * @param array $form_fields Form fields array.
	 */
	protected function render_form( $popup_id, $settings, $form_fields ) {
		?>
		<form class="hashbar-popup-form" data-popup-form>
			<input type="hidden" name="popup_id" value="<?php echo esc_attr( $popup_id ); ?>" />
			<input type="hidden" name="action" value="hashbar_popup_submit" />
			<?php wp_nonce_field( 'hashbar_popup_submit', 'hashbar_popup_nonce' ); ?>

			<div class="hashbar-popup-form-fields">
				<?php foreach ( $form_fields as $field ) : ?>
					<?php $this->render_form_field( $field ); ?>
				<?php endforeach; ?>
			</div>

			<div class="hashbar-popup-form-actions">
				<button type="submit" class="hashbar-popup-submit" data-submit-text="<?php echo esc_attr( $settings['form_submit_text'] ); ?>" data-submitting-text="<?php echo esc_attr( $settings['form_submitting_text'] ); ?>">
					<?php echo esc_html( $settings['form_submit_text'] ); ?>
				</button>
			</div>

			<div class="hashbar-popup-form-message" style="display: none;"></div>
		</form>
		<?php
	}

	/**
	 * Render form field
	 *
	 * @param array $field Field configuration.
	 */
	protected function render_form_field( $field ) {
		$field_id = isset( $field['id'] ) ? $field['id'] : '';
		$field_type = isset( $field['type'] ) ? $field['type'] : 'text';
		$field_label = isset( $field['label'] ) ? $field['label'] : '';
		$field_placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		$field_required = isset( $field['required'] ) && $field['required'];
		$field_width = isset( $field['width'] ) ? $field['width'] : 'full';
		$field_options_raw = isset( $field['options'] ) ? $field['options'] : '';
		// Handle checkbox text (stored in checkboxText field).
		$checkbox_text = isset( $field['checkboxText'] ) ? $field['checkboxText'] : $field_label;

		// Convert options from string (newline-separated) to array if needed.
		if ( is_string( $field_options_raw ) && ! empty( $field_options_raw ) ) {
			$field_options = array_filter( array_map( 'trim', explode( "\n", $field_options_raw ) ) );
		} elseif ( is_array( $field_options_raw ) ) {
			$field_options = $field_options_raw;
		} else {
			$field_options = array();
		}

		$width_class = 'hashbar-popup-field--' . $field_width;
		$required_attr = $field_required ? 'required' : '';
		$required_mark = $field_required ? '<span class="hashbar-popup-required">*</span>' : '';
		?>

		<div class="hashbar-popup-field <?php echo esc_attr( $width_class ); ?> hashbar-popup-field--<?php echo esc_attr( $field_type ); ?>">
			<?php if ( ! empty( $field_label ) && ! in_array( $field_type, array( 'hidden', 'checkbox', 'consent' ), true ) ) : ?>
				<label class="hashbar-popup-label" for="hashbar-field-<?php echo esc_attr( $field_id ); ?>">
					<?php echo esc_html( $field_label ); ?><?php echo $required_mark; // phpcs:ignore ?>
				</label>
			<?php endif; ?>

			<?php
			switch ( $field_type ) {
				case 'email':
					?>
					<input type="email" id="hashbar-field-<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_id ); ?>" placeholder="<?php echo esc_attr( $field_placeholder ); ?>" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>" data-field-label="<?php echo esc_attr( $field_label ); ?>" <?php echo $required_attr; // phpcs:ignore ?> />
					<?php
					break;

				case 'name':
				case 'text':
					?>
					<input type="text" id="hashbar-field-<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_id ); ?>" placeholder="<?php echo esc_attr( $field_placeholder ); ?>" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>" data-field-label="<?php echo esc_attr( $field_label ); ?>" <?php echo $required_attr; // phpcs:ignore ?> />
					<?php
					break;

				case 'phone':
					?>
					<input type="tel" id="hashbar-field-<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_id ); ?>" placeholder="<?php echo esc_attr( $field_placeholder ); ?>" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>" data-field-label="<?php echo esc_attr( $field_label ); ?>" <?php echo $required_attr; // phpcs:ignore ?> />
					<?php
					break;

				case 'textarea':
					?>
					<textarea id="hashbar-field-<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_id ); ?>" placeholder="<?php echo esc_attr( $field_placeholder ); ?>" rows="4" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>" data-field-label="<?php echo esc_attr( $field_label ); ?>" <?php echo $required_attr; // phpcs:ignore ?>></textarea>
					<?php
					break;

				case 'checkbox':
				case 'consent':
					?>
					<label class="hashbar-popup-checkbox-label">
						<input type="checkbox" id="hashbar-field-<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_id ); ?>" value="1" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>" data-field-label="<?php echo esc_attr( $field_label ); ?>" <?php echo $required_attr; // phpcs:ignore ?> />
						<span><?php echo esc_html( $checkbox_text ); ?><?php echo $required_mark; // phpcs:ignore ?></span>
					</label>
					<?php
					break;

				case 'dropdown':
					?>
					<select id="hashbar-field-<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_id ); ?>" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>" data-field-label="<?php echo esc_attr( $field_label ); ?>" <?php echo $required_attr; // phpcs:ignore ?>>
						<?php if ( ! empty( $field_placeholder ) ) : ?>
							<option value=""><?php echo esc_html( $field_placeholder ); ?></option>
						<?php endif; ?>
						<?php foreach ( $field_options as $option ) : ?>
							<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php
					break;

				case 'radio':
					?>
					<div class="hashbar-popup-radio-group">
						<?php foreach ( $field_options as $index => $option ) : ?>
							<label class="hashbar-popup-radio-label">
								<input type="radio" name="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $option ); ?>" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>" data-field-label="<?php echo esc_attr( $field_label ); ?>" <?php echo $index === 0 && $field_required ? 'required' : ''; ?> />
								<span><?php echo esc_html( $option ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<?php
					break;

				case 'date':
					?>
					<input type="date" id="hashbar-field-<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_id ); ?>" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>" data-field-label="<?php echo esc_attr( $field_label ); ?>" <?php echo $required_attr; // phpcs:ignore ?> />
					<?php
					break;

				case 'hidden':
					?>
					<input type="hidden" id="hashbar-field-<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $field_placeholder ); ?>" data-field-id="<?php echo esc_attr( $field_id ); ?>" data-field-type="<?php echo esc_attr( $field_type ); ?>" data-field-label="<?php echo esc_attr( $field_label ); ?>" />
					<?php
					break;
			}
			?>
		</div>
		<?php
	}

	/**
	 * Render CTA and secondary buttons
	 *
	 * @param array $settings Popup settings.
	 */
	protected function render_buttons( $settings ) {
		?>
		<div class="hashbar-popup-buttons">
			<?php if ( $settings['cta_enabled'] ) : ?>
				<a href="<?php echo esc_url( $settings['cta_url'] ); ?>" class="hashbar-popup-cta" target="<?php echo esc_attr( $settings['cta_target'] ); ?>" data-popup-cta>
					<?php echo esc_html( $settings['cta_text'] ); ?>
				</a>
			<?php endif; ?>

			<?php if ( $settings['secondary_enabled'] ) : ?>
				<button type="button" class="hashbar-popup-secondary" data-action="<?php echo esc_attr( $settings['secondary_action'] ); ?>">
					<?php echo esc_html( $settings['secondary_text'] ); ?>
				</button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render external content (Contact Form 7, WPForms, etc.)
	 *
	 * @param int   $popup_id The popup ID.
	 * @param array $settings The popup settings.
	 */
	protected function render_external_content( $popup_id, $settings ) {
		$content_type = $settings['content_type'];

		// Determine wrapper class based on content type
		$form_types   = array( 'cf7', 'wpforms', 'ninja_forms', 'gravity_forms', 'fluent_forms', 'ht_form' );
		$wrapper_class = in_array( $content_type, $form_types, true ) ? 'hashbar-popup-external-form' : 'hashbar-popup-external-content';
		?>
		<div class="hashbar-popup-body">
			<?php if ( $settings['countdown_enabled'] ) : ?>
				<?php $this->render_countdown( $settings ); ?>
			<?php endif; ?>

			<?php if ( $settings['coupon_enabled'] && ! empty( $settings['coupon_code'] ) ) : ?>
				<?php $this->render_coupon( $settings ); ?>
			<?php endif; ?>

			<div class="<?php echo esc_attr( $wrapper_class ); ?>">
				<?php
				switch ( $content_type ) {
					case 'cf7':
						$form_id = get_post_meta( $popup_id, '_wphash_popup_cf7_form_id', true );
						if ( $form_id && shortcode_exists( 'contact-form-7' ) ) {
							echo do_shortcode( '[contact-form-7 id="' . intval( $form_id ) . '"]' );
						}
						break;

					case 'wpforms':
						$form_id = get_post_meta( $popup_id, '_wphash_popup_wpforms_form_id', true );
						if ( $form_id && shortcode_exists( 'wpforms' ) ) {
							echo do_shortcode( '[wpforms id="' . intval( $form_id ) . '"]' );
						}
						break;

					case 'ninja_forms':
						$form_id = get_post_meta( $popup_id, '_wphash_popup_ninja_form_id', true );
						if ( $form_id && shortcode_exists( 'ninja_form' ) ) {
							echo do_shortcode( '[ninja_form id="' . intval( $form_id ) . '"]' );
						}
						break;

					case 'gravity_forms':
						$form_id = get_post_meta( $popup_id, '_wphash_popup_gravity_form_id', true );
						if ( $form_id && shortcode_exists( 'gravityform' ) ) {
							echo do_shortcode( '[gravityform id="' . intval( $form_id ) . '" ajax="true"]' );
						}
						break;

					case 'fluent_forms':
						$form_id = get_post_meta( $popup_id, '_wphash_popup_fluent_form_id', true );
						if ( $form_id && shortcode_exists( 'fluentform' ) ) {
							echo do_shortcode( '[fluentform id="' . intval( $form_id ) . '"]' );
						}
						break;

					case 'ht_form':
						$form_id = get_post_meta( $popup_id, '_wphash_popup_ht_form_id', true );
						if ( $form_id && shortcode_exists( 'ht_form' ) ) {
							echo do_shortcode( '[ht_form id="' . intval( $form_id ) . '"]' );
						}
						break;

					case 'shortcode':
						$shortcode = get_post_meta( $popup_id, '_wphash_popup_shortcode', true );
						if ( ! empty( $shortcode ) ) {
							echo do_shortcode( $shortcode );
						}
						break;

					case 'custom_html':
						$custom_html = get_post_meta( $popup_id, '_wphash_popup_custom_html', true );
						if ( ! empty( $custom_html ) ) {
							// Allow safe HTML tags and process shortcodes
							// wp_kses_post removes scripts, unsafe attributes while keeping safe HTML
							$allowed_html = wp_kses_allowed_html( 'post' );
							// Add iframe support for embeds with restricted attributes
							$allowed_html['iframe'] = array(
								'src'             => true,
								'width'           => true,
								'height'          => true,
								'frameborder'     => true,
								'allowfullscreen' => true,
								'allow'           => true,
								'title'           => true,
								'loading'         => true,
								'class'           => true,
								'style'           => true,
							);
							// Sanitize and output with shortcode processing
							$sanitized_content = wp_kses( $custom_html, $allowed_html );
							echo do_shortcode( $sanitized_content );
						}
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Check if a value is truthy
	 *
	 * @param mixed $value The value to check.
	 * @return bool True if truthy.
	 */
	protected function is_truthy( $value ) {
		return ! empty( $value ) && $value !== '0' && $value !== 'false';
	}

	/**
	 * Get boolean meta with default value
	 *
	 * @param int    $post_id The post ID.
	 * @param string $meta_key The meta key.
	 * @param bool   $default Default value if meta doesn't exist.
	 * @return bool The boolean value.
	 */
	protected function get_bool_meta( $post_id, $meta_key, $default = false ) {
		$value = get_post_meta( $post_id, $meta_key, true );

		// If meta doesn't exist (empty string), return default
		if ( $value === '' ) {
			return $default;
		}

		// Check for explicit false values
		if ( $value === '0' || $value === 'false' || $value === false ) {
			return false;
		}

		// Check for explicit true values
		if ( $value === '1' || $value === 'true' || $value === true ) {
			return true;
		}

		return $default;
	}

	/**
	 * Parse padding value to CSS string
	 *
	 * @param mixed $padding Padding value.
	 * @return string CSS padding string.
	 */
	protected function parse_padding( $padding ) {
		if ( is_string( $padding ) ) {
			$padding = json_decode( $padding, true );
		}

		if ( ! is_array( $padding ) ) {
			return '30px';
		}

		$top = isset( $padding['top'] ) ? intval( $padding['top'] ) : 30;
		$right = isset( $padding['right'] ) ? intval( $padding['right'] ) : 30;
		$bottom = isset( $padding['bottom'] ) ? intval( $padding['bottom'] ) : 30;
		$left = isset( $padding['left'] ) ? intval( $padding['left'] ) : 30;

		return "{$top}px {$right}px {$bottom}px {$left}px";
	}

	/**
	 * Generate background CSS
	 *
	 * @param string $type Background type.
	 * @param string $color Background color.
	 * @param string $gradient_color Gradient second color.
	 * @param string $gradient_direction Gradient direction.
	 * @param mixed  $bg_image Background image.
	 * @return string CSS background value.
	 */
	protected function generate_background( $type, $color, $gradient_color, $gradient_direction, $bg_image ) {
		switch ( $type ) {
			case 'gradient':
				$direction = str_replace( '_', ' ', $gradient_direction );
				return "linear-gradient({$direction}, {$color}, {$gradient_color})";

			case 'image':
				$image_url = $this->get_image_url( $bg_image );
				if ( ! empty( $image_url ) ) {
					return "url('" . esc_url( $image_url ) . "') center/cover no-repeat";
				}
				return $color;

			default:
				return $color;
		}
	}

	/**
	 * Get image URL from image data
	 *
	 * @param mixed $image Image data.
	 * @return string Image URL.
	 */
	protected function get_image_url( $image ) {
		if ( empty( $image ) ) {
			return '';
		}

		if ( is_array( $image ) && ! empty( $image['url'] ) ) {
			return $image['url'];
		}

		if ( is_string( $image ) ) {
			// Try to decode as JSON first
			$decoded = json_decode( $image, true );
			if ( is_array( $decoded ) && ! empty( $decoded['url'] ) ) {
				return $decoded['url'];
			}

			// If it's a plain URL string (starts with http or /), return it directly
			if ( strpos( $image, 'http' ) === 0 || strpos( $image, '/' ) === 0 ) {
				return $image;
			}
		}

		return '';
	}

	/**
	 * Get shadow CSS value
	 *
	 * @param string $shadow Shadow preset.
	 * @return string CSS box-shadow value.
	 */
	protected function get_shadow( $shadow ) {
		$shadows = array(
			'none'   => 'none',
			'small'  => '0 2px 8px rgba(0, 0, 0, 0.1)',
			'medium' => '0 4px 16px rgba(0, 0, 0, 0.15)',
			'large'  => '0 8px 32px rgba(0, 0, 0, 0.2)',
			'xl'     => '0 16px 48px rgba(0, 0, 0, 0.25)',
		);

		return isset( $shadows[ $shadow ] ) ? $shadows[ $shadow ] : $shadows['large'];
	}
}

// Initialize the frontend
Frontend::get_instance();
