<?php
namespace CafeCore\AdminRoles;

defined( 'ABSPATH' ) || exit;

/**
 * Ẩn menu không dùng với người không phải quản trị viên. Quyền thật vẫn do capability quyết định.
 */
final class AdminCleanup {
	private const HIDDEN_MENUS = array( 'edit.php', 'edit-comments.php', 'tools.php' );

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'hide_menus' ), 999 );
	}

	public static function hide_menus(): void {
		if ( current_user_can( 'manage_options' ) ) {
			return;
		}
		foreach ( self::HIDDEN_MENUS as $slug ) {
			remove_menu_page( $slug );
		}
	}
}
