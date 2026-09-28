<?php
namespace CafeCore\Dashboard;

use CafeCore\Capabilities;

defined( 'ABSPATH' ) || exit;

final class DashboardPage {
	public const SLUG           = 'cafe-dashboard';
	public const INVENTORY_SLUG = 'cafe-inventory';
	public const CACHE_KEY      = 'cafe_dashboard_metrics';
	public const PENDING_LIMIT  = 20;
	public const NONCE_ACTION   = 'cafe_mark_delivered';

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'woocommerce_new_order', array( self::class, 'flush_cache' ) );
		add_action( 'woocommerce_order_status_changed', array( self::class, 'flush_cache' ) );
		add_action( 'woocommerce_update_order', array( self::class, 'flush_cache' ) );
		add_action( 'woocommerce_trash_order', array( self::class, 'flush_cache' ) );
		add_action( 'woocommerce_delete_order', array( self::class, 'flush_cache' ) );
		add_action( 'woocommerce_order_refunded', array( self::class, 'flush_cache' ) );
		add_filter( 'login_redirect', array( self::class, 'login_redirect' ), 10, 3 );
		add_action( 'wp_ajax_cafe_mark_delivered', array( self::class, 'mark_delivered' ) );
	}

	public static function menu(): void {
		$title = __( 'Tổng quan cửa hàng', 'cafe-core' );
		add_menu_page( $title, __( 'Tổng quan', 'cafe-core' ), Capabilities::VIEW_DASHBOARD, self::SLUG, array( self::class, 'render' ), 'dashicons-store', 2 );
		add_submenu_page( self::SLUG, $title, __( 'Tổng quan', 'cafe-core' ), Capabilities::VIEW_DASHBOARD, self::SLUG, array( self::class, 'render' ) );
		add_submenu_page( self::SLUG, __( 'Tồn kho', 'cafe-core' ), __( 'Tồn kho', 'cafe-core' ), Capabilities::VIEW_DASHBOARD, self::INVENTORY_SLUG, array( self::class, 'render_inventory' ) );
	}

	/**
	 * Khoá cache theo ngày hiện tại (giờ địa phương) để tự làm mới lúc qua ngày mới,
	 * không phải chờ cache 5 phút hết hạn hoặc có đơn hàng thay đổi.
	 */
	private static function cache_key(): string {
		return self::CACHE_KEY . '_' . wp_date( 'Ymd' );
	}

	/**
	 * Số liệu Tổng quan, cache 5 phút; xoá cache khi có đơn mới hoặc đơn đổi trạng thái.
	 */
	public static function metrics(): array {
		$cached = get_transient( self::cache_key() );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$now   = new \DateTimeImmutable( 'now', wp_timezone() );
		$today = $now->setTime( 0, 0 );
		$since = min( $today->modify( '-' . ( Metrics::CHART_DAYS - 1 ) . ' days' ), $today->modify( 'first day of this month' ) );

		$metrics = Metrics::summarize( StoreData::recent_orders( $since ), $now );
		set_transient( self::cache_key(), $metrics, 5 * MINUTE_IN_SECONDS );
		return $metrics;
	}

	public static function flush_cache(): void {
		delete_transient( self::cache_key() );
	}

	private static function current_page(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để chọn asset.
		return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	}

	public static function assets(): void {
		$page = self::current_page();
		if ( ! in_array( $page, array( self::SLUG, self::INVENTORY_SLUG ), true ) ) {
			return;
		}

		$base = plugin_dir_url( __FILE__ ) . 'assets/';
		wp_enqueue_style( 'cafe-dashboard', $base . 'dashboard.css', array(), CAFE_CORE_VERSION );
		if ( self::SLUG !== $page ) {
			return;
		}

		wp_enqueue_script( 'cafe-chartjs', $base . 'vendor/chart.umd.js', array(), '4.4.4', true );
		wp_enqueue_script( 'cafe-dashboard', $base . 'dashboard.js', array( 'cafe-chartjs' ), CAFE_CORE_VERSION, true );

		$daily  = self::metrics()['daily'];
		$config = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
			'chart'   => array(
				'labels' => array_map( static fn( string $day ): string => substr( $day, 8, 2 ) . '/' . substr( $day, 5, 2 ), array_keys( $daily ) ),
				'values' => array_values( $daily ),
			),
			'i18n'    => array(
				'revenue' => __( 'Doanh thu', 'cafe-core' ),
				'error'   => __( 'Không cập nhật được đơn hàng. Vui lòng tải lại trang và thử lại.', 'cafe-core' ),
			),
		);
		wp_add_inline_script( 'cafe-dashboard', 'window.cafeDashboard = ' . wp_json_encode( $config ) . ';', 'before' );
	}

	public static function mark_delivered(): void {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.', 'cafe-core' ) ), 403 );
		}
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_send_json_error( array( 'message' => __( 'Bạn không có quyền cập nhật đơn hàng.', 'cafe-core' ) ), 403 );
		}

		$order = wc_get_order( absint( $_POST['order_id'] ?? 0 ) );
		if ( ! $order instanceof \WC_Order || ! $order->has_status( 'processing' ) ) {
			wp_send_json_error( array( 'message' => __( 'Đơn hàng không tồn tại hoặc không còn ở trạng thái Đang xử lý.', 'cafe-core' ) ), 400 );
		}

		$order->update_status( 'completed', __( 'Nhân viên đánh dấu đã giao từ trang Tổng quan.', 'cafe-core' ), true );
		wp_send_json_success( array( 'order_id' => $order->get_id() ) );
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
