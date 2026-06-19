<?php
/**
 * Plugin loader — wires up all classes, boots Integration Registry.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Loader
 */
class CAPFW_Loader {

	/**
	 * Bootstrap the plugin.
	 */
	public function run() {
		$this->load_textdomain();
		$this->load_core_classes();
		$this->boot_registry();

		if ( is_admin() ) {
			$this->init_admin();
		}
	}

	/**
	 * Load plugin translations.
	 */
	private function load_textdomain() {
		load_plugin_textdomain(
			'captain-funnel-for-whatsapp',
			false,
			dirname( CAPFW_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Require all core class files.
	 */
	private function load_core_classes() {
		require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-logger.php';
		require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-whatsapp-api.php';
		require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-funnel-runner.php';
		require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-integration-base.php';
		require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-integration-registry.php';
	}

	/**
	 * Boot the integration registry — discovers available plugins,
	 * registers hooks only for active + enabled integrations.
	 */
	private function boot_registry() {
		$registry = CAPFW_Integration_Registry::instance();
		$registry->init();

		// Boot funnel runner (listens to capfw_trigger_fired action).
		$funnel_runner = new CAPFW_Funnel_Runner();
		$funnel_runner->init();
	}

	/**
	 * Init admin panel.
	 */
	private function init_admin() {
		require_once CAPFW_PLUGIN_DIR . 'admin/class-capfw-admin.php';
		$admin = new CAPFW_Admin();
		$admin->init();
	}
}
