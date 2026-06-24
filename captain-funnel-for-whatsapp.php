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
 * Show admin notice for any enabled integration whose plugin is missing.
 * Only fires if the user has actually enabled that integration in settings.
 * WooCommerce (and every other integration) follows this same pattern —
 * no more hard-coded global WooCommerce notice.
 *
 * Logic:
 *  1. Read enabled integration slugs from capfw_settings.
 *  2. For each enabled slug, check if its plugin file is active.
 *  3. If missing → show a dismissible notice specific to that integration.
 */
function capfw_missing_plugin_notices() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Map of slug => [ label, plugin_file ]
	$integration_map = array(
		'woocommerce'        => array( 'WooCommerce',         'woocommerce/woocommerce.php' ),
		'elementor_forms'    => array( 'Elementor Pro',       'elementor-pro/elementor-pro.php' ),
		'contact_form_7'     => array( 'Contact Form 7',      'contact-form-7/wp-contact-form-7.php' ),
		'wpforms'            => array( 'WPForms',             'wpforms-lite/wpforms.php' ),
		'fluent_forms'       => array( 'Fluent Forms',        'fluentform/fluentform.php' ),
		'gravity_forms'      => array( 'Gravity Forms',       'gravityforms/gravityforms.php' ),
		'learndash'          => array( 'LearnDash',           'sfwd-lms/sfwd_lms.php' ),
		'lifterlms'          => array( 'LifterLMS',           'lifterlms/lifterlms.php' ),
		'tutor_lms'          => array( 'Tutor LMS',           'tutor/tutor.php' ),
		'memberpress'        => array( 'MemberPress',         'memberpress/memberpress.php' ),
		'pmpro'              => array( 'Paid Memberships Pro', 'paid-memberships-pro/paid-memberships-pro.php' ),
		'restrict_content'   => array( 'Restrict Content Pro','restrict-content-pro/restrict-content-pro.php' ),
		'amelia'             => array( 'Amelia',              'ameliabooking/ameliabooking.php' ),
		'bookly'             => array( 'Bookly',              'bookly-responsive-appointment-booking-tool/main.php' ),
		'easy_digital'       => array( 'Easy Digital Downloads', 'easy-digital-downloads/easy-digital-downloads.php' ),
	);

	$settings = (array) get_option( 'capfw_settings', array() );
	$enabled  = array_key_exists( 'enabled_integrations', $settings )
		? (array) $settings['enabled_integrations']
		: array_keys( $integration_map ); // fresh install = all enabled

	$active_plugins = (array) get_option( 'active_plugins', array() );
	if ( is_multisite() ) {
		$network = array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) );
		$active_plugins = array_merge( $active_plugins, $network );
	}

	foreach ( $enabled as $slug ) {
		if ( ! isset( $integration_map[ $slug ] ) ) {
			continue;
		}
		[ $label, $plugin_file ] = $integration_map[ $slug ];

		if ( ! in_array( $plugin_file, $active_plugins, true ) ) {
			?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: 1: Plugin label 2: Integration settings link */
							__( '<strong>Captain Funnel for WhatsApp</strong>: <strong>%1$s</strong> is enabled in your integrations but the plugin is not installed or activated. <a href="%2$s">Disable it in Integrations</a> or install the plugin.', 'captain-funnel-for-whatsapp' ),
							esc_html( $label ),
							esc_url( admin_url( 'admin.php?page=captain-funnel-for-whatsapp#/integrations' ) )
						)
					);
					?>
				</p>
			</div>
			<?php
		}
	}
}

/**
 * Main plugin init — runs after plugins loaded.
 *
 * Fix #1: Only the Loader is required here; it handles all other includes
 * via load_core_classes(). Previously logger, api, funnel-runner etc. were
 * required here AND inside the loader — redundant double-loading removed.
 */
function capfw_init_plugin() {
	require_once CAPFW_PLUGIN_DIR . 'includes/class-capfw-loader.php';

	// Show per-integration missing-plugin notices — only for enabled integrations.
	add_action( 'admin_notices', 'capfw_missing_plugin_notices' );

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
