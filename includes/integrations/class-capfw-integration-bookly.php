<?php
/**
 * Bookly integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Integration_Bookly
 */
class CAPFW_Integration_Bookly extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'bookly'; }
	public function get_label(): string    { return 'Bookly'; }
	public function get_category(): string { return 'Booking Systems'; }
	public function get_plugin_file(): string { return 'bookly-responsive-appointment-booking-tool/main.php'; }

	public function get_triggers(): array {
		$vars = $this->get_booking_vars();
		return array(
			'bookly_appointment_created'   => array( 'label' => __( 'Appointment Created', 'captain-funnel-for-whatsapp' ),   'description' => __( 'Fires when a Bookly appointment is created.', 'captain-funnel-for-whatsapp' ),   'variables' => $vars ),
			'bookly_appointment_confirmed' => array( 'label' => __( 'Appointment Confirmed', 'captain-funnel-for-whatsapp' ), 'description' => __( 'Fires when appointment status becomes approved.', 'captain-funnel-for-whatsapp' ), 'variables' => $vars ),
			'bookly_appointment_cancelled' => array( 'label' => __( 'Appointment Cancelled', 'captain-funnel-for-whatsapp' ), 'description' => __( 'Fires when a Bookly appointment is cancelled.', 'captain-funnel-for-whatsapp' ), 'variables' => $vars ),
		);
	}

	public function register_hooks(): void {
		add_action( 'bookly_appointment_created',   array( $this, 'on_created' ) );
		add_action( 'bookly_appointment_status_changed', array( $this, 'on_status_changed' ), 10, 2 );
	}

	public function on_created( $appointment ): void {
		$this->handle( $appointment, 'bookly_appointment_created' );
	}

	public function on_status_changed( $appointment, string $status ): void {
		$map = array(
			'approved'  => 'bookly_appointment_confirmed',
			'cancelled' => 'bookly_appointment_cancelled',
		);
		$trigger = $map[ $status ] ?? '';
		if ( $trigger ) {
			$this->handle( $appointment, $trigger );
		}
	}

	private function handle( $appointment, string $trigger_key ): void {
		if ( ! is_object( $appointment ) ) {
			return;
		}

		$phone = preg_replace( '/[^0-9]/', '', $appointment->getPhone() ?? '' );
		$vars  = array(
			'{customer_name}'  => sanitize_text_field( $appointment->getCustomerFullName() ?? '' ),
			'{customer_phone}' => $phone,
			'{customer_email}' => sanitize_email( $appointment->getCustomerEmail() ?? '' ),
			'{service_name}'   => sanitize_text_field( $appointment->getServiceTitle() ?? '' ),
			'{staff_name}'     => sanitize_text_field( $appointment->getStaffFullName() ?? '' ),
			'{booking_date}'   => sanitize_text_field( $appointment->getStartDate() ?? '' ),
			'{booking_time}'   => sanitize_text_field( $appointment->getStartTime() ?? '' ),
			'{booking_status}' => sanitize_text_field( $appointment->getStatus() ?? '' ),
		);

		if ( empty( $phone ) ) {
			return;
		}

		$this->fire_trigger( $trigger_key, $phone, $vars );
	}

	private function get_booking_vars(): array {
		return array(
			'{customer_name}'  => __( 'Customer full name', 'captain-funnel-for-whatsapp' ),
			'{customer_phone}' => __( 'Customer phone', 'captain-funnel-for-whatsapp' ),
			'{customer_email}' => __( 'Customer email', 'captain-funnel-for-whatsapp' ),
			'{service_name}'   => __( 'Booked service name', 'captain-funnel-for-whatsapp' ),
			'{staff_name}'     => __( 'Staff / provider name', 'captain-funnel-for-whatsapp' ),
			'{booking_date}'   => __( 'Appointment date', 'captain-funnel-for-whatsapp' ),
			'{booking_time}'   => __( 'Appointment time', 'captain-funnel-for-whatsapp' ),
			'{booking_status}' => __( 'Appointment status', 'captain-funnel-for-whatsapp' ),
			'{site_name}'      => __( 'Website name', 'captain-funnel-for-whatsapp' ),
		);
	}
}
