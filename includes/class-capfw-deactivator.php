<?php
/**
 * Fired during plugin deactivation.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Deactivator
 */
class CAPFW_Deactivator {

	/**
	 * Run on deactivation.
	 */
	public static function deactivate() {
		// Clear scheduled cron.
		$timestamp = wp_next_scheduled( 'capfw_process_scheduled_messages' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'capfw_process_scheduled_messages' );
		}
	}
}
