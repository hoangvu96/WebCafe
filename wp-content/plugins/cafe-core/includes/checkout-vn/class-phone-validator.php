<?php
namespace CafeCore\CheckoutVn;

defined( 'ABSPATH' ) || exit;

/**
 * Kiểm tra số điện thoại Việt Nam: 10 số, bắt đầu bằng 0.
 */
final class PhoneValidator {
	public static function normalize( string $phone ): string {
		return str_replace( array( ' ', '.' ), '', trim( $phone ) );
	}

	public static function is_valid( string $phone ): bool {
		return 1 === preg_match( '/^0\d{9}$/', self::normalize( $phone ) );
	}
}
