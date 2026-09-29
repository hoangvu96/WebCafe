<?php
namespace CafeCore\Language;

defined( 'ABSPATH' ) || exit;

/**
 * Nút chọn ngôn ngữ trên header: lá cờ ngôn ngữ hiện tại + menu thả xuống.
 * Mỗi lựa chọn là link ?lang=<mã> về trang đang xem.
 */
final class Switcher {
	public const SCRIPT = 'cafe-language-switcher';

	public static function render( string $current ): string {
		static $instance = 0;
		++$instance;
		$menu_id   = 'cafe-lang-menu-' . $instance;
		$languages = Language::languages();

		$items = '';
		foreach ( $languages as $code => $language ) {
			$items .= sprintf(
				'<li><a class="cafe-lang__option" href="%1$s" hreflang="%2$s" lang="%2$s"%3$s>%4$s<span>%5$s</span></a></li>',
				esc_url( add_query_arg( Language::QUERY_VAR, $code ) ),
				esc_attr( $code ),
				$code === $current ? ' aria-current="true"' : '',
				self::flag( $code ),
				esc_html( $language['label'] )
			);
		}

		return sprintf(
			'<div class="cafe-lang">'
				. '<button type="button" class="cafe-header-icon cafe-lang__toggle" aria-haspopup="true" aria-expanded="false" aria-controls="%1$s" aria-label="%2$s">'
				. '%3$s<svg class="cafe-lang__chevron" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>'
				. '</button>'
				. '<ul class="cafe-lang__menu" id="%1$s" hidden>%4$s</ul>'
				. '</div>',
			esc_attr( $menu_id ),
			/* translators: %s: tên ngôn ngữ đang dùng */
			esc_attr( sprintf( __( 'Chọn ngôn ngữ (đang dùng: %s)', 'cafe-core' ), $languages[ $current ]['label'] ) ),
			self::flag( $current ),
			$items
		);
	}

	/** Cờ SVG tỉ lệ 3:2. Cờ Anh cần clipPath có id riêng vì một trang có thể in nhiều cờ (desktop + mobile, nút + menu). */
	private static function flag( string $code ): string {
		static $clips = 0;
		if ( 'en' === $code ) {
			$clip = 'cafe-flag-gb-' . ( ++$clips );
			return '<svg class="cafe-lang__flag" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30" width="22" height="15" preserveAspectRatio="xMidYMid slice" aria-hidden="true">'
				. '<clipPath id="' . $clip . '"><path d="M30,15h30v15zv15h-30zh-30v-15zv-15h30z"/></clipPath>'
				. '<path d="M0,0v30h60v-30z" fill="#012169"/>'
				. '<path d="M0,0L60,30M60,0L0,30" stroke="#fff" stroke-width="6"/>'
				. '<path d="M0,0L60,30M60,0L0,30" clip-path="url(#' . $clip . ')" stroke="#C8102E" stroke-width="4"/>'
				. '<path d="M30,0v30M0,15h60" stroke="#fff" stroke-width="10"/>'
				. '<path d="M30,0v30M0,15h60" stroke="#C8102E" stroke-width="6"/>'
				. '</svg>';
		}
		return '<svg class="cafe-lang__flag" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 24" width="22" height="15" aria-hidden="true">'
			. '<rect width="36" height="24" fill="#DA251D"/>'
			. '<polygon points="18,4 21.5,14 11,7.5 25,7.5 14.5,14" fill="#FFFF00"/>'
			. '</svg>';
	}
}
