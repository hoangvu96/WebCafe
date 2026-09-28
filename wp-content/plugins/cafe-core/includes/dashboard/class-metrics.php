<?php
namespace CafeCore\Dashboard;

defined( 'ABSPATH' ) || exit;

/**
 * Tính số liệu trang Tổng quan từ danh sách đơn đã lọc trạng thái. Không gọi WordPress.
 */
final class Metrics {
	public const CHART_DAYS = 30;
	public const TOP_LIMIT  = 5;

	/**
	 * @param array<int, array{total: float, created: \DateTimeImmutable, items: array<int, array{product_id: int, name: string, qty: int}>}> $orders
	 */
	public static function summarize( array $orders, \DateTimeImmutable $now ): array {
		$tz          = $now->getTimezone();
		$today       = $now->setTime( 0, 0 );
		$week_start  = $today->modify( '-6 days' );
		$month_start = $today->modify( 'first day of this month' );
		$chart_start = $today->modify( '-' . ( self::CHART_DAYS - 1 ) . ' days' );

		$daily = array();
		for ( $i = 0; $i < self::CHART_DAYS; $i++ ) {
			$daily[ $chart_start->modify( "+{$i} days" )->format( 'Y-m-d' ) ] = 0.0;
		}

		$result = array(
			'revenue_today' => 0.0,
			'revenue_7d'    => 0.0,
			'revenue_month' => 0.0,
			'orders_today'  => 0,
		);
		$top    = array();

		foreach ( $orders as $order ) {
			$created = $order['created']->setTimezone( $tz );
			$total   = (float) $order['total'];

			if ( $created >= $today ) {
				$result['revenue_today'] += $total;
				++$result['orders_today'];
			}
			if ( $created >= $week_start ) {
				$result['revenue_7d'] += $total;
			}
			if ( $created >= $month_start ) {
				$result['revenue_month'] += $total;
			}

			$day = $created->format( 'Y-m-d' );
			if ( isset( $daily[ $day ] ) ) {
				$daily[ $day ] += $total;
			}

			if ( $created >= $chart_start ) {
				foreach ( $order['items'] as $item ) {
					$id = (int) $item['product_id'];
					if ( ! isset( $top[ $id ] ) ) {
						$top[ $id ] = array( 'product_id' => $id, 'name' => (string) $item['name'], 'qty' => 0 );
					}
					$top[ $id ]['qty'] += (int) $item['qty'];
				}
			}
		}

		// Số lượng giảm dần, cùng số lượng thì theo tên A→Z.
		usort( $top, static fn( array $a, array $b ): int => array( $b['qty'], $a['name'] ) <=> array( $a['qty'], $b['name'] ) );

		$result['daily']        = $daily;
		$result['top_products'] = array_slice( $top, 0, self::TOP_LIMIT );
		return $result;
	}
}
