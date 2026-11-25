<?php
/**
 * Plugin Name:       ForgeHub Frictionless Checkout
 * Plugin URI:        https://wpforgehub.com/plugins/frictionless-checkout/
 * Description:       Ultra-fast, 4-field checkout for digital products and SaaS. Removes address friction, auto-detects country via GeoIP/Cloudflare, and enables 1-click $0 lead capture.
 * Version:           1.1.5
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   9.4
 * Author:            WPForgeHub
 * Author URI:        https://wpforgehub.com
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       forgehub-frictionless-checkout
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPFH_FC_VERSION', '1.1.5' );
define( 'WPFH_FC_FILE', __FILE__ );
define( 'WPFH_FC_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPFH_FC_URL', plugin_dir_url( __FILE__ ) );

/**
 * Freemius SDK integration wrapper
 */
if ( ! function_exists( 'wpfh_fc_fs' ) ) {
	function wpfh_fc_fs() {
		global $wpfh_fc_fs;

		if ( ! isset( $wpfh_fc_fs ) ) {
			if ( file_exists( WPFH_FC_PATH . 'freemius/start.php' ) ) {
				require_once WPFH_FC_PATH . 'freemius/start.php';
			}

			if ( function_exists( 'fs_dynamic_init' ) ) {
				$wpfh_fc_fs = fs_dynamic_init( array(
					'id'             => '45082',
					'slug'           => 'wpfh-frictionless-checkout',
					'type'           => 'plugin',
					'public_key'     => 'pk_56291fa562e162ad646487ee9c46f',
					'is_premium'     => false,
					'has_addons'     => false,
					'has_paid_plans' => true,
					'menu'           => array(
						'slug'       => 'forgehub-frictionless-checkout',
						'first-path' => 'admin.php?page=wc-settings&tab=wpfh_fc',
						'parent'     => array(
							'slug' => 'woocommerce',
						),
					),
				) );
			} else {
				// Fallback object for Free / Filter-based Pro distribution
				$wpfh_fc_fs = new class {
					public function can_use_premium_code() {
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
						return (bool) apply_filters( 'fs_can_use_premium_code__wpfh-frictionless-checkout', false );
					}
					public function is__premium_only() { return false; }
					public function is_plan( $plan ) {
						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
						return (bool) apply_filters( 'fs_is_plan__wpfh-frictionless-checkout', false, $plan );
					}
					public function get_upgrade_url() {
						return 'https://wpforgehub.com/product/frictionless-checkout/#pricing';
					}
				};
			}
		}

		return $wpfh_fc_fs;
	}

	wpfh_fc_fs();
}

/**
 * Main Plugin Initialization Class
 */
final class WPFH_Frictionless_Checkout {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 20 );
		add_action( 'before_woocommerce_init', array( $this, 'declare_hpos_compatibility' ) );
	}

	public function declare_hpos_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);
		}
	}

	public function init() {
		// Note: load_plugin_textdomain is intentionally omitted as WordPress.org automatically loads translations since WP 4.6.

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		$this->includes();
	}

	private function includes() {
		require_once WPFH_FC_PATH . 'includes/class-wpfh-fc-geoip.php';
		require_once WPFH_FC_PATH . 'includes/class-wpfh-fc-checkout.php';
		require_once WPFH_FC_PATH . 'includes/class-wpfh-fc-free-capture.php';
		require_once WPFH_FC_PATH . 'includes/class-wpfh-fc-admin.php';

		// Instantiate Free modules
		WPFH_FC_GeoIP::get_instance();
		WPFH_FC_Checkout::get_instance();
		WPFH_FC_Free_Capture::get_instance();
		WPFH_FC_Admin::get_instance();

		// Instantiate Pro modules when licensed
		if ( function_exists( 'wpfh_fc_fs' ) && wpfh_fc_fs()->can_use_premium_code() ) {
			$pro_express = WPFH_FC_PATH . 'includes/pro/class-wpfh-fc-express-buy.php';
			$pro_vies    = WPFH_FC_PATH . 'includes/pro/class-wpfh-fc-eu-vat.php';
			$pro_abandon = WPFH_FC_PATH . 'includes/pro/class-wpfh-fc-abandonment.php';
			$pro_badges  = WPFH_FC_PATH . 'includes/pro/class-wpfh-fc-custom-badges.php';

			if ( file_exists( $pro_express ) ) {
				require_once $pro_express;
				WPFH_FC_Express_Buy::get_instance();
			}
			if ( file_exists( $pro_vies ) ) {
				require_once $pro_vies;
				WPFH_FC_EU_VAT::get_instance();
			}
			if ( file_exists( $pro_abandon ) ) {
				require_once $pro_abandon;
				WPFH_FC_Abandonment::get_instance();
			}
			if ( file_exists( $pro_badges ) ) {
				require_once $pro_badges;
				WPFH_FC_Custom_Badges::get_instance();
			}
		}
	}

	public function woocommerce_missing_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'Frictionless Checkout requires WooCommerce to be installed and active.', 'forgehub-frictionless-checkout' ); ?></p>
		</div>
		<?php
	}
}

WPFH_Frictionless_Checkout::get_instance();
