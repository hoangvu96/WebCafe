<?php
/**
 * Nạp bản tiếng Anh cho nội dung trong DB (trừ sản phẩm): trang, danh mục, giá trị thuộc tính,
 * menu, bảng cụm từ. Chỉ điền chỗ còn thiếu nên chạy lại an toàn và không đè bản đã sửa trong wp-admin.
 * Chạy: wp eval-file /scripts/setup/translations-en.php
 */

use CafeCore\Language\ContentAdmin;
use CafeCore\Language\Phrases;
use const CafeCore\Language\MENU_META;
use const CafeCore\Language\TERM_NAME_META;

require_once WP_PLUGIN_DIR . '/cafe-core/includes/language/class-content-admin.php';

// WP-CLI chạy không có người dùng nên kses sẽ lọc mất iframe/HTML của block; nội dung dưới đây là tin cậy.
kses_remove_filters();

// ─── Trang ───────────────────────────────────────────────────────────────────

/** Cụm từ trên trang chủ (block Kadence): thay trực tiếp trong markup để giữ nguyên bố cục. */
$home_phrases = array(
	'Rang xay mỗi tuần'                                => 'Roasted every week',
	'Cà phê rang mộc, đậm vị cao nguyên'               => 'Honest roasts with the bold taste of the highlands',
	'Hạt chọn lọc từ Buôn Ma Thuột, Cầu Đất, Sơn La – rang mới mỗi tuần, giao tận nhà trên toàn quốc.'
		=> 'Hand-picked beans from Buon Ma Thuot, Cau Dat and Son La – freshly roasted every week and delivered to your door nationwide.',
	'Rang mới mỗi tuần'                                => 'Freshly roasted weekly',
	'Mỗi mẻ rang nhỏ, đóng gói ngay để giữ trọn hương.' => 'Roasted in small batches and packed right away to lock in the aroma.',
	'Nguyên chất 100%'                                 => '100% pure',
	'Không pha trộn đậu, bắp hay hương liệu.'          => 'No soybeans, corn or artificial flavors.',
	'Giao toàn quốc'                                   => 'Nationwide delivery',
	'Miễn phí vận chuyển cho đơn từ 500.000đ.'         => 'Free shipping on orders from 500.000đ.',
	'Danh mục'                                         => 'Categories',
	'Chọn gu cà phê của bạn'                           => 'Find your coffee style',
	'Đậm, đắng, hậu vị sô-cô-la – hợp pha phin.'       => 'Bold and bitter with a chocolate finish – perfect for phin brewing.',
	'Chua thanh, hương trái cây – hợp pour-over.'      => 'Bright acidity and fruity notes – perfect for pour-over.',
	'Hạt tròn đặc biệt, vị đậm và thơm hơn.'           => 'Special round peaberry beans, bolder and more aromatic.',
	'Phối trộn cân bằng cho phin hay espresso.'        => 'A balanced blend for phin or espresso.',
	'Bán chạy'                                         => 'Best sellers',
	'Được yêu thích nhất'                              => 'Customer favorites',
	'Vùng trồng'                                       => 'Growing regions',
	'Từ nương rẫy đến tách cà phê'                     => 'From the farm to your cup',
	'Chúng tôi làm việc trực tiếp với các hộ nông dân ở Tây Nguyên và Tây Bắc, thu hái chín, sơ chế cẩn thận và rang theo từng mẻ nhỏ để giữ đúng hương vị của từng vùng đất.'
		=> 'We work directly with farming families in the Central Highlands and the Northwest, picking only ripe cherries, processing them with care and roasting in small batches to keep the true character of each region.',
	'Mua ngay'                                         => 'Shop now',
	'Xem sản phẩm'                                     => 'View products',
);

$paragraphs = static function ( array $lines ): string {
	return implode( "\n\n", array_map( static fn( string $p ): string => "<!-- wp:paragraph -->\n<p>{$p}</p>\n<!-- /wp:paragraph -->", $lines ) );
};

$contact_page = get_page_by_path( 'lien-he' );
$contact_vi   = $contact_page ? $contact_page->post_content : '';
$contact_en   = strtr(
	$contact_vi,
	array(
		'Địa chỉ:'                 => 'Address:',
		'Điện thoại:'              => 'Phone:',
		'Giờ làm việc:'            => 'Opening hours:',
		'8:00 – 20:00 hằng ngày'   => '8:00 – 20:00 daily',
		'title="Bản đồ Cà Phê Mộc"' => 'title="Map of Cà Phê Mộc"',
	)
);

/** slug trang tiếng Việt => [tiêu đề tiếng Anh, nội dung tiếng Anh hoặc null để giữ nội dung gốc]. */
$pages = array(
	'home'                 => array( 'Home', strtr( (string) get_post_field( 'post_content', (int) get_option( 'page_on_front' ) ), $home_phrases ) ),
	'cua-hang'             => array( 'Shop', null ),
	'gio-hang'             => array( 'Cart', null ),
	'thanh-toan'           => array( 'Checkout', null ),
	'lien-he'              => array( 'Contact', $contact_en ),
	'chinh-sach-giao-hang' => array(
		'Shipping policy',
		$paragraphs(
			array(
				'We deliver nationwide through our shipping partners within 2–5 business days, depending on your area.',
				'Shipping is a flat 30.000đ. Orders from 500.000đ ship free.',
				'You pay in cash when your order arrives (COD).',
			)
		),
	),
	'chinh-sach-doi-tra'   => array(
		'Return policy',
		$paragraphs(
			array(
				'Free exchange within 7 days if the product is defective, the wrong type, or the packaging was damaged in transit.',
				'Please keep the original packaging and call 0900 000 000 for support.',
			)
		),
	),
	'chinh-sach-bao-mat'   => array(
		'Privacy policy',
		$paragraphs(
			array(
				'We only use your name, phone number and address to deliver and look after your orders.',
				'Your information is never shared with third parties other than our shipping partners.',
			)
		),
	),
);

foreach ( $pages as $slug => list( $title, $content ) ) {
	$page = get_page_by_path( $slug );
	if ( ! $page ) {
		WP_CLI::warning( "Không thấy trang {$slug}, bỏ qua." );
		continue;
	}
	$en_id = ContentAdmin::ensure_en_page( $page->ID, $title, (string) $content );
	$left  = preg_match_all( '/[àáảãạăắằẳẵặâấầẩẫậđèéẻẽẹêếềểễệìíỉĩịòóỏõọôốồổỗộơớờởỡợùúủũụưứừửữựỳýỷỹỵ]+/iu', strip_tags( (string) get_post_field( 'post_content', $en_id ) ) );
	WP_CLI::log( "Trang {$slug} → #{$en_id} ({$title})" . ( $left ? " – còn {$left} từ có dấu (địa chỉ, tên riêng?)" : '' ) );
}

// ─── Danh mục, giá trị thuộc tính ───────────────────────────────────────────

$terms = array(
	'product_cat'   => array( 'hoa-tan' => 'Instant', 'phin-giay' => 'Drip bags', 'uncategorized' => 'Uncategorized' ),
	'pa_dang-xay'   => array( 'nguyen-hat' => 'Whole bean', 'xay-phin' => 'Phin grind', 'xay-espresso' => 'Espresso grind', 'xay-pour-over' => 'Pour-over grind' ),
);
foreach ( $terms as $taxonomy => $names ) {
	foreach ( $names as $slug => $name ) {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( $term && '' === (string) get_term_meta( $term->term_id, TERM_NAME_META, true ) ) {
			update_term_meta( $term->term_id, TERM_NAME_META, $name );
		}
	}
}
WP_CLI::log( 'Đã điền tên tiếng Anh cho danh mục và giá trị thuộc tính.' );

// ─── Menu ────────────────────────────────────────────────────────────────────

$menu_titles = array(
	'Trang chủ'            => 'Home',
	'Tất cả sản phẩm'      => 'All products',
	'Sản phẩm'             => 'Products',
	'Liên hệ'              => 'Contact',
	'Chính sách giao hàng' => 'Shipping policy',
	'Chính sách đổi trả'   => 'Return policy',
	'Chính sách bảo mật'   => 'Privacy policy',
);
foreach ( wp_get_nav_menus() as $menu ) {
	foreach ( (array) wp_get_nav_menu_items( $menu ) as $item ) {
		if ( isset( $menu_titles[ $item->title ] ) && '' === (string) get_post_meta( $item->ID, MENU_META, true ) ) {
			update_post_meta( $item->ID, MENU_META, $menu_titles[ $item->title ] );
		}
	}
}
WP_CLI::log( 'Đã điền nhãn tiếng Anh cho menu.' );

// ─── Bảng cụm từ ─────────────────────────────────────────────────────────────

$phrases = array(
	'Cà phê rang xay nguyên chất'                              => 'Pure roasted & ground coffee',
	'Địa chỉ:'                                                 => 'Address:',
	'Điện thoại:'                                              => 'Phone:',
	'Chính sách'                                               => 'Policies',
	'Khối lượng'                                               => 'Weight',
	'Dạng xay'                                                 => 'Grind',
	'Thanh toán khi nhận hàng (COD)'                           => 'Cash on delivery (COD)',
	'Bạn trả tiền mặt cho nhân viên giao hàng khi nhận hàng.'  => 'Pay the courier in cash when your order arrives.',
	'Thông tin cá nhân của bạn được dùng để xử lý đơn hàng và hỗ trợ bạn khi mua sắm trên website, như mô tả trong [privacy_policy].'
		=> 'Your personal data will be used to process your order and support your experience on this website, as described in our [privacy_policy].',
	'Thông tin cá nhân của bạn được dùng để quản lý tài khoản và hỗ trợ bạn trên website, như mô tả trong [privacy_policy].'
		=> 'Your personal data will be used to manage your account and support your experience on this website, as described in our [privacy_policy].',
);
$stored  = (string) get_option( Phrases::OPTION, '' );
$missing = array_diff_key( $phrases, Phrases::parse( $stored ) );
if ( $missing ) {
	update_option( Phrases::OPTION, ltrim( rtrim( $stored ) . "\n" . Phrases::format( $missing ) ), false );
}
WP_CLI::success( sprintf( 'Bản dịch tiếng Anh đã sẵn sàng (thêm %d cụm từ).', count( $missing ) ) );
