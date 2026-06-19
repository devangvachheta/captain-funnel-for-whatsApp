<?php
/**
 * Amelia Booking integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Integration_Amelia
 */
class CAPFW_Integration_Amelia extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'amelia'; }
	public function get_label(): string    { return 'Amelia'; }
	public function get_category(): string { return 'Booking Systems'; }
	public function get_plugin_file(): string { return 'ameliabooking/ameliabooking.php'; }

	public function get_triggers(): array {
		$vars = array(
			'{customer_name}'  => __( 'Customer full name', 'captain-funnel-for-whatsapp' ),
			'{customer_phone}' => __( 'Customer phone', 'captain-funnel-for-whatsapp' ),
			'{customer_email}' => __( 'Customer email', 'captain-funnel-for-whatsapp' ),
			'{service_name}'   => __( 'Service name', 'captain-funnel-for-whatsapp' ),
			'{employee_name}'  => __( 'Employee / staff name', 'captain-funnel-for-whatsapp' ),
			'{booking_date}'   => __( 'Booking start date & time', 'captain-funnel-for-whatsapp' ),
			'{booking_status}' => __( 'Booking status', 'captain-funnel-for-whatsapp' ),
			'{site_name}'      => __( 'Website name', 'captain-funnel-for-whatsapp' ),
		);

		return array(
			'amelia_booking_added'     => array( 'label' => __( 'Booking Added', 'captain-funnel-for-whatsapp' ),     'description' => __( 'Fires when a new Amelia booking is made.', 'captain-funnel-for-whatsapp' ),           'variables' => $vars ),
			'amelia_booking_approved'  => array( 'label' => __( 'Booking Approved', 'captain-funnel-for-whatsapp' ),  'description' => __( 'Fires when Amelia booking is approved.', 'captain-funnel-for-whatsapp' ),           'variables' => $vars ),
			'amelia_booking_cancelled' => array( 'label' => __( 'Booking Cancelled', 'captain-funnel-for-whatsapp' ), 'description' => __( 'Fires when Amelia booking is cancelled.', 'captain-funnel-for-whatsapp' ),          'variables' => $vars ),
			'amelia_booking_rejected'  => array( 'label' => __( 'Booking Rejected', 'captain-funnel-for-whatsapp' ),  'description' => __( 'Fires when Amelia booking is rejected.', 'captain-funnel-for-whatsapp' ),           'variables' => $vars ),
		);
	}

	public function register_hooks(): void {
		add_action( 'amelia_booking_added',     array( $this, 'on_booking_event' ), 10, 2 );
		add_action( 'amelia_booking_approved',  array( $this, 'on_booking_event' ), 10, 2 );
		add_action( 'amelia_booking_cancelled', array( $this, 'on_booking_event' ), 10, 2 );
		add_action( 'amelia_booking_rejected',  array( $this, 'on_booking_event' ), 10, 2 );
	}

	public function on_booking_event( array $booking, array $appointment ): void {
		$current_filter = current_filter();
		$trigger_key    = str_replace( 'amelia_', 'amelia_booking_', '' );
		$trigger_key    = $current_filter; // Use the hook name directly as trigger key.

		$phone = preg_replace( '/[^0-9]/', '', $booking['phone'] ?? '' );
		$vars  = array(
			'{customer_name}'  => sanitize_text_field( trim( ( $booking['firstName'] ?? '' ) . ' ' . ( $booking['lastName'] ?? '' ) ) ),
			'{customer_phone}' => $phone,
			'{customer_email}' => sanitize_email( $booking['info']['email'] ?? '' ),
			'{service_name}'   => sanitize_text_field( $appointment['service']['name'] ?? '' ),
			'{employee_name}'  => sanitize_text_field( trim( ( $appointment['employee']['firstName'] ?? '' ) . ' ' . ( $appointment['employee']['lastName'] ?? '' ) ) ),
			'{booking_date}'   => sanitize_text_field( $appointment['bookingStart'] ?? '' ),
			'{booking_status}' => sanitize_text_field( $booking['status'] ?? '' ),
		);

		if ( empty( $phone ) ) {
			return;
		}

		$this->fire_trigger( $current_filter, $phone, $vars );
	}
}
