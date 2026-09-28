<?php
// Các file trong plugin có dòng kiểm tra ABSPATH ở đầu.
define( 'ABSPATH', __DIR__ . '/' );

// Thay thế tối thiểu cho hàm dịch của WordPress để test các class thuần.
if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

require dirname( __DIR__ ) . '/vendor/autoload.php';
