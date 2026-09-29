<?php
namespace CafeCore\Language;

defined( 'ABSPATH' ) || exit;

/*
 * Bản tiếng Anh cho nội dung lưu trong DB (trừ sản phẩm, vốn giữ một ngôn ngữ):
 * - Trang: một trang riêng tư chứa bản tiếng Anh, liên kết qua meta PAGE_META. URL vẫn là trang gốc.
 * - Danh mục sản phẩm, giá trị thuộc tính (pa_*): term meta TERM_NAME_META.
 * - Mục menu: meta MENU_META.
 * - Chuỗi lẻ (khẩu hiệu, footer, widget, COD, câu chính sách, tên thuộc tính): bảng Phrases.
 */

const PAGE_META      = '_cafe_en_page';
const SOURCE_META    = '_cafe_vi_page';
const TERM_NAME_META = 'cafe_en_name';
const MENU_META      = '_cafe_en_title';

/** Đang hiển thị giao diện khách bằng ngôn ngữ khác tiếng Việt. */
function translating(): bool {
	return applies() && Language::DEFAULT !== current();
}

/** Trang tiếng Anh đã sẵn sàng (công khai hoặc riêng tư, không tính bản nháp) của một trang. */
function en_page( int $post_id ): ?\WP_Post {
	if ( $post_id <= 0 || 'page' !== get_post_type( $post_id ) ) {
		return null;
	}
	$en_id = (int) get_post_meta( $post_id, PAGE_META, true );
	$en    = $en_id ? get_post( $en_id ) : null;
	return ( $en && 'page' === $en->post_type && in_array( $en->post_status, array( 'publish', 'private' ), true ) ) ? $en : null;
}

function translatable_taxonomy( string $taxonomy ): bool {
	return 'product_cat' === $taxonomy || str_starts_with( $taxonomy, 'pa_' );
}

/** @return array<string, string> */
function phrases(): array {
	static $map = null;
	if ( null === $map ) {
		$map = Phrases::parse( (string) get_option( Phrases::OPTION, '' ) );
	}
	return $map;
}

function translate_phrases( $text ) {
	return is_string( $text ) && translating() ? Phrases::translate( $text, phrases() ) : $text;
}

// ─── Trang ─────────────────────────────────────────────────────────────────────

add_filter(
	'the_title',
	static function ( $title, $post_id = 0 ) {
		$en = translating() ? en_page( (int) $post_id ) : null;
		return $en && '' !== $en->post_title ? $en->post_title : $title;
	},
	10,
	2
);

add_filter(
	'single_post_title',
	static function ( $title, $post ) {
		$en = translating() && $post instanceof \WP_Post ? en_page( $post->ID ) : null;
		return $en && '' !== $en->post_title ? $en->post_title : $title;
	},
	10,
	2
);

// Ưu tiên 1: thay nội dung gốc trước khi do_blocks (9) và wpautop/shortcode chạy.
add_filter(
	'the_content',
	static function ( $content ) {
		$post = translating() ? get_post() : null;
		$en   = $post ? en_page( $post->ID ) : null;
		return $en ? $en->post_content : $content;
	},
	1
);

// ─── Danh mục, giá trị thuộc tính ─────────────────────────────────────────────

add_filter(
	'get_term',
	static function ( $term, $taxonomy ) {
		if ( ! $term instanceof \WP_Term || ! translatable_taxonomy( (string) $taxonomy ) || ! translating() ) {
			return $term;
		}
		$name = (string) get_term_meta( $term->term_id, TERM_NAME_META, true );
		if ( '' !== $name ) {
			$term->name = $name; // get_term() trả object mới mỗi lần nên không ảnh hưởng cache.
		}
		return $term;
	},
	10,
	2
);

// ─── Menu ──────────────────────────────────────────────────────────────────────

add_filter(
	'wp_setup_nav_menu_item',
	static function ( $item ) {
		if ( ! translating() || ! isset( $item->ID ) ) {
			return $item;
		}
		$title = (string) get_post_meta( $item->ID, MENU_META, true );
		if ( '' !== $title ) {
			$item->title = $title;
		}
		return $item;
	}
);

// ─── Chuỗi lẻ qua bảng cụm từ ────────────────────────────────────────────────

foreach ( array( 'option_blogdescription', 'theme_mod_footer_html_content', 'widget_block_content', 'widget_title', 'woocommerce_gateway_title', 'woocommerce_gateway_description', 'woocommerce_get_privacy_policy_text', 'woocommerce_attribute_label' ) as $hook ) {
	add_filter( $hook, __NAMESPACE__ . '\\translate_phrases', 20 );
}

if ( is_admin() ) {
	require_once __DIR__ . '/class-content-admin.php';
	ContentAdmin::register();
}
