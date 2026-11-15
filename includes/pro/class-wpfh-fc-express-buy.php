<?php
/**
 * WPFH Frictionless Checkout — Pro Express 1-Click Buy Links
 *
 * Enables direct purchase URLs (?wpfh_buy=PRODUCT_ID) bypassing cart page,
 * supporting instant coupon application and seamless checkout routing.
 *
 * @package WPForgeHub\FrictionlessCheckout\Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPFH_FC_Express_Buy {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'template_redirect', array( $this, 'handle_express_buy_url' ), 5 );
        add_shortcode( 'wpfh_buy_button', array( $this, 'render_buy_button_shortcode' ) );
    }

    /**
     * Intercept ?wpfh_buy=ID query parameter
     */
    public function handle_express_buy_url() {
        if ( ! isset( $_GET['wpfh_buy'] ) ) {
            return;
        }

        $product_id = absint( $_GET['wpfh_buy'] );
        if ( ! $product_id ) {
            return;
        }

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return;
        }

        // Optional clear existing cart for pure 1-click express checkout
        $clear_cart = isset( $_GET['clear'] ) ? (bool) $_GET['clear'] : true;
        if ( $clear_cart ) {
            WC()->cart->empty_cart();
        }

        $quantity = isset( $_GET['qty'] ) ? max( 1, absint( $_GET['qty'] ) ) : 1;
        $added = WC()->cart->add_to_cart( $product_id, $quantity );

        // Apply discount coupon if passed in query string (?wpfh_buy=123&coupon=PROMO)
        if ( ! empty( $_GET['coupon'] ) ) {
            $coupon_code = sanitize_text_field( $_GET['coupon'] );
            if ( ! WC()->cart->has_discount( $coupon_code ) ) {
                WC()->cart->apply_coupon( $coupon_code );
            }
        }

        if ( $added ) {
            $checkout_url = wc_get_checkout_url();
            wp_safe_redirect( $checkout_url );
            if ( ! defined( 'WPFH_TEST_RUN' ) ) {
                exit;
            }
        }
    }

    /**
     * Shortcode: [wpfh_buy_button id="123" text="Instant Checkout" coupon="SAVE20"]
     */
    public function render_buy_button_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id'     => 0,
            'text'   => __( '⚡ Instant Checkout', 'wpfh-frictionless-checkout' ),
            'coupon' => '',
            'class'  => 'button wpfh-express-buy-btn',
        ), $atts, 'wpfh_buy_button' );

        $product_id = absint( $atts['id'] );
        if ( ! $product_id ) {
            return '';
        }

        $params = array( 'wpfh_buy' => $product_id );
        if ( ! empty( $atts['coupon'] ) ) {
            $params['coupon'] = sanitize_text_field( $atts['coupon'] );
        }

        $buy_url = add_query_arg( $params, home_url( '/' ) );

        return sprintf(
            '<a href="%s" class="%s" style="background:#10b981; color:#0f172a; padding:10px 20px; font-weight:700; border-radius:8px; text-decoration:none; display:inline-block;">%s</a>',
            esc_url( $buy_url ),
            esc_attr( $atts['class'] ),
            esc_html( $atts['text'] )
        );
    }
}
