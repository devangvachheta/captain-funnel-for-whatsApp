<?php
/**
 * Elementor (Pro) Forms integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Integration_Elementor
 */
class CAPFW_Integration_Elementor extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'elementor_forms'; }
	public function get_label(): string    { return 'Elementor Forms'; }
	public function get_category(): string { return 'Form Submissions'; }
	public function get_plugin_file(): string { return 'elementor-pro/elementor-pro.php'; }

	public function get_triggers(): array {
		return array(
			'elementor_form_submitted' => array(
				'label'       => __( 'Form Submitted', 'captain-funnel-for-whatsapp' ),
				'description' => __( 'Fires when any Elementor form is submitted on the site.', 'captain-funnel-for-whatsapp' ),
				'variables'   => array(
					'{form_name}'    => __( 'Form name (set in Elementor form widget)', 'captain-funnel-for-whatsapp' ),
					'{form_id}'      => __( 'Elementor form widget ID', 'captain-funnel-for-whatsapp' ),
					'{page_title}'   => __( 'Title of the page containing the form', 'captain-funnel-for-whatsapp' ),
					'{sender_name}'  => __( 'Name field value', 'captain-funnel-for-whatsapp' ),
					'{sender_email}' => __( 'Email field value', 'captain-funnel-for-whatsapp' ),
					'{sender_phone}' => __( 'Phone / tel field value', 'captain-funnel-for-whatsapp' ),
					'{message}'      => __( 'Message / textarea field value', 'captain-funnel-for-whatsapp' ),
					'{site_name}'    => __( 'Website name', 'captain-funnel-for-whatsapp' ),
				),
			),
		);
	}

	public function register_hooks(): void {
		add_action( 'elementor_pro/forms/new_record', array( $this, 'on_submit' ), 10, 2 );
	}

	/**
	 * Handle a new Elementor form submission.
	 *
	 * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record       Submission record.
	 * @param mixed                                           $ajax_handler Ajax handler instance.
	 */
	public function on_submit( $record, $ajax_handler ): void {
		if ( ! is_object( $record ) || ! method_exists( $record, 'get' ) ) {
			return;
		}

		$raw_fields = (array) $record->get( 'fields' );
		$settings   = (array) $record->get( 'form_settings' );

		$fields = array();
		foreach ( $raw_fields as $id => $field ) {
			$fields[ $id ] = array(
				'type'  => $field['type'] ?? '',
				'title' => $field['title'] ?? $id,
				'value' => is_array( $field['value'] ?? null ) ? implode( ', ', $field['value'] ) : ( $field['value'] ?? '' ),
			);
		}

		$phone = $this->extract_phone( $fields );

		$post_id     = $record->get( 'post_id' );
		$form_name   = sanitize_text_field( $settings['form_name'] ?? '' );
		$page_title  = $post_id ? get_the_title( $post_id ) : '';

		$vars = array(
			'{form_name}'    => $form_name,
			'{form_id}'      => sanitize_text_field( $settings['id'] ?? '' ),
			'{page_title}'   => sanitize_text_field( $page_title ),
			'{sender_name}'  => sanitize_text_field( $this->find_field( $fields, array( 'name', 'full_name', 'your_name', 'fname' ) ) ),
			'{sender_email}' => sanitize_email( $this->find_field( $fields, array( 'email' ), array( 'email' ) ) ),
			'{sender_phone}' => sanitize_text_field( $phone ),
			'{message}'      => sanitize_textarea_field( $this->find_field( $fields, array( 'message', 'comments' ), array( 'textarea' ) ) ),
		);

		if ( empty( $phone ) ) {
			$this->notify_admin(
				sprintf(
					/* translators: %s: form name */
					__( 'New Elementor form submission on "%s".', 'captain-funnel-for-whatsapp' ),
					$form_name
				)
			);
			return;
		}

		$this->fire_trigger( 'elementor_form_submitted', $phone, $vars, (int) $post_id );
	}

	/**
	 * Look for a phone-type field first, then fall back to common field name/id matches.
	 *
	 * @param array $fields Normalized fields keyed by field id.
	 * @return string
	 */
	private function extract_phone( array $fields ): string {
		foreach ( $fields as $field ) {
			if ( 'tel' === $field['type'] && ! empty( $field['value'] ) ) {
				return preg_replace( '/[^0-9]/', '', $field['value'] );
			}
		}

		$value = $this->find_field( $fields, array( 'phone', 'whatsapp', 'mobile', 'tel', 'phone_number' ) );
		return $value ? preg_replace( '/[^0-9]/', '', $value ) : '';
	}

	/**
	 * Find a field's value by matching its id/title (case-insensitive) or its type.
	 *
	 * @param array $fields     Normalized fields keyed by field id.
	 * @param array $name_slugs Candidate ids/titles to match (lowercased, underscores for spaces).
	 * @param array $types      Optional candidate field types to match.
	 * @return string
	 */
	private function find_field( array $fields, array $name_slugs, array $types = array() ): string {
		foreach ( $fields as $id => $field ) {
			$id_slug    = strtolower( str_replace( ' ', '_', $id ) );
			$title_slug = strtolower( str_replace( ' ', '_', $field['title'] ?? '' ) );

			if ( in_array( $id_slug, $name_slugs, true ) || in_array( $title_slug, $name_slugs, true ) ) {
				return $field['value'] ?? '';
			}
		}

		if ( ! empty( $types ) ) {
			foreach ( $fields as $field ) {
				if ( in_array( $field['type'] ?? '', $types, true ) && ! empty( $field['value'] ) ) {
					return $field['value'];
				}
			}
		}

		return '';
	}
}
