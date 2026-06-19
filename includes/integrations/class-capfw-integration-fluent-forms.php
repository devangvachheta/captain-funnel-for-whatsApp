<?php
/**
 * Fluent Forms integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Integration_FluentForms
 */
class CAPFW_Integration_FluentForms extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'fluent_forms'; }
	public function get_label(): string    { return 'Fluent Forms'; }
	public function get_category(): string { return 'Form Submissions'; }
	public function get_plugin_file(): string { return 'fluentform/fluentform.php'; }

	public function get_triggers(): array {
		return array(
			'fluent_form_submitted' => array(
				'label'       => __( 'Form Submitted', 'captain-funnel-for-whatsapp' ),
				'description' => __( 'Fires after a Fluent Forms submission is inserted.', 'captain-funnel-for-whatsapp' ),
				'variables'   => array(
					'{form_name}'    => __( 'Form title', 'captain-funnel-for-whatsapp' ),
					'{form_id}'      => __( 'Form ID', 'captain-funnel-for-whatsapp' ),
					'{entry_id}'     => __( 'Entry ID', 'captain-funnel-for-whatsapp' ),
					'{sender_name}'  => __( 'Submitter name', 'captain-funnel-for-whatsapp' ),
					'{sender_email}' => __( 'Submitter email', 'captain-funnel-for-whatsapp' ),
					'{sender_phone}' => __( 'Submitter phone', 'captain-funnel-for-whatsapp' ),
					'{site_name}'    => __( 'Website name', 'captain-funnel-for-whatsapp' ),
				),
			),
		);
	}

	public function register_hooks(): void {
		add_action( 'fluentform_submission_inserted', array( $this, 'on_submit' ), 10, 3 );
	}

	public function on_submit( int $entry_id, array $form_data, object $form ): void {
		$phone = preg_replace( '/[^0-9]/', '', $form_data['phone'] ?? $form_data['phone_number'] ?? $form_data['mobile'] ?? '' );

		$vars = array(
			'{form_name}'    => sanitize_text_field( $form->title ?? '' ),
			'{form_id}'      => (string) $form->id,
			'{entry_id}'     => (string) $entry_id,
			'{sender_name}'  => sanitize_text_field( $form_data['names']['first_name'] ?? $form_data['name'] ?? '' ),
			'{sender_email}' => sanitize_email( $form_data['email'] ?? '' ),
			'{sender_phone}' => sanitize_text_field( $phone ),
		);

		if ( empty( $phone ) ) {
			$this->notify_admin( sprintf( __( 'New Fluent Forms submission on "%s".', 'captain-funnel-for-whatsapp' ), $vars['{form_name}'] ) );
			return;
		}

		$this->fire_trigger( 'fluent_form_submitted', $phone, $vars, $entry_id );
	}
}
