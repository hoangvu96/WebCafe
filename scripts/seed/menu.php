<?php
/**
 * Tạo lại menu chính và menu footer, gán vào vị trí menu của Kadence.
 * Chạy: wp eval-file /scripts/seed/menu.php
 */
function cafe_seed_menu( string $name, array $items ): int {
	$existing = wp_get_nav_menu_object( $name );
	if ( $existing ) {
		wp_delete_nav_menu( $existing->term_id );
	}
	$menu_id = wp_create_nav_menu( $name );
	if ( is_wp_error( $menu_id ) ) {
		WP_CLI::error( $menu_id->get_error_message() );
	}
	foreach ( $items as $args ) {
		$result = wp_update_nav_menu_item( $menu_id, 0, $args + array( 'menu-item-status' => 'publish' ) );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
	}
	return (int) $menu_id;
}

function cafe_seed_page_item( string $slug ): array {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		WP_CLI::error( "Thiếu trang /{$slug}/ – chạy seed/pages.php trước." );
	}
	return array( 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $page->ID );
}

$main_items = array(
	array( 'menu-item-title' => 'Trang chủ', 'menu-item-type' => 'custom', 'menu-item-url' => home_url( '/' ) ),
	array( 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => wc_get_page_id( 'shop' ) ),
);
foreach ( array( 'robusta', 'arabica', 'culi', 'blend' ) as $slug ) {
	$term         = get_term_by( 'slug', $slug, 'product_cat' );
	$main_items[] = array( 'menu-item-type' => 'taxonomy', 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $term->term_id );
}
$main_items[] = cafe_seed_page_item( 'lien-he' );

$main_id   = cafe_seed_menu( 'Menu chính', $main_items );
$footer_id = cafe_seed_menu(
	'Chính sách',
	array( cafe_seed_page_item( 'chinh-sach-giao-hang' ), cafe_seed_page_item( 'chinh-sach-doi-tra' ), cafe_seed_page_item( 'chinh-sach-bao-mat' ), cafe_seed_page_item( 'lien-he' ) )
);

$locations            = (array) get_theme_mod( 'nav_menu_locations', array() );
$locations['primary'] = $main_id;
$locations['mobile']  = $main_id;
$locations['footer']  = $footer_id;
set_theme_mod( 'nav_menu_locations', $locations );

WP_CLI::success( 'Menu đã gán vào primary, mobile, footer.' );
