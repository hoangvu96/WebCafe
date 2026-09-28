<?php
namespace CafeCore\Dashboard;

defined( 'ABSPATH' ) || exit;

/**
 * Đọc đơn hàng và tồn kho qua API WooCommerce.
 */
final class StoreData {
	public const COUNTED_STATUSES = array( 'processing', 'completed' );

	/**
	 * Đơn được tính doanh thu, dạng dữ liệu cho Metrics::summarize().
	 */
	public static function recent_orders( \DateTimeImmutable $since ): array {
		$orders = wc_get_orders(
			array(
				'type'         => 'shop_order',
				'status'       => self::COUNTED_STATUSES,
				'date_created' => '>=' . $since->getTimestamp(),
				'limit'        => -1,
			)
		);

		$tz   = wp_timezone();
		$rows = array();
		foreach ( $orders as $order ) {
			$created = $order->get_date_created();
			if ( ! $created ) {
				continue;
			}
			$items = array();
			foreach ( $order->get_items() as $item ) {
				$product_id = (int) $item->get_product_id();
				$items[]    = array(
					'product_id' => $product_id,
					'name'       => $product_id ? get_the_title( $product_id ) : $item->get_name(),
					'qty'        => (int) $item->get_quantity(),
				);
			}
			$rows[] = array(
				'total'   => (float) $order->get_total(),
				'created' => ( new \DateTimeImmutable( '@' . $created->getTimestamp() ) )->setTimezone( $tz ),
				'items'   => $items,
			);
		}
		return $rows;
	}

	/**
	 * @return \WC_Order[] Đơn Đang xử lý, mới nhất trước.
	 */
	public static function processing_orders( int $limit ): array {
		return wc_get_orders( array( 'type' => 'shop_order', 'status' => 'processing', 'limit' => $limit, 'orderby' => 'date', 'order' => 'DESC' ) );
	}

	public static function processing_count(): int {
		$result = wc_get_orders(
			array(
				'type'     => 'shop_order',
				'status'   => 'processing',
				'paginate' => true,
				'limit'    => 1,
			)
		);
		return (int) $result->total;
	}

	/**
	 * Sản phẩm/biến thể có quản lý tồn kho, tồn ít nhất trước.
	 *
	 * @param int|null $max_stock Chỉ lấy tồn ≤ giá trị này; null = tất cả.
	 * @return \WC_Product[]
	 */
	public static function stock_products( ?int $max_stock, int $limit ): array {
		$meta_query = array( array( 'key' => '_manage_stock', 'value' => 'yes' ) );
		if ( null !== $max_stock ) {
			$meta_query[] = array( 'key' => '_stock', 'value' => $max_stock, 'compare' => '<=', 'type' => 'NUMERIC' );
		}

		$query = new \WP_Query(
			array(
				'post_type'      => array( 'product', 'product_variation' ),
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_key'       => '_stock', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
			)
		);

		return array_values( array_filter( array_map( 'wc_get_product', $query->posts ) ) );
	}
}
