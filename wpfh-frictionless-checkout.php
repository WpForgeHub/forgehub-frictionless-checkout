<?php
/**
 * Plugin Name:       WPFH Frictionless Checkout for WooCommerce
 * Plugin URI:        https://wpforgehub.com/plugins/wpfh-frictionless-checkout/
 * Description:       Ultra-fast, 4-field checkout for digital products and SaaS. Removes address friction, auto-detects country via GeoIP/Cloudflare, and enables 1-click $0 lead capture.
 * Version:           1.0.0
 * Author:            WPForgeHub
 * Author URI:        https://wpforgehub.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpfh-frictionless-checkout
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   9.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPFH_FC_VERSION', '1.0.0' );
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
					'id'             => '17961',
					'slug'           => 'wpfh-frictionless-checkout',
					'type'           => 'plugin',
					'public_key'     => 'pk_dummy_fc_placeholder_key',
					'is_premium'     => false,
					'has_addons'     => false,
					'has_paid_plans' => true,
					'menu'           => array(
						'slug'       => 'wpfh-frictionless-checkout',
						'first-path' => 'admin.php?page=wc-settings&tab=wpfh_fc',
						'parent'     => array(
							'slug' => 'woocommerce',
						),
					),
				) );
			}
		}

		return $wpfh_fc_fs;
	}

	// Initialize Freemius if present
	if ( file_exists( WPFH_FC_PATH . 'freemius/start.php' ) ) {
		wpfh_fc_fs();
	}
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
		load_plugin_textdomain( 'wpfh-frictionless-checkout', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

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

		// Instantiate modules
		WPFH_FC_GeoIP::get_instance();
		WPFH_FC_Checkout::get_instance();
		WPFH_FC_Free_Capture::get_instance();
		WPFH_FC_Admin::get_instance();
	}

	public function woocommerce_missing_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'WPFH Frictionless Checkout requires WooCommerce to be installed and active.', 'wpfh-frictionless-checkout' ); ?></p>
		</div>
		<?php
	}
}

WPFH_Frictionless_Checkout::get_instance();
