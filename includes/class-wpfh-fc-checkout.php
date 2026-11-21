<?php
/**
 * WPFH Frictionless Checkout Field & Layout Optimizer
 *
 * @package WPFH_Frictionless_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPFH_FC_Checkout {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'woocommerce_checkout_fields', array( $this, 'filter_checkout_fields' ), 999 );
		add_filter( 'woocommerce_enable_order_notes_field', array( $this, 'disable_order_notes' ), 999 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'woocommerce_review_order_after_submit', array( $this, 'render_trust_badges' ), 10 );
		add_filter( 'woocommerce_order_button_text', array( $this, 'custom_order_button_text' ) );
	}

	/**
	 * Remove physical friction fields for digital goods
	 */
	public function filter_checkout_fields( $fields ) {
		$enabled = get_option( 'wpfh_fc_enable_frictionless', 'yes' );
		if ( 'yes' !== $enabled ) {
			return $fields;
		}

		// Never strip address or shipping fields when the cart requires physical shipping.
		if ( function_exists( 'WC' ) && WC()->cart && WC()->cart->needs_shipping() ) {
			return $fields;
		}

		// Keep only First Name, Last Name, Country, Email (plus any explicitly allowed Pro fields).
		$allowed_billing = apply_filters(
			'wpfh_fc_allowed_billing_fields',
			array(
				'billing_first_name',
				'billing_last_name',
				'billing_country',
				'billing_email',
				'billing_vat_id',
			)
		);

		if ( isset( $fields['billing'] ) && is_array( $fields['billing'] ) ) {
			foreach ( array_keys( $fields['billing'] ) as $key ) {
				if ( ! in_array( $key, $allowed_billing, true ) ) {
					unset( $fields['billing'][ $key ] );
				}
			}
		}

		// Remove shipping fields completely
		if ( isset( $fields['shipping'] ) ) {
			unset( $fields['shipping'] );
		}

		// Remove account password fields if guest checkout or auto-account creation
		if ( isset( $fields['account'] ) && 'yes' === get_option( 'wpfh_fc_auto_account', 'yes' ) ) {
			// Auto password will be generated cleanly by WooCommerce
		}

		// Adjust field priorities for clean 2x2 grid
		if ( isset( $fields['billing']['billing_first_name'] ) ) {
			$fields['billing']['billing_first_name']['priority'] = 10;
			$fields['billing']['billing_first_name']['class']    = array( 'form-row-first' );
		}
		if ( isset( $fields['billing']['billing_last_name'] ) ) {
			$fields['billing']['billing_last_name']['priority'] = 20;
			$fields['billing']['billing_last_name']['class']    = array( 'form-row-last' );
		}
		if ( isset( $fields['billing']['billing_country'] ) ) {
			$fields['billing']['billing_country']['priority'] = 30;
			$fields['billing']['billing_country']['class']    = array( 'form-row-first' );
		}
		if ( isset( $fields['billing']['billing_email'] ) ) {
			$fields['billing']['billing_email']['priority'] = 40;
			$fields['billing']['billing_email']['class']    = array( 'form-row-last' );
		}

		return $fields;
	}

	/**
	 * Disable order comments textarea
	 */
	public function disable_order_notes() {
		return 'yes' !== get_option( 'wpfh_fc_enable_frictionless', 'yes' );
	}

	/**
	 * Dynamic Order Button CTA
	 */
	public function custom_order_button_text( $text ) {
		if ( WC()->cart && 0.0 == WC()->cart->total ) {
			return __( 'Complete Free Download', 'forgehub-frictionless-checkout' );
		}
		return __( 'Complete Order & Get Access', 'forgehub-frictionless-checkout' );
	}

	/**
	 * Enqueue styles and scripts
	 */
	public function enqueue_assets() {
		if ( is_checkout() ) {
			$css_path = WPFH_FC_PATH . 'assets/css/checkout.css';
			$js_path  = WPFH_FC_PATH . 'assets/js/checkout.js';
			$css_ver  = file_exists( $css_path ) ? filemtime( $css_path ) : WPFH_FC_VERSION;
			$js_ver   = file_exists( $js_path ) ? filemtime( $js_path ) : WPFH_FC_VERSION;

			wp_enqueue_style(
				'wpfh-fc-checkout',
				WPFH_FC_URL . 'assets/css/checkout.css',
				array(),
				$css_ver
			);

			wp_enqueue_script(
				'wpfh-fc-checkout',
				WPFH_FC_URL . 'assets/js/checkout.js',
				array( 'jquery' ),
				$js_ver,
				true
			);

			wp_localize_script( 'wpfh-fc-checkout', 'wpfh_fc_vars', array(
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'is_zero'          => ( WC()->cart && 0.0 === (float) WC()->cart->total ) ? 1 : 0,
				'free_button_text' => __( 'Complete Free Download', 'forgehub-frictionless-checkout' ),
				'paid_button_text' => __( 'Complete Order & Get Access', 'forgehub-frictionless-checkout' ),
			) );
		}
	}

	/**
	 * Render conversion trust badges
	 */
	public function render_trust_badges() {
		if ( 'yes' !== get_option( 'wpfh_fc_show_trust_badges', 'yes' ) ) {
			return;
		}
		?>
		<div class="wpfh-fc-trust-badges">
			<div class="wpfh-fc-badge">
				<span class="wpfh-fc-badge-icon">⚡</span>
				<span class="wpfh-fc-badge-text"><?php esc_html_e( 'Instant Digital Access', 'forgehub-frictionless-checkout' ); ?></span>
			</div>
			<div class="wpfh-fc-badge">
				<span class="wpfh-fc-badge-icon">🔒</span>
				<span class="wpfh-fc-badge-text"><?php esc_html_e( '256-Bit SSL Encrypted', 'forgehub-frictionless-checkout' ); ?></span>
			</div>
			<div class="wpfh-fc-badge">
				<span class="wpfh-fc-badge-icon">🛡️</span>
				<span class="wpfh-fc-badge-text"><?php esc_html_e( '14-Day Money-Back Guarantee', 'forgehub-frictionless-checkout' ); ?></span>
			</div>
		</div>
		<?php
	}
}
