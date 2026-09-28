<?php
use CafeCore\Shipping\ShippingCalculator;
use PHPUnit\Framework\TestCase;

final class ShippingCalculatorTest extends TestCase {
	public function test_below_threshold_pays_fee(): void {
		$this->assertSame( 30000, ShippingCalculator::cost( 95000, 30000, 500000 ) );
	}

	public function test_at_threshold_is_free(): void {
		$this->assertSame( 0, ShippingCalculator::cost( 500000, 30000, 500000 ) );
	}

	public function test_above_threshold_is_free(): void {
		$this->assertSame( 0, ShippingCalculator::cost( 570000, 30000, 500000 ) );
	}

	public function test_zero_threshold_disables_free_shipping(): void {
		$this->assertSame( 30000, ShippingCalculator::cost( 10000000, 30000, 0 ) );
	}

	public function test_negative_fee_is_clamped_to_zero(): void {
		$this->assertSame( 0, ShippingCalculator::cost( 1000, -5000, 500000 ) );
	}
}
