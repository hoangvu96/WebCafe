<?php
/**
 * Plugin Name:       Cafe Core
 * Description:       Chức năng nghiệp vụ cho website bán cà phê: thông tin hạt, thanh toán Việt Nam, phí ship, trang tổng quan, phân quyền nhân viên.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Text Domain:       cafe-core
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'CAFE_CORE_VERSION', '0.1.0' );
define( 'CAFE_CORE_FILE', __FILE__ );
define( 'CAFE_CORE_DIR', plugin_dir_path( __FILE__ ) );

/** Các module nghiệp vụ, mỗi module nằm trong includes/<tên>/module.php. */
const CAFE_CORE_MODULES = array( 'checkout-vn', 'shipping', 'bean-info', 'admin-roles', 'dashboard' );

require_once CAFE_CORE_DIR . 'includes/class-settings.php';
require_once CAFE_CORE_DIR . 'includes/class-settings-page.php';
require_once CAFE_CORE_DIR . 'includes/class-capabilities.php';

add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			// Đã kiểm tra tương thích HPOS (bảng đơn hàng riêng); trang thanh toán chỉ dùng shortcode/classic nên không hỗ trợ block Cart & Checkout.
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CAFE_CORE_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', CAFE_CORE_FILE, false );
		}
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain( 'cafe-core', false, dirname( plugin_basename( CAFE_CORE_FILE ) ) . '/languages' );

		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		\CafeCore\SettingsPage::register();

		foreach ( CAFE_CORE_MODULES as $module ) {
			require_once CAFE_CORE_DIR . "includes/{$module}/module.php";
		}
	}
);
