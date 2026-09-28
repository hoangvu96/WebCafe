<?php
use CafeCore\CheckoutVn\PhoneValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneValidatorTest extends TestCase {
	public static function valid_numbers(): array {
		return array( array( '0912345678' ), array( '0912 345 678' ), array( '0912.345.678' ), array( ' 0387654321 ' ) );
	}

	public static function invalid_numbers(): array {
		return array( array( '' ), array( '912345678' ), array( '09123456789' ), array( '+84912345678' ), array( '09123abc78' ), array( '0912-345-678' ) );
	}

	#[DataProvider( 'valid_numbers' )]
	public function test_accepts_valid_numbers( string $phone ): void {
		$this->assertTrue( PhoneValidator::is_valid( $phone ) );
	}

	#[DataProvider( 'invalid_numbers' )]
	public function test_rejects_invalid_numbers( string $phone ): void {
		$this->assertFalse( PhoneValidator::is_valid( $phone ) );
	}

	public function test_normalize_removes_spaces_and_dots(): void {
		$this->assertSame( '0912345678', PhoneValidator::normalize( ' 0912.345 678 ' ) );
	}
}
