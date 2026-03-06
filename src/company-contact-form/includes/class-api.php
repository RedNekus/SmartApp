<?php
/**
 * API Class - REST endpoint handler for Company Contact Form
 *
 * @package Company Contact Form
 * @since   1.2.0
 */

namespace CCF;

/**
 * API class for handling REST requests.
 *
 * Provides endpoint registration, spam checks, and submission handling.
 */
class API {

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		add_action(
			'rest_api_init',
			function () {
				register_rest_route(
					'company/v1',
					'/contact',
					array(
						'methods'             => 'POST',
						'callback'            => array( __CLASS__, 'handle_submission' ),
						'permission_callback' => function () {
							// Allow all requests in development mode.
							return true;
						},
					)
				);
			}
		);
	}

	/**
	 * Anti-spam checks: Honeypot + Time-trap + Rate-limit.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return true|\WP_Error True if passed, WP_Error if spam detected.
	 */
	private static function check_spam( $request ) {
		// 1. Honeypot: if hidden field is filled, it's a bot.
		$honeypot = $request->get_param( 'website' );
		if ( ! empty( $honeypot ) ) {
			return new \WP_Error( 'spam_detected', 'Honeypot triggered', array( 'status' => 400 ) );
		}

		// 2. Time-trap: if form submitted faster than 2 seconds, it's a bot.
		$start_time = intval( $request->get_param( 'form_start_time' ) );
		if ( $start_time > 0 ) {
			$fill_time = time() - $start_time;
			if ( $fill_time < 2 ) {
				return new \WP_Error( 'spam_detected', 'Time-trap triggered', array( 'status' => 400 ) );
			}
		}

		// 3. Rate-limit: max 5 submissions per minute per IP.
		$ip       = self::get_client_ip();
		$rate_key = 'ccf_rate_limit_' . md5( $ip );
		$attempts = get_transient( $rate_key );

		if ( false === $attempts ) {
			set_transient( $rate_key, 1, 60 );
		} elseif ( $attempts >= 5 ) {
			return new \WP_Error( 'too_many_requests', 'Rate limit exceeded', array( 'status' => 429 ) );
		} else {
			set_transient( $rate_key, $attempts + 1, 60 );
		}

		return true;
	}

	/**
	 * Get client IP address (proxy-aware).
	 *
	 * @return string Sanitized IP address.
	 */
	private static function get_client_ip() {
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$ip = explode( ',', wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )[0]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		} elseif ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$ip = wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$ip = wp_unslash( $_SERVER['REMOTE_ADDR'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		} else {
			$ip = '0.0.0.0';
		}
		return sanitize_text_field( $ip );
	}

	/**
	 * Handle form submission via REST API.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response|\WP_Error Response or error.
	 */
	public static function handle_submission( $request ) {
		// Unpack block attributes from hidden field.
		$attributes_json = $request->get_param( 'ccf_block_attributes' );
		$attributes      = $attributes_json ? json_decode( $attributes_json, true ) : array();

		// === ANTI-SPAM CHECK ===
		$spam_check = self::check_spam( $request );
		if ( is_wp_error( $spam_check ) ) {
			if ( class_exists( 'CCF\Logger' ) ) {
				\CCF\Logger::log(
					sanitize_email( $request->get_param( 'email' ) ),
					'spam_blocked',
					null,
					self::get_client_ip()
				);
			}
			return $spam_check;
		}
		// === END ANTI-SPAM ===

		// Validate email (RFC-compliant).
		$email = sanitize_email( $request->get_param( 'email' ) );
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'invalid_email', 'Invalid email format', array( 'status' => 400 ) );
		}

		// Sanitize remaining fields.
		$data = array(
			'first_name' => sanitize_text_field( $request->get_param( 'first_name' ) ),
			'last_name'  => sanitize_text_field( $request->get_param( 'last_name' ) ),
			'subject'    => sanitize_text_field( $request->get_param( 'subject' ) ),
			'message'    => sanitize_textarea_field( $request->get_param( 'message' ) ),
			'email'      => $email,
			'ip'         => self::get_client_ip(),
			'timestamp'  => current_time( 'mysql' ),
		);

		// HubSpot integration (MOCK or PRODUCTION).
		self::send_to_hubspot( $request, $attributes );

		// Email notification to admin.
		self::send_admin_email( $data, $attributes );

		// Save to database.
		if ( class_exists( 'CCF\Database' ) ) {
			\CCF\Database::save_submission( $data );
		}

		// Log successful submission.
		if ( class_exists( 'CCF\Logger' ) ) {
			\CCF\Logger::log( $email, 'received', null, $data['ip'] );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => 'Form submitted successfully',
				'data'    => array( 'email' => $email ),
			)
		);
	}

	/**
	 * Send data to HubSpot (with Mock mode support).
	 *
	 * @param \WP_REST_Request $request    The REST request object.
	 * @param array            $attributes Block attributes.
	 * @return bool True on success, false on failure.
	 */
	private static function send_to_hubspot( $request, $attributes ) {
		// Use Config class for credentials (supports .env and wp-config.php)
		$use_constants = \CCF\Config::get_bool( 'CCF_HUBSPOT_USE_CONSTANTS', false );

		if ( $use_constants ) {
			$token     = \CCF\Config::get( 'CCF_HUBSPOT_TOKEN', '' );
			$portal_id = \CCF\Config::get( 'CCF_HUBSPOT_PORTAL_ID', '' );
			$form_id   = \CCF\Config::get( 'CCF_HUBSPOT_FORM_ID', '' );
		} else {
			$token     = $attributes['hubspotAccessToken'] ?? '';
			$portal_id = $attributes['hubspotPortalId'] ?? '';
			$form_id   = $attributes['hubspotFormId'] ?? '';
		}

		// === MOCK MODE: if no credentials ===
		if ( empty( $token ) || empty( $portal_id ) || empty( $form_id ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[CCF] HubSpot MOCK MODE: Integration not configured' );
			return true;
		}

		// === PRODUCTION MODE: real API call ===
		$field_map = array(
			'first_name' => 'firstname',
			'last_name'  => 'lastname',
			'email'      => 'email',
			'message'    => 'message',
			'subject'    => 'subject',
		);

		$fields = array();
		foreach ( $field_map as $form_field => $hubspot_prop ) {
			$value = $request->get_param( $form_field );
			if ( ! empty( $value ) ) {
				$fields[] = array(
					'name'  => $hubspot_prop,
					'value' => sanitize_text_field( $value ),
				);
			}
		}

		if ( empty( $fields ) ) {
			return false;
		}

		// Prepare HubSpot API request using wp_remote_post (WordPress native).
		$url = sprintf(
			'https://api.hubapi.com/submissions/v3/integration/submit/%s/%s',
			$portal_id,
			$form_id
		);

		$payload = array(
			'fields'  => $fields,
			'context' => array(
				'pageUri'   => home_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
				'pageName'  => get_the_title() ?: '',
				'ipAddress' => self::get_client_ip(),
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $token,
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 10,
			)
		);

		$http_code = wp_remote_retrieve_response_code( $response );

		// Log result.
		if ( $http_code >= 200 && $http_code < 300 ) {
			if ( class_exists( 'CCF\Logger' ) ) {
				\CCF\Logger::log( $request->get_param( 'email' ), 'hubspot_sent', $form_id, self::get_client_ip() );
			}
			return true;
		} else {
			if ( class_exists( 'CCF\Logger' ) ) {
				\CCF\Logger::log( $request->get_param( 'email' ), 'hubspot_failed', $form_id, self::get_client_ip() );
			}
			return false;
		}
	}

	/**
	 * Send admin email notification.
	 *
	 * @param array $data       Submission data.
	 * @param array $attributes Block attributes.
	 * @return bool True on success, false on failure.
	 */
	private static function send_admin_email( $data, $attributes ) {
		$recipient = ! empty( $attributes['recipientEmail'] )
			? sanitize_email( $attributes['recipientEmail'] )
			: get_option( 'admin_email' );

		if ( ! is_email( $recipient ) ) {
			return false;
		}

		$prefix  = ! empty( $attributes['subjectPrefix'] ) ? $attributes['subjectPrefix'] : '';
		$subject = sprintf(
			'%sNew contact form submission from %s',
			$prefix ? $prefix . ' - ' : '',
			get_bloginfo( 'name' )
		);

		// Build email body.
		$message  = "New message received via Company Contact Form\n\n";
		$message .= "=== Contact Details ===\n";
		$message .= 'Name: ' . $data['first_name'] . ' ' . $data['last_name'] . "\n";
		$message .= 'Email: ' . $data['email'] . "\n";
		$message .= 'Subject: ' . $data['subject'] . "\n";
		$message .= "Message:\n" . $data['message'] . "\n\n";
		$message .= "=== Technical Details ===\n";
		$message .= 'IP: ' . $data['ip'] . "\n";
		$message .= 'Time: ' . $data['timestamp'] . "\n";
		$message .= 'Site: ' . esc_url( home_url() ) . "\n";

		$headers = array(
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
			'Reply-To: ' . $data['email'],
			'Content-Type: text/plain; charset=UTF-8',
		);

		$sent = wp_mail( $recipient, $subject, $message, $headers );

		return (bool) $sent;
	}
}
