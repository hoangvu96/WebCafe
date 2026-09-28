<?php
/**
 * @var array         $metrics
 * @var \WC_Order[]   $pending
 * @var int           $pending_count
 * @var \WC_Product[] $low_stock
 */
defined( 'ABSPATH' ) || exit;

$cards = array(
	__( 'Doanh thu hôm nay', 'cafe-core' )   => wc_price( $metrics['revenue_today'] ),
	__( 'Doanh thu 7 ngày', 'cafe-core' )    => wc_price( $metrics['revenue_7d'] ),
	__( 'Doanh thu tháng này', 'cafe-core' ) => wc_price( $metrics['revenue_month'] ),
	__( 'Đơn mới hôm nay', 'cafe-core' )     => esc_html( number_format_i18n( $metrics['orders_today'] ) ),
);
?>
<div class="wrap cafe-dashboard">
	<h1><?php esc_html_e( 'Tổng quan cửa hàng', 'cafe-core' ); ?></h1>

	<div class="cafe-cards">
		<?php foreach ( $cards as $label => $value ) : ?>
			<div class="cafe-card">
				<span class="cafe-card__label"><?php echo esc_html( $label ); ?></span>
				<strong class="cafe-card__value"><?php echo wp_kses_post( $value ); ?></strong>
			</div>
		<?php endforeach; ?>
	</div>

	<section class="cafe-panel">
		<h2><?php esc_html_e( 'Doanh thu 30 ngày', 'cafe-core' ); ?></h2>
		<div class="cafe-chart"><canvas id="cafe-revenue-chart" aria-label="<?php esc_attr_e( 'Biểu đồ doanh thu 30 ngày', 'cafe-core' ); ?>"></canvas></div>
	</section>

	<div class="cafe-grid">
		<section class="cafe-panel">
			<?php /* translators: %s: số đơn đang xử lý, bọc trong span để JS cập nhật khi đánh dấu đã giao */ ?>
			<h2><?php printf( esc_html__( 'Đơn cần xử lý (%s)', 'cafe-core' ), '<span class="cafe-pending-count">' . esc_html( number_format_i18n( $pending_count ) ) . '</span>' ); ?></h2>
			<?php if ( ! $pending ) : ?>
				<p><?php esc_html_e( 'Không có đơn nào đang chờ giao.', 'cafe-core' ); ?></p>
			<?php else : ?>
				<table class="widefat striped cafe-pending">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Đơn', 'cafe-core' ); ?></th>
							<th><?php esc_html_e( 'Khách', 'cafe-core' ); ?></th>
							<th><?php esc_html_e( 'Số điện thoại', 'cafe-core' ); ?></th>
							<th><?php esc_html_e( 'Tổng tiền', 'cafe-core' ); ?></th>
							<th><?php esc_html_e( 'Đặt lúc', 'cafe-core' ); ?></th>
							<th><span class="screen-reader-text"><?php esc_html_e( 'Thao tác', 'cafe-core' ); ?></span></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pending as $order ) : ?>
							<tr data-order-id="<?php echo esc_attr( $order->get_id() ); ?>">
								<td><a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">#<?php echo esc_html( $order->get_order_number() ); ?></a></td>
								<td><?php echo esc_html( $order->get_billing_first_name() ); ?></td>
								<td><a href="tel:<?php echo esc_attr( $order->get_billing_phone() ); ?>"><?php echo esc_html( $order->get_billing_phone() ); ?></a></td>
								<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
								<td><?php echo esc_html( wc_format_datetime( $order->get_date_created(), 'd/m H:i' ) ); ?></td>
								<td><button type="button" class="button button-primary cafe-mark-delivered" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>"><?php esc_html_e( 'Đã giao', 'cafe-core' ); ?></button></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php if ( $pending_count > count( $pending ) ) : ?>
					<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-orders&status=wc-processing' ) ); ?>"><?php esc_html_e( 'Xem tất cả đơn đang xử lý', 'cafe-core' ); ?></a></p>
				<?php endif; ?>
			<?php endif; ?>
		</section>

		<section class="cafe-panel">
			<h2><?php esc_html_e( 'Sắp hết hàng', 'cafe-core' ); ?></h2>
			<?php if ( ! $low_stock ) : ?>
				<p><?php esc_html_e( 'Tồn kho đều ổn.', 'cafe-core' ); ?></p>
			<?php else : ?>
				<ul class="cafe-list">
					<?php foreach ( $low_stock as $product ) : ?>
						<li>
							<span><?php echo esc_html( $product->get_name() ); ?></span>
							<strong class="cafe-stock-low"><?php echo esc_html( number_format_i18n( (int) $product->get_stock_quantity() ) ); ?></strong>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>

		<section class="cafe-panel">
			<h2><?php esc_html_e( 'Bán chạy 30 ngày', 'cafe-core' ); ?></h2>
			<?php if ( ! $metrics['top_products'] ) : ?>
				<p><?php esc_html_e( 'Chưa có dữ liệu.', 'cafe-core' ); ?></p>
			<?php else : ?>
				<ol class="cafe-list">
					<?php foreach ( $metrics['top_products'] as $row ) : ?>
						<li>
							<span><?php echo esc_html( $row['name'] ); ?></span>
							<?php /* translators: %s: số lượng đã bán */ ?>
							<strong><?php printf( esc_html__( '%s đã bán', 'cafe-core' ), esc_html( number_format_i18n( $row['qty'] ) ) ); ?></strong>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
		</section>
	</div>
</div>
