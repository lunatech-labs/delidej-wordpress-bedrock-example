<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('WP_REST_Response')) {
    require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-response.php';
    require_once ABSPATH . 'wp-includes/rest-api.php';
}

if (!class_exists('Hashbar_Settings_Panel_Settings')) {
    require_once HASHBAR_WPNBP_DIR . '/admin/settings-panel/api/admin-settings.php';
}

/**
 * Hashbar REST API Handler Class
 * 
 * Handles all REST API endpoints for the Hashbar plugin
 * 
 * @since 1.0.0
 */
class Hashbar_REST_API {

    /**
     * Instance of this class
     * 
     * @var Hashbar_REST_API|null
     */
    private static $_instance = null;

    /**
     * API namespace
     * 
     * @var string
     */
    private $namespace = 'hashbar/v1';

    /**
     * Get instance of the class
     * 
     * @return Hashbar_REST_API
     */
    public static function get_instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register all REST API routes
     * 
     * @return void
     */
    public function register_routes() {
        // Get sidebar content endpoint
        register_rest_route($this->namespace, '/sidebar-content', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_sidebar_content'),
            'permission_callback' => array($this, 'check_manage_options_permission')
        ));

        // Get all notifications endpoint
        register_rest_route($this->namespace, '/notifications', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_notifications'),
            'permission_callback' => array($this, 'check_manage_options_permission')
        ));
        
        // Delete notification endpoint
        register_rest_route($this->namespace, '/notifications/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_notification'),
            'permission_callback' => array($this, 'check_manage_options_permission')
        ));

        // Update notification endpoint
        register_rest_route($this->namespace, '/notifications/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_notification'),
            'permission_callback' => array($this, 'check_manage_options_permission')
        ));

        // Get pages endpoint
        register_rest_route($this->namespace, '/pages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_pages'),
            'permission_callback' => array($this, 'check_edit_pages_permission')
        ));
        
        // Get posts endpoint
        register_rest_route($this->namespace, '/posts', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_posts'),
            'permission_callback' => array($this, 'check_edit_posts_permission')
        ));
        
        // Update dashboard settings endpoint
        register_rest_route($this->namespace, '/update-dashboard-settings', array(
            'methods' => 'POST',
            'callback' => array($this, 'update_dashboard_settings'),
            'permission_callback' => array($this, 'check_manage_options_permission')
        ));
        
        // Get analytics data endpoint
        register_rest_route($this->namespace, '/analytics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_analytics_data'),
            'permission_callback' => array($this, 'check_manage_options_permission')
        ));

        // Reset settings endpoint
        register_rest_route($this->namespace, '/reset-settings', array(
            'methods' => 'POST',
            'callback' => array($this, 'reset_settings'),
            'permission_callback' => array($this, 'check_manage_options_permission')
        ));

        // Duplicate post endpoint
        register_rest_route($this->namespace, '/duplicate-post', array(
            'methods' => 'POST',
            'callback' => array($this, 'duplicate_post'),
            'permission_callback' => array($this, 'check_manage_options_permission')
        ));
    }

    /**
     * Permission callback for manage_options capability
     * 
     * @return bool
     */
    public function check_manage_options_permission() {
        return current_user_can('manage_options');
    }

    /**
     * Permission callback for edit_pages capability
     * 
     * @return bool
     */
    public function check_edit_pages_permission() {
        return current_user_can('edit_pages');
    }

    /**
     * Permission callback for edit_posts capability
     * 
     * @return bool
     */
    public function check_edit_posts_permission() {
        return current_user_can('edit_posts');
    }

    /**
     * Duplicate a post
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response Response object
     */
    public function duplicate_post($request) {
        try {
            global $wpdb;
            $post_id = absint($request->get_param('post_id'));

            if (!$post_id) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => esc_html__('No post ID provided', 'hashbar')
                ), 400);
            }

            $post = get_post($post_id);
            if (!$post) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => esc_html__('Post not found', 'hashbar')
                ), 404);
            }

            // Get current user as post author
            $current_user = wp_get_current_user();
            $new_post_author = $current_user->ID;

            // Create the post duplicate
            $args = array(
                'comment_status' => $post->comment_status,
                'ping_status'    => $post->ping_status,
                'post_author'    => $new_post_author,
                'post_content'   => $post->post_content,
                'post_excerpt'   => $post->post_excerpt,
                'post_name'      => $post->post_name,
                'post_parent'    => $post->post_parent,
                'post_password'  => $post->post_password,
                'post_status'    => 'draft',
                'post_title'     => $post->post_title . ' (Copy)',
                'post_type'      => $post->post_type,
                'to_ping'        => $post->to_ping,
                'menu_order'     => $post->menu_order
            );

            $new_post_id = wp_insert_post($args);
            if (is_wp_error($new_post_id)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => esc_html__('Failed to create post duplicate', 'hashbar')
                ), 500);
            }

            // Copy post taxonomies
            $this->copy_post_taxonomies($post_id, $new_post_id, $post->post_type);

            // Copy post meta
            $this->copy_post_meta($post_id, $new_post_id);

            // Set default meta for notifications
            update_post_meta($new_post_id, '_wphash_notification_where_to_show', 'none');

            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'id' => $new_post_id,
                    'title' => get_the_title($new_post_id),
                    'edit_url' => admin_url('post.php?action=edit&post=' . $new_post_id)
                ),
                'message' => esc_html__('Post duplicated successfully', 'hashbar')
            ), 200);

        } catch (Throwable $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => esc_html__('An error occurred while duplicating the post', 'hashbar')
            ), 500);
        }
    }

    /**
     * Copy post taxonomies
     * 
     * @param int $source_post_id
     * @param int $target_post_id
     * @param string $post_type
     * @return void
     */
    private function copy_post_taxonomies($source_post_id, $target_post_id, $post_type) {
        $taxonomies = get_object_taxonomies($post_type);
        foreach ($taxonomies as $taxonomy) {
            $post_terms = wp_get_object_terms($source_post_id, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($target_post_id, $post_terms, $taxonomy, false);
        }
    }

    /**
     * Copy post meta
     * 
     * @param int $source_post_id
     * @param int $target_post_id
     * @return void
     */
    private function copy_post_meta($source_post_id, $target_post_id) {
        global $wpdb;
        
        $post_meta_infos = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d",
            $source_post_id
        ));

        if (count($post_meta_infos) > 0) {
            $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
            $sql_query_sel = array();
            
            foreach ($post_meta_infos as $meta_info) {
                $meta_key = $meta_info->meta_key;
                if ($meta_key === '_wp_old_slug') continue;
                $meta_value = addslashes($meta_info->meta_value);
                $sql_query_sel[] = $wpdb->prepare("SELECT %d, %s, %s", $target_post_id, $meta_key, $meta_value);
            }
            
            if (!empty($sql_query_sel)) {
                $sql_query .= implode(" UNION ALL ", $sql_query_sel);
                $wpdb->query($sql_query);
            }
        }
    }

    /**
     * Update notification
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_notification($request) {
        $notification_id = $request->get_param('id');
        $params = $request->get_params();
        $post_status = get_post_status($notification_id);

        // Validate notification exists
        if (!$post_status) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => esc_html__('Notification not found', 'hashbar')
            ), 404);
        }

        // Update notification options
        $sanitized_options = array();
        if (isset($params['notification_enable_options'])) {
            $notification_enable_options = $params['notification_enable_options'];
            
            foreach ($notification_enable_options as $key => $value) {
                // Ensure the key starts with _wphash_ prefix
                $meta_key = (strpos($key, '_wphash_') === 0) ? $key : '_wphash_' . $key;
                
                if (is_array($value)) {
                    $sanitized_options[$meta_key] = array_map('sanitize_text_field', $value);
                } else {
                    $sanitized_options[$meta_key] = sanitize_text_field($value);
                }
                
                update_post_meta($notification_id, $meta_key, $sanitized_options[$meta_key]);
            }
        }

        // Publish the post if it's not already published
        if ($post_status != 'publish') {
            wp_update_post(array(
                'ID' => $notification_id,
                'post_status' => 'publish'
            ));
        }

        // Get updated data
        $updated_data = array(
            'notification_enable_options' => $sanitized_options,
            'settings' => array()
        );

        return new WP_REST_Response(array(
            'success' => true,
            'data' => $updated_data,
            'message' => esc_html__('Notification updated successfully', 'hashbar')
        ), 200);
    }

    /**
     * Delete notification
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function delete_notification($request) {
        $notification_id = $request->get_param('id');
        wp_trash_post($notification_id);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => esc_html__('Notification deleted successfully', 'hashbar')
        ), 200);
    }

    /**
     * Get sidebar content from the template
     * 
     * @return WP_REST_Response|WP_Error
     */
    public function get_sidebar_content() {
        try {
            $template_path = HASHBAR_WPNBP_DIR . '/admin/settings-panel/templates/sidebar-banner.php';
            
            if (!file_exists($template_path)) {
                return new WP_Error(
                    'template_not_found',
                    esc_html__('Sidebar template file not found.', 'hashbar'),
                    array('status' => 404)
                );
            }

            ob_start();
            include $template_path;
            $content = ob_get_clean();

            if ($content === false) {
                throw new Exception('Failed to capture output buffer');
            }

            return new WP_REST_Response(array(
                'success' => true,
                'content' => $content
            ), 200);

        } catch (Exception $e) {
            return new WP_Error(
                'template_error',
                $e->getMessage(),
                array('status' => 500)
            );
        }
    }

    /**
     * Update dashboard settings
     * 
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function update_dashboard_settings($request) {
        try {
            // Get and decode JSON data
            $settings = json_decode($request->get_body(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Invalid JSON data'
                ), 400);
            }

            // Update Pro version option
            $update_result = update_option('hashbar_wpnbp_opt', $settings);

            if ($update_result === false && $settings !== get_option('hashbar_wpnbp_opt')) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Failed to update settings in database'
                ), 500);
            }

            // Sync settings to free version's option as well for cross-version compatibility
            update_option('hashbar_wpnb_opt', $settings);

            return new WP_REST_Response(array(
                'success' => true,
                'data' => $settings,
                'message' => esc_html__('Settings updated successfully', 'hashbar'),
            ), 200);

        } catch (Throwable $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => esc_html__('An error occurred while updating settings', 'hashbar'),
            ), 500);
        }
    }

    /**
     * Get all notifications
     *
     * @return WP_REST_Response Response object with notifications data
     */
    public function get_notifications() {
        $args = array(
            'post_type'      => 'wphash_ntf_bar',
            'posts_per_page' => -1,
            'post_status'    => array('publish', 'draft', 'future')
        );

        // Define notification enable fields directly since the admin settings class might not be available
        $notification_enable_fields = $this->get_notification_enable_fields();

        $notifications = get_posts($args);
        $notification_data = array();

        foreach ($notifications as $notification) {
            $enable_options = array();
            foreach ($notification_enable_fields as $key => $field_config) {
                $meta_key = '_wphash_' . $key;
                $enable_options[$meta_key] = get_post_meta($notification->ID, $meta_key, true);
            }

            $notification_data[] = array(
                'id'      => $notification->ID,
                'title'   => $notification->post_title,
                'content' => $notification->post_content,
                'created_at' => $notification->post_date,
                'status'  => $notification->post_status == 'draft' ? 'none' : get_post_meta($notification->ID, '_wphash_notification_where_to_show', true),
                'settings' => array(),
                'notification_enable_options' => $enable_options,
                'post_status' => $notification->post_status,
                'permalink' => get_post_permalink($notification->ID),
                'post_date' => $notification->post_date
            );
        }

        return new WP_REST_Response(array(
            'success' => true,
            'data'    => $notification_data
        ), 200);
    }

    /**
     * Get notification enable fields
     * Based on the metabox fields found in metabox.php
     * 
     * @return array
     */
    private function get_notification_enable_fields() {
        return array(
            // General Options
            'notification_where_to_show' => array(
                'type' => 'select',
                'title' => __('Where to show', 'hashbar')
            ),
            'notification_where_to_show_custom' => array(
                'type' => 'checkbox',
                'title' => __('Custom options where to show', 'hashbar')
            ),
            'notification_position' => array(
                'type' => 'radio',
                'title' => __('Positioning', 'hashbar')
            ),
            'notification_sticky' => array(
                'type' => 'switcher',
                'title' => __('Enable sticky notification', 'hashbar')
            ),
            'themes_header_type' => array(
                'type' => 'radio',
                'title' => __('Theme\'s header type', 'hashbar')
            ),
            'notification_transparent_selector' => array(
                'type' => 'text',
                'title' => __('Header CSS selector', 'hashbar')
            ),
            'promo_banner_top_display' => array(
                'type' => 'select',
                'title' => __('Promo banner top position', 'hashbar')
            ),
            'notification_display' => array(
                'type' => 'select',
                'title' => __('Load as minimized', 'hashbar')
            ),
            'notification_delay_time' => array(
                'type' => 'number',
                'title' => __('Notification delay', 'hashbar')
            ),
            'notification_close_button' => array(
                'type' => 'select',
                'title' => __('Show close button', 'hashbar')
            ),
            'hide_open_toggle' => array(
                'type' => 'select',
                'title' => __('Show open button', 'hashbar')
            ),
            'notification_close_button_text' => array(
                'type' => 'text',
                'title' => __('Close button text', 'hashbar')
            ),
            'notification_open_button_text' => array(
                'type' => 'text',
                'title' => __('Open button text', 'hashbar')
            ),
            
            // Visibility Options
            'notification_on_mobile' => array(
                'type' => 'select',
                'title' => __('Hide on mobile device', 'hashbar')
            ),
            'notification_on_desktop' => array(
                'type' => 'select',
                'title' => __('Hide on desktop device', 'hashbar')
            ),
            'show_hide_scroll' => array(
                'type' => 'select',
                'title' => __('Show/Hide notification based on scroll position', 'hashbar')
            ),
            'show_scroll_position' => array(
                'type' => 'text',
                'title' => __('Scroll position to show', 'hashbar')
            ),
            'hide_scroll_position' => array(
                'type' => 'text',
                'title' => __('Scroll position to hide', 'hashbar')
            ),
            'notification_how_many_times_to_show' => array(
                'type' => 'text',
                'title' => __('How many times to show this notification', 'hashbar')
            ),
            'notification_schedule' => array(
                'type' => 'select',
                'title' => __('Schedule notification expiry date/time', 'hashbar')
            ),
            'notification_schedule_datetime' => array(
                'type' => 'callback',
                'title' => __('Expiry date/time', 'hashbar')
            ),
            
            // Design Options
            'notification_content_bg_color' => array(
                'type' => 'color',
                'title' => __('Content background color', 'hashbar')
            ),
            'notification_content_bg_image' => array(
                'type' => 'media',
                'title' => __('Content background image', 'hashbar')
            ),
            'notification_content_text_color' => array(
                'type' => 'color',
                'title' => __('Content text color', 'hashbar')
            ),
            'notification_content_border' => array(
                'type' => 'border',
                'title' => __('Content border', 'hashbar')
            ),
            'notification_content_bg_opcacity' => array(
                'type' => 'text',
                'title' => __('Content opacity', 'hashbar')
            ),
            'notification_content_margin' => array(
                'type' => 'callback',
                'title' => __('Content margin', 'hashbar')
            ),
            'notification_content_padding' => array(
                'type' => 'callback',
                'title' => __('Content padding', 'hashbar')
            ),
            
            // Close Button Options
            'notification_close_button_color' => array(
                'type' => 'color',
                'title' => __('Close button text color', 'hashbar')
            ),
            'notification_close_button_hover_color' => array(
                'type' => 'color',
                'title' => __('Close button text hover color', 'hashbar')
            ),
            'notification_close_button_bg_color' => array(
                'type' => 'color',
                'title' => __('Close button background color', 'hashbar')
            ),
            'notification_close_button_hover_bg_color' => array(
                'type' => 'color',
                'title' => __('Close button background hover color', 'hashbar')
            ),
        );
    }

    /**
     * Get pages for settings selector
     * 
     * @return WP_REST_Response
     */
    public function get_pages() {
        $pages = get_pages(array(
            'post_status' => 'publish',
            'numberposts' => 150
        ));
        
        $result = array();
        
        foreach ($pages as $page) {
            $result[] = array(
                'id' => $page->ID,
                'title' => $page->post_title,
                'url' => get_permalink($page->ID)
            );
        }
        
        return new WP_REST_Response($result, 200);
    }

    /**
     * Get posts for settings selector
     * 
     * @return WP_REST_Response
     */
    public function get_posts() {
        $posts = get_posts(array(
            'post_status' => 'publish',
            'numberposts' => 150
        ));
        
        $result = array();
        
        foreach ($posts as $post) {
            $result[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'url' => get_permalink($post->ID)
            );
        }
        
        return new WP_REST_Response($result, 200);
    }

    /**
     * Get analytics data including total views, clicks, and tracking information
     * 
     * @return WP_REST_Response Response object with analytics data
     */
    public function get_analytics_data() {
        $total_tracking = get_transient('total_ht_traking_count') ?: array();
        $postwise_tracking = get_transient('postwise_ht_traking_count') ?: array();
        $country_tracking = get_transient('countrywise_ht_traking_count') ?: array();

        $trk_length = count($total_tracking);
        $total_clicks = $trk_length > 0 ? $total_tracking[0]['totalclicks'] : 0;
        $total_views = $trk_length > 0 ? $total_tracking[0]['totalviews'] : 0;
        $total_click_through_rate = $trk_length > 0 ? round(($total_tracking[0]['totalclicks']/$total_tracking[0]['totalviews'])*100, 2) : 0;

        // Format postwise tracking data
        $notification_stats = array();
        foreach ($postwise_tracking as $tracking) {
            if ('publish' === get_post_status($tracking['post_id'])) {
                $notification_stats[] = array(
                    'title' => get_the_title($tracking['post_id']),
                    'views' => $tracking['totalviews'],
                    'clicks' => $tracking['totalclicks'],
                    'through_rate' => round(($tracking['totalclicks']/$tracking['totalviews'])*100, 2)
                );
            }
        }

        // Format country tracking data
        $top_countries = array_map(function($country) {
            return array(
                'name' => $country['country']
            );
        }, $country_tracking);

        $response_data = array(
            'overview' => array(
                'total_clicks' => $total_clicks,
                'total_views' => $total_views,
                'click_through_rate' => $total_click_through_rate
            ),
            'notification_stats' => $notification_stats,
            'top_countries' => $top_countries
        );

        return new WP_REST_Response($response_data, 200);
    }

    /**
     * Reset settings to default values
     * 
     * @return WP_REST_Response
     */
    public function reset_settings() {
        update_option('hashbar_wpnbp_opt', null);

        // Sync reset to free version as well
        update_option('hashbar_wpnb_opt', null);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Settings reset successfully',
            'settings' => null
        ), 200);
    }
}

// Initialize the class
Hashbar_REST_API::get_instance();