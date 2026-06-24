<?php
/**
 * Logger — stores WhatsApp message logs in DB.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Logger
 */
class CAPFW_Logger {

	/**
	 * Insert a log entry.
	 *
	 * @param array $data Log data.
	 * @return int|false Inserted row ID or false on failure.
	 */
	public static function log( array $data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'capfw_logs';

		$inserted = $wpdb->insert(
			$table,
			array(
				'order_id'         => absint( $data['order_id'] ?? 0 ),
				'customer_phone'   => sanitize_text_field( $data['customer_phone'] ?? '' ),
				'message'          => wp_kses_post( $data['message'] ?? '' ),
				'status'           => sanitize_text_field( $data['status'] ?? 'pending' ),
				'response'         => wp_kses_post( $data['response'] ?? '' ),
				'integration_slug' => sanitize_key( $data['integration_slug'] ?? 'woocommerce' ),
				'trigger_key'      => sanitize_key( $data['trigger_key'] ?? '' ),
				'created_at'       => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Update a log entry status.
	 *
	 * @param int    $log_id  Log row ID.
	 * @param string $status  New status.
	 * @param string $response API response string.
	 */
	public static function update_status( int $log_id, string $status, string $response = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . 'capfw_logs';

		$wpdb->update(
			$table,
			array(
				'status'   => sanitize_text_field( $status ),
				'response' => wp_kses_post( $response ),
			),
			array( 'id' => absint( $log_id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get paginated logs.
	 *
	 * @param int $per_page Rows per page.
	 * @param int $paged    Current page number.
	 * @return array
	 */
	public static function get_logs( int $per_page = 20, int $paged = 1 ): array {
		global $wpdb;

		$table  = $wpdb->prefix . 'capfw_logs';
		$offset = ( $paged - 1 ) * $per_page;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Simple paginated admin query; no user-provided variables in table name.
		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses $wpdb->prefix only, no user data.
				"SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);
	}

	/**
	 * Get total log count.
	 *
	 * @return int
	 */
	public static function get_total_count(): int {
		global $wpdb;
		$table = $wpdb->prefix . 'capfw_logs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Simple count query for admin pagination.
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses $wpdb->prefix only.
	}

	/**
	 * Get message stats for dashboard.
	 *
	 * @return array
	 */
	public static function get_stats(): array {
		global $wpdb;
		$table = $wpdb->prefix . 'capfw_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin dashboard stats; acceptable without caching.
		$rows = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses $wpdb->prefix only.
			"SELECT status, COUNT(*) as cnt FROM {$table} GROUP BY status"
		);

		$stats = array(
			'sent'    => 0,
			'failed'  => 0,
			'pending' => 0,
		);

		foreach ( $rows as $row ) {
			if ( isset( $stats[ $row->status ] ) ) {
				$stats[ $row->status ] = (int) $row->cnt;
			}
		}

		return $stats;
	}

	/**
	 * Delete ALL log entries.
	 *
	 * @return int|false Number of rows deleted, or false on failure.
	 */
	public static function clear_all() {
		global $wpdb;
		$table = $wpdb->prefix . 'capfw_logs';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin-triggered bulk delete; table name uses $wpdb->prefix only.
		return $wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses $wpdb->prefix only, no user data.
	}

	/**
	 * Delete log entries older than a given number of days.
	 *
	 * @param int $days Entries older than this many days will be deleted.
	 * @return int|false Number of rows deleted, or false on failure.
	 */
	public static function clear_older_than( int $days ) {
		global $wpdb;
		$table = $wpdb->prefix . 'capfw_logs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin-triggered bulk delete; table name uses $wpdb->prefix only.
		return $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses $wpdb->prefix only, no user data.
				"DELETE FROM {$table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);
	}
}
