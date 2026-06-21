<?php
/**
 * Integration Registry — discovers, loads, and exposes all CAPFW integrations.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Integration_Registry
 */
class CAPFW_Integration_Registry {

	/**
	 * All registered integration instances.
	 *
	 * @var CAPFW_Integration_Base[]
	 */
	private array $integrations = array();

	/**
	 * Only active (plugin installed + enabled) integrations.
	 *
	 * @var CAPFW_Integration_Base[]
	 */
	private array $active_integrations = array();

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register all integration classes and boot the active ones.
	 */
	public function init(): void {
		$this->register_all();
		$this->boot_active();
	}

	/**
	 * Require and instantiate every integration class.
	 */
	private function register_all(): void {
		$dir = CAPFW_PLUGIN_DIR . 'includes/integrations/';

		$classes = array(
			'class-capfw-integration-woocommerce.php'      => 'CAPFW_Integration_WooCommerce',
			'class-capfw-integration-edd.php'              => 'CAPFW_Integration_EDD',
			'class-capfw-integration-cf7.php'              => 'CAPFW_Integration_CF7',
			'class-capfw-integration-wpforms.php'          => 'CAPFW_Integration_WPForms',
			'class-capfw-integration-fluent-forms.php'     => 'CAPFW_Integration_FluentForms',
			'class-capfw-integration-gravity-forms.php'    => 'CAPFW_Integration_GravityForms',
			'class-capfw-integration-elementor.php'        => 'CAPFW_Integration_Elementor',
			'class-capfw-integration-user-registration.php'=> 'CAPFW_Integration_UserRegistration',
			'class-capfw-integration-bookly.php'           => 'CAPFW_Integration_Bookly',
			'class-capfw-integration-amelia.php'           => 'CAPFW_Integration_Amelia',
			'class-capfw-integration-motopress.php'        => 'CAPFW_Integration_MotoPress',
			'class-capfw-integration-memberpress.php'      => 'CAPFW_Integration_MemberPress',
			'class-capfw-integration-pmpro.php'            => 'CAPFW_Integration_PMPro',
			'class-capfw-integration-restrict-content.php' => 'CAPFW_Integration_RestrictContent',
			'class-capfw-integration-learndash.php'        => 'CAPFW_Integration_LearnDash',
			'class-capfw-integration-tutor-lms.php'        => 'CAPFW_Integration_TutorLMS',
			'class-capfw-integration-lifterlms.php'        => 'CAPFW_Integration_LifterLMS',
			'class-capfw-integration-custom.php'           => 'CAPFW_Integration_Custom',
		);

		foreach ( $classes as $file => $class ) {
			$path = $dir . $file;
			if ( file_exists( $path ) ) {
				require_once $path;
				if ( class_exists( $class ) ) {
					$instance = new $class();
					$this->integrations[ $instance->get_slug() ] = $instance;
				}
			}
		}

		/**
		 * Allow third-party plugins to register their own CAPFW integrations.
		 *
		 * @param CAPFW_Integration_Registry $registry Registry instance.
		 */
		do_action( 'capfw_register_integrations', $this );
	}

	/**
	 * Register a custom integration from outside the plugin.
	 *
	 * @param CAPFW_Integration_Base $integration Integration instance.
	 */
	public function register( CAPFW_Integration_Base $integration ): void {
		$this->integrations[ $integration->get_slug() ] = $integration;
	}

	/**
	 * Boot active integrations: check availability + register hooks.
	 */
	private function boot_active(): void {
		$enabled = $this->get_enabled_slugs();

		foreach ( $this->integrations as $slug => $integration ) {
			if ( $integration->is_available() && in_array( $slug, $enabled, true ) ) {
				$this->active_integrations[ $slug ] = $integration;
				$integration->register_hooks();
			}
		}
	}

	/**
	 * Get slugs that the user has explicitly enabled in settings.
	 *
	 * @return string[]
	 */
	public function get_enabled_slugs(): array {
		$settings = (array) get_option( 'capfw_settings', array() );

		// If the key doesn't exist yet (fresh install / never saved Integrations page),
		// treat it as "not configured" and enable all available integrations by default
		// so hooks register correctly without requiring a manual save first.
		if ( ! array_key_exists( 'enabled_integrations', $settings ) ) {
			return array_keys( $this->integrations );
		}

		$enabled = (array) $settings['enabled_integrations'];

		// Backward compat: empty array means user saved with nothing checked — respect that.
		return $enabled;
	}

	/**
	 * Get all registered integrations (for admin UI listing).
	 *
	 * @return array[]
	 */
	public function get_all_for_api(): array {
		$enabled = $this->get_enabled_slugs();
		$result  = array();

		foreach ( $this->integrations as $slug => $integration ) {
			$result[] = array(
				'slug'        => $slug,
				'label'       => $integration->get_label(),
				'category'    => $integration->get_category(),
				'available'   => $integration->is_available(),
				'enabled'     => in_array( $slug, $enabled, true ),
				'plugin_file' => $integration->get_plugin_file(),
			);
		}

		return $result;
	}

	/**
	 * Get all available triggers from active + available integrations.
	 * Used by React Funnels UI to dynamically populate trigger dropdown.
	 *
	 * @return array[]
	 */
	public function get_available_triggers(): array {
		$result  = array();
		$enabled = $this->get_enabled_slugs();

		foreach ( $this->integrations as $slug => $integration ) {
			if ( ! $integration->is_available() ) {
				continue;
			}
			if ( ! in_array( $slug, $enabled, true ) ) {
				continue;
			}

			foreach ( $integration->get_triggers() as $key => $trigger ) {
				$result[] = array(
					'key'             => $key,
					'label'           => $trigger['label'],
					'description'     => $trigger['description'] ?? '',
					'variables'       => array_keys( $trigger['variables'] ?? array() ),
					'variable_labels' => $trigger['variables'] ?? array(),
					'integration'     => $slug,
					'category'        => $integration->get_category(),
					'int_label'       => $integration->get_label(),
				);
			}
		}

		return $result;
	}

	/**
	 * Get templates for a specific integration grouped by trigger.
	 *
	 * @param string $integration_slug Integration slug.
	 * @return array
	 */
	public function get_templates_for_integration( string $integration_slug ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'capfw_templates';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT trigger_key, template_body, status FROM {$table} WHERE integration_slug = %s",
				$integration_slug
			)
		);

		$result = array();
		foreach ( $rows as $row ) {
			$result[ $row->trigger_key ] = array(
				'body'   => $row->template_body,
				'status' => $row->status,
			);
		}

		return $result;
	}

	/**
	 * Get a specific active integration instance.
	 *
	 * @param string $slug Integration slug.
	 * @return CAPFW_Integration_Base|null
	 */
	public function get( string $slug ): ?CAPFW_Integration_Base {
		return $this->active_integrations[ $slug ] ?? null;
	}
}
