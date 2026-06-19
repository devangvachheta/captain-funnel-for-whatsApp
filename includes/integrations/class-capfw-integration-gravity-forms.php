<?php
/**
 * Gravity Forms integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Integration_GravityForms
 */
class CAPFW_Integration_GravityForms extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'gravity_forms'; }
	public function get_label(): string    { return 'Gravity Forms'; }
	public function get_category(): string { return 'Form Submissions'; }
	public function get_plugin_file(): string { return 'gravityforms/gravityforms.php'; }

	public function get_triggers(): array {
		return array(
			'gf_form_submitted' => array(
				'label'       => __( 'Form Submitted', 'captain-funnel-for-whatsapp' ),
				'description' => __( 'Fires after Gravity Forms submission.', 'captain-funnel-for-whatsapp' ),
				'variables'   => array(
					'{form_name}'    => __( 'Form title', 'captain-funnel-for-whatsapp' ),
					'{form_id}'      => __( 'Form ID', 'captain-funnel-for-whatsapp' ),
					'{entry_id}'     => __( 'Entry ID', 'captain-funnel-for-whatsapp' ),
					'{sender_name}'  => __( 'Submitter name (Name field)', 'captain-funnel-for-whatsapp' ),
					'{sender_email}' => __( 'Submitter email', 'captain-funnel-for-whatsapp' ),
					'{sender_phone}' => __( 'Submitter phone', 'captain-funnel-for-whatsapp' ),
					'{site_name}'    => __( 'Website name', 'captain-funnel-for-whatsapp' ),
				),
			),
		);
	}

	public function register_hooks(): void {
		add_action( 'gform_after_submission', array( $this, 'on_submit' ), 10, 2 );
	}

	public function on_submit( array $entry, array $form ): void {
		$phone = '';
		$name  = '';
		$email = '';

		// Walk form fields to find phone, name, email by type.
		foreach ( $form['fields'] ?? array() as $field ) {
			$field_id    = $field->id;
			$field_type  = $field->type;
			$field_value = $entry[ $field_id ] ?? '';

			if ( 'phone' === $field_type && empty( $phone ) ) {
				$phone = preg_replace( '/[^0-9]/', '', $field_value );
			} elseif ( 'name' === $field_type && empty( $name ) ) {
				$name = trim(
					( $entry[ $field_id . '.3' ] ?? '' ) . ' ' .
					( $entry[ $field_id . '.6' ] ?? '' )
				);
			} elseif ( 'email' === $field_type && empty( $email ) ) {
				$email = $entry[ $field_id ] ?? '';
			}
		}

		$vars = array(
			'{form_name}'    => sanitize_text_field( $form['title'] ?? '' ),
			'{form_id}'      => (string) ( $form['id'] ?? '' ),
			'{entry_id}'     => (string) ( $entry['id'] ?? '' ),
			'{sender_name}'  => sanitize_text_field( $name ),
			'{sender_email}' => sanitize_email( $email ),
			'{sender_phone}' => sanitize_text_field( $phone ),
		);

		if ( empty( $phone ) ) {
			$this->notify_admin( sprintf( __( 'New Gravity Forms submission on "%s".', 'captain-funnel-for-whatsapp' ), $vars['{form_name}'] ) );
			return;
		}

		$this->fire_trigger( 'gf_form_submitted', $phone, $vars, (int) ( $entry['id'] ?? 0 ) );
	}
}
