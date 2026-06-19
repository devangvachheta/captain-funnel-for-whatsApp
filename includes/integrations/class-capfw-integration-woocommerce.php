<?php
/**
 * WooCommerce integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Integration_WooCommerce
 */
class CAPFW_Integration_WooCommerce extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'woocommerce'; }
	public function get_label(): string    { return 'WooCommerce'; }
	public function get_category(): string { return 'E-commerce'; }
	public function get_plugin_file(): string { return 'woocommerce/woocommerce.php'; }

	public function get_triggers(): array {
		$vars = $this->get_wc_variables();
		return array(
			'wc_order_pending'    => array( 'label' => __( 'Order Pending', 'captain-funnel-for-whatsapp' ),    'description' => __( 'Order placed, awaiting payment.', 'captain-funnel-for-whatsapp' ),         'variables' => $vars ),
			'wc_order_processing' => array( 'label' => __( 'Order Processing', 'captain-funnel-for-whatsapp' ), 'description' => __( 'Payment received, order processing.', 'captain-funnel-for-whatsapp' ),    'variables' => $vars ),
			'wc_order_on_hold'    => array( 'label' => __( 'Order On Hold', 'captain-funnel-for-whatsapp' ),    'description' => __( 'Order on hold, awaiting action.', 'captain-funnel-for-whatsapp' ),        'variables' => $vars ),
			'wc_order_completed'  => array( 'label' => __( 'Order Completed', 'captain-funnel-for-whatsapp' ),  'description' => __( 'Order fulfilled and complete.', 'captain-funnel-for-whatsapp' ),          'variables' => $vars ),
			'wc_order_cancelled'  => array( 'label' => __( 'Order Cancelled', 'captain-funnel-for-whatsapp' ),  'description' => __( 'Order cancelled by admin or customer.', 'captain-funnel-for-whatsapp' ), 'variables' => $vars ),
			'wc_order_refunded'   => array( 'label' => __( 'Order Refunded', 'captain-funnel-for-whatsapp' ),   'description' => __( 'Order fully refunded.', 'captain-funnel-for-whatsapp' ),                  'variables' => $vars ),
			'wc_order_failed'     => array( 'label' => __( 'Order Failed', 'captain-funnel-for-whatsapp' ),     'description' => __( 'Payment failed or declined.', 'captain-funnel-for-whatsapp' ),           'variables' => $vars ),
		);
	}

	public function register_hooks(): void {
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_status_changed' ), 10, 4 );
	}

	public function on_status_changed( int $order_id, string $old_status, string $new_status, WC_Order $order ): void {
		$trigger_map = array(
			'pending'    => 'wc_order_pending',
			'processing' => 'wc_order_processing',
			'on-hold'    => 'wc_order_on_hold',
			'completed'  => 'wc_order_completed',
			'cancelled'  => 'wc_order_cancelled',
			'refunded'   => 'wc_order_refunded',
			'failed'     => 'wc_order_failed',
		);

		$trigger_key = $trigger_map[ $new_status ] ?? '';
		if ( empty( $trigger_key ) ) {
			return;
		}

		$phone = preg_replace( '/[^0-9]/', '', $order->get_billing_phone() );
		$this->fire_trigger( $trigger_key, $phone, $this->build_variables( $order ), $order_id );

		// Fire funnel runner.
		do_action( 'capfw_order_completed_trigger', $order_id, $order );
	}

	/**
	 * Build WooCommerce variable map.
	 *
	 * @param WC_Order $order Order object.
	 * @return array
	 */
	public function build_variables( WC_Order $order ): array {
		return array(
			'{customer_name}'   => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			'{order_number}'    => $order->get_order_number(),
			'{order_total}'     => wp_strip_all_tags( wc_price( $order->get_total() ) ),
			'{order_status}'    => wc_get_order_status_name( $order->get_status() ),
			'{customer_phone}'  => $order->get_billing_phone(),
			'{customer_email}'  => $order->get_billing_email(),
			'{coupon_code}'     => implode( ', ', $order->get_coupon_codes() ),
			'{tracking_number}' => (string) get_post_meta( $order->get_id(), '_tracking_number', true ),
			'{delivery_date}'   => (string) get_post_meta( $order->get_id(), '_delivery_date', true ),
			'{billing_address}' => $order->get_formatted_billing_address(),
		);
	}

	/**
	 * WooCommerce variable definitions for UI.
	 *
	 * @return array
	 */
	private function get_wc_variables(): array {
		return array(
			'{customer_name}'   => __( 'Full customer name', 'captain-funnel-for-whatsapp' ),
			'{order_number}'    => __( 'WooCommerce order number', 'captain-funnel-for-whatsapp' ),
			'{order_total}'     => __( 'Formatted order total with currency', 'captain-funnel-for-whatsapp' ),
			'{order_status}'    => __( 'Current order status label', 'captain-funnel-for-whatsapp' ),
			'{customer_phone}'  => __( 'Customer billing phone', 'captain-funnel-for-whatsapp' ),
			'{customer_email}'  => __( 'Customer email address', 'captain-funnel-for-whatsapp' ),
			'{coupon_code}'     => __( 'Applied coupon codes', 'captain-funnel-for-whatsapp' ),
			'{tracking_number}' => __( 'Shipment tracking number (if set)', 'captain-funnel-for-whatsapp' ),
			'{delivery_date}'   => __( 'Delivery date (if set)', 'captain-funnel-for-whatsapp' ),
			'{billing_address}' => __( 'Formatted billing address', 'captain-funnel-for-whatsapp' ),
			'{site_name}'       => __( 'Your website name', 'captain-funnel-for-whatsapp' ),
		);
	}
}
