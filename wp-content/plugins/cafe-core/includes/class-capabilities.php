<?php
namespace CafeCore;

defined( 'ABSPATH' ) || exit;

/**
 * Capability và vai trò dùng chung giữa các module.
 */
final class Capabilities {
	public const VIEW_DASHBOARD = 'cafe_view_dashboard';
	public const ROLE_STAFF     = 'cafe_staff';

	/**
	 * Quyền của vai trò Nhân viên bán hàng.
	 *
	 * @return array<string, bool>
	 */
	public static function staff(): array {
		return array(
			'read'                       => true,
			'view_admin_dashboard'       => true, // WooCommerce chặn vào wp-admin nếu thiếu quyền này.
			self::VIEW_DASHBOARD         => true,
			'edit_shop_orders'           => true,
			'edit_others_shop_orders'    => true,
			'edit_published_shop_orders' => true,
			'edit_private_shop_orders'   => true,
			'read_shop_order'            => true,
			'read_private_shop_orders'   => true,
		);
	}

	/**
	 * Quyền nhân viên không được có (sửa giá/sản phẩm, cài đặt, xoá đơn, báo cáo).
	 *
	 * @return string[]
	 */
	public static function forbidden_for_staff(): array {
		return array(
			'manage_options',
			'manage_woocommerce',
			'edit_products',
			'edit_others_products',
			'edit_published_products',
			'publish_products',
			'delete_products',
			'delete_shop_orders',
			'view_woocommerce_reports',
		);
	}
}
