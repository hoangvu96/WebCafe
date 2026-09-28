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
		$this->assertSame( array( 'country', 'first_name', 'city', 'ward', 'address_1' ), array_keys( $fields ) );
		$this->assertSame( 'Họ và tên', $fields['first_name']['label'] );
		$this->assertSame( 'Tỉnh/Thành phố', $fields['city']['label'] );
		$this->assertSame( 'Phường/Xã/Đặc khu', $fields['ward']['label'] );
		$this->assertTrue( $fields['ward']['required'] );
		$this->assertTrue( $fields['city']['required'] );
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
		$this->assertFalse( $fields['billing_email']['required'] );
		$this->assertSame( 30, $fields['billing_email']['priority'] );
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
}
