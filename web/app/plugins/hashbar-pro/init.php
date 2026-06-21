<?php
/**
 * Plugin Name: HashBar Pro - Announcement, Notification Bar & Popup Campaign
 * Plugin URI: https://wphashbar.com/
 * Description: Announcement, Notification & Popup Campaign plugin for WordPress
 * Version: 1.7.5
 * Author: HasThemes
 * Author URI: https://hasthemes.com/
 * Text Domain: hashbar
*/

// define path
define( 'HASHBAR_WPNBP_ROOT', __FILE__ );
define( 'HASHBAR_WPNBP_URI', plugins_url( '', __FILE__ ) );
define( 'HASHBAR_WPNBP_DIR', dirname( __FILE__ ) );
define( 'HASHBAR_WPNBP_VERSION', '1.7.5' );

$wordpress_version           = (int)get_bloginfo( 'version' );
$hashbarpro_gutenberg_enable = $wordpress_version < 5 ? false : true;

// Get hashbar option value
function hashbar_get_opt($opt_key){
    $options = get_option( 'hashbar_wpnbp_opt' );
    if(isset($options[$opt_key])){
        return $options[$opt_key];
    }else{
        return '';
    }
}

// Include all files
if ( ! function_exists('is_plugin_active') ){
    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}
include_once( HASHBAR_WPNBP_DIR. '/inc/functions.php');
include_once( HASHBAR_WPNBP_DIR. '/inc/custom-posts.php');
include_once( HASHBAR_WPNBP_DIR. '/inc/post-duplicator.php');

// settings panel files
include_once( HASHBAR_WPNBP_DIR. '/admin/settings-panel/settings-panel.php');


if( true === $hashbarpro_gutenberg_enable ){
    if( is_admin() ){
        if( hashbar_wpnbp_check_post() ){
            include_once( HASHBAR_WPNBP_DIR. '/blocks/block-init.php' );  
        }
    }else{
        include_once( HASHBAR_WPNBP_DIR. '/blocks/block-init.php' );
    }
}

include_once( HASHBAR_WPNBP_DIR. '/inc/shortcode.php');
include_once( HASHBAR_WPNBP_DIR. '/inc/database-installer.php');
include_once( HASHBAR_WPNBP_DIR. '/inc/manage-cash.php');
include_once( HASHBAR_WPNBP_DIR. '/inc/analytical-store.php');
include_once( HASHBAR_WPNBP_DIR. '/inc/recurring-countdown.php');
include_once( HASHBAR_WPNBP_DIR. '/admin/plugin-options.php');

if(is_admin()){
    add_action( 'wp_loaded', function(){
        include_once( HASHBAR_WPNBP_DIR. '/inc/licence/HashbarPro.php');
    } );

}

if ( ! class_exists( 'CSF' ) ) {
    require_once HASHBAR_WPNBP_DIR .'/libs/codestar-framework/classes/setup.class.php';
}

add_action('csf_loaded', function() {
    include_once( HASHBAR_WPNBP_DIR. '/inc/metabox.php'); 
});
 
// Announcement Bar functionality
require_once HASHBAR_WPNBP_DIR . '/inc/announcement-bar-cpt.php';
require_once HASHBAR_WPNBP_DIR . '/inc/announcement-bar-frontend.php';

// A/B Testing
require_once HASHBAR_WPNBP_DIR . '/inc/ab-test-database.php';
require_once HASHBAR_WPNBP_DIR . '/inc/ab-test-tracking.php';
require_once HASHBAR_WPNBP_DIR . '/inc/ab-test-assignment.php';
require_once HASHBAR_WPNBP_DIR . '/inc/ab-test-statistics.php';
require_once HASHBAR_WPNBP_DIR . '/inc/ab-test-winner.php';

// Analytics
require_once HASHBAR_WPNBP_DIR . '/inc/announcement-analytics-processor.php';
require_once HASHBAR_WPNBP_DIR . '/inc/analytical-store.php';

// Announcement Bar REST API endpoints
require_once HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/announcement-bar-api.php';
require_once HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/announcement-bar-settings.php';
require_once HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/ab-test-api.php';
require_once HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/announcement-analytics-api.php';

// Popup Campaign functionality
require_once HASHBAR_WPNBP_DIR . '/inc/popup-campaign-cpt.php';
require_once HASHBAR_WPNBP_DIR . '/inc/popup-campaign-frontend.php';
require_once HASHBAR_WPNBP_DIR . '/inc/popup-campaign-database.php';
require_once HASHBAR_WPNBP_DIR . '/inc/popup-campaign-form-handler.php';
require_once HASHBAR_WPNBP_DIR . '/inc/popup-analytics-processor.php';

// Popup Campaign REST API endpoints
require_once HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/popup-campaign-api.php';
require_once HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/popup-campaign-settings.php';
require_once HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/popup-analytics-api.php';
require_once HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/popup-ab-test-api.php';

// settings panel files
include_once( HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/admin-dashboard-api.php');
include_once( HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/changelog-api.php');
include_once( HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/recommended-plugins-api.php');
include_once( HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/pages-posts-ajax.php');
include_once( HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/admin-settings.php');
add_action('rest_api_init', function() {
    $plugins_api = new \HashBarPro\Api\Plugins();
    $plugins_api->register_routes();
});
function hashbar_remove_admin_notice(){
    $current_screen = get_current_screen();
    $hide_screen = ['edit-wphash_ntf_bar', 'wphash_ntf_bar', 'wphash_ntf_bar_page_hashbar_options_page', 'wphash_ntf_bar_page_hashbar-pro', 'update', 'toplevel_page_hashbar'];
    if( in_array( $current_screen->id, $hide_screen) ){
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
    }
}
add_action('in_admin_header', 'hashbar_remove_admin_notice', 1000);

// Deactivate the light version
register_activation_hook( HASHBAR_WPNBP_ROOT, 'hashbar_pro_register_activation_hook' );
function hashbar_pro_register_activation_hook(){
    if( is_plugin_active('hashbar-wp-notification-bar/init.php') ){
        deactivate_plugins('hashbar-wp-notification-bar/init.php');
    }

    // Create Pro database tables
    \Hashbar\Pro\DatabaseInstaller\Database_Installer::create_tables();

    // Create Announcement Bar database tables
    \Hashbar\Pro\ABTest\AB_Test_Database::create_tables();

    // Create Popup Campaign database tables
    \Hashbar\Pro\Hashbar_Popup_Campaign_Database::create_tables();

    $plugin_data = get_file_data( HASHBAR_WPNBP_ROOT, array('Version'=>'Version'), 'plugin' );
    $vesion = $plugin_data['Version'];

    if(version_compare($vesion,'1.2.6','>')){
        $args = array( 'post_type' => 'wphash_ntf_bar', 'posts_per_page' => -1 );

        $ntf_query = new WP_Query($args);

        while( $ntf_query->have_posts() ){
            $ntf_query->the_post();
            $post_id  = get_the_id();

            $exclude_ids = get_post_meta( $post_id , '_wphash_exclusion_page_for_notification', true );
            // update_post_meta(2409, '_log', 'azad'); 

            if(!empty($exclude_ids) && is_array($exclude_ids)){
                $implode_page_ids = implode(",",$exclude_ids);
                update_post_meta( $post_id, '_wphash_exclusion_page_for_notification', $implode_page_ids);
            }
        }
        wp_reset_query(); wp_reset_postdata();
    }
    
    if(!get_option('hashbar_sample_bar_added')) {

        // Check if the post already exists to prevent duplicates
        $existing_post = get_posts([
            'post_type'   => 'wphash_ntf_bar',
            'post_status' => 'draft',
            'numberposts' => 1,
        ]);
        if (!empty($existing_post)) {
            return; // If the post exists, do nothing
        }

        $block_content = '<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
        <div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontSize":"28px"},"layout":{"selfStretch":"fill","flexSize":null}}} -->
        <p style="font-size:28px">New Year, New Savings: Mega Bundle Upgrade Offer! Only <strong>$159</strong></p>
        <!-- /wp:paragraph -->
        <!-- wp:buttons -->
        <div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"palette-color-8","textColor":"palette-color-2","style":{"border":{"radius":"100px"},"elements":{"link":{"color":{"text":"var:preset|color|palette-color-2"}}}}} -->
        <div class="wp-block-button"><a class="wp-block-button__link has-palette-color-2-color has-palette-color-8-background-color has-text-color has-background has-link-color wp-element-button" style="border-radius:100px">Upgrade</a></div>
        <!-- /wp:button --></div>
        <!-- /wp:buttons --></div>
        <!-- /wp:group -->';

        // Prepare the post data
        $default_post = [
            'post_title'   => 'Sample Hashbar',
            'post_content' => $block_content, // Insert Gutenberg blocks here
            'post_status'  => 'draft',
            'post_type'    => 'wphash_ntf_bar', // Replace with your post type slug
        ];

        // Insert the post and get the ID
        $post_id = wp_insert_post($default_post);

        if (!is_wp_error($post_id)) {
            // Mark it as the default post with meta
            update_post_meta($post_id, '_wphash_notification_content_bg_image', [
                'url' => esc_url(HASHBAR_WPNBP_URI . '/assets/images/top-bar-bg.png'),
            ]);
            update_post_meta($post_id, '_wphash_notification_content_padding', [
                'padding_top' => '10px',
                'padding_right' => '0px',
                'padding_bottom' => '10px',
                'padding_left' => '0px',
            ]);
            update_option('hashbar_sample_bar_added', true);
            update_post_meta($post_id, '_wphash_notification_where_to_show', 'none');
        }
    }
}

add_action( 'plugins_loaded', 'hashbar_wpnbp_tablecreate' );
function hashbar_wpnbp_tablecreate(){

    $analytics_table_exist = get_option( 'hthb_analyticstbl_exist', $default = false );
    $plugin_data = get_file_data( HASHBAR_WPNBP_ROOT, array('Version'=>'Version'), 'plugin' );
    $vesion = $plugin_data['Version'];

    if($analytics_table_exist === false){
        if(version_compare($vesion,'1.2.6','>')){
            \Hashbar\Pro\DatabaseInstaller\Database_Installer::create_tables();
        }
    }
}

add_action('init', 'hashbar_wpnbp_upgrade_metadata');
function hashbar_wpnbp_upgrade_metadata(){
    $plugin_data = get_file_data( HASHBAR_WPNBP_ROOT, array('Version'=>'Version'), 'plugin' );
    
    $vesion      = $plugin_data['Version'];
    // Record the version number for future purpose
    $version_plain_number = str_replace('.', '', $vesion);
    if( !get_option('hashbar_pro_'. $version_plain_number ) ){
        add_option('hashbar_pro_'. $version_plain_number, true);
    }

    if( version_compare($vesion,'1.2.7','<') && get_option('hashbar_pro_1st_upgrade_completed') ){
        return;
    }

    // Upgrade when a notification bar has BG image or Date field is set
    // Before upgrade take a backup of existing value
    $args = array( 
        'post_type'      => 'wphash_ntf_bar', 
        'posts_per_page' => '-1',
        'post_status'    => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash')
    );
    $upgrade_query = new WP_Query( $args );

    while( $upgrade_query->have_posts() ){
        $upgrade_query->the_post();
        $post_id  = get_the_id();

        // Upgrade BG image field data
        $meta_values             = get_post_meta( $post_id );
        $old_bg_content_image    = get_post_meta( $post_id, '_wphash_notification_content_bg_image', true );
        $old_bg_content_image_id = get_post_meta( $post_id, '_wphash_notification_content_bg_image_id', true );
        
        if( $old_bg_content_image ){
            if( !is_array($old_bg_content_image) ){
                update_post_meta( $post_id, '__wphash_notification_content_bg_image', $old_bg_content_image );

                // replace old notfication bars meta value with new format
                // don't apply for the new notification bar which created by the current version
                update_post_meta( $post_id, '_wphash_notification_content_bg_image', array(
                    'url'       => $old_bg_content_image,
                    'id'        => $old_bg_content_image_id,
                    'thumbnail' => $old_bg_content_image
                ));
            }
        }

        // Upgrade date field
        $old_date = get_post_meta( $post_id, '_wphash_notification_schedule_datetime', true );
        if( $old_date ){
            if( !strpos($old_date, '/') ){
                update_post_meta( $post_id, '__wphash_notification_schedule_datetime', $old_date );
                update_post_meta( $post_id, '_wphash_notification_schedule_datetime', date('m/d/Y h:i a', $old_date) );
            }
        }
        
    }
    wp_reset_query(); wp_reset_postdata();

    add_option('hashbar_pro_1st_upgrade_completed', true);
}

// Add settings in plugin action
add_filter('plugin_action_links_'.plugin_basename(__FILE__),function( $links ){

    $link = sprintf( "<a href='%s'>%s</a>", esc_url(admin_url('edit.php?post_type=wphash_ntf_bar')), __('Settings','hashbar') );

    array_unshift( $links, $link );

    return $links;

});

// Define text domain path
function hashbar_wpnbp_textdomain() {
    load_plugin_textdomain( 'hashbar', false, basename(HASHBAR_WPNBP_URI) . '/languages/' );
}
add_action( 'init', 'hashbar_wpnbp_textdomain' );

// Enqueue scripts
add_action( 'wp_enqueue_scripts','hashbar_wpnbp_enqueue_scripts');
function  hashbar_wpnbp_enqueue_scripts(){

    // CSS
    wp_enqueue_style( 'material-design-iconic-font', HASHBAR_WPNBP_URI.'/assets/css/material-design-iconic-font.min.css', '', HASHBAR_WPNBP_VERSION);
    wp_enqueue_style( 'hashbar-pro-frontend', HASHBAR_WPNBP_URI.'/assets/css/frontend.css', '', time());
    wp_register_script( 'jquery-countdown', HASHBAR_WPNBP_URI.'/assets/js/jquery.countdown.min.js', array('jquery'),HASHBAR_WPNBP_VERSION, true);

    // JS
    wp_enqueue_script( 'js-cookie', HASHBAR_WPNBP_URI.'/assets/js/js.cookie.min.js', array('jquery'), HASHBAR_WPNBP_VERSION, false);
    wp_enqueue_script( 'hashbar-pro-frontend', HASHBAR_WPNBP_URI.'/assets/js/frontend.js', array('jquery'), time(), true );
    wp_enqueue_script( 'hashbar-pro-analytics', HASHBAR_WPNBP_URI.'/assets/js/analytics.js', array('jquery'),HASHBAR_WPNBP_VERSION, true );

    $checkbox_value            = hashbar_get_opt('dont_show_bar_after_close');
    $bar_closed_checkbox_value = hashbar_get_opt('keep_closed_bar');
    $cn_cookies_expire_time       = hashbar_get_opt('cn_cookies_expire_time');
    $cn_cookies_expire_type       = hashbar_get_opt('cn_cookies_expire_type');
    $localized_vars = array(
        'dont_show_bar_after_close' => $checkbox_value,
        'notification_display_time' => apply_filters("hashbar_wpnbp_dispaly_loading_time", 400 ),
        'bar_keep_closed'           => $bar_closed_checkbox_value,
        'cn_cookies_expire_time'       => $cn_cookies_expire_time,
        'cn_cookies_expire_type'       => $cn_cookies_expire_type,
    );

    $hashbar_localize_analytical_data = [
        'ajaxurl'          => admin_url( 'admin-ajax.php' ),
        'nonce_key'        => wp_create_nonce('hashbar_analytics'),
        'enable_analytics' => hashbar_get_opt('enable_analytics')
    ];

    // Localize
    wp_localize_script( 'hashbar-pro-frontend', 'hashbar_localize', $localized_vars );
    wp_localize_script( 'hashbar-pro-analytics', 'hashbar_analytical', $hashbar_localize_analytical_data );

}

// Enqueue admin scripts
add_action( 'admin_enqueue_scripts','hashbar_wpnbp_admin_scripts');
function  hashbar_wpnbp_admin_scripts(){
    // Enqueue CSS
    if((get_post_type() == 'wphash_ntf_bar' && isset($_GET['action']) && $_GET['action'] == 'edit') || (isset($_GET['post_type']) && $_GET['post_type'] == 'wphash_ntf_bar'))
    {   
        if( isset($_GET['page']) && $_GET['page'] == 'hashbar_analytics_page' ){
            wp_enqueue_style( 'google-fonts', '//fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap', '', HASHBAR_WPNBP_VERSION, false );
        }

        wp_enqueue_style( 'tooltipster-bundle', HASHBAR_WPNBP_URI.'/libs/tooltipster/css/tooltipster.bundle.min.css','',HASHBAR_WPNBP_VERSION);
        wp_enqueue_style( 'tooltipster-sidetip-light', HASHBAR_WPNBP_URI.'/libs/tooltipster/css/plugins/tooltipster/sideTip/themes/tooltipster-sideTip-light.min.css','',HASHBAR_WPNBP_VERSION);
        if(hashbar_wpnbp_check_pro_post()){
           wp_enqueue_style( 'hashbar-editor-icon', HASHBAR_WPNBP_URI.'/assets/css/material-design-iconic-font.min.css','',HASHBAR_WPNBP_VERSION);
        }
        wp_enqueue_style( 'jquery-ui-timepicker-addon', HASHBAR_WPNBP_URI. '/admin/css/jquery-ui-timepicker-addon.min.css','',HASHBAR_WPNBP_VERSION);
        wp_enqueue_style( 'hashbar-pro-admin', HASHBAR_WPNBP_URI.'/admin/css/admin.css','',HASHBAR_WPNBP_VERSION);

        // Enqueue JS
        wp_enqueue_script( 'tooltipster-bundle', HASHBAR_WPNBP_URI.'/libs/tooltipster/js/tooltipster.bundle.min.js', array('jquery'),HASHBAR_WPNBP_VERSION, false);
        wp_enqueue_script( 'jquery-ui-timepicker-addon', HASHBAR_WPNBP_URI. '/admin/js/jquery-ui-timepicker-addon.min.js', array('jquery', 'jquery-ui-datepicker'),HASHBAR_WPNBP_VERSION);
        wp_enqueue_script( 'hashbar-pro-admin', HASHBAR_WPNBP_URI .'/admin/js/admin.js', array( 'jquery', 'wp-blocks', 'wp-data'), time(), true );

        $hashbar_localize_data = [
            'ajaxurl'         => admin_url( 'admin-ajax.php' ),
            'hashbar_nonce'   => wp_create_nonce('hashbar_protected'),
            'hashbar_post_id' => isset( $_GET['post'] ) ? $_GET['post'] : "",
            'hashbar_plugin_uri' => HASHBAR_WPNBP_URI,
        ];

        wp_localize_script( 'hashbar-pro-admin', 'hashbar_admin', $hashbar_localize_data);
    }
}

add_action( 'wp_footer', 'hashbar_wpnbp_load_notification_to_footer' );
function hashbar_wpnbp_load_notification_to_footer(){

    if( class_exists( '\Elementor\Plugin' ) && ( \Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode() ) ){
        return;
    }

    $current_page_id = get_the_ID();
    $deagult_args = array( 'post_type' => 'wphash_ntf_bar', 'posts_per_page' => -1 );
    $args = apply_filters( "hashbar_query_args", $deagult_args );

    $ntf_query = new WP_Query($args);

    while( $ntf_query->have_posts() ){

        $ntf_query->the_post();
        $post_id  = get_the_id();

        // shedule notification meta
        $schedule = get_post_meta( $post_id, '_wphash_notification_schedule', true );
        $schedule_datetime = get_post_meta( $post_id, '_wphash_notification_schedule_datetime', true );

        if( $schedule == 'on' && $schedule_datetime){

            $schedyke_timestamp = strtotime($schedule_datetime);

            if( current_time('timestamp') > $schedyke_timestamp ){
                $draft_post = array();
                $draft_post['ID'] = $post_id;
                $draft_post['post_status'] = 'draft';
                // Update the post into the database
                wp_update_post( $draft_post );
            }

        }

        $where_to_show = get_post_meta( $post_id , '_wphash_notification_where_to_show', true );

        if( is_single() && $where_to_show == 'post' ){

            $ids_arr = get_post_meta( $post_id , '_wphash_notification_where_to_show_Post', true );
            if($ids_arr && in_array($current_page_id, $ids_arr)){
                hashbar_wpnbp_output($post_id);
            }
            
        }elseif( is_single() && $where_to_show == 'post_cat' ){
            $ids_arr = get_post_meta( $post_id , '_wphash_notification_where_to_show_Categories', true );
            $post_categories = wp_get_post_categories($current_page_id, array('fields' => 'ids'));
            if(count(array_intersect($post_categories, $ids_arr)) > 0) {
                hashbar_wpnbp_output($post_id);
            }
        }elseif( is_single() && $where_to_show == 'post_tags' ){
            $ids_arr = get_post_meta( $post_id , '_wphash_notification_where_to_show_Tags', true );
            $post_tags = wp_get_post_tags($current_page_id, array('fields' => 'ids'));
            if(count(array_intersect($post_tags, $ids_arr)) > 0) {
                hashbar_wpnbp_output($post_id);
            }
        }elseif( is_page() && $where_to_show == 'page' ){

            $ids_arr = get_post_meta( $post_id , '_wphash_notification_where_to_show_Page', true );

            if(function_exists( 'is_cart' ) && is_cart()){
                $get_cart_page_id = get_option( 'woocommerce_cart_page_id' );
                if( $ids_arr && in_array( $get_cart_page_id, $ids_arr ) ){
                    hashbar_wpnbp_output( $post_id );
                } 
            }elseif(function_exists( 'is_checkout' ) && is_checkout()){
                $get_checkout_page_id = get_option( 'woocommerce_checkout_page_id' );
                if( $ids_arr && in_array( $get_checkout_page_id, $ids_arr ) ){
                    hashbar_wpnbp_output( $post_id );
                } 
            }else{
                if( $ids_arr && in_array( $current_page_id, $ids_arr ) ){
                    hashbar_wpnbp_output( $post_id );
                }
            }

        }elseif( function_exists( 'is_shop' ) && is_shop() && $where_to_show == 'page' ){

            $ids_arr = get_post_meta( $post_id , '_wphash_notification_where_to_show_Page', true );
            $get_shop_page_id = get_option( 'woocommerce_shop_page_id' );
            if( $ids_arr && in_array( $get_shop_page_id, $ids_arr ) ){
                hashbar_wpnbp_output( $post_id );
            }

        }elseif( $where_to_show == 'product' && is_singular($post_types = 'product')){

            $ids_arr = get_post_meta( $post_id , '_wphash_notification_where_to_show_Product', true );
            if( $ids_arr && in_array( $current_page_id, $ids_arr ) ){
                hashbar_wpnbp_output( $post_id );
            }

        }elseif( function_exists('is_product') && $where_to_show == 'woo_catagories' ){
            $selected_woo_categories = get_post_meta( $post_id , '_wphash_woocommerce_categories', true );
            $disable_for_archive     = get_post_meta( $post_id , '_wphash_woocommerce_categories_archive_optin', true );
            $all_products_by_cat     = hashbar_wpnbp_allporduct_by_cat($selected_woo_categories, 'id');
            $current_page_obj        = get_queried_object();
            $current_cat_id          = isset($current_page_obj->term_id) ? $current_page_obj->term_id : '';

            if( is_product() && $all_products_by_cat && in_array( $current_page_id, $all_products_by_cat ) ){

                hashbar_wpnbp_output( $post_id );

            } elseif( !$disable_for_archive && is_product_category() && $current_cat_id && in_array($current_cat_id, $selected_woo_categories)){

                hashbar_wpnbp_output( $post_id );
            }

        } elseif ( $where_to_show == 'homepage' && is_front_page() ){

            hashbar_wpnbp_output($post_id);

        } elseif ( $where_to_show == 'everywhere' ){

            $exclude_ids = get_post_meta( $post_id , '_wphash_exclusion_page_for_notification', true );

            $exclude_ids_arr = [];
            if(!empty($exclude_ids)) {
                $exclude_ids_arr = explode(",",$exclude_ids);
            }
 
            if(is_front_page()){
                $page_on_front = get_option('page_on_front');
                if($exclude_ids_arr && in_array($page_on_front,$exclude_ids_arr)){
                    hashbar_wpnbp_output('');
                }else{
                    hashbar_wpnbp_output($post_id);
                }
            }elseif(function_exists( 'is_shop' ) && is_shop()){
                $get_shope_page_id = get_option( 'woocommerce_shop_page_id' );
                if($exclude_ids_arr && in_array($get_shope_page_id,$exclude_ids_arr)){
                    hashbar_wpnbp_output('');
                }else{
                    hashbar_wpnbp_output($post_id);
                }
            }elseif(function_exists( 'is_cart' ) && is_cart()){
                $get_cart_page_id = get_option( 'woocommerce_cart_page_id' );
                if($exclude_ids_arr && in_array($get_cart_page_id,$exclude_ids_arr)){
                    hashbar_wpnbp_output('');
                }else{
                    hashbar_wpnbp_output($post_id);
                }
            }elseif(function_exists( 'is_checkout' ) && is_checkout()){
                $get_checkout_page_id = get_option( 'woocommerce_checkout_page_id' );
                if($exclude_ids_arr && in_array($get_checkout_page_id,$exclude_ids_arr)){
                    hashbar_wpnbp_output('');
                }else{
                    hashbar_wpnbp_output($post_id);
                }
            }else{
                if($exclude_ids_arr && in_array($current_page_id,$exclude_ids_arr)){
                    hashbar_wpnbp_output('');
                }else{
                    hashbar_wpnbp_output($post_id); 
                }
            }

        } elseif( $where_to_show == 'url_param' ){

            $page_url_param = get_post_meta( $post_id, '_wphash_url_param', true );
            $url_param = isset( $_GET['param'] )  && $_GET['param'] ? $_GET['param'] : '';

            if($page_url_param == $url_param){
                hashbar_wpnbp_output( $post_id );
            }

        } elseif( $where_to_show == 'specific_ids' ){

            $ids_arr = get_post_meta( $post_id , '_wphash_specific_post_ids', true );
            $ids_arr = explode( ',', $ids_arr );

            if( $ids_arr && in_array( $current_page_id, $ids_arr ) ){
               hashbar_wpnbp_output( $post_id );
            }

        } elseif( $where_to_show == 'custom' ){
            $where_to_show_custom =  get_post_meta( $post_id , '_wphash_notification_where_to_show_custom', true );

            if( !empty($where_to_show_custom) && is_array($where_to_show_custom) ){
                foreach( $where_to_show_custom as $item){
                    if(is_front_page() && $item == 'home'){
                       hashbar_wpnbp_output($post_id);
                    }

                    if(is_single() && get_post_type($current_page_id) == 'post' && $item == 'posts'){
                        hashbar_wpnbp_output($post_id);
                    }

                    if(is_page() && get_post_type($current_page_id) == 'page' && $item == 'page' ){
                       hashbar_wpnbp_output($post_id);
                    }

                    if( function_exists('is_product') && is_product() && $item == 'products' ){
                       hashbar_wpnbp_output($post_id);
                    }
                }
            }
        }
    }
    wp_reset_query(); wp_reset_postdata();
}

// Notification bar output
function hashbar_wpnbp_output($post_id){
    // Don't load notification bar in admin
    if( is_admin() || is_customize_preview() ){
        return;
    }
    
    if($post_id):

        $hashbar_wpnbp_opt = get_option( 'hashbar_wpnbp_opt');
        $positon = get_post_meta( $post_id , '_wphash_notification_position', true );

        if( empty( $positon ) || $positon == 'ht-n-top' ){
            $positon =  'hthb-pos--top';
        } elseif( $positon == 'ht-n-bottom' ){
            $positon =  'hthb-pos--bottom';
        } elseif( $positon == 'ht-n-left' ){
            $positon = 'hthb-pos--left-wall';
        } elseif( $positon == 'ht-n-right' ){
            $positon = 'hthb-pos--right-wall';
        } elseif( $positon == 'ht-n_toppromo' ){
            $positon = 'hthb-pos--top-promo';
        } elseif( $positon == 'ht-n_bottompromo' ){
            $positon = 'hthb-pos--bottom-promo';
        }

        $notification_sticky = get_post_meta( $post_id , '_wphash_notification_sticky', true );
        $where_to_show = get_post_meta( $post_id , '_wphash_show_hide_scroll', true );
        $scroll_trigger_status = get_post_meta($post_id, '_wphash_show_hide_scroll', true);

        $width = get_post_meta( $post_id , '_wphash_notification_width', true );
        $height = get_post_meta( $post_id , '_wphash_notification_height', true );
        $margin = get_post_meta($post_id,'_wphash_notification_content_margin');
        $padding = get_post_meta($post_id,'_wphash_notification_content_padding');
        $mobile_height = get_post_meta( $post_id , '_wphash_notification_mobile_height', true );
        $count_down = get_post_meta( $post_id , '_wphash_count_down', true );
        $count_position = get_post_meta( $post_id , '_wphash_countdown_position', true );
        $delay_time = (int) get_post_meta($post_id, '_wphash_notification_delay_time', true);

        $header_type          = get_post_meta( $post_id , '_wphash_themes_header_type', true );
        $transparent_selector = '';
        if( $positon == 'hthb-pos--top' && $header_type == 'transparent' ){
            $transparent_selector = get_post_meta( $post_id , '_wphash_notification_transparent_selector', true );
        }else{
            $header_type = '';
        }

        $sticky_hide          = get_post_meta( $post_id , '_wphash_sticky_on_hide_status', true );

        $on_desktop = get_post_meta( $post_id, '_wphash_notification_on_desktop', true );
        $on_mobile  = get_post_meta( $post_id, '_wphash_notification_on_mobile', true );

        // Notification state
        $display = get_post_meta( $post_id , '_wphash_notification_display', true );
        $keep_closed = '';
        $keep_closed_option = isset( $hashbar_wpnbp_opt['keep_closed_bar'] ) ? $hashbar_wpnbp_opt['keep_closed_bar'] : '';
        $keep_closed_cookie = isset($_COOKIE['keep_closed_bar_'.$post_id]) ? $_COOKIE['keep_closed_bar_'.$post_id] : '';
        if( $keep_closed_option && $keep_closed_cookie ) {
            $keep_closed = true;
        }

        
        $scroll_to_show = get_post_meta($post_id, '_wphash_show_scroll_position', true);
        $scroll_to_hide = get_post_meta($post_id, '_wphash_hide_scroll_position', true);
        if( $scroll_trigger_status != 'show_hide_scroll_enable' ){
            $scroll_to_show = '';
            $scroll_to_hide = '';
        }

        $display = ($display == 'ht-n-open') ? 'hthb-state--open' : 'hthb-state--minimized';
        if( $keep_closed ){
            $display = 'hthb-state--minimized';
        } elseif ($delay_time){
            $display = 'hthb-state--minimized';
        } else{
            if( $scroll_trigger_status == 'show_hide_scroll_enable' && $scroll_to_show ){
                $display = 'hthb-state--minimized';
            }
        }

        $content_width = get_post_meta( $post_id, '_wphash_notification_content_width', true );

        $content_color = get_post_meta( $post_id, '_wphash_notification_content_text_color', true );
        $content_bg_color = get_post_meta( $post_id, '_wphash_notification_content_bg_color', true );
        $content_border = get_post_meta( $post_id, '_wphash_notification_content_border', true );
        $content_bg_image = get_post_meta( $post_id, '_wphash_notification_content_bg_image', true );
        $content_bg_opacity = get_post_meta( $post_id, '_wphash_notification_content_bg_opcacity', true );

        // Button options
        $close_button = get_post_meta( $post_id, '_wphash_notification_close_button', true );
        $close_button_class = '';
        $close_button_text = '';
        $open_button_text = '';
        if($close_button != 'off'){
            $close_button_class = 'hthb-has-close-button';
            $close_button_text  = get_post_meta( $post_id, '_wphash_notification_close_button_text', true );
            $open_button_text   = get_post_meta( $post_id, '_wphash_notification_open_button_text', true );
        }

        
        $button_margin = get_post_meta( $post_id,'_wphash_notification_button_margin' );
        $button_padding = get_post_meta( $post_id,'_wphash_notification_button_padding' );

        $close_button_bg_color = get_post_meta( $post_id, '_wphash_notification_close_button_bg_color', true );
        $close_button_color = get_post_meta( $post_id, '_wphash_notification_close_button_color', true );
        $close_button_hover_color = get_post_meta( $post_id, '_wphash_notification_close_button_hover_color', true );
        $close_button_hover_bg_color = get_post_meta( $post_id, '_wphash_notification_close_button_hover_bg_color', true );

        $arrow_color = get_post_meta( $post_id, '_wphash_notification_arrow_color', true );
        $arrow_bg_color = get_post_meta( $post_id, '_wphash_notification_arrow_bg_color', true );
        $arrow_hover_color = get_post_meta( $post_id, '_wphash_notification_arrow_hover_color', true );
        $arrow_hover_bg_color = get_post_meta( $post_id, '_wphash_notification_arrow_hover_bg_color', true );
        $prb_margin = get_post_meta( $post_id,'_wphash_prb_margin' );

        $css_style = '';
        if( !empty( $content_color ) ){
            $css_style .= "#notification-$post_id .hthb-notification-content,#notification-$post_id .hthb-notification-content p{color:$content_color}";
        }

        if( !empty( $content_bg_color ) ){
            $css_style .= "#notification-$post_id::before{background-color:$content_bg_color}";
        }

        if( !empty( $content_bg_image ) && isset($content_bg_image['url']) ){
            $content_bg_image = $content_bg_image['url'];
            $css_style .= "#notification-$post_id::before{background-image:url($content_bg_image)}";
        }
        
        if( !empty( $content_border ) ){
            $css_style .= "#notification-" . esc_attr($post_id) . "{";
                foreach ( $content_border as $key => $value ) {
                    if ( in_array( $key, [ 'top', 'right', 'bottom', 'left' ] ) && is_numeric( $value ) ) {
                        $css_style .= "border-$key: " . intval( $value ) . "px " . esc_attr( $content_border['style'] ) . " " . esc_attr( $content_border['color'] ) . ";";
                    }
                }
            $css_style .= "}";
        }

        if( !empty( $content_bg_opacity ) ){
            $css_style .= "#notification-$post_id::before{opacity:$content_bg_opacity}";
        }


        if($width){
            if( 'hthb-pos--bottom-promo' == $positon || 'hthb-pos--top-promo' == $positon ){
                $css_style .= "#notification-$post_id .hthb-notification-content .ht-promo-banner{width:$width}";
                $css_style .= "#notification-$post_id .hthb-notification-content .ht-promo-banner-image a img{width:$width !important}";
            }else{
                $css_style .= "#notification-$post_id{max-width:$width}";
            }
        }

        if($margin && is_array($margin[0])){
            $css_style .= "#notification-$post_id .hthb-notification-content{margin:".$margin[0]['margin_top']." ".$margin[0]['margin_right']." ".$margin[0]['margin_bottom']." ".$margin[0]['margin_left']."}";
        }

        if($padding && is_array($padding[0])){
            $css_style .= "#notification-$post_id .hthb-notification-content{padding:".$padding[0]['padding_top']." ".$padding[0]['padding_right']." ".$padding[0]['padding_bottom']." ".$padding[0]['padding_left']."}";
        }

        if($button_margin && is_array($button_margin[0])){
            $css_style .= "#notification-$post_id .hthb-notification-content .ht_btn{margin:".$button_margin[0]['button_margin_top']." ".$button_margin[0]['button_margin_right']." ".$button_margin[0]['button_margin_bottom']." ".$button_margin[0]['button_margin_left']."}";
        }

        if($button_padding && is_array($button_padding[0])){
            $css_style .= "#notification-$post_id .hthb-notification-content .ht_btn{padding:".$button_padding[0]['button_padding_top']." ".$button_padding[0]['button_padding_right']." ".$button_padding[0]['button_padding_bottom']." ".$button_padding[0]['button_padding_left']."}";
        }

        if( $positon == 'hthb-pos--top' || $positon == 'ht-n-bottom' ){
            $css_style .= "#notification-$post_id.hthb-state--open{height:{$height}px}";
        }

        //promo banner position
        $prb_margin_top    = $prb_margin && is_array($prb_margin[0]) && !empty($prb_margin[0]['margin_top']) ? $prb_margin[0]['margin_top'] : '';
        $prb_margin_right  = $prb_margin && is_array($prb_margin[0]) && !empty($prb_margin[0]['margin_right']) ? $prb_margin[0]['margin_right'] : '';
        $prb_margin_bottom = $prb_margin && is_array($prb_margin[0]) && !empty($prb_margin[0]['margin_bottom']) ? $prb_margin[0]['margin_bottom'] : '';
        $prb_margin_left   = $prb_margin && is_array($prb_margin[0]) && !empty($prb_margin[0]['margin_left']) ? $prb_margin[0]['margin_left'] : '';
        $promo_top_alignment = get_post_meta( $post_id, '_wphash_promo_banner_top_display', true );
        $promo_bottom_alignment = get_post_meta( $post_id, '_wphash_promo_banner_bottom_display', true );
        $promo_alignment_class = '';

        if($positon == 'hthb-pos--top-promo'){
            if ($promo_top_alignment == 'promo-top-left' ){
                $promo_alignment_class = 'hthb-promo-alignment--left';
                $css_style .= "#notification-$post_id.hthb-pos--top-promo{margin-left:{$prb_margin_left};margin-top:{$prb_margin_top}}";
            } else{
                $promo_alignment_class = 'hthb-promo-alignment--right';
                $css_style .= "#notification-$post_id.hthb-pos--top-promo{margin-right:{$prb_margin_right};margin-top:{$prb_margin_top}}";
            }
        } elseif($positon == 'hthb-pos--bottom-promo'){
            if ($promo_bottom_alignment == 'promo-bottom-left' ){
                $promo_alignment_class = 'hthb-promo-alignment--left';
                $css_style .= "#notification-$post_id.hthb-pos--bottom-promo{margin-left:{$prb_margin_left};margin-bottom:{$prb_margin_bottom}}";
            } else{
                $promo_alignment_class = 'hthb-promo-alignment--right';
                $css_style .= "#notification-$post_id.hthb-pos--bottom-promo{margin-right:{$prb_margin_right};margin-bottom:{$prb_margin_bottom}}";
            }
        }

        if($close_button_bg_color) $css_style .= "#notification-$post_id .hthb-close-toggle{background-color:$close_button_bg_color}";
        if($close_button_color) $css_style .= "#notification-$post_id .hthb-close-toggle,#notification-$post_id .hthb-close-toggle svg path{fill:$close_button_color}";
        if($close_button_hover_bg_color) $css_style .= "#notification-$post_id .hthb-close-toggle:hover{background-color:$close_button_hover_bg_color}";
        if($close_button_hover_color) $css_style .= "#notification-$post_id .hthb-close-toggle:hover{color:$close_button_hover_color}";
        if($close_button_hover_color) $css_style .= "#notification-$post_id .hthb-close-toggle:hover svg path{fill:$close_button_hover_color}";

        if($arrow_bg_color) $css_style .= "#notification-$post_id .hthb-open-toggle{background-color:$arrow_bg_color}";
        if($arrow_color) $css_style .= "#notification-$post_id .hthb-open-toggle{color:$arrow_color}";

        if($arrow_hover_color) $css_style .= "#notification-$post_id .hthb-open-toggle:hover i{color:$arrow_hover_color}";
        if($arrow_hover_bg_color) $css_style .= "#notification-$post_id .hthb-open-toggle:hover{background-color:$arrow_hover_bg_color}";

        if( $positon == 'hthb-pos--top' ){
            // If Sticky is off
            if( $sticky_hide == 'yes' && $header_type != 'none' ){
                $css_style .= "#notification-$post_id.hthb-state--open{position:absolute !important;}";
                $css_style .= "html body $transparent_selector.htnfix-header{top:0 !important; transition: background-color .4s,color .4s,transform .4s,opacity .4s ease-in-out,-webkit-transform .4s;}";

                $css_style .= ".admin-bar $transparent_selector.htnfix-header{top:32px !important; transition: background-color .4s,color .4s,transform .4s,opacity .4s ease-in-out,-webkit-transform .4s;}";
            }
        }

        //Notification open toggle button
        $ntf_open_toggle = get_post_meta( $post_id, '_wphash_hide_open_toggle', true );

        if('ntf_open_toggle_disable' == $ntf_open_toggle){
            $css_style .= "#notification-".$post_id." .hthb-open-toggle{display: none;}";
        }

        // Mobile device breakpoint
        $mobile_device_width  = isset( $hashbar_wpnbp_opt['mobile_device_breakpoint'] ) ? $hashbar_wpnbp_opt['mobile_device_breakpoint'] : '';
        $mobile_device_width  = empty( $mobile_device_width ) ? 768 : $mobile_device_width; 
        $desktop_device_width = $mobile_device_width + 1;

        $responsive_style = '';

        if( $on_mobile == 'off' ){
            $margin_top = '';
            $padding_bottom = '';
            if($positon == 'hthb-pos--top'){
                $margin_top = 'margin-top:0 !important;';
            } elseif( $positon == 'ht-n-bottom' ){
                $padding_bottom = 'padding-bottom:0 !important;';
            }

            $responsive_style = "@media (max-width: ".$mobile_device_width ."px){#notification-$post_id{display:none !important;} body.htnotification-mobile{ $margin_top $padding_bottom} #notification-$post_id.hthb-state--open{height:{$mobile_height}px} }";
        }else{
           $responsive_style = "@media (max-width: ".$mobile_device_width ."px){ #notification-$post_id.hthb-state--open{height:{$mobile_height}px;} }"; 
        }

        if( $on_desktop == 'off' ){
            $responsive_style = "@media (min-width: ". $desktop_device_width ."px){#notification-$post_id{display:none  !important}}";
        }

        if( $on_mobile == 'off' && $on_desktop == 'off'){
            $css_style .= "#notification-$post_id{display:none !important;}";
        }

        $dont_show_bar_after_close = isset( $hashbar_wpnbp_opt['dont_show_bar_after_close'] ) ? $hashbar_wpnbp_opt['dont_show_bar_after_close'] : '';

        // Get the number input of how many time this notifcation will show
        $how_many_time_to_show = get_post_meta( $post_id, '_wphash_notification_how_many_times_to_show', true );
        $how_many_time_to_show = (int) $how_many_time_to_show;

        // Dont show if dont_show_bar bar coockie value is 1
        if(
            ( $dont_show_bar_after_close == '' || !( isset($_COOKIE['dont_show_bar_'.$post_id]) && $_COOKIE['dont_show_bar_'.$post_id] == '1' ) )
        ):  
            if( 'ht-n_bottompromo' == $positon ){
                $positon = 'ht-n-bottom ht-n_bottompromo';
            }

            if( 'ht-n_toppromo' == $positon ){
                $positon = 'hthb-pos--top ht-n_toppromo';
            }
        ?>

        <!--Notification Section-->
        <?php
            $open_button_text_class = '';
            $close_button_text_class = '';
            
            if($close_button_text){
                $close_button_text_class = 'hthb-has-close-button-text';
            }
            
            if($open_button_text){
                $open_button_text_class = 'hthb-has-open-button-text';
            }

            $notification_bar_classes_arr = array();
            $notification_bar_classes_arr[] = 'hthb-notification ht-notification-section';
            $notification_bar_classes_arr[] = 'hthb-'.$header_type;
            $notification_bar_classes_arr[] = $close_button_class;
            $notification_bar_classes_arr[] = $open_button_text_class;
            $notification_bar_classes_arr[] = $close_button_text_class;
            $notification_bar_classes_arr[] = $promo_alignment_class;
            $notification_bar_classes_arr[] = $positon;
            $notification_bar_classes_arr[] = $display;
            $notification_bar_classes_arr[] = $where_to_show == 'show_hide_scroll_enable' ? 'hthb-scroll' : '';
            $notification_bar_classes_arr[] = $count_down == 'ntf_countdown_enable' ? 'hthb-countdown' : ''; 
            $notification_bar_classes_arr[] = $count_position == 'center' ? 'hthb-countdown-center' : '';
            $notification_bar_classes_arr[] = $positon == 'hthb-pos--top' && $notification_sticky == '0' ? 'hthb-absolute' : '';

            $notification_bar_classes = implode(' ', $notification_bar_classes_arr);

            $notification_region_label = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );
            if ( '' === $notification_region_label ) {
                $notification_region_label = __( 'Site notification', 'hashbar' );
            }
        ?>
        <div id="notification-<?php echo esc_attr( $post_id ); ?>"
            style="visibility: hidden;"
            role="region"
            aria-label="<?php echo esc_attr( $notification_region_label ); ?>"
            <?php hashbar_render_html_attr('data-id', $post_id); ?>
            <?php hashbar_render_html_attr('data-transparent_header_selector', $transparent_selector); ?>
            <?php hashbar_render_html_attr('data-scroll_to_show', $scroll_to_show); ?>
            <?php hashbar_render_html_attr('data-scroll_to_hide', $scroll_to_hide); ?>
            <?php hashbar_render_html_attr('data-time_to_show', $how_many_time_to_show); ?>
            <?php if($delay_time) hashbar_render_html_attr('data-delay_time', $delay_time); ?>
            class="<?php echo esc_attr($notification_bar_classes); ?>">

            <!--Notification Open Buttons-->
            <?php if ( empty( $open_button_text ) ) : ?>
                <span class="hthb-open-toggle" role="button" tabindex="0" aria-label="<?php echo esc_attr__( 'Show notification', 'hashbar' ); ?>">
                    <svg aria-hidden="true" focusable="false" enable-background="new 0 0 64 64" height="25" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><path d="m37.379 12.552c-.799-.761-2.066-.731-2.827.069-.762.8-.73 2.066.069 2.828l15.342 14.551h-39.963c-1.104 0-2 .896-2 2s.896 2 2 2h39.899l-15.278 14.552c-.8.762-.831 2.028-.069 2.828.393.412.92.62 1.448.62.496 0 .992-.183 1.379-.552l17.449-16.62c.756-.755 1.172-1.759 1.172-2.828s-.416-2.073-1.207-2.862z" fill="#ffffff"/></svg>
                </span>
            <?php else : ?>
                <span class="hthb-open-toggle" role="button" tabindex="0"><span><?php echo esc_html( $open_button_text ); ?></span></span>
            <?php endif; ?>

            <div class="hthb-row">
                <div class="<?php echo $content_width == 'ht-n-full-width' ? esc_attr( 'hthb-full-width' ) : esc_attr('hthb-container'); ?>">

                    <!--Notification Buttons-->
                    <div class="hthb-close-toggle-wrapper">
                        <span class="hthb-close-toggle" role="button" tabindex="0" data-id="<?php echo esc_attr( $post_id ); ?>" data-text="<?php echo esc_attr( $close_button_text ); ?>"<?php echo empty( $close_button_text ) ? ' aria-label="' . esc_attr__( 'Close notification', 'hashbar' ) . '"' : ''; ?>>
                            <svg aria-hidden="true" focusable="false" version="1.1" width="15" height="25" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                 viewBox="0 0 496.096 496.096" style="enable-background:new 0 0 496.096 496.096;" xml:space="preserve">
                                <path d="M259.41,247.998L493.754,13.654c3.123-3.124,3.123-8.188,0-11.312c-3.124-3.123-8.188-3.123-11.312,0L248.098,236.686
                                        L13.754,2.342C10.576-0.727,5.512-0.639,2.442,2.539c-2.994,3.1-2.994,8.015,0,11.115l234.344,234.344L2.442,482.342
                                        c-3.178,3.07-3.266,8.134-0.196,11.312s8.134,3.266,11.312,0.196c0.067-0.064,0.132-0.13,0.196-0.196L248.098,259.31
                                        l234.344,234.344c3.178,3.07,8.242,2.982,11.312-0.196c2.995-3.1,2.995-8.016,0-11.116L259.41,247.998z" fill="#ffffff" data-original="#000000"/>
                            </svg>
                            <span class="hthb-close-text"><?php echo esc_html( $close_button_text ); ?></span>
                        </span>
                    </div>

                    <!--Notification Text-->
                    <div class="hthb-notification-content ht-notification-text">
                        <?php 
                            if($count_down == 'ntf_countdown_enable' && $count_position != 'shortcode'){
                                echo hashbar_do_shortcode('hashbar_countdown');
                            }
                        ?>
                        <?php the_content(); ?>
                    </div>

                </div>
            </div>
        </div>

        <style type="text/css">
            <?php echo esc_html( $css_style.$responsive_style ); ?>
        </style>

        <?php

        endif;
    endif;
}


// Page builder support for content editor
add_action( 'init', 'hashbar_wpnbp_page_builder_support' );
function hashbar_wpnbp_page_builder_support(){
    
    // King composer support
    global $kc;

    if( $kc ){
        $kc->add_content_type( 'wphash_ntf_bar' );
    }

    // VC support
    if( class_exists( 'VC_Manager' ) ){
        $default_post_types = vc_default_editor_post_types();

        if(!in_array('wphash_ntf_bar', $default_post_types)){
            $default_post_types[] = 'wphash_ntf_bar';
        }

        vc_set_default_editor_post_types( $default_post_types );
    }

}

// add status column in hashbar post list
add_filter('manage_wphash_ntf_bar_posts_columns', 'hashbar_status_column');
if ( !function_exists( 'hashbar_status_column' ) ){
    function hashbar_status_column($columns){
        $offset = array_search('date', array_keys($columns));
        return array_merge(array_slice($columns, 0, $offset), ['status' => __('Where to show', 'hashbar')], array_slice($columns, $offset, null));
    }
}

// add status value in column
add_action('manage_wphash_ntf_bar_posts_custom_column', 'hashbar_status_value', 10, 2);
if ( !function_exists( 'hashbar_status_value' ) ){
    function hashbar_status_value($column_name, $post_ID){
        if ($column_name == 'status') {
            $hashabar_post_status = get_post_meta( $post_ID, '_wphash_notification_where_to_show', true );
                if ($hashabar_post_status) {
                    ?>
                        <p style="text-transform: capitalize; font-size: 15px;"><?php echo str_replace('_', ' ', $hashabar_post_status) ?></p>
                    <?php
                }
            
        }
    }
}

// Hashbar Single Template
function hashbar_wpnbp_template( $single_template ) {

    if( defined('ELEMENTOR_PATH') ){
        global $post;
        if ( 'wphash_ntf_bar' == $post->post_type ) {
            $elementor_2_0_canvas = ELEMENTOR_PATH . '/modules/page-templates/templates/canvas.php';
            if ( file_exists( $elementor_2_0_canvas ) ) {
                return $elementor_2_0_canvas;
            } else {
                return ELEMENTOR_PATH . '/includes/page-templates/canvas.php';
            }
        }
    }

    return $single_template;    
}
add_filter( 'single_template', 'hashbar_wpnbp_template' );


// Deactivate the free plugin if active
if( is_plugin_active( 'hashbar-wp-notification-bar/init.php' ) ){
    add_action('update_option_active_plugins', function(){
        deactivate_plugins( 'hashbar-wp-notification-bar/init.php' );
    });
}