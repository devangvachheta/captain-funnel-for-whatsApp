<?php
/**
 * Funnel Runner — schedules and processes funnel steps.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CAPFW_Funnel_Runner
 */
class CAPFW_Funnel_Runner {

	/**
	 * Register hooks.
	 *
	 * Fix #2: WooCommerce-specific actions (WC_Order type-hints, wc_get_order calls)
	 * are only registered when WooCommerce is actually active — prevents PHP Fatal
	 * errors on sites without WooCommerce when the cron fires.
	 */
	public function init() {
		add_action( 'capfw_process_scheduled_messages', array( $this, 'process_scheduled_messages' ) );

		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		add_action( 'capfw_order_completed_trigger', array( $this, 'schedule_funnel_for_order' ), 10, 2 );
		add_action( 'capfw_order_created_trigger',   array( $this, 'schedule_funnel_on_created' ), 10, 1 );
		add_action( 'capfw_send_funnel_step',        array( $this, 'send_funnel_step_message' ),  10, 2 );
	}

	/**
	 * Schedule funnels when an order is completed.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order    Order object.
	 */
	public function schedule_funnel_for_order( int $order_id, WC_Order $order ) {
		$funnels = $this->get_active_funnels_by_trigger( 'order_completed' );
		foreach ( $funnels as $funnel ) {
			if ( $this->passes_conditions( $funnel->id, $order ) ) {
				$this->schedule_funnel_steps( (int) $funnel->id, $order_id );
			}
		}
	}

	/**
	 * Schedule funnels when an order is created.
	 *
	 * @param int $order_id Order ID.
	 */
	public function schedule_funnel_on_created( int $order_id ) {
		$order   = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$funnels = $this->get_active_funnels_by_trigger( 'order_created' );
		foreach ( $funnels as $funnel ) {
			if ( $this->passes_conditions( (int) $funnel->id, $order ) ) {
				$this->schedule_funnel_steps( (int) $funnel->id, $order_id );
			}
		}
	}

	/**
	 * Schedule individual funnel steps as one-time WP-Cron events.
	 *
	 * @param int $funnel_id Funnel ID.
	 * @param int $order_id  Order ID.
	 */
	private function schedule_funnel_steps( int $funnel_id, int $order_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'capfw_funnel_steps';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin-triggered funnel scheduling; no caching needed.
		$steps = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses $wpdb->prefix only.
				"SELECT * FROM {$table} WHERE funnel_id = %d AND status = 'active' ORDER BY sort_order ASC",
				$funnel_id
			)
		);

		if ( empty( $steps ) ) {
			return;
		}

		$cumulative_delay = 0;

		foreach ( $steps as $step ) {
			$delay_seconds     = $this->delay_to_seconds( (int) $step->delay_value, $step->delay_unit );
			$cumulative_delay += $delay_seconds;
			$send_at           = time() + $cumulative_delay;

			// Fix #5: Pass only order_id + step_id — template is fetched fresh
			// from DB in send_funnel_step_message(). Avoids serialising large
			// TEXT blobs into wp_options (cron arg store) which causes duplication
			// issues when the same template is scheduled multiple times.
			wp_schedule_single_event(
				$send_at,
				'capfw_send_funnel_step',
				array( $order_id, (int) $step->id )
			);
		}
	}

	/**
	 * Send a single funnel step message (called by WP-Cron).
	 *
	 * Fix #5: Signature reduced to (order_id, step_id) — template is now fetched
	 * fresh from the DB here instead of being passed as a cron argument, which
	 * previously caused large TEXT blobs to be serialised into wp_options.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @param int $step_id  Funnel step row ID.
	 */
	public function send_funnel_step_message( int $order_id, int $step_id ) {
		global $wpdb;

		// Fetch the step's template fresh from DB.
		$step = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT message_template FROM {$wpdb->prefix}capfw_funnel_steps WHERE id = %d",
				$step_id
			)
		);

		if ( ! $step || empty( $step->message_template ) ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$phone = preg_replace( '/[^0-9]/', '', $order->get_billing_phone() );
		if ( empty( $phone ) ) {
			return;
		}

		$order_hooks = new CAPFW_Order_Hooks();
		$message     = $order_hooks->parse_template( $step->message_template, $order );

		$result = CAPFW_WhatsApp_API::send_message( $phone, $message );

		CAPFW_Logger::log(
			array(
				'order_id'       => $order_id,
				'customer_phone' => $phone,
				'message'        => $message,
				'status'         => $result['success'] ? 'sent' : 'failed',
				'response'       => $result['response'],
			)
		);
	}

	/**
	 * Placeholder hook for future batch processing via hourly cron.
	 */
	public function process_scheduled_messages() {
		// WP-Cron single events handle individual messages; this hook can be used for future batch logic.
		do_action( 'capfw_batch_process' );
	}

	/**
	 * Get active funnels by trigger event.
	 *
	 * @param string $trigger Trigger event key.
	 * @return array
	 */
	private function get_active_funnels_by_trigger( string $trigger ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'capfw_funnels';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Runtime funnel lookup; acceptable without transient.
		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name uses $wpdb->prefix only.
				"SELECT * FROM {$table} WHERE trigger_event = %s AND status = 'active'",
				$trigger
			)
		);
	}

	/**
	 * Check if an order passes funnel conditions.
	 *
	 * Basic implementation; extend with category/product/total checks as needed.
	 *
	 * @param int      $funnel_id Funnel ID.
	 * @param WC_Order $order     Order object.
	 * @return bool
	 */
	private function passes_conditions( int $funnel_id, WC_Order $order ): bool {
		/**
		 * Filters whether an order passes funnel conditions.
		 *
		 * @param bool     $passes    Default true (no conditions = always passes).
		 * @param int      $funnel_id Funnel ID being evaluated.
		 * @param WC_Order $order     WooCommerce order.
		 */
		return (bool) apply_filters( 'capfw_funnel_passes_conditions', true, $funnel_id, $order );
	}

	/**
	 * Convert delay value + unit to seconds.
	 *
	 * @param int    $value Delay amount.
	 * @param string $unit  'hours', 'days', or 'weeks'.
	 * @return int
	 */
	private function delay_to_seconds( int $value, string $unit ): int {
		switch ( $unit ) {
			case 'hours':
				return $value * HOUR_IN_SECONDS;
			case 'weeks':
				return $value * WEEK_IN_SECONDS;
			case 'days':
			default:
				return $value * DAY_IN_SECONDS;
		}
	}
}
