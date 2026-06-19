<?php
/**
 * Abstract base class for all CAPFW integrations.
 *
 * Every integration extends this class and implements the required methods.
 * The registry loads only integrations whose plugin is actually active.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Class CAPFW_Integration_Base
 */
abstract class CAPFW_Integration_Base {

	/**
	 * Unique slug, e.g. 'woocommerce', 'cf7', 'learndash'.
	 *
	 * @return string
	 */
	abstract public function get_slug(): string;

	/**
	 * Human-readable label shown in the UI.
	 *
	 * @return string
	 */
	abstract public function get_label(): string;

	/**
	 * Category label shown in UI grouping.
	 *
	 * @return string
	 */
	abstract public function get_category(): string;

	/**
	 * Return the main plugin file path relative to plugins dir.
	 * Used by registry to check is_plugin_active().
	 * Return empty string for WordPress core integrations (always active).
	 *
	 * @return string e.g. 'woocommerce/woocommerce.php'
	 */
	abstract public function get_plugin_file(): string;

	/**
	 * Return all triggers this integration provides.
	 *
	 * Format:
	 * [
	 *   'trigger_key' => [
	 *     'label'       => 'Human label',
	 *     'description' => 'When this fires',
	 *     'variables'   => [ '{var}' => 'Description', ... ],
	 *   ],
	 * ]
	 *
	 * @return array
	 */
	abstract public function get_triggers(): array;

	/**
	 * Register WordPress hooks for this integration.
	 * Called only when the integration's plugin is active.
	 */
	abstract public function register_hooks(): void;

	/**
	 * Check if the required plugin is active.
	 *
	 * @return bool
	 */
	public function is_available(): bool {
		$plugin_file = $this->get_plugin_file();

		// WordPress core events — always available.
		if ( empty( $plugin_file ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin_file );
	}

	/**
	 * Fire a trigger: resolve template → send WhatsApp → log it.
	 *
	 * @param string $trigger_key  Trigger key, e.g. 'order_completed'.
	 * @param string $phone        Recipient phone in E.164 format.
	 * @param array  $variables    Key-value pairs for template replacement.
	 * @param int    $ref_id       Optional reference ID (order ID, form entry ID, etc.).
	 */
	protected function fire_trigger( string $trigger_key, string $phone, array $variables, int $ref_id = 0 ): void {
		if ( empty( $phone ) ) {
			return;
		}

		// Sanitize phone — digits only.
		$phone = preg_replace( '/[^0-9]/', '', $phone );
		if ( empty( $phone ) ) {
			return;
		}

		$template = $this->get_template( $trigger_key );
		if ( empty( $template ) ) {
			return;
		}

		$message = $this->parse_variables( $template, $variables );
		$result  = CAPFW_WhatsApp_API::send_message( $phone, $message );

		CAPFW_Logger::log(
			array(
				'order_id'         => $ref_id,
				'customer_phone'   => $phone,
				'message'          => $message,
				'status'           => $result['success'] ? 'sent' : 'failed',
				'response'         => $result['response'],
				'integration_slug' => $this->get_slug(),
				'trigger_key'      => $trigger_key,
			)
		);

		// Dispatch to funnel runner.
		do_action( 'capfw_trigger_fired', $this->get_slug(), $trigger_key, $variables, $ref_id );
	}

	/**
	 * Retrieve saved template for this integration + trigger.
	 *
	 * @param string $trigger_key Trigger key.
	 * @return string
	 */
	protected function get_template( string $trigger_key ): string {
		global $wpdb;
		$table = $wpdb->prefix . 'capfw_templates';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT template_body FROM {$table} WHERE integration_slug = %s AND trigger_key = %s AND status = 'active' LIMIT 1",
				$this->get_slug(),
				$trigger_key
			)
		);

		return $row ? sanitize_textarea_field( $row->template_body ) : '';
	}

	/**
	 * Replace template variable placeholders with actual values.
	 *
	 * @param string $template  Template string with {variable} placeholders.
	 * @param array  $variables Key-value map: [ '{var}' => 'value' ].
	 * @return string
	 */
	protected function parse_variables( string $template, array $variables ): string {
		// Always inject site name.
		$variables['{site_name}'] = get_bloginfo( 'name' );
		$variables['{site_url}']  = home_url();

		return str_replace(
			array_keys( $variables ),
			array_values( $variables ),
			$template
		);
	}

	/**
	 * Send admin notification WhatsApp message.
	 *
	 * @param string $message Message body.
	 */
	protected function notify_admin( string $message ): void {
		$settings    = (array) get_option( 'capfw_settings', array() );
		$admin_phone = sanitize_text_field( $settings['admin_phone'] ?? '' );

		if ( empty( $admin_phone ) ) {
			return;
		}

		$phone = preg_replace( '/[^0-9]/', '', $admin_phone );
		if ( empty( $phone ) ) {
			return;
		}

		$result = CAPFW_WhatsApp_API::send_message( $phone, $message );

		CAPFW_Logger::log(
			array(
				'order_id'         => 0,
				'customer_phone'   => $phone,
				'message'          => $message,
				'status'           => $result['success'] ? 'sent' : 'failed',
				'response'         => $result['response'],
				'integration_slug' => $this->get_slug(),
				'trigger_key'      => 'admin_notification',
			)
		);
	}
}
