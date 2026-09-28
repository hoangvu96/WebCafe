<?php
namespace CafeCore\AdminRoles;

use CafeCore\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Tạo vai trò Nhân viên bán hàng và cấp quyền xem Tổng quan. Tăng VERSION khi đổi quyền.
 */
final class Roles {
	public const VERSION        = '1';
	public const VERSION_OPTION = 'cafe_core_roles_version';

	public static function register(): void {
		add_action( 'init', array( self::class, 'maybe_install' ) );
	}

	public static function maybe_install(): void {
		if ( get_option( self::VERSION_OPTION ) === self::VERSION ) {
			return;
		}

		remove_role( Capabilities::ROLE_STAFF );
		add_role( Capabilities::ROLE_STAFF, __( 'Nhân viên bán hàng', 'cafe-core' ), Capabilities::staff() );

		foreach ( array( 'administrator', 'shop_manager' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				$role->add_cap( Capabilities::VIEW_DASHBOARD );
			}
		}

		update_option( self::VERSION_OPTION, self::VERSION );
	}
}
