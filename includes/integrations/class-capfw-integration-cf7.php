<?php
/**
 * Contact Form 7 integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Integration_CF7
 */
class CAPFW_Integration_CF7 extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'cf7'; }
	public function get_label(): string    { return 'Contact Form 7'; }
	public function get_category(): string { return 'Form Submissions'; }
	public function get_plugin_file(): string { return 'contact-form-7/wp-contact-form-7.php'; }

	public function get_triggers(): array {
		$vars = array(
			'{form_name}'   => __( 'Form title', 'captain-funnel-for-whatsapp' ),
			'{form_id}'     => __( 'CF7 form ID', 'captain-funnel-for-whatsapp' ),
			'{sender_name}' => __( 'your-name field value', 'captain-funnel-for-whatsapp' ),
			'{sender_email}'=> __( 'your-email field value', 'captain-funnel-for-whatsapp' ),
			'{sender_phone}'=> __( 'your-phone / tel field value', 'captain-funnel-for-whatsapp' ),
			'{message}'     => __( 'your-message field value', 'captain-funnel-for-whatsapp' ),
			'{site_name}'   => __( 'Website name', 'captain-funnel-for-whatsapp' ),
		);

		return array(
			'cf7_mail_sent'   => array( 'label' => __( 'Form Submitted (Mail Sent)', 'captain-funnel-for-whatsapp' ),   'description' => __( 'Fires after CF7 successfully sends its mail.', 'captain-funnel-for-whatsapp' ),   'variables' => $vars ),
			'cf7_mail_failed' => array( 'label' => __( 'Form Submitted (Mail Failed)', 'captain-funnel-for-whatsapp' ), 'description' => __( 'Fires when CF7 form is submitted but mail fails.', 'captain-funnel-for-whatsapp' ), 'variables' => $vars ),
		);
	}

	public function register_hooks(): void {
		add_action( 'wpcf7_mail_sent',   array( $this, 'on_mail_sent' ) );
		add_action( 'wpcf7_mail_failed', array( $this, 'on_mail_failed' ) );
	}

	public function on_mail_sent( WPCF7_ContactForm $cf7 ): void {
		$this->handle( $cf7, 'cf7_mail_sent' );
	}

	public function on_mail_failed( WPCF7_ContactForm $cf7 ): void {
		$this->handle( $cf7, 'cf7_mail_failed' );
	}

	private function handle( WPCF7_ContactForm $cf7, string $trigger_key ): void {
		$submission = WPCF7_Submission::get_instance();
		if ( ! $submission ) {
			return;
		}

		$posted = $submission->get_posted_data();
		$vars   = $this->build_variables( $cf7, $posted );

		// Get phone from submitted data — check common field names.
		$phone = $this->extract_phone( $posted );
		if ( empty( $phone ) ) {
			// No phone in form — notify admin instead.
			$admin_msg = sprintf(
				/* translators: %1$s: form name, %2$s: sender name */
				__( 'New form submission from %1$s on form "%2$s".', 'captain-funnel-for-whatsapp' ),
				$vars['{sender_name}'],
				$vars['{form_name}']
			);
			$this->notify_admin( $admin_msg );
			return;
		}

		$this->fire_trigger( $trigger_key, $phone, $vars );
	}

	private function build_variables( WPCF7_ContactForm $cf7, array $posted ): array {
		return array(
			'{form_name}'    => $cf7->title(),
			'{form_id}'      => (string) $cf7->id(),
			'{sender_name}'  => sanitize_text_field( $posted['your-name'] ?? $posted['name'] ?? '' ),
			'{sender_email}' => sanitize_email( $posted['your-email'] ?? $posted['email'] ?? '' ),
			'{sender_phone}' => sanitize_text_field( $posted['your-phone'] ?? $posted['phone'] ?? $posted['tel'] ?? '' ),
			'{message}'      => sanitize_textarea_field( $posted['your-message'] ?? $posted['message'] ?? '' ),
		);
	}

	private function extract_phone( array $posted ): string {
		$phone_keys = array( 'your-phone', 'phone', 'tel', 'mobile', 'whatsapp', 'phone-number' );
		foreach ( $phone_keys as $key ) {
			if ( ! empty( $posted[ $key ] ) ) {
				return preg_replace( '/[^0-9]/', '', $posted[ $key ] );
			}
		}
		return '';
	}
}
