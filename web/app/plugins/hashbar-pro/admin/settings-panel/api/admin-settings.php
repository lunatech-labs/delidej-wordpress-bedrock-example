<?php
namespace Hashbar\Pro\Settings;
if (!defined('ABSPATH')) exit;

class Hashbar_Settings_Panel_Settings {
    private static $instance = null;
    private $is_pro = false;
    private $prefix = '_wphash_';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->is_pro = defined('HASHBAR_WPNBP_VERSION');
    }

    public function get_help_section() {
        return [
            'docLink' => 'https://wphashbar.com/docs/',
            'supportLink' => 'https://wphashbar.com/contact/',
            'licenseLink' => admin_url('admin.php?page=hashbar-pro'),
            'recommendedPluginsLink' => admin_url('admin.php?page=hashbar_recommendations'),
            'upgradeLink' => 'https://wphashbar.com/pricing/'
        ];
    }

    public function get_menu_settings() {
        return [
            'dashboard' => [
                'label' => __('Dashboard', 'hashbar'),
                'icon' => 'Grid',
                'link' => '/',
                'order' => 1,
                'visible' => true,
                'isRouter' => true
            ],
            'announcement_bar' => [
                'label' => __('Announcement Bar', 'hashbar'),
                'icon' => 'MessageCircle',
                'link' => '/announcement-bar',
                'order' => 2,
                'visible' => true,
                'isRouter' => true
            ],
            'popup_campaign' => [
                'label' => __('Popup Campaign', 'hashbar'),
                'icon' => 'Popup',
                'link' => '/popup-campaign',
                'order' => 3,
                'visible' => true,
                'isRouter' => true,
                'proBadge' => false
            ],
            'notifications' => [
                'label' => __('Notification Bar', 'hashbar'),
                'icon' => 'Bell',
                'link' => '/notifications',
                'order' => 4,
                'visible' => true,
                'isRouter' => true
            ],
            'settings' => [
                'label' => __('Settings', 'hashbar'),
                'icon' => 'Setting',
                'link' => '/settings',
                'order' => 5,
                'visible' => true,
                'isRouter' => true
            ],
            'analytics' => [
                'label' => __('Analytics', 'hashbar'),
                'icon' => 'DataAnalysis',
                'link' => '/analytics',
                'order' => 6,
                'visible' => true,
                'isRouter' => true
            ],

            'ab_testing' => [
                'label' => __('A/B Testing', 'hashbar'),
                'icon' => 'Trophy',
                'link' => '/ab-testing',
                'order' => 7,
                'visible' => true,
                'isRouter' => true
            ],
            'license' => [
                'label' => __('License', 'hashbar'),
                'icon' => 'Key',
                'link' => '/license',
                'order' => 8,
                'visible' => true,
                'isRouter' => true
            ],
            'recommended_plugins' => [
                'label' => __('Recommended Plugins', 'hashbar'),
                'icon' => 'Promotion',
                'link' => '/recommended',
                'order' => 9,
                'visible' => true,
                'isRouter' => true
            ],
        ];
    }

    /**
     * Get notification enable fields with options
     */
    public function get_notification_enable_fields() {
        $prefix = '_wphash_';
        $enable_ajax_select = true;
        $hashbar_options = get_option('hashbar_wpnb_opt', array());
        $limit = array(
            'post'    => isset($hashbar_options['posts_limit']) && $hashbar_options['posts_limit'] !== '' ? intval($hashbar_options['posts_limit']) : 20,
            'page'    => isset($hashbar_options['pages_limit']) && $hashbar_options['pages_limit'] !== '' ? intval($hashbar_options['pages_limit']) : 20,
            'product' => isset($hashbar_options['product_limit']) && $hashbar_options['product_limit'] !== '' ? intval($hashbar_options['product_limit']) : 20,
        );

        $fields = [
            $prefix.'notification_where_to_show' => [
                'label' => __('Where to Show: ', 'hashbar'),
                'description' => __('Choose where to show the notification.', 'hashbar'),
                'placeholder' => esc_html__('Select Where to Show', 'hashbar'),
                'type' => 'select',
                'options' => [
                    'none' => __('Don\'t show', 'hashbar'),
                    'everywhere' => __('Entire Site', 'hashbar'),
                    'homepage' => __('Homepage Only', 'hashbar'),
                    'post' => __('Posts', 'hashbar'),
                    'post_cat' => esc_html__( 'Post Categories', 'hashbar' ),
                    'post_tags' => esc_html__( 'Post Tags', 'hashbar' ),
                    'page' => __('Pages', 'hashbar'),
                    'specific_ids' => __('Any Post/Page/Custom Post IDs', 'hashbar'),
                    'url_param' => __('URL Parameter', 'hashbar'),
                    'custom' => __('Custom', 'hashbar')
                ],
                'default' => 'everywhere',
                'pro' => [],
                'proBadge' => false
            ],
            $prefix.'exclusion_page_for_notification' => [
                'label' => __('Exclude pages for notification', 'hashbar'),
                'description' => __('Write any Page/Post/Custom Post ids here separated by comma. Example: 4,32,17.', 'hashbar'),
                'type' => 'text',
                'placeholder' => esc_html__('4,32,17', 'hashbar'),
                'condition' => [
                    'key' => $prefix.'notification_where_to_show',
                    'operator' => '==',
                    'value' => 'everywhere'
                ],
                'default' => '',
                'proBadge' => false
            ],
            // Posts selection field - shown when where_to_show is 'posts'
            $prefix.'notification_where_to_show_Post' => [
                'label' => __('Choose Posts', 'hashbar'),
                'description' => __('Select specific posts where this notification will appear', 'hashbar'),
                'type' => 'select',
                'placeholder' => esc_html__('Select Posts', 'hashbar'),
                'options' => hashbar_post_list('post', $limit['post']),
                'multiple' => true,
                'condition' => [
                    'key' => $prefix.'notification_where_to_show',
                    'operator' => '==',
                    'value' => 'post'
                ],
                'default' => []
            ],
            // post categories filed 
            $prefix.'notification_where_to_show_Categories' => [
                'label' => __('Choose Categories', 'hashbar'),
                'description' => __('Select specific categories where this notification will appear', 'hashbar'),
                'type' => 'select',
                'placeholder' => esc_html__('Select Categories', 'hashbar'),
                'options' => hashbar_post_list('post_cat'),
                'multiple' => true,
                'condition' => [
                    'key' => $prefix.'notification_where_to_show',
                    'operator' => '==',
                    'value' => 'post_cat'
                ],
                'default' => []
            ],
            // post tags filed 
            $prefix.'notification_where_to_show_Tags' => [
                'label' => __('Choose Tags', 'hashbar'),
                'description' => __('Select specific tags where this notification will appear', 'hashbar'),
                'type' => 'select',
                'placeholder' => esc_html__('Select Tags', 'hashbar'),
                'options' => hashbar_post_list('post_tags'),
                'multiple' => true,
                'condition' => [
                    'key' => $prefix.'notification_where_to_show',
                    'operator' => '==',
                    'value' => 'post_tags'
                ],
                'default' => []
            ],
            // Pages selection field - shown when where_to_show is 'pages'
            $prefix.'notification_where_to_show_Page' => [
                'label' => __('Choose Pages', 'hashbar'),
                'description' => __('Select specific pages where this notification will appear', 'hashbar'),
                'type' => 'select',
                'placeholder' => esc_html__('Select Pages', 'hashbar'),
                'options' => hashbar_post_list('page', $limit['page']),
                'multiple' => true,
                'condition' => [
                    'key' => $prefix.'notification_where_to_show',
                    'operator' => '==',
                    'value' => 'page'
                ],
                'default' => []
            ],

            // Custom IDs field - shown when where_to_show is 'custom_ids'
            $prefix.'specific_post_ids' => [
                'label' => __('Post/Page/Custom Post IDs', 'hashbar'),
                'description' => __('Put the post/page/custom post ids here separated by a comma. Example: 50,60,54', 'hashbar'),
                'type' => 'text',
                'placeholder' => esc_html__('50,60,54', 'hashbar'),
                'condition' => [
                    'key' => $prefix.'notification_where_to_show',
                    'operator' => '==',
                    'value' => 'specific_ids'
                ],
                'default' => ''
            ],

            // URL Parameter field - shown when where_to_show is 'url_param'
            $prefix.'url_param' => [
                'label' => __('URL Parameter Value', 'hashbar'),
                'description' => __('Input URL parameter value, Example: discount_50. Your URL should look like: example.com/?param=discount_50', 'hashbar'),
                'type' => 'text',
                'condition' => [
                    'key' => $prefix.'notification_where_to_show',
                    'operator' => '==',
                    'value' => 'url_param'
                ],
                'default' => ''
            ],

            // Custom options - shown when where_to_show is 'custom'
            $prefix.'notification_where_to_show_custom' => [
                'label' => __('Custom Options Where to Show', 'hashbar'),
                'description' => __('Select specific locations where this notification will appear', 'hashbar'),
                'type' => 'checkbox',
                'options' => [
                    'home' => __('Homepage', 'hashbar'),
                    'posts' => __('All Posts', 'hashbar'),
                    'page' => __('All Pages', 'hashbar')
                ],
                'condition' => [
                    'key' => $prefix.'notification_where_to_show',
                    'operator' => '==',
                    'value' => 'custom'
                ],
                'default' => []
            ]
        ];

        // Add WooCommerce specific fields if WooCommerce is active
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            // Add products option to where_to_show
            $fields[$prefix.'notification_where_to_show']['options']['product'] = __('Products', 'hashbar');
            $fields[$prefix.'notification_where_to_show']['options']['woo_catagories'] = __('Products Of Selected Categories', 'hashbar');

            // Add products selection field
            $fields[$prefix.'notification_where_to_show_Product'] = [
                'label' => __('Choose Products', 'hashbar'),
                'description' => __('Select specific products where this notification will appear', 'hashbar'),
                'type' => 'select',
                'placeholder' => esc_html__('Select Products', 'hashbar'),
                'options' => hashbar_post_list('product', $limit['product']),
                'multiple' => true,
                'condition' => [
                    'key' => $prefix.'notification_where_to_show',
                    'operator' => '==',
                    'value' => 'product'
                ],
                'default' => []
            ];

            // Add WooCommerce categories field
            $fields[$prefix.'woocommerce_categories'] = [
                'label' => __('Product Categories', 'hashbar'),
                'description' => __('This notification will appear in all product details/archive pages of selected categories', 'hashbar'),
                'type' => 'select',
                'placeholder' => esc_html__('Select Categories', 'hashbar'),
                'options' => hashbar_post_list('product_cat'),
                'multiple' => true,
                'condition' => [
                    'key' => $prefix.'notification_where_to_show',
                    'operator' => '==',
                    'value' => 'woo_catagories'
                ],
                'default' => [],
                'proBadge' => false
            ];

            $fields[$prefix.'woocommerce_categories_archive_optin'] = [
                'label' => __('Disable for Archives', 'hashbar'),
                'description' => __('By Checking ths box, this notification will not be displayed in the selected categorie(archive) page above.', 'hashbar'),
                'type' => 'checkbox',
                'placeholder' => esc_html__('Select Categories', 'hashbar'),
                'condition' => [
                    'key' => $prefix.'notification_where_to_show|'.$prefix.'woocommerce_categories',
                    'operator' => '==|!=',
                    'value' => 'woo_catagories|',
                    'type'=> 'AND'
                ],
                'default' => [],
                'proBadge' => false
            ];
            // Add custom options for WooCommerce
            $fields[$prefix.'notification_where_to_show_custom']['options']['product'] = __('All Products', 'hashbar');


        }

        return $fields;
    }
    public function get_dashboard_settings(){
        $dashboard_settings = [
            'manage_notifications' => [
                'plugin_filter_options' => [
                    'label' => __('Filter Nofitications', 'hashbar'),
                    'options'=>[
                        'all' => __('All Notifications', 'hashbar'),
                        'active_notifications' => __('Active Notifications', 'hashbar'),
                        'inactive_notifications' => __('Inactive Notifications', 'hashbar'),
                        'draft' => __('Draft Notifications', 'hashbar'),
                    ],
                    'isPro' => ['backend_optimized'],
                    'proBadge' => false,
                    'type' => 'select',
                ]
            ],
            'general_settings' => [
                'dont_show_bar_after_close' => [
                    'label' => __('Don\'t Show Again', 'hashbar'),
                    'type' => 'checkbox',
                    'default' => false,
                    'desc' => __('If check this option. The notification will not appear again on a page, after closing the notification.', 'hashbar'),
                ],
                'keep_closed_bar' => [
                    'label' => __('Keep Notification Bar Closed', 'hashbar'),
                    'type' => 'checkbox',
                    'default' => false,
                    'desc' => __('When you close the notification bar once then it will always keep closed in all pages of your site. This option will be effective for the notifications which have set "Load as minimized = No" from the notification metabox options', 'hashbar'),
                ],
                'cookies_expire_time' => [
                    'label' => __('Cookies expire time', 'hashbar'),
                    'type' => 'number',
                    'default' => 7,
                    'min' => 1,
                    'max' => 365,
                    'step' => 1,
                    'desc' => __('Specify the duration of the expiration time for the cookie when a user closes the notification bar. After the expiration time has passed, the notification will reappear for that user. (Default: 7 Days).', 'hashbar'),
                ],
                'cookies_expire_type' => [
                    'label' => __('Cookies expire time unit', 'hashbar'),
                    'type' => 'select',
                    'default' => 'days',
                    'options' => [
                        'days' => __('Days', 'hashbar'),
                        'hours' => __('Hours', 'hashbar'),
                        'minutes' => __('Minutes', 'hashbar'),
                    ],
                    'desc' => __('Set the unit of time for cookies expiration.', 'hashbar'),
                ],
                'enable_analytics' => [
                    'label' => __('Enable Analytics', 'hashbar'),
                    'type' => 'checkbox',
                    'default' => true,
                    'desc' => __('Enable Analytics to get the analytical report about your notifications.', 'hashbar'),
                ],
                'count_onece_byip' => [
                    'label' => __('Count Only 1 From Each IP', 'hashbar'),
                    'type' => 'checkbox',
                    'default' => false,
                    'desc' => __('Enable to count the views and clicks only once from each IP-address.', 'hashbar'),
                ],
                'analytics_from' => [
                    'label' => __('User Tracking Options', 'hashbar'),
                    'type' => 'select',
                    'options' => [
                        'everyone' => __('Everyone', 'hashbar'),
                        'guests' => __('Guest Users Only', 'hashbar'),
                        'registered_users' => __('Rigestered Users Only', 'hashbar'),
                    ],
                    'default' => 'everyone',
                    'desc' => __('Select which users to track for analytics.', 'hashbar'),
                ],
                'mobile_device_breakpoint' => [
                    'label' => __('Mobile device breakpoint (px)', 'hashbar'),
                    'type' => 'number',
                    'default' => 991,
                    'min' => 320,
                    'max' => 1200,
                    'step' => 1,
                    'desc' => __('Set the breakpoint for mobile devices in pixels.', 'hashbar'),
                ],
                'items_per_page' => [
                    'label' => __('Items per Page', 'hashbar'),
                    'default' => 10,
                    'desc' => __('Default: 10 items per page. Adjust if you have more notifications to manage.', 'hashbar'),
                    'type' => 'number',
                    'min' => -1,
                    'max' => 100,
                    'step' => 1,
                    'isPro' => false,
                    'proBadge' => false,
                ],
                'posts_limit' => [
                    'label' => __('Limit Posts List', 'hashbar'),
                    'default' => 20,
                    'desc' => __('Leave it empty for default. Default = 20. Use -1 to load all posts into the dropdown options', 'hashbar'),
                    'type' => 'number',
                    'min' => -1,
                    'max' => 100000,
                    'step' => 1,
                    'isPro' => false,
                    'proBadge' => false,
                ],
                'pages_limit' => [
                    'label' => __('Limit Pages List', 'hashbar'),
                    'default' => 20,
                    'desc' => __('Leave it empty for default. Default = 20. Use -1 to load all posts into the dropdown options.', 'hashbar'),
                    'type' => 'number',
                    'min' => -1,
                    'max' => 100000,
                    'step' => 1,
                    'isPro' => false,
                    'proBadge' => false,
                ],
            ],
        ];
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            $dashboard_settings['general_settings']['product_limit'] = [
                'label' => __('Limit Products List', 'hashbar'),
                'default' => 20,
                'desc' => __('Leave it empty for default. Default = 20. Use -1 to load all products into the dropdown options.', 'hashbar'),
                'type' => 'number',
                'min' => -1,
                'max' => 100000,
                'step' => 1,
                'isPro' => false,
                'proBadge' => false,
            ];
        }
        return $dashboard_settings;
    }

    public function get_labels_texts() {
        return [
            // Existing strings
            'upgrade_to_pro' => __('Upgrade to Pro', 'hashbar'),

            // Settings Page
            'settings_page' => [
                'title' => __('HashBar Settings', 'hashbar'),
                'description' => __('Configure HashBar settings to manage notifications', 'hashbar'),
                'reset_settings' => __('Reset All Settings', 'hashbar'),
                'save_settings' => __('Save Settings', 'hashbar'),
                'reset_confirm_title' => __('Reset Settings', 'hashbar'),
                'reset_confirm_message' => __('Are you sure you want to reset all settings to default values?', 'hashbar'),
                'reset_confirm_button' => __('Reset', 'hashbar'),
                'reset_cancel_button' => __('Cancel', 'hashbar'),
                'reset_success_message' => __('Settings have been reset successfully', 'hashbar'),
                'reset_error_message' => __('Failed to reset settings', 'hashbar'),
            ],

            // Notifications Page
            'notifications_page' => [
                'title' => __('Manage Notifications', 'hashbar'),
                'filter_placeholder' => __('Filter by status', 'hashbar'),
                'search_placeholder' => __('Search Notifications...', 'hashbar'),
                'add_new_button' => __('Add New', 'hashbar'),
                'show_in_label' => __('Show in:', 'hashbar'),
                'draft_status' => __('Draft', 'hashbar'),
                'scheduled_at_label' => __('Scheduled At:', 'hashbar'),
                'inactive_status' => __('Inactive', 'hashbar'),
                'view_notification' => __('View Notification', 'hashbar'),
                'duplicate_action' => __('Duplicate', 'hashbar'),
                'delete_action' => __('Delete', 'hashbar'),
                'delete_confirm' => __('Are you sure you want to delete this notification?', 'hashbar'),
                'no_notifications_title' => __('No Notifications Found', 'hashbar'),
                'no_notifications_search' => __('Try adjusting your search or filter criteria', 'hashbar'),
                'no_notifications_empty' => __('No notifications are available at the moment', 'hashbar'),
                'disable_notification_error' => __('Failed to disable notification', 'hashbar'),
                'load_notifications_error' => __('Failed to load notifications', 'hashbar'),
                'recommendation_note' => __('<strong>Recommended:</strong> We highly recommend using our <a class="announcement-link">Announcement Bar</a> and <a class="popup-link">Popup Campaign</a> features for better visitor engagement. These features come with predefined templates and advanced options, making it easy to set up and customize.', 'hashbar'),
            ],

            // Welcome Section
            'welcome_section' => [
                'title' => __('Welcome to Hashbar Notification!', 'hashbar'),
                'subtitle' => __('Thank you for choosing Hashbar Notification Bar! 🚀', 'hashbar'),
                'description' => __('Create engaging notification bars to boost conversions, grow your audience, and inform visitors easily.', 'hashbar'),
                'video_title' => __('Welcome to Hashbar Notification', 'hashbar'),
            ],

            // Analytics Section
            'analytics_section' => [
                'title' => __('Analytics Overview', 'hashbar'),
                'total_clicks' => __('Total Clicks', 'hashbar'),
                'total_views' => __('Total Views', 'hashbar'),
                'click_through_rate' => __('Click Through Rate', 'hashbar'),
                'tracking_title' => __('Tracking By Notification Bars', 'hashbar'),
                'table_name' => __('Name', 'hashbar'),
                'table_views' => __('Total Views', 'hashbar'),
                'table_clicks' => __('Total Clicks', 'hashbar'),
                'table_rate' => __('Through Rate', 'hashbar'),
                'top_countries' => __('Top Countries', 'hashbar'),
                'no_notification_data' => __('No notification data available', 'hashbar'),
                'no_country_data' => __('No country data available', 'hashbar'),
                'upgrade_message' => __('Unlock this feature and more by upgrading to HashBar Pro', 'hashbar'),
            ],

            // Notification Modal
            'notification_modal' => [
                'title' => __('Notification Enable/Disable Settings', 'hashbar'),
                'go_to_settings' => __('Go to Other Settings', 'hashbar'),
                'cancel_button' => __('Cancel', 'hashbar'),
                'save_button' => __('Save', 'hashbar'),
                'update_settings_error' => __('Failed to update notification settings', 'hashbar'),
            ],

            // Dashboard Cards Section
            'dashboard_cards' => [
                'support_title' => __('Support & Feedback', 'hashbar'),
                'support_description' => __('Need help or want a free store set-up? We will get back to you within 12-24 hours after receiving your inquiry.', 'hashbar'),
                'support_button' => __('Get Support', 'hashbar'),
                'community_title' => __('Join Our Community', 'hashbar'),
                'community_description' => __('Engage with our community to connect & share your ideas. Join a network where collaboration and growth thrive!', 'hashbar'),
                'community_button' => __('Join Now', 'hashbar'),
                'documentation_title' => __('Documentation', 'hashbar'),
                'documentation_description' => __('We\'ve regularly updated the documentation to help you use the plugin effectively.', 'hashbar'),
                'documentation_button' => __('Documentation', 'hashbar'),
            ],

            // Newsletter Section
            'newsletter_section' => [
                'badge_text' => __('Subscribe Our Newsletter', 'hashbar'),
                'title' => __('Subscribe to receive discount, offer, plugin updates and news in your inbox.', 'hashbar'),
                'subscribe_button' => __('Subscribe Now', 'hashbar'),
            ],

            // Missing Features Section
            'missing_features' => [
                'title' => __('Missing Any Feature?', 'hashbar'),
                'description' => __('Have you ever noticed any missing features? Please notify us if you do. As soon as possible, our staff will add any necessary features based on your requests. Our commitment to our clients is second to none.', 'hashbar'),
                'request_button' => __('Request Feature', 'hashbar'),
            ],

            // Plugin Grid Section
            'plugin_grid' => [
                'active_installations' => __('Active Installations', 'hashbar'),
                'more_details' => __('More Details', 'hashbar'),
            ],

            // Common Messages
            'common' => [
                'success' => __('Success', 'hashbar'),
                'error' => __('Error', 'hashbar'),
                'loading' => __('Loading...', 'hashbar'),
                'save_failed' => __('Failed to save settings. Please try again.', 'hashbar'),
                'load_failed' => __('Failed to load settings. Please try again.', 'hashbar'),
                'pro_suffix' => __(' (Pro)', 'hashbar'),
            ],
        ];
    }

    public function get_modal_settings_fields() {
        $notification_enable_settings = $this->get_notification_enable_fields();
        return $notification_enable_settings;
        // Merge frontend and backend settings
        //return array_merge($feature_settings, $notification_enable_settings );
    }

    public function get_modal_settings_field($field) {
        $settings = $this->get_modal_settings_fields();
        return isset($settings[$field]) ? $settings[$field] : null;
    }

    public function is_pro() {
        return $this->is_pro;
    }

    public function get_recommendations_plugins() {
        $recommendations_plugins = array();
        // Recommended Tab
        $recommendations_plugins[] = array(
            'title'  => esc_html__( 'Recommended', 'hashbar' ),
            'active' => true,
            'plugins' => array(
                array(
                    'slug'        => 'woolentor-addons',
                    'location'    => 'woolentor_addons_elementor.php',
                    'name'        => esc_html__( 'WooLentor', 'hashbar' ),
                    'description' => esc_html__( 'If you own a WooCommerce website, you ll almost certainly want to use these capabilities: Woo Builder (Elementor WooCommerce Builder), WooCommerce Templates, WooCommerce Widgets,...', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null,
                    'recommend' => is_plugin_active('woocommerce/woocommerce.php') ? true : false,

                ),
                array(
                    'slug'        => 'ht-mega-for-elementor',
                    'location'    => 'htmega_addons_elementor.php',
                    'name'        => esc_html__( 'HT Mega', 'hashbar' ),
                    'description' => esc_html__( 'HTMega is an absolute addon for elementor that includes 80+ elements', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null,
                    'recommend' => is_plugin_active('elementor/elementor.php') ? true : false,
                ),
                array(
                    'slug'        => 'support-genix-lite',
                    'location'    => 'support-genix-lite.php',
                    'name'        => esc_html__( 'Support Genix Lite – Support Tickets Managing System', 'hashbar' ),
                    'description' => esc_html__( 'Support Genix is a support ticket system for WordPress and WooCommerce.', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null
                ),
                array(
                    'slug'        => 'whols',
                    'location'    => 'whols.php',
                    'name'        => esc_html__( 'Whols – Wholesale Prices and B2B Store', 'hashbar' ),
                    'description' => esc_html__( 'WooCommerce Wholesale plugin for B2B store management.', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null,
                    'recommend' => is_plugin_active('woocommerce/woocommerce.php') ? true : false,
                ),
                array(
                    'slug'        => 'wp-plugin-manager',
                    'location'    => 'init.php',
                    'name'        => esc_html__( 'WP Plugin Manager', 'hashbar' ),
                    'description' => esc_html__( 'Deactivate plugins per page', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null
                ),
                array(
                    'slug'        => 'pixelavo',
                    'location'    => 'pixelavo.php',
                    'name'        => esc_html__( 'Pixelavo – Facebook Pixel Conversion API', 'hashbar' ),
                    'description' => esc_html__( 'Easy connection of Facebook pixel to your online store.', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null
                ),
                array(
                    'slug'        => 'ht-contactform',
                    'location'    => 'contact-form-widget-elementor.php',
                    'name'        => esc_html__( 'HT Contact Form Widget For Elementor Page Builder & Gutenberg Blocks & Form Builder.', 'hashbar' ),
                    'description' => esc_html__( 'HT Contact Form Widget For Elementor Page Builder & Gutenberg Blocks & Form Builder.', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null
                ),
                array(
                    'slug'        => 'extensions-for-cf7',
                    'location'    => 'extensions-for-cf7.php',
                    'name'        => esc_html__( 'Extensions For CF7', 'hashbar' ),
                    'description' => esc_html__( 'Additional features for Contact Form 7', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null,
                    'recommend' => is_plugin_active('contact-form-7/wp-contact-form-7.php') ? true : false,
                ),
            )
        );
    
        // You May Also Like Tab
        $recommendations_plugins[] = [
            'title' => esc_html__( 'WooCommerce', 'hashbar' ),
            'plugins' => [
                array(
                    'slug'        => 'whols',
                    'location'    => 'whols.php',
                    'name'        => esc_html__( 'Whols – Wholesale Prices and B2B Store', 'hashbar' ),
                    'description' => esc_html__( 'WooCommerce Wholesale plugin for B2B store management.', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null
                ),
                array(
                    'slug'        => 'woolentor-addons',
                    'location'    => 'woolentor_addons_elementor.php',
                    'name'        => esc_html__( 'WooLentor', 'hashbar' ),
                    'description' => esc_html__( 'If you own a WooCommerce website, you’ll almost certainly want to use these capabilities: Woo Builder (Elementor WooCommerce Builder), WooCommerce Templates, WooCommerce Widgets,...', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null
                ),
                array(
                    'slug'        => 'swatchly',
                    'location'    => 'swatchly.php',
                    'name'        => esc_html__( 'Swatchly', 'hashbar' ),
                    'description' => esc_html__( 'Swatchly – WooCommerce Variation Swatches for Products (product attributes: Image swatch, Color swatches, Label swatches...)', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null
                ),
                array(
                    'slug'        => 'just-tables',
                    'location'    => 'just-tables.php',
                    'name'        => esc_html__( 'JustTables – WooCommerce Product Table', 'hashbar' ),
                    'description' => esc_html__( 'JustTables is an incredible WordPress plugin that lets you showcase all your WooCommerce products in a sortable and filterable table view. It allows your customers to easily navigate through different attributes of the products and compare them on a single page...', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null
                ),
            ]
        ];
    
        // Others Tab
        $recommendations_plugins[] = [
            'title' => esc_html__( 'Others', 'hashbar' ),
            'plugins' => [
                array(
                    'slug'        => 'support-genix-lite',
                    'location'    => 'support-genix-lite.php',
                    'name'        => esc_html__( 'Support Genix Lite – Support Tickets Managing System', 'hashbar' ),
                    'description' => esc_html__( 'Support Genix is a support ticket system for WordPress and WooCommerce.', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null
                ),
                array(
                    'slug'        => 'ht-mega-for-elementor',
                    'location'    => 'htmega_addons_elementor.php',
                    'name'        => esc_html__( 'HT Mega', 'hashbar' ),
                    'description' => esc_html__( 'HTMega is an absolute addon for elementor that includes 80+ elements', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null
                ),
                array(
                    'slug'        => 'pixelavo',
                    'location'    => 'pixelavo.php',
                    'name'        => esc_html__( 'Pixelavo – Facebook Pixel Conversion API', 'hashbar' ),
                    'description' => esc_html__( 'Easy connection of Facebook pixel to your online store.', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null
                ),
                array(
                    'slug'        => 'insert-headers-and-footers-script',
                    'location'    => 'init.php',
                    'name'        => esc_html__( 'Insert Headers and Footers Code – HT Script', 'hashbar' ),
                    'description' => esc_html__( 'Insert Headers and Footers Code allows you to insert Google Analytics, Facebook Pixel, custom CSS, custom HTML, JavaScript code to your website header and footer without modifying your theme code', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null
                ),
                array(
                    'slug'        => 'ht-slider-for-elementor',
                    'location'    => 'ht-slider-for-elementor.php',
                    'name'        => esc_html__( 'HT Slider For Elementor', 'hashbar' ),
                    'description' => esc_html__( 'Create beautiful sliders for your website using Elementor', 'hashbar' ),
                    'status'     => 'inactive',
                    'isLoading'  => false,
                    'icon'       => null
                ),
                array(
                    'slug' => 'ht-google-place-review',
                    'location' => 'ht-google-place-review.php',
                    'name' => esc_html__('Google Place Review', 'hashbar'),
                    'link' => 'https://hasthemes.com/plugins/google-place-review-plugin-for-wordpress/',
                    'author_link' => 'https://hasthemes.com/',
                    'description' => esc_html__('Display Google Reviews on your site.', 'hashbar'),
                    'pro' => true
                ),
            ]
        ];
    
        $recommendations_plugins[0]['plugins'] = array_values(array_filter(
            $recommendations_plugins[0]['plugins'],
            function($plugin) {
                return !isset($plugin['recommend']) || $plugin['recommend'] !== false;
            }
        ));
        return $recommendations_plugins;
    }
}