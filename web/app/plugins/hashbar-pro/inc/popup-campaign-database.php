<?php
/**
 * Popup Campaign Database Installer
 *
 * Creates database tables for popup submissions and analytics.
 *
 * @package HashBar Pro
 * @since 2.0.0
 */

namespace Hashbar\Pro;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Popup Campaign Database Installer Class
 */
class Hashbar_Popup_Campaign_Database {

	/**
	 * Create all popup campaign tables
	 *
	 * @return void
	 */
	public static function create_tables() {
		self::create_submissions_table();
		self::create_analytics_table();
	}

	/**
	 * Create popup submissions table
	 *
	 * Stores form submission data from popup campaigns.
	 *
	 * @return void
	 */
	public static function create_submissions_table() {
		global $wpdb;

		$table_exist = get_option( 'hashbar_popup_submissions_tbl_exist', false );

		if ( $table_exist !== false ) {
			return;
		}

		$table_name      = $wpdb->prefix . 'hashbar_popup_submissions';
		$charset_collate = $wpdb->get_charset_collate();

		$schema = "
		CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			popup_id BIGINT(20) UNSIGNED NOT NULL COMMENT 'Popup campaign ID',
			variant_id VARCHAR(50) DEFAULT NULL COMMENT 'A/B test variant if applicable',

			form_data LONGTEXT NOT NULL COMMENT 'JSON encoded form submission data',
			email VARCHAR(255) DEFAULT NULL COMMENT 'Email field value for quick lookup',
			name VARCHAR(255) DEFAULT NULL COMMENT 'Name field value',
			phone VARCHAR(50) DEFAULT NULL COMMENT 'Phone field value',

			ip_address VARCHAR(45) NOT NULL COMMENT 'IPv4/IPv6 address',
			user_agent TEXT DEFAULT NULL,

			page_url TEXT NOT NULL COMMENT 'URL where form was submitted',
			page_id BIGINT(20) DEFAULT NULL COMMENT 'WordPress page ID',
			referrer_url TEXT DEFAULT NULL COMMENT 'Original referrer',

			utm_source VARCHAR(255) DEFAULT NULL,
			utm_medium VARCHAR(255) DEFAULT NULL,
			utm_campaign VARCHAR(255) DEFAULT NULL,
			utm_term VARCHAR(255) DEFAULT NULL,
			utm_content VARCHAR(255) DEFAULT NULL,

			user_id BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'WordPress user ID if logged in',

			mailchimp_status VARCHAR(50) DEFAULT NULL COMMENT 'pending, subscribed, failed',
			mailchimp_error TEXT DEFAULT NULL COMMENT 'Error message if subscription failed',

			status VARCHAR(20) DEFAULT 'active' COMMENT 'active, spam, deleted',

			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

			PRIMARY KEY (id),
			KEY idx_popup_id (popup_id),
			KEY idx_email (email),
			KEY idx_status (status),
			KEY idx_created (created_at),
			KEY idx_user_id (user_id)
		) $charset_collate;
		";

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( $schema );

		update_option( 'hashbar_popup_submissions_tbl_exist', 'true' );
	}

	/**
	 * Create popup analytics table
	 *
	 * Stores analytics events for popup campaigns (impressions, views, submissions, closes).
	 *
	 * @return void
	 */
	public static function create_analytics_table() {
		global $wpdb;

		$table_exist = get_option( 'hashbar_popup_analytics_tbl_exist', false );

		if ( $table_exist !== false ) {
			return;
		}

		$table_name      = $wpdb->prefix . 'hashbar_popup_analytics';
		$charset_collate = $wpdb->get_charset_collate();

		$schema = "
		CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			popup_id BIGINT(20) UNSIGNED NOT NULL COMMENT 'Popup campaign ID',
			variant_id VARCHAR(50) DEFAULT NULL COMMENT 'A/B test variant',

			event_type ENUM('impression', 'view', 'interaction', 'submission', 'close') NOT NULL DEFAULT 'impression',
			event_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

			session_id VARCHAR(64) NOT NULL COMMENT 'Browser session identifier',
			visitor_id VARCHAR(64) DEFAULT NULL COMMENT 'Persistent visitor identifier',

			user_id BIGINT(20) DEFAULT NULL COMMENT 'WordPress user ID if logged in',
			ip_address VARCHAR(45) NOT NULL COMMENT 'IPv4/IPv6',

			country VARCHAR(100) DEFAULT NULL,
			country_code CHAR(2) DEFAULT NULL,
			city VARCHAR(100) DEFAULT NULL,

			device_type ENUM('desktop', 'tablet', 'mobile', 'unknown') DEFAULT 'unknown',
			browser VARCHAR(50) DEFAULT NULL,
			browser_version VARCHAR(20) DEFAULT NULL,
			os VARCHAR(50) DEFAULT NULL,
			os_version VARCHAR(20) DEFAULT NULL,
			user_agent TEXT DEFAULT NULL,

			page_url TEXT NOT NULL COMMENT 'URL where popup was shown',
			page_id BIGINT(20) DEFAULT NULL COMMENT 'WordPress page ID',
			page_type VARCHAR(50) DEFAULT NULL COMMENT 'home, post, page, product, archive',
			referrer_url TEXT DEFAULT NULL,

			trigger_type VARCHAR(50) DEFAULT NULL COMMENT 'time_delay, exit_intent, scroll, click, etc.',

			utm_source VARCHAR(255) DEFAULT NULL,
			utm_medium VARCHAR(255) DEFAULT NULL,
			utm_campaign VARCHAR(255) DEFAULT NULL,

			close_method VARCHAR(50) DEFAULT NULL COMMENT 'button, overlay, esc, cta',

			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

			PRIMARY KEY (id),
			KEY idx_popup_event (popup_id, event_type, event_timestamp),
			KEY idx_popup_id (popup_id),
			KEY idx_event_type (event_type),
			KEY idx_session (session_id),
			KEY idx_visitor (visitor_id),
			KEY idx_device (device_type),
			KEY idx_country (country_code),
			KEY idx_timestamp (event_timestamp),
			KEY idx_trigger (trigger_type)
		) $charset_collate;
		";

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( $schema );

		update_option( 'hashbar_popup_analytics_tbl_exist', 'true' );
	}

	/**
	 * Drop all popup campaign tables
	 *
	 * Used during plugin uninstall.
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hashbar_popup_submissions" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hashbar_popup_analytics" );
		// phpcs:enable

		delete_option( 'hashbar_popup_submissions_tbl_exist' );
		delete_option( 'hashbar_popup_analytics_tbl_exist' );
	}

	/**
	 * Get submissions table name
	 *
	 * @return string
	 */
	public static function get_submissions_table() {
		global $wpdb;
		return $wpdb->prefix . 'hashbar_popup_submissions';
	}

	/**
	 * Get analytics table name
	 *
	 * @return string
	 */
	public static function get_analytics_table() {
		global $wpdb;
		return $wpdb->prefix . 'hashbar_popup_analytics';
	}

	/**
	 * Check if tables exist
	 *
	 * @return bool
	 */
	public static function tables_exist() {
		global $wpdb;

		$submissions_table = $wpdb->prefix . 'hashbar_popup_submissions';
		$analytics_table   = $wpdb->prefix . 'hashbar_popup_analytics';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$submissions_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $submissions_table ) ) === $submissions_table;
		$analytics_exists   = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $analytics_table ) ) === $analytics_table;
		// phpcs:enable

		return $submissions_exists && $analytics_exists;
	}
}

// Create tables on admin init (for updates when plugin is already active)
add_action( 'admin_init', function() {
	if ( ! Hashbar_Popup_Campaign_Database::tables_exist() ) {
		Hashbar_Popup_Campaign_Database::create_tables();
	}
} );
