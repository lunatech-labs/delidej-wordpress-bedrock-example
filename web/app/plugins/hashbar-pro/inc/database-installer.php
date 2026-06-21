<?php
/**
 * Installer class
 */

namespace Hashbar\Pro\DatabaseInstaller;

class Database_Installer {

    /**
     * [create_tables]
     * @return [void]
    */
    public static function create_tables() {
        global $wpdb;
        $analytics_table_exist =get_option( 'hthb_analyticstbl_exist', $default = false );

        if($analytics_table_exist !== false) return; 

        $table_name      = $wpdb->prefix .'hthb_analytics';       

        $charset_collate = $wpdb-> get_charset_collate();

        $schema = "
        CREATE TABLE $table_name (
        id bigint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(55) DEFAULT NULL,
        views bigint(55) DEFAULT NULL,
        clicks bigint(55) DEFAULT NULL,
        ip_address varchar(255) DEFAULT NULL,
        country varchar(255) DEFAULT NULL,
        created_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        updated_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;
        ";

        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta( $schema );

        update_option( 'hthb_analyticstbl_exist', 'true');
    }

    /**
     * [create_announcement_analytics_table]
     * Creates dedicated announcement analytics table (separate from notification bar analytics)
     * @return [void]
    */
    public static function create_announcement_analytics_table() {
        global $wpdb;
        $table_exist = get_option( 'hthb_announcement_analyticstbl_exist', $default = false );

        if( $table_exist !== false ) return;

        $table_name      = $wpdb->prefix . 'hashbar_announcement_analytics';
        $charset_collate = $wpdb->get_charset_collate();

        $schema = "
        CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,

            campaign_id BIGINT(20) UNSIGNED NOT NULL COMMENT 'Announcement bar or popup ID',
            campaign_type VARCHAR(50) DEFAULT 'announcement' COMMENT 'announcement, popup, etc',
            variant_id VARCHAR(50) DEFAULT NULL COMMENT 'A/B test variant',

            event_type ENUM('view', 'click', 'conversion', 'close') NOT NULL DEFAULT 'view',
            event_timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            user_id BIGINT(20) DEFAULT NULL COMMENT 'Logged in user ID',
            session_id VARCHAR(64) NOT NULL COMMENT 'Session identifier',
            ip_address VARCHAR(45) NOT NULL COMMENT 'IPv4/IPv6',

            country VARCHAR(100) DEFAULT NULL,
            country_code CHAR(2) DEFAULT NULL,

            device_type ENUM('desktop', 'tablet', 'mobile', 'unknown') DEFAULT 'unknown',
            browser VARCHAR(50) DEFAULT NULL,
            os VARCHAR(50) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,

            page_url TEXT NOT NULL,
            page_id BIGINT(20) DEFAULT NULL,
            page_type VARCHAR(50) DEFAULT NULL COMMENT 'home, post, page, archive',
            referrer_url TEXT DEFAULT NULL,

            conversion_value DECIMAL(10,2) DEFAULT NULL,

            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

            PRIMARY KEY (id),
            KEY idx_campaign_event (campaign_id, event_type, event_timestamp),
            KEY idx_campaign_type (campaign_type, event_type),
            KEY idx_session (session_id),
            KEY idx_device (device_type),
            KEY idx_country (country_code),
            KEY idx_timestamp (event_timestamp)
        ) $charset_collate;
        ";

        if ( ! function_exists( 'dbDelta' ) ) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        dbDelta( $schema );

        update_option( 'hthb_announcement_analyticstbl_exist', 'true');
    }

    /**
     * [drop_tables] Delete table
     * @return [void]
    */
    public static function drop_tables() {
        global $wpdb;
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hthb_analytics" );  // phpcs:ignore
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hashbar_announcement_analytics" );  // phpcs:ignore
        
        // Drop A/B test tables if they exist
        if ( class_exists( '\Hashbar\Pro\ABTest\AB_Test_Database' ) ) {
            \Hashbar\Pro\ABTest\AB_Test_Database::drop_tables();
        }
    }
}