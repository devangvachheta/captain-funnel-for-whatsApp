<?php
/**
 * Admin — registers menu pages, enqueues React app, handles unified AJAX.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Admin
 */
class CAPFW_Admin {

	/**
	 * Register all admin hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_capfw_react_ajax', array( $this, 'handle_react_ajax' ) );
	}

	/**
	 * Register admin menu pages.
	 */
	public function register_menu_pages() {
		add_menu_page(
			esc_html__( 'Captain Funnel for WhatsApp', 'captain-funnel-for-whatsapp' ),
			esc_html__( 'WA Funnel', 'captain-funnel-for-whatsapp' ),
			'manage_options',
			'capfw-dashboard',
			array( $this, 'render_app_page' ),
			'dashicons-whatsapp',
			56
		);

		add_submenu_page( 'capfw-dashboard', esc_html__( 'Dashboard', 'captain-funnel-for-whatsapp' ),    esc_html__( 'Dashboard', 'captain-funnel-for-whatsapp' ),      'manage_options', 'capfw-dashboard',     array( $this, 'render_app_page' ) );
		add_submenu_page( 'capfw-dashboard', esc_html__( 'Settings', 'captain-funnel-for-whatsapp' ),     esc_html__( 'Settings', 'captain-funnel-for-whatsapp' ),       'manage_options', 'capfw-settings',      array( $this, 'render_app_page' ) );
		add_submenu_page( 'capfw-dashboard', esc_html__( 'Integrations', 'captain-funnel-for-whatsapp' ), esc_html__( 'Integrations', 'captain-funnel-for-whatsapp' ),   'manage_options', 'capfw-integrations',  array( $this, 'render_app_page' ) );
		add_submenu_page( 'capfw-dashboard', esc_html__( 'Templates', 'captain-funnel-for-whatsapp' ),    esc_html__( 'Templates', 'captain-funnel-for-whatsapp' ),      'manage_options', 'capfw-templates',     array( $this, 'render_app_page' ) );
		add_submenu_page( 'capfw-dashboard', esc_html__( 'Funnels', 'captain-funnel-for-whatsapp' ),      esc_html__( 'Funnels', 'captain-funnel-for-whatsapp' ),        'manage_options', 'capfw-funnels',       array( $this, 'render_app_page' ) );
		add_submenu_page( 'capfw-dashboard', esc_html__( 'Message Logs', 'captain-funnel-for-whatsapp' ), esc_html__( 'Logs', 'captain-funnel-for-whatsapp' ),           'manage_options', 'capfw-logs',          array( $this, 'render_app_page' ) );
	}

	/**
	 * Enqueue React app assets — only on plugin pages.
	 * FIX: Correct WP submenu hook format: {parent_slug}_page_{submenu_slug}
	 * FIX: Use wp-element (not react/react-dom) so WP 6.x React globals are available.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ) {
		$capfw_pages = array(
			'toplevel_page_capfw-dashboard',
			'capfw-dashboard_page_capfw-settings',
			'capfw-dashboard_page_capfw-integrations',
			'capfw-dashboard_page_capfw-templates',
			'capfw-dashboard_page_capfw-funnels',
			'capfw-dashboard_page_capfw-logs',
		);

		if ( ! in_array( $hook, $capfw_pages, true ) ) {
			return;
		}

		wp_register_style( 'capfw-react-app-css', CAPFW_PLUGIN_URL . 'admin/css/capfw-react-app.css', array(), CAPFW_VERSION );
		wp_enqueue_style( 'capfw-react-app-css' );

		// wp-element ensures React & ReactDOM globals are available in WP 6.x+
		wp_register_script( 'capfw-react-app-js', CAPFW_PLUGIN_URL . 'admin/js/capfw-react-app.js', array( 'wp-element', 'wp-i18n' ), CAPFW_VERSION, true );
		wp_enqueue_script( 'capfw-react-app-js' );

		wp_localize_script(
			'capfw-react-app-js',
			'capfw_data',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'capfw_react_nonce' ),
				'plugin_url' => CAPFW_PLUGIN_URL,
				'version'    => CAPFW_VERSION,
				'home_url'   => home_url(),
				'page'       => sanitize_key( $_GET['page'] ?? 'capfw-dashboard' ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			)
		);

		wp_set_script_translations( 'capfw-react-app-js', 'captain-funnel-for-whatsapp', CAPFW_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Render the single-page React app container.
	 */
	public function render_app_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div id="capfw-react-app"></div>';
	}

	// =========================================================================
	// Unified AJAX — single endpoint, routed by 'type' field.
	// FIX: wp_die() only once at the end — removed duplicate calls inside methods.
	// =========================================================================

	/**
	 * Main AJAX router.
	 */
	public function handle_react_ajax() {
		check_ajax_referer( 'capfw_react_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Permission denied.', 'captain-funnel-for-whatsapp' ) ) );
			wp_die();
		}

		$type = sanitize_key( $_POST['type'] ?? '' );

		switch ( $type ) {
			case 'get_stats':        $this->ajax_get_stats();        break;
			case 'get_recent_logs':  $this->ajax_get_recent_logs();  break;
			case 'get_settings':     $this->ajax_get_settings();     break;
			case 'save_settings':    $this->ajax_save_settings();    break;
			case 'test_connection':  $this->ajax_test_connection();  break;
			case 'get_templates':    $this->ajax_get_templates();    break;
			case 'save_templates':   $this->ajax_save_templates();   break;
			case 'get_funnels':      $this->ajax_get_funnels();      break;
			case 'save_funnel':      $this->ajax_save_funnel();      break;
			case 'delete_funnel':    $this->ajax_delete_funnel();    break;
			case 'get_logs':              $this->ajax_get_logs();              break;
			case 'get_integrations':      $this->ajax_get_integrations();      break;
			case 'save_integrations':     $this->ajax_save_integrations();     break;
			case 'get_available_triggers':$this->ajax_get_available_triggers(); break;
			case 'get_integration_templates': $this->ajax_get_integration_templates(); break;
			case 'save_integration_template': $this->ajax_save_integration_template(); break;
			default:
				wp_send_json_error( array( 'message' => esc_html__( 'Unknown request type.', 'captain-funnel-for-whatsapp' ) ) );
		}

		wp_die(); // Single wp_die() at the end — no duplicates in private methods.
	}

	// ── Stats ─────────────────────────────────────────────────────────────────

	private function ajax_get_stats() {
		wp_send_json_success( CAPFW_Logger::get_stats() );
	}

	private function ajax_get_recent_logs() {
		wp_send_json_success( CAPFW_Logger::get_logs( 5, 1 ) );
	}

	// ── Settings ──────────────────────────────────────────────────────────────

	private function ajax_get_settings() {
		$settings = (array) get_option( 'capfw_settings', array() );
		wp_send_json_success( array(
			'access_token'        => sanitize_text_field( $settings['access_token']        ?? '' ),
			'phone_number_id'     => sanitize_text_field( $settings['phone_number_id']     ?? '' ),
			'business_account_id' => sanitize_text_field( $settings['business_account_id'] ?? '' ),
			'admin_phone'         => sanitize_text_field( $settings['admin_phone']         ?? '' ),
			'enabled_statuses'    => (array) ( $settings['enabled_statuses'] ?? array() ),
		) );
	}

	private function ajax_save_settings() {
		$raw      = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$incoming = json_decode( $raw, true );

		if ( ! is_array( $incoming ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid settings data.', 'captain-funnel-for-whatsapp' ) ) );
			return;
		}

		$settings = (array) get_option( 'capfw_settings', array() );
		$settings['access_token']        = sanitize_text_field( $incoming['access_token']        ?? '' );
		$settings['phone_number_id']      = sanitize_text_field( $incoming['phone_number_id']      ?? '' );
		$settings['business_account_id']  = sanitize_text_field( $incoming['business_account_id']  ?? '' );
		$settings['admin_phone']          = sanitize_text_field( $incoming['admin_phone']          ?? '' );
		$settings['enabled_statuses']     = array_map( 'sanitize_key', (array) ( $incoming['enabled_statuses'] ?? array() ) );

		update_option( 'capfw_settings', $settings );
		wp_send_json_success( array( 'message' => esc_html__( 'Settings saved successfully.', 'captain-funnel-for-whatsapp' ) ) );
	}

	/**
	 * FIX Critical #3: Test with live form credentials if provided, else fall back to saved.
	 */
	private function ajax_test_connection() {
		$live_token = isset( $_POST['access_token'] )    ? sanitize_text_field( wp_unslash( $_POST['access_token'] ) )    : '';
		$live_phone = isset( $_POST['phone_number_id'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_number_id'] ) ) : '';

		if ( ! empty( $live_token ) && ! empty( $live_phone ) ) {
			$result = CAPFW_WhatsApp_API::test_connection_with( $live_token, $live_phone );
		} else {
			$result = CAPFW_WhatsApp_API::test_connection();
		}

		if ( $result['success'] ) {
			wp_send_json_success( array( 'message' => esc_html( $result['message'] ) ) );
		} else {
			wp_send_json_error( array( 'message' => esc_html( $result['message'] ) ) );
		}
	}

	// ── Templates ─────────────────────────────────────────────────────────────

	private function ajax_get_templates() {
		$settings  = (array) get_option( 'capfw_settings', array() );
		$statuses  = array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' );
		$templates = array();

		foreach ( $statuses as $status ) {
			$templates[ $status ] = sanitize_textarea_field( $settings[ 'template_' . sanitize_key( $status ) ] ?? '' );
		}

		wp_send_json_success( $templates );
	}

	private function ajax_save_templates() {
		$raw      = isset( $_POST['templates'] ) ? wp_unslash( $_POST['templates'] ) : '{}'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$incoming = json_decode( $raw, true );

		if ( ! is_array( $incoming ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid template data.', 'captain-funnel-for-whatsapp' ) ) );
			return;
		}

		$settings = (array) get_option( 'capfw_settings', array() );
		$statuses = array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed' );

		foreach ( $statuses as $status ) {
			if ( array_key_exists( $status, $incoming ) ) {
				$settings[ 'template_' . sanitize_key( $status ) ] = sanitize_textarea_field( $incoming[ $status ] );
			}
		}

		update_option( 'capfw_settings', $settings );
		wp_send_json_success( array( 'message' => esc_html__( 'Templates saved successfully.', 'captain-funnel-for-whatsapp' ) ) );
	}

	// ── Funnels ───────────────────────────────────────────────────────────────

	private function ajax_get_funnels() {
		global $wpdb;
		$table   = $wpdb->prefix . 'capfw_funnels';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$funnels = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC" );
		wp_send_json_success( $funnels ?: array() );
	}

	private function ajax_save_funnel() {
		global $wpdb;

		$funnel_id   = absint( $_POST['funnel_id']     ?? 0 );
		$funnel_name = sanitize_text_field( wp_unslash( $_POST['funnel_name']   ?? '' ) );
		$trigger     = sanitize_key( $_POST['trigger_event'] ?? '' );
		$status      = sanitize_key( $_POST['funnel_status'] ?? 'active' );

		if ( empty( $funnel_name ) || empty( $trigger ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Funnel name and trigger are required.', 'captain-funnel-for-whatsapp' ) ) );
			return;
		}

		$table = $wpdb->prefix . 'capfw_funnels';

		if ( $funnel_id > 0 ) {
			$wpdb->update(
				$table,
				array( 'funnel_name' => $funnel_name, 'trigger_event' => $trigger, 'status' => $status ),
				array( 'id' => $funnel_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table,
				array( 'funnel_name' => $funnel_name, 'trigger_event' => $trigger, 'status' => $status ),
				array( '%s', '%s', '%s' )
			);
			$funnel_id = $wpdb->insert_id;
		}

		// FIX Critical #5: Return created_at so React can show it immediately.
		wp_send_json_success( array(
			'message'    => esc_html__( 'Funnel saved successfully.', 'captain-funnel-for-whatsapp' ),
			'funnel_id'  => absint( $funnel_id ),
			'created_at' => current_time( 'mysql' ),
		) );
	}

	private function ajax_delete_funnel() {
		global $wpdb;

		$funnel_id = absint( $_POST['funnel_id'] ?? 0 );
		if ( ! $funnel_id ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid funnel ID.', 'captain-funnel-for-whatsapp' ) ) );
			return;
		}

		$wpdb->delete( $wpdb->prefix . 'capfw_funnel_steps', array( 'funnel_id' => $funnel_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'capfw_funnels',      array( 'id'        => $funnel_id ), array( '%d' ) );

		wp_send_json_success( array( 'message' => esc_html__( 'Funnel deleted.', 'captain-funnel-for-whatsapp' ) ) );
	}

	// ── Logs ──────────────────────────────────────────────────────────────────

	private function ajax_get_logs() {
		$per_page      = absint( $_POST['per_page'] ?? 20 );
		$paged         = absint( $_POST['paged']    ?? 1 );
		$filter_status = sanitize_key( $_POST['filter_status'] ?? 'all' );

		global $wpdb;
		$table  = $wpdb->prefix . 'capfw_logs';
		$offset = ( $paged - 1 ) * $per_page;
		$where  = '';
		$args   = array();

		if ( 'all' !== $filter_status ) {
			$where  = 'WHERE status = %s';
			$args[] = $filter_status;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $args ) {
			$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} {$where}", $args ) );
		} else {
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}

		$query_args = array_merge( $args, array( $per_page, $offset ) );
		$logs       = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", $query_args ) );
		// phpcs:enable

		wp_send_json_success( array(
			'logs'  => $logs ?: array(),
			'total' => $total,
		) );
	}

	// ── Integrations ──────────────────────────────────────────────────────────

	private function ajax_get_integrations() {
		$registry = CAPFW_Integration_Registry::instance();
		wp_send_json_success( $registry->get_all_for_api() );
	}

	private function ajax_save_integrations() {
		$raw      = isset( $_POST['enabled'] ) ? wp_unslash( $_POST['enabled'] ) : '[]'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$incoming = json_decode( $raw, true );

		if ( ! is_array( $incoming ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid data.', 'captain-funnel-for-whatsapp' ) ) );
			return;
		}

		$settings = (array) get_option( 'capfw_settings', array() );
		$settings['enabled_integrations'] = array_map( 'sanitize_key', $incoming );
		update_option( 'capfw_settings', $settings );

		wp_send_json_success( array( 'message' => esc_html__( 'Integrations saved.', 'captain-funnel-for-whatsapp' ) ) );
	}

	private function ajax_get_available_triggers() {
		$registry = CAPFW_Integration_Registry::instance();
		wp_send_json_success( $registry->get_available_triggers() );
	}

	private function ajax_get_integration_templates() {
		$slug     = sanitize_key( $_POST['integration_slug'] ?? '' );
		$registry = CAPFW_Integration_Registry::instance();
		wp_send_json_success( $registry->get_templates_for_integration( $slug ) );
	}

	private function ajax_save_integration_template() {
		global $wpdb;

		$slug        = sanitize_key( $_POST['integration_slug'] ?? '' );
		$trigger_key = sanitize_key( $_POST['trigger_key']      ?? '' );
		$body        = sanitize_textarea_field( wp_unslash( $_POST['template_body'] ?? '' ) );
		$status      = sanitize_key( $_POST['status'] ?? 'active' );

		if ( empty( $slug ) || empty( $trigger_key ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Integration slug and trigger key are required.', 'captain-funnel-for-whatsapp' ) ) );
			return;
		}

		$table = $wpdb->prefix . 'capfw_templates';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$existing = $wpdb->get_var( $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT id FROM {$table} WHERE integration_slug = %s AND trigger_key = %s",
			$slug,
			$trigger_key
		) );

		if ( $existing ) {
			$wpdb->update(
				$table,
				array( 'template_body' => $body, 'status' => $status ),
				array( 'integration_slug' => $slug, 'trigger_key' => $trigger_key ),
				array( '%s', '%s' ),
				array( '%s', '%s' )
			);
		} else {
			$wpdb->insert(
				$table,
				array( 'integration_slug' => $slug, 'trigger_key' => $trigger_key, 'template_body' => $body, 'status' => $status ),
				array( '%s', '%s', '%s', '%s' )
			);
		}

		wp_send_json_success( array( 'message' => esc_html__( 'Template saved.', 'captain-funnel-for-whatsapp' ) ) );
	}
}
