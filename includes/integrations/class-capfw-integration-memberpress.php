<?php
/**
 * MemberPress integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CAPFW_Integration_MemberPress extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'memberpress'; }
	public function get_label(): string    { return 'MemberPress'; }
	public function get_category(): string { return 'Membership'; }
	public function get_plugin_file(): string { return 'memberpress/memberpress.php'; }

	public function get_triggers(): array {
		$vars = $this->get_member_vars();
		return array(
			'mepr_signup'  => array( 'label' => __( 'Member Signup', 'captain-funnel-for-whatsapp' ),           'description' => __( 'Fires when a MemberPress member signs up.', 'captain-funnel-for-whatsapp' ),             'variables' => $vars ),
			'mepr_expired' => array( 'label' => __( 'Membership Expired', 'captain-funnel-for-whatsapp' ),      'description' => __( 'Fires when a MemberPress membership expires.', 'captain-funnel-for-whatsapp' ),          'variables' => $vars ),
			'mepr_cancelled'=> array( 'label' => __( 'Membership Cancelled', 'captain-funnel-for-whatsapp' ),   'description' => __( 'Fires when a membership is cancelled.', 'captain-funnel-for-whatsapp' ),                 'variables' => $vars ),
		);
	}

	public function register_hooks(): void {
		add_action( 'mepr-event-member-signup-record',   array( $this, 'on_signup' ) );
		add_action( 'mepr-event-member-expired-record',  array( $this, 'on_expired' ) );
		add_action( 'mepr-event-subscription-cancelled', array( $this, 'on_cancelled' ) );
	}

	public function on_signup( $event ): void   { $this->handle( $event, 'mepr_signup' ); }
	public function on_expired( $event ): void  { $this->handle( $event, 'mepr_expired' ); }
	public function on_cancelled( $event ): void{ $this->handle( $event, 'mepr_cancelled' ); }

	private function handle( $event, string $trigger_key ): void {
		if ( ! isset( $event->args['member'] ) ) { return; }
		$member  = $event->args['member'];
		$user    = get_user_by( 'id', $member->ID );
		if ( ! $user ) { return; }

		$phone = preg_replace( '/[^0-9]/', '', get_user_meta( $user->ID, 'billing_phone', true ) );
		$membership_name = '';
		if ( isset( $event->args['membership'] ) ) {
			$membership_name = $event->args['membership']->post_title ?? '';
		}

		$vars = array(
			'{member_name}'      => $user->display_name,
			'{member_email}'     => $user->user_email,
			'{member_phone}'     => $phone,
			'{membership_name}'  => sanitize_text_field( $membership_name ),
			'{expiry_date}'      => sanitize_text_field( $event->args['expiry_date'] ?? '' ),
		);

		if ( empty( $phone ) ) {
			$this->notify_admin( sprintf( __( 'MemberPress: %s — %s', 'captain-funnel-for-whatsapp' ), $trigger_key, $user->user_email ) );
			return;
		}
		$this->fire_trigger( $trigger_key, $phone, $vars, $user->ID );
	}

	private function get_member_vars(): array {
		return array(
			'{member_name}'     => __( 'Member display name', 'captain-funnel-for-whatsapp' ),
			'{member_email}'    => __( 'Member email', 'captain-funnel-for-whatsapp' ),
			'{member_phone}'    => __( 'Member phone (billing meta)', 'captain-funnel-for-whatsapp' ),
			'{membership_name}' => __( 'Membership level name', 'captain-funnel-for-whatsapp' ),
			'{expiry_date}'     => __( 'Membership expiry date', 'captain-funnel-for-whatsapp' ),
			'{site_name}'       => __( 'Website name', 'captain-funnel-for-whatsapp' ),
		);
	}
}
