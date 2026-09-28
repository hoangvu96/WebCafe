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
