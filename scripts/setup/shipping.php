<?php
/**
 * Tạo vùng ship "Việt Nam" dùng phương thức cafe_flat. Chạy lại an toàn.
 * Chạy: wp eval-file /scripts/setup/shipping.php
 */
$zone = null;
foreach ( WC_Shipping_Zones::get_zones() as $data ) {
	if ( 'Việt Nam' === $data['zone_name'] ) {
		$zone = new WC_Shipping_Zone( $data['id'] );
	}
}

if ( ! $zone ) {
	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( 'Việt Nam' );
	$zone->add_location( 'VN', 'country' );
	$zone->save();
}

foreach ( $zone->get_shipping_methods() as $method ) {
	if ( 'cafe_flat' !== $method->id ) {
		$zone->delete_shipping_method( $method->instance_id );
	}
}

$has_method = false;
foreach ( $zone->get_shipping_methods() as $method ) {
	$has_method = $has_method || 'cafe_flat' === $method->id;
}
if ( ! $has_method ) {
	$zone->add_shipping_method( 'cafe_flat' );
}

WP_CLI::success( 'Vùng ship Việt Nam dùng phương thức cafe_flat.' );
