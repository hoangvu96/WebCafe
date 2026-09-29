<?php
namespace CafeCore\Language;

defined( 'ABSPATH' ) || exit;

/**
 * Bảng cụm từ "Tiếng Việt = English" cho các chuỗi lẻ lưu trong DB (khẩu hiệu, footer,
 * widget, phương thức thanh toán...). Mỗi dòng một cặp; dòng bắt đầu bằng # là ghi chú.
 */
final class Phrases {
	public const OPTION    = 'cafe_en_phrases';
	public const SEPARATOR = ' = ';

	/** @return array<string, string> */
	public static function parse( string $text ): array {
		$map = array();
		foreach ( preg_split( '/\R/', $text ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || str_starts_with( $line, '#' ) ) {
				continue;
			}
			$parts = explode( trim( self::SEPARATOR ), $line, 2 );
			if ( 2 !== count( $parts ) ) {
				continue;
			}
			list( $vi, $en ) = array_map( 'trim', $parts );
			if ( '' !== $vi && '' !== $en ) {
				$map[ $vi ] = $en;
			}
		}
		return $map;
	}

	/** @param array<string, string> $map */
	public static function format( array $map ): string {
		$lines = array();
		foreach ( $map as $vi => $en ) {
			$lines[] = $vi . self::SEPARATOR . $en;
		}
		return implode( "\n", $lines );
	}

	/**
	 * strtr ưu tiên cụm dài nhất nên "Cà phê rang xay" được thay trước "Cà phê".
	 *
	 * @param array<string, string> $map
	 */
	public static function translate( string $text, array $map ): string {
		return $map ? strtr( $text, $map ) : $text;
	}
}
