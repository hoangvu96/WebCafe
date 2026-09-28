<?php
namespace CafeCore\AdminRoles;

defined( 'ABSPATH' ) || exit;

/**
 * Màu thương hiệu tạm cho trang đăng nhập và thanh admin.
 */
final class Branding {
	private const BROWN = '#4A2C1D';
	private const CREAM = '#F5EDE0';

	public static function register(): void {
		add_action( 'login_enqueue_scripts', array( self::class, 'login_styles' ) );
		add_filter( 'login_headerurl', static fn(): string => home_url( '/' ) );
		add_filter( 'login_headertext', static fn(): string => get_bloginfo( 'name' ) );
		add_action( 'admin_head', array( self::class, 'admin_bar_styles' ) );
		add_action( 'wp_head', array( self::class, 'admin_bar_styles' ) );
	}

	public static function login_styles(): void {
		printf(
			'<style>body.login{background:%2$s}.login h1 a{background:none;width:auto;height:auto;text-indent:0;font:600 28px/1.3 Georgia,serif;color:%1$s}.login #wp-submit{background:%1$s;border-color:%1$s}.login #nav a,.login #backtoblog a{color:%1$s}</style>',
			esc_attr( self::BROWN ),
			esc_attr( self::CREAM )
		);
	}

	public static function admin_bar_styles(): void {
		if ( ! is_admin_bar_showing() ) {
			return;
		}
		printf( '<style>#wpadminbar{background:%s}</style>', esc_attr( self::BROWN ) );
	}
}
