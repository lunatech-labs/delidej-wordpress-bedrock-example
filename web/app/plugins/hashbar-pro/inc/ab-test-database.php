<?php
namespace Hashbar\Pro\ABTest;

/**
 * A/B Test Database Schema
 *
 * Creates and manages database tables for A/B testing functionality.
 *
 * @package HashBar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A/B Test Database Manager
 */
class AB_Test_Database {

	/**
	 * Create A/B test database tables
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;

		$table_exist = get_option( 'hthb_ab_test_tables_exist', false );

		if ( $table_exist !== false ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Table 1: Visitor assignments (wp_hthb_ab_tests)
		$table_name = $wpdb->prefix . 'hthb_ab_tests';
		$schema_assignments = "
		CREATE TABLE $table_name (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			bar_id BIGINT(20) UNSIGNED NOT NULL,
			variant_id VARCHAR(50) NOT NULL,
			visitor_id VARCHAR(100) NOT NULL,
			visitor_type ENUM('cookie', 'user_id') NOT NULL,
			assigned_at DATETIME NOT NULL,
			last_seen_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			INDEX idx_bar_variant (bar_id, variant_id),
			INDEX idx_visitor (visitor_id),
			INDEX idx_bar (bar_id),
			UNIQUE KEY idx_unique_assignment (bar_id, visitor_id, visitor_type)
		) $charset_collate;
		";

		dbDelta( $schema_assignments );

		// Table 2: A/B test events (wp_hthb_ab_events)
		$table_name_events = $wpdb->prefix . 'hthb_ab_events';
		$schema_events = "
		CREATE TABLE $table_name_events (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			bar_id BIGINT(20) UNSIGNED NOT NULL,
			variant_id VARCHAR(50) NOT NULL,
			visitor_id VARCHAR(100) NOT NULL,
			event_type ENUM('impression', 'click', 'conversion') NOT NULL,
			event_value VARCHAR(255) DEFAULT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			INDEX idx_bar_variant_type (bar_id, variant_id, event_type),
			INDEX idx_visitor (visitor_id),
			INDEX idx_created (created_at),
			INDEX idx_bar_variant (bar_id, variant_id)
		) $charset_collate;
		";

		dbDelta( $schema_events );

		update_option( 'hthb_ab_test_tables_exist', 'true' );
	}

	/**
	 * Drop A/B test database tables
	 *
	 * @return void
	 */
	public static function drop_tables() {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hthb_ab_tests" ); // phpcs:ignore
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hthb_ab_events" ); // phpcs:ignore

		delete_option( 'hthb_ab_test_tables_exist' );
	}
}

