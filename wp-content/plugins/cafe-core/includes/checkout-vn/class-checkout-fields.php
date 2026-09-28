<?php
namespace CafeCore\CheckoutVn;

use CafeCore\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Form thanh toán theo địa chỉ Việt Nam 2 cấp (Tỉnh/Thành phố, Phường/Xã) và trang cảm ơn COD.
 */
final class CheckoutFields {
	public const HIDDEN_CLASS       = 'cafe-hidden-field';
	public const WARD_META          = '_billing_ward'; // WooCommerce tự lưu trường billing_ward vào meta này.
	public const SHIPPING_WARD_META = '_shipping_ward'; // Khi ship_to_destination=billing_only WooCommerce sao chép billing sang shipping và lưu vào đây.

	public static function register(): void {
		add_filter( 'woocommerce_default_address_fields', array( self::class, 'address_fields' ), 20 );
		add_filter( 'woocommerce_billing_fields', array( self::class, 'billing_fields' ), 20 );
		add_filter( 'woocommerce_localisation_address_formats', array( self::class, 'address_formats' ), 20 );
		add_filter( 'woocommerce_order_formatted_billing_address', array( self::class, 'formatted_address' ), 10, 2 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( self::class, 'formatted_shipping_address' ), 10, 2 );
		add_filter( 'woocommerce_validate_phone', array( self::class, 'validate_phone_filter' ), 10, 2 );
		add_action( 'woocommerce_after_checkout_validation', array( self::class, 'validate' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( self::class, 'normalize_phone' ) );
		add_action( 'woocommerce_thankyou_cod', array( self::class, 'thankyou_cod' ), 5 );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	public static function address_fields( array $fields ): array {
		unset( $fields['last_name'], $fields['company'], $fields['address_2'], $fields['state'], $fields['postcode'] );

		// Chỉ bán trong nước: giữ trường quốc gia (WooCommerce cần) nhưng ẩn đi.
		$fields['country']['priority'] = 5;
		$fields['country']['class'][]  = self::HIDDEN_CLASS;

		$fields['first_name'] = array_merge(
			$fields['first_name'],
			array( 'label' => __( 'Họ và tên', 'cafe-core' ), 'class' => array( 'form-row-wide' ), 'priority' => 10 )
		);
		$fields['city']       = array_merge(
			$fields['city'],
			array( 'label' => __( 'Tỉnh/Thành phố', 'cafe-core' ), 'required' => true, 'class' => array( 'form-row-first', 'address-field' ), 'priority' => 40 )
		);
		$fields['ward']       = array(
			'label'    => __( 'Phường/Xã/Đặc khu', 'cafe-core' ),
			'required' => true,
			'class'    => array( 'form-row-last' ),
			'priority' => 50,
		);
		$fields['address_1']  = array_merge(
			$fields['address_1'],
			array(
				'label'       => __( 'Địa chỉ cụ thể', 'cafe-core' ),
				'placeholder' => __( 'Số nhà, tên đường, thôn/xóm', 'cafe-core' ),
				'class'       => array( 'form-row-wide', 'address-field' ),
				'priority'    => 60,
			)
		);

		return $fields;
	}

	public static function billing_fields( array $fields ): array {
		if ( isset( $fields['billing_phone'] ) ) {
			$fields['billing_phone'] = array_merge(
				$fields['billing_phone'],
				array( 'label' => __( 'Số điện thoại', 'cafe-core' ), 'type' => 'tel', 'required' => true, 'class' => array( 'form-row-first' ), 'priority' => 20 )
			);
		}
		if ( isset( $fields['billing_email'] ) ) {
			$fields['billing_email'] = array_merge(
				$fields['billing_email'],
				array( 'label' => __( 'Email (để nhận xác nhận đơn)', 'cafe-core' ), 'required' => false, 'class' => array( 'form-row-last' ), 'priority' => 30 )
			);
		}
		return $fields;
	}

	public static function address_formats( array $formats ): array {
		$formats['VN'] = "{name}\n{address_1}\n{address_2}\n{city}\n{country}";
		return $formats;
	}

	/**
	 * Hiển thị Phường/Xã ở dòng address_2 (trường address_2 gốc đã bị bỏ khỏi form).
	 * Chỉ ghi đè khi có giá trị ward để không xoá mất address_2 của các đơn cũ.
	 *
	 * @param array  $address Địa chỉ thô.
	 * @param object $order   WC_Order.
	 */
	public static function formatted_address( array $address, $order ): array {
		$ward = (string) $order->get_meta( self::WARD_META );
		if ( '' !== $ward ) {
			$address['address_2'] = $ward;
		}
		return $address;
	}

	/**
	 * Hiển thị Phường/Xã ở dòng address_2 của địa chỉ giao hàng.
	 * Khi ship_to_destination=billing_only, WooCommerce sao chép billing sang shipping
	 * nhưng để chắc chắn (ví dụ đơn cũ trước khi bật tuỳ chọn này) vẫn dự phòng đọc _billing_ward.
	 * Chỉ ghi đè khi có giá trị ward để không xoá mất address_2 của các đơn cũ.
	 *
	 * @param array  $address Địa chỉ thô.
	 * @param object $order   WC_Order.
	 */
	public static function formatted_shipping_address( array $address, $order ): array {
		$ward = (string) $order->get_meta( self::SHIPPING_WARD_META );
		if ( '' === $ward ) {
			$ward = (string) $order->get_meta( self::WARD_META );
		}
		if ( '' !== $ward ) {
			$address['address_2'] = $ward;
		}
		return $address;
	}

	/**
	 * Việt Nam hoá kiểm tra số điện thoại của WooCommerce (WC_Validation::is_phone).
	 * Áp dụng chung vì cửa hàng chỉ bán trong nước.
	 */
	public static function validate_phone_filter( bool $valid, string $phone ): bool {
		return '' === $phone || PhoneValidator::is_valid( $phone );
	}

	public static function validate( array $data, \WP_Error $errors ): void {
		$phone = (string) ( $data['billing_phone'] ?? '' );
		if ( '' !== $phone && ! PhoneValidator::is_valid( $phone ) ) {
			// WooCommerce có thể đã thêm lỗi cùng mã (billing_phone_validation) ở validate_posted_data();
			// xoá đi để chỉ còn đúng một thông báo tiếng Việt.
			$errors->remove( 'billing_phone_validation' );
			$errors->add(
				'billing_phone_validation',
				__( 'Số điện thoại không hợp lệ. Vui lòng nhập 10 số, bắt đầu bằng 0.', 'cafe-core' ),
				array( 'id' => 'billing_phone' )
			);
		}
	}

	public static function normalize_phone( \WC_Order $order ): void {
		$order->set_billing_phone( PhoneValidator::normalize( $order->get_billing_phone() ) );
	}

	public static function thankyou_cod( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		echo '<section class="cafe-thankyou-cod">';
		printf(
			'<p>%s</p>',
			/* translators: %s: tổng tiền đơn hàng */
			sprintf( esc_html__( 'Vui lòng chuẩn bị %s khi nhận hàng.', 'cafe-core' ), wp_kses_post( $order->get_formatted_order_total() ) )
		);

		$phone = (string) Settings::get( 'shop_phone' );
		if ( '' !== $phone ) {
			printf(
				'<p>%s</p>',
				/* translators: %s: số điện thoại cửa hàng */
				sprintf(
					esc_html__( 'Cần hỗ trợ? Gọi %s', 'cafe-core' ),
					'<a href="' . esc_url( 'tel:' . PhoneValidator::normalize( $phone ) ) . '">' . esc_html( $phone ) . '</a>'
				)
			);
		}
		echo '</section>';
	}

	public static function enqueue(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		wp_register_style( 'cafe-checkout', false, array(), CAFE_CORE_VERSION );
		wp_enqueue_style( 'cafe-checkout' );
		wp_add_inline_style( 'cafe-checkout', '.' . self::HIDDEN_CLASS . '{display:none!important}' );
	}
}
