<?php
/**
 * Plugin Name: WC Coupons by Location
 * Plugin URI: https://github.com/shivapoudel/wc-coupon-by-location
 * Description: WooCommerce Coupons by Location restricts coupons by customer’s billing or shipping country.
 * Version: 1.0.0
 * Author: Shiva Poudel
 * Author URI: http://shivapoudel.com
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

			// Action Hooks
			add_action( 'woocommerce_coupon_options_usage_restriction', array( $this, 'coupon_options_data' ) );
			add_action( 'woocommerce_coupon_options_save', array( $this, 'coupon_options_save' ) );
			add_action( 'woocommerce_coupon_loaded', array( $this, 'coupon_loaded' ) );

			// Filter Hooks
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'is_valid_for_country' ), 10, 2 );
			add_filter( 'woocommerce_coupon_error', array( $this, 'get_country_coupon_error' ), 10, 3 );
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
		<p class="form-field"><label for="billing_locations"><?php _e( 'Billing locations', 'wc-coupons-by-location' ); ?></label>
		<select id="billing_locations" name="billing_locations[]" style="width: 50%;" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any billing locations', 'wc-coupons-by-location' ); ?>">
			<?php
				$locations = (array) get_post_meta( $post->ID, 'billing_locations', true );
				$countries = WC()->countries->countries;

				if ( $countries ) foreach ( $countries as $key => $val ) {
					echo '<option value="' . esc_attr( $key ) . '"' . selected( in_array( $key, $locations ), true, false ) . '>' . esc_html( $val ) . '</option>';
				}
			?>
		</select> <?php echo wc_help_tip( __( 'An user must be in this billing locations for the coupon to remain valid or, for "Product Discounts", users in these location will be discounted.', 'wc-coupons-by-location' ) ); ?></p>
		<?php

		echo '</div><div class="options_group">';

		// Shipping Locations
		?>
		<p class="form-field"><label for="shipping_locations"><?php _e( 'Shipping locations', 'wc-coupons-by-location' ); ?></label>
		<select id="shipping_locations" name="shipping_locations[]" style="width: 50%;" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any shipping locations', 'wc-coupons-by-location' ); ?>">
			<?php
				$locations = (array) get_post_meta( $post->ID, 'shipping_locations', true );
				$countries = WC()->countries->countries;

				if ( $countries ) foreach ( $countries as $key => $val ) {
					echo '<option value="' . esc_attr( $key ) . '"' . selected( in_array( $key, $locations ), true, false ) . '>' . esc_html( $val ) . '</option>';
				}
			?>
		</select> <?php echo wc_help_tip( __( 'An user must be in this shipping locations for the coupon to remain valid or, for "Product Discounts", users in these location will be discounted.', 'wc-coupons-by-location' ) ); ?></p>
		<?php

		echo '</div>';
	}

	/**
	 * Save coupons meta box data.
	 */
	public function coupon_options_save( $post_id ) {
		$billing_locations  = isset( $_POST['billing_locations'] ) ? wc_clean( $_POST['billing_locations'] ) : array();
		$shipping_locations = isset( $_POST['shipping_locations'] ) ? wc_clean( $_POST['shipping_locations'] ) : array();

		// Save billing and shipping locations.
		update_post_meta( $post_id, 'billing_locations', $billing_locations );
		update_post_meta( $post_id, 'shipping_locations', $shipping_locations );
	}

	/**
	 * Populates an order from the loaded post data.
	 */
	public function coupon_loaded( $coupon ) {
		$coupon->billing_locations = get_post_meta( $coupon->id, 'billing_locations', true );
		$coupon->shipping_locations = get_post_meta( $coupon->id, 'shipping_locations', true );
	}

	/**
	 * Check if coupon is valid for country.
	 * @return bool
	 */
	public function is_valid_for_country( $valid_for_cart, $coupon ) {
		if ( sizeof( $coupon->billing_locations ) > 0 || sizeof( $coupon->shipping_locations ) > 0 ) {
			$valid_for_cart = false;
			if ( ! WC()->cart->is_empty() ) {
				if ( in_array( WC()->customer->country, $coupon->billing_locations ) || in_array( WC()->customer->shipping_country, $coupon->shipping_locations ) ) {
					$valid_for_cart = true;
				}
			}
			if ( ! $valid_for_cart ) {
				throw new Exception( self::E_WC_COUPON_INVALID_COUNTRY );
			}
		}

		return $valid_for_cart;
	}

	/**
	 * Map one of the WC_Coupon error codes to an error string.
	 * @param  string $err Error message.
	 * @param  int $err_code Error code
	 * @return string| Error string
	 */
	public function get_country_coupon_error( $err, $err_code, $coupon ) {
		if ( self::E_WC_COUPON_INVALID_COUNTRY == $err_code ) {
			$err = sprintf( __( 'Sorry, coupon "%s" is not applicable to your country.', 'wc-coupons-by-location' ), $coupon->code );
		}

		return $err;
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
