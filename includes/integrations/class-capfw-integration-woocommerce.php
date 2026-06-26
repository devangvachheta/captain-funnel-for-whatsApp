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
		// ── Hook 1: Classic hook — fires on any status change (legacy + HPOS)
		add_action( 'woocommerce_order_status_changed', array( $this, 'on_status_changed' ), 10, 4 );

		// ── Hook 2: Per-status hooks — more reliable in HPOS + block checkout
		// woocommerce_order_status_{status} fires with ( order_id, order )
		add_action( 'woocommerce_order_status_pending',    array( $this, 'on_to_pending' ),    10, 2 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'on_to_processing' ), 10, 2 );
		add_action( 'woocommerce_order_status_on-hold',    array( $this, 'on_to_on_hold' ),    10, 2 );
		add_action( 'woocommerce_order_status_completed',  array( $this, 'on_to_completed' ),  10, 2 );
		add_action( 'woocommerce_order_status_cancelled',  array( $this, 'on_to_cancelled' ),  10, 2 );
		add_action( 'woocommerce_order_status_refunded',   array( $this, 'on_to_refunded' ),   10, 2 );
		add_action( 'woocommerce_order_status_failed',     array( $this, 'on_to_failed' ),     10, 2 );

		// ── Hook 3: Block/Store API checkout — passes WC_Order object (not int)
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'on_store_api_order' ), 10, 1 );

		// ── Hook 4: Classic checkout — passes order ID as int
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'on_classic_checkout_order' ), 10, 1 );
	}

	// ── Hook callbacks ────────────────────────────────────────────────────────

	/**
	 * Hook 1: woocommerce_order_status_changed
	 * Passes ( order_id, old_status, new_status, order )
	 */
	public function on_status_changed( int $order_id, string $old_status, string $new_status, WC_Order $order ): void {
		$this->handle_status( $order_id, $new_status, $order );
	}

	/**
	 * Hook 2: per-status — woocommerce_order_status_{status}
	 * Passes ( order_id, order ) — both are safe to use
	 */
	public function on_to_pending( int $order_id, $order = null ): void    { $this->safe_handle( $order_id, 'pending', $order ); }
	public function on_to_processing( int $order_id, $order = null ): void { $this->safe_handle( $order_id, 'processing', $order ); }
	public function on_to_on_hold( int $order_id, $order = null ): void    { $this->safe_handle( $order_id, 'on-hold', $order ); }
	public function on_to_completed( int $order_id, $order = null ): void  { $this->safe_handle( $order_id, 'completed', $order ); }
	public function on_to_cancelled( int $order_id, $order = null ): void  { $this->safe_handle( $order_id, 'cancelled', $order ); }
	public function on_to_refunded( int $order_id, $order = null ): void   { $this->safe_handle( $order_id, 'refunded', $order ); }
	public function on_to_failed( int $order_id, $order = null ): void     { $this->safe_handle( $order_id, 'failed', $order ); }

	/**
	 * Hook 3: woocommerce_store_api_checkout_order_processed
	 * Block checkout — passes WC_Order object directly (NOT int)
	 */
	public function on_store_api_order( $order ): void {
		if ( ! is_a( $order, 'WC_Abstract_Order' ) ) {
			return;
		}
		$this->handle_status( $order->get_id(), $order->get_status(), $order );
	}

	/**
	 * Hook 4: woocommerce_checkout_order_processed
	 * Classic checkout — passes int order_id
	 */
	public function on_classic_checkout_order( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order ) {
			$this->handle_status( $order_id, $order->get_status(), $order );
		}
	}

	// ── Core logic ────────────────────────────────────────────────────────────

	/**
	 * Safe wrapper — fetches order object if not provided or wrong type.
	 */
	private function safe_handle( int $order_id, string $status, $order = null ): void {
		if ( ! is_a( $order, 'WC_Abstract_Order' ) ) {
			$order = wc_get_order( $order_id );
		}
		if ( is_a( $order, 'WC_Abstract_Order' ) ) {
			$this->handle_status( $order_id, $status, $order );
		} else {
		}
	}

	/**
	 * Central handler — deduplicates and fires the trigger.
	 */
	private function handle_status( int $order_id, string $new_status, $order ): void {
		// Deduplicate: both hooks may fire for same order+status in one request
		static $fired = array();
		$key = $order_id . '_' . $new_status;
		if ( isset( $fired[ $key ] ) ) {
			return;
		}
		$fired[ $key ] = true;

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
		do_action( 'capfw_order_completed_trigger', $order_id, $order );
	}

	/**
	 * Build WooCommerce variable map.
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
