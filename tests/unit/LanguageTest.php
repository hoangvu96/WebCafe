<?php
use CafeCore\Language\Language;
use PHPUnit\Framework\TestCase;

final class LanguageTest extends TestCase {
	public function test_defaults_to_vietnamese(): void {
		$this->assertSame( 'vi', Language::resolve( null, null ) );
	}

	public function test_uses_cookie_when_no_request(): void {
		$this->assertSame( 'en', Language::resolve( null, 'en' ) );
	}

	public function test_request_overrides_cookie(): void {
		$this->assertSame( 'vi', Language::resolve( 'vi', 'en' ) );
	}

	public function test_ignores_unsupported_values(): void {
		$this->assertSame( 'en', Language::resolve( 'fr', 'en' ) );
		$this->assertSame( 'vi', Language::resolve( 'xx', 'yy' ) );
		$this->assertSame( 'vi', Language::resolve( '', '' ) );
	}

	public function test_is_case_sensitive(): void {
		$this->assertSame( 'vi', Language::resolve( 'EN', null ) );
	}

	public function test_maps_code_to_wordpress_locale(): void {
		$this->assertSame( 'vi', Language::locale( 'vi' ) );
		$this->assertSame( 'en_US', Language::locale( 'en' ) );
		$this->assertSame( 'vi', Language::locale( 'fr' ) );
	}

	public function test_lists_vietnamese_first(): void {
		$this->assertSame( array( 'vi', 'en' ), array_keys( Language::languages() ) );
	}
}
