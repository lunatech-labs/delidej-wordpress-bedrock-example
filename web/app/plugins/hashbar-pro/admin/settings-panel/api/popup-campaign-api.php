<?php
/**
 * Popup Campaign REST API
 *
 * Provides REST API endpoints for managing popup campaigns.
 *
 * @package HashBar Pro
 * @since 2.0.0
 */

namespace Hashbar\Pro\API;

use WP_REST_Response;
use WP_REST_Request;
use WP_Query;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Popup Campaign REST API Class
 */
class PopupCampaign {

	/**
	 * Register REST API routes
	 */
	public static function register_routes() {
		// Get all popup campaigns
		register_rest_route( 'hashbar/v1', '/popup-campaigns', array(
			'methods'             => 'GET',
			'callback'            => array( self::class, 'get_popup_campaigns' ),
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
		) );

		// Create new popup campaign
		register_rest_route( 'hashbar/v1', '/popup-campaigns', array(
			'methods'             => 'POST',
			'callback'            => array( self::class, 'create_popup_campaign' ),
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
		) );

		// Get single popup campaign
		register_rest_route( 'hashbar/v1', '/popup-campaigns/(?P<id>\d+)', array(
			'methods'             => 'GET',
			'callback'            => array( self::class, 'get_popup_campaign' ),
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'id' => array(
					'validate_callback' => function( $param ) {
						return is_numeric( $param );
					},
				),
			),
		) );

		// Update popup campaign
		register_rest_route( 'hashbar/v1', '/popup-campaigns/(?P<id>\d+)', array(
			'methods'             => 'PUT',
			'callback'            => array( self::class, 'update_popup_campaign' ),
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'id' => array(
					'validate_callback' => function( $param ) {
						return is_numeric( $param );
					},
				),
			),
		) );

		// Delete popup campaign
		register_rest_route( 'hashbar/v1', '/popup-campaigns/(?P<id>\d+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( self::class, 'delete_popup_campaign' ),
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'id' => array(
					'validate_callback' => function( $param ) {
						return is_numeric( $param );
					},
				),
			),
		) );

		// Duplicate popup campaign
		register_rest_route( 'hashbar/v1', '/popup-campaigns/(?P<id>\d+)/duplicate', array(
			'methods'             => 'POST',
			'callback'            => array( self::class, 'duplicate_popup_campaign' ),
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
			'args'                => array(
				'id' => array(
					'validate_callback' => function( $param ) {
						return is_numeric( $param );
					},
				),
			),
		) );

		// Get popup campaign templates
		register_rest_route( 'hashbar/v1', '/popup-campaigns/templates', array(
			'methods'             => 'GET',
			'callback'            => array( self::class, 'get_popup_templates' ),
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
		) );

		// Get available forms for form integrations
		register_rest_route( 'hashbar/v1', '/popup-campaigns/available-forms', array(
			'methods'             => 'GET',
			'callback'            => array( self::class, 'get_available_forms' ),
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
		) );

		// Get Mailchimp lists
		register_rest_route( 'hashbar/v1', '/popup-campaigns/mailchimp-lists', array(
			'methods'             => 'POST',
			'callback'            => array( self::class, 'get_mailchimp_lists' ),
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
		) );

		// Get WooCommerce coupons
		register_rest_route( 'hashbar/v1', '/popup-campaigns/woo-coupons', array(
			'methods'             => 'GET',
			'callback'            => array( self::class, 'get_woo_coupons' ),
			'permission_callback' => function() {
				return current_user_can( 'manage_options' );
			},
		) );
	}

	/**
	 * Get all popup campaigns
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function get_popup_campaigns( $request ) {
		try {
			$args = array(
				'post_type'      => 'wphash_popup',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			);

			// Add search parameter
			$search = $request->get_param( 'search' );
			if ( ! empty( $search ) ) {
				$args['s'] = sanitize_text_field( $search );
			}

			// Add status filter
			$status = $request->get_param( 'status' );
			if ( ! empty( $status ) ) {
				$args['post_status'] = sanitize_text_field( $status );
			} else {
				$args['post_status'] = array( 'publish', 'draft' );
			}

			$query  = new WP_Query( $args );
			$popups = array();

			foreach ( $query->posts as $post ) {
				$popups[] = self::format_popup_campaign_data( $post );
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $popups,
					'total'   => count( $popups ),
				),
				200
			);

		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Get single popup campaign
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function get_popup_campaign( $request ) {
		try {
			$post_id = absint( $request['id'] );

			$post = get_post( $post_id );
			if ( ! $post || 'wphash_popup' !== $post->post_type ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => esc_html__( 'Popup campaign not found', 'hashbar' ),
					),
					404
				);
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => self::format_popup_campaign_data( $post ),
				),
				200
			);

		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Create new popup campaign
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function create_popup_campaign( $request ) {
		try {
			$title = sanitize_text_field( $request->get_param( 'title' ) );
			if ( empty( $title ) ) {
				$title = esc_html__( 'Untitled Popup', 'hashbar' );
			}

			$post_data = array(
				'post_title'  => $title,
				'post_type'   => 'wphash_popup',
				'post_status' => $request->has_param( 'status' ) ? sanitize_text_field( $request->get_param( 'status' ) ) : 'draft',
			);

			$post_id = wp_insert_post( $post_data );

			if ( is_wp_error( $post_id ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $post_id->get_error_message(),
					),
					500
				);
			}

			// Set popup status
			$post_status  = sanitize_text_field( $request->get_param( 'status' ) ) ?: 'draft';
			$popup_status = ( 'publish' === $post_status ) ? 'active' : 'inactive';
			update_post_meta( $post_id, '_wphash_popup_status', $popup_status );

			// Save meta data if provided
			$meta_data = $request->get_param( 'meta' );
			if ( is_array( $meta_data ) ) {
				self::save_meta_data( $post_id, $meta_data );
			}

			$post = get_post( $post_id );

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => esc_html__( 'Popup campaign created successfully', 'hashbar' ),
					'data'    => self::format_popup_campaign_data( $post ),
				),
				201
			);

		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Update popup campaign
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function update_popup_campaign( $request ) {
		try {
			$post_id = absint( $request['id'] );

			$post = get_post( $post_id );
			if ( ! $post || 'wphash_popup' !== $post->post_type ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => esc_html__( 'Popup campaign not found', 'hashbar' ),
					),
					404
				);
			}

			// Update post data
			$post_data = array(
				'ID' => $post_id,
			);

			if ( $request->has_param( 'title' ) ) {
				$post_data['post_title'] = sanitize_text_field( $request->get_param( 'title' ) );
			}

			if ( $request->has_param( 'status' ) ) {
				$post_data['post_status'] = sanitize_text_field( $request->get_param( 'status' ) );
			}

			$update_result = wp_update_post( $post_data );

			if ( is_wp_error( $update_result ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $update_result->get_error_message(),
					),
					500
				);
			}

			// Set popup status based on post_status
			if ( $request->has_param( 'status' ) ) {
				$post_status  = sanitize_text_field( $request->get_param( 'status' ) );
				$popup_status = ( 'publish' === $post_status ) ? 'active' : 'inactive';
				update_post_meta( $post_id, '_wphash_popup_status', $popup_status );
			}

			// Update meta data
			$meta_data = $request->get_param( 'meta' );
			if ( is_array( $meta_data ) ) {
				self::save_meta_data( $post_id, $meta_data );
			}

			$post = get_post( $post_id );

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => esc_html__( 'Popup campaign updated successfully', 'hashbar' ),
					'data'    => self::format_popup_campaign_data( $post ),
				),
				200
			);

		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Delete popup campaign
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function delete_popup_campaign( $request ) {
		try {
			$post_id = absint( $request['id'] );

			$post = get_post( $post_id );
			if ( ! $post || 'wphash_popup' !== $post->post_type ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => esc_html__( 'Popup campaign not found', 'hashbar' ),
					),
					404
				);
			}

			// Delete post and meta
			$result = wp_delete_post( $post_id, true );

			if ( ! $result ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => esc_html__( 'Failed to delete popup campaign', 'hashbar' ),
					),
					500
				);
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => esc_html__( 'Popup campaign deleted successfully', 'hashbar' ),
					'data'    => array( 'id' => $post_id ),
				),
				200
			);

		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Duplicate popup campaign
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function duplicate_popup_campaign( $request ) {
		try {
			$post_id = absint( $request['id'] );

			$post = get_post( $post_id );
			if ( ! $post || 'wphash_popup' !== $post->post_type ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => esc_html__( 'Popup campaign not found', 'hashbar' ),
					),
					404
				);
			}

			// Create the post duplicate
			$args = array(
				'post_author'  => $post->post_author,
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
				'post_status'  => 'draft',
				'post_title'   => $post->post_title . ' ' . esc_html__( '(Copy)', 'hashbar' ),
				'post_type'    => $post->post_type,
			);

			$new_post_id = wp_insert_post( $args );

			if ( is_wp_error( $new_post_id ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $new_post_id->get_error_message(),
					),
					500
				);
			}

			// Copy all post meta
			$post_metas = get_post_meta( $post_id );
			foreach ( $post_metas as $meta_key => $meta_values ) {
				foreach ( $meta_values as $meta_value ) {
					add_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value ) );
				}
			}

			// Set status to inactive for the duplicate
			update_post_meta( $new_post_id, '_wphash_popup_status', 'inactive' );

			$new_post = get_post( $new_post_id );

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => esc_html__( 'Popup campaign duplicated successfully', 'hashbar' ),
					'data'    => self::format_popup_campaign_data( $new_post ),
				),
				201
			);

		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Get popup templates
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function get_popup_templates( $request ) {
		try {
			$is_pro = defined( 'HASHBAR_WPNBP_VERSION' );

			$templates = array(
				// Free Templates
				array(
					'id'          => 'newsletter_classic',
					'name'        => esc_html__( 'Newsletter Classic', 'hashbar' ),
					'description' => esc_html__( 'Simple and clean email capture popup', 'hashbar' ),
					'isPro'       => false,
					'category'    => 'lead_capture',
					'type'        => 'center',
					'preview'     => 'newsletter-classic.jpg',
					'defaults'    => array(
						'_wphash_popup_position'    => 'center',
						'_wphash_popup_campaign_type' => 'lead_capture',
						'_wphash_popup_heading'     => esc_html__( 'Subscribe to our Newsletter', 'hashbar' ),
						'_wphash_popup_description' => esc_html__( 'Get exclusive updates and offers delivered straight to your inbox.', 'hashbar' ),
						'_wphash_popup_bg_color'    => '#ffffff',
						'_wphash_popup_btn_bg_color' => '#1890ff',
					),
				),
				array(
					'id'          => 'welcome_popup',
					'name'        => esc_html__( 'Welcome Popup', 'hashbar' ),
					'description' => esc_html__( 'Greet first-time visitors with a friendly message', 'hashbar' ),
					'isPro'       => false,
					'category'    => 'welcome',
					'type'        => 'center',
					'preview'     => 'welcome-popup.jpg',
					'defaults'    => array(
						'_wphash_popup_position'    => 'center',
						'_wphash_popup_campaign_type' => 'welcome',
						'_wphash_popup_heading'     => esc_html__( 'Welcome!', 'hashbar' ),
						'_wphash_popup_description' => esc_html__( 'Thanks for visiting our site. We\'re glad you\'re here!', 'hashbar' ),
						'_wphash_popup_bg_color'    => '#ffffff',
						'_wphash_popup_btn_bg_color' => '#52c41a',
					),
				),
				array(
					'id'          => 'basic_promotion',
					'name'        => esc_html__( 'Basic Promotion', 'hashbar' ),
					'description' => esc_html__( 'Announce sales and discount offers', 'hashbar' ),
					'isPro'       => false,
					'category'    => 'promotion',
					'type'        => 'center',
					'preview'     => 'basic-promotion.jpg',
					'defaults'    => array(
						'_wphash_popup_position'    => 'center',
						'_wphash_popup_campaign_type' => 'promotion',
						'_wphash_popup_heading'     => esc_html__( 'Special Offer!', 'hashbar' ),
						'_wphash_popup_description' => esc_html__( 'Get 20% off your first order. Limited time only!', 'hashbar' ),
						'_wphash_popup_bg_color'    => '#f5222d',
						'_wphash_popup_text_color'  => '#ffffff',
						'_wphash_popup_heading_color' => '#ffffff',
						'_wphash_popup_btn_bg_color' => '#ffffff',
						'_wphash_popup_btn_text_color' => '#f5222d',
					),
				),
				// Pro Templates
				array(
					'id'          => 'exit_intent_offer',
					'name'        => esc_html__( 'Exit Intent Offer', 'hashbar' ),
					'description' => esc_html__( 'Capture leaving visitors with a special offer', 'hashbar' ),
					'isPro'       => true,
					'category'    => 'exit_intent',
					'type'        => 'center',
					'preview'     => 'exit-intent-offer.jpg',
					'defaults'    => array(
						'_wphash_popup_position'     => 'center',
						'_wphash_popup_campaign_type' => 'exit_intent',
						'_wphash_popup_trigger_type' => 'exit_intent',
						'_wphash_popup_heading'      => esc_html__( 'Wait! Don\'t Leave Yet!', 'hashbar' ),
						'_wphash_popup_description'  => esc_html__( 'Get 15% off when you complete your purchase today.', 'hashbar' ),
					),
				),
				array(
					'id'          => 'slide_in_notification',
					'name'        => esc_html__( 'Slide-In Notification', 'hashbar' ),
					'description' => esc_html__( 'Subtle slide-in from bottom right', 'hashbar' ),
					'isPro'       => true,
					'category'    => 'announcement',
					'type'        => 'bottom_right',
					'preview'     => 'slide-in-notification.jpg',
					'defaults'    => array(
						'_wphash_popup_position'        => 'bottom_right',
						'_wphash_popup_campaign_type'   => 'announcement',
						'_wphash_popup_overlay_enabled' => false,
						'_wphash_popup_heading'         => esc_html__( 'New Feature!', 'hashbar' ),
						'_wphash_popup_width'           => 350,
					),
				),
				array(
					'id'          => 'fullscreen_takeover',
					'name'        => esc_html__( 'Fullscreen Takeover', 'hashbar' ),
					'description' => esc_html__( 'Full attention-grabbing overlay', 'hashbar' ),
					'isPro'       => true,
					'category'    => 'promotion',
					'type'        => 'fullscreen',
					'preview'     => 'fullscreen-takeover.jpg',
					'defaults'    => array(
						'_wphash_popup_position' => 'fullscreen',
						'_wphash_popup_campaign_type' => 'promotion',
						'_wphash_popup_heading' => esc_html__( 'Massive Sale!', 'hashbar' ),
						'_wphash_popup_bg_type' => 'gradient',
					),
				),
			);

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $templates,
					'isPro'   => $is_pro,
				),
				200
			);

		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Get available forms from form plugins
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function get_available_forms( $request ) {
		try {
			$forms = array();

			// Contact Form 7
			if ( class_exists( 'WPCF7' ) ) {
				$cf7_forms = get_posts(
					array(
						'post_type'      => 'wpcf7_contact_form',
						'posts_per_page' => -1,
						'orderby'        => 'title',
						'order'          => 'ASC',
					)
				);

				$forms['cf7'] = array(
					'name'      => 'Contact Form 7',
					'installed' => true,
					'forms'     => array(),
				);

				foreach ( $cf7_forms as $form ) {
					$forms['cf7']['forms'][] = array(
						'id'    => $form->ID,
						'title' => $form->post_title,
					);
				}
			} else {
				$forms['cf7'] = array(
					'name'      => 'Contact Form 7',
					'installed' => false,
					'forms'     => array(),
				);
			}

			// WPForms
			if ( function_exists( 'wpforms' ) ) {
				$wpforms_forms = wpforms()->form->get();

				$forms['wpforms'] = array(
					'name'      => 'WPForms',
					'installed' => true,
					'forms'     => array(),
				);

				if ( ! empty( $wpforms_forms ) ) {
					foreach ( $wpforms_forms as $form ) {
						$forms['wpforms']['forms'][] = array(
							'id'    => $form->ID,
							'title' => $form->post_title,
						);
					}
				}
			} else {
				$forms['wpforms'] = array(
					'name'      => 'WPForms',
					'installed' => false,
					'forms'     => array(),
				);
			}

			// Ninja Forms
			if ( class_exists( 'Ninja_Forms' ) ) {
				$ninja_forms = \Ninja_Forms()->form()->get_forms();

				$forms['ninja_forms'] = array(
					'name'      => 'Ninja Forms',
					'installed' => true,
					'forms'     => array(),
				);

				foreach ( $ninja_forms as $form ) {
					$forms['ninja_forms']['forms'][] = array(
						'id'    => $form->get_id(),
						'title' => $form->get_setting( 'title' ),
					);
				}
			} else {
				$forms['ninja_forms'] = array(
					'name'      => 'Ninja Forms',
					'installed' => false,
					'forms'     => array(),
				);
			}

			// Gravity Forms
			if ( class_exists( 'GFAPI' ) ) {
				$gravity_forms = \GFAPI::get_forms();

				$forms['gravity_forms'] = array(
					'name'      => 'Gravity Forms',
					'installed' => true,
					'forms'     => array(),
				);

				foreach ( $gravity_forms as $form ) {
					$forms['gravity_forms']['forms'][] = array(
						'id'    => $form['id'],
						'title' => $form['title'],
					);
				}
			} else {
				$forms['gravity_forms'] = array(
					'name'      => 'Gravity Forms',
					'installed' => false,
					'forms'     => array(),
				);
			}

			// Fluent Forms
			if ( defined( 'FLUENTFORM' ) ) {
				global $wpdb;
				$table         = $wpdb->prefix . 'fluentform_forms';
				$fluent_forms  = $wpdb->get_results( "SELECT id, title FROM {$table} WHERE status = 'published'" ); // phpcs:ignore

				$forms['fluent_forms'] = array(
					'name'      => 'Fluent Forms',
					'installed' => true,
					'forms'     => array(),
				);

				foreach ( $fluent_forms as $form ) {
					$forms['fluent_forms']['forms'][] = array(
						'id'    => $form->id,
						'title' => $form->title,
					);
				}
			} else {
				$forms['fluent_forms'] = array(
					'name'      => 'Fluent Forms',
					'installed' => false,
					'forms'     => array(),
				);
			}

			// HT Contact Form
			if ( class_exists( 'HT_FORM_BUILDER' ) || defined( 'HTCONTACTFORM_VERSION' ) ) {
				$ht_forms = get_posts(
					array(
						'post_type'      => 'ht_form',
						'posts_per_page' => -1,
						'post_status'    => 'publish',
						'orderby'        => 'title',
						'order'          => 'ASC',
					)
				);

				$forms['ht_form'] = array(
					'name'      => 'HT Contact Form',
					'installed' => true,
					'forms'     => array(),
				);

				foreach ( $ht_forms as $form ) {
					$forms['ht_form']['forms'][] = array(
						'id'    => $form->ID,
						'title' => $form->post_title,
					);
				}
			} else {
				$forms['ht_form'] = array(
					'name'      => 'HT Contact Form',
					'installed' => false,
					'forms'     => array(),
				);
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $forms,
				),
				200
			);

		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Get Mailchimp lists
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public static function get_mailchimp_lists( $request ) {
		try {
			$api_key = sanitize_text_field( $request->get_param( 'api_key' ) );

			if ( empty( $api_key ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => esc_html__( 'API key is required', 'hashbar' ),
					),
					400
				);
			}

			// Extract data center from API key
			$dc = '';
			if ( strpos( $api_key, '-' ) !== false ) {
				$dc = explode( '-', $api_key )[1];
			}

			if ( empty( $dc ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => esc_html__( 'Invalid API key format', 'hashbar' ),
					),
					400
				);
			}

			$url = "https://{$dc}.api.mailchimp.com/3.0/lists";

			$response = wp_remote_get(
				$url,
				array(
					'headers' => array(
						'Authorization' => 'Basic ' . base64_encode( 'anystring:' . $api_key ),
						'Content-Type'  => 'application/json',
					),
					'timeout' => 15,
				)
			);

			if ( is_wp_error( $response ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $response->get_error_message(),
					),
					500
				);
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( isset( $body['status'] ) && $body['status'] >= 400 ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => $body['detail'] ?? esc_html__( 'Failed to fetch lists', 'hashbar' ),
					),
					400
				);
			}

			$lists = array();
			if ( isset( $body['lists'] ) ) {
				foreach ( $body['lists'] as $list ) {
					$lists[] = array(
						'id'           => $list['id'],
						'name'         => $list['name'],
						'member_count' => $list['stats']['member_count'] ?? 0,
					);
				}
			}

			return new WP_REST_Response(
				array(
					'success' => true,
					'data'    => $lists,
				),
				200
			);

		} catch ( Exception $e ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Save meta data for a popup campaign
	 *
	 * @param int   $post_id Post ID.
	 * @param array $meta_data Meta data array.
	 */
	private static function save_meta_data( $post_id, $meta_data ) {
		$array_fields = array(
			'_wphash_popup_target_page_ids',
			'_wphash_popup_exclude_page_ids',
			'_wphash_popup_target_devices',
			'_wphash_popup_form_fields',
			'_wphash_popup_spinwheel_segments',
			'_wphash_popup_image',
			'_wphash_popup_bg_image',
			'_wphash_popup_padding',
			'_wphash_popup_image_border_radius',
			'_wphash_popup_content_padding',
			'_wphash_popup_content_border_radius',
			'_wphash_popup_schedule_days',
			'_wphash_popup_ab_variants',
		);

		foreach ( $meta_data as $key => $value ) {
			// Handle boolean values
			if ( is_bool( $value ) ) {
				$value = $value ? '1' : '0';
			} elseif ( is_array( $value ) && in_array( $key, $array_fields, true ) ) {
				// Keep arrays as-is for WordPress to serialize
				$value = $value;
			}

			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Format popup campaign data for API response
	 *
	 * @param WP_Post $post Post object.
	 * @return array Formatted popup campaign data.
	 */
	public static function format_popup_campaign_data( $post ) {
		if ( ! $post || 'wphash_popup' !== $post->post_type ) {
			return array();
		}

		$meta_data = get_post_meta( $post->ID );

		// Boolean fields that need conversion
		$boolean_fields = array(
			'_wphash_popup_overlay_enabled',
			'_wphash_popup_overlay_close',
			'_wphash_popup_close_enabled',
			'_wphash_popup_esc_close',
			'_wphash_popup_cta_enabled',
			'_wphash_popup_cta_close_on_click',
			'_wphash_popup_secondary_enabled',
			'_wphash_popup_form_enabled',
			'_wphash_popup_mailchimp_enabled',
			'_wphash_popup_mailchimp_double_optin',
			'_wphash_popup_exit_mobile_enabled',
			'_wphash_popup_target_new_visitors',
			'_wphash_popup_target_returning_visitors',
			'_wphash_popup_target_referrer_enabled',
			'_wphash_popup_spinwheel_enabled',
			'_wphash_popup_countdown_enabled',
			'_wphash_popup_countdown_show_days',
			'_wphash_popup_countdown_show_hours',
			'_wphash_popup_countdown_show_minutes',
			'_wphash_popup_countdown_show_seconds',
			'_wphash_popup_coupon_enabled',
			'_wphash_popup_coupon_copy_button',
			'_wphash_popup_coupon_autocopy_on_click',
			'_wphash_popup_coupon_woo_integration',
			'_wphash_popup_coupon_auto_apply',
			'_wphash_popup_ab_enabled',
		);

		// Array fields that need special handling
		$array_fields = array(
			'_wphash_popup_target_page_ids',
			'_wphash_popup_exclude_page_ids',
			'_wphash_popup_target_devices',
			'_wphash_popup_form_fields',
			'_wphash_popup_spinwheel_segments',
			'_wphash_popup_content_padding',
			'_wphash_popup_content_border_radius',
			'_wphash_popup_schedule_days',
			'_wphash_popup_ab_variants',
		);

		// Object fields
		$object_fields = array(
			'_wphash_popup_image',
			'_wphash_popup_bg_image',
			'_wphash_popup_padding',
			'_wphash_popup_image_border_radius',
			'_wphash_popup_content_padding',
			'_wphash_popup_content_border_radius',
		);

		// Integer fields
		$integer_fields = array(
			'_wphash_popup_cf7_form_id',
			'_wphash_popup_wpforms_form_id',
			'_wphash_popup_ninja_form_id',
			'_wphash_popup_gravity_form_id',
			'_wphash_popup_fluent_form_id',
			'_wphash_popup_width',
			'_wphash_popup_max_width',
			'_wphash_popup_image_width',
			'_wphash_popup_content_gap',
			'_wphash_popup_heading_size',
			'_wphash_popup_text_size',
			'_wphash_popup_border_radius',
			'_wphash_popup_border_width',
			'_wphash_popup_btn_border_radius',
			'_wphash_popup_btn_font_size',
			'_wphash_popup_close_size',
			'_wphash_popup_trigger_delay',
			'_wphash_popup_trigger_scroll_percent',
			'_wphash_popup_trigger_inactivity_time',
			'_wphash_popup_trigger_page_views_count',
			'_wphash_popup_frequency_days',
			'_wphash_popup_frequency_times',
			'_wphash_popup_after_close_days',
			'_wphash_popup_after_convert_days',
			'_wphash_popup_animation_duration',
			'_wphash_popup_animation_delay',
			'_wphash_popup_form_close_delay',
		);

		$meta = array();
		foreach ( $meta_data as $key => $values ) {
			$value = ! empty( $values ) ? $values[0] : '';

			if ( in_array( $key, $boolean_fields, true ) ) {
				$meta[ $key ] = ! empty( $value ) && '0' !== $value;
			} elseif ( in_array( $key, $array_fields, true ) ) {
				if ( is_array( $value ) ) {
					$meta[ $key ] = $value;
				} elseif ( is_string( $value ) && ! empty( $value ) ) {
					$decoded = json_decode( $value, true );
					if ( is_array( $decoded ) ) {
						$meta[ $key ] = $decoded;
					} elseif ( 0 === strpos( $value, 'a:' ) ) {
						$unserialized  = maybe_unserialize( $value );
						$meta[ $key ] = is_array( $unserialized ) ? $unserialized : array();
					} else {
						$meta[ $key ] = array();
					}
				} else {
					$meta[ $key ] = array();
				}
			} elseif ( in_array( $key, $object_fields, true ) ) {
				if ( is_array( $value ) ) {
					$meta[ $key ] = $value;
				} elseif ( is_string( $value ) && ! empty( $value ) ) {
					$decoded = json_decode( $value, true );
					if ( is_array( $decoded ) ) {
						$meta[ $key ] = $decoded;
					} elseif ( 0 === strpos( $value, 'a:' ) ) {
						$unserialized  = maybe_unserialize( $value );
						$meta[ $key ] = is_array( $unserialized ) ? $unserialized : array();
					} else {
						$meta[ $key ] = array();
					}
				} else {
					$meta[ $key ] = array();
				}
			} elseif ( in_array( $key, $integer_fields, true ) ) {
				$meta[ $key ] = (int) $value;
			} else {
				$meta[ $key ] = $value;
			}
		}

		return array(
			'id'       => $post->ID,
			'title'    => $post->post_title,
			'status'   => $post->post_status,
			'date'     => $post->post_date,
			'modified' => $post->post_modified,
			'meta'     => $meta,
			'editUrl'  => admin_url( 'admin.php?page=hashbar#/popup-campaign/' . $post->ID ),
		);
	}

	/**
	 * Get WooCommerce coupons
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public static function get_woo_coupons( $request ) {
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'WooCommerce is not installed or active.', 'hashbar' ),
					'coupons' => array(),
				),
				200
			);
		}

		$coupons = array();

		// Query WooCommerce coupons
		$args = array(
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);

		$coupon_posts = get_posts( $args );

		foreach ( $coupon_posts as $coupon_post ) {
			$coupon_code = $coupon_post->post_title;
			$discount_type = get_post_meta( $coupon_post->ID, 'discount_type', true );
			$coupon_amount = get_post_meta( $coupon_post->ID, 'coupon_amount', true );

			// Format discount type for display
			$type_label = '';
			$currency_symbol = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );

			switch ( $discount_type ) {
				case 'percent':
					$type_label = $coupon_amount . '% ' . __( 'off', 'hashbar' );
					break;
				case 'fixed_cart':
					$type_label = $currency_symbol . $coupon_amount . ' ' . __( 'off cart', 'hashbar' );
					break;
				case 'fixed_product':
					$type_label = $currency_symbol . $coupon_amount . ' ' . __( 'off product', 'hashbar' );
					break;
				default:
					$type_label = $discount_type;
			}

			$coupons[] = array(
				'id'            => $coupon_post->ID,
				'code'          => $coupon_code,
				'discount_type' => $discount_type,
				'amount'        => $coupon_amount,
				'label'         => $coupon_code . ( $type_label ? ' (' . $type_label . ')' : '' ),
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'coupons' => $coupons,
			),
			200
		);
	}
}

add_action( 'rest_api_init', array( __NAMESPACE__ . '\PopupCampaign', 'register_routes' ) );
