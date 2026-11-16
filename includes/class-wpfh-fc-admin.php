<?php
/**
 * WPFH Frictionless Checkout Admin Settings
 *
 * @package WPFH_Frictionless_Checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPFH_FC_Admin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 60 );
		add_action( 'woocommerce_settings_tabs_wpfh_fc', array( $this, 'render_settings' ) );
		add_action( 'woocommerce_update_options_wpfh_fc', array( $this, 'save_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WPFH_FC_FILE ), array( $this, 'add_action_links' ) );
	}

	public function add_settings_tab( $tabs ) {
		$tabs['wpfh_fc'] = __( 'Frictionless Checkout', 'forgehub-frictionless-checkout' );
		return $tabs;
	}

	public function add_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=wpfh_fc' ) ) . '">' . __( 'Settings', 'forgehub-frictionless-checkout' ) . '</a>';
		$pro_link      = '<a href="https://wpforgehub.com/plugins/wpfh-frictionless-checkout/" style="color:#10b981;font-weight:700;" target="_blank">' . __( 'Upgrade to Pro', 'forgehub-frictionless-checkout' ) . '</a>';
		array_unshift( $links, $settings_link, $pro_link );
		return $links;
	}

	private function get_settings() {
		return array(
			array(
				'title' => __( 'Frictionless Checkout Settings', 'forgehub-frictionless-checkout' ),
				'type'  => 'title',
				'desc'  => __( 'Optimize WooCommerce checkout conversion rates by stripping address friction and auto-detecting location.', 'forgehub-frictionless-checkout' ),
				'id'    => 'wpfh_fc_general_options',
			),
			array(
				'title'   => __( 'Enable 4-Field Checkout', 'forgehub-frictionless-checkout' ),
				'desc'    => __( 'Strip street, city, state, postal code, phone, company, and order notes from checkout.', 'forgehub-frictionless-checkout' ),
				'id'      => 'wpfh_fc_enable_frictionless',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Auto-Detect Country', 'forgehub-frictionless-checkout' ),
				'desc'    => __( 'Detect country automatically from Cloudflare / GeoIP headers while keeping country selector visible.', 'forgehub-frictionless-checkout' ),
				'id'      => 'wpfh_fc_enable_geoip',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( '1-Click Free Lead Capture', 'forgehub-frictionless-checkout' ),
				'desc'    => __( 'Automatically complete $0 orders instantly and skip payment gateway redirection.', 'forgehub-frictionless-checkout' ),
				'id'      => 'wpfh_fc_enable_free_capture',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'   => __( 'Show Trust Badges', 'forgehub-frictionless-checkout' ),
				'desc'    => __( 'Display security and instant delivery badges below the place order button.', 'forgehub-frictionless-checkout' ),
				'id'      => 'wpfh_fc_show_trust_badges',
				'default' => 'yes',
				'type'    => 'checkbox',
			),
			array(
				'title'    => __( 'Free Order Destination', 'forgehub-frictionless-checkout' ),
				'desc'     => __( 'Where to redirect customers after completing a free order.', 'forgehub-frictionless-checkout' ),
				'id'       => 'wpfh_fc_free_redirect',
				'default'  => 'my_account_downloads',
				'type'     => 'select',
				'options'  => array(
					'my_account_downloads' => __( 'Customer Portal - Downloads Tab', 'forgehub-frictionless-checkout' ),
					'thank_you'            => __( 'Standard WooCommerce Thank You Page', 'forgehub-frictionless-checkout' ),
				),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wpfh_fc_general_options',
			),
		);
	}

	public function render_settings() {
		woocommerce_admin_fields( $this->get_settings() );
		$this->render_pro_upsell();
	}

	public function save_settings() {
		woocommerce_update_options( $this->get_settings() );
	}

	private function render_pro_upsell() {
		?>
		<div class="wpfh-pro-card" style="background:#0f172a;border:1px solid #1e293b;border-radius:12px;padding:24px;margin-top:24px;color:#e2e8f0;max-width:800px;">
			<div style="display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #334155;padding-bottom:16px;margin-bottom:16px;">
				<div>
					<h3 style="margin:0;font-size:18px;color:#f8fafc;">🚀 WPFH Frictionless Checkout Pro</h3>
					<p style="margin:4px 0 0 0;color:#94a3b8;font-size:13px;">Maximize your revenue with enterprise SaaS checkout enhancements.</p>
				</div>
				<a href="https://wpforgehub.com/plugins/wpfh-frictionless-checkout/" target="_blank" style="background:#10b981;color:#ffffff;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:700;font-size:13px;display:inline-block;">Upgrade to Pro &rarr;</a>
			</div>
			<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;font-size:13px;">
				<div style="background:#1e293b;padding:12px 16px;border-radius:8px;">
					<strong style="color:#38bdf8;">⚡ 1-Click Buy Links</strong>
					<p style="margin:4px 0 0 0;color:#cbd5e1;">Generate direct buy links that auto-add products to cart and open checkout instantly.</p>
				</div>
				<div style="background:#1e293b;padding:12px 16px;border-radius:8px;">
					<strong style="color:#38bdf8;">🇪🇺 EU B2B VAT Reverse Charge</strong>
					<p style="margin:4px 0 0 0;color:#cbd5e1;">Live VIES VAT ID validation with automatic zero-rating for EU B2B customers.</p>
				</div>
				<div style="background:#1e293b;padding:12px 16px;border-radius:8px;">
					<strong style="color:#38bdf8;">🛒 Cart Abandonment Webhooks</strong>
					<p style="margin:4px 0 0 0;color:#cbd5e1;">Capture customer email as typed (on blur) and trigger webhook recovery automations.</p>
				</div>
				<div style="background:#1e293b;padding:12px 16px;border-radius:8px;">
					<strong style="color:#38bdf8;">🎨 Custom Trust Badge Designer</strong>
					<p style="margin:4px 0 0 0;color:#cbd5e1;">Add your own SVG badges, money-back guarantees, and custom styling overrides.</p>
				</div>
			</div>
		</div>
		<?php
	}
}
