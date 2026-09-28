<?php
namespace CafeCore\Dashboard;

use CafeCore\Capabilities;

defined( 'ABSPATH' ) || exit;

final class DashboardPage {
	public const SLUG           = 'cafe-dashboard';
	public const INVENTORY_SLUG = 'cafe-inventory';
	public const CACHE_KEY      = 'cafe_dashboard_metrics';
	public const PENDING_LIMIT  = 20;

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'woocommerce_new_order', array( self::class, 'flush_cache' ) );
		add_action( 'woocommerce_order_status_changed', array( self::class, 'flush_cache' ) );
		add_filter( 'login_redirect', array( self::class, 'login_redirect' ), 10, 3 );
	}

	public static function menu(): void {
		$title = __( 'Tổng quan cửa hàng', 'cafe-core' );
		add_menu_page( $title, __( 'Tổng quan', 'cafe-core' ), Capabilities::VIEW_DASHBOARD, self::SLUG, array( self::class, 'render' ), 'dashicons-store', 2 );
		add_submenu_page( self::SLUG, $title, __( 'Tổng quan', 'cafe-core' ), Capabilities::VIEW_DASHBOARD, self::SLUG, array( self::class, 'render' ) );
		add_submenu_page( self::SLUG, __( 'Tồn kho', 'cafe-core' ), __( 'Tồn kho', 'cafe-core' ), Capabilities::VIEW_DASHBOARD, self::INVENTORY_SLUG, array( self::class, 'render_inventory' ) );
	}

	/**
	 * Số liệu Tổng quan, cache 5 phút; xoá cache khi có đơn mới hoặc đơn đổi trạng thái.
	 */
	public static function metrics(): array {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$now   = new \DateTimeImmutable( 'now', wp_timezone() );
		$today = $now->setTime( 0, 0 );
		$since = min( $today->modify( '-' . ( Metrics::CHART_DAYS - 1 ) . ' days' ), $today->modify( 'first day of this month' ) );

		$metrics = Metrics::summarize( StoreData::recent_orders( $since ), $now );
		set_transient( self::CACHE_KEY, $metrics, 5 * MINUTE_IN_SECONDS );
		return $metrics;
	}

	public static function flush_cache(): void {
		delete_transient( self::CACHE_KEY );
	}

	private static function current_page(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để chọn asset.
		return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	}

	public static function assets(): void {
		if ( ! in_array( self::current_page(), array( self::SLUG, self::INVENTORY_SLUG ), true ) ) {
			return;
		}
		wp_enqueue_style( 'cafe-dashboard', plugin_dir_url( __FILE__ ) . 'assets/dashboard.css', array(), CAFE_CORE_VERSION );
	}

	public static function render(): void {
		$metrics       = self::metrics();
		$pending       = StoreData::processing_orders( self::PENDING_LIMIT );
		$pending_count = StoreData::processing_count();
		$low_stock     = StoreData::stock_products( (int) get_option( 'woocommerce_notify_low_stock_amount', 5 ), 20 );
		include __DIR__ . '/views/dashboard.php';
	}

	public static function render_inventory(): void {
		$products  = StoreData::stock_products( null, 500 );
		$threshold = (int) get_option( 'woocommerce_notify_low_stock_amount', 5 );
		include __DIR__ . '/views/inventory.php';
	}

	/**
	 * Sau khi đăng nhập ở wp-login.php mà không yêu cầu trang cụ thể thì vào thẳng Tổng quan.
	 *
	 * @param string            $redirect  URL WordPress định chuyển tới.
	 * @param string            $requested URL được yêu cầu (redirect_to).
	 * @param \WP_User|\WP_Error $user      Người dùng.
	 */
	public static function login_redirect( $redirect, $requested, $user ) {
		if ( ! $user instanceof \WP_User || ! user_can( $user, Capabilities::VIEW_DASHBOARD ) ) {
			return $redirect;
		}
		if ( '' !== $requested && admin_url() !== $requested ) {
			return $redirect;
		}
		return admin_url( 'admin.php?page=' . self::SLUG );
	}
}
