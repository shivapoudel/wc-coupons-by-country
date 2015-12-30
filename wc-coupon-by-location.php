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
			add_filter( 'woocommerce_coupon_is_valid', array( $this, 'coupon_is_valid' ), 10, 2 );
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
		<p class="form-field"><label for="billing_locations"><?php _e( 'Billing Locations', 'wc-coupons-by-location' ); ?></label>
		<select id="billing_locations" name="billing_locations[]" style="width: 50%;" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Any location', 'wc-coupons-by-location' ); ?>">
			<?php
				$locations = (array) get_post_meta( $post->ID, 'billing_locations', true );
				$countries = WC()->countries->countries;

				if ( $countries ) foreach ( $countries as $key => $val ) {
					echo '<option value="' . esc_attr( $key ) . '"' . selected( in_array( $key, $locations ), true, false ) . '>' . esc_html( $val ) . '</option>';
				}
			?>
		</select> <?php echo wc_help_tip( __( 'An user must be in this location for the coupon to remain valid or, for "Product Discounts", users in these location will be discounted.', 'wc-coupons-by-location' ) ); ?></p>
		<?php

		// Exclude Locations
		?>
		<p class="form-field"><label for="exclude_billing_locations"><?php _e( 'Exclude Locations', 'wc-coupons-by-location' ); ?></label>
		<select id="exclude_billing_locations" name="exclude_billing_locations[]" style="width: 50%;" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php esc_attr_e( 'No location', 'wc-coupons-by-location' ); ?>">
			<?php
				$locations = (array) get_post_meta( $post->ID, 'exclude_billing_locations', true );
				$countries = WC()->countries->countries;

				if ( $countries ) foreach ( $countries as $key => $val ) {
					echo '<option value="' . esc_attr( $key ) . '"' . selected( in_array( $key, $locations ), true, false ) . '>' . esc_html( $val ) . '</option>';
				}
			?>
		</select> <?php echo wc_help_tip( __( 'An user must not be in this location for the coupon to remain valid or, for "Product Discounts", users in these location will be discounted.', 'wc-coupons-by-location' ) ); ?></p>
		<?php

		echo '</div>';
	}

	/**
	 * Save coupons meta box data.
	 */
	public function coupon_options_save( $post_id ) {
		$billing_locations = isset( $_POST['billing_locations'] ) ? wc_clean( $_POST['billing_locations'] ) : array();
		$exclude_billing_locations = isset( $_POST['exclude_billing_locations'] ) ? wc_clean( $_POST['exclude_billing_locations'] ) : array();

		// Save
		update_post_meta( $post_id, 'billing_locations', $billing_locations );
		update_post_meta( $post_id, 'exclude_billing_locations', $exclude_billing_locations );
	}

	/**
	 * Populates an order from the loaded post data.
	 */
	public function coupon_loaded( $coupon ) {
		$coupon->billing_locations = get_post_meta( $coupon->id, 'billing_locations', true );
	}

	/**
	 * Check if coupon is valid.
	 * @return bool
	 */
	public function coupon_is_valid( $valid_for_cart, $coupon ) {
		if ( sizeof( $coupon->billing_locations ) > 0 ) {
			$valid_for_cart = false;
			if ( ! WC()->cart->is_empty() ) {
				$location = WC_Geolocation::geolocate_ip();
				$country  = ! empty( $location['country'] ) ? $location['country'] : 'US';
				if ( in_array( $country, $coupon->billing_locations ) ) {
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
	 * @param  int $err_code Error code
	 * @return string| Error string
	 */
	public function get_country_coupon_error( $err, $err_code, $coupon ) {
		switch ( $err_code ) {
			case self::E_WC_COUPON_INVALID_COUNTRY:
				$err = sprintf( __( 'Sorry, coupon "%s" is not applicable to your country.', 'wc-coupons-by-location' ), $coupon->code );
			break;
			default:
				$err = '';
			break;
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
