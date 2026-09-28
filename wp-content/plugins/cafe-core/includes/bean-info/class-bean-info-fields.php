<?php
namespace CafeCore\BeanInfo;

defined( 'ABSPATH' ) || exit;

/**
 * Trường nhập trong trang sửa sản phẩm và khung hiển thị trên trang chi tiết.
 */
final class BeanInfoFields {
	public static function register(): void {
		add_action( 'woocommerce_product_options_general_product_data', array( self::class, 'admin_fields' ) );
		add_action( 'woocommerce_admin_process_product_object', array( self::class, 'save' ) );
		add_action( 'woocommerce_single_product_summary', array( self::class, 'render' ), 35 );
	}

	private static function labels(): array {
		return array(
			'origin'   => __( 'Nguồn gốc', 'cafe-core' ),
			'altitude' => __( 'Độ cao', 'cafe-core' ),
			'roast'    => __( 'Mức rang', 'cafe-core' ),
			'flavor'   => __( 'Hương vị', 'cafe-core' ),
		);
	}

	private static function roast_labels(): array {
		return array(
			'light'  => __( 'Nhạt', 'cafe-core' ),
			'medium' => __( 'Vừa', 'cafe-core' ),
			'dark'   => __( 'Đậm', 'cafe-core' ),
		);
	}

	public static function admin_fields(): void {
		$labels = self::labels();
		echo '<div class="options_group cafe-bean-info-fields">';
		woocommerce_wp_text_input( array( 'id' => BeanInfo::FIELDS['origin'], 'label' => $labels['origin'], 'placeholder' => __( 'VD: Cầu Đất, Lâm Đồng', 'cafe-core' ) ) );
		woocommerce_wp_text_input( array( 'id' => BeanInfo::FIELDS['altitude'], 'label' => $labels['altitude'], 'placeholder' => __( 'VD: 1.500m', 'cafe-core' ) ) );
		woocommerce_wp_select(
			array(
				'id'      => BeanInfo::FIELDS['roast'],
				'label'   => $labels['roast'],
				'options' => array( '' => __( '— Không hiển thị —', 'cafe-core' ) ) + self::roast_labels(),
			)
		);
		woocommerce_wp_text_input( array( 'id' => BeanInfo::FIELDS['flavor'], 'label' => $labels['flavor'], 'placeholder' => __( 'VD: Cam chanh, hoa trắng', 'cafe-core' ) ) );
		echo '</div>';
	}

	public static function save( \WC_Product $product ): void {
		foreach ( BeanInfo::FIELDS as $field => $meta_key ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce đã kiểm tra nonce khi lưu sản phẩm.
			$value = sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ?? '' ) );
			if ( 'roast' === $field ) {
				$value = BeanInfo::sanitize_roast( $value );
			}
			$product->update_meta_data( $meta_key, $value );
		}
	}

	public static function render(): void {
		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$values = array();
		foreach ( BeanInfo::FIELDS as $field => $meta_key ) {
			$values[ $field ] = (string) $product->get_meta( $meta_key );
		}
		$rows = BeanInfo::rows( $values );
		if ( ! $rows ) {
			return;
		}

		$labels = self::labels();
		$roasts = self::roast_labels();
		echo '<section class="cafe-bean-info"><h2 class="cafe-bean-info__title">' . esc_html__( 'Thông tin hạt', 'cafe-core' ) . '</h2><dl class="cafe-bean-info__list">';
		foreach ( $rows as $field => $value ) {
			printf(
				'<div class="cafe-bean-info__row"><dt>%s</dt><dd>%s</dd></div>',
				esc_html( $labels[ $field ] ),
				esc_html( 'roast' === $field ? $roasts[ $value ] : $value )
			);
		}
		echo '</dl></section>';
	}
}
