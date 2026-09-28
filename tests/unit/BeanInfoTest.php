<?php
use CafeCore\BeanInfo\BeanInfo;
use PHPUnit\Framework\TestCase;

final class BeanInfoTest extends TestCase {
	public function test_no_values_gives_no_rows(): void {
		$this->assertSame( array(), BeanInfo::rows( array() ) );
	}

	public function test_keeps_non_empty_values_in_fixed_order_and_trims(): void {
		$this->assertSame(
			array( 'origin' => 'Cầu Đất', 'flavor' => 'Cam chanh' ),
			BeanInfo::rows( array( 'flavor' => ' Cam chanh ', 'origin' => 'Cầu Đất', 'altitude' => '' ) )
		);
	}

	public function test_invalid_roast_is_dropped(): void {
		$this->assertSame( array(), BeanInfo::rows( array( 'roast' => 'burnt' ) ) );
		$this->assertSame( array( 'roast' => 'dark' ), BeanInfo::rows( array( 'roast' => 'dark' ) ) );
	}

	public function test_sanitize_roast(): void {
		$this->assertSame( 'medium', BeanInfo::sanitize_roast( 'medium' ) );
		$this->assertSame( '', BeanInfo::sanitize_roast( 'x' ) );
	}

	public function test_meta_keys_are_private(): void {
		foreach ( BeanInfo::FIELDS as $meta_key ) {
			$this->assertStringStartsWith( '_cafe_', $meta_key );
		}
	}
}
