<?php
/**
 * WPForms integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Integration_WPForms
 */
class CAPFW_Integration_WPForms extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'wpforms'; }
	public function get_label(): string    { return 'WPForms'; }
	public function get_category(): string { return 'Form Submissions'; }
	public function get_plugin_file(): string { return 'wpforms-lite/wpforms.php'; }

	public function get_triggers(): array {
		return array(
			'wpforms_submitted' => array(
				'label'       => __( 'Form Submitted', 'captain-funnel-for-whatsapp' ),
				'description' => __( 'Fires when any WPForms form is submitted.', 'captain-funnel-for-whatsapp' ),
				'variables'   => $this->get_common_form_vars(),
			),
		);
	}

	public function register_hooks(): void {
		add_action( 'wpforms_process_complete', array( $this, 'on_submit' ), 10, 4 );
	}

	public function on_submit( array $fields, array $entry, array $form_data, int $entry_id ): void {
		$phone = $this->extract_phone_from_fields( $fields );
		$vars  = array(
			'{form_name}'  => sanitize_text_field( $form_data['settings']['form_title'] ?? '' ),
			'{form_id}'    => (string) ( $form_data['id'] ?? '' ),
			'{entry_id}'   => (string) $entry_id,
			'{sender_name}'  => sanitize_text_field( $this->find_field( $fields, array( 'name', 'first-name', 'full-name' ) ) ),
			'{sender_email}' => sanitize_email( $this->find_field( $fields, array( 'email' ) ) ),
			'{sender_phone}' => sanitize_text_field( $phone ),
		);

		if ( empty( $phone ) ) {
			$this->notify_admin( sprintf( __( 'New WPForms submission on "%s".', 'captain-funnel-for-whatsapp' ), $vars['{form_name}'] ) );
			return;
		}

		$this->fire_trigger( 'wpforms_submitted', $phone, $vars, $entry_id );
	}

	private function extract_phone_from_fields( array $fields ): string {
		$phone_types = array( 'phone', 'tel' );
		foreach ( $fields as $field ) {
			if ( in_array( $field['type'] ?? '', $phone_types, true ) && ! empty( $field['value'] ) ) {
				return preg_replace( '/[^0-9]/', '', $field['value'] );
			}
		}
		return '';
	}

	private function find_field( array $fields, array $slugs ): string {
		foreach ( $fields as $field ) {
			if ( in_array( strtolower( $field['name'] ?? '' ), $slugs, true ) ) {
				return $field['value'] ?? '';
			}
		}
		return '';
	}

	private function get_common_form_vars(): array {
		return array(
			'{form_name}'    => __( 'Form title', 'captain-funnel-for-whatsapp' ),
			'{form_id}'      => __( 'Form ID', 'captain-funnel-for-whatsapp' ),
			'{entry_id}'     => __( 'Submission entry ID', 'captain-funnel-for-whatsapp' ),
			'{sender_name}'  => __( 'Submitter name', 'captain-funnel-for-whatsapp' ),
			'{sender_email}' => __( 'Submitter email', 'captain-funnel-for-whatsapp' ),
			'{sender_phone}' => __( 'Submitter phone', 'captain-funnel-for-whatsapp' ),
			'{site_name}'    => __( 'Website name', 'captain-funnel-for-whatsapp' ),
		);
	}
}
