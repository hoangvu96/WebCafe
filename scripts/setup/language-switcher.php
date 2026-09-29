<?php
/**
 * Đặt nút chọn ngôn ngữ [cafe_language_switcher] (plugin cafe-core) vào header Kadence:
 * desktop: phần tử HTML giữa ô tìm kiếm và giỏ hàng; mobile: phần tử HTML ngay trước giỏ hàng.
 * Chạy lại an toàn. Chạy: wp eval-file /scripts/setup/language-switcher.php
 */
$shortcode = '[cafe_language_switcher]';

/** Chèn $item vào $list ngay trước $before (hoặc cuối danh sách), nếu chưa có. */
$insert_before = static function ( array $list, string $item, string $before ): array {
	if ( in_array( $item, $list, true ) ) {
		return $list;
	}
	$pos = array_search( $before, $list, true );
	array_splice( $list, false === $pos ? count( $list ) : $pos, 0, array( $item ) );
	return $list;
};

$desktop = (array) get_theme_mod( 'header_desktop_items', array() );
$desktop['main']['main_right'] = $insert_before( (array) ( $desktop['main']['main_right'] ?? array() ), 'html', 'cart' );
set_theme_mod( 'header_desktop_items', $desktop );
set_theme_mod( 'header_html_content', $shortcode );
set_theme_mod( 'header_html_wpautop', false );

$mobile = (array) get_theme_mod( 'header_mobile_items', array() );
$mobile['main']['main_right'] = $insert_before( (array) ( $mobile['main']['main_right'] ?? array() ), 'mobile-html', 'mobile-cart' );
set_theme_mod( 'header_mobile_items', $mobile );
set_theme_mod( 'mobile_html_content', $shortcode );
set_theme_mod( 'mobile_html_wpautop', false );

WP_CLI::success( 'Đã đặt nút chọn ngôn ngữ vào header desktop và mobile.' );
