<?php
/**
 * Custom Automation integration — WordPress core events + webhook trigger.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CAPFW_Integration_Custom extends CAPFW_Integration_Base {

	public function get_slug(): string     { return 'custom'; }
	public function get_label(): string    { return 'Custom Automation'; }
	public function get_category(): string { return 'Custom Automation'; }
	public function get_plugin_file(): string { return ''; } // Always active — WordPress core.

	public function get_triggers(): array {
		return array(
			'custom_post_published' => array(
				'label'       => __( 'Post Published', 'captain-funnel-for-whatsapp' ),
				'description' => __( 'Fires when any post (or custom post type) is published.', 'captain-funnel-for-whatsapp' ),
				'variables'   => array(
					'{post_title}'    => __( 'Post title', 'captain-funnel-for-whatsapp' ),
					'{post_url}'      => __( 'Post permalink', 'captain-funnel-for-whatsapp' ),
					'{post_type}'     => __( 'Post type (post, page, etc.)', 'captain-funnel-for-whatsapp' ),
					'{author_name}'   => __( 'Post author display name', 'captain-funnel-for-whatsapp' ),
					'{publish_date}'  => __( 'Publish date', 'captain-funnel-for-whatsapp' ),
					'{admin_phone}'   => __( 'Admin WhatsApp number', 'captain-funnel-for-whatsapp' ),
					'{site_name}'     => __( 'Website name', 'captain-funnel-for-whatsapp' ),
				),
			),
			'custom_comment_approved' => array(
				'label'       => __( 'Comment Approved', 'captain-funnel-for-whatsapp' ),
				'description' => __( 'Fires when a comment is approved.', 'captain-funnel-for-whatsapp' ),
				'variables'   => array(
					'{commenter_name}'  => __( 'Commenter name', 'captain-funnel-for-whatsapp' ),
					'{commenter_email}' => __( 'Commenter email', 'captain-funnel-for-whatsapp' ),
					'{post_title}'      => __( 'Post the comment is on', 'captain-funnel-for-whatsapp' ),
					'{post_url}'        => __( 'Post permalink', 'captain-funnel-for-whatsapp' ),
					'{comment_content}' => __( 'Comment text (first 100 chars)', 'captain-funnel-for-whatsapp' ),
					'{site_name}'       => __( 'Website name', 'captain-funnel-for-whatsapp' ),
				),
			),
			'custom_user_role_changed' => array(
				'label'       => __( 'User Role Changed', 'captain-funnel-for-whatsapp' ),
				'description' => __( 'Fires when a user\'s role is changed.', 'captain-funnel-for-whatsapp' ),
				'variables'   => array(
					'{user_name}'   => __( 'User display name', 'captain-funnel-for-whatsapp' ),
					'{user_email}'  => __( 'User email', 'captain-funnel-for-whatsapp' ),
					'{user_phone}'  => __( 'User phone (billing meta)', 'captain-funnel-for-whatsapp' ),
					'{old_role}'    => __( 'Previous role', 'captain-funnel-for-whatsapp' ),
					'{new_role}'    => __( 'New role', 'captain-funnel-for-whatsapp' ),
					'{site_name}'   => __( 'Website name', 'captain-funnel-for-whatsapp' ),
				),
			),
			'custom_webhook' => array(
				'label'       => __( 'Webhook Trigger', 'captain-funnel-for-whatsapp' ),
				'description' => __( 'Trigger via REST API or custom PHP code using do_action(\'capfw_webhook_trigger\', $phone, $vars).', 'captain-funnel-for-whatsapp' ),
				'variables'   => array(
					'{message}'   => __( 'Custom message passed via webhook', 'captain-funnel-for-whatsapp' ),
					'{site_name}' => __( 'Website name', 'captain-funnel-for-whatsapp' ),
				),
			),
		);
	}

	public function register_hooks(): void {
		add_action( 'transition_post_status', array( $this, 'on_post_published' ), 10, 3 );
		add_action( 'comment_approved_',      array( $this, 'on_comment_approved' ), 10, 2 );
		add_action( 'set_user_role',          array( $this, 'on_role_changed' ), 10, 3 );
		add_action( 'capfw_webhook_trigger',    array( $this, 'on_webhook' ), 10, 2 );

		// REST API endpoint for webhook.
		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
	}

	// ── Post Published ────────────────────────────────────────────────────────
	public function on_post_published( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}
		// Skip revisions and auto-drafts.
		if ( in_array( $post->post_type, array( 'revision', 'auto-draft', 'nav_menu_item' ), true ) ) {
			return;
		}

		$author = get_user_by( 'id', $post->post_author );
		$vars   = array(
			'{post_title}'   => $post->post_title,
			'{post_url}'     => get_permalink( $post->ID ),
			'{post_type}'    => $post->post_type,
			'{author_name}'  => $author ? $author->display_name : '',
			'{publish_date}' => wp_date( get_option( 'date_format' ) ),
			'{admin_phone}'  => ( (array) get_option( 'capfw_settings', array() ) )['admin_phone'] ?? '',
		);

		// Admin-only notification (no customer phone here).
		$this->notify_admin( $this->parse_variables( $this->get_template( 'custom_post_published' ) ?: "New post published: {post_title}\n{post_url}", $vars ) );
	}

	// ── Comment Approved ─────────────────────────────────────────────────────
	public function on_comment_approved( WP_Comment $comment, string $approved ): void {
		if ( '1' !== (string) $approved && 'approve' !== $approved ) {
			return;
		}

		$post = get_post( $comment->comment_post_ID );
		$vars = array(
			'{commenter_name}'  => sanitize_text_field( $comment->comment_author ),
			'{commenter_email}' => sanitize_email( $comment->comment_author_email ),
			'{post_title}'      => $post ? $post->post_title : '',
			'{post_url}'        => get_permalink( $comment->comment_post_ID ),
			'{comment_content}' => mb_substr( wp_strip_all_tags( $comment->comment_content ), 0, 100 ),
		);

		$this->notify_admin( $this->parse_variables(
			$this->get_template( 'custom_comment_approved' ) ?: "New comment by {commenter_name} on \"{post_title}\".",
			$vars
		) );
	}

	// ── User Role Changed ─────────────────────────────────────────────────────
	public function on_role_changed( int $user_id, string $new_role, array $old_roles ): void {
		$user  = get_user_by( 'id', $user_id );
		if ( ! $user ) { return; }

		$phone = preg_replace( '/[^0-9]/', '', get_user_meta( $user_id, 'billing_phone', true ) );
		$vars  = array(
			'{user_name}'  => $user->display_name,
			'{user_email}' => $user->user_email,
			'{user_phone}' => $phone,
			'{old_role}'   => implode( ', ', $old_roles ),
			'{new_role}'   => $new_role,
		);

		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'custom_user_role_changed', $phone, $vars, $user_id );
		}
		$this->notify_admin( sprintf(
			/* translators: %1$s: username, %2$s: old role, %3$s: new role */
			__( 'User role changed: %1$s — %2$s → %3$s', 'captain-funnel-for-whatsapp' ),
			$user->display_name,
			implode( ', ', $old_roles ),
			$new_role
		) );
	}

	// ── Webhook ───────────────────────────────────────────────────────────────
	public function on_webhook( string $phone, array $vars ): void {
		$phone = preg_replace( '/[^0-9]/', '', $phone );
		if ( ! empty( $phone ) ) {
			$this->fire_trigger( 'custom_webhook', $phone, $vars );
		}
	}

	// ── REST Endpoint ─────────────────────────────────────────────────────────
	public function register_rest_route(): void {
		register_rest_route(
			'capfw/v1',
			'/trigger',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_trigger_callback' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
				'args'                => array(
					'phone'   => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
					'message' => array( 'required' => true, 'sanitize_callback' => 'sanitize_textarea_field' ),
				),
			)
		);
	}

	public function rest_permission_check( WP_REST_Request $request ): bool {
		$api_key  = sanitize_text_field( $request->get_header( 'X-CAPFW-API-Key' ) );
		$settings = (array) get_option( 'capfw_settings', array() );
		$saved    = sanitize_text_field( $settings['webhook_api_key'] ?? '' );
		return ! empty( $saved ) && hash_equals( $saved, $api_key );
	}

	public function rest_trigger_callback( WP_REST_Request $request ): WP_REST_Response {
		$phone   = sanitize_text_field( $request->get_param( 'phone' ) );
		$message = sanitize_textarea_field( $request->get_param( 'message' ) );
		do_action( 'capfw_webhook_trigger', $phone, array( '{message}' => $message ) );
		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}
