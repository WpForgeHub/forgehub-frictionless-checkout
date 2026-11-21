<?php
/**
 * WPFH Frictionless Checkout — Pro EU B2B VIES VAT Validator & Reverse Charge
 *
 * Implements real-time European Commission VIES database validation,
 * automatic VAT zero-rating (reverse charge) for cross-border B2B purchases,
 * and immutable order audit records.
 *
 * @package WPForgeHub\FrictionlessCheckout\Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPFH_FC_EU_VAT {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter( 'woocommerce_checkout_fields', array( $this, 'inject_vat_field' ), 1005 );
        add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'render_vat_client_script' ) );
        add_action( 'wp_ajax_wpfh_fc_validate_vat', array( $this, 'ajax_validate_vat' ) );
        add_action( 'wp_ajax_nopriv_wpfh_fc_validate_vat', array( $this, 'ajax_validate_vat' ) );
        add_action( 'woocommerce_checkout_create_order', array( $this, 'save_vat_order_meta' ), 20, 2 );
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_vat_in_admin_order' ) );
    }

    public function inject_vat_field( $fields ) {
        $fields['billing']['billing_vat_id'] = array(
            'label'       => __( 'EU VAT Number (Optional for B2B)', 'forgehub-frictionless-checkout' ),
            'placeholder' => __( 'e.g. DE123456789 or FR12345678901', 'forgehub-frictionless-checkout' ),
            'required'    => false,
            'class'       => array( 'form-row-wide', 'wpfh-fc-vat-row' ),
            'clear'       => true,
            'priority'    => 25,
        );
        return $fields;
    }

    public function render_vat_client_script() {
        if ( ! is_checkout() ) {
            return;
        }
        ?>
        <div id="wpfh-vat-status-message" style="display:none; margin-bottom:12px; font-size:13px; font-weight:600; padding:8px 12px; border-radius:6px;"></div>
        <script>
        jQuery(document).ready(function($) {
            var vatTimer = null;
            $('#billing_vat_id').on('input', function() {
                var vat = $(this).val().trim();
                clearTimeout(vatTimer);
                if (vat.length < 5) {
                    $('#wpfh-vat-status-message').hide();
                    vatTimer = setTimeout(function() {
                        $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                            action: 'wpfh_fc_validate_vat',
                            vat_id: '',
                            _ajax_nonce: '<?php echo esc_js( wp_create_nonce( 'wpfh_fc_vat_nonce' ) ); ?>'
                        }, function() {
                            $('body').trigger('update_checkout');
                        });
                    }, 300);
                    return;
                }
                vatTimer = setTimeout(function() {
                    $('#wpfh-vat-status-message').show().css({'background':'#1e293b', 'color':'#38bdf8'}).text('Validating with EU VIES database...');
                    $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                        action: 'wpfh_fc_validate_vat',
                        vat_id: vat,
                        _ajax_nonce: '<?php echo esc_js( wp_create_nonce( 'wpfh_fc_vat_nonce' ) ); ?>'
                    }, function(res) {
                        if (res.success && res.data.valid) {
                            $('#wpfh-vat-status-message').css({'background':'#064e3b', 'color':'#34d399'}).text('✓ ' + (res.data.message || 'Valid EU VAT.'));
                        } else {
                            $('#wpfh-vat-status-message').css({'background':'#450a0a', 'color':'#f87171'}).text('⚠️ ' + ((res.data && res.data.message) ? res.data.message : 'Invalid EU VAT. Standard tax will apply.'));
                        }
                        $('body').trigger('update_checkout');
                    });
                }, 600);
            });
        });
        </script>
        <?php
    }

    private function clear_vat_exemption() {
        if ( function_exists( 'WC' ) ) {
            if ( WC()->customer ) {
                WC()->customer->set_is_vat_exempt( false );
            }
            if ( WC()->session ) {
                WC()->session->set( 'wpfh_fc_validated_vat', null );
            }
        }
    }

    public function ajax_validate_vat() {
        check_ajax_referer( 'wpfh_fc_vat_nonce' );

        $raw_input = isset( $_POST['vat_id'] ) ? sanitize_text_field( wp_unslash( $_POST['vat_id'] ) ) : '';
        $raw_vat   = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', $raw_input ) );

        if ( '' === $raw_vat ) {
            $this->clear_vat_exemption();
            wp_send_json_success( array( 'valid' => false, 'cleared' => true ) );
        }

        if ( strlen( $raw_vat ) < 4 ) {
            $this->clear_vat_exemption();
            wp_send_json_error( array( 'valid' => false, 'message' => __( 'VAT ID is too short.', 'forgehub-frictionless-checkout' ) ) );
        }

        $country = substr( $raw_vat, 0, 2 );
        $number  = substr( $raw_vat, 2 );

        // European Union member state codes (EL is Greece in VIES).
        $eu_countries = array( 'AT','BE','BG','CY','CZ','DE','DK','EE','EL','ES','FI','FR','HR','HU','IE','IT','LT','LU','LV','MT','NL','PL','PT','RO','SE','SI','SK' );

        if ( ! in_array( $country, $eu_countries, true ) ) {
            $this->clear_vat_exemption();
            wp_send_json_error( array( 'valid' => false, 'message' => __( 'Country prefix is not a recognized EU member state.', 'forgehub-frictionless-checkout' ) ) );
        }

        // Live VIES API query with timeout — never blindly exempt on failure/timeout.
        $endpoint = sprintf( 'https://ec.europa.eu/taxation_customs/vies/rest-api/ms/%s/vat/%s', $country, $number );
        $response = wp_remote_get( $endpoint, array( 'timeout' => 5 ) );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            $this->clear_vat_exemption();
            wp_send_json_error( array(
                'valid'   => false,
                'message' => __( 'EU VIES verification service is temporarily unavailable. Standard tax will apply.', 'forgehub-frictionless-checkout' ),
            ) );
        }

        $data         = json_decode( wp_remote_retrieve_body( $response ), true );
        $is_valid     = ! empty( $data['isValid'] );
        $company_name = sanitize_text_field( $data['name'] ?? '' );

        if ( ! $is_valid ) {
            $this->clear_vat_exemption();
            wp_send_json_error( array(
                'valid'   => false,
                'message' => __( 'VIES verification failed: Invalid VAT number for this jurisdiction.', 'forgehub-frictionless-checkout' ),
            ) );
        }

        // Cross-border check: EU reverse charge only applies when buyer country differs from store base country.
        $base_country = ( function_exists( 'WC' ) && isset( WC()->countries ) ) ? strtoupper( WC()->countries->get_base_country() ) : '';
        $buyer_iso    = ( 'EL' === $country ) ? 'GR' : $country;
        $is_domestic  = ( ! empty( $base_country ) && $buyer_iso === $base_country );

        if ( function_exists( 'WC' ) && WC()->customer && WC()->session ) {
            if ( $is_domestic ) {
                WC()->customer->set_is_vat_exempt( false );
                WC()->session->set( 'wpfh_fc_validated_vat', array(
                    'vat_id'         => $raw_vat,
                    'company_name'   => $company_name,
                    'reverse_charge' => false,
                    'timestamp'      => current_time( 'mysql' ),
                ) );
                wp_send_json_success( array(
                    'valid'   => true,
                    'vat_id'  => $raw_vat,
                    'name'    => $company_name,
                    'message' => __( 'Valid domestic VAT number recorded. Standard domestic VAT applies.', 'forgehub-frictionless-checkout' ),
                ) );
            }

            WC()->customer->set_is_vat_exempt( true );
            WC()->session->set( 'wpfh_fc_validated_vat', array(
                'vat_id'         => $raw_vat,
                'company_name'   => $company_name,
                'reverse_charge' => true,
                'timestamp'      => current_time( 'mysql' ),
            ) );

            wp_send_json_success( array(
                'valid'   => true,
                'vat_id'  => $raw_vat,
                'name'    => $company_name,
                'message' => __( 'Valid EU VAT number. Reverse-charge tax exemption (0% VAT) applied.', 'forgehub-frictionless-checkout' ),
            ) );
        }

        $this->clear_vat_exemption();
        wp_send_json_error( array(
            'valid'   => false,
            'message' => __( 'Unable to update tax session.', 'forgehub-frictionless-checkout' ),
        ) );
    }

    public function save_vat_order_meta( $order, $data ) {
        $vat_id = isset( $_POST['billing_vat_id'] ) ? strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', sanitize_text_field( wp_unslash( $_POST['billing_vat_id'] ) ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce checkout nonce already verified.
        if ( ! empty( $vat_id ) ) {
            $order->update_meta_data( '_billing_vat_id', $vat_id );

            if ( function_exists( 'WC' ) && WC()->session ) {
                $session_vat = WC()->session->get( 'wpfh_fc_validated_vat' );
                if ( ! empty( $session_vat ) && $session_vat['vat_id'] === $vat_id ) {
                    if ( ! empty( $session_vat['reverse_charge'] ) ) {
                        $order->update_meta_data( '_vat_reverse_charge', 'yes' );
                    }
                    $order->update_meta_data( '_vat_company_name', $session_vat['company_name'] );
                }
            }
        }
    }

    public function display_vat_in_admin_order( $order ) {
        $vat_id = $order->get_meta( '_billing_vat_id' );
        if ( ! empty( $vat_id ) ) {
            $reverse_charge = $order->get_meta( '_vat_reverse_charge' ) === 'yes';
            $company = $order->get_meta( '_vat_company_name' );
            ?>
            <p>
                <strong><?php esc_html_e( 'EU VAT ID:', 'forgehub-frictionless-checkout' ); ?></strong>
                <code><?php echo esc_html( $vat_id ); ?></code>
                <?php if ( $reverse_charge ) : ?>
                    <span style="background:#065f46; color:#a7f3d0; padding:2px 6px; border-radius:4px; font-size:11px; font-weight:700;">REVERSE CHARGE (0%)</span>
                <?php endif; ?>
                <?php if ( $company ) : ?>
                    <br><small style="color:#64748b;"><?php echo esc_html( $company ); ?></small>
                <?php endif; ?>
            </p>
            <?php
        }
    }
}
