<?php
/**
 * WhatsApp Cloud API handler.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_WhatsApp_API
 *
 * Handles all communication with the WhatsApp Cloud API.
 */
class CAPFW_WhatsApp_API {

	/**
	 * WhatsApp Cloud API base URL.
	 *
	 * @var string
	 */
	const API_BASE = 'https://graph.facebook.com/v25.0/';

	/**
	 * Get plugin settings.
	 *
	 * @return array
	 */
	private static function get_settings(): array {
		return (array) get_option( 'capfw_settings', array() );
	}

	/**
	 * Get access token.
	 *
	 * @return string
	 */
	private static function get_token(): string {
		$settings = self::get_settings();
		return sanitize_text_field( $settings['access_token'] ?? '' );
	}

	/**
	 * Get Phone Number ID.
	 *
	 * @return string
	 */
	private static function get_phone_number_id(): string {
		$settings = self::get_settings();
		return sanitize_text_field( $settings['phone_number_id'] ?? '' );
	}

	/**
	 * Get configured message type: 'text' or 'template'.
	 *
	 * Defaults to 'text' to preserve existing behaviour for sites that
	 * haven't picked a value yet.
	 *
	 * @return string
	 */
		private static function get_integration_settings( string $slug ): array {
		$all = (array) get_option( 'capfw_integration_settings', array() );
		return (array) ( $all[ $slug ] ?? array() );
	}

	private static function get_message_type( string $slug = '' ): string {
		if ( $slug ) {
			$int = self::get_integration_settings( $slug );
			if ( isset( $int['message_type'] ) ) {
				return ( 'template' === $int['message_type'] ) ? 'template' : 'text';
			}
		}
		$settings = self::get_settings();
		$type     = sanitize_text_field( $settings['message_type'] ?? 'text' );
		return 'template' === $type ? 'template' : 'text';
	}

	private static function get_template_name( string $slug = '' ): string {
		if ( $slug ) {
			$int = self::get_integration_settings( $slug );
			if ( ! empty( $int['template_name'] ) ) {
				return sanitize_text_field( $int['template_name'] );
			}
		}
		$settings = self::get_settings();
		return sanitize_text_field( $settings['template_name'] ?? '' );
	}

	private static function get_template_language( string $slug = '' ): string {
		if ( $slug ) {
			$int = self::get_integration_settings( $slug );
			if ( ! empty( $int['template_language'] ) ) {
				return sanitize_text_field( $int['template_language'] );
			}
		}
		$settings = self::get_settings();
		$lang     = sanitize_text_field( $settings['template_language'] ?? '' );
		return $lang ? $lang : 'en_US';
	}

	private static function is_template_no_variables( string $slug = '' ): bool {
		if ( $slug ) {
			$int = self::get_integration_settings( $slug );
			if ( isset( $int['template_no_variables'] ) ) {
				return ! empty( $int['template_no_variables'] );
			}
		}
		$settings = self::get_settings();
		return ! empty( $settings['template_no_variables'] );
	}

	/**
	 * Send a message via WhatsApp Cloud API.
	 *
	 * Dispatches to a free-form text message or an approved template
	 * message depending on the saved "Message Type" setting.
	 *
	 * IMPORTANT — WhatsApp 24-hour customer service window:
	 * Free-form "text" messages can ONLY be delivered if the recipient
	 * has messaged your business number within the last 24 hours.
	 * Outside that window, Meta will still accept the API call (you'll
	 * get a wamid back and the log will show "sent") but the message
	 * will silently fail to deliver. Use 'template' type to reliably
	 * reach customers outside the 24-hour window.
	 *
	 * @param string $to      Recipient phone (E.164 format, e.g. 919876543210).
	 * @param string $message Plain text message. Used as the template's
	 *                        body variable {{1}} when message_type is 'template'
	 *                        and the template has a single body placeholder;
	 *                        ignored if the template has no variables.
	 * @return array          Response array with 'success' bool and 'response' string.
	 */
	public static function send_message( string $to, string $message, string $integration_slug = '' ): array {
		$token           = self::get_token();
		$phone_number_id = self::get_phone_number_id();

		if ( empty( $token ) || empty( $phone_number_id ) ) {
			$missing = empty( $token ) ? 'Access Token' : 'Phone Number ID';
			return array(
				'success'  => false,
				'response' => sprintf(
					/* translators: %s: name of the missing credential */
					__( 'WhatsApp API credentials are not configured. Missing: %s. Please go to WA Funnel → Settings and save your credentials.', 'captain-funnel-for-whatsapp' ),
					$missing
				),
			);
		}

		if ( 'template' === self::get_message_type( $integration_slug ) ) {
			return self::send_template_message( $to, $message, $token, $phone_number_id, $integration_slug );
		}

		return self::send_text_message( $to, $message, $token, $phone_number_id );
	}

	/**
	 * Send a free-form text message (only deliverable within the 24h window).
	 *
	 * @param string $to              Recipient phone.
	 * @param string $message         Message body.
	 * @param string $token           Access token.
	 * @param string $phone_number_id Phone Number ID.
	 * @return array
	 */
	private static function send_text_message( string $to, string $message, string $token, string $phone_number_id ): array {
		$endpoint = self::API_BASE . $phone_number_id . '/messages';

		$body = wp_json_encode(
			array(
				'messaging_product' => 'whatsapp',
				'to'                => sanitize_text_field( $to ),
				'type'              => 'text',
				'text'              => array(
					'body' => wp_strip_all_tags( $message ),
				),
			)
		);

		return self::post_message( $endpoint, $body, $token );
	}

	/**
	 * Send an approved template message (deliverable outside the 24h window).
	 *
	 * @param string $to              Recipient phone.
	 * @param string $message         Body variable for {{1}} if the template expects one.
	 * @param string $token           Access token.
	 * @param string $phone_number_id Phone Number ID.
	 * @return array
	 */
	private static function send_template_message( string $to, string $message, string $token, string $phone_number_id, string $slug = '' ): array {
		$template_name = self::get_template_name( $slug );

		if ( empty( $template_name ) ) {
			return array(
				'success'  => false,
				'response' => __( 'Message Type is set to "Template" but no Template Name is configured. Please go to WA Funnel → Settings.', 'captain-funnel-for-whatsapp' ),
			);
		}

		$endpoint = self::API_BASE . $phone_number_id . '/messages';

		$template_payload = array(
			'name'     => $template_name,
			'language' => array(
				'code' => self::get_template_language( $slug ),
			),
		);

		// If user marked "Template has no variables", send bare template — no components.
		// This is required for templates like 'hello_world' that have zero placeholders.
		// If variables are expected, split message by | to support multiple variables:
		//   "Hello {name}|{phone}" → {{1}} = name, {{2}} = phone
		if ( ! self::is_template_no_variables( $slug ) && '' !== trim( wp_strip_all_tags( $message ) ) ) {
			$raw_parts  = explode( '|', wp_strip_all_tags( $message ) );
			$parameters = array();
			foreach ( $raw_parts as $part ) {
				$part = trim( $part );
				if ( '' !== $part ) {
					$parameters[] = array(
						'type' => 'text',
						'text' => $part,
					);
				}
			}
			if ( ! empty( $parameters ) ) {
				$template_payload['components'] = array(
					array(
						'type'       => 'body',
						'parameters' => $parameters,
					),
				);
			}
		}

		$body = wp_json_encode(
			array(
				'messaging_product' => 'whatsapp',
				'to'                => sanitize_text_field( $to ),
				'type'              => 'template',
				'template'          => $template_payload,
			)
		);

		return self::post_message( $endpoint, $body, $token );
	}

	/**
	 * Shared POST helper for both text and template sends.
	 *
	 * @param string $endpoint Full API endpoint URL.
	 * @param string $body     JSON-encoded request body.
	 * @param string $token    Access token.
	 * @return array
	 */
	private static function post_message( string $endpoint, string $body, string $token ): array {
		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
					'Content-Type'  => 'application/json',
				),
				'body'    => $body,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success'  => false,
				'response' => $response->get_error_message(),
			);
		}

		$code          = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		return array(
			'success'  => ( 200 === (int) $code ),
			'response' => $response_body,
		);
	}

	/**
	 * Test API connection with saved credentials.
	 *
	 * @return array
	 */
	public static function test_connection(): array {
		$settings = self::get_settings();
		return self::test_connection_with(
			sanitize_text_field( $settings['access_token']   ?? '' ),
			sanitize_text_field( $settings['phone_number_id'] ?? '' )
		);
	}

	/**
	 * Test with explicitly provided credentials (live form values).
	 *
	 * Uses the Phone Number ID GET endpoint — only requires
	 * whatsapp_business_messaging permission on the token.
	 *
	 * @param string $token           Access token.
	 * @param string $phone_number_id Phone number ID.
	 * @return array
	 */
	public static function test_connection_with( string $token, string $phone_number_id ): array {
		if ( empty( $token ) || empty( $phone_number_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Please enter Access Token and Phone Number ID first.', 'captain-funnel-for-whatsapp' ),
			);
		}

		// GET /{phone-number-id} — validates both token and phone number ID in one call.
		$endpoint = self::API_BASE . $phone_number_id . '?fields=display_phone_number,verified_name,code_verification_status';

		$response = wp_remote_get(
			$endpoint,
			array(
				'headers' => array( 'Authorization' => 'Bearer ' . $token ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 === $code ) {
			$name  = sanitize_text_field( $body['verified_name']        ?? '' );
			$phone = sanitize_text_field( $body['display_phone_number'] ?? '' );
			/* translators: 1: verified business name, 2: WhatsApp phone number */
			$msg = ! empty( $name )
				? sprintf( __( 'Connected! Business: %1$s | Phone: %2$s', 'captain-funnel-for-whatsapp' ), $name, $phone )
				: __( 'Connection successful! WhatsApp API is working.', 'captain-funnel-for-whatsapp' );

			return array( 'success' => true, 'message' => $msg );
		}

		// Surface the API's own error message so users know exactly what went wrong.
		$api_error = sanitize_text_field( $body['error']['message'] ?? '' );
		$message   = $api_error
			? $api_error
			: __( 'Connection failed. Please check your credentials.', 'captain-funnel-for-whatsapp' );

		return array( 'success' => false, 'message' => $message );
	}
}
