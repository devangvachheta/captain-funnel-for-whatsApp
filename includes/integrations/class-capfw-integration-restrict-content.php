<?php
/**
 * Restrict Content Pro integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CAPFW_Integration_RestrictContent extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'restrict_content'; }
	public function get_label(): string    { return 'Restrict Content Pro'; }
	public function get_category(): string { return 'Membership'; }
	public function get_plugin_file(): string { return 'restrict-content-pro/restrict-content-pro.php'; }

	public function get_triggers(): array {
		$vars = array(
			'{member_name}'     => __( 'Member display name', 'captain-funnel-for-whatsapp' ),
			'{member_email}'    => __( 'Member email', 'captain-funnel-for-whatsapp' ),
			'{member_phone}'    => __( 'Member phone (meta)', 'captain-funnel-for-whatsapp' ),
			'{membership_name}' => __( 'Membership level name', 'captain-funnel-for-whatsapp' ),
			'{expiry_date}'     => __( 'Expiration date', 'captain-funnel-for-whatsapp' ),
			'{site_name}'       => __( 'Website name', 'captain-funnel-for-whatsapp' ),
		);

		return array(
			'rcp_activated'  => array( 'label' => __( 'Membership Activated', 'captain-funnel-for-whatsapp' ),  'description' => __( 'Fires when RCP membership is activated.', 'captain-funnel-for-whatsapp' ),  'variables' => $vars ),
			'rcp_expired'    => array( 'label' => __( 'Membership Expired', 'captain-funnel-for-whatsapp' ),    'description' => __( 'Fires when RCP membership expires.', 'captain-funnel-for-whatsapp' ),    'variables' => $vars ),
			'rcp_cancelled'  => array( 'label' => __( 'Membership Cancelled', 'captain-funnel-for-whatsapp' ),  'description' => __( 'Fires when RCP membership is cancelled.', 'captain-funnel-for-whatsapp' ), 'variables' => $vars ),
		);
	}

	public function register_hooks(): void {
		add_action( 'rcp_membership_activated',  array( $this, 'on_activated' ) );
		add_action( 'rcp_membership_expired',    array( $this, 'on_expired' ) );
		add_action( 'rcp_membership_cancelled',  array( $this, 'on_cancelled' ) );
	}

	public function on_activated( $membership ): void { $this->handle( $membership, 'rcp_activated' ); }
	public function on_expired( $membership ): void   { $this->handle( $membership, 'rcp_expired' ); }
	public function on_cancelled( $membership ): void { $this->handle( $membership, 'rcp_cancelled' ); }

	private function handle( $membership, string $trigger_key ): void {
		if ( ! method_exists( $membership, 'get_user_id' ) ) { return; }
		$user_id = $membership->get_user_id();
		$user    = get_user_by( 'id', $user_id );
		if ( ! $user ) { return; }

		$phone = preg_replace( '/[^0-9]/', '', get_user_meta( $user_id, 'rcp_phone', true ) );
		$level = rcp_get_membership_level( $membership->get_object_id() );

		$vars = array(
			'{member_name}'     => $user->display_name,
			'{member_email}'    => $user->user_email,
			'{member_phone}'    => $phone,
			'{membership_name}' => $level ? sanitize_text_field( $level->get_name() ) : '',
			'{expiry_date}'     => sanitize_text_field( $membership->get_expiration_date() ),
		);

		if ( ! empty( $phone ) ) {
			$this->fire_trigger( $trigger_key, $phone, $vars, $user_id );
		} else {
			$this->notify_admin( sprintf( __( 'RCP: %s — %s', 'captain-funnel-for-whatsapp' ), $trigger_key, $user->user_email ) );
		}
	}
}
