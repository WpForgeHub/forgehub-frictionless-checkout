<?php
/**
 * WPFH Frictionless Checkout — Pro On-Blur Cart Abandonment Lead Capture
 *
 * Captures email addresses instantly when customer leaves the billing email field,
 * logs abandonments, provides 1-click cart restoration tokens, and triggers webhook alerts.
 *
 * @package WPForgeHub\FrictionlessCheckout\Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPFH_FC_Abandonment {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_footer', array( $this, 'render_on_blur_listener' ) );
        add_action( 'wp_ajax_wpfh_fc_capture_lead', array( $this, 'ajax_capture_lead' ) );
        add_action( 'wp_ajax_nopriv_wpfh_fc_capture_lead', array( $this, 'ajax_capture_lead' ) );
        add_action( 'template_redirect', array( $this, 'handle_restore_cart' ) );
        add_action( 'woocommerce_thankyou', array( $this, 'mark_cart_recovered' ) );
    }

    public function render_on_blur_listener() {
        if ( ! is_checkout() || is_order_received_page() ) {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            var capturedEmail = '';
            $('#billing_email').on('blur', function() {
                var email = $(this).val().trim();
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email) || email === capturedEmail) {
                    return;
                }
                capturedEmail = email;

                $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                    action: 'wpfh_fc_capture_lead',
                    email: email,
                    first_name: $('#billing_first_name').val() || '',
                    last_name: $('#billing_last_name').val() || '',
                    _ajax_nonce: '<?php echo wp_create_nonce( 'wpfh_fc_lead_nonce' ); ?>'
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_capture_lead() {
        check_ajax_referer( 'wpfh_fc_lead_nonce' );

        $email = sanitize_email( $_POST['email'] ?? '' );
        if ( ! is_email( $email ) ) {
            wp_send_json_error( 'Invalid email' );
        }

        if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
            wp_send_json_error( 'Cart is empty' );
        }

        $items = array();
        foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
            $product = $values['data'];
            $items[] = array(
                'product_id' => $values['product_id'],
                'name'       => $product->get_name(),
                'quantity'   => $values['quantity'],
                'subtotal'   => $values['line_subtotal'],
            );
        }

        $token = wp_generate_password( 24, false, false );
        $lead_data = array(
            'token'      => $token,
            'email'      => $email,
            'first_name' => sanitize_text_field( $_POST['first_name'] ?? '' ),
            'last_name'  => sanitize_text_field( $_POST['last_name'] ?? '' ),
            'total'      => WC()->cart->get_total( 'edit' ),
            'currency'   => get_woocommerce_currency(),
            'items'      => $items,
            'time'       => current_time( 'mysql' ),
            'status'     => 'abandoned',
        );

        // Store in transient for 7 days
        set_transient( 'wpfh_fc_cart_' . $token, $lead_data, 7 * DAY_IN_SECONDS );

        // Maintain log of recent 50 abandoned carts
        $recent = get_option( 'wpfh_fc_recent_abandoned_leads', array() );
        $recent[ $email ] = array(
            'token' => $token,
            'total' => $lead_data['total'],
            'time'  => $lead_data['time'],
            'items' => count( $items ),
        );
        if ( count( $recent ) > 50 ) {
            $recent = array_slice( $recent, -50, 50, true );
        }
        update_option( 'wpfh_fc_recent_abandoned_leads', $recent, false );

        // Webhook dispatch if webhook URL is configured
        $webhook_url = get_option( 'wpfh_fc_abandonment_webhook_url' );
        if ( ! empty( $webhook_url ) && filter_var( $webhook_url, FILTER_VALIDATE_URL ) ) {
            $recovery_url = add_query_arg( array( 'wpfh_restore_cart' => $token ), wc_get_checkout_url() );
            $lead_data['recovery_url'] = $recovery_url;

            wp_remote_post( $webhook_url, array(
                'blocking' => false,
                'headers'  => array( 'Content-Type' => 'application/json' ),
                'body'     => wp_json_encode( $lead_data ),
            ) );
        }

        wp_send_json_success( array( 'token' => $token ) );
    }

    public function handle_restore_cart() {
        if ( empty( $_GET['wpfh_restore_cart'] ) ) {
            return;
        }

        $token = sanitize_key( $_GET['wpfh_restore_cart'] );
        $lead_data = get_transient( 'wpfh_fc_cart_' . $token );

        if ( empty( $lead_data ) || empty( $lead_data['items'] ) ) {
            wc_add_notice( __( 'Your saved cart session has expired. Please select your items again.', 'wpfh-frictionless-checkout' ), 'notice' );
            return;
        }

        if ( function_exists( 'WC' ) && WC()->cart ) {
            WC()->cart->empty_cart();
            foreach ( $lead_data['items'] as $item ) {
                WC()->cart->add_to_cart( $item['product_id'], $item['quantity'] );
            }
            wc_add_notice( __( '🛒 Your previous cart items have been successfully restored!', 'wpfh-frictionless-checkout' ), 'success' );
            wp_safe_redirect( wc_get_checkout_url() );
            exit;
        }
    }

    public function mark_cart_recovered( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }
        $email = $order->get_billing_email();
        $recent = get_option( 'wpfh_fc_recent_abandoned_leads', array() );
        if ( isset( $recent[ $email ] ) ) {
            $recent[ $email ]['status'] = 'recovered';
            $recent[ $email ]['recovered_order_id'] = $order_id;
            update_option( 'wpfh_fc_recent_abandoned_leads', $recent, false );
        }
    }
}
