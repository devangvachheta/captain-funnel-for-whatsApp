<?php
/**
 * WooCommerce order hooks — triggers WhatsApp notifications.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Order_Hooks
 */
class CAPFW_Order_Hooks {

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_order_status_changed' ), 10, 4 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'on_order_created' ), 10, 1 );
	}

	/**
	 * Fires when an order status changes.
	 *
	 * @param int      $order_id    Order ID.
	 * @param string   $old_status  Previous status.
	 * @param string   $new_status  New status.
	 * @param WC_Order $order       Order object.
	 */
	public function on_order_status_changed( int $order_id, string $old_status, string $new_status, WC_Order $order ) {
		$settings = (array) get_option( 'capfw_settings', array() );

		// Check if notifications are enabled for this status.
		$enabled_statuses = $settings['enabled_statuses'] ?? array();
		if ( ! in_array( $new_status, (array) $enabled_statuses, true ) ) {
			return;
		}

		$phone = $this->get_customer_phone( $order );
		if ( empty( $phone ) ) {
			return;
		}

		$template_key = 'template_' . sanitize_key( $new_status );
		$template     = $settings[ $template_key ] ?? '';

		if ( empty( $template ) ) {
			return;
		}

		$message = $this->parse_template( $template, $order );
		$result  = CAPFW_WhatsApp_API::send_message( $phone, $message );

		CAPFW_Logger::log(
			array(
				'order_id'       => $order_id,
				'customer_phone' => $phone,
				'message'        => $message,
				'status'         => $result['success'] ? 'sent' : 'failed',
				'response'       => $result['response'],
			)
		);

		// Trigger funnel runner for completed orders.
		if ( 'completed' === $new_status ) {
			do_action( 'capfw_order_completed_trigger', $order_id, $order );
		}
	}

	/**
	 * Fires when a new order is created at checkout.
	 *
	 * @param int $order_id Order ID.
	 */
	public function on_order_created( int $order_id ) {
		do_action( 'capfw_order_created_trigger', $order_id );
	}

	/**
	 * Replace template variables with real order data.
	 *
	 * @param string   $template Message template string.
	 * @param WC_Order $order    WooCommerce order object.
	 * @return string
	 */
	public function parse_template( string $template, WC_Order $order ): string {
		// FIX High #4: Added {coupon_code}, {tracking_number}, {delivery_date}.
		$replacements = array(
			'{customer_name}'   => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			'{order_number}'    => $order->get_order_number(),
			'{order_total}'     => wp_strip_all_tags( wc_price( $order->get_total() ) ),
			'{order_status}'    => wc_get_order_status_name( $order->get_status() ),
			'{customer_phone}'  => $order->get_billing_phone(),
			'{store_name}'      => get_bloginfo( 'name' ),
			'{coupon_code}'     => implode( ', ', $order->get_coupon_codes() ),
			'{tracking_number}' => get_post_meta( $order->get_id(), '_tracking_number', true ) ?: '',
			'{delivery_date}'   => get_post_meta( $order->get_id(), '_delivery_date', true ) ?: '',
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
	}

	/**
	 * Get customer phone from order, normalized for WhatsApp API (E.164).
	 *
	 * @param WC_Order $order Order object.
	 * @return string
	 */
	private function get_customer_phone( WC_Order $order ): string {
		$phone = $order->get_billing_phone();
		if ( empty( $phone ) ) {
			return '';
		}
		// Strip non-numeric characters.
		return preg_replace( '/[^0-9]/', '', $phone );
	}
}
