<?php
/**
 * Hàm hỗ trợ cho script seed (chạy trong WP-CLI).
 */

function cafe_seed_term( string $name, string $slug, string $taxonomy ): int {
	$term = get_term_by( 'slug', $slug, $taxonomy );
	if ( $term ) {
		return (int) $term->term_id;
	}
	$result = wp_insert_term( $name, $taxonomy, array( 'slug' => $slug ) );
	if ( is_wp_error( $result ) ) {
		WP_CLI::error( "{$taxonomy}/{$slug}: " . $result->get_error_message() );
	}
	return (int) $result['term_id'];
}

/**
 * Tạo thuộc tính toàn cục (pa_<slug>) và các giá trị của nó.
 *
 * @param array<string, string> $terms slug => tên.
 * @return string Tên taxonomy.
 */
function cafe_seed_attribute( string $name, string $slug, array $terms ): string {
	$taxonomy = wc_attribute_taxonomy_name( $slug );
	if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) {
		$result = wc_create_attribute( array( 'name' => $name, 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false ) );
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
	}
	// Taxonomy của thuộc tính mới chỉ được đăng ký ở request sau, nên đăng ký tạm cho request này.
	if ( ! taxonomy_exists( $taxonomy ) ) {
		register_taxonomy( $taxonomy, array( 'product', 'product_variation' ), array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
	}
	$order = 0;
	foreach ( $terms as $term_slug => $term_name ) {
		update_term_meta( cafe_seed_term( $term_name, $term_slug, $taxonomy ), 'order', $order++ );
	}
	return $taxonomy;
}

/**
 * @param string[] $term_slugs
 */
function cafe_seed_product_attribute( string $taxonomy, array $term_slugs ): WC_Product_Attribute {
	$attribute = new WC_Product_Attribute();
	$attribute->set_id( wc_attribute_taxonomy_id_by_name( $taxonomy ) );
	$attribute->set_name( $taxonomy );
	$attribute->set_options( array_map( static fn( string $slug ): int => (int) get_term_by( 'slug', $slug, $taxonomy )->term_id, $term_slugs ) );
	$attribute->set_visible( true );
	$attribute->set_variation( true );
	return $attribute;
}

function cafe_seed_center_text( $image, string $font, int $size, int $color, string $text, int $y ): void {
	$box = imagettfbbox( $size, 0, $font, $text );
	imagettftext( $image, $size, 0, (int) ( ( imagesx( $image ) - ( $box[2] - $box[0] ) ) / 2 ), $y, $color, $font, $text );
}

/**
 * Vẽ ảnh placeholder "túi cà phê kraft" 800×800 rồi đưa vào thư viện Media.
 */
function cafe_seed_image( string $title, string $subtitle, string $alt ): int {
	$font  = __DIR__ . '/../assets/fonts/BeVietnamPro-Bold.ttf';
	$image = imagecreatetruecolor( 800, 800 );
	$cream = imagecolorallocate( $image, 0xF5, 0xED, 0xE0 );
	$kraft = imagecolorallocate( $image, 0xD9, 0xC3, 0xA5 );
	$earth = imagecolorallocate( $image, 0x8B, 0x5A, 0x3C );
	$brown = imagecolorallocate( $image, 0x4A, 0x2C, 0x1D );

	imagefilledrectangle( $image, 0, 0, 800, 800, $cream );
	imagefilledrectangle( $image, 190, 130, 610, 710, $kraft ); // thân túi
	imagefilledrectangle( $image, 190, 130, 610, 185, $earth ); // mép dán
	imagefilledrectangle( $image, 230, 330, 570, 560, $cream ); // nhãn
	imagerectangle( $image, 240, 340, 560, 550, $brown );
	cafe_seed_center_text( $image, $font, 30, $brown, $title, 435 );
	cafe_seed_center_text( $image, $font, 18, $earth, $subtitle, 495 );

	$tmp = tempnam( sys_get_temp_dir(), 'cafe' );
	imagejpeg( $image, $tmp, 88 );
	imagedestroy( $image );

	$upload = wp_upload_bits( sanitize_title( $alt ) . '.jpg', null, (string) file_get_contents( $tmp ) );
	unlink( $tmp );
	if ( $upload['error'] ) {
		WP_CLI::error( $upload['error'] );
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	$attachment_id = wp_insert_attachment( array( 'post_mime_type' => 'image/jpeg', 'post_title' => $alt, 'post_status' => 'inherit' ), $upload['file'] );
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
	return (int) $attachment_id;
}

function cafe_seed_page( string $slug, string $title, string $content ): int {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		return (int) $page->ID;
	}
	$page_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => $content ), true );
	if ( is_wp_error( $page_id ) ) {
		WP_CLI::error( $page_id->get_error_message() );
	}
	WP_CLI::log( "Tạo trang {$title} (/{$slug}/)" );
	return (int) $page_id;
}

function cafe_seed_paragraphs( string ...$paragraphs ): string {
	return implode( "\n\n", array_map( static fn( string $p ): string => "<!-- wp:paragraph -->\n<p>{$p}</p>\n<!-- /wp:paragraph -->", $paragraphs ) );
}
