<?php
/**
 * @var \WC_Product[] $products
 * @var int           $threshold
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap cafe-dashboard">
	<h1><?php esc_html_e( 'Tồn kho', 'cafe-core' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Chỉ xem. Việc sửa sản phẩm và giá do Quản lý cửa hàng thực hiện.', 'cafe-core' ); ?></p>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Sản phẩm', 'cafe-core' ); ?></th>
				<th><?php esc_html_e( 'Mã SKU', 'cafe-core' ); ?></th>
				<th><?php esc_html_e( 'Giá', 'cafe-core' ); ?></th>
				<th><?php esc_html_e( 'Tồn kho', 'cafe-core' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $products as $product ) : ?>
				<?php $stock = (int) $product->get_stock_quantity(); ?>
				<tr>
					<td><?php echo esc_html( $product->get_name() ); ?></td>
					<td><?php echo esc_html( $product->get_sku() ); ?></td>
					<td><?php echo wp_kses_post( $product->get_price_html() ); ?></td>
					<td class="<?php echo $stock <= $threshold ? 'cafe-stock-low' : ''; ?>"><?php echo esc_html( number_format_i18n( $stock ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
