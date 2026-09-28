<?php
use CafeCore\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase {
	public function test_defaults_when_nothing_stored(): void {
		$this->assertSame(
			array( 'shipping_fee' => 30000, 'free_threshold' => 500000, 'shop_phone' => '' ),
			Settings::with_defaults( false )
		);
	}

	public function test_stored_values_override_defaults_and_unknown_keys_are_dropped(): void {
		$this->assertSame(
			array( 'shipping_fee' => 25000, 'free_threshold' => 500000, 'shop_phone' => '0900' ),
			Settings::with_defaults( array( 'shipping_fee' => 25000, 'shop_phone' => '0900', 'foo' => 1 ) )
		);
	}

	public function test_sanitize_casts_clamps_and_strips(): void {
		$this->assertSame(
			array( 'shipping_fee' => 0, 'free_threshold' => 400000, 'shop_phone' => '0900 000 000' ),
			Settings::sanitize( array( 'shipping_fee' => '-5', 'free_threshold' => '400000', 'shop_phone' => '0900 000 000<script>' ) )
		);
	}

	public function test_sanitize_uses_defaults_for_missing_keys(): void {
		$this->assertSame( Settings::DEFAULTS, Settings::sanitize( null ) );
	}
}
