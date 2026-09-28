<?php
/**
 * Cấu hình WordPress + WooCommerce cho cửa hàng trong nước, chỉ COD, không tài khoản khách.
 * Chạy: wp eval-file /scripts/setup/options.php
 */
$options = array(
	'blogdescription'                                  => 'Cà phê rang xay nguyên chất',
	'timezone_string'                                  => 'Asia/Ho_Chi_Minh',
	'date_format'                                      => 'd/m/Y',
	'time_format'                                      => 'H:i',
	'start_of_week'                                    => 1,
	'default_comment_status'                           => 'closed',
	'woocommerce_default_country'                      => 'VN',
	'woocommerce_allowed_countries'                    => 'specific',
	'woocommerce_specific_allowed_countries'           => array( 'VN' ),
	'woocommerce_ship_to_countries'                    => '',
	'woocommerce_ship_to_destination'                  => 'billing_only',
	'woocommerce_currency'                             => 'VND',
	'woocommerce_currency_pos'                         => 'right_space',
	'woocommerce_price_thousand_sep'                   => '.',
	'woocommerce_price_decimal_sep'                    => ',',
	'woocommerce_price_num_decimals'                   => 0,
	'woocommerce_calc_taxes'                           => 'no',
	'woocommerce_enable_guest_checkout'                => 'yes',
	'woocommerce_enable_checkout_login_reminder'       => 'no',
	'woocommerce_enable_signup_and_login_from_checkout' => 'no',
	'woocommerce_enable_myaccount_registration'        => 'no',
	'woocommerce_checkout_phone_field'                 => 'required',
	'woocommerce_checkout_company_field'               => 'hidden',
	'woocommerce_checkout_address_2_field'             => 'hidden',
	'woocommerce_enable_reviews'                       => 'no',
	'woocommerce_enable_coupons'                       => 'no',
	'woocommerce_manage_stock'                         => 'yes',
	'woocommerce_notify_low_stock_amount'              => 5,
	'woocommerce_coming_soon'                          => 'no',
	'woocommerce_task_list_hidden'                     => 'yes',
	'woocommerce_onboarding_profile'                   => array( 'skipped' => true ),
	'woocommerce_permalinks'                           => array(
		'product_base'           => '/san-pham',
		'category_base'          => 'danh-muc',
		'tag_base'               => 'the-san-pham',
		'attribute_base'         => '',
		'use_verbose_page_rules' => false,
	),
	'woocommerce_cod_settings'                         => array(
		'enabled'            => 'yes',
		'title'              => 'Thanh toán khi nhận hàng (COD)',
		'description'        => 'Bạn trả tiền mặt cho nhân viên giao hàng khi nhận hàng.',
		'instructions'       => '', // cafe-core tự hiển thị hướng dẫn ở trang cảm ơn.
		'enable_for_methods' => array(),
		'enable_for_virtual' => 'yes',
	),
	'woocommerce_bacs_settings'                        => array( 'enabled' => 'no' ),
	'woocommerce_cheque_settings'                      => array( 'enabled' => 'no' ),
);

foreach ( $options as $name => $value ) {
	update_option( $name, $value );
}

WP_CLI::success( 'Đã cập nhật ' . count( $options ) . ' option.' );
