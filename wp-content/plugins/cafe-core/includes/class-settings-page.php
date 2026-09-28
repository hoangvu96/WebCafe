<?php
namespace CafeCore;

defined( 'ABSPATH' ) || exit;

/**
 * Trang WooCommerce → Cài đặt Cafe.
 */
final class SettingsPage {
	public const SLUG  = 'cafe-settings';
	public const GROUP = 'cafe_core';

	public static function register(): void {
		add_action( 'admin_init', array( self::class, 'register_setting' ) );
		add_action( 'admin_menu', array( self::class, 'menu' ), 60 );
		// Mặc định options.php đòi manage_options; cho phép Quản lý cửa hàng lưu.
		add_filter( 'option_page_capability_' . self::GROUP, static fn(): string => 'manage_woocommerce' );
	}

	public static function register_setting(): void {
		register_setting(
			self::GROUP,
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Settings::class, 'sanitize' ),
				'default'           => Settings::DEFAULTS,
			)
		);
	}

	public static function menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Cài đặt cửa hàng cà phê', 'cafe-core' ),
			__( 'Cài đặt Cafe', 'cafe-core' ),
			'manage_woocommerce',
			self::SLUG,
			array( self::class, 'render' )
		);
	}

	public static function render(): void {
		$settings = Settings::all();
		$name     = Settings::OPTION;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Cài đặt cửa hàng cà phê', 'cafe-core' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( self::GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="cafe-shipping-fee"><?php esc_html_e( 'Phí ship cố định (đ)', 'cafe-core' ); ?></label></th>
						<td><input id="cafe-shipping-fee" type="number" min="0" step="1000" name="<?php echo esc_attr( $name ); ?>[shipping_fee]" value="<?php echo esc_attr( $settings['shipping_fee'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="cafe-free-threshold"><?php esc_html_e( 'Miễn phí ship cho đơn từ (đ)', 'cafe-core' ); ?></label></th>
						<td>
							<input id="cafe-free-threshold" type="number" min="0" step="1000" name="<?php echo esc_attr( $name ); ?>[free_threshold]" value="<?php echo esc_attr( $settings['free_threshold'] ); ?>">
							<p class="description"><?php esc_html_e( 'Tính trên tổng tiền hàng, chưa gồm phí ship. Đặt 0 để tắt miễn phí ship.', 'cafe-core' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="cafe-shop-phone"><?php esc_html_e( 'Số điện thoại hỗ trợ', 'cafe-core' ); ?></label></th>
						<td><input id="cafe-shop-phone" type="text" class="regular-text" name="<?php echo esc_attr( $name ); ?>[shop_phone]" value="<?php echo esc_attr( $settings['shop_phone'] ); ?>"></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
