<?php
use CafeCore\Capabilities;
use PHPUnit\Framework\TestCase;

final class CapabilitiesTest extends TestCase {
	public function test_staff_can_enter_admin_view_dashboard_and_edit_orders(): void {
		$caps = Capabilities::staff();
		foreach ( array( 'read', 'view_admin_dashboard', Capabilities::VIEW_DASHBOARD, 'edit_shop_orders', 'edit_others_shop_orders' ) as $cap ) {
			$this->assertTrue( $caps[ $cap ] ?? false, $cap );
		}
	}

	public function test_staff_has_no_forbidden_capability(): void {
		$caps = Capabilities::staff();
		foreach ( Capabilities::forbidden_for_staff() as $cap ) {
			$this->assertArrayNotHasKey( $cap, $caps, $cap );
		}
	}

	public function test_forbidden_list_covers_prices_and_settings(): void {
		$forbidden = Capabilities::forbidden_for_staff();
		foreach ( array( 'manage_options', 'manage_woocommerce', 'edit_products', 'delete_products' ) as $cap ) {
			$this->assertContains( $cap, $forbidden );
		}
	}
}
