<?php
defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'cafe-child-fonts',
			'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600&family=Lora:wght@500;600;700&display=swap',
			array(),
			null
		);
		// Priority 20: nạp sau CSS và biến màu inline của Kadence để ghi đè được.
		wp_enqueue_style(
			'cafe-child',
			get_stylesheet_directory_uri() . '/assets/css/cafe.css',
			array( 'cafe-child-fonts' ),
			wp_get_theme()->get( 'Version' )
		);
	},
	20
);

/**
 * Việt hoá vài chuỗi hiển thị của Kadence/WooCommerce chưa có bản dịch tiếng Việt.
 */
add_filter(
	'gettext',
	static function ( string $translation, string $text, string $domain ): string {
		static $kadence = null;
		if ( 'kadence' !== $domain ) {
			return $translation;
		}
		if ( null === $kadence ) {
			$kadence = array(
				'Cart Summary'    => __( 'Sản phẩm trong giỏ', 'cafe-child' ),
				'Skip to content' => __( 'Chuyển đến nội dung', 'cafe-child' ),
				'Shopping Cart'   => __( 'Giỏ hàng', 'cafe-child' ),
				'Open menu'       => __( 'Mở menu', 'cafe-child' ),
				'Close menu'      => __( 'Đóng menu', 'cafe-child' ),
				'Grid View'       => __( 'Dạng lưới', 'cafe-child' ),
				'List View'       => __( 'Dạng danh sách', 'cafe-child' ),
				'Grid'            => __( 'Lưới', 'cafe-child' ),
				'List'            => __( 'Danh sách', 'cafe-child' ),
				'Primary'         => __( 'Menu chính', 'cafe-child' ),
				'Primary Mobile'  => __( 'Menu chính trên điện thoại', 'cafe-child' ),
			);
		}
		return isset( $kadence[ $text ] ) ? $kadence[ $text ] : $translation;
	},
	10,
	3
);
add_filter(
	'gettext_with_context',
	static function ( string $translation, string $text, string $context, string $domain ): string {
		if ( 'woocommerce' === $domain && 'shipping packages' === $context && 'Shipment' === $text ) {
			return __( 'Vận chuyển', 'cafe-child' );
		}
		return $translation;
	},
	10,
	4
);
