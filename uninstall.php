<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package captain-funnel-for-whatsapp
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom tables.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching -- DDL statements exempt from prepare(); dropping plugin tables on uninstall.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}capfw_logs" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching -- DDL statements exempt from prepare(); dropping plugin tables on uninstall.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}capfw_funnels" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching -- DDL statements exempt from prepare(); dropping plugin tables on uninstall.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}capfw_funnel_steps" );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.DirectDatabaseQuery.NoCaching -- DDL statements exempt from prepare(); dropping plugin tables on uninstall.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}capfw_templates" );

// Delete plugin options.
delete_option( 'capfw_settings' );
delete_option( 'capfw_db_version' );

// Remove scheduled cron events.
$timestamp = wp_next_scheduled( 'capfw_process_scheduled_messages' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'capfw_process_scheduled_messages' );
}
