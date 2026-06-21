<?php
namespace Hashbar\Pro\API;

use WP_REST_Response;
use WP_REST_Request;
use WP_Query;
use Exception;

if (!class_exists('WP_REST_Response')) {
	require_once ABSPATH . 'wp-includes/rest-api/class-wp-rest-response.php';
	require_once ABSPATH . 'wp-includes/rest-api.php';
}

/**
 * Register custom REST API endpoints for Announcement Bars
 *
 * @since 1.8.0
 */
class AnnouncementBar {

	public static function register_routes() {
		// Get all announcement bars
		register_rest_route('hashbar/v1', '/announcement-bars', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'get_announcement_bars' ],
			'permission_callback' => function() {
				return current_user_can('manage_options');
			}
		]);

		// Create new announcement bar
		register_rest_route('hashbar/v1', '/announcement-bars', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'create_announcement_bar' ],
			'permission_callback' => function() {
				return current_user_can('manage_options');
			}
		]);

		// Get single announcement bar
		register_rest_route('hashbar/v1', '/announcement-bars/(?P<id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'get_announcement_bar' ],
			'permission_callback' => function() {
				return current_user_can('manage_options');
			},
			'args'                => [
				'id' => [
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				]
			]
		]);

		// Update announcement bar
		register_rest_route('hashbar/v1', '/announcement-bars/(?P<id>\d+)', [
			'methods'             => 'PUT',
			'callback'            => [ self::class, 'update_announcement_bar' ],
			'permission_callback' => function() {
				return current_user_can('manage_options');
			},
			'args'                => [
				'id' => [
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				]
			]
		]);

		// Delete announcement bar
		register_rest_route('hashbar/v1', '/announcement-bars/(?P<id>\d+)', [
			'methods'             => 'DELETE',
			'callback'            => [ self::class, 'delete_announcement_bar' ],
			'permission_callback' => function() {
				return current_user_can('manage_options');
			},
			'args'                => [
				'id' => [
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				]
			]
		]);

		// Duplicate announcement bar
		register_rest_route('hashbar/v1', '/announcement-bars/(?P<id>\d+)/duplicate', [
			'methods'             => 'POST',
			'callback'            => [ self::class, 'duplicate_announcement_bar' ],
			'permission_callback' => function() {
				return current_user_can('manage_options');
			},
			'args'                => [
				'id' => [
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				]
			]
		]);

		// Get announcement bar templates
		register_rest_route('hashbar/v1', '/announcement-bars/templates', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'get_announcement_bar_templates' ],
			'permission_callback' => function() {
				return current_user_can('manage_options');
			}
		]);

		// Get pages and posts for targeting
		register_rest_route('hashbar/v1', '/pages-posts', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'get_pages_posts' ],
			'permission_callback' => function() {
				return current_user_can('manage_options');
			}
		]);

		// Debug geographic targeting (WP_DEBUG only)
		register_rest_route('hashbar/v1', '/announcement-bars/(?P<id>\d+)/debug-geo', [
			'methods'             => 'GET',
			'callback'            => [ self::class, 'debug_geographic_targeting' ],
			'permission_callback' => function() {
				return current_user_can('manage_options');
			},
			'args'                => [
				'id' => [
					'validate_callback' => function($param) {
						return is_numeric($param);
					}
				]
			]
		]);
	}

	/**
	 * Get all announcement bars
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response Response object
	 */
	public static function get_announcement_bars($request) {
		try {
			$args = [
				'post_type'      => 'wphash_announcement',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
			];

			// Add search parameter
			$search = $request->get_param('search');
			if (!empty($search)) {
				$args['s'] = sanitize_text_field($search);
			}

			// Add status filter
			$status = $request->get_param('status');
			if (!empty($status)) {
				$args['post_status'] = sanitize_text_field($status);
			} else {
				$args['post_status'] = ['publish', 'draft'];
			}

			$query = new WP_Query($args);
			$bars  = [];

			foreach ($query->posts as $post) {
				$bars[] = self::format_announcement_bar_data($post);
			}

			return new WP_REST_Response([
				'success' => true,
				'data'    => $bars,
				'total'   => count($bars),
			], 200);

		} catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Get single announcement bar
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response Response object
	 */
	public static function get_announcement_bar($request) {
		try {
			$post_id = absint($request['id']);

			$post = get_post($post_id);
			if (!$post || $post->post_type !== 'wphash_announcement') {
				return new WP_REST_Response([
					'success' => false,
					'message' => esc_html__('Announcement bar not found', 'hashbar'),
				], 404);
			}

			return new WP_REST_Response([
				'success' => true,
				'data'    => self::format_announcement_bar_data($post),
			], 200);

		} catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Create new announcement bar
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response Response object
	 */
	public static function create_announcement_bar($request) {
		try {
			$title = sanitize_text_field($request->get_param('title'));
			if (empty($title)) {
				return new WP_REST_Response([
					'success' => false,
					'message' => esc_html__('Title is required', 'hashbar'),
				], 400);
			}

			$post_data = [
				'post_title'  => $title,
				'post_type'   => 'wphash_announcement',
				'post_status' => $request->has_param('status') ? sanitize_text_field($request->get_param('status')) : 'draft',
			];

			$post_id = wp_insert_post($post_data);

			if (is_wp_error($post_id)) {
				return new WP_REST_Response([
					'success' => false,
					'message' => $post_id->get_error_message(),
				], 500);
			}

			// Set announcement bar status (active if published, inactive if draft)
			$post_status = sanitize_text_field($request->get_param('status')) ?: 'draft';
			$bar_status = ($post_status === 'publish') ? 'active' : 'inactive';
			update_post_meta($post_id, '_wphash_ab_status', $bar_status);

			// Save meta data if provided
			$meta_data = $request->get_param('meta');
			if (is_array($meta_data)) {
				foreach ($meta_data as $key => $value) {
					// Handle boolean values
					if (is_bool($value)) {
						$value = $value ? '1' : '0';
					}
					// Handle complex arrays - JSON encode them for storage
					elseif (is_array($value) && in_array($key, ['_wphash_ab_target_page_ids', '_wphash_ab_exclude_page_ids', '_wphash_ab_target_devices', '_wphash_ab_target_countries', '_wphash_ab_countdown_reset_days', '_wphash_ab_bg_image'], true)) {
						$value = wp_json_encode($value, JSON_UNESCAPED_UNICODE);
					}
					update_post_meta($post_id, $key, $value);
				}
			}

			$post = get_post($post_id);

			return new WP_REST_Response([
				'success' => true,
				'message' => esc_html__('Announcement bar created successfully', 'hashbar'),
				'data'    => self::format_announcement_bar_data($post),
			], 201);

		} catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Update announcement bar
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response Response object
	 */
	public static function update_announcement_bar($request) {
		try {
			$post_id = absint($request['id']);

			$post = get_post($post_id);
			if (!$post || $post->post_type !== 'wphash_announcement') {
				return new WP_REST_Response([
					'success' => false,
					'message' => esc_html__('Announcement bar not found', 'hashbar'),
				], 404);
			}

			// Update post data
			$post_data = [
				'ID' => $post_id,
			];

			if ($request->has_param('title')) {
				$post_data['post_title'] = sanitize_text_field($request->get_param('title'));
			}

			if ($request->has_param('status')) {
				$post_data['post_status'] = sanitize_text_field($request->get_param('status'));
			}

			$update_result = wp_update_post($post_data);

			if (is_wp_error($update_result)) {
				return new WP_REST_Response([
					'success' => false,
					'message' => $update_result->get_error_message(),
				], 500);
			}

			// Set announcement bar status based on post_status
			if ($request->has_param('status')) {
				$post_status = sanitize_text_field($request->get_param('status'));
				$bar_status = ($post_status === 'publish') ? 'active' : 'inactive';
				update_post_meta($post_id, '_wphash_ab_status', $bar_status);
			}

			// Update meta data
			$meta_data = $request->get_param('meta');
			if (is_array($meta_data)) {
				foreach ($meta_data as $key => $value) {
					// Handle boolean values
					if (is_bool($value)) {
						$value = $value ? '1' : '0';
					}
					// Handle complex arrays - JSON encode them for storage
					elseif (is_array($value) && in_array($key, ['_wphash_ab_target_page_ids', '_wphash_ab_exclude_page_ids', '_wphash_ab_target_devices', '_wphash_ab_target_countries', '_wphash_ab_countdown_reset_days', '_wphash_ab_bg_image'], true)) {
						$value = wp_json_encode($value, JSON_UNESCAPED_UNICODE);
					}
					update_post_meta($post_id, $key, $value);
				}
			}

			$post = get_post($post_id);

			return new WP_REST_Response([
				'success' => true,
				'message' => esc_html__('Announcement bar updated successfully', 'hashbar'),
				'data'    => self::format_announcement_bar_data($post),
			], 200);

		} catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Delete announcement bar
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response Response object
	 */
	public static function delete_announcement_bar($request) {
		try {
			$post_id = absint($request['id']);

			$post = get_post($post_id);
			if (!$post || $post->post_type !== 'wphash_announcement') {
				return new WP_REST_Response([
					'success' => false,
					'message' => esc_html__('Announcement bar not found', 'hashbar'),
				], 404);
			}

			// Delete post and meta
			$result = wp_delete_post($post_id, true);

			if (!$result) {
				return new WP_REST_Response([
					'success' => false,
					'message' => esc_html__('Failed to delete announcement bar', 'hashbar'),
				], 500);
			}

			return new WP_REST_Response([
				'success' => true,
				'message' => esc_html__('Announcement bar deleted successfully', 'hashbar'),
				'data'    => ['id' => $post_id],
			], 200);

		} catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Duplicate announcement bar
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response Response object
	 */
	public static function duplicate_announcement_bar($request) {
		try {
			$post_id = absint($request['id']);

			$post = get_post($post_id);
			if (!$post || $post->post_type !== 'wphash_announcement') {
				return new WP_REST_Response([
					'success' => false,
					'message' => esc_html__('Announcement bar not found', 'hashbar'),
				], 404);
			}

			// Create the post duplicate
			$args = [
				'post_author'  => $post->post_author,
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
				'post_status'  => 'draft',
				'post_title'   => $post->post_title . ' ' . esc_html__('(Copy)', 'hashbar'),
				'post_type'    => $post->post_type,
			];

			$new_post_id = wp_insert_post($args);

			if (is_wp_error($new_post_id)) {
				return new WP_REST_Response([
					'success' => false,
					'message' => $new_post_id->get_error_message(),
				], 500);
			}

			// Copy all post meta
			$post_metas = get_post_meta($post_id);
			foreach ($post_metas as $meta_key => $meta_values) {
				foreach ($meta_values as $meta_value) {
					add_post_meta($new_post_id, $meta_key, $meta_value);
				}
			}

			$new_post = get_post($new_post_id);

			return new WP_REST_Response([
				'success' => true,
				'message' => esc_html__('Announcement bar duplicated successfully', 'hashbar'),
				'data'    => self::format_announcement_bar_data($new_post),
			], 201);

		} catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Get announcement bar templates
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response Response object
	 */
	public static function get_announcement_bar_templates($request) {
		try {
			$templates = [
				[
					'id'       => 'classic',
					'name'     => esc_html__('Classic', 'hashbar'),
					'icon'     => 'image/classic-template.png',
					'pro'      => false,
					'defaults' => [
						'_wphash_ab_bg_color'   => '#000000',
						'_wphash_ab_text_color' => '#ffffff',
						'_wphash_ab_font_size'  => 16,
						'_wphash_ab_height'     => 60,
					]
				],
				[
					'id'       => 'sale-alert',
					'name'     => esc_html__('Sale Alert', 'hashbar'),
					'icon'     => 'image/sale-template.png',
					'pro'      => false,
					'defaults' => [
						'_wphash_ab_bg_color'   => '#ff6347',
						'_wphash_ab_text_color' => '#ffffff',
						'_wphash_ab_font_size'  => 18,
						'_wphash_ab_font_weight' => 700,
						'_wphash_ab_height'     => 65,
					]
				],
				[
					'id'       => 'free-shipping',
					'name'     => esc_html__('Free Shipping', 'hashbar'),
					'icon'     => 'image/shipping-template.png',
					'pro'      => false,
					'defaults' => [
						'_wphash_ab_bg_color'   => '#28a745',
						'_wphash_ab_text_color' => '#ffffff',
						'_wphash_ab_font_size'  => 16,
						'_wphash_ab_height'     => 60,
					]
				],
				[
					'id'       => 'info-banner',
					'name'     => esc_html__('Info Banner', 'hashbar'),
					'icon'     => 'image/info-template.png',
					'pro'      => false,
					'defaults' => [
						'_wphash_ab_bg_color'   => '#007bff',
						'_wphash_ab_text_color' => '#ffffff',
						'_wphash_ab_font_size'  => 16,
						'_wphash_ab_height'     => 60,
					]
				],
				[
					'id'       => 'countdown-sale',
					'name'     => esc_html__('Countdown Sale', 'hashbar'),
					'icon'     => 'image/countdown-template.png',
					'pro'      => true,
					'defaults' => [
						'_wphash_ab_bg_color'   => '#ff6347',
						'_wphash_ab_text_color' => '#ffffff',
						'_wphash_ab_countdown_enabled' => true,
						'_wphash_ab_countdown_type'    => 'fixed',
					]
				],
				[
					'id'       => 'coupon-display',
					'name'     => esc_html__('Coupon Display', 'hashbar'),
					'icon'     => 'image/coupon-template.png',
					'pro'      => true,
					'defaults' => [
						'_wphash_ab_bg_color'   => '#ffc107',
						'_wphash_ab_text_color' => '#000000',
						'_wphash_ab_coupon_enabled' => true,
					]
				]
			];

			return new WP_REST_Response([
				'success' => true,
				'data'    => $templates,
			], 200);

		} catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500);
		}
	}

	/**
	 * Format announcement bar data for API response
	 *
	 * @param WP_Post $post Post object
	 * @return array Formatted announcement bar data
	 */
	public static function format_announcement_bar_data($post) {
		if (!$post || $post->post_type !== 'wphash_announcement') {
			return [];
		}

		$meta_data = get_post_meta($post->ID);

		// Flatten meta data and convert boolean-like values back to proper format
		$meta = [];
		foreach ($meta_data as $key => $values) {
			$value = !empty($values) ? $values[0] : '';

			// Convert stored string values back to proper types for React
			// Check if this is a boolean field (ends with _enabled, _sticky, or customer/behavioral targeting fields)
			if (in_array($key, [
				'_wphash_ab_sticky',
				'_wphash_ab_close_enabled',
				'_wphash_ab_reopen_enabled',
				'_wphash_ab_cookie_use_custom',
				'_wphash_ab_cta_enabled',
				'_wphash_ab_countdown_enabled',
				'_wphash_ab_countdown_show_days',
				'_wphash_ab_countdown_show_hours',
				'_wphash_ab_countdown_show_minutes',
				'_wphash_ab_countdown_show_seconds',
				'_wphash_ab_coupon_enabled',
				'_wphash_ab_coupon_show_copy_button',
				'_wphash_ab_coupon_auto_copy',
				'_wphash_ab_coupon_autocopy_on_click',
				'_wphash_ab_coupon_autoapply',
				'_wphash_ab_schedule_enabled',
				'_wphash_ab_schedule_recurring',
				'_wphash_ab_schedule_time_targeting',
				'_wphash_ab_test_enabled',
				'_wphash_ab_enable_customer_segmentation',
				'_wphash_ab_target_logged_in_customers',
				'_wphash_ab_target_guest_visitors',
				'_wphash_ab_show_after_time_on_site',
				'_wphash_ab_message_rotation_enabled'
			], true)) {
				// Convert '1' to true, empty string or '0' to false
				$meta[$key] = !empty($value) && $value !== '0' ? true : false;
			} else if (in_array($key, ['_wphash_ab_target_devices', '_wphash_ab_target_countries', '_wphash_ab_target_page_ids', '_wphash_ab_exclude_page_ids', '_wphash_ab_countdown_reset_days', '_wphash_ab_schedule_recurring_days'], true)) {
				// Unserialize array data (handle JSON, PHP serialized, and comma-separated formats)
				if (is_array($value)) {
					// Already an array
					$meta[$key] = $value;
				} else if (is_string($value) && !empty($value)) {
					// Try to parse as JSON first (new format)
					$decoded = json_decode($value, true);
					if (is_array($decoded)) {
						$meta[$key] = $decoded;
					} else if (strpos($value, 'a:') === 0) {
						// This is PHP serialized data - unserialize it (legacy format)
						$unserialized = unserialize($value);
						$meta[$key] = is_array($unserialized) ? $unserialized : [];
					} else {
						// Try to parse as comma-separated (fallback)
						$meta[$key] = array_filter(array_map('trim', explode(',', $value)));
					}
				} else {
					// Empty or unknown format
					$meta[$key] = [];
				}
			} else if (in_array($key, ['_wphash_ab_minimum_time_on_site', '_wphash_ab_message_rotation_interval'], true)) {
				// Convert to integer
				$meta[$key] = (int) $value;
			} else if ($key === '_wphash_ab_messages') {
				// Handle messages array (JSON or serialized)
				if (is_string($value) && !empty($value)) {
					// Try to parse as JSON first
					$decoded = json_decode($value, true);
					if (is_array($decoded)) {
						$meta[$key] = $decoded;
					} else if (strpos($value, 'a:') === 0) {
						// PHP serialized array
						$unserialized = unserialize($value);
						$meta[$key] = is_array($unserialized) ? $unserialized : [];
					} else {
						// Fallback
						$meta[$key] = [];
					}
				} else if (is_array($value)) {
					$meta[$key] = $value;
				} else {
					$meta[$key] = [];
				}
			} else if ($key === '_wphash_ab_padding') {
				// Handle padding - convert to object format {top, right, bottom, left}
				if (is_string($value)) {
					// Try to parse as JSON first
					$decoded = json_decode($value, true);
					if (is_array($decoded)) {
						$meta[$key] = $decoded;
					} else if (strpos($value, 'a:') === 0) {
						// PHP serialized array
						$unserialized = unserialize($value);
						$meta[$key] = is_array($unserialized) ? $unserialized : ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10];
					} else {
						$meta[$key] = ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10];
					}
				} else if (is_array($value)) {
					// Already an array - ensure it has the right structure
					if (isset($value['top']) || isset($value['right']) || isset($value['bottom']) || isset($value['left'])) {
						// Object format
						$meta[$key] = $value;
					} else if (isset($value[0])) {
						// Numeric array format [top, right, bottom, left]
						$meta[$key] = [
							'top'    => isset($value[0]) ? (int)$value[0] : 10,
							'right'  => isset($value[1]) ? (int)$value[1] : 10,
							'bottom' => isset($value[2]) ? (int)$value[2] : 10,
							'left'   => isset($value[3]) ? (int)$value[3] : 10
						];
					} else {
						$meta[$key] = ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10];
					}
				} else {
					$meta[$key] = ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10];
				}
			} else if ($key === '_wphash_ab_test_variants') {
				// Handle test variants array (JSON or serialized)
				if (is_array($value)) {
					// Already an array
					$meta[$key] = $value;
				} else if (is_string($value) && !empty($value)) {
					// Try to parse as JSON first
					$decoded = json_decode($value, true);
					if (is_array($decoded)) {
						$meta[$key] = $decoded;
					} else if (strpos($value, 'a:') === 0) {
						// PHP serialized array
						$unserialized = unserialize($value);
						$meta[$key] = is_array($unserialized) ? $unserialized : [];
					} else {
						// Fallback
						$meta[$key] = [];
					}
				} else {
					$meta[$key] = [];
				}
			} else if ($key === '_wphash_ab_bg_image') {
				// Handle background image object (JSON or serialized)
				if (is_array($value)) {
					// Already an array
					$meta[$key] = $value;
				} else if (is_string($value) && !empty($value)) {
					// Try to parse as JSON first
					$decoded = json_decode($value, true);
					if (is_array($decoded)) {
						$meta[$key] = $decoded;
					} else if (strpos($value, 'a:') === 0) {
						// PHP serialized array
						$unserialized = unserialize($value);
						$meta[$key] = is_array($unserialized) ? $unserialized : ['url' => '', 'id' => 0, 'size' => 'cover'];
					} else {
						// Fallback
						$meta[$key] = ['url' => '', 'id' => 0, 'size' => 'cover'];
					}
				} else {
					$meta[$key] = ['url' => '', 'id' => 0, 'size' => 'cover'];
				}
			} else {
				$meta[$key] = $value;
			}
		}

		return [
			'id'       => $post->ID,
			'title'    => $post->post_title,
			'status'   => $post->post_status,
			'content'  => $post->post_content,
			'date'     => $post->post_date,
			'modified' => $post->post_modified,
			'meta'     => $meta,
			'editUrl'  => admin_url('admin.php?page=hashbar#/announcement-bar/' . $post->ID),
		];
	}

	/**
	 * Get all pages and posts for targeting
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response Response object
	 */
	public static function get_pages_posts($request) {
		try {
			$pages_args = [
				'post_type'      => 'page',
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			];

			$posts_args = [
				'post_type'      => 'post',
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			];

			$pages_query = new WP_Query($pages_args);
			$posts_query = new WP_Query($posts_args);

			$items = [];

			// Add pages
			foreach ($pages_query->posts as $post) {
				$items[] = [
					'value' => $post->ID,
					'label' => '[Page] ' . $post->post_title,
				];
			}

			// Add posts
			foreach ($posts_query->posts as $post) {
				$items[] = [
					'value' => $post->ID,
					'label' => '[Post] ' . $post->post_title,
				];
			}

			// Sort alphabetically
			usort($items, function($a, $b) {
				return strcmp($a['label'], $b['label']);
			});

			return new WP_REST_Response([
				'success' => true,
				'data'    => $items,
				'total'   => count($items),
			], 200);

		} catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500);
		}
	}
	/**
	 * Debug endpoint to check announcement bar data and geographic targeting
	 * Only available if WP_DEBUG is enabled
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response Response object
	 */
	public static function debug_geographic_targeting($request) {
		// Only allow in debug mode
		if (!defined('WP_DEBUG') || !WP_DEBUG) {
			return new WP_REST_Response([
				'success' => false,
				'message' => 'Debug mode is not enabled. Set WP_DEBUG to true in wp-config.php',
			], 403);
		}

		try {
			$post_id = absint($request['id']);

			$post = get_post($post_id);
			if (!$post || $post->post_type !== 'wphash_announcement') {
				return new WP_REST_Response([
					'success' => false,
					'message' => 'Announcement bar not found',
				], 404);
			}

			// Get raw meta value
			$raw_countries = get_post_meta($post_id, '_wphash_ab_target_countries', true);

			// Get formatted data
			$formatted_data = self::format_announcement_bar_data($post);
			$formatted_countries = $formatted_data['_wphash_ab_target_countries'] ?? null;

			// Get visitor IP info for debugging
			// Check for IP from share internet
			if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
				$current_ip = $_SERVER['HTTP_CLIENT_IP'];
			}
			// Check for IP passed from proxy
			elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				// Handle multiple IPs (take the first one)
				$ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
				$current_ip = trim( $ips[0] );
			}
			// Check for remote address
			else {
				$current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
			}

			// Validate IP
			if ( ! filter_var( $current_ip, FILTER_VALIDATE_IP ) ) {
				$current_ip = '';
			}

			return new WP_REST_Response([
				'success' => true,
				'debug_info' => [
					'post_id' => $post_id,
					'post_title' => $post->post_title,
					'raw_countries_value' => $raw_countries,
					'raw_countries_type' => gettype($raw_countries),
					'formatted_countries' => $formatted_countries,
					'formatted_countries_type' => gettype($formatted_countries),
					'visitor_ip' => $current_ip,
					'admin_note' => 'To check visitor country detection, access this endpoint from the frontend or use ?hashbar_test_country=XX parameter',
				],
			], 200);

		} catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500);
		}
	}
}

add_action('rest_api_init', [ AnnouncementBar::class, 'register_routes' ]);
