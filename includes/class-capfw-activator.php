<?php
/**
 * Fired during plugin activation.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Activator
 */
class CAPFW_Activator {

	/**
	 * Run on activation.
	 */
	public static function activate() {
		self::create_tables();
		self::maybe_upgrade_tables();
		self::schedule_cron();
	}

	/**
	 * Create custom database tables.
	 */
	private static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// ── wp_capfw_logs ──────────────────────────────────────────────────────
		$table_logs = $wpdb->prefix . 'capfw_logs';
		$sql_logs   = "CREATE TABLE IF NOT EXISTS {$table_logs} (
			id                BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			order_id          BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			customer_phone    VARCHAR(30)  NOT NULL DEFAULT '',
			message           TEXT         NOT NULL,
			status            VARCHAR(20)  NOT NULL DEFAULT 'pending',
			response          TEXT,
			integration_slug  VARCHAR(50)  NOT NULL DEFAULT '',
			trigger_key       VARCHAR(100) NOT NULL DEFAULT '',
			created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY order_id (order_id),
			KEY status (status),
			KEY integration_slug (integration_slug)
		) {$charset_collate};";

		// ── wp_capfw_funnels ───────────────────────────────────────────────────
		$table_funnels = $wpdb->prefix . 'capfw_funnels';
		$sql_funnels   = "CREATE TABLE IF NOT EXISTS {$table_funnels} (
			id                BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			funnel_name       VARCHAR(200) NOT NULL DEFAULT '',
			trigger_event     VARCHAR(100) NOT NULL DEFAULT '',
			integration_slug  VARCHAR(50)  NOT NULL DEFAULT '',
			status            VARCHAR(10)  NOT NULL DEFAULT 'active',
			created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) {$charset_collate};";

		// ── wp_capfw_funnel_steps ──────────────────────────────────────────────
		$table_steps = $wpdb->prefix . 'capfw_funnel_steps';
		$sql_steps   = "CREATE TABLE IF NOT EXISTS {$table_steps} (
			id                BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			funnel_id         BIGINT(20) UNSIGNED NOT NULL,
			step_name         VARCHAR(200) NOT NULL DEFAULT '',
			delay_value       INT(11)      NOT NULL DEFAULT 0,
			delay_unit        VARCHAR(10)  NOT NULL DEFAULT 'days',
			message_template  TEXT         NOT NULL,
			status            VARCHAR(10)  NOT NULL DEFAULT 'active',
			sort_order        INT(11)      NOT NULL DEFAULT 0,
			PRIMARY KEY (id),
			KEY funnel_id (funnel_id)
		) {$charset_collate};";

		// ── wp_capfw_templates — NEW unified template store ────────────────────
		$table_templates = $wpdb->prefix . 'capfw_templates';
		$sql_templates   = "CREATE TABLE IF NOT EXISTS {$table_templates} (
			id                BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			integration_slug  VARCHAR(50)  NOT NULL DEFAULT '',
			trigger_key       VARCHAR(100) NOT NULL DEFAULT '',
			template_body     TEXT         NOT NULL,
			status            VARCHAR(10)  NOT NULL DEFAULT 'active',
			updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY integration_trigger (integration_slug, trigger_key)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_logs );
		dbDelta( $sql_funnels );
		dbDelta( $sql_steps );
		dbDelta( $sql_templates );

		update_option( 'capfw_db_version', CAPFW_VERSION );
	}

	/**
	 * Add new columns to existing tables (for updates from older versions).
	 */
	private static function maybe_upgrade_tables() {
		global $wpdb;

		// Add integration_slug + trigger_key to capfw_logs if missing (fresh installs
		// before v0.0.1 used an older schema without these columns).
		$col = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SHOW COLUMNS FROM `{$wpdb->prefix}capfw_logs` LIKE 'integration_slug'"
		);
		if ( empty( $col ) ) {
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}capfw_logs` ADD COLUMN `integration_slug` VARCHAR(50) NOT NULL DEFAULT '' AFTER `response`, ADD COLUMN `trigger_key` VARCHAR(100) NOT NULL DEFAULT '' AFTER `integration_slug`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
		}

		// Add integration_slug to capfw_funnels if missing.
		$col2 = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SHOW COLUMNS FROM `{$wpdb->prefix}capfw_funnels` LIKE 'integration_slug'"
		);
		if ( empty( $col2 ) ) {
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}capfw_funnels` ADD COLUMN `integration_slug` VARCHAR(50) NOT NULL DEFAULT '' AFTER `trigger_event`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
		}
	}

	/**
	 * Schedule WP-Cron event.
	 */
	private static function schedule_cron() {
		if ( ! wp_next_scheduled( 'capfw_process_scheduled_messages' ) ) {
			wp_schedule_event( time(), 'hourly', 'capfw_process_scheduled_messages' );
		}
	}
}
