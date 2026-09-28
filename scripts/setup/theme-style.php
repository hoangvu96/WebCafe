<?php
/**
 * Phong cách "mộc mạc Việt" cho Kadence: global palette, typography, header, footer.
 * Chạy lại an toàn. Chạy: wp eval-file /scripts/setup/theme-style.php
 * (Chưa gọi từ setup.sh – xem Task 16.)
 */

// 1. Global palette (option JSON của Kadence). Đặt palette1–10, giữ nguyên palette11–15 và 2 palette phụ.
$cafe_colors = array(
	'palette1' => '#4A2C1D',
	'palette2' => '#8B5A3C',
	'palette3' => '#2E1B12',
	'palette4' => '#4A2C1D',
	'palette5' => '#6B5242',
	'palette6' => '#9C8574',
	'palette7' => '#D9C3A5',
	'palette8' => '#F5EDE0',
	'palette9' => '#FFFBF5',
	'palette10' => '#5B6B3A', // Complement – xanh lá cà phê (badge còn hàng)
);
$palette = json_decode( (string) get_option( 'kadence_global_palette', '' ), true );
if ( ! is_array( $palette ) || empty( $palette['palette'] ) ) {
	$palette = array( 'palette' => array() );
}
$existing = array();
foreach ( $palette['palette'] as $item ) {
	$existing[ $item['slug'] ] = $item;
}
foreach ( $cafe_colors as $slug => $color ) {
	$existing[ $slug ] = array(
		'color' => $color,
		'slug'  => $slug,
		'name'  => isset( $existing[ $slug ]['name'] ) ? $existing[ $slug ]['name'] : 'Palette Color ' . substr( $slug, 7 ),
	);
}
$palette['palette'] = array_values( $existing );
$palette['active']  = 'palette';
update_option( 'kadence_global_palette', wp_json_encode( $palette ) );

// 2. Typography: nội dung Be Vietnam Pro, tiêu đề Lora.
$base_font = array_merge(
	(array) get_theme_mod( 'base_font', array() ),
	array(
		'size'       => array( 'desktop' => 17, 'tablet' => '', 'mobile' => 16 ),
		'lineHeight' => array( 'desktop' => 1.65 ),
		'family'     => 'Be Vietnam Pro',
		'google'     => true,
		'weight'     => '400',
		'variant'    => 'regular',
		'color'      => 'palette5',
		'sizeType'   => 'px',
		'lineType'   => '',
		'fallback'   => 'sans-serif',
	)
);
set_theme_mod( 'base_font', $base_font );
set_theme_mod(
	'heading_font',
	array(
		'family'   => 'Lora',
		'google'   => true,
		'fallback' => 'serif',
		'variant'  => array( '500', '600', '700' ),
	)
);
$heading_sizes = array(
	'h1_font' => array( 52, 40, 34, '700', 'palette3' ),
	'h2_font' => array( 38, 32, 28, '700', 'palette3' ),
	'h3_font' => array( 28, 24, 22, '600', 'palette3' ),
	'h4_font' => array( 22, 20, 19, '600', 'palette4' ),
	'h5_font' => array( 18, 17, 17, '600', 'palette4' ),
	'h6_font' => array( 14, 14, 13, '600', 'palette2' ),
);
foreach ( $heading_sizes as $mod => $v ) {
	$font = (array) get_theme_mod( $mod, array() );
	set_theme_mod(
		$mod,
		array_merge(
			$font,
			array(
				'size'       => array( 'desktop' => $v[0], 'tablet' => $v[1], 'mobile' => $v[2] ),
				'lineHeight' => array( 'desktop' => 1.25 ),
				'lineType'   => '',
				'sizeType'   => 'px',
				'family'     => 'inherit',
				'google'     => false,
				'weight'     => $v[3],
				'variant'    => $v[3],
				'color'      => $v[4],
			)
		)
	);
}
$h6 = (array) get_theme_mod( 'h6_font', array() );
$h6['transform']     = 'uppercase';
$h6['letterSpacing'] = array( 'desktop' => 0.12 );
$h6['spacingType']   = 'em';
set_theme_mod( 'h6_font', $h6 );

// 3. Nhận diện: bỏ logo ảnh của mẫu, dùng tên site dạng chữ.
remove_theme_mod( 'custom_logo' );
set_theme_mod(
	'logo_layout',
	array(
		'include' => array( 'mobile' => 'title', 'tablet' => 'title', 'desktop' => 'title' ),
		'layout'  => array( 'mobile' => 'standard', 'tablet' => 'standard', 'desktop' => 'standard' ),
		'flag'    => true,
	)
);
set_theme_mod(
	'brand_typography',
	array(
		'size'       => array( 'desktop' => 28, 'tablet' => 26, 'mobile' => 22 ),
		'lineHeight' => array( 'desktop' => 1.2 ),
		'family'     => 'Lora',
		'google'     => true,
		'weight'     => '700',
		'variant'    => '700',
		'color'      => 'palette3',
		'sizeType'   => 'px',
	)
);

// 4. Header: logo trái, menu chính + giỏ hàng phải; bỏ hàng trên cùng (mạng xã hội giả) và menu phụ rỗng.
set_theme_mod(
	'header_desktop_items',
	array(
		'top'    => array( 'top_left' => array(), 'top_left_center' => array(), 'top_center' => array(), 'top_right_center' => array(), 'top_right' => array() ),
		'main'   => array( 'main_left' => array( 'logo' ), 'main_left_center' => array(), 'main_center' => array(), 'main_right_center' => array(), 'main_right' => array( 'navigation', 'cart' ) ),
		'bottom' => array( 'bottom_left' => array(), 'bottom_left_center' => array(), 'bottom_center' => array(), 'bottom_right_center' => array(), 'bottom_right' => array() ),
		'flag'   => true,
	)
);
set_theme_mod( 'header_main_padding', array( 'size' => array( 'desktop' => array( '', '', '', '' ) ), 'unit' => array( 'desktop' => 'px' ), 'locked' => array( 'desktop' => false ), 'flag' => true ) );
set_theme_mod( 'header_main_height', array( 'size' => array( 'mobile' => 64, 'tablet' => 72, 'desktop' => 88 ), 'unit' => array( 'mobile' => 'px', 'tablet' => 'px', 'desktop' => 'px' ) ) );
set_theme_mod( 'header_main_background', array( 'desktop' => array( 'color' => 'palette9' ), 'flag' => true ) );
set_theme_mod( 'header_main_bottom_border', array( 'desktop' => array( 'width' => 1, 'unit' => 'px', 'style' => 'dashed', 'color' => 'palette7' ), 'flag' => true ) );
set_theme_mod( 'header_cart_color', array( 'color' => 'palette1', 'hover' => 'palette2' ) );
// Menu trượt trên điện thoại: nền nâu cà phê đậm, chữ kem.
set_theme_mod( 'header_popup_background', array( 'desktop' => array( 'color' => 'palette3' ) ) );
set_theme_mod( 'mobile_navigation_color', array( 'color' => 'palette8', 'hover' => 'palette7', 'active' => 'palette7' ) );
set_theme_mod( 'mobile_navigation_divider', array( 'width' => 1, 'unit' => 'px', 'style' => 'dashed', 'color' => 'rgba(217,195,165,0.35)' ) );
set_theme_mod( 'mobile_navigation_typography', array( 'size' => array( 'desktop' => 16 ), 'family' => 'inherit', 'google' => false, 'weight' => '500', 'variant' => '500' ) );
$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
unset( $locations['secondary'] );
set_theme_mod( 'nav_menu_locations', $locations );

// 5. Footer: cột 1 tên + tagline + địa chỉ + điện thoại, cột 2 widget menu "Chính sách"; dòng bản quyền tiếng Việt.
set_theme_mod(
	'footer_items',
	array(
		'top'    => array( 'top_1' => array(), 'top_2' => array(), 'top_3' => array(), 'top_4' => array(), 'top_5' => array() ),
		'middle' => array( 'middle_1' => array( 'footer-widget1' ), 'middle_2' => array( 'footer-widget2' ), 'middle_3' => array(), 'middle_4' => array(), 'middle_5' => array() ),
		'bottom' => array( 'bottom_1' => array( 'footer-html' ), 'bottom_2' => array(), 'bottom_3' => array(), 'bottom_4' => array(), 'bottom_5' => array() ),
		'flag'   => true,
	)
);
set_theme_mod( 'footer_middle_columns', '2' );
set_theme_mod( 'footer_middle_layout', array( 'mobile' => 'row', 'tablet' => '', 'desktop' => 'left-golden', 'flag' => true ) );
set_theme_mod( 'footer_middle_background', array( 'desktop' => array( 'color' => 'palette7' ), 'flag' => true ) );
set_theme_mod( 'footer_middle_top_border', array( 'desktop' => array( 'width' => 1, 'unit' => 'px', 'style' => 'dashed', 'color' => 'palette2' ), 'flag' => true ) );
set_theme_mod( 'footer_middle_bottom_border', array( 'desktop' => array( 'width' => 0, 'unit' => 'px', 'style' => 'solid', 'color' => '' ), 'flag' => true ) );
set_theme_mod( 'footer_bottom_background', array( 'desktop' => array( 'color' => 'palette3' ), 'flag' => true ) );
set_theme_mod( 'footer_html_content', '{copyright} {year} {site-title} – Cà phê rang xay nguyên chất.' );
set_theme_mod( 'footer_html_typography', array( 'color' => 'palette7' ) );

$footer_block = '<!-- wp:heading {"level":3,"className":"cafe-footer-brand"} -->' . "\n"
	. '<h3 class="wp-block-heading cafe-footer-brand">Cà Phê Mộc</h3>' . "\n"
	. '<!-- /wp:heading -->' . "\n\n"
	. '<!-- wp:paragraph -->' . "\n"
	. '<p>Cà phê rang xay nguyên chất</p>' . "\n"
	. '<!-- /wp:paragraph -->' . "\n\n"
	. '<!-- wp:paragraph -->' . "\n"
	. '<p>Địa chỉ: 12 Lê Duẩn, Phường Buôn Ma Thuột, Tỉnh Đắk Lắk<br>Điện thoại: <a href="tel:0900000000">0900 000 000</a></p>' . "\n"
	. '<!-- /wp:paragraph -->';
$blocks       = (array) get_option( 'widget_block', array() );
$blocks[12]   = array( 'content' => $footer_block );
update_option( 'widget_block', $blocks );

$policy_menu = wp_get_nav_menu_object( 'chinh-sach' );
$nav_widgets = (array) get_option( 'widget_nav_menu', array() );
$nav_widgets[2] = array(
	'title'    => 'Chính sách',
	'nav_menu' => $policy_menu ? (int) $policy_menu->term_id : 0,
);
$nav_widgets['_multiwidget'] = 1;
update_option( 'widget_nav_menu', $nav_widgets );

$sidebars            = wp_get_sidebars_widgets();
$sidebars['footer1'] = array( 'block-12' );
$sidebars['footer2'] = array( 'nav_menu-2' );
wp_set_sidebars_widgets( $sidebars );

// 6. Tiêu đề trang (mẫu đã tắt): bật lại cho trang thường và lưu trữ sản phẩm; ẩn trên trang chủ (đã có hero).
set_theme_mod( 'page_title', true );
set_theme_mod( 'page_title_layout', 'normal' );
set_theme_mod( 'product_archive_title', true );
$front_id = (int) get_option( 'page_on_front' );
if ( $front_id ) {
	update_post_meta( $front_id, '_kad_post_title', 'hide' );
	update_post_meta( $front_id, '_kad_post_layout', 'fullwidth' );
}
// Trang thường (Liên hệ, Chính sách) dùng khung có lề; chỉ trang chủ tràn toàn màn hình.
set_theme_mod( 'page_layout', 'normal' );
// Mẫu import đặt ẩn tiêu đề ở Giỏ hàng/Thanh toán – trả về mặc định để trang có h1.
foreach ( array( 'woocommerce_cart_page_id', 'woocommerce_checkout_page_id' ) as $wc_page_option ) {
	$wc_page_id = (int) get_option( $wc_page_option );
	if ( $wc_page_id && 'hide' === get_post_meta( $wc_page_id, '_kad_post_title', true ) ) {
		delete_post_meta( $wc_page_id, '_kad_post_title' );
	}
}

// 7. Lưới sản phẩm 2 cột trên điện thoại.
set_theme_mod( 'product_archive_mobile_columns', 'twocolumn' );

// 8. Câu thông báo quyền riêng tư của WooCommerce (lưu tiếng Anh trong DB từ lúc cài).
update_option( 'woocommerce_checkout_privacy_policy_text', 'Thông tin cá nhân của bạn được dùng để xử lý đơn hàng và hỗ trợ bạn khi mua sắm trên website, như mô tả trong [privacy_policy].' );
update_option( 'woocommerce_registration_privacy_policy_text', 'Thông tin cá nhân của bạn được dùng để quản lý tài khoản và hỗ trợ bạn trên website, như mô tả trong [privacy_policy].' );

echo "theme-style: done\n";
