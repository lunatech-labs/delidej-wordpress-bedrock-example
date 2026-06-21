<?php
namespace Hashbar\Pro;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*=======================================================
 *    Register Announcement Bar Post Type
 * =======================================================*/

/**
 * Register announcement bar custom post type
 *
 * This creates the new wphash_announcement CPT for the modern
 * announcement bar feature. Separate from legacy wphash_ntf_bar.
 *
 * @since 1.8.0
 */
function hashbar_register_announcement_bar_cpt() {
	$labels = array(
		'name'                  => _x( 'Announcement Bars', 'Post Type General Name', 'hashbar' ),
		'singular_name'         => _x( 'Announcement Bar', 'Post Type Singular Name', 'hashbar' ),
		'menu_name'             => __( 'Announcement Bars', 'hashbar' ),
		'name_admin_bar'        => __( 'Announcement Bar', 'hashbar' ),
		'archives'              => __( 'Announcement Archives', 'hashbar' ),
		'parent_item_colon'     => __( 'Parent Announcement:', 'hashbar' ),
		'all_items'             => __( 'All Announcements', 'hashbar' ),
		'add_new_item'          => __( 'Add New Announcement', 'hashbar' ),
		'add_new'               => __( 'Add New Announcement', 'hashbar' ),
		'new_item'              => __( 'New Announcement', 'hashbar' ),
		'edit_item'             => __( 'Edit Announcement', 'hashbar' ),
		'update_item'           => __( 'Update Announcement', 'hashbar' ),
		'view_item'             => __( 'View Announcement', 'hashbar' ),
		'search_items'          => __( 'Search Announcement', 'hashbar' ),
		'not_found'             => __( 'Not found', 'hashbar' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'hashbar' ),
		'featured_image'        => __( 'Featured Image', 'hashbar' ),
		'set_featured_image'    => __( 'Set featured image', 'hashbar' ),
		'remove_featured_image' => __( 'Remove featured image', 'hashbar' ),
		'use_featured_image'    => __( 'Use as featured image', 'hashbar' ),
		'insert_into_item'      => __( 'Insert into announcement', 'hashbar' ),
		'uploaded_to_this_item' => __( 'Uploaded to this announcement', 'hashbar' ),
		'items_list'            => __( 'Announcements list', 'hashbar' ),
		'items_list_navigation' => __( 'Announcements list navigation', 'hashbar' ),
		'filter_items_list'     => __( 'Filter announcements list', 'hashbar' ),
	);

	$args = array(
		'label'              => __( 'Announcement Bar', 'hashbar' ),
		'labels'             => $labels,
		'supports'           => array( 'title' ), // Only title, rest via meta
		'hierarchical'       => false,
		'public'             => false,           // Hidden from WP admin public view
		'show_ui'            => false,           // Not shown in WordPress admin
		'show_in_menu'       => false,           // Managed via Vue dashboard
		'show_in_rest'       => true,            // Enable REST API access
		'show_in_admin_bar'  => false,
		'show_in_nav_menus'  => false,
		'can_export'         => true,
		'has_archive'        => false,
		'exclude_from_search' => true,
		'publicly_queryable' => false,
		'capability_type'    => 'post',
		'map_meta_cap'       => true,
		'rest_base'          => 'announcement-bars',
		'rest_controller_class' => 'WP_REST_Posts_Controller',
	);

	register_post_type( 'wphash_announcement', $args );
}

add_action( 'init', __NAMESPACE__ . '\hashbar_register_announcement_bar_cpt' );

/**
 * Register announcement bar post meta
 *
 * Registers all meta fields used by announcement bars for REST API access
 *
 * @since 1.8.0
 */
function hashbar_register_announcement_bar_meta() {
	// Tab 0: Templates
	register_post_meta( 'wphash_announcement', '_wphash_ab_template_id', array(
		'type'              => 'string',
		'description'       => __( 'Template ID', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	// Tab 1: Content
	register_post_meta( 'wphash_announcement', '_wphash_ab_message', array(
		'type'              => 'string',
		'description'       => __( 'Main message text', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_cta_text', array(
		'type'              => 'string',
		'description'       => __( 'CTA button text', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_cta_url', array(
		'type'              => 'string',
		'description'       => __( 'CTA button URL', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_cta_target', array(
		'type'              => 'string',
		'description'       => __( 'CTA link target', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '_self',
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_cta_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Show CTA button', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => true,
	) );

	// Messages array (new format for message rotation)
	register_post_meta( 'wphash_announcement', '_wphash_ab_messages', array(
		'type'              => 'object',
		'description'       => __( 'Multiple messages for rotation', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	// Message rotation
	register_post_meta( 'wphash_announcement', '_wphash_ab_message_rotation_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Enable message rotation', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_message_rotation_interval', array(
		'type'              => 'integer',
		'description'       => __( 'Message rotation interval in seconds', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 5,
	) );

	// Custom CSS
	register_post_meta( 'wphash_announcement', '_wphash_ab_custom_css', array(
		'type'              => 'string',
		'description'       => __( 'Custom CSS', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	// Track if user has edited content (for template preservation)
	register_post_meta( 'wphash_announcement', '_user_has_edited_content', array(
		'type'              => 'boolean',
		'description'       => __( 'User has edited the content', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	// Tab 2: Design
	register_post_meta( 'wphash_announcement', '_wphash_ab_bg_color', array(
		'type'              => 'string',
		'description'       => __( 'Background color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#000000',
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_bg_gradient', array(
		'type'              => 'object',
		'description'       => __( 'Gradient settings', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'       => 'object',
				'properties' => array(
					'enabled'    => array( 'type' => 'boolean' ),
					'color_stop' => array( 'type' => 'string' ),
					'angle'      => array( 'type' => 'integer' ),
				),
			),
		),
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_bg_type', array(
		'type'              => 'string',
		'description'       => __( 'Background type (solid/gradient/image)', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'solid',
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_bg_image', array(
		'type'              => 'object',
		'description'       => __( 'Background image settings', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'       => 'object',
				'properties' => array(
					'url'  => array( 'type' => 'string' ),
					'id'   => array( 'type' => 'integer' ),
					'size' => array( 'type' => 'string' ),
				),
			),
		),
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_text_color', array(
		'type'              => 'string',
		'description'       => __( 'Text color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#ffffff',
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_font_family', array(
		'type'              => 'string',
		'description'       => __( 'Font family', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'system-ui',
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_font_size', array(
		'type'              => 'integer',
		'description'       => __( 'Font size in pixels', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 16,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_font_weight', array(
		'type'              => 'integer',
		'description'       => __( 'Font weight', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 400,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_text_align', array(
		'type'              => 'string',
		'description'       => __( 'Text alignment', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'center',
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_height', array(
		'type'              => 'integer',
		'description'       => __( 'Bar height in pixels', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 60,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_padding', array(
		'type'              => 'object',
		'description'       => __( 'Padding values', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'       => 'object',
				'properties' => array(
					'top'    => array( 'type' => 'integer' ),
					'right'  => array( 'type' => 'integer' ),
					'bottom' => array( 'type' => 'integer' ),
					'left'   => array( 'type' => 'integer' ),
				),
			),
		),
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_border', array(
		'type'              => 'object',
		'description'       => __( 'Border settings', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'       => 'object',
				'properties' => array(
					'width' => array( 'type' => 'integer' ),
					'color' => array( 'type' => 'string' ),
					'style' => array( 'type' => 'string' ),
				),
			),
		),
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_shadow', array(
		'type'              => 'object',
		'description'       => __( 'Box shadow settings', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'       => 'object',
				'properties' => array(
					'enabled'  => array( 'type' => 'boolean' ),
					'offsetX'  => array( 'type' => 'integer' ),
					'offsetY'  => array( 'type' => 'integer' ),
					'blur'     => array( 'type' => 'integer' ),
					'spread'   => array( 'type' => 'integer' ),
					'color'    => array( 'type' => 'string' ),
				),
			),
		),
	) );

	// Tab 3: Position
	register_post_meta( 'wphash_announcement', '_wphash_ab_position', array(
		'type'              => 'string',
		'description'       => __( 'Bar position (top/bottom)', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'top',
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_sticky', array(
		'type'              => 'boolean',
		'description'       => __( 'Fixed position', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_z_index', array(
		'type'              => 'integer',
		'description'       => __( 'Z-index stacking order', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 9999,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_close_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Show close button', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_close_text', array(
		'type'              => 'string',
		'description'       => __( 'Close button text', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '✕',
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_reopen_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Show reopen button', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_reopen_text', array(
		'type'              => 'string',
		'description'       => __( 'Reopen button text', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'Show',
	) );

	// Cookie Duration Settings
	register_post_meta( 'wphash_announcement', '_wphash_ab_cookie_use_custom', array(
		'type'              => 'boolean',
		'description'       => __( 'Use custom cookie duration', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_cookie_expire_after_close', array(
		'type'              => 'string',
		'description'       => __( 'Cookie expiration after close', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '7_days',
	) );

	// Button Styling: CTA Button
	register_post_meta( 'wphash_announcement', '_wphash_ab_cta_color', array(
		'type'              => 'string',
		'description'       => __( 'CTA button text color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_cta_bg_color', array(
		'type'              => 'string',
		'description'       => __( 'CTA button background color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_cta_hover_color', array(
		'type'              => 'string',
		'description'       => __( 'CTA button hover text color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_cta_hover_bg_color', array(
		'type'              => 'string',
		'description'       => __( 'CTA button hover background color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_cta_font_size', array(
		'type'              => 'integer',
		'description'       => __( 'CTA button font size in pixels', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_cta_font_weight', array(
		'type'              => 'integer',
		'description'       => __( 'CTA button font weight', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_cta_border_radius', array(
		'type'              => 'integer',
		'description'       => __( 'CTA button border radius in pixels', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	// Button Styling: Close Button
	register_post_meta( 'wphash_announcement', '_wphash_ab_close_color', array(
		'type'              => 'string',
		'description'       => __( 'Close button text color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_close_bg_color', array(
		'type'              => 'string',
		'description'       => __( 'Close button background color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_close_hover_color', array(
		'type'              => 'string',
		'description'       => __( 'Close button hover text color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_close_hover_bg_color', array(
		'type'              => 'string',
		'description'       => __( 'Close button hover background color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_close_font_size', array(
		'type'              => 'integer',
		'description'       => __( 'Close button font size in pixels', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_close_font_weight', array(
		'type'              => 'integer',
		'description'       => __( 'Close button font weight', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_close_border_radius', array(
		'type'              => 'integer',
		'description'       => __( 'Close button border radius in pixels', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	// Tab 4: Targeting
	register_post_meta( 'wphash_announcement', '_wphash_ab_target_pages', array(
		'type'              => 'string',
		'description'       => __( 'Page targeting mode', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'all',
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_target_page_ids', array(
		'type'              => 'array',
		'description'       => __( 'Specific page IDs to target', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'integer' ),
			),
		),
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_exclude_page_ids', array(
		'type'              => 'array',
		'description'       => __( 'Page IDs to exclude', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'integer' ),
			),
		),
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_target_devices', array(
		'type'              => 'array',
		'description'       => __( 'Target devices', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'string' ),
			),
		),
		'default'           => array( 'desktop', 'mobile', 'tablet' ),
	) );

	// Customer Segmentation
	register_post_meta( 'wphash_announcement', '_wphash_ab_enable_customer_segmentation', array(
		'type'              => 'boolean',
		'description'       => __( 'Enable customer segmentation', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_target_logged_in_customers', array(
		'type'              => 'boolean',
		'description'       => __( 'Target logged in customers', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_target_guest_visitors', array(
		'type'              => 'boolean',
		'description'       => __( 'Target guest visitors', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	// Behavioral Targeting
	register_post_meta( 'wphash_announcement', '_wphash_ab_show_after_time_on_site', array(
		'type'              => 'boolean',
		'description'       => __( 'Show bar after time on site', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_minimum_time_on_site', array(
		'type'              => 'integer',
		'description'       => __( 'Minimum time on site in seconds', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 0,
	) );

	// Tab 5: Countdown (Pro)
	register_post_meta( 'wphash_announcement', '_wphash_ab_countdown_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Enable countdown', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_countdown_type', array(
		'type'              => 'string',
		'description'       => __( 'Countdown type', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'fixed',
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_countdown_date', array(
		'type'              => 'string',
		'description'       => __( 'Countdown target date/time', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_countdown_timezone', array(
		'type'              => 'string',
		'description'       => __( 'Timezone for countdown', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'site',
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_countdown_style', array(
		'type'              => 'string',
		'description'       => __( 'Countdown display format', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'compact',
	) );

	// Tab 6: Coupon (Pro)
	register_post_meta( 'wphash_announcement', '_wphash_ab_coupon_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Enable coupon', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_coupon_code', array(
		'type'              => 'string',
		'description'       => __( 'Coupon code', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_coupon_autocopy', array(
		'type'              => 'boolean',
		'description'       => __( 'Auto-copy to clipboard', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_coupon_autoapply', array(
		'type'              => 'boolean',
		'description'       => __( 'Auto-apply to cart', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	// Tab 7: Schedule (Pro)
	register_post_meta( 'wphash_announcement', '_wphash_ab_schedule_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Enable schedule', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_schedule_start', array(
		'type'              => 'string',
		'description'       => __( 'Schedule start datetime', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_schedule_end', array(
		'type'              => 'string',
		'description'       => __( 'Schedule end datetime', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_schedule_timezone', array(
		'type'              => 'string',
		'description'       => __( 'Schedule timezone', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'site',
	) );

	// Tab 8: Animation (Pro)
	register_post_meta( 'wphash_announcement', '_wphash_ab_animation_entry', array(
		'type'              => 'string',
		'description'       => __( 'Entry animation', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'slideInDown',
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_animation_exit', array(
		'type'              => 'string',
		'description'       => __( 'Exit animation', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'slideOutUp',
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_animation_duration', array(
		'type'              => 'integer',
		'description'       => __( 'Animation duration in ms', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 500,
	) );

	// Tab 9: A/B Test (Pro)
	register_post_meta( 'wphash_announcement', '_wphash_ab_test_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Enable A/B testing', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_announcement', '_wphash_ab_test_variants', array(
		'type'              => 'array',
		'description'       => __( 'A/B test variants', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'id'       => array( 'type' => 'string' ),
						'name'     => array( 'type' => 'string' ),
						'traffic'  => array( 'type' => 'integer' ),
						'settings' => array( 'type' => 'object' ),
					),
				),
			),
		),
	) );

	// Register _wphash_ab_status meta field
	register_meta( 'post', '_wphash_ab_status', array(
		'object_subtype' => 'wphash_announcement',
		'type'           => 'string',
		'description'    => 'Controls whether the announcement bar is active or inactive',
		'single'         => true,
		'sanitize_callback' => function( $value ) {
			return in_array( $value, array( 'active', 'inactive' ), true ) ? $value : 'inactive';
		},
		'auth_callback'  => function() {
			return current_user_can( 'manage_options' );
		},
	) );
}

add_action( 'init', __NAMESPACE__ . '\hashbar_register_announcement_bar_meta', 5 );
