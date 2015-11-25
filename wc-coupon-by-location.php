<?php
/**
 * Plugin Name: WC Coupons by Location
 * Plugin URI: https://github.com/axisthemes/wc-coupon-by-location
 * Description: WooCommerce Coupons by Location restricts coupons by customer’s billing or shipping country.
 * Version: 1.0.0
 * Author: AxisThemes
 * Author URI: http://axisthemes.com
 * License: GPLv3 or later
 * Text Domain: wc-coupons-by-location
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Coupons_Location' ) ) :

/**
 * Main WC_Coupons_Location Class.
 */
class WC_Coupons_Location {

	/**
	 * Plugin version.
	 * @var string
	 */
	const VERSION = '1.0.0';

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

			// Hooks
			add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'coupon_options_data' ) );
			add_action( 'woocommerce_coupon_options_save', array( $this, 'coupon_options_save' ) );
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
	 *      - WP_LANG_DIR/wc-coupons-by-location/wc-coupons-by-location-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/wc-coupons-by-location-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wc-coupons-by-location' );

		load_textdomain( 'wc-coupons-by-location', WP_LANG_DIR . '/wc-coupons-by-location/wc-coupons-by-location-' . $locale . '.mo' );
		load_plugin_textdomain( 'wc-coupons-by-location', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Output coupons meta box data.
	 */
	public function coupon_options_data() {
		global $post;

		echo '<div class="options_group">';

		// Billing Locations
		?>
		<p class="form-field"><label for="billing_locations"><?php _e( 'Billing Locations', 'wc-coupons-by-location' ); ?></label>
		<select id="billing_locations" name="billing_locations[]" style="width: 50%;" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any location', 'wc-coupons-by-location' ); ?>">
			<?php
				$billing_locations = (array) get_post_meta( $post->ID, 'billing_locations', true );
				$billing_countries = WC()->countries->countries;

				if ( $billing_countries ) foreach ( $billing_countries as $key => $val ) {
					echo '<option value="' . esc_attr( $key ) . '"' . selected( in_array( $key, $billing_locations ), true, false ) . '>' . esc_html( $val ) . '</option>';
				}
			?>
		</select> <?php echo wc_help_tip( __( 'An user must be in this location for the coupon to remain valid or, for "Product Discounts", users in these location will be discounted.', 'wc-coupons-by-location' ) ); ?></p>
		<?php

		echo '</div>';
	}

	/**
	 * Save coupons meta box data.
	 */
	public function coupon_options_save( $post_id ) {
		$billing_locations = isset( $_POST['billing_locations'] ) ? wc_clean( $_POST['billing_locations'] ) : array();

		// Save
		update_post_meta( $post_id, 'billing_locations', $billing_locations );
	}

	/**
	 * WooCommerce fallback notice.
	 * @return string
	 */
	public function woocommerce_missing_notice() {
		echo '<div class="error notice is-dismissible"><p>' . sprintf( __( 'WooCommerce Coupons by Location depends on the last version of %s or later to work!', 'wc-coupons-by-location' ), '<a href="http://www.woothemes.com/woocommerce/" target="_blank">' . __( 'WooCommerce 2.3', 'wc-coupons-by-location' ) . '</a>' ) . '</p></div>';
	}
}

add_action( 'plugins_loaded', array( 'WC_Coupons_Location', 'get_instance' ), 0 );

endif;