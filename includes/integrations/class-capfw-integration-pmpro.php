<?php
/**
 * Paid Memberships Pro integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CAPFW_Integration_PMPro extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'pmpro'; }
	public function get_label(): string    { return 'Paid Memberships Pro'; }
	public function get_category(): string { return 'Membership'; }
	public function get_plugin_file(): string { return 'paid-memberships-pro/paid-memberships-pro.php'; }

	public function get_triggers(): array {
		$vars = array(
			'{member_name}'     => __( 'Member display name', 'captain-funnel-for-whatsapp' ),
			'{member_email}'    => __( 'Member email', 'captain-funnel-for-whatsapp' ),
			'{member_phone}'    => __( 'Member phone (billing meta)', 'captain-funnel-for-whatsapp' ),
			'{membership_name}' => __( 'Membership level name', 'captain-funnel-for-whatsapp' ),
			'{site_name}'       => __( 'Website name', 'captain-funnel-for-whatsapp' ),
		);

		return array(
			'pmpro_activated'  => array( 'label' => __( 'Membership Activated', 'captain-funnel-for-whatsapp' ),  'description' => __( 'Fires when PMPro membership is activated.', 'captain-funnel-for-whatsapp' ),  'variables' => $vars ),
			'pmpro_expired'    => array( 'label' => __( 'Membership Expired', 'captain-funnel-for-whatsapp' ),    'description' => __( 'Fires when PMPro membership expires.', 'captain-funnel-for-whatsapp' ),    'variables' => $vars ),
			'pmpro_cancelled'  => array( 'label' => __( 'Membership Cancelled', 'captain-funnel-for-whatsapp' ),  'description' => __( 'Fires when PMPro membership is cancelled.', 'captain-funnel-for-whatsapp' ), 'variables' => $vars ),
		);
	}

	public function register_hooks(): void {
		add_action( 'pmpro_after_change_membership_level', array( $this, 'on_level_change' ), 10, 2 );
	}

	public function on_level_change( int $level_id, int $user_id ): void {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) { return; }

		$phone = preg_replace( '/[^0-9]/', '', get_user_meta( $user_id, 'pmpro_bphone', true ) );
		$level = pmpro_getLevel( $level_id );

		$vars = array(
			'{member_name}'     => $user->display_name,
			'{member_email}'    => $user->user_email,
			'{member_phone}'    => $phone,
			'{membership_name}' => $level ? sanitize_text_field( $level->name ) : '',
		);

		$trigger_key = $level_id > 0 ? 'pmpro_activated' : 'pmpro_cancelled';

		if ( ! empty( $phone ) ) {
			$this->fire_trigger( $trigger_key, $phone, $vars, $user_id );
		} else {
			$this->notify_admin( sprintf( __( 'PMPro: %s — %s', 'captain-funnel-for-whatsapp' ), $trigger_key, $user->user_email ) );
		}
	}
}
