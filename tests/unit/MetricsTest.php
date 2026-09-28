<?php
use CafeCore\Dashboard\Metrics;
use PHPUnit\Framework\TestCase;

final class MetricsTest extends TestCase {
	private \DateTimeZone $tz;

	protected function setUp(): void {
		$this->tz = new \DateTimeZone( 'Asia/Ho_Chi_Minh' );
	}

	private function now(): \DateTimeImmutable {
		return new \DateTimeImmutable( '2026-09-28 15:00', $this->tz );
	}

	private function order( string $created, float $total, array $items = array() ): array {
		return array( 'total' => $total, 'created' => new \DateTimeImmutable( $created, $this->tz ), 'items' => $items );
	}

	private function item( int $id, string $name, int $qty ): array {
		return array( 'product_id' => $id, 'name' => $name, 'qty' => $qty );
	}

	public function test_revenue_windows_and_orders_today(): void {
		$m = Metrics::summarize(
			array(
				$this->order( '2026-09-28 10:00', 100000 ),
				$this->order( '2026-09-27 09:00', 200000 ),
				$this->order( '2026-09-22 08:00', 50000 ),  // ngày đầu của 7 ngày
				$this->order( '2026-09-21 23:59', 70000 ),  // ngoài 7 ngày, trong tháng
				$this->order( '2026-09-01 00:00', 30000 ),  // ngày đầu tháng
				$this->order( '2026-08-31 12:00', 40000 ),  // tháng trước
			),
			$this->now()
		);

		$this->assertSame( 100000.0, $m['revenue_today'] );
		$this->assertSame( 1, $m['orders_today'] );
		$this->assertSame( 350000.0, $m['revenue_7d'] );
		$this->assertSame( 450000.0, $m['revenue_month'] );
	}

	public function test_daily_chart_covers_30_days_ending_today(): void {
		$m = Metrics::summarize(
			array( $this->order( '2026-08-31 12:00', 40000 ), $this->order( '2026-08-29 12:00', 99000 ) ),
			$this->now()
		);

		$this->assertCount( 30, $m['daily'] );
		$this->assertSame( '2026-08-30', array_key_first( $m['daily'] ) );
		$this->assertSame( '2026-09-28', array_key_last( $m['daily'] ) );
		$this->assertSame( 40000.0, $m['daily']['2026-08-31'] );
		$this->assertArrayNotHasKey( '2026-08-29', $m['daily'] );
	}

	public function test_order_times_are_read_in_shop_timezone(): void {
		// 18:00 UTC ngày 27 = 01:00 ngày 28 giờ Việt Nam.
		$created = new \DateTimeImmutable( '2026-09-27 18:00', new \DateTimeZone( 'UTC' ) );
		$m       = Metrics::summarize( array( array( 'total' => 80000, 'created' => $created, 'items' => array() ) ), $this->now() );

		$this->assertSame( 80000.0, $m['revenue_today'] );
		$this->assertSame( 80000.0, $m['daily']['2026-09-28'] );
	}

	public function test_top_products_sorted_by_quantity_then_name_and_limited_to_five(): void {
		$m = Metrics::summarize(
			array(
				$this->order( '2026-09-28 09:00', 1, array( $this->item( 1, 'A', 1 ), $this->item( 2, 'B', 5 ) ) ),
				$this->order( '2026-09-27 09:00', 1, array( $this->item( 1, 'A', 3 ), $this->item( 3, 'C', 2 ), $this->item( 4, 'D', 2 ), $this->item( 6, 'F', 1 ), $this->item( 5, 'E', 1 ) ) ),
			),
			$this->now()
		);

		$this->assertSame( array( 2, 1, 3, 4, 5 ), array_column( $m['top_products'], 'product_id' ) );
		$this->assertSame( 4, $m['top_products'][1]['qty'] );
	}

	public function test_no_orders(): void {
		$m = Metrics::summarize( array(), $this->now() );

		$this->assertSame( 0.0, $m['revenue_today'] );
		$this->assertSame( 0, $m['orders_today'] );
		$this->assertSame( array(), $m['top_products'] );
		$this->assertSame( 0.0, array_sum( $m['daily'] ) );
	}
}
