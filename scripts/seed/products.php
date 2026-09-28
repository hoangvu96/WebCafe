<?php
/**
 * Tạo danh mục, thuộc tính và sản phẩm mẫu. Chạy lại an toàn (bỏ qua SKU đã có).
 * Chạy: wp eval-file /scripts/seed/products.php
 */
use CafeCore\BeanInfo\BeanInfo;

require_once __DIR__ . '/helpers.php';
$data = require __DIR__ . '/data.php';

// Xoá sản phẩm mẫu của template (SKU không bắt đầu bằng CF-).
foreach ( wc_get_products( array( 'limit' => -1, 'status' => array( 'publish', 'draft', 'private', 'pending' ) ) ) as $existing ) {
	if ( 0 !== strpos( (string) $existing->get_sku(), 'CF-' ) ) {
		WP_CLI::log( 'Xoá sản phẩm của template: ' . $existing->get_name() );
		$existing->delete( true );
	}
}

$category_ids = array();
foreach ( $data['categories'] as $slug => $name ) {
	$category_ids[ $slug ] = cafe_seed_term( $name, $slug, 'product_cat' );
}

$weight_taxonomy = cafe_seed_attribute( 'Khối lượng', 'khoi-luong', $data['weights'] );
$grind_taxonomy  = cafe_seed_attribute( 'Dạng xay', 'dang-xay', $data['grinds'] );

foreach ( $data['products'] as $item ) {
	if ( wc_get_product_id_by_sku( $item['sku'] ) ) {
		WP_CLI::log( "Đã có {$item['sku']}, bỏ qua." );
		continue;
	}

	$product = 'variable' === $item['type'] ? new WC_Product_Variable() : new WC_Product_Simple();
	$product->set_name( $item['name'] );
	$product->set_slug( $item['slug'] );
	$product->set_sku( $item['sku'] );
	$product->set_status( 'publish' );
	$product->set_short_description( $item['short'] );
	$product->set_description( $item['description'] );
	$product->set_category_ids( array( $category_ids[ $item['category'] ] ) );
	$product->set_image_id( cafe_seed_image( $item['label'][0], $item['label'][1], $item['name'] ) );
	foreach ( BeanInfo::FIELDS as $field => $meta_key ) {
		$product->update_meta_data( $meta_key, $item['bean'][ $field ] ?? '' );
	}

	if ( 'simple' === $item['type'] ) {
		$product->set_regular_price( (string) $item['price'] );
		$product->set_manage_stock( true );
		$product->set_stock_quantity( $item['stock'] );
		$product->save();
		WP_CLI::log( "Tạo {$item['name']}" );
		continue;
	}

	$product->set_attributes(
		array(
			cafe_seed_product_attribute( $weight_taxonomy, array_keys( $item['prices'] ) ),
			cafe_seed_product_attribute( $grind_taxonomy, array_keys( $data['grinds'] ) ),
		)
	);
	$product->set_default_attributes( array( $weight_taxonomy => '250g', $grind_taxonomy => 'xay-phin' ) );
	$product_id = $product->save();

	foreach ( $item['prices'] as $weight => $price ) {
		foreach ( array_keys( $data['grinds'] ) as $grind ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product_id );
			$variation->set_attributes( array( $weight_taxonomy => $weight, $grind_taxonomy => $grind ) );
			$variation->set_regular_price( (string) $price );
			$variation->set_manage_stock( true );
			$variation->set_stock_quantity( $item['stock'][ $weight ] ?? 20 );
			$variation->set_weight( (string) $data['weight_kg'][ $weight ] );
			$variation->set_status( 'publish' );
			$variation->save();
		}
	}
	WC_Product_Variable::sync( $product_id );
	WP_CLI::log( "Tạo {$item['name']} với " . count( $item['prices'] ) * count( $data['grinds'] ) . ' biến thể' );
}

WP_CLI::success( 'Sản phẩm mẫu đã sẵn sàng.' );
