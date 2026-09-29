<?php
namespace CafeCore\AdminRoles;

use CafeCore\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Dọn dẹp giao diện wp-admin theo vai trò.
 *
 * Quyền truy cập thật vẫn do capability quyết định; class này chỉ ẩn menu
 * để giao diện gọn hơn — không phải rào bảo mật.
 *
 * Admin    : ẩn Posts, Comments, Tools và một số menu Kadence/plugin ít dùng.
 * cafe_staff: chỉ giữ Cafe Dashboard và Đơn hàng WooCommerce; ẩn hết còn lại.
 */
final class AdminCleanup {

	/** Menu ẩn với MỌI người kể cả admin (hoàn toàn không dùng cho site này). */
	private const ALWAYS_HIDDEN = array(
		'edit.php',          // Bài viết (blog)
		'edit-comments.php', // Bình luận
		'tools.php',         // Công cụ WP
	);

	/** Menu ẩn thêm với nhân viên (cafe_staff). */
	private const STAFF_HIDDEN = array(
		'index.php',                    // Dashboard WP (thay bằng Cafe Dashboard)
		'upload.php',                   // Thư viện media
		'edit.php?post_type=page',      // Trang
		'edit.php?post_type=product',   // Sản phẩm
		'themes.php',                   // Giao diện
		'plugins.php',                  // Plugin
		'users.php',                    // Người dùng
		'options-general.php',          // Cài đặt WP
		'woocommerce-marketing',        // Marketing WC
		'wc-admin&path=/extensions',    // Extensions WC
		'kadence-blocks',               // Kadence Blocks
		'kadence',                      // Kadence (menu chính nếu có)
		'kadence-starter-templates',    // Kadence Templates
	);

	/** Submenu WooCommerce GIỮ LẠI cho nhân viên; ẩn hết các submenu khác. */
	private const STAFF_WC_KEEP_SUBMENUS = array(
		'wc-orders',                         // Đơn hàng (HPOS)
		'edit.php?post_type=shop_order',     // Đơn hàng (legacy, phòng khi tắt HPOS)
	);

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'hide_menus' ), 999 );
		add_action( 'admin_bar_menu', array( self::class, 'clean_admin_bar' ), 999 );
		add_action( 'admin_init', array( self::class, 'redirect_dashboard' ) );
		add_filter( 'screen_options_show_screen', array( self::class, 'hide_screen_options' ) );
	}

	// -------------------------------------------------------------------------
	// Menu sidebar
	// -------------------------------------------------------------------------

	public static function hide_menus(): void {
		// Admin: chỉ ẩn các mục hoàn toàn không dùng.
		foreach ( self::ALWAYS_HIDDEN as $slug ) {
			remove_menu_page( $slug );
		}

		if ( self::is_staff() ) {
			self::hide_staff_menus();
		}
	}

	private static function hide_staff_menus(): void {
		foreach ( self::STAFF_HIDDEN as $slug ) {
			remove_menu_page( $slug );
		}

		// Dọn submenu WooCommerce: chỉ giữ Đơn hàng.
		global $submenu;
		if ( isset( $submenu['woocommerce'] ) ) {
			foreach ( $submenu['woocommerce'] as $idx => $item ) {
				$item_slug = $item[2] ?? '';
				if ( ! in_array( $item_slug, self::STAFF_WC_KEEP_SUBMENUS, true ) ) {
					unset( $submenu['woocommerce'][ $idx ] );
				}
			}
		}

		// Dọn submenu Settings: chỉ giữ Cài đặt Cafe.
		if ( isset( $submenu['options-general.php'] ) ) {
			remove_menu_page( 'options-general.php' );
		}
	}

	// -------------------------------------------------------------------------
	// Admin bar (thanh trên cùng)
	// -------------------------------------------------------------------------

	public static function clean_admin_bar( \WP_Admin_Bar $wp_admin_bar ): void {
		// Ẩn với tất cả (không dùng trong workflow này).
		$always_remove = array(
			'new-content',   // nút "+ Tạo mới"
			'comments',      // biểu tượng bình luận
			'wp-logo',       // logo WordPress
			'customize',     // nút Tùy chỉnh
		);
		foreach ( $always_remove as $id ) {
			$wp_admin_bar->remove_node( $id );
		}

		// Ẩn thêm với nhân viên.
		if ( self::is_staff() ) {
			$staff_remove = array(
				'updates',      // thông báo cập nhật
				'search',       // tìm kiếm wp-admin
				'site-name',    // tên site (link về front-end)
				'view-site',    // Xem trang
			);
			foreach ( $staff_remove as $id ) {
				$wp_admin_bar->remove_node( $id );
			}
		}
	}

	// -------------------------------------------------------------------------
	// Chuyển hướng nhân viên về Cafe Dashboard khi vào /wp-admin/
	// -------------------------------------------------------------------------

	public static function redirect_dashboard(): void {
		if ( ! self::is_staff() ) {
			return;
		}

		global $pagenow;
		if ( 'index.php' === $pagenow && ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( admin_url( 'admin.php?page=cafe-dashboard' ) );
			exit;
		}
	}

	// -------------------------------------------------------------------------
	// Ẩn tab "Screen Options" với nhân viên (không cần tùy chỉnh bố cục).
	// -------------------------------------------------------------------------

	public static function hide_screen_options( bool $show ): bool {
		return self::is_staff() ? false : $show;
	}

	// -------------------------------------------------------------------------
	// Helper
	// -------------------------------------------------------------------------

	private static function is_staff(): bool {
		return current_user_can( Capabilities::ROLE_STAFF )
			&& ! current_user_can( 'manage_options' );
	}
}
