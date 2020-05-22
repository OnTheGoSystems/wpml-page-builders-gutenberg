<?php

namespace WPML\PB\Gutenberg\StringsInBlock;

use function rand_long_str;

/**
 * @group page-builders
 * @group gutenberg
 * @group strings-in-block
 */
class TestBase extends \OTGS_TestCase {

	/**
	 * @test
	 * @dataProvider dpGetsStringType
	 * @group wpmlcore-7069
	 */
	public function itGetsStringType( $string, $expectedType ) {
		$this->assertEquals( $expectedType, Base::get_string_type( $string ) );
	}

	public function dpGetsStringType() {
		return [
			'simple string'               => [ 'some simple string', 'LINE' ],
			'string including line break' => [ "Hello\nThere", 'AREA' ],
			'long string'                 => [ rand_long_str( Base::LONG_STRING_LENGTH + 1 ), 'AREA' ],
			'string with HTML tag'        => [ 'Hello <b>There</b>', 'VISUAL' ],
			'with a simple URL'           => [ 'http://example.com', 'LINK' ],
			'string with text and URL'    => [ 'A string containing a URL http://example.com', 'LINE' ],
		];
	}
}