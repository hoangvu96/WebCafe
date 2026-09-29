<?php
defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'cafe-child-fonts',
			'https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Noto+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap',
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
		wp_enqueue_script(
			'cafe-motion',
			get_stylesheet_directory_uri() . '/assets/js/cafe-motion.js',
			array(),
			wp_get_theme()->get( 'Version' ),
			array( 'strategy' => 'defer', 'in_footer' => true )
		);
	},
	20
);

add_action(
	'after_setup_theme',
	static function (): void {
		load_child_theme_textdomain( 'cafe-child', get_stylesheet_directory() . '/languages' );
	}
);

/**
 * Site đang dùng tiếng Việt? (chỉ khi đó mới điền các chuỗi còn thiếu bản dịch bên dưới).
 */
function cafe_child_is_vi_locale(): bool {
	return 0 === strpos( determine_locale(), 'vi' );
}

/**
 * Việt hoá vài chuỗi hiển thị của Kadence/WooCommerce chưa có bản dịch tiếng Việt.
 * Chỉ thay khi locale là tiếng Việt và chuỗi chưa được dịch, để không đè bản dịch chính thức.
 */
add_filter(
	'gettext',
	static function ( string $translation, string $text, string $domain ): string {
		static $kadence = null;
		if ( 'kadence' !== $domain || $translation !== $text || ! cafe_child_is_vi_locale() ) {
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
		if ( 'woocommerce' === $domain && 'shipping packages' === $context && 'Shipment' === $text
			&& $translation === $text && cafe_child_is_vi_locale() ) {
			return __( 'Vận chuyển', 'cafe-child' );
		}
		return $translation;
	},
	10,
	4
);

/**
 * Inject logo image vào header desktop và mobile của Kadence.
 * Kadence dùng 2 action khác nhau cho desktop và mobile.
 */
( static function (): void {
	$render = static function (): void {
		$id  = (int) get_theme_mod( 'custom_logo' );
		$url = $id ? wp_get_attachment_url( $id ) : '';
		// Fallback: dùng SVG trong theme nếu chưa set custom logo trong DB.
		if ( ! $url ) {
			$url = get_stylesheet_directory_uri() . '/assets/images/logo.svg';
		}
		echo '<img src="' . esc_url( $url ) . '" class="custom-logo svg-logo-image" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" />';
		add_filter( 'kadence_custom_logo', '__return_empty_string' );
		add_filter( 'kadence_mobile_custom_logo', '__return_empty_string' );
	};
	add_action( 'before_kadence_logo_output', $render );
	add_action( 'before_kadence_mobile_logo_output', $render );
} )();

/**
 * Cho phép tải lên SVG (cần cho logo vector).
 */
add_filter(
	'upload_mimes',
	static function ( array $mimes ): array {
		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';
		return $mimes;
	}
);
add_filter(
	'wp_check_filetype_and_ext',
	static function ( array $data, string $file, string $filename ): array {
		if ( substr( $filename, -4 ) === '.svg' ) {
			$data['ext']  = 'svg';
			$data['type'] = 'image/svg+xml';
		}
		return $data;
	},
	10,
	3
);

/**
 * Breadcrumb trên trang cửa hàng và danh mục sản phẩm.
 * Dùng WooCommerce breadcrumb với separator dấu "/" kiểu bánh mì.
 */
add_action(
	'woocommerce_before_shop_loop',
	static function (): void {
		if ( ! ( is_shop() || is_product_category() || is_product_tag() ) ) {
			return;
		}
		woocommerce_breadcrumb( array(
			'delimiter'   => '<span class="cafe-breadcrumb__sep" aria-hidden="true">/</span>',
			'wrap_before' => '<nav class="cafe-breadcrumb" aria-label="' . esc_attr__( 'Đường dẫn', 'cafe-child' ) . '">',
			'wrap_after'  => '</nav>',
			'before'      => '<span class="cafe-breadcrumb__item">',
			'after'       => '</span>',
			'home'        => __( 'Trang chủ', 'cafe-child' ),
		) );
	},
	5
);

/**
 * Đổi nhãn "Danh mục" cho breadcrumb cấp trung gian trên trang danh mục sản phẩm.
 * Kadence/WooCommerce thêm slug danh mục vào breadcrumb, nhưng không có cấp "Danh mục" ảo.
 * Bộ lọc này chèn thêm mục "Danh mục" vào giữa "Trang chủ" và tên danh mục cụ thể.
 */
add_filter(
	'woocommerce_get_breadcrumb',
	static function ( array $crumbs ): array {
		if ( ! is_product_category() ) {
			return $crumbs;
		}
		$shop_url = get_permalink( wc_get_page_id( 'shop' ) );
		// Chèn mục "Danh mục" ngay sau "Trang chủ" (index 0).
		array_splice( $crumbs, 1, 0, array( array( __( 'Danh mục', 'cafe-child' ), $shop_url ) ) );
		return $crumbs;
	},
	10
);

/**
 * AJAX live search sản phẩm — trả về JSON cho cafe-search.js.
 */
add_action( 'wp_ajax_cafe_live_search', 'cafe_child_live_search' );
add_action( 'wp_ajax_nopriv_cafe_live_search', 'cafe_child_live_search' );
function cafe_child_live_search(): void {
	check_ajax_referer( 'cafe_search_nonce', 'nonce' );

	$q = sanitize_text_field( wp_unslash( $_GET['q'] ?? '' ) );
	if ( strlen( $q ) < 2 ) {
		wp_send_json_success( array( 'products' => array(), 'total' => 0 ) );
	}

	$query = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		's'              => $q,
		'posts_per_page' => 6,
		'no_found_rows'  => false,
	) );

	$products = array();
	foreach ( $query->posts as $post ) {
		$product  = wc_get_product( $post->ID );
		if ( ! $product ) continue;
		$thumb_id = $product->get_image_id();
		$thumb    = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'thumbnail' ) : wc_placeholder_img_src( 'thumbnail' );
		$products[] = array(
			'id'    => $post->ID,
			'title' => get_the_title( $post->ID ),
			'url'   => get_permalink( $post->ID ),
			'price' => $product->get_price_html(),
			'thumb' => $thumb,
		);
	}

	wp_send_json_success( array(
		'products' => $products,
		'total'    => (int) $query->found_posts,
		'shop_url' => add_query_arg( 's', rawurlencode( $q ), get_permalink( wc_get_page_id( 'shop' ) ) ),
	) );
}

/**
 * Enqueue cafe-search.js và pass AJAX config.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_script(
			'cafe-search',
			get_stylesheet_directory_uri() . '/assets/js/cafe-search.js',
			array(),
			wp_get_theme()->get( 'Version' ),
			array( 'strategy' => 'defer', 'in_footer' => true )
		);
		wp_localize_script( 'cafe-search', 'cafeSearch', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'cafe_search_nonce' ),
			'more'    => __( 'Xem thêm %d sản phẩm', 'cafe-child' ),
			'label'   => __( 'TÌM KIẾM', 'cafe-child' ),
		) );
	},
	25
);
