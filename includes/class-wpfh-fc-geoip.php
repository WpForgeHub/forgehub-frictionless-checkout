<?php
/**
 * WPFH Frictionless Checkout GeoIP Detector
 *
 * @package WPFH_Frictionless_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPFH_FC_GeoIP {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'default_checkout_billing_country', array( $this, 'get_default_country' ), 99 );
	}

	/**
	 * Detect customer country from headers or WC Geolocation
	 *
	 * @return string 2-letter ISO country code (e.g. 'TR', 'US', 'DE')
	 */
	public function get_detected_country() {
		// 1. Cloudflare header
		if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			$country = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) );
			if ( 2 === strlen( $country ) && 'XX' !== $country && 'T1' !== $country ) {
				return $country;
			}
		}

		// 2. Standard CDN / GeoIP headers
		$headers = array(
			'HTTP_X_COUNTRY_CODE',
			'GEOIP_COUNTRY_CODE',
			'HTTP_GEOIP_COUNTRY_CODE',
		);

		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$country = strtoupper( sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) ) );
				if ( 2 === strlen( $country ) ) {
					return $country;
				}
			}
		}

		// 3. WooCommerce Core Geolocation if available
		if ( class_exists( 'WC_Geolocation' ) ) {
			$ip = WC_Geolocation::get_ip_address();
			if ( $ip ) {
				$geo = WC_Geolocation::geolocate_ip( $ip, true );
				if ( ! empty( $geo['country'] ) ) {
					return strtoupper( $geo['country'] );
				}
			}
		}

		// 4. Default fallback: WooCommerce base country
		$base_country = get_option( 'woocommerce_default_country', 'US' );
		if ( false !== strpos( $base_country, ':' ) ) {
			$parts = explode( ':', $base_country );
			$base_country = $parts[0];
		}

		return $base_country ? $base_country : 'US';
	}

	/**
	 * Filter default checkout billing country
	 */
	public function get_default_country( $default ) {
		// If user is logged in and has saved billing country, respect it
		if ( is_user_logged_in() ) {
			$saved = get_user_meta( get_current_user_id(), 'billing_country', true );
			if ( ! empty( $saved ) ) {
				return $saved;
			}
		}

		return $this->get_detected_country();
	}
}
