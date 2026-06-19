<?php
/**
 * WordPress User Registration integration.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Integration_UserRegistration
 */
class CAPFW_Integration_UserRegistration extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'user_registration'; }
	public function get_label(): string    { return 'User Registration'; }
	public function get_category(): string { return 'User Registration'; }
	public function get_plugin_file(): string { return ''; } // WordPress core — always active.

	public function get_triggers(): array {
		$base_vars = array(
			'{user_name}'    => __( 'User display name', 'captain-funnel-for-whatsapp' ),
			'{user_login}'   => __( 'Username / login', 'captain-funnel-for-whatsapp' ),
			'{user_email}'   => __( 'User email address', 'captain-funnel-for-whatsapp' ),
			'{user_phone}'   => __( 'User phone (billing_phone meta)', 'captain-funnel-for-whatsapp' ),
			'{user_role}'    => __( 'User role', 'captain-funnel-for-whatsapp' ),
			'{register_date}'=> __( 'Registration date', 'captain-funnel-for-whatsapp' ),
			'{site_name}'    => __( 'Website name', 'captain-funnel-for-whatsapp' ),
		);

		return array(
			'user_registered'   => array(
				'label'       => __( 'New User Registered', 'captain-funnel-for-whatsapp' ),
				'description' => __( 'Fires when a new WordPress user registers.', 'captain-funnel-for-whatsapp' ),
				'variables'   => $base_vars,
			),
			'user_password_reset' => array(
				'label'       => __( 'Password Reset Requested', 'captain-funnel-for-whatsapp' ),
				'description' => __( 'Fires when user requests a password reset.', 'captain-funnel-for-whatsapp' ),
				'variables'   => array_merge( $base_vars, array(
					'{reset_link}' => __( 'Password reset URL', 'captain-funnel-for-whatsapp' ),
				) ),
			),
		);
	}

	public function register_hooks(): void {
		add_action( 'user_register',    array( $this, 'on_register' ) );
		add_action( 'retrieve_password_message', array( $this, 'capture_reset_link' ), 10, 4 );
	}

	public function on_register( int $user_id ): void {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		$phone = preg_replace( '/[^0-9]/', '', get_user_meta( $user_id, 'billing_phone', true ) );

		$vars = array(
			'{user_name}'     => $user->display_name,
			'{user_login}'    => $user->user_login,
			'{user_email}'    => $user->user_email,
			'{user_phone}'    => $phone,
			'{user_role}'     => implode( ', ', $user->roles ),
			'{register_date}' => wp_date( get_option( 'date_format' ) ),
		);

		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'user_registered', $phone, $vars, $user_id );
		}

		// Always notify admin.
		$this->notify_admin(
			sprintf(
				/* translators: %1$s: username, %2$s: email */
				__( 'New user registered: %1$s (%2$s)', 'captain-funnel-for-whatsapp' ),
				$user->user_login,
				$user->user_email
			)
		);
	}

	/**
	 * Capture reset link from the password reset email message.
	 *
	 * @param string  $message    Email message.
	 * @param string  $key        Reset key.
	 * @param string  $user_login Login.
	 * @param WP_User $user_data  User object.
	 * @return string
	 */
	public function capture_reset_link( string $message, string $key, string $user_login, WP_User $user_data ): string {
		$phone = preg_replace( '/[^0-9]/', '', get_user_meta( $user_data->ID, 'billing_phone', true ) );
		if ( empty( $phone ) ) {
			return $message;
		}

		$reset_link = network_site_url( "wp-login.php?action=rp&key={$key}&login=" . rawurlencode( $user_login ), 'login' );

		$vars = array(
			'{user_name}'    => $user_data->display_name,
			'{user_login}'   => $user_login,
			'{user_email}'   => $user_data->user_email,
			'{user_phone}'   => $phone,
			'{user_role}'    => implode( ', ', $user_data->roles ),
			'{register_date}'=> wp_date( get_option( 'date_format' ), strtotime( $user_data->user_registered ) ),
			'{reset_link}'   => $reset_link,
		);

		$this->fire_trigger( 'user_password_reset', $phone, $vars, $user_data->ID );

		return $message;
	}
}
