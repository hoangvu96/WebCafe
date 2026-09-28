<?php
use CafeCore\CheckoutVn\CheckoutFields;
use PHPUnit\Framework\TestCase;

final class CheckoutFieldsTest extends TestCase {
	/** Giống cấu trúc WC_Countries::get_default_address_fields(). */
	private function wc_defaults(): array {
		return array(
			'first_name' => array( 'label' => 'First name', 'required' => true, 'class' => array( 'form-row-first' ), 'priority' => 10 ),
			'last_name'  => array( 'label' => 'Last name', 'required' => true, 'class' => array( 'form-row-last' ), 'priority' => 20 ),
			'company'    => array( 'label' => 'Company', 'class' => array( 'form-row-wide' ), 'priority' => 30 ),
			'country'    => array( 'type' => 'country', 'label' => 'Country', 'required' => true, 'class' => array( 'form-row-wide', 'address-field', 'update_totals_on_change' ), 'priority' => 40 ),
			'address_1'  => array( 'label' => 'Street address', 'required' => true, 'class' => array( 'form-row-wide', 'address-field' ), 'priority' => 50 ),
			'address_2'  => array( 'label' => 'Apartment', 'required' => false, 'class' => array( 'form-row-wide', 'address-field' ), 'priority' => 60 ),
			'city'       => array( 'label' => 'Town / City', 'required' => true, 'class' => array( 'form-row-wide', 'address-field' ), 'priority' => 70 ),
			'state'      => array( 'type' => 'state', 'label' => 'State', 'required' => true, 'class' => array( 'form-row-wide', 'address-field' ), 'priority' => 80 ),
			'postcode'   => array( 'label' => 'Postcode', 'required' => true, 'class' => array( 'form-row-wide', 'address-field' ), 'priority' => 90 ),
			// WC_Countries::get_default_address_fields() còn có 'phone' (không tiền tố) mà
			// WooCommerce dùng để dựng snapshot cho address-i18n.js (wc_address_i18n_params.locale['default']).
			// Nếu không cập nhật priority/class ở đây, JS sẽ ghi đè lại priority=100 gốc sau khi
			// chạy country_to_state_changing, đẩy Số điện thoại xuống cuối form dù PHP đã render đúng thứ tự.
			'phone'      => array( 'label' => 'Phone', 'required' => true, 'type' => 'tel', 'class' => array( 'form-row-wide' ), 'priority' => 100 ),
		);
	}

	public function test_removes_fields_not_used_in_vietnam(): void {
		$fields = CheckoutFields::address_fields( $this->wc_defaults() );
		foreach ( array( 'last_name', 'company', 'address_2', 'state', 'postcode' ) as $key ) {
			$this->assertArrayNotHasKey( $key, $fields );
		}
	}

	public function test_two_level_address_in_order(): void {
		$fields = CheckoutFields::address_fields( $this->wc_defaults() );
		uasort( $fields, static fn( array $a, array $b ): int => $a['priority'] <=> $b['priority'] );
		// 'phone' (không tiền tố) phải nằm giữa first_name và city ở chính bộ lọc này, vì đây là
		// snapshot mà address-i18n.js dùng để sắp xếp lại các trường sau khi JS chạy trên trình duyệt.
		$this->assertSame( array( 'country', 'first_name', 'phone', 'city', 'ward', 'address_1' ), array_keys( $fields ) );
		$this->assertSame( 'Họ và tên', $fields['first_name']['label'] );
		$this->assertSame( 'Tỉnh/Thành phố', $fields['city']['label'] );
		$this->assertSame( 'Phường/Xã/Đặc khu', $fields['ward']['label'] );
		$this->assertTrue( $fields['ward']['required'] );
		$this->assertTrue( $fields['city']['required'] );

		// Cả 4 trường nửa dòng cũ giờ full-width để tránh lệch hàng float; address-field giữ nguyên
		// ở city/address_1 (đã có sẵn) để không phá vỡ các hook khác của WooCommerce dựa vào lớp này.
		$this->assertSame( array( 'form-row-wide' ), $fields['phone']['class'] );
		$this->assertSame( 20, $fields['phone']['priority'] );
		$this->assertSame( 'Số điện thoại', $fields['phone']['label'] );
		$this->assertSame( array( 'form-row-wide', 'address-field' ), $fields['city']['class'] );
		$this->assertSame( array( 'form-row-wide' ), $fields['ward']['class'] );
		$this->assertSame( array( 'form-row-wide', 'address-field' ), $fields['address_1']['class'] );
	}

	public function test_country_is_hidden(): void {
		$fields = CheckoutFields::address_fields( $this->wc_defaults() );
		$this->assertContains( CheckoutFields::HIDDEN_CLASS, $fields['country']['class'] );
	}

	public function test_phone_required_and_email_optional(): void {
		$fields = CheckoutFields::billing_fields(
			array(
				'billing_phone' => array( 'label' => 'Phone', 'required' => false, 'priority' => 100 ),
				'billing_email' => array( 'label' => 'Email', 'required' => true, 'priority' => 110 ),
			)
		);
		$this->assertTrue( $fields['billing_phone']['required'] );
		$this->assertSame( 'tel', $fields['billing_phone']['type'] );
		$this->assertSame( 20, $fields['billing_phone']['priority'] );
		$this->assertSame( array( 'form-row-wide' ), $fields['billing_phone']['class'] );
		$this->assertFalse( $fields['billing_email']['required'] );
		$this->assertSame( 30, $fields['billing_email']['priority'] );
		$this->assertSame( array( 'form-row-wide' ), $fields['billing_email']['class'] );
	}

	public function test_vietnam_address_format_includes_ward_line(): void {
		$formats = CheckoutFields::address_formats( array( 'default' => 'x', 'VN' => 'y' ) );
		$this->assertSame( "{name}\n{address_1}\n{address_2}\n{city}\n{country}", $formats['VN'] );
		$this->assertSame( 'x', $formats['default'] );
	}

	public function test_formatted_address_puts_ward_in_address_2(): void {
		$order = new class() {
			public function get_meta( string $key ): string {
				return '_billing_ward' === $key ? 'Phường Bến Thành' : '';
			}
		};
		$address = CheckoutFields::formatted_address( array( 'first_name' => 'An', 'address_2' => '' ), $order );
		$this->assertSame( 'Phường Bến Thành', $address['address_2'] );
		$this->assertSame( 'An', $address['first_name'] );
	}

	public function test_formatted_address_does_not_clobber_legacy_address_2_when_ward_empty(): void {
		$order = new class() {
			public function get_meta( string $key ): string {
				return '';
			}
		};
		$address = CheckoutFields::formatted_address( array( 'address_2' => 'Legacy apt 4B' ), $order );
		$this->assertSame( 'Legacy apt 4B', $address['address_2'] );
	}

	public function test_formatted_shipping_address_uses_shipping_ward(): void {
		$order = new class() {
			public function get_meta( string $key ): string {
				if ( '_shipping_ward' === $key ) {
					return 'Phường Thủ Đức';
				}
				if ( '_billing_ward' === $key ) {
					return 'Phường Bến Thành';
				}
				return '';
			}
		};
		$address = CheckoutFields::formatted_shipping_address( array( 'first_name' => 'An', 'address_2' => '' ), $order );
		$this->assertSame( 'Phường Thủ Đức', $address['address_2'] );
	}

	public function test_formatted_shipping_address_falls_back_to_billing_ward_when_shipping_ward_empty(): void {
		// Trường hợp ship_to_destination=billing_only: WooCommerce chỉ lưu _billing_ward.
		$order = new class() {
			public function get_meta( string $key ): string {
				return '_billing_ward' === $key ? 'Phường Bến Thành' : '';
			}
		};
		$address = CheckoutFields::formatted_shipping_address( array( 'address_2' => '' ), $order );
		$this->assertSame( 'Phường Bến Thành', $address['address_2'] );
	}

	public function test_formatted_shipping_address_does_not_clobber_legacy_address_2_when_ward_empty(): void {
		$order = new class() {
			public function get_meta( string $key ): string {
				return '';
			}
		};
		$address = CheckoutFields::formatted_shipping_address( array( 'address_2' => 'Legacy apt 4B' ), $order );
		$this->assertSame( 'Legacy apt 4B', $address['address_2'] );
	}
}
