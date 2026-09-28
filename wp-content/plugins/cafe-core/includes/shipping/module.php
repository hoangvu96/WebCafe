<?php
namespace CafeCore\Shipping;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/class-shipping-calculator.php';

add_filter(
	'woocommerce_shipping_methods',
	static function ( array $methods ): array {
		require_once __DIR__ . '/class-shipping-method.php';
		$methods[ ShippingMethod::ID ] = ShippingMethod::class;
		return $methods;
	}
);

/**
 * WooCommerce cache mức phí ship theo hash của gói hàng, trong đó có
 * WC_Cache_Helper::get_transient_version('shipping'). Khi đổi cài đặt phí ship/ngưỡng
 * miễn phí ship của cửa hàng thì phải tăng version này để xoá cache cũ.
 */
add_action( 'update_option_' . \CafeCore\Settings::OPTION, static function (): void {
	\WC_Cache_Helper::get_transient_version( 'shipping', true );
} );
add_action( 'add_option_' . \CafeCore\Settings::OPTION, static function (): void {
	\WC_Cache_Helper::get_transient_version( 'shipping', true );
} );
