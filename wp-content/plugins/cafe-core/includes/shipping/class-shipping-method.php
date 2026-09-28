<?php
namespace CafeCore\Shipping;

use CafeCore\Settings;

defined( 'ABSPATH' ) || exit;

final class ShippingMethod extends \WC_Shipping_Method {
	public const ID = 'cafe_flat';

	/**
	 * @param int $instance_id Id phương thức trong vùng ship.
	 */
	public function __construct( $instance_id = 0 ) {
		parent::__construct( $instance_id );
		$this->id                 = self::ID;
		$this->method_title       = __( 'Phí ship cố định (Cafe)', 'cafe-core' );
		$this->method_description = __( 'Phí cố định, miễn phí khi đơn đạt ngưỡng. Cấu hình tại WooCommerce → Cài đặt Cafe.', 'cafe-core' );
		$this->supports           = array( 'shipping-zones' );
		$this->enabled            = 'yes';
		$this->title              = __( 'Giao hàng tận nơi', 'cafe-core' );
	}

	/**
	 * @param array $package Gói hàng WooCommerce.
	 */
	public function calculate_shipping( $package = array() ): void {
		$settings = Settings::all();
		$cost     = ShippingCalculator::cost(
			(float) ( $package['contents_cost'] ?? 0 ),
			(int) $settings['shipping_fee'],
			(int) $settings['free_threshold']
		);

		$this->add_rate(
			array(
				'id'      => $this->get_rate_id(),
				'label'   => 0 === $cost ? __( 'Miễn phí vận chuyển', 'cafe-core' ) : __( 'Phí vận chuyển', 'cafe-core' ),
				'cost'    => $cost,
				'package' => $package,
			)
		);
	}
}
