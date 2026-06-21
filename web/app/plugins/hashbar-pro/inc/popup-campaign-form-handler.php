<?php
/**
 * Popup Campaign Form Handler
 *
 * Handles form submissions from popup campaigns.
 *
 * @package HashBar Pro
 * @since 2.0.0
 */

namespace Hashbar\Pro;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Popup Campaign Form Handler Class
 */
class Hashbar_Popup_Campaign_Form_Handler {

	/**
	 * Initialize the form handler
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST API routes for form submission
	 */
	public static function register_routes() {
		register_rest_route(
			'hashbar/v1',
			'/popup-campaigns/(?P<id>\d+)/submit',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_submission' ),
				'permission_callback' => '__return_true', // Public endpoint
				'args'                => array(
					'id' => array(
						'validate_callback' => function( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);
	}

	/**
	 * Handle form submission
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Response object.
	 */
	public static function handle_submission( $request ) {
		try {
			$popup_id = absint( $request['id'] );

			// Verify popup exists and is active
			$popup = get_post( $popup_id );
			if ( ! $popup || 'wphash_popup' !== $popup->post_type ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => esc_html__( 'Invalid popup campaign', 'hashbar' ),
					),
					404
				);
			}

			$popup_status = get_post_meta( $popup_id, '_wphash_popup_status', true );
			if ( 'active' !== $popup_status && 'publish' !== $popup->post_status ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => esc_html__( 'This popup is not active', 'hashbar' ),
					),
					403
				);
			}

			// Get form data
			$form_data = $request->get_param( 'form_data' );
			if ( empty( $form_data ) || ! is_array( $form_data ) ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => esc_html__( 'No form data provided', 'hashbar' ),
					),
					400
				);
			}

			// Get form field configuration
			$form_fields = get_post_meta( $popup_id, '_wphash_popup_form_fields', true );
			if ( empty( $form_fields ) ) {
				$form_fields = array();
			}

			// Validate required fields
			$validation_result = self::validate_form_data( $form_data, $form_fields );
			if ( ! $validation_result['valid'] ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => $validation_result['message'],
						'errors'  => $validation_result['errors'],
					),
					400
				);
			}

			// Sanitize form data
			$sanitized_data = self::sanitize_form_data( $form_data, $form_fields );

			// Extract common fields by type (since field IDs are dynamic like 'email_1234567890')
			$email = '';
			$name  = '';
			$phone = '';

			foreach ( $form_fields as $field ) {
				$field_id   = isset( $field['id'] ) ? $field['id'] : '';
				$field_type = isset( $field['type'] ) ? $field['type'] : '';

				if ( 'email' === $field_type && isset( $sanitized_data[ $field_id ] ) ) {
					$email = $sanitized_data[ $field_id ];
				} elseif ( 'name' === $field_type && isset( $sanitized_data[ $field_id ] ) ) {
					$name = $sanitized_data[ $field_id ];
				} elseif ( 'phone' === $field_type && isset( $sanitized_data[ $field_id ] ) ) {
					$phone = $sanitized_data[ $field_id ];
				}
			}

			// Get visitor info
			$ip_address  = self::get_visitor_ip();
			$user_agent  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
			$page_url    = $request->get_param( 'page_url' ) ? esc_url_raw( $request->get_param( 'page_url' ) ) : '';
			$page_id     = $request->get_param( 'page_id' ) ? absint( $request->get_param( 'page_id' ) ) : 0;
			$referrer    = $request->get_param( 'referrer' ) ? esc_url_raw( $request->get_param( 'referrer' ) ) : '';
			$user_id     = get_current_user_id();

			// Get UTM parameters
			$utm_source   = $request->get_param( 'utm_source' ) ? sanitize_text_field( $request->get_param( 'utm_source' ) ) : '';
			$utm_medium   = $request->get_param( 'utm_medium' ) ? sanitize_text_field( $request->get_param( 'utm_medium' ) ) : '';
			$utm_campaign = $request->get_param( 'utm_campaign' ) ? sanitize_text_field( $request->get_param( 'utm_campaign' ) ) : '';
			$utm_term     = $request->get_param( 'utm_term' ) ? sanitize_text_field( $request->get_param( 'utm_term' ) ) : '';
			$utm_content  = $request->get_param( 'utm_content' ) ? sanitize_text_field( $request->get_param( 'utm_content' ) ) : '';

			// Store submission in database
			$submission_id = self::store_submission(
				array(
					'popup_id'     => $popup_id,
					'form_data'    => $sanitized_data,
					'email'        => $email,
					'name'         => $name,
					'phone'        => $phone,
					'ip_address'   => $ip_address,
					'user_agent'   => $user_agent,
					'page_url'     => $page_url,
					'page_id'      => $page_id,
					'referrer_url' => $referrer,
					'utm_source'   => $utm_source,
					'utm_medium'   => $utm_medium,
					'utm_campaign' => $utm_campaign,
					'utm_term'     => $utm_term,
					'utm_content'  => $utm_content,
					'user_id'      => $user_id,
				)
			);

			if ( ! $submission_id ) {
				return new \WP_REST_Response(
					array(
						'success' => false,
						'message' => esc_html__( 'Failed to save submission', 'hashbar' ),
					),
					500
				);
			}

			// Handle Mailchimp integration
			$mailchimp_enabled = get_post_meta( $popup_id, '_wphash_popup_mailchimp_enabled', true );
			$mailchimp_result  = null;

			if ( $mailchimp_enabled && ! empty( $email ) ) {
				$mailchimp_result = self::send_to_mailchimp( $popup_id, $email, $name, $sanitized_data, $submission_id );
			}

			// Get success action settings
			$success_action  = get_post_meta( $popup_id, '_wphash_popup_form_success_action', true ) ?: 'message';
			$success_message = get_post_meta( $popup_id, '_wphash_popup_form_success_message', true ) ?: esc_html__( 'Thank you! You have successfully subscribed.', 'hashbar' );
			$redirect_url     = get_post_meta( $popup_id, '_wphash_popup_form_success_redirect_url', true ) ?: '';
			$redirecting_text = get_post_meta( $popup_id, '_wphash_popup_form_redirecting_text', true ) ?: esc_html__( 'Redirecting...', 'hashbar' );
			$close_delay      = get_post_meta( $popup_id, '_wphash_popup_form_close_delay', true ) ?: 3;

			// Build response
			$response_data = array(
				'success'        => true,
				'message'        => $success_message,
				'submission_id'  => $submission_id,
				'success_action' => $success_action,
			);

			if ( 'redirect' === $success_action && ! empty( $redirect_url ) ) {
				$response_data['redirect_url']     = esc_url( $redirect_url );
				$response_data['redirecting_text'] = $redirecting_text;
			}

			if ( 'close' === $success_action ) {
				$response_data['close_delay'] = absint( $close_delay );
			}

			if ( null !== $mailchimp_result ) {
				$response_data['mailchimp'] = $mailchimp_result;
			}

			// Fire action hook for other integrations
			do_action( 'hashbar_popup_form_submitted', $submission_id, $popup_id, $sanitized_data );

			return new \WP_REST_Response( $response_data, 200 );

		} catch ( \Exception $e ) {
			return new \WP_REST_Response(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				),
				500
			);
		}
	}

	/**
	 * Validate form data against field configuration
	 *
	 * @param array $form_data Submitted form data.
	 * @param array $form_fields Field configuration.
	 * @return array Validation result.
	 */
	private static function validate_form_data( $form_data, $form_fields ) {
		$errors = array();

		foreach ( $form_fields as $field ) {
			$field_id   = isset( $field['id'] ) ? $field['id'] : '';
			$field_type = isset( $field['type'] ) ? $field['type'] : '';
			$required   = isset( $field['required'] ) && $field['required'];
			$label      = isset( $field['label'] ) ? $field['label'] : $field_id;

			$value = isset( $form_data[ $field_id ] ) ? $form_data[ $field_id ] : '';

			// Check required fields
			if ( $required && empty( $value ) ) {
				$errors[ $field_id ] = sprintf(
					/* translators: %s: field label */
					esc_html__( '%s is required', 'hashbar' ),
					$label
				);
				continue;
			}

			// Skip validation for empty non-required fields
			if ( empty( $value ) ) {
				continue;
			}

			// Type-specific validation
			switch ( $field_type ) {
				case 'email':
					if ( ! is_email( $value ) ) {
						$errors[ $field_id ] = esc_html__( 'Please enter a valid email address', 'hashbar' );
					}
					break;

				case 'phone':
					// Basic phone validation - allow digits, spaces, dashes, parentheses, plus
					if ( ! preg_match( '/^[0-9\s\-\(\)\+]+$/', $value ) ) {
						$errors[ $field_id ] = esc_html__( 'Please enter a valid phone number', 'hashbar' );
					}
					break;

				case 'checkbox':
				case 'consent':
					if ( $required && empty( $value ) ) {
						$errors[ $field_id ] = sprintf(
							/* translators: %s: field label */
							esc_html__( 'You must agree to %s', 'hashbar' ),
							$label
						);
					}
					break;
			}
		}

		if ( ! empty( $errors ) ) {
			return array(
				'valid'   => false,
				'message' => esc_html__( 'Please correct the errors below', 'hashbar' ),
				'errors'  => $errors,
			);
		}

		return array(
			'valid'   => true,
			'message' => '',
			'errors'  => array(),
		);
	}

	/**
	 * Sanitize form data
	 *
	 * @param array $form_data Submitted form data.
	 * @param array $form_fields Field configuration.
	 * @return array Sanitized data.
	 */
	private static function sanitize_form_data( $form_data, $form_fields ) {
		$sanitized = array();

		// Build a map of field types
		$field_types = array();
		foreach ( $form_fields as $field ) {
			$field_id               = isset( $field['id'] ) ? $field['id'] : '';
			$field_types[ $field_id ] = isset( $field['type'] ) ? $field['type'] : 'text';
		}

		foreach ( $form_data as $key => $value ) {
			$field_type = isset( $field_types[ $key ] ) ? $field_types[ $key ] : 'text';

			switch ( $field_type ) {
				case 'email':
					$sanitized[ $key ] = sanitize_email( $value );
					break;

				case 'textarea':
					$sanitized[ $key ] = sanitize_textarea_field( $value );
					break;

				case 'checkbox':
				case 'consent':
					$sanitized[ $key ] = ! empty( $value );
					break;

				case 'dropdown':
				case 'radio':
					$sanitized[ $key ] = sanitize_text_field( $value );
					break;

				case 'phone':
					// Allow only phone characters
					$sanitized[ $key ] = preg_replace( '/[^0-9\s\-\(\)\+]/', '', $value );
					break;

				case 'date':
					$sanitized[ $key ] = sanitize_text_field( $value );
					break;

				case 'hidden':
					$sanitized[ $key ] = sanitize_text_field( $value );
					break;

				default:
					$sanitized[ $key ] = sanitize_text_field( $value );
					break;
			}
		}

		return $sanitized;
	}

	/**
	 * Store submission in database
	 *
	 * @param array $data Submission data.
	 * @return int|false Submission ID or false on failure.
	 */
	private static function store_submission( $data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'hashbar_popup_submissions';

		// Check if table exists, create if not
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			if ( class_exists( __NAMESPACE__ . '\Hashbar_Popup_Campaign_Database' ) ) {
				Hashbar_Popup_Campaign_Database::create_submissions_table();
			}
		}

		$insert_data = array(
			'popup_id'     => $data['popup_id'],
			'form_data'    => wp_json_encode( $data['form_data'] ),
			'email'        => $data['email'],
			'name'         => $data['name'],
			'phone'        => $data['phone'],
			'ip_address'   => $data['ip_address'],
			'user_agent'   => $data['user_agent'],
			'page_url'     => $data['page_url'],
			'page_id'      => $data['page_id'] ?: null,
			'referrer_url' => $data['referrer_url'],
			'utm_source'   => $data['utm_source'],
			'utm_medium'   => $data['utm_medium'],
			'utm_campaign' => $data['utm_campaign'],
			'utm_term'     => $data['utm_term'],
			'utm_content'  => $data['utm_content'],
			'user_id'      => $data['user_id'] ?: null,
			'status'       => 'active',
			'created_at'   => current_time( 'mysql' ),
		);

		$format = array(
			'%d', // popup_id
			'%s', // form_data
			'%s', // email
			'%s', // name
			'%s', // phone
			'%s', // ip_address
			'%s', // user_agent
			'%s', // page_url
			'%d', // page_id
			'%s', // referrer_url
			'%s', // utm_source
			'%s', // utm_medium
			'%s', // utm_campaign
			'%s', // utm_term
			'%s', // utm_content
			'%d', // user_id
			'%s', // status
			'%s', // created_at
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert( $table_name, $insert_data, $format );

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Send subscriber to Mailchimp
	 *
	 * @param int    $popup_id Popup ID.
	 * @param string $email Subscriber email.
	 * @param string $name Subscriber name.
	 * @param array  $form_data Form data.
	 * @param int    $submission_id Submission ID.
	 * @return array Result of Mailchimp API call.
	 */
	private static function send_to_mailchimp( $popup_id, $email, $name, $form_data, $submission_id ) {
		global $wpdb;

		$api_key      = get_post_meta( $popup_id, '_wphash_popup_mailchimp_api_key', true );
		$list_id      = get_post_meta( $popup_id, '_wphash_popup_mailchimp_list_id', true );
		$double_optin = get_post_meta( $popup_id, '_wphash_popup_mailchimp_double_optin', true );
		$tags         = get_post_meta( $popup_id, '_wphash_popup_mailchimp_tags', true );

		if ( empty( $api_key ) || empty( $list_id ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'Mailchimp not configured properly', 'hashbar' ),
			);
		}

		// Extract data center from API key
		$dc = '';
		if ( strpos( $api_key, '-' ) !== false ) {
			$dc = explode( '-', $api_key )[1];
		}

		if ( empty( $dc ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'Invalid Mailchimp API key', 'hashbar' ),
			);
		}

		// Build subscriber data
		$subscriber_data = array(
			'email_address' => $email,
			'status'        => $double_optin ? 'pending' : 'subscribed',
		);

		// Add merge fields if name is provided
		if ( ! empty( $name ) ) {
			$name_parts = explode( ' ', $name, 2 );
			$subscriber_data['merge_fields'] = array(
				'FNAME' => $name_parts[0],
				'LNAME' => isset( $name_parts[1] ) ? $name_parts[1] : '',
			);
		}

		// Add tags
		if ( ! empty( $tags ) ) {
			$tag_array = array_map( 'trim', explode( ',', $tags ) );
			$subscriber_data['tags'] = $tag_array;
		}

		// Use PUT with subscriber hash to handle both new and existing subscribers
		// This avoids "already a list member" error when using POST
		$subscriber_hash = md5( strtolower( $email ) );
		$url = "https://{$dc}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$subscriber_hash}";

		// Use status_if_new for PUT requests - this sets status only for new subscribers
		// Existing subscribers keep their current status
		$subscriber_data['status_if_new'] = $subscriber_data['status'];
		unset( $subscriber_data['status'] );

		$response = wp_remote_request(
			$url,
			array(
				'method'  => 'PUT',
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( 'anystring:' . $api_key ),
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $subscriber_data ),
				'timeout' => 15,
			)
		);

		$table_name = $wpdb->prefix . 'hashbar_popup_submissions';

		if ( is_wp_error( $response ) ) {
			// Update submission with error
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update(
				$table_name,
				array(
					'mailchimp_status' => 'failed',
					'mailchimp_error'  => $response->get_error_message(),
				),
				array( 'id' => $submission_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$body        = json_decode( wp_remote_retrieve_body( $response ), true );
		$status_code = wp_remote_retrieve_response_code( $response );

		if ( $status_code >= 400 ) {
			$error_message = isset( $body['detail'] ) ? $body['detail'] : esc_html__( 'Mailchimp subscription failed', 'hashbar' );

			// Update submission with error
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update(
				$table_name,
				array(
					'mailchimp_status' => 'failed',
					'mailchimp_error'  => $error_message,
				),
				array( 'id' => $submission_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);

			return array(
				'success' => false,
				'message' => $error_message,
			);
		}

		// Update submission with success
		$mailchimp_status = $double_optin ? 'pending' : 'subscribed';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			$table_name,
			array(
				'mailchimp_status' => $mailchimp_status,
			),
			array( 'id' => $submission_id ),
			array( '%s' ),
			array( '%d' )
		);

		return array(
			'success' => true,
			'status'  => $mailchimp_status,
			'message' => $double_optin
				? esc_html__( 'Please check your email to confirm subscription', 'hashbar' )
				: esc_html__( 'Successfully subscribed', 'hashbar' ),
		);
	}

	/**
	 * Get visitor IP address
	 *
	 * @return string IP address.
	 */
	private static function get_visitor_ip() {
		$ip = '';

		// Check for shared internet IP
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// Handle multiple IPs (take the first one)
			$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip  = trim( $ips[0] );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		// Validate IP
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$ip = '';
		}

		return $ip;
	}
}

// Initialize form handler
Hashbar_Popup_Campaign_Form_Handler::init();
