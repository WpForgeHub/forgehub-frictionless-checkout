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
