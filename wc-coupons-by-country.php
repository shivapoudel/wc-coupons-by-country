<?php
/**
 * Plugin Name: WC Coupons by Country
 * Plugin URI: https://github.com/axisthemes/wc-coupons-by-country
 * Description: WooCommerce Coupons by Country restricts coupons usage to specific countries.
 * Version: 1.0.0
 * Author: AxisThemes
 * Author URI: http://axisthemes.com
 * License: GPLv3 or later
 * Text Domain: wc-coupons-by-country
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Coupons_Country' ) ) :

/**
 * Main WC_Coupons_Country Class.
 */
class WC_Coupons_Country {

	/**
	 * Plugin version.
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Coupon message code.
	 * @var integer
	 */
	const E_WC_COUPON_INVALID_COUNTRY = 99;

	/**
	 * Instance of this class.
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin.
	 */
	private function __construct() {
		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Checks with WooCommerce is installed.
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.3', '>=' ) ) {
			$this->includes();
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/wc-coupons-by-country/wc-coupons-by-country-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/wc-coupons-by-country-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wc-coupons-by-country' );

		load_textdomain( 'wc-coupons-by-country', WP_LANG_DIR . '/wc-coupons-by-country/wc-coupons-by-country-' . $locale . '.mo' );
		load_plugin_textdomain( 'wc-coupons-by-country', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Includes.
	 */
	private function includes() {
		include_once( 'includes/class-wc-meta-box-coupon-usage-data.php' );
		include_once( 'includes/class-wc-api-coupon-usage-restrictions.php' );
	}

	/**
	 * WooCommerce fallback notice.
	 * @return string
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error notice is-dismissible"><p>' . sprintf( __( 'WooCommerce Coupons by Location depends on the last version of %s or later to work!', 'wc-coupons-by-country' ), '<a href="http://www.woothemes.com/woocommerce/" target="_blank">' . __( 'WooCommerce 2.3', 'wc-coupons-by-country' ) . '</a>' ) . '</p></div>';
	}
}

add_action( 'plugins_loaded', array( 'WC_Coupons_Country', 'get_instance' ), 0 );

endif;
