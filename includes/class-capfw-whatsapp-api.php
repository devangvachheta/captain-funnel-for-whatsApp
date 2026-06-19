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
	const API_BASE = 'https://graph.facebook.com/v19.0/';

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
	 * Send a text message via WhatsApp Cloud API.
	 *
	 * @param string $to      Recipient phone (E.164 format, e.g. 919876543210).
	 * @param string $message Plain text message.
	 * @return array          Response array with 'success' bool and 'response' string.
	 */
	public static function send_message( string $to, string $message ): array {
		$token           = self::get_token();
		$phone_number_id = self::get_phone_number_id();

		if ( empty( $token ) || empty( $phone_number_id ) ) {
			return array(
				'success'  => false,
				'response' => __( 'WhatsApp API credentials are not configured.', 'captain-funnel-for-whatsapp' ),
			);
		}

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

		$code         = wp_remote_retrieve_response_code( $response );
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
	 * FIX Critical #3: Test with explicitly provided credentials (live form values).
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

		$endpoint = self::API_BASE . $phone_number_id . '?fields=display_phone_number,verified_name';

		$response = wp_remote_get(
			$endpoint,
			array(
				'headers' => array( 'Authorization' => 'Bearer ' . $token ),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => $response->get_error_message() );
		}

		if ( 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
			return array( 'success' => true,  'message' => __( 'Connection successful! WhatsApp API is working.', 'captain-funnel-for-whatsapp' ) );
		}

		return array( 'success' => false, 'message' => __( 'Connection failed. Please check your credentials.', 'captain-funnel-for-whatsapp' ) );
	}
}
