<?php
/**
 * Trang Liên hệ, 3 trang chính sách và thông tin cửa hàng mẫu.
 * Chạy: wp eval-file /scripts/seed/pages.php
 */
use CafeCore\Settings;

require_once __DIR__ . '/helpers.php';

$address = '12 Lê Duẩn, Phường Buôn Ma Thuột, Tỉnh Đắk Lắk';
$phone   = '0900 000 000';

$contact = cafe_seed_paragraphs(
	'<strong>Cà Phê Mộc</strong><br>Địa chỉ: ' . $address . '<br>Điện thoại: ' . $phone . '<br>Giờ làm việc: 8:00 – 20:00 hằng ngày'
) . "\n\n<!-- wp:html -->\n" .
	'<iframe title="Bản đồ Cà Phê Mộc" src="https://www.google.com/maps?q=' . rawurlencode( $address ) . '&amp;output=embed" width="100%" height="380" style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>' .
	"\n<!-- /wp:html -->";
cafe_seed_page( 'lien-he', 'Liên hệ', $contact );

cafe_seed_page(
	'chinh-sach-giao-hang',
	'Chính sách giao hàng',
	cafe_seed_paragraphs(
		'Chúng tôi giao hàng toàn quốc qua đơn vị vận chuyển, thời gian 2–5 ngày làm việc tuỳ khu vực.',
		'Phí giao hàng đồng giá 30.000đ. Miễn phí giao hàng cho đơn từ 500.000đ.',
		'Bạn thanh toán bằng tiền mặt khi nhận hàng (COD).'
	)
);
cafe_seed_page(
	'chinh-sach-doi-tra',
	'Chính sách đổi trả',
	cafe_seed_paragraphs(
		'Đổi hàng miễn phí trong 7 ngày nếu sản phẩm lỗi, sai loại hoặc bao bì bị hư hỏng khi vận chuyển.',
		'Vui lòng giữ nguyên bao bì và liên hệ ' . $phone . ' để được hỗ trợ.'
	)
);
$privacy_id = cafe_seed_page(
	'chinh-sach-bao-mat',
	'Chính sách bảo mật',
	cafe_seed_paragraphs(
		'Chúng tôi chỉ dùng họ tên, số điện thoại và địa chỉ của bạn để giao hàng và chăm sóc đơn hàng.',
		'Thông tin không được chia sẻ cho bên thứ ba ngoài đơn vị vận chuyển.'
	)
);
update_option( 'wp_page_for_privacy_policy', $privacy_id );

update_option( 'woocommerce_store_address', '12 Lê Duẩn' );
update_option( 'woocommerce_store_city', 'Đắk Lắk' );
if ( '' === Settings::get( 'shop_phone' ) ) {
	update_option( Settings::OPTION, Settings::sanitize( array_merge( Settings::all(), array( 'shop_phone' => $phone ) ) ) );
}

WP_CLI::success( 'Trang nội dung đã sẵn sàng.' );
