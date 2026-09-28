<?php
/**
 * Đặt tên/slug tiếng Việt cho các trang WooCommerce và ép Giỏ hàng/Thanh toán dùng shortcode (classic).
 * Chạy: wp eval-file /scripts/setup/pages.php
 */
$pages = array(
	'woocommerce_shop_page_id'     => array( 'Cửa hàng', 'cua-hang', null ),
	'woocommerce_cart_page_id'     => array( 'Giỏ hàng', 'gio-hang', "<!-- wp:shortcode -->\n[woocommerce_cart]\n<!-- /wp:shortcode -->" ),
	'woocommerce_checkout_page_id' => array( 'Thanh toán', 'thanh-toan', "<!-- wp:shortcode -->\n[woocommerce_checkout]\n<!-- /wp:shortcode -->" ),
);

foreach ( $pages as $option => list( $title, $slug, $content ) ) {
	$page_id = (int) get_option( $option );
	if ( ! $page_id || ! get_post( $page_id ) ) {
		$page_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title ), true );
		if ( is_wp_error( $page_id ) ) {
			WP_CLI::error( $page_id->get_error_message() );
		}
		update_option( $option, $page_id );
	}

	$update = array( 'ID' => $page_id, 'post_title' => $title, 'post_name' => $slug, 'post_status' => 'publish' );
	if ( null !== $content ) {
		$update['post_content'] = $content;
	}
	wp_update_post( $update );
	WP_CLI::log( "{$title}: /{$slug}/ (#{$page_id})" );
}

// Không dùng tài khoản khách hàng ở giai đoạn 1.
$account_id = (int) get_option( 'woocommerce_myaccount_page_id' );
if ( $account_id && get_post( $account_id ) && 'trash' !== get_post_status( $account_id ) ) {
	wp_trash_post( $account_id );
	WP_CLI::log( 'Đã chuyển trang Tài khoản vào thùng rác.' );
}

WP_CLI::success( 'Trang WooCommerce đã sẵn sàng.' );
