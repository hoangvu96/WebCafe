<?php
namespace CafeCore;

defined( 'ABSPATH' ) || exit;

/**
 * Cài đặt của cửa hàng, lưu trong một option dạng mảng.
 */
final class Settings {
	public const OPTION = 'cafe_core_settings';

	public const DEFAULTS = array(
		'shipping_fee'   => 30000,
		'free_threshold' => 500000,
		'shop_phone'     => '',
	);

	/**
	 * Ghép giá trị đã lưu với giá trị mặc định, bỏ các khoá lạ.
	 *
	 * @param mixed $stored Giá trị đọc từ get_option().
	 */
	public static function with_defaults( $stored ): array {
		$stored = is_array( $stored ) ? $stored : array();
		return array_merge( self::DEFAULTS, array_intersect_key( $stored, self::DEFAULTS ) );
	}

	/**
	 * Làm sạch dữ liệu gửi từ form cài đặt.
	 *
	 * @param mixed $input Dữ liệu thô.
	 */
	public static function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();
		return array(
			'shipping_fee'   => max( 0, (int) ( $input['shipping_fee'] ?? self::DEFAULTS['shipping_fee'] ) ),
			'free_threshold' => max( 0, (int) ( $input['free_threshold'] ?? self::DEFAULTS['free_threshold'] ) ),
			'shop_phone'     => trim( (string) preg_replace( '/[^0-9 .+]/', '', (string) ( $input['shop_phone'] ?? '' ) ) ),
		);
	}

	public static function all(): array {
		return self::with_defaults( get_option( self::OPTION, array() ) );
	}

	/**
	 * @return mixed
	 */
	public static function get( string $key ) {
		return self::all()[ $key ] ?? null;
	}
}
