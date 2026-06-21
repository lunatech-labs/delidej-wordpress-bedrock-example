<?php
/**
 * Popup Campaign Custom Post Type Registration
 *
 * Registers the wphash_popup CPT and all associated meta fields
 * for the Popup Campaign feature.
 *
 * @package HashBar Pro
 * @since 2.0.0
 */

namespace Hashbar\Pro;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*=======================================================
 *    Register Popup Campaign Post Type
 * =======================================================*/

/**
 * Register popup campaign custom post type
 *
 * Creates the wphash_popup CPT for popup campaigns.
 * Separate from announcement bars.
 *
 * @since 2.0.0
 */
function hashbar_register_popup_campaign_cpt() {
	$labels = array(
		'name'                  => _x( 'Popup Campaigns', 'Post Type General Name', 'hashbar' ),
		'singular_name'         => _x( 'Popup Campaign', 'Post Type Singular Name', 'hashbar' ),
		'menu_name'             => __( 'Popup Campaigns', 'hashbar' ),
		'name_admin_bar'        => __( 'Popup Campaign', 'hashbar' ),
		'archives'              => __( 'Popup Archives', 'hashbar' ),
		'parent_item_colon'     => __( 'Parent Popup:', 'hashbar' ),
		'all_items'             => __( 'All Popups', 'hashbar' ),
		'add_new_item'          => __( 'Add New Popup', 'hashbar' ),
		'add_new'               => __( 'Add New Popup', 'hashbar' ),
		'new_item'              => __( 'New Popup', 'hashbar' ),
		'edit_item'             => __( 'Edit Popup', 'hashbar' ),
		'update_item'           => __( 'Update Popup', 'hashbar' ),
		'view_item'             => __( 'View Popup', 'hashbar' ),
		'search_items'          => __( 'Search Popup', 'hashbar' ),
		'not_found'             => __( 'Not found', 'hashbar' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'hashbar' ),
		'featured_image'        => __( 'Featured Image', 'hashbar' ),
		'set_featured_image'    => __( 'Set featured image', 'hashbar' ),
		'remove_featured_image' => __( 'Remove featured image', 'hashbar' ),
		'use_featured_image'    => __( 'Use as featured image', 'hashbar' ),
		'insert_into_item'      => __( 'Insert into popup', 'hashbar' ),
		'uploaded_to_this_item' => __( 'Uploaded to this popup', 'hashbar' ),
		'items_list'            => __( 'Popups list', 'hashbar' ),
		'items_list_navigation' => __( 'Popups list navigation', 'hashbar' ),
		'filter_items_list'     => __( 'Filter popups list', 'hashbar' ),
	);

	$args = array(
		'label'               => __( 'Popup Campaign', 'hashbar' ),
		'labels'              => $labels,
		'supports'            => array( 'title' ),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => false,
		'show_in_menu'        => false,
		'show_in_rest'        => true,
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => false,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'rest_base'           => 'popup-campaigns',
		'rest_controller_class' => 'WP_REST_Posts_Controller',
	);

	register_post_type( 'wphash_popup', $args );
}

add_action( 'init', __NAMESPACE__ . '\hashbar_register_popup_campaign_cpt' );

/**
 * Register popup campaign post meta
 *
 * Registers all meta fields used by popup campaigns for REST API access
 *
 * @since 2.0.0
 */
function hashbar_register_popup_campaign_meta() {

	// ===========================================
	// Status
	// ===========================================
	register_post_meta( 'wphash_popup', '_wphash_popup_status', array(
		'type'              => 'string',
		'description'       => __( 'Popup status (active/inactive)', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'inactive',
		'sanitize_callback' => function( $value ) {
			return in_array( $value, array( 'active', 'inactive' ), true ) ? $value : 'inactive';
		},
	) );

	// ===========================================
	// Templates Tab
	// ===========================================
	register_post_meta( 'wphash_popup', '_wphash_popup_template_id', array(
		'type'              => 'string',
		'description'       => __( 'Template ID', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	// ===========================================
	// Basic Tab
	// ===========================================
	register_post_meta( 'wphash_popup', '_wphash_popup_position', array(
		'type'              => 'string',
		'description'       => __( 'Popup position (center, bottom_right, bottom_left, fullscreen, side_left, side_right, floating)', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'center',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_campaign_type', array(
		'type'              => 'string',
		'description'       => __( 'Campaign type', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'lead_capture',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_overlay_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Show background overlay', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => true,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_overlay_close', array(
		'type'              => 'boolean',
		'description'       => __( 'Close on overlay click', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => true,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_close_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Show close button', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => true,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_close_position', array(
		'type'              => 'string',
		'description'       => __( 'Close button position', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'top_right',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_esc_close', array(
		'type'              => 'boolean',
		'description'       => __( 'Close on ESC key', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => true,
	) );

	// ===========================================
	// Content Tab
	// ===========================================
	register_post_meta( 'wphash_popup', '_wphash_popup_content_type', array(
		'type'              => 'string',
		'description'       => __( 'Content type (custom, cf7, wpforms, etc.)', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'custom',
	) );

	// Form plugin IDs
	register_post_meta( 'wphash_popup', '_wphash_popup_cf7_form_id', array(
		'type'              => 'integer',
		'description'       => __( 'Contact Form 7 form ID', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 0,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_wpforms_form_id', array(
		'type'              => 'integer',
		'description'       => __( 'WPForms form ID', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 0,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_ninja_form_id', array(
		'type'              => 'integer',
		'description'       => __( 'Ninja Forms form ID', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 0,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_gravity_form_id', array(
		'type'              => 'integer',
		'description'       => __( 'Gravity Forms form ID', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 0,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_fluent_form_id', array(
		'type'              => 'integer',
		'description'       => __( 'Fluent Forms form ID', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 0,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_ht_form_id', array(
		'type'              => 'integer',
		'description'       => __( 'HT Contact Form ID', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 0,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_shortcode', array(
		'type'              => 'string',
		'description'       => __( 'Custom shortcode', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	// Custom HTML / Visual Editor content
	register_post_meta( 'wphash_popup', '_wphash_popup_custom_html', array(
		'type'              => 'string',
		'description'       => __( 'Custom HTML content', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	// Custom content
	register_post_meta( 'wphash_popup', '_wphash_popup_image', array(
		'type'              => 'object',
		'description'       => __( 'Popup image', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'       => 'object',
				'properties' => array(
					'url' => array( 'type' => 'string' ),
					'id'  => array( 'type' => 'integer' ),
				),
			),
		),
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_image_position', array(
		'type'              => 'string',
		'description'       => __( 'Image position', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'top',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_heading', array(
		'type'              => 'string',
		'description'       => __( 'Popup heading', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_subheading', array(
		'type'              => 'string',
		'description'       => __( 'Popup subheading', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_description', array(
		'type'              => 'string',
		'description'       => __( 'Popup description', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	// CTA Button
	register_post_meta( 'wphash_popup', '_wphash_popup_cta_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Show CTA button', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => true,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_cta_text', array(
		'type'              => 'string',
		'description'       => __( 'CTA button text', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'Get Started',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_cta_url', array(
		'type'              => 'string',
		'description'       => __( 'CTA button URL', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_cta_target', array(
		'type'              => 'string',
		'description'       => __( 'CTA link target', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '_self',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_cta_close_on_click', array(
		'type'              => 'boolean',
		'description'       => __( 'Close popup on CTA click', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	// Secondary Button
	register_post_meta( 'wphash_popup', '_wphash_popup_secondary_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Show secondary button', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_secondary_text', array(
		'type'              => 'string',
		'description'       => __( 'Secondary button text', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'No Thanks',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_secondary_action', array(
		'type'              => 'string',
		'description'       => __( 'Secondary button action', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'close',
	) );

	// ===========================================
	// Form Tab
	// ===========================================
	register_post_meta( 'wphash_popup', '_wphash_popup_form_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Enable built-in form', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => true,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_form_fields', array(
		'type'              => 'array',
		'description'       => __( 'Form fields configuration', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'id'          => array( 'type' => 'string' ),
						'type'        => array( 'type' => 'string' ),
						'label'       => array( 'type' => 'string' ),
						'placeholder' => array( 'type' => 'string' ),
						'required'    => array( 'type' => 'boolean' ),
						'width'       => array( 'type' => 'string' ),
						'options'     => array( 'type' => 'array' ),
					),
				),
			),
		),
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_form_submit_text', array(
		'type'              => 'string',
		'description'       => __( 'Submit button text', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'Subscribe',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_form_submitting_text', array(
		'type'              => 'string',
		'description'       => __( 'Submitting text', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'Submitting...',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_form_success_action', array(
		'type'              => 'string',
		'description'       => __( 'Success action (message, redirect, close)', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'message',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_form_success_message', array(
		'type'              => 'string',
		'description'       => __( 'Success message', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'Thank you! You have successfully subscribed.',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_form_success_redirect_url', array(
		'type'              => 'string',
		'description'       => __( 'Redirect URL after submission', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_form_close_delay', array(
		'type'              => 'integer',
		'description'       => __( 'Close delay in seconds', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 3,
	) );

	// Mailchimp Integration
	register_post_meta( 'wphash_popup', '_wphash_popup_mailchimp_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Enable Mailchimp integration', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_mailchimp_api_key', array(
		'type'              => 'string',
		'description'       => __( 'Mailchimp API key', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_mailchimp_list_id', array(
		'type'              => 'string',
		'description'       => __( 'Mailchimp list/audience ID', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_mailchimp_double_optin', array(
		'type'              => 'boolean',
		'description'       => __( 'Enable double opt-in', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => true,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_mailchimp_tags', array(
		'type'              => 'string',
		'description'       => __( 'Mailchimp subscriber tags', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	// ===========================================
	// Design Tab
	// ===========================================
	register_post_meta( 'wphash_popup', '_wphash_popup_width', array(
		'type'              => 'integer',
		'description'       => __( 'Popup width in pixels', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 500,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_max_width', array(
		'type'              => 'integer',
		'description'       => __( 'Max width percentage', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 90,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_padding', array(
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

	register_post_meta( 'wphash_popup', '_wphash_popup_bg_type', array(
		'type'              => 'string',
		'description'       => __( 'Background type', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'solid',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_bg_color', array(
		'type'              => 'string',
		'description'       => __( 'Background color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#ffffff',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_gradient_color', array(
		'type'              => 'string',
		'description'       => __( 'Gradient second color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#f0f0f0',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_gradient_direction', array(
		'type'              => 'string',
		'description'       => __( 'Gradient direction', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'to_bottom',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_bg_image', array(
		'type'              => 'object',
		'description'       => __( 'Background image', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'       => 'object',
				'properties' => array(
					'url' => array( 'type' => 'string' ),
					'id'  => array( 'type' => 'integer' ),
				),
			),
		),
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_text_color', array(
		'type'              => 'string',
		'description'       => __( 'Text color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#1a1a1a',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_heading_color', array(
		'type'              => 'string',
		'description'       => __( 'Heading color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#1a1a1a',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_overlay_color', array(
		'type'              => 'string',
		'description'       => __( 'Overlay color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'rgba(0, 0, 0, 0.5)',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_font_family', array(
		'type'              => 'string',
		'description'       => __( 'Font family', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'system-ui',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_heading_size', array(
		'type'              => 'integer',
		'description'       => __( 'Heading size in pixels', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 24,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_text_size', array(
		'type'              => 'integer',
		'description'       => __( 'Text size in pixels', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 16,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_text_align', array(
		'type'              => 'string',
		'description'       => __( 'Text alignment', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'center',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_border_radius', array(
		'type'              => 'integer',
		'description'       => __( 'Border radius in pixels', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 12,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_border_width', array(
		'type'              => 'integer',
		'description'       => __( 'Border width in pixels', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 0,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_border_color', array(
		'type'              => 'string',
		'description'       => __( 'Border color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#e8e8e8',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_shadow', array(
		'type'              => 'string',
		'description'       => __( 'Box shadow preset', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'large',
	) );

	// Button styling
	register_post_meta( 'wphash_popup', '_wphash_popup_btn_bg_color', array(
		'type'              => 'string',
		'description'       => __( 'Button background color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#1890ff',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_btn_text_color', array(
		'type'              => 'string',
		'description'       => __( 'Button text color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#ffffff',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_btn_hover_bg_color', array(
		'type'              => 'string',
		'description'       => __( 'Button hover background', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#40a9ff',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_btn_hover_text_color', array(
		'type'              => 'string',
		'description'       => __( 'Button hover text color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#ffffff',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_btn_border_radius', array(
		'type'              => 'integer',
		'description'       => __( 'Button border radius', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 6,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_btn_font_size', array(
		'type'              => 'integer',
		'description'       => __( 'Button font size', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 16,
	) );

	// Close button styling
	register_post_meta( 'wphash_popup', '_wphash_popup_close_color', array(
		'type'              => 'string',
		'description'       => __( 'Close button color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#8c8c8c',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_close_hover_color', array(
		'type'              => 'string',
		'description'       => __( 'Close button hover color', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '#1a1a1a',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_close_size', array(
		'type'              => 'integer',
		'description'       => __( 'Close button size', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 24,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_custom_css', array(
		'type'              => 'string',
		'description'       => __( 'Custom CSS', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	// Content order
	register_post_meta( 'wphash_popup', '_wphash_popup_content_order', array(
		'type'              => 'array',
		'description'       => __( 'Content element order', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'string' ),
			),
		),
		'default'           => array( 'heading', 'subheading', 'description', 'countdown', 'coupon', 'form_or_buttons' ),
	) );

	// Element spacing
	register_post_meta( 'wphash_popup', '_wphash_popup_element_spacing', array(
		'type'              => 'object',
		'description'       => __( 'Per-element spacing (margin-bottom)', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'                 => 'object',
				'additionalProperties' => array( 'type' => 'number' ),
			),
		),
		'default'           => array(
			'heading'         => 6,
			'subheading'      => 8,
			'description'     => 16,
			'countdown'       => 16,
			'coupon'          => 16,
			'form_or_buttons' => 0,
		),
	) );

	// ===========================================
	// Triggers Tab
	// ===========================================
	register_post_meta( 'wphash_popup', '_wphash_popup_trigger_type', array(
		'type'              => 'string',
		'description'       => __( 'Trigger type', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'time_delay',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_trigger_delay', array(
		'type'              => 'integer',
		'description'       => __( 'Time delay in seconds', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 5,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_trigger_scroll_percent', array(
		'type'              => 'integer',
		'description'       => __( 'Scroll percentage', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 50,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_trigger_click_selector', array(
		'type'              => 'string',
		'description'       => __( 'Click trigger CSS selector', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_trigger_click_delay', array(
		'type'              => 'integer',
		'description'       => __( 'Delay in seconds after click before showing popup', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 0,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_trigger_inactivity_time', array(
		'type'              => 'integer',
		'description'       => __( 'Inactivity time in seconds', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 30,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_trigger_element_selector', array(
		'type'              => 'string',
		'description'       => __( 'Element visible selector', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_trigger_page_views_count', array(
		'type'              => 'integer',
		'description'       => __( 'Page views count', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 3,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_exit_sensitivity', array(
		'type'              => 'string',
		'description'       => __( 'Exit intent sensitivity', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'medium',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_exit_mobile_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Enable exit intent on mobile', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	// ===========================================
	// Targeting Tab
	// ===========================================
	register_post_meta( 'wphash_popup', '_wphash_popup_target_pages', array(
		'type'              => 'string',
		'description'       => __( 'Page targeting mode', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'all',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_target_page_ids', array(
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

	register_post_meta( 'wphash_popup', '_wphash_popup_exclude_page_ids', array(
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

	register_post_meta( 'wphash_popup', '_wphash_popup_target_devices', array(
		'type'              => 'array',
		'description'       => __( 'Target devices', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array( 'type' => 'string' ),
			),
		),
		'default'           => array( 'desktop', 'tablet', 'mobile' ),
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_target_user_status', array(
		'type'              => 'string',
		'description'       => __( 'User status targeting', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'all',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_target_new_visitors', array(
		'type'              => 'boolean',
		'description'       => __( 'Target new visitors only', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_target_returning_visitors', array(
		'type'              => 'boolean',
		'description'       => __( 'Target returning visitors only', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_target_referrer_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Enable referrer targeting', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_target_referrer_type', array(
		'type'              => 'string',
		'description'       => __( 'Referrer match type', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'include',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_target_referrer_sources', array(
		'type'              => 'string',
		'description'       => __( 'Referrer URLs', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => '',
	) );

	// ===========================================
	// Frequency Tab
	// ===========================================
	register_post_meta( 'wphash_popup', '_wphash_popup_frequency_type', array(
		'type'              => 'string',
		'description'       => __( 'Frequency type', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'always',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_frequency_days', array(
		'type'              => 'integer',
		'description'       => __( 'Number of days', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 7,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_frequency_times', array(
		'type'              => 'integer',
		'description'       => __( 'Maximum times to show', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 3,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_after_close', array(
		'type'              => 'string',
		'description'       => __( 'After close behavior', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'respect_frequency',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_after_close_days', array(
		'type'              => 'integer',
		'description'       => __( 'Days to hide after close', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 7,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_after_convert', array(
		'type'              => 'string',
		'description'       => __( 'After conversion behavior', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'never_show',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_after_convert_days', array(
		'type'              => 'integer',
		'description'       => __( 'Days to hide after conversion', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 30,
	) );

	// ===========================================
	// Animation Tab
	// ===========================================
	register_post_meta( 'wphash_popup', '_wphash_popup_animation_entry', array(
		'type'              => 'string',
		'description'       => __( 'Entry animation', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'fadeIn',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_animation_exit', array(
		'type'              => 'string',
		'description'       => __( 'Exit animation', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 'fadeOut',
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_animation_duration', array(
		'type'              => 'integer',
		'description'       => __( 'Animation duration in ms', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 300,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_animation_delay', array(
		'type'              => 'integer',
		'description'       => __( 'Animation delay in ms', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => 0,
	) );

	// ===========================================
	// Spin to Win (Gamification)
	// ===========================================
	register_post_meta( 'wphash_popup', '_wphash_popup_spinwheel_enabled', array(
		'type'              => 'boolean',
		'description'       => __( 'Enable spin wheel', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => true,
		'default'           => false,
	) );

	register_post_meta( 'wphash_popup', '_wphash_popup_spinwheel_segments', array(
		'type'              => 'array',
		'description'       => __( 'Spin wheel segments', 'hashbar' ),
		'single'            => true,
		'show_in_rest'      => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'id'          => array( 'type' => 'string' ),
						'label'       => array( 'type' => 'string' ),
						'color'       => array( 'type' => 'string' ),
						'probability' => array( 'type' => 'integer' ),
						'prize_type'  => array( 'type' => 'string' ),
						'prize_value' => array( 'type' => 'string' ),
					),
				),
			),
		),
	) );
}

add_action( 'init', __NAMESPACE__ . '\hashbar_register_popup_campaign_meta', 5 );
