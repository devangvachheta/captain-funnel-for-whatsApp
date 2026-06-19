<?php
/**
 * Easy Digital Downloads integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Integration_EDD
 */
class CAPFW_Integration_EDD extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'edd'; }
	public function get_label(): string    { return 'Easy Digital Downloads'; }
	public function get_category(): string { return 'E-commerce'; }
	public function get_plugin_file(): string { return 'easy-digital-downloads/easy-digital-downloads.php'; }

	public function get_triggers(): array {
		$vars = array(
			'{customer_name}'  => __( 'Customer full name', 'captain-funnel-for-whatsapp' ),
			'{customer_email}' => __( 'Customer email', 'captain-funnel-for-whatsapp' ),
			'{order_total}'    => __( 'Order total with currency', 'captain-funnel-for-whatsapp' ),
			'{payment_id}'     => __( 'EDD payment ID', 'captain-funnel-for-whatsapp' ),
			'{download_names}' => __( 'Comma-separated product names', 'captain-funnel-for-whatsapp' ),
			'{license_keys}'   => __( 'License keys if applicable', 'captain-funnel-for-whatsapp' ),
			'{site_name}'      => __( 'Website name', 'captain-funnel-for-whatsapp' ),
		);

		return array(
			'edd_purchase_complete' => array( 'label' => __( 'Purchase Complete', 'captain-funnel-for-whatsapp' ), 'description' => __( 'Fires when EDD payment is complete.', 'captain-funnel-for-whatsapp' ), 'variables' => $vars ),
			'edd_payment_failed'    => array( 'label' => __( 'Payment Failed', 'captain-funnel-for-whatsapp' ),    'description' => __( 'Fires when EDD payment fails.', 'captain-funnel-for-whatsapp' ),    'variables' => $vars ),
			'edd_payment_refunded'  => array( 'label' => __( 'Payment Refunded', 'captain-funnel-for-whatsapp' ),  'description' => __( 'Fires when EDD payment is refunded.', 'captain-funnel-for-whatsapp' ), 'variables' => $vars ),
		);
	}

	public function register_hooks(): void {
		add_action( 'edd_complete_purchase',   array( $this, 'on_purchase_complete' ) );
		add_action( 'edd_update_payment_status', array( $this, 'on_status_update' ), 10, 3 );
	}

	public function on_purchase_complete( int $payment_id ): void {
		$vars = $this->build_variables( $payment_id );
		$phone = $this->get_customer_phone( $payment_id );
		$this->fire_trigger( 'edd_purchase_complete', $phone, $vars, $payment_id );
	}

	public function on_status_update( int $payment_id, string $new_status, string $old_status ): void {
		$trigger_map = array(
			'failed'   => 'edd_payment_failed',
			'refunded' => 'edd_payment_refunded',
		);

		$trigger_key = $trigger_map[ $new_status ] ?? '';
		if ( empty( $trigger_key ) ) {
			return;
		}

		$vars  = $this->build_variables( $payment_id );
		$phone = $this->get_customer_phone( $payment_id );
		$this->fire_trigger( $trigger_key, $phone, $vars, $payment_id );
	}

	private function get_customer_phone( int $payment_id ): string {
		$phone = edd_get_payment_user_info( $payment_id )['phone'] ?? '';
		return preg_replace( '/[^0-9]/', '', $phone );
	}

	private function build_variables( int $payment_id ): array {
		$user_info = edd_get_payment_user_info( $payment_id );
		$downloads = edd_get_payment_meta_downloads( $payment_id );

		$download_names = array();
		$license_keys   = array();

		if ( is_array( $downloads ) ) {
			foreach ( $downloads as $dl ) {
				$download_names[] = get_the_title( $dl['id'] ?? 0 );
				$license = get_post_meta( $dl['id'] ?? 0, '_edd_sl_license_key', true );
				if ( $license ) {
					$license_keys[] = $license;
				}
			}
		}

		return array(
			'{customer_name}'  => trim( ( $user_info['first_name'] ?? '' ) . ' ' . ( $user_info['last_name'] ?? '' ) ),
			'{customer_email}' => $user_info['email'] ?? '',
			'{order_total}'    => edd_currency_filter( edd_format_amount( edd_get_payment_amount( $payment_id ) ) ),
			'{payment_id}'     => (string) $payment_id,
			'{download_names}' => implode( ', ', $download_names ),
			'{license_keys}'   => implode( ', ', $license_keys ),
		);
	}
}
