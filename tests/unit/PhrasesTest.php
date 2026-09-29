<?php
use CafeCore\Language\Phrases;
use PHPUnit\Framework\TestCase;

final class PhrasesTest extends TestCase {
	public function test_parses_one_pair_per_line(): void {
		$this->assertSame(
			array( 'Liên hệ' => 'Contact', 'Địa chỉ:' => 'Address:' ),
			Phrases::parse( "Liên hệ = Contact\r\nĐịa chỉ: = Address:\n" )
		);
	}

	public function test_skips_blank_comment_and_malformed_lines(): void {
		$this->assertSame(
			array( 'A' => 'B' ),
			Phrases::parse( "\n# ghi chú = bỏ qua\nkhông có dấu bằng\n = thiếu vế trái\nthiếu vế phải = \nA = B" )
		);
	}

	public function test_only_first_separator_splits(): void {
		$this->assertSame( array( '1 + 1' => '2 = two' ), Phrases::parse( '1 + 1 = 2 = two' ) );
	}

	public function test_translate_replaces_longest_phrase_first(): void {
		$map = array( 'Cà phê' => 'Coffee', 'Cà phê rang xay nguyên chất' => 'Pure roasted coffee' );
		$this->assertSame( 'Pure roasted coffee – Coffee', Phrases::translate( 'Cà phê rang xay nguyên chất – Cà phê', $map ) );
	}

	public function test_translate_without_map_returns_text(): void {
		$this->assertSame( 'Xin chào', Phrases::translate( 'Xin chào', array() ) );
	}

	public function test_format_round_trips(): void {
		$map = array( 'Liên hệ' => 'Contact', 'Chính sách' => 'Policies' );
		$this->assertSame( $map, Phrases::parse( Phrases::format( $map ) ) );
	}
}
