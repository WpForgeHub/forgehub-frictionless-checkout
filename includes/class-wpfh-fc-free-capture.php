<?php
/**
 * WPFH Frictionless Checkout Zero-Dollar Free Lead Capture
 *
 * @package WPFH_Frictionless_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPFH_FC_Free_Capture {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'woocommerce_cart_needs_payment', array( $this, 'cart_needs_payment' ), 10, 2 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_free_order' ), 20, 3 );
		add_filter( 'woocommerce_payment_successful_result', array( $this, 'custom_redirect_after_checkout' ), 20, 2 );
	}

	/**
	 * Total 0 carts do not require payment gateway
	 */
	public function cart_needs_payment( $needs_payment, $cart ) {
		if ( 'yes' !== get_option( 'wpfh_fc_enable_free_capture', 'yes' ) ) {
			return $needs_payment;
		}
		if ( 0.0 == $cart->total ) {
			return false;
		}
		return $needs_payment;
	}

	/**
	 * Auto-complete free orders immediately upon placement
	 */
	public function process_free_order( $order_id, $posted_data, $order ) {
		if ( 'yes' !== get_option( 'wpfh_fc_enable_free_capture', 'yes' ) ) {
			return;
		}

		if ( ! $order ) {
			$order = wc_get_order( $order_id );
		}

		if ( ! $order ) {
			return;
		}

		if ( 0.0 == $order->get_total() ) {
			// Ensure customer account is properly associated before completing payment
			$customer_id = $order->get_customer_id();
			if ( ! $customer_id && $order->get_billing_email() ) {
				$user = get_user_by( 'email', $order->get_billing_email() );
				if ( ! $user && 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' ) ) {
					$username = sanitize_user( current( explode( '@', $order->get_billing_email() ) ), true );
					if ( empty( $username ) || username_exists( $username ) ) {
						$username = 'cust_' . wp_generate_password( 6, false );
					}
					$password = wp_generate_password();
					$new_user_id = function_exists( 'wc_create_new_customer' )
						? wc_create_new_customer( $order->get_billing_email(), $username, $password )
						: wp_create_user( $username, $password, $order->get_billing_email() );

					if ( ! is_wp_error( $new_user_id ) ) {
						$new_user = new WP_User( $new_user_id );
						$new_user->set_role( 'customer' );
						update_user_meta( $new_user_id, 'first_name', $order->get_billing_first_name() );
						update_user_meta( $new_user_id, 'last_name', $order->get_billing_last_name() );
						update_user_meta( $new_user_id, 'billing_country', $order->get_billing_country() );
						$order->set_customer_id( $new_user_id );
						$order->save();

						// Log customer in automatically
						if ( ! is_user_logged_in() ) {
							wp_set_current_user( $new_user_id );
							wp_set_auth_cookie( $new_user_id );
						}
					}
				} elseif ( $user ) {
					$order->set_customer_id( $user->ID );
					$order->save();
				}
			}

			// Auto complete free orders
			$order->set_payment_method( 'wpfh_fc_free' );
			$order->set_payment_method_title( __( 'Free Digital Download', 'forgehub-frictionless-checkout' ) );
			$order->payment_complete();
			$order->update_status( 'completed', __( 'Order auto-completed by Frictionless Checkout.', 'forgehub-frictionless-checkout' ) );

			// Explicitly grant downloadable product permissions to this order and customer
			if ( function_exists( 'wc_downloadable_product_permissions' ) ) {
				wc_downloadable_product_permissions( $order->get_id(), true );
			}
		}
	}

	/**
	 * Seamless redirect after successful zero-dollar capture
	 */
	public function custom_redirect_after_checkout( $result, $order_id ) {
		if ( 'yes' !== get_option( 'wpfh_fc_enable_free_capture', 'yes' ) ) {
			return $result;
		}
		$order = wc_get_order( $order_id );
		if ( $order && 0.0 == $order->get_total() ) {
			// Check if custom redirect URL configured in settings
			$redirect_dest = get_option( 'wpfh_fc_free_redirect', 'my_account_downloads' );
			if ( 'my_account_downloads' === $redirect_dest && function_exists( 'wc_get_account_endpoint_url' ) ) {
				$result['redirect'] = wc_get_account_endpoint_url( 'downloads' );
			}
		}
		return $result;
	}
}
