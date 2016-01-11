<?php
/**
 * WooCommerce API Coupons Class
 *
 * Handles requests to the /coupons endpoint with usage restriction.
 *
 * @author   AxisThemes
 * @category API
 * @package  WooCommerce/API
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_API_Coupons_Usage_Country Class.
 */
class WC_API_Coupons_Usage_Country {

	/**
	 * Hooks in methods.
	 */
	public static function init() {
		add_filter( 'woocommerce_api_coupon_response', array( __CLASS__, 'coupon_response' ), 10, 2 );
		add_action( 'woocommerce_api_create_coupon', array( __CLASS__, 'create_coupon' ), 10, 2 );
		add_action( 'woocommerce_api_edit_coupon', array( __CLASS__, 'edit_coupon' ), 10, 2 );
	}

	/**
	 * Get the coupon response.
	 * @param  array  $coupon_data
	 * @param  object $coupon
	 * @return array
	 */
	public static function coupon_response( $coupon_data, $coupon ) {
		$coupon_data['billing_countries']  = $coupon->billing_countries;
		$coupon_data['shipping_countries'] = $coupon->shipping_countries;
		return $coupon_data;
	}

	/**
	 * Create a coupon.
	 * @param int   $id
	 * @param array $data
	 */
	public static function create_coupon( $id, $data ) {
		$billing_countries  = isset( $data['billing_countries'] ) ? wc_clean( $data['billing_countries'] ) : array();
		$shipping_countries = isset( $data['shipping_countries'] ) ? wc_clean( $data['shipping_countries'] ) : array();

		// Save billing and shipping countries.
		update_post_meta( $id, 'billing_countries', $billing_countries );
		update_post_meta( $id, 'shipping_countries', $shipping_countries );
	}

	/**
	 * Edit a coupon.
	 * @param int   $id
	 * @param array $data
	 */
	public static function edit_coupon( $id, $data ) {
		if ( isset( $data['billing_countries'] ) ) {
			update_post_meta( $id, 'billing_countries', wc_clean( $data['billing_countries'] ) );
		}

		if ( isset( $data['shipping_countries'] ) ) {
			update_post_meta( $id, 'shipping_countries', wc_clean( $data['shipping_countries'] ) );
		}
	}
}

WC_API_Coupons_Usage_Country::init();
