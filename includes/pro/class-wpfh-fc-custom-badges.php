<?php
/**
 * WPFH Frictionless Checkout — Pro Custom Trust Badge Designer
 *
 * Allows merchants to inject customizable SVG security badges,
 * money-back guarantees, and custom buyer assurances into the checkout flow.
 *
 * @package WPForgeHub\FrictionlessCheckout\Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPFH_FC_Custom_Badges {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'woocommerce_review_order_after_submit', array( $this, 'render_custom_trust_badges' ), 10 );
    }

    public function get_badges() {
        $default_badges = array(
            array(
                'icon'  => '🔒',
                'title' => __( '256-Bit SSL Encryption', 'forgehub-frictionless-checkout' ),
                'desc'  => __( 'End-to-end encrypted checkout powered by Stripe.', 'forgehub-frictionless-checkout' ),
            ),
            array(
                'icon'  => '⚡',
                'title' => __( 'Instant Digital Delivery', 'forgehub-frictionless-checkout' ),
                'desc'  => __( 'Access your plugin license & zip download in seconds.', 'forgehub-frictionless-checkout' ),
            ),
            array(
                'icon'  => '🛡️',
                'title' => __( '14-Day Money Back Guarantee', 'forgehub-frictionless-checkout' ),
                'desc'  => __( '100% risk-free. No questions asked refund policy.', 'forgehub-frictionless-checkout' ),
            ),
        );

        $saved = get_option( 'wpfh_fc_custom_badges', array() );
        return ! empty( $saved ) && is_array( $saved ) ? $saved : $default_badges;
    }

    public function render_custom_trust_badges() {
        $badges = $this->get_badges();
        if ( empty( $badges ) ) {
            return;
        }
        ?>
        <div class="wpfh-fc-pro-badges-container" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-top:20px; padding:16px; background:#0f172a; border:1px solid #1e293b; border-radius:10px;">
            <?php foreach ( $badges as $badge ) : ?>
                <div class="wpfh-fc-pro-badge-item" style="display:flex; align-items:flex-start; gap:10px;">
                    <div style="font-size:22px; line-height:1; flex-shrink:0;"><?php echo esc_html( $badge['icon'] ?? '🔒' ); ?></div>
                    <div>
                        <strong style="display:block; color:#f8fafc; font-size:13px; font-weight:700;"><?php echo esc_html( $badge['title'] ?? '' ); ?></strong>
                        <span style="display:block; color:#94a3b8; font-size:11px; line-height:1.4; margin-top:2px;"><?php echo esc_html( $badge['desc'] ?? '' ); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
