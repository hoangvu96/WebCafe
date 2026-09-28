<?php
namespace CafeCore\Shipping;

defined( 'ABSPATH' ) || exit;

final class ShippingCalculator {
	/**
	 * @param float $subtotal  Tổng tiền hàng, chưa gồm phí ship.
	 * @param int   $fee       Phí ship cố định.
	 * @param int   $threshold Ngưỡng miễn phí; 0 = tắt miễn phí.
	 */
	public static function cost( float $subtotal, int $fee, int $threshold ): int {
		if ( $threshold > 0 && $subtotal >= $threshold ) {
			return 0;
		}
		return max( 0, $fee );
	}
}
