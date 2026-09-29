<?php
namespace CafeCore\Language;

defined( 'ABSPATH' ) || exit;

/*
 * Module này được nạp ngay khi plugin load (không chờ plugins_loaded) để filter
 * determine_locale có hiệu lực trước khi WordPress và các plugin nạp file dịch.
 */
require_once __DIR__ . '/class-language.php';
require_once __DIR__ . '/class-switcher.php';
require_once __DIR__ . '/class-phrases.php';

/** Ngôn ngữ khách vừa chọn qua ?lang=, hoặc null nếu không có / không hỗ trợ. */
function requested(): ?string {
	if ( ! isset( $_GET[ Language::QUERY_VAR ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return null;
	}
	$code = sanitize_key( wp_unslash( $_GET[ Language::QUERY_VAR ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return Language::is_supported( $code ) ? $code : null;
}

function current(): string {
	$cookie = isset( $_COOKIE[ Language::COOKIE ] ) ? sanitize_key( wp_unslash( $_COOKIE[ Language::COOKIE ] ) ) : null;
	return Language::resolve( requested(), $cookie );
}

/**
 * Chỉ áp dụng cho giao diện khách. wp-admin giữ ngôn ngữ của tài khoản; AJAX gửi từ
 * trang wp-admin (vd. trang tổng quan nhân viên) cũng vậy.
 */
function applies(): bool {
	if ( ! is_admin() ) {
		return true;
	}
	$referer = isset( $_SERVER['HTTP_REFERER'] ) ? (string) wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
	return wp_doing_ajax() && ! str_contains( $referer, '/wp-admin/' );
}

add_filter(
	'determine_locale',
	static function ( string $locale ): string {
		return applies() ? Language::locale( current() ) : $locale;
	},
	20
);

/** Lưu lựa chọn vào cookie rồi chuyển về URL không còn ?lang= để link chia sẻ không bị dính ngôn ngữ. */
add_action(
	'template_redirect',
	static function (): void {
		$code = requested();
		if ( null === $code || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
			return;
		}
		setcookie(
			Language::COOKIE,
			$code,
			array(
				'expires'  => time() + YEAR_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			)
		);
		wp_safe_redirect( remove_query_arg( Language::QUERY_VAR ) );
		exit;
	},
	1
);

/**
 * WooCommerce cache mức phí ship trong session theo hash của gói hàng, nên nhãn đã dịch
 * ("Phí vận chuyển") bị dùng lại sau khi đổi ngôn ngữ. Thêm locale vào gói để hash đổi theo.
 */
add_filter(
	'woocommerce_cart_shipping_packages',
	static function ( array $packages ): array {
		foreach ( $packages as $key => $package ) {
			$packages[ $key ]['cafe_locale'] = determine_locale();
		}
		return $packages;
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_register_script(
			Switcher::SCRIPT,
			plugins_url( 'assets/language-switcher.js', __FILE__ ),
			array(),
			CAFE_CORE_VERSION,
			array( 'strategy' => 'defer', 'in_footer' => true )
		);
	}
);

// Đặt [cafe_language_switcher] vào phần tử HTML của header Kadence (xem scripts/setup/language-switcher.php).
add_shortcode(
	'cafe_language_switcher',
	static function (): string {
		wp_enqueue_script( Switcher::SCRIPT );
		return Switcher::render( current() );
	}
);

require_once __DIR__ . '/content.php';
