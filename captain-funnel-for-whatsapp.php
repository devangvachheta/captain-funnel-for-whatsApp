<?php
/**
 * Plugin Name:       Captain Funnel for WhatsApp
 * Plugin URI:        https://wordpress.org/plugins/captain-funnel-for-whatsapp
 * Description:       Automate WhatsApp customer journeys — order notifications, form submissions, review requests, coupon campaigns, and product recommendations.
 * Version:           0.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            devangvachheta
 * Text Domain:       captain-funnel-for-whatsapp
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'CAPFW_VERSION', '0.0.1' );
define( 'CAPFW_PLUGIN_FILE', __FILE__ );
define( 'CAPFW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CAPFW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CAPFW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Check if WooCommerce is active.
 *
 * @return bool
 */
function capfw_is_woocommerce_active() {
	return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true );
}

/**
 * Show an informational (non-blocking) admin notice if WooCommerce is not active.
 * WooCommerce-specific triggers simply won't be available; every other
 * integration (forms, LMS, booking, membership, custom, etc.) still works.
 */
function capfw_woocommerce_missing_notice() {
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: %s: WooCommerce plugin link */
					__( '<strong>Captain Funnel for WhatsApp</strong>: %s is not active, so WooCommerce order triggers are unavailable. All other integrations (forms, LMS, booking, membership, etc.) work without it.', 'captain-funnel-for-whatsapp' ),
					'<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
				)
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Main plugin init — runs after plugins loaded.
 */
function capfw_init_plugin() {
	// Load required files. These load regardless of WooCommerce — only the
	// WooCommerce-specific hooks within them stay dormant if WooCommerce is absent.
	require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-activator.php';
	require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-deactivator.php';
	require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-logger.php';
	require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-whatsapp-api.php';
	require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-order-hooks.php';
	require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-funnel-runner.php';
	require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-loader.php';

	if ( ! capfw_is_woocommerce_active() ) {
		add_action( 'admin_notices', 'capfw_woocommerce_missing_notice' );
	}

	// Boot loader — runs for everyone; the Integration Registry already
	// gates WooCommerce-only features via is_plugin_active() internally.
	$loader = new CAPFW_Loader();
	$loader->run();
}
add_action( 'plugins_loaded', 'capfw_init_plugin' );

/**
 * Plugin activation hook.
 */
function capfw_activate_plugin() {
	require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-activator.php';
	CAPFW_Activator::activate();
}
register_activation_hook( CAPFW_PLUGIN_FILE, 'capfw_activate_plugin' );

/**
 * Plugin deactivation hook.
 */
function capfw_deactivate_plugin() {
	require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-deactivator.php';
	CAPFW_Deactivator::deactivate();
}
register_deactivation_hook( CAPFW_PLUGIN_FILE, 'capfw_deactivate_plugin' );
