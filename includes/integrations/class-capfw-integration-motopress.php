<?php
/**
 * MotoPress Hotel Booking integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CAPFW_Integration_MotoPress extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'motopress'; }
	public function get_label(): string    { return 'MotoPress Booking'; }
	public function get_category(): string { return 'Booking Systems'; }
	public function get_plugin_file(): string { return 'motopress-hotel-booking/motopress-hotel-booking.php'; }

	public function get_triggers(): array {
		$vars = array(
			'{customer_name}'  => __( 'Guest full name', 'captain-funnel-for-whatsapp' ),
			'{customer_phone}' => __( 'Guest phone', 'captain-funnel-for-whatsapp' ),
			'{customer_email}' => __( 'Guest email', 'captain-funnel-for-whatsapp' ),
			'{accommodation}'  => __( 'Accommodation / room name', 'captain-funnel-for-whatsapp' ),
			'{check_in}'       => __( 'Check-in date', 'captain-funnel-for-whatsapp' ),
			'{check_out}'      => __( 'Check-out date', 'captain-funnel-for-whatsapp' ),
			'{booking_total}'  => __( 'Booking total price', 'captain-funnel-for-whatsapp' ),
			'{site_name}'      => __( 'Website name', 'captain-funnel-for-whatsapp' ),
		);

		return array(
			'mphb_booking_confirmed' => array( 'label' => __( 'Booking Confirmed', 'captain-funnel-for-whatsapp' ), 'description' => __( 'Fires when MotoPress booking is confirmed.', 'captain-funnel-for-whatsapp' ), 'variables' => $vars ),
			'mphb_booking_cancelled' => array( 'label' => __( 'Booking Cancelled', 'captain-funnel-for-whatsapp' ), 'description' => __( 'Fires when MotoPress booking is cancelled.', 'captain-funnel-for-whatsapp' ), 'variables' => $vars ),
		);
	}

	public function register_hooks(): void {
		add_action( 'mphb_booking_confirmed', array( $this, 'on_confirmed' ) );
		add_action( 'mphb_booking_cancelled', array( $this, 'on_cancelled' ) );
	}

	public function on_confirmed( $booking ): void { $this->handle( $booking, 'mphb_booking_confirmed' ); }
	public function on_cancelled( $booking ): void { $this->handle( $booking, 'mphb_booking_cancelled' ); }

	private function handle( $booking, string $trigger_key ): void {
		if ( ! method_exists( $booking, 'getCustomer' ) ) { return; }
		$customer = $booking->getCustomer();
		$phone    = preg_replace( '/[^0-9]/', '', method_exists( $customer, 'getPhone' ) ? $customer->getPhone() : '' );

		$reserved = $booking->getReservedAccommodations();
		$acc_name = '';
		if ( ! empty( $reserved ) ) {
			$first    = reset( $reserved );
			$acc_name = $first->getAccommodationType() ? $first->getAccommodationType()->getTitle() : '';
		}

		$vars = array(
			'{customer_name}'  => sanitize_text_field( $customer->getName() ?? '' ),
			'{customer_phone}' => $phone,
			'{customer_email}' => sanitize_email( method_exists( $customer, 'getEmail' ) ? $customer->getEmail() : '' ),
			'{accommodation}'  => sanitize_text_field( $acc_name ),
			'{check_in}'       => sanitize_text_field( $booking->getCheckInDate() ? $booking->getCheckInDate()->format( 'Y-m-d' ) : '' ),
			'{check_out}'      => sanitize_text_field( $booking->getCheckOutDate() ? $booking->getCheckOutDate()->format( 'Y-m-d' ) : '' ),
			'{booking_total}'  => sanitize_text_field( (string) $booking->getTotalPrice() ),
		);

		if ( empty( $phone ) ) { return; }
		$this->fire_trigger( $trigger_key, $phone, $vars );
	}
}
