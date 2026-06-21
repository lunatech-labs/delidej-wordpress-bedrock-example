<?php
namespace Hashbar\Pro;
// If this file is accessed directly, exit.
if (!defined('ABSPATH')) {
    exit;
}

class Hashbar_Settiigs_Panel {

    private static $_instance = null;

    public static function get_instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_hashbar_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_hashbar_assets'));
        add_action('admin_footer', array($this, 'hashbar_wpnb_enqueue_admin_head_scripts'));


        // dismiss the admin notice for user
        $user_id = get_current_user_id();
        if ( isset( $_GET['hthb-notice-dismissed'] ) ){ // phpcs:ignore
            add_user_meta( $user_id, 'hthb_notice_dismissed', 'true', true );
        }
    }
    
    public function add_hashbar_menu() {

        global $submenu;

        $slug        = 'hashbar';
        $capability  = 'manage_options';

        $hook = add_menu_page(
            esc_html__( 'HashBars', 'hashbar' ),
            esc_html__( 'HashBar', 'hashbar' ),
            $capability,
            $slug,
            [ $this, 'settings_page_render' ],
            'dashicons-format-status',
            5
        );

        if ( current_user_can( $capability ) ) {
            $default_hash = '#/';
            $submenu[ $slug ][] = array( esc_html__( 'Dashboard', 'hashbar' ), $capability, 'admin.php?page=' . $slug . $default_hash );
            $submenu[ $slug ][] = array( esc_html__( 'Announcement Bars', 'hashbar' ), $capability, 'admin.php?page=' . $slug .'#/announcement-bar' );
            $submenu[ $slug ][] = array( esc_html__( 'Popup Campaign', 'hashbar' ), $capability, 'admin.php?page=' . $slug .'#/popup-campaign' );
            $submenu[ $slug ][] = array( esc_html__( 'Notification Bar', 'hashbar' ), $capability, 'admin.php?page=' . $slug .'#/notifications' );
            $submenu[ $slug ][] = array( esc_html__( 'Settings', 'hashbar' ), $capability, 'admin.php?page=' . $slug .'#/settings' );
            $submenu[ $slug ][] = array( esc_html__( 'Analytics', 'hashbar' ), $capability, 'admin.php?page=' . $slug . '#/analytics' );
            $submenu[ $slug ][] = array( esc_html__( 'A/B Testing', 'hashbar' ), $capability, 'admin.php?page=' . $slug . '#/ab-testing' );
            $submenu[ $slug ][] = array( esc_html__( 'License', 'hashbar' ), $capability, 'admin.php?page=' . $slug . '#/license' );
            $submenu[ $slug ][] = array( esc_html__( 'Recommended', 'hashbar' ), $capability, 'admin.php?page=' . $slug . '#/recommended' );
        }

        if( !is_plugin_active('hashbar-pro/init.php') ){
            add_submenu_page(
                'hashbar', 
                __('Upgrade to Pro', 'hashbar'),
                __('Upgrade to Pro', 'hashbar'), 
                'manage_options', 
                'https://wphashbar.com/pricing/?utm_source=admin&utm_medium=mainmenu&utm_campaign=free'
            );
        }
    }

    public  function hashbar_wpnb_enqueue_admin_head_scripts() {
        printf( '<style>%s</style>', '#adminmenu #toplevel_page_hashbar a.hashbar-upgrade-pro { font-weight: 600; background-color: #ff6e30; color: #ffffff; text-align: left; margin-top: 3px; }' );
        printf( '<script>%s</script>', '(function ($) {
            $("#toplevel_page_hashbar .wp-submenu a").each(function() {
                if($(this)[0].href === "https://wphashbar.com/pricing/?utm_source=admin&utm_medium=mainmenu&utm_campaign=free") {
                    $(this).addClass("hashbar-upgrade-pro").attr("target", "_blank");
                }
            })
        })(jQuery);' );
    }
    
    public function enqueue_hashbar_assets($hook) {
        if ($hook !== 'toplevel_page_hashbar') {
            return;
        }

        // Enqueue WordPress media library for image uploads
        wp_enqueue_media();

        // Enqueue WordPress editor scripts for TinyMCE
        wp_enqueue_editor();

        // Load built assets
        wp_enqueue_style(
            'hashbar-settings-panel',
            HASHBAR_WPNBP_URI . '/admin/settings-panel/assets/dist/css/style.css',
            array(),
            HASHBAR_WPNBP_VERSION
        );
        wp_enqueue_script(
            'hashbar-settings-panel',
            HASHBAR_WPNBP_URI . '/admin/settings-panel/assets/dist/js/main.js',
            array(),
            HASHBAR_WPNBP_VERSION,
            true
        );

        add_filter('script_loader_tag', function($tag, $handle, $src) {
            if ($handle === 'hashbar-settings-panel') {
                return '<script type="module" src="' . esc_url($src) . '"></script>';
            }
            return $tag;
        }, 10, 3);

        $admin_settings = \Hashbar\Pro\Settings\Hashbar_Settings_Panel_Settings::get_instance();
        $notification_enable_fields = $admin_settings->get_notification_enable_fields();

        // Load announcement bar settings
        require_once dirname( __FILE__ ) . '/api/announcement-bar-settings.php';
        $ab_settings = new \Hashbar\Pro\Hashbar_Announcement_Bar_Settings();

        // Load popup campaign settings
        require_once dirname( __FILE__ ) . '/api/popup-campaign-settings.php';
        $popup_settings = new \Hashbar\Pro\Hashbar_Popup_Campaign_Settings();

        $hashbar_wpnb_opt = get_option('hashbar_wpnbp_opt') ? get_option('hashbar_wpnbp_opt') : [];
        if( empty( $hashbar_wpnb_opt ) && !empty( get_option('hashbar_wpnb_opt') ) ){
            $hashbar_wpnb_opt = get_option('hashbar_wpnb_opt');
        }

        // Calculate site timezone offset for JavaScript
        $site_timezone = wp_timezone();
        $now = new \DateTime( 'now', $site_timezone );
        $site_timezone_offset = $now->getOffset() / 3600; // Convert seconds to hours

        $localize_data = [
            'ajaxurl'          => admin_url( 'admin-ajax.php' ),
            'adminURL'         => admin_url(),
            'pluginURL'        => plugin_dir_url( __FILE__ ),
            'assetsURL'        => plugin_dir_url( __FILE__ ) . 'assets/',
            'restUrl' => rest_url(),  // This will include the wp-json prefix
            'nonce' => wp_create_nonce('wp_rest'),
            'siteTimezoneOffset' => $site_timezone_offset,
            'licenseNonce'  => wp_create_nonce( 'el-license' ),
            'licenseEmail'  => get_option( 'HashbarPro_lic_email', get_bloginfo( 'admin_email' ) ),
            'message'          =>[
                'packagedesc'=> esc_html__( 'in this package', 'hashbar' ),
                'allload'    => esc_html__( 'All Items have been Loaded', 'hashbar' ),
                'notfound'   => esc_html__( 'Nothing Found', 'hashbar' ),
                'errorLoadingStats' => esc_html__( 'Error Loading Statistics', 'hashbar' ),
                'noStatsAvailable' => esc_html__( 'No Statistics Available', 'hashbar' ),
                'startAbTest' => esc_html__( 'Start your A/B test to see statistics here.', 'hashbar' ),
            ],
            'buttontxt'      =>[
                'tmplibrary' => esc_html__( 'Import to Library', 'hashbar' ),
                'tmppage'    => esc_html__( 'Import to Page', 'hashbar' ),
                'import'     => esc_html__( 'Import', 'hashbar' ),
                'buynow'     => esc_html__( 'Buy Now', 'hashbar' ),
                'buynow_link' => 'https://wphashbar.com/pricing/?utm_source=admin&utm_medium=mainmenu&utm_campaign=free',
                'preview'    => esc_html__( 'Preview', 'hashbar' ),
                'installing' => esc_html__( 'Installing..', 'hashbar' ),
                'activating' => esc_html__( 'Activating..', 'hashbar' ),
                'active'     => esc_html__( 'Active', 'hashbar' ),
                'pro' => __( 'Pro', 'hashbar' ),
                'refresh'    => esc_html__( 'Refresh', 'hashbar' ),
                'retry'      => esc_html__( 'Retry', 'hashbar' ),
                'modal' => [
                    'title' => __( 'BUY PRO', 'hashbar' ),
                    'desc' => __( 'Our free version is great, but it doesn\'t have all our advanced features. The best way to unlock all of the features in our plugin is by purchasing the pro version.', 'hashbar' )
                ],
            ],
            'existingData' => get_option('hashbar_options'),
            'helpSection' => [
                'title' => esc_html__('Need Help with Hashbar?', 'hashbar'),
                'description' => esc_html__('Our comprehensive documentation provides detailed information on how to use Hashbar effectively to improve your websites performance.', 'hashbar'),
                'documentation' => esc_html__('Documentation', 'hashbar'),
                'videoTutorial' => esc_html__('Video Tutorial', 'hashbar'),
                'support' => esc_html__('Support', 'hashbar'),
                'docLink' => 'https://wphashbar.com/docs/',
                'videoLink' => 'https://www.youtube.com/watch?v=9VUc5Is-9Uw',
                'supportLink' => 'https://wphashbar.com/contact/',
                'upgradeLink' => 'https://wphashbar.com/pricing/?utm_source=admin&utm_medium=mainmenu&utm_campaign=free',
                'licenseLink' => 'https://wphashbar.com/pricing/?utm_source=admin&utm_medium=mainmenu&utm_campaign=free',
                'recommendedPluginsLink' => 'https://hasthemes.com/plugins/',
            ],
            'adminSettings' => [
                'modal_settings_fields' => $notification_enable_fields,
                'is_pro' => \Hashbar\Pro\Hashbar_Popup_Campaign_Settings::is_pro(),
                'labels_texts' => $admin_settings->get_labels_texts(),
                'dashboard_settings' => $admin_settings->get_dashboard_settings(),
                'menu_settings' => $admin_settings->get_menu_settings(),
                'recommendations_plugins' => $admin_settings->get_recommendations_plugins(),
                'notification_enable_fields' => $notification_enable_fields,
                'hashbar_wpnb_opt' => $hashbar_wpnb_opt,
            ],
            'announcementBarSettings' => [
                'tabs' => $ab_settings->get_editor_tabs(),
                'fields' => [
                    'content' => $ab_settings->get_content_fields(),
                    'design' => $ab_settings->get_design_fields(),
                    'position' => $ab_settings->get_position_fields(),
                    'targeting' => $ab_settings->get_targeting_fields(),
                    'countdown' => $ab_settings->get_countdown_fields(),
                    'coupon' => $ab_settings->get_coupon_fields(),
                    'schedule' => $ab_settings->get_schedule_fields(),
                    'animation' => $ab_settings->get_animation_fields(),
                    'ab-test' => $ab_settings->get_abtest_fields(),
                ],
                'templates' => $ab_settings->get_templates(),
                'labels' => $ab_settings->get_labels(),
                'is_pro' => \Hashbar\Pro\Hashbar_Announcement_Bar_Settings::is_pro(),
                'video_guide' => \Hashbar\Pro\Hashbar_Announcement_Bar_Settings::get_video_guide(),
            ],
            'popupCampaignSettings' => [
                'tabs' => \Hashbar\Pro\Hashbar_Popup_Campaign_Settings::get_editor_tabs(),
                'fields' => $popup_settings->get_all_fields(),
                'templates' => $popup_settings->get_templates(),
                'labels' => $popup_settings->get_labels(),
                'formPluginInfo' => \Hashbar\Pro\Hashbar_Popup_Campaign_Settings::get_form_plugin_info(),
                'is_pro' => \Hashbar\Pro\Hashbar_Popup_Campaign_Settings::is_pro(),
                'video_guide' => \Hashbar\Pro\Hashbar_Popup_Campaign_Settings::get_video_guide(),
            ],
        ];
        wp_localize_script( 'hashbar-settings-panel', 'HashbarSettingsLocalize', $localize_data );

        // Enqueue announcement bar frontend assets
        wp_enqueue_script(
            'hashbar-announcement-frontend',
            HASHBAR_WPNBP_URI . '/assets/js/frontend.js',
            array('jquery'),
            HASHBAR_WPNBP_VERSION,
            true
        );

        wp_enqueue_script(
            'hashbar-announcement-analytics',
            HASHBAR_WPNBP_URI . '/assets/js/announcement-analytics.js',
            array('jquery'),
            HASHBAR_WPNBP_VERSION,
            true
        );

        wp_enqueue_style(
            'hashbar-announcement-frontend',
            HASHBAR_WPNBP_URI . '/assets/css/frontend.css',
            array(),
            HASHBAR_WPNBP_VERSION
        );
    }



	/**
	 * Page Render Contents
	 * @return void
	 */
	public function settings_page_render () {
		// check user capabilities
		if ( !current_user_can( 'manage_options' ) ) {
			return;
		}?>
        <?php do_action('hashbar_admin_notices') ?>
        <!-- // Render React app container -->
        <div id="hashbar-app"></div>
        <?php
        
	}    
}

// Initialize HashBar Admin Dashboard
add_action('init', function() {
    if (is_admin()) {
       \Hashbar\Pro\Hashbar_Settiigs_Panel::get_instance();
    }
});