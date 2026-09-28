import { execFileSync } from 'node:child_process';
import path from 'node:path';

/**
 * Dọn đơn hàng do E2E tạo (khách "Nguyễn Văn An"): chuyển sang Đã huỷ để WooCommerce
 * trả lại tồn kho, rồi xoá hẳn đơn và ghi chú đơn. Xoá luôn ghi chú mồ côi (đơn không còn).
 */
const CLEANUP_PHP = `
global $wpdb;
$statuses = array_merge( array_keys( wc_get_order_statuses() ), array( 'trash', 'checkout-draft' ) );
$removed  = 0;
foreach ( wc_get_orders( array( 'limit' => -1, 'status' => $statuses ) ) as $order ) {
	if ( 'Nguyễn Văn An' !== $order->get_billing_first_name() ) {
		continue;
	}
	$id = $order->get_id();
	if ( ! $order->has_status( array( 'cancelled', 'trash' ) ) ) {
		$order->update_status( 'cancelled', 'E2E teardown' );
	}
	$order->delete( true );
	foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_post_ID = %d AND comment_type = 'order_note'", $id ) ) as $note ) {
		wp_delete_comment( (int) $note, true );
	}
	++$removed;
}
$orphans = 0;
foreach ( $wpdb->get_col( "SELECT DISTINCT comment_post_ID FROM {$wpdb->comments} WHERE comment_type = 'order_note'" ) as $post_id ) {
	if ( wc_get_order( (int) $post_id ) ) {
		continue;
	}
	foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_post_ID = %d AND comment_type = 'order_note'", $post_id ) ) as $note ) {
		wp_delete_comment( (int) $note, true );
		++$orphans;
	}
}
echo "E2E teardown: xoá {$removed} đơn test, {$orphans} ghi chú mồ côi\\n";
`;

export default function globalTeardown(): void {
	const repoRoot = path.resolve(__dirname, '..', '..');
	const project = process.env.COMPOSE_PROJECT_NAME;
	const args = [
		'compose',
		...(project ? ['-p', project] : []),
		'run', '--rm', '-T', 'wpcli', 'wp', 'eval', CLEANUP_PHP,
	];
	execFileSync('docker', args, { cwd: repoRoot, env: process.env, stdio: ['ignore', 'inherit', 'inherit'] });
}
