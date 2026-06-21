<?php
namespace Hashbar\Pro;

/**
 * Announcement Bar Settings Localization
 *
 * Provides all field definitions, labels, options, and configurations
 * for the announcement bar editor in a centralized localization file.
 *
 * @package HashBar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Announcement Bar Settings Localization Class
 *
 * Handles all localized strings and field definitions for the announcement bar editor.
 */
class Hashbar_Announcement_Bar_Settings {

	/**
	 * Check if Pro version is active
	 *
	 * @return bool
	 */
	public static function is_pro() {
		// Temporarily return true for A/B testing development in free version
		return true; // defined( 'HASHBAR_WPNBP_VERSION' );
	}

	/**
	 * Get full URL for a template preview image
	 *
	 * @param string $filename The image filename
	 * @return string Full URL to the template preview image
	 */
	public static function get_template_preview_url( $filename ) {
		if ( empty( $filename ) ) {
			return '';
		}
		if ( defined( 'HASHBAR_TEMPLATE_IMG_SOURCE' ) && 'external' === HASHBAR_TEMPLATE_IMG_SOURCE ) {
			$base = defined( 'HASHBAR_TEMPLATE_IMG_URL' ) ? HASHBAR_TEMPLATE_IMG_URL : '';
			return trailingslashit( $base ) . 'announcement/preview/' . $filename;
		}
		return plugins_url( 'hashbar-templates-library-src/announcement/preview/' . $filename, dirname( dirname( dirname( __FILE__ ) ) ) );
	}

	/**
	 * Get full URL for a template content image
	 *
	 * @param string $filename The image filename
	 * @return string Full URL to the template image
	 */
	public static function get_template_image_url( $filename ) {
		if ( empty( $filename ) ) {
			return '';
		}
		if ( defined( 'HASHBAR_TEMPLATE_IMG_SOURCE' ) && 'external' === HASHBAR_TEMPLATE_IMG_SOURCE ) {
			$base = defined( 'HASHBAR_TEMPLATE_IMG_URL' ) ? HASHBAR_TEMPLATE_IMG_URL : '';
			return trailingslashit( $base ) . 'announcement/images/' . $filename;
		}
		return plugins_url( 'hashbar-templates-library-src/announcement/images/' . $filename, dirname( dirname( dirname( __FILE__ ) ) ) );
	}

	/**
	 * Get editor tab definitions
	 *
	 * @return array
	 */
	public static function get_editor_tabs() {
		return array(
			array(
				'key'   => 'templates',
				'icon'  => 'ThunderboltOutlined',
				'label' => esc_html__( 'Templates', 'hashbar' ),
			),
			array(
				'key'   => 'content',
				'icon'  => 'FormOutlined',
				'label' => esc_html__( 'Content', 'hashbar' ),
			),
			array(
				'key'   => 'position',
				'icon'  => 'LayoutOutlined',
				'label' => esc_html__( 'Position', 'hashbar' ),
			),
			array(
				'key'   => 'targeting',
				'icon'  => 'AimOutlined',
				'label' => esc_html__( 'Targeting', 'hashbar' ),
			),
			array(
				'key'   => 'countdown',
				'icon'  => 'HourglassOutlined',
				'label' => esc_html__( 'Countdown', 'hashbar' ),
			),
			array(
				'key'   => 'coupon',
				'icon'  => 'GiftOutlined',
				'label' => esc_html__( 'Coupon', 'hashbar' ),
				'isPro' => false,
			),
			array(
				'key'   => 'schedule',
				'icon'  => 'CalendarOutlined',
				'label' => esc_html__( 'Schedule', 'hashbar' ),
				'isPro' => false,
			),
			array(
				'key'   => 'design',
				'icon'  => 'BgColorsOutlined',
				'label' => esc_html__( 'Design', 'hashbar' ),
			),
			array(
				'key'   => 'animation',
				'icon'  => 'ThunderboltOutlined',
				'label' => esc_html__( 'Animation', 'hashbar' ),
				'isPro' => false, // Partially free
			),
		);
	}

	/**
	 * Get Content Tab field definitions
	 *
	 * @return array
	 */
	public static function get_content_fields() {
		return array(
			'section_bar_content' => array(
				'type'        => 'section',
				'label' => esc_html__( 'Bar Content', 'hashbar' ),
				'description' => esc_html__( 'Configure the messages and call-to-action for your announcement bar', 'hashbar' ),
			),
			'_wphash_ab_messages' => array(
				'type'       => 'messages_array',
				'label' => esc_html__( 'Messages', 'hashbar' ),
				'default'    => array(
					array(
						'text'           => '',
						'cta_enabled'    => true,
						'cta_text' => esc_html__( 'Learn More', 'hashbar' ),
						'cta_url'        => '#',
						'cta_target'     => '_self',
					),
				),
				'subfields'  => array(
					'text' => array(
						'type'        => 'textarea',
						'label' => esc_html__( 'Message Text', 'hashbar' ),
						'placeholder' => esc_html__( 'Enter your announcement message (max 500 characters)', 'hashbar' ),
						'maxLength'   => 500,
						'rows'        => 3,
					),
					'cta_enabled' => array(
						'type'  => 'switch',
						'label' => esc_html__( 'Call-to-Action Button', 'hashbar' ),
					),
					'cta_text' => array(
						'type'        => 'text',
						'label' => esc_html__( 'Button Text', 'hashbar' ),
						'placeholder' => esc_html__( 'e.g., Shop Now, Learn More', 'hashbar' ),
					),
					'cta_url' => array(
						'type'        => 'text',
						'label' => esc_html__( 'Button URL', 'hashbar' ),
						'placeholder' => esc_html__( 'https://example.com', 'hashbar' ),
					),
					'cta_target' => array(
						'type'    => 'select',
						'label' => esc_html__( 'Link Target', 'hashbar' ),
						'options' => array(
							'_self' => esc_html__( 'Open in Same Window', 'hashbar' ),
							'_blank' => esc_html__( 'Open in New Tab', 'hashbar' ),
						),
					),
				),
			),
			'section_message_rotation' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Message Rotation', 'hashbar' ),
			),
			'_wphash_ab_message_rotation_enabled' => array(
				'type'  => 'switch',
				'label' => esc_html__( 'Enable Message Rotation', 'hashbar' ),
				'desc' => esc_html__( 'Automatically rotate between multiple messages', 'hashbar' ),
			),
			'_wphash_ab_message_rotation_interval' => array(
				'type'      => 'number',
				'label' => esc_html__( 'Time Between Messages', 'hashbar' ),
				'desc' => esc_html__( 'Time between each message when rotating (5-60 seconds)', 'hashbar' ),
				'default'   => 5,
				'min'       => 5,
				'max'       => 60,
				'condition' => array(
					'key'      => '_wphash_ab_message_rotation_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
		);
	}

	/**
	 * Get Design Tab field definitions
	 *
	 * @return array
	 */
	public static function get_design_fields() {
		return array(
			'section_colors' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Colors', 'hashbar' ),
			),
			'_wphash_ab_bg_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Background Color', 'hashbar' ),
				'default'  => '#000000',
				'presets'  => array( '#000000', '#ffffff', '#1890ff', '#52c41a', '#faad14', '#f5222d' ),
			),
			'_wphash_ab_bg_type' => array(
				'type'    => 'select',
				'label' => esc_html__( 'Background Type', 'hashbar' ),
				'default' => 'solid',
				'options' => array(
					'solid'    => esc_html__( 'Solid Color', 'hashbar' ),
					'gradient' => esc_html__( 'Gradient', 'hashbar' ),
					'image'    => esc_html__( 'Image', 'hashbar' ),
				),
			),
			'_wphash_ab_gradient_direction' => array(
				'type'    => 'select',
				'label' => esc_html__( 'Gradient Direction', 'hashbar' ),
				'default' => 'to_bottom',
				'options' => array(
					'to_bottom'       => esc_html__( 'Top to Bottom', 'hashbar' ),
					'to_right'        => esc_html__( 'Left to Right', 'hashbar' ),
					'to_bottom_right' => esc_html__( 'Top-Left to Bottom-Right', 'hashbar' ),
					'to_bottom_left'  => esc_html__( 'Top-Right to Bottom-Left', 'hashbar' ),
				),
				'condition' => array(
					'key'      => '_wphash_ab_bg_type',
					'operator' => '==',
					'value'    => 'gradient',
				),
			),
			'_wphash_ab_gradient_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Gradient Second Color', 'hashbar' ),
				'default'  => '#ffffff',
				'presets'  => array( '#ffffff', '#000000', '#1890ff', '#52c41a', '#faad14', '#f5222d' ),
				'condition' => array(
					'key'      => '_wphash_ab_bg_type',
					'operator' => '==',
					'value'    => 'gradient',
				),
			),
			'_wphash_ab_bg_image' => array(
				'type'    => 'image',
				'label' => esc_html__( 'Background Image', 'hashbar' ),
				'default' => array(
					'url'  => '',
					'id'   => 0,
					'size' => 'cover',
				),
				'condition' => array(
					'key'      => '_wphash_ab_bg_type',
					'operator' => '==',
					'value'    => 'image',
				),
			),
			'_wphash_ab_bg_image_size' => array(
				'type'    => 'select',
				'label' => esc_html__( 'Image Size', 'hashbar' ),
				'default' => 'cover',
				'options' => array(
					'cover'   => esc_html__( 'Cover', 'hashbar' ),
					'contain' => esc_html__( 'Contain', 'hashbar' ),
					'auto'    => esc_html__( 'Auto', 'hashbar' ),
				),
				'condition' => array(
					'key'      => '_wphash_ab_bg_type',
					'operator' => '==',
					'value'    => 'image',
				),
			),
			'_wphash_ab_text_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Text Color', 'hashbar' ),
				'default'  => '#ffffff',
				'presets'  => array( '#ffffff', '#000000', '#1890ff', '#52c41a', '#faad14', '#f5222d' ),
			),
			'section_typography' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Typography', 'hashbar' ),
			),
			'_wphash_ab_font_family' => array(
				'type'    => 'select',
				'label' => esc_html__( 'Font Family', 'hashbar' ),
				'default' => 'system-ui',
				'options' => array(
					'system-ui'      => 'System UI',
					'Arial'          => 'Arial',
					'Georgia'        => 'Georgia',
					'Courier New'    => 'Courier New',
					'Trebuchet MS'   => 'Trebuchet MS',
					'Times New Roman' => 'Times New Roman',
				),
			),
			'_wphash_ab_font_weight' => array(
				'type'    => 'select',
				'label' => esc_html__( 'Font Weight', 'hashbar' ),
				'default' => 400,
				'options' => array(
					300 => esc_html__( 'Light (300)', 'hashbar' ),
					400 => esc_html__( 'Normal (400)', 'hashbar' ),
					600 => esc_html__( 'Semibold (600)', 'hashbar' ),
					700 => esc_html__( 'Bold (700)', 'hashbar' ),
				),
			),
			'_wphash_ab_font_size' => array(
				'type'    => 'slider',
				'label' => esc_html__( 'Font Size', 'hashbar' ),
				'default' => 16,
				'min'     => 12,
				'max'     => 32,
				'unit'    => 'px',
			),
			'_wphash_ab_text_align' => array(
				'type'    => 'select',
				'label' => esc_html__( 'Text Alignment', 'hashbar' ),
				'default' => 'center',
				'options' => array(
					'left' => esc_html__( 'Left', 'hashbar' ),
					'center' => esc_html__( 'Center', 'hashbar' ),
					'right' => esc_html__( 'Right', 'hashbar' ),
				),
			),
			'section_spacing' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Spacing', 'hashbar' ),
			),
			'_wphash_ab_height' => array(
				'type'    => 'slider',
				'label' => esc_html__( 'Bar Height', 'hashbar' ),
				'default' => 60,
				'min'     => 40,
				'max'     => 200,
				'unit'    => 'px',
			),
			'_wphash_ab_padding' => array(
				'type'    => 'padding',
				'label' => esc_html__( 'Padding', 'hashbar' ),
				'default' => array(
					'top'    => 10,
					'right'  => 20,
					'bottom' => 10,
					'left'   => 20,
				),
				'unit'    => 'px',
			),
			'section_cta_styling' => array(
				'type'      => 'section',
				'label' => esc_html__( 'CTA Button Styling', 'hashbar' ),
				'condition' => array(
					'key'      => '_wphash_ab_cta_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_cta_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Button Text Color', 'hashbar' ),
				'default'  => '#000000',
				'presets'  => array( '#000000', '#ffffff', '#1890ff', '#52c41a', '#faad14', '#f5222d' ),
				'condition' => array(
					'key'      => '_wphash_ab_cta_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_cta_bg_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Button Background Color', 'hashbar' ),
				'default'  => '#ffffff',
				'presets'  => array( '#ffffff', '#000000', '#1890ff', '#52c41a', '#faad14', '#f5222d' ),
				'condition' => array(
					'key'      => '_wphash_ab_cta_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_cta_hover_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Hover Text Color', 'hashbar' ),
				'default'  => '#000000',
				'presets'  => array( '#000000', '#ffffff', '#1890ff', '#52c41a', '#faad14', '#f5222d' ),
				'condition' => array(
					'key'      => '_wphash_ab_cta_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_cta_hover_bg_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Hover Background Color', 'hashbar' ),
				'default'  => '#f0f0f0',
				'presets'  => array( '#f0f0f0', '#ffffff', '#1890ff', '#52c41a', '#faad14', '#f5222d' ),
				'condition' => array(
					'key'      => '_wphash_ab_cta_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_cta_font_size' => array(
				'type'      => 'slider',
				'label' => esc_html__( 'CTA Font Size', 'hashbar' ),
				'default'   => 14,
				'min'       => 12,
				'max'       => 32,
				'unit'      => 'px',
				'condition' => array(
					'key'      => '_wphash_ab_cta_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_cta_font_weight' => array(
				'type'      => 'select',
				'label' => esc_html__( 'CTA Font Weight', 'hashbar' ),
				'default'   => 500,
				'options'   => array(
					300 => esc_html__( 'Light (300)', 'hashbar' ),
					400 => esc_html__( 'Normal (400)', 'hashbar' ),
					600 => esc_html__( 'Semibold (600)', 'hashbar' ),
					700 => esc_html__( 'Bold (700)', 'hashbar' ),
				),
				'condition' => array(
					'key'      => '_wphash_ab_cta_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_cta_border_radius' => array(
				'type'      => 'slider',
				'label' => esc_html__( 'CTA Border Radius', 'hashbar' ),
				'default'   => 4,
				'min'       => 0,
				'max'       => 20,
				'unit'      => 'px',
				'condition' => array(
					'key'      => '_wphash_ab_cta_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'section_close_styling' => array(
				'type'      => 'section',
				'label' => esc_html__( 'Close Button Styling', 'hashbar' ),
				'condition' => array(
					'key'      => '_wphash_ab_close_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_close_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Button Text Color', 'hashbar' ),
				'default'  => '#000000',
				'presets'  => array( '#000000', '#ffffff', '#1890ff', '#52c41a', '#faad14', '#f5222d' ),
				'condition' => array(
					'key'      => '_wphash_ab_close_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_close_bg_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Button Background Color', 'hashbar' ),
				'default'  => '#ffffff',
				'presets'  => array( '#ffffff', '#000000', '#1890ff', '#52c41a', '#faad14', '#f5222d' ),
				'condition' => array(
					'key'      => '_wphash_ab_close_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_close_hover_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Hover Text Color', 'hashbar' ),
				'default'  => '#000000',
				'presets'  => array( '#000000', '#ffffff', '#1890ff', '#52c41a', '#faad14', '#f5222d' ),
				'condition' => array(
					'key'      => '_wphash_ab_close_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_close_hover_bg_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Hover Background Color', 'hashbar' ),
				'default'  => '#f0f0f0',
				'presets'  => array( '#f0f0f0', '#ffffff', '#1890ff', '#52c41a', '#faad14', '#f5222d' ),
				'condition' => array(
					'key'      => '_wphash_ab_close_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_close_font_size' => array(
				'type'      => 'slider',
				'label' => esc_html__( 'Close Font Size', 'hashbar' ),
				'default'   => 14,
				'min'       => 12,
				'max'       => 32,
				'unit'      => 'px',
				'condition' => array(
					'key'      => '_wphash_ab_close_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_close_font_weight' => array(
				'type'      => 'select',
				'label' => esc_html__( 'Close Font Weight', 'hashbar' ),
				'default'   => 500,
				'options'   => array(
					300 => esc_html__( 'Light (300)', 'hashbar' ),
					400 => esc_html__( 'Normal (400)', 'hashbar' ),
					600 => esc_html__( 'Semibold (600)', 'hashbar' ),
					700 => esc_html__( 'Bold (700)', 'hashbar' ),
				),
				'condition' => array(
					'key'      => '_wphash_ab_close_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_close_border_radius' => array(
				'type'      => 'slider',
				'label' => esc_html__( 'Close Border Radius', 'hashbar' ),
				'default'   => 4,
				'min'       => 0,
				'max'       => 20,
				'unit'      => 'px',
				'condition' => array(
					'key'      => '_wphash_ab_close_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'section_reopen_styling' => array(
				'type'      => 'section',
				'label' => esc_html__( 'Reopen Button Styling', 'hashbar' ),
				'condition' => array(
					'key'      => '_wphash_ab_reopen_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_reopen_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Button Text Color', 'hashbar' ),
				'default'  => '#ffffff',
				'presets'  => array( '#ffffff', '#000000', '#1890ff', '#52c41a', '#faad14', '#f5222d' ),
				'condition' => array(
					'key'      => '_wphash_ab_reopen_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_reopen_bg_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Button Background Color', 'hashbar' ),
				'default'  => '#667eea',
				'presets'  => array( '#667eea', '#764ba2', '#000000', '#1890ff', '#52c41a', '#f5222d' ),
				'condition' => array(
					'key'      => '_wphash_ab_reopen_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_reopen_hover_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Hover Text Color', 'hashbar' ),
				'default'  => '#ffffff',
				'presets'  => array( '#ffffff', '#000000', '#1890ff', '#52c41a', '#faad14', '#f5222d' ),
				'condition' => array(
					'key'      => '_wphash_ab_reopen_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_reopen_hover_bg_color' => array(
				'type'     => 'color',
				'label' => esc_html__( 'Hover Background Color', 'hashbar' ),
				'default'  => '#764ba2',
				'presets'  => array( '#764ba2', '#667eea', '#000000', '#1890ff', '#52c41a', '#f5222d' ),
				'condition' => array(
					'key'      => '_wphash_ab_reopen_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_reopen_font_size' => array(
				'type'      => 'slider',
				'label' => esc_html__( 'Font Size', 'hashbar' ),
				'default'   => 14,
				'min'       => 12,
				'max'       => 32,
				'unit'      => 'px',
				'condition' => array(
					'key'      => '_wphash_ab_reopen_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_reopen_font_weight' => array(
				'type'      => 'select',
				'label' => esc_html__( 'Font Weight', 'hashbar' ),
				'default'   => 500,
				'options'   => array(
					300 => esc_html__( 'Light (300)', 'hashbar' ),
					400 => esc_html__( 'Normal (400)', 'hashbar' ),
					600 => esc_html__( 'Semibold (600)', 'hashbar' ),
					700 => esc_html__( 'Bold (700)', 'hashbar' ),
				),
				'condition' => array(
					'key'      => '_wphash_ab_reopen_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_reopen_border_radius' => array(
				'type'      => 'slider',
				'label' => esc_html__( 'Border Radius', 'hashbar' ),
				'default'   => 4,
				'min'       => 0,
				'max'       => 20,
				'unit'      => 'px',
				'condition' => array(
					'key'      => '_wphash_ab_reopen_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'section_custom_css' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Custom CSS', 'hashbar' ),
			),
			'_wphash_ab_custom_css' => array(
				'type'        => 'textarea',
				'label'       => esc_html__( 'Enter your custom CSS here', 'hashbar' ),
				'description' => esc_html__( 'Add custom CSS to style your announcement bar. Use .hashbar-announcement-bar, .hashbar-announcement-cta, .hashbar-coupon-display, or .hashbar-announcement-close as selectors.', 'hashbar' ),
				'default'     => '',
				'rows'        => 6,
				'placeholder' => ".hashbar-announcement-bar { border-bottom: 2px solid red; }\n.hashbar-announcement-cta { }\n.hashbar-coupon-display {}\n.hashbar-announcement-close { }",
			),
		);
	}

	/**
	 * Get Position Tab field definitions
	 *
	 * @return array
	 */
	public static function get_position_fields() {
		return array(
			'section_bar_position' => array(
				'type'        => 'section',
				'label' => esc_html__( 'Bar Position & Display', 'hashbar' ),
				'description' => esc_html__( 'Control where and how your announcement bar appears', 'hashbar' ),
			),
			'_wphash_ab_position' => array(
				'type'        => 'radio',
				'label' => esc_html__( 'Bar Position', 'hashbar' ),
				'description' => esc_html__( 'Choose the position where your bar will be displayed', 'hashbar' ),
				'default'     => 'top',
				'options'     => array(
					'top' => esc_html__( 'Top of page', 'hashbar' ),
					'bottom' => esc_html__( 'Bottom of page', 'hashbar' ),
				),
			),
			'_wphash_ab_sticky' => array(
				'type'        => 'switch',
				'label' => esc_html__( 'Make bar sticky (stays visible when scrolling)', 'hashbar' ),
				'default'     => true,
			),
			'_wphash_ab_z_index' => array(
				'type'        => 'number',
				'label' => esc_html__( 'Z-Index', 'hashbar' ),
				'default'     => 10001,
				'min'         => 1,
				'max'         => 999999,
				'description' => esc_html__( 'Controls stacking order (higher values appear on top)', 'hashbar' ),
			),
			'section_close_button' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Close Button', 'hashbar' ),
			),
			'_wphash_ab_close_enabled' => array(
				'type'    => 'switch',
				'label' => esc_html__( 'Show close button', 'hashbar' ),
				'default' => true,
			),
			'_wphash_ab_close_text' => array(
				'type'        => 'text',
				'label' => esc_html__( 'Close Button Text', 'hashbar' ),
				'default'     => '✕',
				'placeholder' => '✕',
				'maxLength'   => 5,
				'condition'   => array(
					'key'      => '_wphash_ab_close_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_close_position' => array(
				'type'      => 'select',
				'label' => esc_html__( 'Close Button Position', 'hashbar' ),
				'default'   => 'right',
				'options'   => array(
					'left' => esc_html__( 'Left', 'hashbar' ),
					'right' => esc_html__( 'Right', 'hashbar' ),
				),
				'condition' => array(
					'key'      => '_wphash_ab_close_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'section_reopen_button' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Reopen Button', 'hashbar' ),
				'isPro' => false,
			),
			'_wphash_ab_reopen_enabled' => array(
				'type'    => 'switch',
				'label' => esc_html__( 'Enable reopen button', 'hashbar' ),
				'default' => false,
				'isPro'   => false,
			),
			'_wphash_ab_reopen_text' => array(
				'type'        => 'text',
				'label' => esc_html__( 'Reopen Button Text', 'hashbar' ),
				'default' => esc_html__( 'Show', 'hashbar' ),
				'isPro'       => false,
				'condition'   => array(
					'key'      => '_wphash_ab_reopen_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'section_cookie_duration' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Cookie Duration', 'hashbar' ),
				'isPro' => false,
			),
			'_wphash_ab_cookie_expire_after_close' => array(
				'type'        => 'select',
				'label'       => esc_html__( 'Expire After Close', 'hashbar' ),
				'description' => esc_html__( 'Set when this announcement bar should reappear after being closed. Note: Changing this setting only applies to future closes. If the bar is hidden by a previous cookie, clear your browser cookies or wait for it to expire.', 'hashbar' ),
				'default'     => 'show_on_reload',
				'options'     => array(
					'show_on_reload' => esc_html__( 'Show on each page reload (no cookie)', 'hashbar' ),
					'session_only' => esc_html__( 'Session only (closes when browser closes)', 'hashbar' ),
					'1_hour'       => esc_html__( '1 Hour', 'hashbar' ),
					'6_hours'      => esc_html__( '6 Hours', 'hashbar' ),
					'1_day'        => esc_html__( '1 Day', 'hashbar' ),
					'7_days'       => esc_html__( '7 Days', 'hashbar' ),
					'2_weeks'      => esc_html__( '2 Weeks', 'hashbar' ),
					'1_month'      => esc_html__( '1 Month', 'hashbar' ),
					'never'        => esc_html__( 'Never (persistent)', 'hashbar' ),
				),
				'isPro'       => false,
			),
		);
	}

	/**
	 * Get Targeting Tab field definitions
	 *
	 * @return array
	 */
	public static function get_targeting_fields() {
		return array(
			'section_page_targeting' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Page Targeting', 'hashbar' ),
			),
			'_wphash_ab_target_pages' => array(
				'type'        => 'radio',
				'label' => esc_html__( 'Show On', 'hashbar' ),
				'description' => esc_html__( 'Choose where your announcement bar will be displayed', 'hashbar' ),
				'default'     => 'all',
				'options'     => array(
					'all' => esc_html__( 'All Pages', 'hashbar' ),
					'homepage' => esc_html__( 'Homepage Only', 'hashbar' ),
					'specific' => esc_html__( 'Specific Pages/Posts', 'hashbar' ),
					'exclude' => esc_html__( 'All Except Excluded', 'hashbar' ),
				),
			),
			'_wphash_ab_target_page_ids' => array(
				'type'      => 'multi-select',
				'label' => esc_html__( 'Select Pages/Posts', 'hashbar' ),
				'default'   => array(),
				'condition' => array(
					'key'      => '_wphash_ab_target_pages',
					'operator' => '==',
					'value'    => 'specific',
				),
			),
			'_wphash_ab_exclude_page_ids' => array(
				'type'      => 'multi-select',
				'label' => esc_html__( 'Exclude Pages/Posts', 'hashbar' ),
				'default'   => array(),
				'condition' => array(
					'key'      => '_wphash_ab_target_pages',
					'operator' => '==',
					'value'    => 'exclude',
				),
			),
			'section_device_targeting' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Device Targeting', 'hashbar' ),
			),
			'_wphash_ab_target_devices' => array(
				'type'    => 'checkbox-group',
				'label' => esc_html__( 'Show On Devices', 'hashbar' ),
				'default' => array( 'desktop', 'tablet', 'mobile' ),
				'options' => array(
					'desktop' => esc_html__( 'Desktop', 'hashbar' ),
					'tablet' => esc_html__( 'Tablet', 'hashbar' ),
					'mobile' => esc_html__( 'Mobile', 'hashbar' ),
				),
			),
			'section_geographic_targeting' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Geographic Targeting', 'hashbar' ),
			),
			'_wphash_ab_target_countries' => array(
				'type'        => 'multi-select',
				'label' => esc_html__( 'Target Countries', 'hashbar' ),
				'description' => esc_html__( 'Select countries where your announcement bar will be displayed. Leave empty to display in all countries.', 'hashbar' ),
				'default'     => array(),
				'options'     => array(
					'US' => esc_html__( 'United States', 'hashbar' ),
					'GB' => esc_html__( 'United Kingdom', 'hashbar' ),
					'CA' => esc_html__( 'Canada', 'hashbar' ),
					'AU' => esc_html__( 'Australia', 'hashbar' ),
					'DE' => esc_html__( 'Germany', 'hashbar' ),
					'FR' => esc_html__( 'France', 'hashbar' ),
					'ES' => esc_html__( 'Spain', 'hashbar' ),
					'IT' => esc_html__( 'Italy', 'hashbar' ),
					'NL' => esc_html__( 'Netherlands', 'hashbar' ),
					'BE' => esc_html__( 'Belgium', 'hashbar' ),
					'CH' => esc_html__( 'Switzerland', 'hashbar' ),
					'AT' => esc_html__( 'Austria', 'hashbar' ),
					'SE' => esc_html__( 'Sweden', 'hashbar' ),
					'NO' => esc_html__( 'Norway', 'hashbar' ),
					'DK' => esc_html__( 'Denmark', 'hashbar' ),
					'FI' => esc_html__( 'Finland', 'hashbar' ),
					'PL' => esc_html__( 'Poland', 'hashbar' ),
					'CZ' => esc_html__( 'Czech Republic', 'hashbar' ),
					'IE' => esc_html__( 'Ireland', 'hashbar' ),
					'JP' => esc_html__( 'Japan', 'hashbar' ),
					'CN' => esc_html__( 'China', 'hashbar' ),
					'IN' => esc_html__( 'India', 'hashbar' ),
					'BR' => esc_html__( 'Brazil', 'hashbar' ),
					'MX' => esc_html__( 'Mexico', 'hashbar' ),
					'KR' => esc_html__( 'South Korea', 'hashbar' ),
					'SG' => esc_html__( 'Singapore', 'hashbar' ),
					'HK' => esc_html__( 'Hong Kong', 'hashbar' ),
					'TH' => esc_html__( 'Thailand', 'hashbar' ),
					'MY' => esc_html__( 'Malaysia', 'hashbar' ),
					'ID' => esc_html__( 'Indonesia', 'hashbar' ),
					'PH' => esc_html__( 'Philippines', 'hashbar' ),
					'VN' => esc_html__( 'Vietnam', 'hashbar' ),
					'NZ' => esc_html__( 'New Zealand', 'hashbar' ),
					'ZA' => esc_html__( 'South Africa', 'hashbar' ),
					'RU' => esc_html__( 'Russia', 'hashbar' ),
					'UA' => esc_html__( 'Ukraine', 'hashbar' ),
					'GR' => esc_html__( 'Greece', 'hashbar' ),
					'PT' => esc_html__( 'Portugal', 'hashbar' ),
					'AR' => esc_html__( 'Argentina', 'hashbar' ),
					'CL' => esc_html__( 'Chile', 'hashbar' ),
					'CO' => esc_html__( 'Colombia', 'hashbar' ),
					'PE' => esc_html__( 'Peru', 'hashbar' ),
					'TR' => esc_html__( 'Turkey', 'hashbar' ),
					'AE' => esc_html__( 'United Arab Emirates', 'hashbar' ),
					'SA' => esc_html__( 'Saudi Arabia', 'hashbar' ),
					'IL' => esc_html__( 'Israel', 'hashbar' ),
					'BD' => esc_html__( 'Bangladesh', 'hashbar' ),
					'PK' => esc_html__( 'Pakistan', 'hashbar' ),
					'LK' => esc_html__( 'Sri Lanka', 'hashbar' ),
					'EG' => esc_html__( 'Egypt', 'hashbar' ),
					'NG' => esc_html__( 'Nigeria', 'hashbar' ),
					'KE' => esc_html__( 'Kenya', 'hashbar' ),
				),
			),
			'section_customer_segmentation' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Customer Segmentation', 'hashbar' ),
			),
			'_wphash_ab_enable_customer_segmentation' => array(
				'type'    => 'switch',
				'label' => esc_html__( 'Enable Customer Segmentation', 'hashbar' ),
				'default' => false,
			),
			'_wphash_ab_target_logged_in_customers' => array(
				'type'      => 'switch',
				'label' => esc_html__( 'Show to Logged In Customers', 'hashbar' ),
				'default'   => false,
				'condition' => array(
					'key'      => '_wphash_ab_enable_customer_segmentation',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_target_guest_visitors' => array(
				'type'      => 'switch',
				'label' => esc_html__( 'Show to Guest Visitors', 'hashbar' ),
				'default'   => false,
				'condition' => array(
					'key'      => '_wphash_ab_enable_customer_segmentation',
					'operator' => '==',
					'value'    => true,
				),
			),
			'section_behavioral_targeting' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Behavioral Targeting', 'hashbar' ),
			),
			'_wphash_ab_show_after_time_on_site' => array(
				'type'    => 'switch',
				'label' => esc_html__( 'Show after time on site', 'hashbar' ),
				'default' => false,
			),
			'_wphash_ab_minimum_time_on_site' => array(
				'type'      => 'number',
				'label' => esc_html__( 'Minimum Time on Site', 'hashbar' ),
				'default'   => 0,
				'min'       => 0,
				'max'       => 3600,
				'unit'      => 'seconds',
				'condition' => array(
					'key'      => '_wphash_ab_show_after_time_on_site',
					'operator' => '==',
					'value'    => true,
				),
			),
		);
	}

	/**
	 * Get Countdown Tab field definitions
	 *
	 * @return array
	 */
	public static function get_countdown_fields() {
		return array(
			'section_countdown' => array(
				'type'        => 'section',
				'label'       => esc_html__( 'Countdown Timer', 'hashbar' ),
				'description' => esc_html__( 'Set up a countdown timer for your announcement', 'hashbar' ),
			),
			'_wphash_ab_countdown_enabled' => array(
				'type'    => 'switch',
				'label' => esc_html__( 'Enable Countdown Timer', 'hashbar' ),
				'default' => false,
			),
			'_wphash_ab_countdown_type' => array(
				'type'      => 'select',
				'label' => esc_html__( 'Countdown Type', 'hashbar' ),
				'default'   => 'fixed',
				'options'   => array(
					'fixed' => esc_html__( 'Fixed End Date', 'hashbar' ),
					'recurring' => esc_html__( 'Recurring', 'hashbar' ),
					'evergreen' => esc_html__( 'Evergreen', 'hashbar' ),
				),
				'isPro'     => false,
				'condition' => array(
					'key'      => '_wphash_ab_countdown_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
		'_wphash_ab_countdown_reset_time' => array(
			'type'      => 'time',
			'label' => esc_html__( 'Reset Time', 'hashbar' ),
			'default'   => '00:00',
			'isPro'     => false,
			'condition' => array(
				'key'      => '_wphash_ab_countdown_type',
				'operator' => '==',
				'value'    => 'recurring',
			),
		),
		'_wphash_ab_countdown_reset_days' => array(
			'type'      => 'array',
			'label' => esc_html__( 'Reset Days', 'hashbar' ),
			'default'   => array(),
			'isPro'     => false,
			'condition' => array(
				'key'      => '_wphash_ab_countdown_type',
				'operator' => '==',
				'value'    => 'recurring',
			),
		),
		'_wphash_ab_countdown_duration' => array(
			'type'      => 'number',
			'label' => esc_html__( 'Duration (hours)', 'hashbar' ),
			'default'   => 24,
			'min'       => 1,
			'max'       => 720,
			'isPro'     => false,
			'condition' => array(
				'key'      => '_wphash_ab_countdown_type',
				'operator' => '==',
				'value'    => 'evergreen',
			),
		),
		'_wphash_ab_countdown_date' => array(
				'type'      => 'datetime-local',
				'label' => esc_html__( 'End Date & Time', 'hashbar' ),
				'default'   => '',
				'condition' => array(
					'key'      => '_wphash_ab_countdown_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_countdown_style' => array(
				'type'      => 'select',
				'label' => esc_html__( 'Timer Style', 'hashbar' ),
				'default'   => 'simple',
				'options'   => array(
					'simple' => esc_html__( 'Simple', 'hashbar' ),
					'digital' => esc_html__( 'Digital', 'hashbar' ),
					'box' => esc_html__( 'Box', 'hashbar' ),
					'circular' => esc_html__( 'Circular', 'hashbar' ),
				),
				'condition' => array(
					'key'      => '_wphash_ab_countdown_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_countdown_position' => array(
				'type'      => 'select',
				'label' => esc_html__( 'Timer Position', 'hashbar' ),
				'default'   => 'right',
				'options'   => array(
					'top' => esc_html__( 'Top (Above Message)', 'hashbar' ),
					'left' => esc_html__( 'Left (Left of Message)', 'hashbar' ),
					'right' => esc_html__( 'Right (Right of Message)', 'hashbar' ),
					'below' => esc_html__( 'Below (Underneath Message)', 'hashbar' ),
				),
				'condition' => array(
					'key'      => '_wphash_ab_countdown_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_countdown_text_before' => array(
				'type'        => 'text',
				'label' => esc_html__( 'Text Before Timer', 'hashbar' ),
				'default' => esc_html__( 'Offer ends in:', 'hashbar' ),
				'placeholder' => esc_html__( 'Offer ends in:', 'hashbar' ),
				'condition'   => array(
					'key'      => '_wphash_ab_countdown_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_countdown_text_after' => array(
				'type'      => 'text',
				'label' => esc_html__( 'Text After Timer', 'hashbar' ),
				'default'   => '',
				'condition' => array(
					'key'      => '_wphash_ab_countdown_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'section_countdown_display' => array(
				'type'      => 'section',
				'label' => esc_html__( 'Display Options', 'hashbar' ),
			'isPro'     => false,
				'condition' => array(
					'key'      => '_wphash_ab_countdown_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_countdown_show_days' => array(
				'type'      => 'switch',
				'label' => esc_html__( 'Show Days', 'hashbar' ),
				'default'   => true,
				'condition' => array(
					'key'      => '_wphash_ab_countdown_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_countdown_show_hours' => array(
				'type'      => 'switch',
				'label' => esc_html__( 'Show Hours', 'hashbar' ),
				'default'   => true,
				'condition' => array(
					'key'      => '_wphash_ab_countdown_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_countdown_show_minutes' => array(
				'type'      => 'switch',
				'label' => esc_html__( 'Show Minutes', 'hashbar' ),
				'default'   => true,
				'condition' => array(
					'key'      => '_wphash_ab_countdown_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_countdown_show_seconds' => array(
				'type'      => 'switch',
				'label' => esc_html__( 'Show Seconds', 'hashbar' ),
				'default'   => true,
				'condition' => array(
					'key'      => '_wphash_ab_countdown_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_countdown_timezone' => array(
				'type'      => 'select',
				'label' => esc_html__( 'Timezone', 'hashbar' ),
				'default'   => 'site',
				'isPro'     => false,
				'options'   => array(
					'site'    => esc_html__( 'Site Timezone', 'hashbar' ),
					'visitor' => esc_html__( 'Visitor Timezone', 'hashbar' ),
				),
				'condition' => array(
					'key'      => '_wphash_ab_countdown_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
		);
	}

	/**
	 * Get Coupon Tab field definitions
	 *
	 * @return array
	 */
	public static function get_coupon_fields() {
		return array(
			'section_coupon' => array(
				'type'        => 'section',
				'label'       => esc_html__( 'Coupon Code', 'hashbar' ),
				'description' => esc_html__( 'Display a coupon code for your visitors', 'hashbar' ),
				'isPro'       => false,
			),
			'_wphash_ab_coupon_enabled' => array(
				'type'    => 'switch',
				'label' => esc_html__( 'Enable Coupon Display', 'hashbar' ),
				'default' => false,
				'isPro'   => false,
			),
			'_wphash_ab_coupon_code' => array(
				'type'        => 'text',
				'label' => esc_html__( 'Coupon Code', 'hashbar' ),
				'placeholder' => esc_html__( 'Enter coupon code (e.g., SAVE20)', 'hashbar' ),
				'default'     => 'SAVE20',
				'isPro'       => false,
				'condition'   => array(
					'key'      => '_wphash_ab_coupon_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_coupon_show_copy_button' => array(
				'type'      => 'switch',
				'label' => esc_html__( 'Show Copy Button', 'hashbar' ),
				'default'   => false,
				'isPro'     => false,
				'condition' => array(
					'key'      => '_wphash_ab_coupon_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_coupon_copy_button_text' => array(
				'type'        => 'text',
				'label' => esc_html__( 'Copy Button Text', 'hashbar' ),
				'placeholder' => esc_html__( 'e.g., Copy', 'hashbar' ),
				'default' => esc_html__( 'Copy', 'hashbar' ),
				'isPro'       => false,
				'condition'   => array(
					'key'      => '_wphash_ab_coupon_show_copy_button',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_coupon_copied_button_text' => array(
				'type'        => 'text',
				'label' => esc_html__( 'Copied Button Text', 'hashbar' ),
				'placeholder' => esc_html__( 'e.g., Copied!', 'hashbar' ),
				'default' => esc_html__( 'Copied!', 'hashbar' ),
				'isPro'       => false,
				'condition'   => array(
					'key'      => '_wphash_ab_coupon_show_copy_button',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_coupon_autocopy_on_click' => array(
				'type'      => 'switch',
				'label' => esc_html__( 'Auto-copy on click', 'hashbar' ),
				'default'   => false,
				'isPro'     => false,
				'condition' => array(
					'key'      => '_wphash_ab_coupon_show_copy_button',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_coupon_autoapply' => array(
				'type'      => 'switch',
				'label' => esc_html__( 'Auto-apply to Cart', 'hashbar' ),
				'default'   => false,
				'isPro'     => false,
				'condition' => array(
					'key'      => '_wphash_ab_coupon_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
		'section_coupon_advanced' => array(
			'type'        => 'section',
			'label'       => esc_html__( 'Advanced Coupon Options', 'hashbar' ),
			'description' => esc_html__( 'Configure advanced coupon features including auto-copy and auto-apply functionality', 'hashbar' ),
			'isPro'       => false,
		),
		);
	}

	/**
	 * Get Schedule Tab field definitions
	 *
	 * @return array
	 */
	public static function get_schedule_fields() {
		return array(
			'section_schedule' => array(
				'type'        => 'section',
				'label'       => esc_html__( 'Schedule', 'hashbar' ),
				'description' => esc_html__( 'Set a simple date range for your announcement', 'hashbar' ),
				'isPro'       => false,
			),
			'_wphash_ab_schedule_enabled' => array(
				'type'    => 'switch',
				'label' => esc_html__( 'Enable Schedule', 'hashbar' ),
				'default' => false,
				'isPro'   => false,
			),
			'_wphash_ab_schedule_start' => array(
				'type'      => 'datetime-local',
				'label' => esc_html__( 'Start Date & Time', 'hashbar' ),
				'default'   => '',
				'isPro'     => false,
				'condition' => array(
					'key'      => '_wphash_ab_schedule_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_schedule_end' => array(
				'type'      => 'datetime-local',
				'label' => esc_html__( 'End Date & Time', 'hashbar' ),
				'default'   => '',
				'isPro'     => false,
				'condition' => array(
					'key'      => '_wphash_ab_schedule_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_schedule_timezone' => array(
				'type'    => 'select',
				'label' => esc_html__( 'Timezone', 'hashbar' ),
				'default' => 'site',
				'isPro'   => false,
				'options' => array(
					'site'    => esc_html__( 'Site Timezone', 'hashbar' ),
					'visitor' => esc_html__( 'Visitor Timezone', 'hashbar' ),
				),
			),
			'_wphash_ab_schedule_recurring' => array(
				'type'      => 'switch',
				'label' => esc_html__( 'Enable Recurring Schedule', 'hashbar' ),
				'default'   => false,
				'isPro'     => false,
				'condition' => array(
					'key'      => '_wphash_ab_schedule_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_schedule_recurring_days' => array(
				'type'      => 'checkbox-group',
				'label' => esc_html__( 'Active Days', 'hashbar' ),
				'default'   => array(),
				'isPro'     => false,
				'options'   => array(
					'monday' => esc_html__( 'Monday', 'hashbar' ),
					'tuesday' => esc_html__( 'Tuesday', 'hashbar' ),
					'wednesday' => esc_html__( 'Wednesday', 'hashbar' ),
					'thursday' => esc_html__( 'Thursday', 'hashbar' ),
					'friday' => esc_html__( 'Friday', 'hashbar' ),
					'saturday' => esc_html__( 'Saturday', 'hashbar' ),
					'sunday' => esc_html__( 'Sunday', 'hashbar' ),
				),
				'condition' => array(
					'key'      => '_wphash_ab_schedule_recurring',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_schedule_time_targeting' => array(
				'type'      => 'switch',
				'label' => esc_html__( 'Enable Time Targeting', 'hashbar' ),
				'default'   => false,
				'isPro'     => false,
				'condition' => array(
					'key'      => '_wphash_ab_schedule_enabled',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_schedule_time_start' => array(
				'type'      => 'time',
				'label' => esc_html__( 'Start Time', 'hashbar' ),
				'default'   => '00:00',
				'isPro'     => false,
				'condition' => array(
					'key'      => '_wphash_ab_schedule_time_targeting',
					'operator' => '==',
					'value'    => true,
				),
			),
			'_wphash_ab_schedule_time_end' => array(
				'type'      => 'time',
				'label' => esc_html__( 'End Time', 'hashbar' ),
				'default'   => '23:59',
				'isPro'     => false,
				'condition' => array(
					'key'      => '_wphash_ab_schedule_time_targeting',
					'operator' => '==',
					'value'    => true,
				),
			),
		);
	}

	/**
	 * Get Animation Tab field definitions
	 *
	 * @return array
	 */
	public static function get_animation_fields() {
		return array(
			'section_animation' => array(
				'type'  => 'section',
				'label' => esc_html__( 'Animation', 'hashbar' ),
			),
			'_wphash_ab_animation_entry' => array(
				'type'    => 'select',
				'label' => esc_html__( 'Entry Animation', 'hashbar' ),
				'default' => 'slideInDown',
				'options' => array(
					'fadeIn' => esc_html__( 'Fade In', 'hashbar' ),
					'slideInDown' => esc_html__( 'Slide Down', 'hashbar' ),
					'slideInUp' => esc_html__( 'Slide Up', 'hashbar' ),
					'bounceIn' => esc_html__( 'Bounce In', 'hashbar' ),
					'zoomIn' => esc_html__( 'Zoom In', 'hashbar' ),
				),
				'isPro'   => false,
			),
			'_wphash_ab_animation_exit' => array(
				'type'    => 'select',
				'label' => esc_html__( 'Exit Animation', 'hashbar' ),
				'default' => 'slideOutUp',
				'options' => array(
					'slideOutUp' => esc_html__( 'Slide Out Up', 'hashbar' ),
					'slideOutDown' => esc_html__( 'Slide Out Down', 'hashbar' ),
					'fadeOut' => esc_html__( 'Fade Out', 'hashbar' ),
					'bounceOut' => esc_html__( 'Bounce Out', 'hashbar' ),
					'zoomOut' => esc_html__( 'Zoom Out', 'hashbar' ),
				),
				'isPro'   => false,
			),
			'_wphash_ab_animation_duration' => array(
				'type'    => 'slider',
				'label' => esc_html__( 'Animation Duration', 'hashbar' ),
				'default' => 500,
				'min'     => 200,
				'max'     => 2000,
				'step'    => 100,
				'unit'    => 'ms',
			),
		);
	}

	/**
	 * Get A/B Test Tab field definitions
	 *
	 * @return array
	 */
	public static function get_abtest_fields() {
		return array(
			'section_abtest' => array(
				'type'  => 'section',
				'label' => esc_html__( 'A/B Testing', 'hashbar' ),
				'isPro' => false,
			),
			'_wphash_ab_test_enabled' => array(
				'type'    => 'switch',
				'label' => esc_html__( 'Enable A/B Testing', 'hashbar' ),
				'default' => false,
				'isPro'   => false,
			),
			'_wphash_ab_test_variants' => array(
				'type'    => 'variants-array',
				'label' => esc_html__( 'Test Variants', 'hashbar' ),
				'default' => array(),
				'isPro'   => false,
			),
		);
	}

	/**
	 * Get template definitions
	 *
	 * @return array
	 */
	public static function get_templates() {
		return array(
			'classic' => array(
				'id'          => 'classic',
				'name' => esc_html__( 'Classic', 'hashbar' ),
				'description' => esc_html__( 'A simple and clean notification banner', 'hashbar' ),
				'isPro'       => false,
				'preview'     => 'classic.jpg',
				'color'       => '#000000',
				'textColor'   => '#ffffff',
			),
			'sale_alert' => array(
				'id'          => 'sale_alert',
				'name' => esc_html__( 'Sale Alert', 'hashbar' ),
				'description' => esc_html__( 'Perfect for promoting limited-time sales', 'hashbar' ),
				'isPro'       => false,
				'preview'     => 'sale-alert.jpg',
				'color'       => '#f5222d',
				'textColor'   => '#ffffff',
			),
			'free_shipping' => array(
				'id'          => 'free_shipping',
				'name' => esc_html__( 'Free Shipping', 'hashbar' ),
				'description' => esc_html__( 'Announce free shipping offers', 'hashbar' ),
				'isPro'       => false,
				'preview'     => 'free-shipping.jpg',
				'color'       => '#52c41a',
				'textColor'   => '#ffffff',
			),
			'info_banner' => array(
				'id'          => 'info_banner',
				'name' => esc_html__( 'Info Banner', 'hashbar' ),
				'description' => esc_html__( 'Display important information or updates', 'hashbar' ),
				'isPro'       => false,
				'preview'     => 'info-banner.jpg',
				'color'       => '#1890ff',
				'textColor'   => '#ffffff',
			),
			'countdown_sale' => array(
				'id'          => 'countdown_sale',
				'name' => esc_html__( 'Countdown Sale', 'hashbar' ),
				'description' => esc_html__( 'Create urgency with countdown timer', 'hashbar' ),
				'isPro'       => false,
				'preview'     => 'countdown-sale.jpg',
				'color'       => '#faad14',
				'textColor'   => '#ffffff',
			),
			'coupon_display' => array(
				'id'          => 'coupon_display',
				'name' => esc_html__( 'Coupon Display', 'hashbar' ),
				'description' => esc_html__( 'Showcase and distribute coupon codes', 'hashbar' ),
				'isPro'       => false,
				'preview'     => 'coupon-display.jpg',
				'color'       => '#722ed1',
				'textColor'   => '#ffffff',
			),
			'minimal_elegant' => array(
				'id'          => 'minimal_elegant',
				'name' => esc_html__( 'Minimal Elegant', 'hashbar' ),
				'description' => wp_kses_post( __( '✨ Quality products, exceptional service. Shop now.', 'hashbar' ) ),
				'isPro'       => false,
				'preview'     => 'minimal-elegant.jpg',
				'color'       => '#f5f5f5',
				'textColor'   => '#262626',
			),
			'urgent_alert' => array(
				'id'          => 'urgent_alert',
				'name' => esc_html__( 'Urgent Alert', 'hashbar' ),
				'description' => wp_kses_post( __( '🔥 HURRY! Sale ends in 2 hours - Don\'t miss out!', 'hashbar' ) ),
				'isPro'       => false,
				'preview'     => 'urgent-alert.jpg',
				'color'       => '#ff6b00',
				'textColor'   => '#ffffff',
			),
			'trust_builder' => array(
				'id'          => 'trust_builder',
				'name' => esc_html__( 'Trust Builder', 'hashbar' ),
				'description' => wp_kses_post( __( '🔒 Secure Checkout | 30-Day Returns | Free Shipping Over $50', 'hashbar' ) ),
				'isPro'       => false,
				'preview'     => 'trust-builder.jpg',
				'bgType'      => 'gradient',
				'color'       => '#0ea5e9',
				'gradientColor' => '#06b6d4',
				'gradientDirection' => 'to_right',
				'textColor'   => '#ffffff',
			),
			'new_launch' => array(
				'id'          => 'new_launch',
				'name' => esc_html__( 'New Launch', 'hashbar' ),
				'description' => wp_kses_post( __( '🚀 NEW ARRIVALS: Check out our latest collection!', 'hashbar' ) ),
				'isPro'       => false,
				'preview'     => 'new-launch.jpg',
				'bgType'      => 'gradient',
				'color'       => '#1e293b',
				'gradientColor' => '#10b981',
				'gradientDirection' => 'to_bottom',
				'textColor'   => '#ffffff',
			),
			'premium_gold' => array(
				'id'          => 'premium_gold',
				'name' => esc_html__( 'Premium Gold', 'hashbar' ),
				'description' => wp_kses_post( __( '⭐ VIP Members: Exclusive 40% Off Luxury Collection', 'hashbar' ) ),
				'isPro'       => false,
				'preview'     => 'premium-gold.jpg',
				'bgType'      => 'gradient',
				'color'       => '#1f2937',
				'gradientColor' => '#0a0a0a',
				'gradientDirection' => 'to_right',
				'textColor'   => '#fbbf24',
			),
			'holiday_special' => array(
				'id'          => 'holiday_special',
				'name' => esc_html__( 'Holiday Special', 'hashbar' ),
				'description' => wp_kses_post( __( '🎄 Holiday Sale: Up to 70% Off + Free Gift Wrapping!', 'hashbar' ) ),
				'isPro'       => false,
				'preview'     => 'holiday-special.jpg',
				'color'       => '#1e3a8a',
				'textColor'   => '#fde047',
			),
			'summer_vibes' => array(
				'id'          => 'summer_vibes',
				'name' => esc_html__( 'Summer Vibes', 'hashbar' ),
				'description' => wp_kses_post( __( '☀️ SUMMER SALE: Up to 60% Off Everything!', 'hashbar' ) ),
				'isPro'       => false,
				'preview'     => 'summer-vibes.jpg',
				'bgType'      => 'gradient',
				'color'       => '#f97316',
				'gradientColor' => '#ea580c',
				'gradientDirection' => 'to_bottom_right',
				'textColor'   => '#ffffff',
			),
			'social_media' => array(
				'id'          => 'social_media',
				'name' => esc_html__( 'Social Media', 'hashbar' ),
				'description' => html_entity_decode( wp_kses_post( __( '📱 Follow us on social media for exclusive updates & special offers!', 'hashbar' ) ) ),
				'isPro'       => false,
				'preview'     => 'social-media.jpg',
				'bgType'      => 'gradient',
				'color'       => '#9333ea',
				'gradientColor' => '#7c3aed',
				'gradientDirection' => 'to_bottom',
				'textColor'   => '#ffffff',
			),
			'bundle_deal' => array(
				'id'          => 'bundle_deal',
				'name' => esc_html__( 'Bundle Deal', 'hashbar' ),
				'description' => wp_kses_post( __( '🎁 Buy 2 Get 1 Free on All Items - Limited Time!', 'hashbar' ) ),
				'isPro'       => false,
				'preview'     => 'bundle-deal.jpg',
				'bgType'      => 'gradient',
				'color'       => '#059669',
				'gradientColor' => '#047857',
				'gradientDirection' => 'to_right',
				'textColor'   => '#ffffff',
			),
			'vip_exclusive' => array(
				'id'          => 'vip_exclusive',
				'name' => esc_html__( 'VIP Exclusive', 'hashbar' ),
				'description' => wp_kses_post( __( '👑 Members Only: Early Access to New Collection + Extra 25% Off', 'hashbar' ) ),
				'isPro'       => false,
				'preview'     => 'vip-exclusive.jpg',
				'bgType'      => 'gradient',
				'color'       => '#4f46e5',
				'gradientColor' => '#6366f1',
				'gradientDirection' => 'to_bottom_right',
				'textColor'   => '#ffffff',
			),
			'limited_stock' => array(
				'id'          => 'limited_stock',
				'name' => esc_html__( 'Limited Stock', 'hashbar' ),
				'description' => wp_kses_post( __( '⚠️ ALMOST GONE! Only few items left - Order before they\'re gone!', 'hashbar' ) ),
				'isPro'       => false,
				'preview'     => 'limited-stock.jpg',
				'color'       => '#dc2626',
				'textColor'   => '#ffffff',
			),
			'early_bird' => array(
				'id'          => 'early_bird',
				'name' => esc_html__( 'Early Bird', 'hashbar' ),
				'description' => wp_kses_post( __( '🌅 Early Bird Special: Get First Access + Extra 15% Off Pre-Orders!', 'hashbar' ) ),
				'isPro'       => false,
				'preview'     => 'early-bird.jpg',
				'bgType'      => 'gradient',
				'color'       => '#ea580c',
				'gradientColor' => '#f97316',
				'gradientDirection' => 'to_bottom',
				'textColor'   => '#ffffff',
			),
		);
	}

	/**
	 * Get all labels and UI text
	 *
	 * @return array
	 */
	public static function get_labels() {
		return array(
			'editor_title'             => esc_html__( 'Edit Announcement Bar', 'hashbar' ),
			'create_new'               => esc_html__( 'Create New Announcement Bar', 'hashbar' ),
			'save'                     => esc_html__( 'Save', 'hashbar' ),
			'cancel'                   => esc_html__( 'Cancel', 'hashbar' ),
			'back'                     => esc_html__( 'Back', 'hashbar' ),
			'delete'                   => esc_html__( 'Delete', 'hashbar' ),
			'remove'                   => esc_html__( 'Remove', 'hashbar' ),
			'add_message'              => esc_html__( 'Add Another Message', 'hashbar' ),
			'add_variant'              => esc_html__( 'Add Variant', 'hashbar' ),
			'pro_feature'              => esc_html__( 'Pro Feature', 'hashbar' ),
			'pro_badge'                => esc_html__( 'PRO', 'hashbar' ),
			'reopen_pro_message'       => esc_html__( 'Enable reopen button is a Pro feature', 'hashbar' ),
			'color_presets'            => esc_html__( 'Recommended', 'hashbar' ),
			'message_count'            => esc_html__( 'Message #{count}', 'hashbar' ),
			'control_variant'          => esc_html__( 'Control Variant (Original)', 'hashbar' ),
			'traffic_split'            => esc_html__( 'Traffic Split: {value}%', 'hashbar' ),
			'no_variants'              => esc_html__( 'No variants yet', 'hashbar' ),
			'date_time_required'       => esc_html__( 'Date and time are required', 'hashbar' ),
			'end_after_start'          => esc_html__( 'End date must be after start date', 'hashbar' ),

			// List page UI
			'list_all_bars'            => esc_html__( 'Announcement Bars', 'hashbar' ),
			'list_create_new'          => esc_html__( 'Create New', 'hashbar' ),
			'list_search_placeholder'  => esc_html__( 'Search announcement bars...', 'hashbar' ),
			'list_no_bars_found'       => esc_html__( 'No announcement bars found', 'hashbar' ),
			'list_create_first'        => esc_html__( 'No announcement bars yet', 'hashbar' ),
			'list_duplicate'           => esc_html__( 'Duplicate', 'hashbar' ),
			'list_delete_title'        => esc_html__( 'Delete Announcement Bar', 'hashbar' ),
			'list_delete_confirm'      => esc_html__( 'Are you sure you want to delete this announcement bar?', 'hashbar' ),
			'list_untitled'            => esc_html__( 'Untitled', 'hashbar' ),
			'list_published'           => esc_html__( 'Published', 'hashbar' ),
			'list_draft'               => esc_html__( 'Draft', 'hashbar' ),
			'list_save_as_draft'       => esc_html__( 'Save as Draft', 'hashbar' ),
			'list_publish'             => esc_html__( 'Publish', 'hashbar' ),
			'list_all_status'          => esc_html__( 'All Bars', 'hashbar' ),
			'list_total_label'         => esc_html__( 'Total', 'hashbar' ),
			'list_bars_label'          => esc_html__( 'bars', 'hashbar' ),

			// Live Preview UI
			'preview_mode'             => esc_html__( 'Preview Mode', 'hashbar' ),
			'preview_exit'             => esc_html__( 'Exit Preview', 'hashbar' ),
			'preview_mobile'           => esc_html__( 'Mobile Preview', 'hashbar' ),
			'preview_desktop'          => esc_html__( 'Desktop Preview', 'hashbar' ),
			'preview_tablet'           => esc_html__( 'Tablet Preview', 'hashbar' ),
			'preview_countdown_ends'   => esc_html__( 'Countdown Ends', 'hashbar' ),
			'preview_copy_coupon'      => esc_html__( 'Copy Coupon Code', 'hashbar' ),
			'preview_coupon_copied'    => esc_html__( 'Coupon Copied!', 'hashbar' ),
			'preview_days'             => esc_html__( 'Days', 'hashbar' ),
			'preview_hours'            => esc_html__( 'Hours', 'hashbar' ),
			'preview_minutes'          => esc_html__( 'Minutes', 'hashbar' ),
			'preview_seconds'          => esc_html__( 'Seconds', 'hashbar' ),
			'preview_hidden'           => esc_html__( 'Hidden', 'hashbar' ),
			'preview_visible'          => esc_html__( 'Visible', 'hashbar' ),

			// Analytics UI (non-AB test analytics)
			'analytics_impressions_over_time' => esc_html__( 'Impressions Over Time', 'hashbar' ),
			'analytics_clicks_over_time'      => esc_html__( 'Clicks Over Time', 'hashbar' ),
			'analytics_conversion_rate'       => esc_html__( 'Conversion Rate Over Time', 'hashbar' ),
			'analytics_top_performing'        => esc_html__( 'Top Performing Bars', 'hashbar' ),
			'analytics_performance_comparison' => esc_html__( 'Performance Comparison', 'hashbar' ),
			'analytics_date_range'            => esc_html__( 'Date Range', 'hashbar' ),
			'analytics_last_7_days'           => esc_html__( 'Last 7 Days', 'hashbar' ),
			'analytics_last_30_days'          => esc_html__( 'Last 30 Days', 'hashbar' ),
			'analytics_last_90_days'          => esc_html__( 'Last 90 Days', 'hashbar' ),
			'analytics_custom_range'          => esc_html__( 'Custom Range', 'hashbar' ),
			'analytics_compare_with'          => esc_html__( 'Compare with', 'hashbar' ),
			'analytics_select_bar'            => esc_html__( 'Select Bar', 'hashbar' ),
			'analytics_total_impressions'     => esc_html__( 'Total Impressions', 'hashbar' ),
			'analytics_total_clicks'          => esc_html__( 'Total Clicks', 'hashbar' ),
			'analytics_average_ctr'           => esc_html__( 'Average CTR', 'hashbar' ),
			'analytics_unique_visitors'       => esc_html__( 'Unique Visitors', 'hashbar' ),
			'analytics_total_conversions'     => esc_html__( 'Total Conversions', 'hashbar' ),
			'analytics_average_position'      => esc_html__( 'Average Position', 'hashbar' ),

			// Editor UI
			'editor_save_changes'      => esc_html__( 'Save Changes', 'hashbar' ),
			'editor_discard_changes'   => esc_html__( 'Discard Changes', 'hashbar' ),
			'editor_preview'           => esc_html__( 'Preview', 'hashbar' ),
			'editor_live_preview'      => esc_html__( 'Live Preview', 'hashbar' ),
			'editor_back_to_list'      => esc_html__( 'Back to List', 'hashbar' ),

			// Common actions
			'edit'                     => esc_html__( 'Edit', 'hashbar' ),
			'duplicate'                => esc_html__( 'Duplicate', 'hashbar' ),
			'clone'                    => esc_html__( 'Clone', 'hashbar' ),
			'close'                    => esc_html__( 'Close', 'hashbar' ),
			'loading'                  => esc_html__( 'Loading...', 'hashbar' ),
			'success'                  => esc_html__( 'Success', 'hashbar' ),
			'error'                    => esc_html__( 'Error', 'hashbar' ),
			'warning'                  => esc_html__( 'Warning', 'hashbar' ),
			'confirm'                  => esc_html__( 'Confirm', 'hashbar' ),
			'yes'                      => esc_html__( 'Yes', 'hashbar' ),
			'no'                       => esc_html__( 'No', 'hashbar' ),
			'select_option'            => esc_html__( 'Select an option', 'hashbar' ),
			'search'                   => esc_html__( 'Search', 'hashbar' ),
			'filter'                   => esc_html__( 'Filter', 'hashbar' ),
			'refresh'                  => esc_html__( 'Refresh', 'hashbar' ),
			'retry'                    => esc_html__( 'Retry', 'hashbar' ),
			'no_results'               => esc_html__( 'No results found', 'hashbar' ),
			'view_details'             => esc_html__( 'View Details', 'hashbar' ),
			'select_all'               => esc_html__( 'Select All', 'hashbar' ),
			'bulk_delete'              => esc_html__( 'Bulk Delete', 'hashbar' ),
			'list_bar_name'            => esc_html__( 'Title', 'hashbar' ),
			'list_status'              => esc_html__( 'Status', 'hashbar' ),
			'list_date_created'        => esc_html__( 'Created', 'hashbar' ),
			'list_actions'             => esc_html__( 'Actions', 'hashbar' ),

			// Analytics Page UI
			'analytics_data'           => esc_html__( 'Analytics Data', 'hashbar' ),
			'analytics_description'    => esc_html__( 'Real-time analytics for this announcement bar. Data is collected from page views, button clicks, and user interactions.', 'hashbar' ),
			'analytics_announcement_bar' => esc_html__( 'Announcement Bar', 'hashbar' ),
			'analytics_date_range_label' => esc_html__( 'Date Range', 'hashbar' ),
			'analytics_granularity_label' => esc_html__( 'Granularity', 'hashbar' ),
			'analytics_daily'          => esc_html__( 'Daily', 'hashbar' ),
			'analytics_weekly'         => esc_html__( 'Weekly', 'hashbar' ),
			'analytics_monthly'        => esc_html__( 'Monthly', 'hashbar' ),
			'analytics_refresh'        => esc_html__( 'Refresh', 'hashbar' ),
			'analytics_export'         => esc_html__( 'Export', 'hashbar' ),
			'analytics_impressions'    => esc_html__( 'Impressions', 'hashbar' ),
			'analytics_clicks'         => esc_html__( 'Clicks', 'hashbar' ),
			'analytics_ctr_label'      => esc_html__( 'Click-Through Rate', 'hashbar' ),
			'analytics_conversions'    => esc_html__( 'Conversions', 'hashbar' ),
			'analytics_performance_over_time' => esc_html__( 'Performance Over Time', 'hashbar' ),
			'analytics_table_date'     => esc_html__( 'Date', 'hashbar' ),
			'analytics_table_views'    => esc_html__( 'Views', 'hashbar' ),
			'analytics_table_clicks'   => esc_html__( 'Clicks', 'hashbar' ),
			'analytics_table_ctr'      => esc_html__( 'CTR', 'hashbar' ),
			'analytics_table_conversions' => esc_html__( 'Conversions', 'hashbar' ),
			'analytics_device_distribution' => esc_html__( 'Device Distribution', 'hashbar' ),
			'analytics_table_device'   => esc_html__( 'Device', 'hashbar' ),
			'analytics_top_countries'  => esc_html__( 'Top Countries', 'hashbar' ),
			'analytics_page_performance' => esc_html__( 'Page Performance', 'hashbar' ),
			'analytics_table_page_url' => esc_html__( 'Page URL', 'hashbar' ),
			'analytics_table_type'     => esc_html__( 'Type', 'hashbar' ),
			'analytics_no_data'        => esc_html__( 'No analytics data available yet. Views and clicks will appear here as visitors interact with your announcement bars.', 'hashbar' ),

		// A/B Test Statistics UI
		'abtest_variant_performance'  => esc_html__( 'Variant Performance', 'hashbar' ),
		'abtest_ab_testing_results'   => esc_html__( 'AB Testing Results', 'hashbar' ),
		'abtest_variant'              => esc_html__( 'Variant', 'hashbar' ),
		'abtest_impressions'          => esc_html__( 'Impressions', 'hashbar' ),
		'abtest_clicks'               => esc_html__( 'Clicks', 'hashbar' ),
		'abtest_conversions'          => esc_html__( 'Conversions', 'hashbar' ),
		'abtest_ctr'                  => esc_html__( 'CTR', 'hashbar' ),
		'abtest_conversion_rate'      => esc_html__( 'Conversion Rate', 'hashbar' ),
		'abtest_control'              => esc_html__( 'Control', 'hashbar' ),
		'abtest_winner'               => esc_html__( 'Winner', 'hashbar' ),
		'abtest_total_visitors'       => esc_html__( 'Total Visitors', 'hashbar' ),
		'abtest_total_impressions'    => esc_html__( 'Total Impressions', 'hashbar' ),
		'abtest_total_clicks'         => esc_html__( 'Total Clicks', 'hashbar' ),
		'abtest_total_conversions'    => esc_html__( 'Total Conversions', 'hashbar' ),

		// A/B Test Editor UI
		'abtest_message_text'         => esc_html__( 'Message Text', 'hashbar' ),
		'abtest_enable_cta_button'    => esc_html__( 'Enable CTA Button', 'hashbar' ),
		'abtest_cta_button_text'      => esc_html__( 'CTA Button Text', 'hashbar' ),
		'abtest_cta_url'              => esc_html__( 'CTA URL', 'hashbar' ),
		'abtest_cta_target'           => esc_html__( 'CTA Target', 'hashbar' ),
		'abtest_same_window'          => esc_html__( 'Same Window', 'hashbar' ),
		'abtest_new_tab'              => esc_html__( 'New Tab', 'hashbar' ),
		'abtest_messages_cta'         => esc_html__( 'Messages & CTA', 'hashbar' ),
		'abtest_no_messages_configured' => esc_html__( 'No messages configured', 'hashbar' ),
		'abtest_add_another_message'  => esc_html__( '+ Add Another Message', 'hashbar' ),

		// A/B Test Design UI
		'abtest_colors'               => esc_html__( 'Colors', 'hashbar' ),
		'abtest_background_color'     => esc_html__( 'Background Color', 'hashbar' ),
		'abtest_text_color'           => esc_html__( 'Text Color', 'hashbar' ),

		// A/B Test Feature UI
		'abtest_countdown_timer'      => esc_html__( 'Countdown Timer', 'hashbar' ),
		'abtest_enable_countdown'     => esc_html__( 'Enable Countdown', 'hashbar' ),
		'abtest_countdown_style'      => esc_html__( 'Countdown Style', 'hashbar' ),
		'abtest_reset_countdown_settings' => esc_html__( 'Reset Countdown Settings', 'hashbar' ),
		'abtest_coupon_code'          => esc_html__( 'Coupon Code', 'hashbar' ),
		'abtest_enable_coupon'        => esc_html__( 'Enable Coupon', 'hashbar' ),
		'abtest_reset_coupon_settings' => esc_html__( 'Reset Coupon Settings', 'hashbar' ),
		'abtest_content'              => esc_html__( 'Content', 'hashbar' ),
		'abtest_design'               => esc_html__( 'Design', 'hashbar' ),
		'abtest_inherited'            => esc_html__( 'Inherited', 'hashbar' ),
		'abtest_reset'                => esc_html__( 'Reset', 'hashbar' ),
		'abtest_enabled'              => esc_html__( 'Enabled', 'hashbar' ),
		'abtest_disabled'             => esc_html__( 'Disabled', 'hashbar' ),
		);
	}

	/**
	 * Get video guide data
	 *
	 * @return array
	 */
	public static function get_video_guide() {
		return array(
			'label' => esc_html__( 'Video Guide', 'hashbar' ),
			'url'   => 'https://www.youtube.com/watch?v=9VUc5Is-9Uw',
		);
	}

	/**
	 * Get timezone options for selects
	 *
	 * @return array
	 */
	public static function get_timezone_options() {
		return array(
			'UTC'      => 'UTC (Coordinated Universal Time)',
			'EST'      => 'EST (UTC-5)',
			'CST'      => 'CST (UTC-6)',
			'MST'      => 'MST (UTC-7)',
			'PST'      => 'PST (UTC-8)',
			'GMT'      => 'GMT (UTC+0)',
			'CET'      => 'CET (UTC+1)',
			'EET'      => 'EET (UTC+2)',
			'IST'      => 'IST (UTC+5:30)',
			'JST'      => 'JST (UTC+9)',
			'AEST'     => 'AEST (UTC+10)',
		);
	}
}
