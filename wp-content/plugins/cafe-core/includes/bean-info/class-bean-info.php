<?php
namespace CafeCore\BeanInfo;

defined( 'ABSPATH' ) || exit;

/**
 * Dữ liệu khung "Thông tin hạt". Không gọi WordPress.
 */
final class BeanInfo {
	/** Trường => meta key (gạch dưới đầu để ẩn khỏi hộp Custom Fields). */
	public const FIELDS = array(
		'origin'   => '_cafe_origin',
		'altitude' => '_cafe_altitude',
		'roast'    => '_cafe_roast',
		'flavor'   => '_cafe_flavor',
	);

	public const ROASTS = array( 'light', 'medium', 'dark' );

	/**
	 * @param array<string, string> $values Giá trị theo tên trường.
	 * @return array<string, string> Các dòng có giá trị, theo thứ tự FIELDS.
	 */
	public static function rows( array $values ): array {
		$rows = array();
		foreach ( array_keys( self::FIELDS ) as $field ) {
			$value = trim( (string) ( $values[ $field ] ?? '' ) );
			if ( 'roast' === $field ) {
				$value = self::sanitize_roast( $value );
			}
			if ( '' !== $value ) {
				$rows[ $field ] = $value;
			}
		}
		return $rows;
	}

	public static function sanitize_roast( string $value ): string {
		return in_array( $value, self::ROASTS, true ) ? $value : '';
	}
}
