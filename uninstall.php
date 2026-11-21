<?php
/**
 * WPFH Frictionless Checkout Uninstall
 *
 * @package WPFH_Frictionless_Checkout
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'wpfh_fc_enable_frictionless' );
delete_option( 'wpfh_fc_enable_geoip' );
delete_option( 'wpfh_fc_enable_free_capture' );
delete_option( 'wpfh_fc_show_trust_badges' );
delete_option( 'wpfh_fc_free_redirect' );
delete_option( 'wpfh_fc_abandonment_webhook_url' );
delete_option( 'wpfh_fc_recent_abandoned_leads' );
delete_option( 'wpfh_fc_custom_badges' );
delete_option( 'wpfh_fc_custom_badges_text' );

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall cleanup of transients.
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_wpfh_fc_cart_%' OR option_name LIKE '_transient_timeout_wpfh_fc_cart_%'" );
