<?php
namespace CafeCore\Language;

defined( 'ABSPATH' ) || exit;

/**
 * Ngôn ngữ giao diện khách chọn: mã ngắn (vi, en) ↔ locale WordPress.
 * Chỉ đổi các chuỗi dịch trong code (gettext), không đổi dữ liệu trong database.
 */
final class Language {
	public const DEFAULT   = 'vi';
	public const COOKIE    = 'cafe_lang';
	public const QUERY_VAR = 'lang';

	/**
	 * Các ngôn ngữ hỗ trợ, ngôn ngữ mặc định đứng đầu.
	 * Tên ngôn ngữ viết bằng chính ngôn ngữ đó nên không bọc hàm dịch.
	 *
	 * @return array<string, array{locale: string, label: string}>
	 */
	public static function languages(): array {
		return array(
			'vi' => array( 'locale' => 'vi', 'label' => 'Tiếng Việt' ),
			'en' => array( 'locale' => 'en_US', 'label' => 'English' ),
		);
	}

	public static function is_supported( ?string $code ): bool {
		return null !== $code && isset( self::languages()[ $code ] );
	}

	/** Ưu tiên ngôn ngữ vừa chọn trên URL, rồi đến cookie, cuối cùng là mặc định. */
	public static function resolve( ?string $requested, ?string $cookie ): string {
		foreach ( array( $requested, $cookie ) as $code ) {
			if ( self::is_supported( $code ) ) {
				return $code;
			}
		}
		return self::DEFAULT;
	}

	public static function locale( string $code ): string {
		$languages = self::languages();
		return $languages[ self::is_supported( $code ) ? $code : self::DEFAULT ]['locale'];
	}
}
