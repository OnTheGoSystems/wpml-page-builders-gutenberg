<?php

namespace phpunit\tests\Widgets\Block;


use WPML\API\MakeMock;
use WPML\Element\API\LanguagesMock;
use WPML\LIB\WP\OnActionMock;
use WPML\LIB\WP\WPDBMock;
use WPML\PB\Gutenberg\Widgets\Block\Strings;
use WPML\PB\Gutenberg\Widgets\Block\WidgetMock;

class StringsTest extends \OTGS_TestCase {
	use WPDBMock;
	use MakeMock;
	use OnActionMock;
	use LanguagesMock;
	use WidgetMock;

	public function setUp() {
		parent::setUp();

		$this->setUpWPDBMock();
		$this->setUpMakeMock();
		$this->setUpOnAction();
		$this->setupLanguagesMock();
		$this->setUpWidgetBlock();
	}

	/**
	 * @test
	 */
	public function it_loads_strings_from_mo_file() {
		$locale = 'fr_FR';
		$this->setStringsInMOFile( Strings::DOMAIN, $locale, [
			'my string 1' => 'fr my string 1',
			'my string 2' => 'fr my string 2',
			'my string 3' => 'fr my string 3',
		] );

		$result   = Strings::fromMo( $locale );
		$expected = [
			'my string 1' => [
				'fr' => [ 'value' => 'fr my string 1', 'status' => ICL_STRING_TRANSLATION_COMPLETE ]
			],
			'my string 2' => [
				'fr' => [ 'value' => 'fr my string 2', 'status' => ICL_STRING_TRANSLATION_COMPLETE ]
			],
			'my string 3' => [
				'fr' => [ 'value' => 'fr my string 3', 'status' => ICL_STRING_TRANSLATION_COMPLETE ]
			],
		];
		$this->assertEquals( $expected, $result );
	}

	/**
	 * @test
	 */
	public function it_returns_empty_array_if_mo_file_cannot_be_read() {
		$locale = 'fr_FR';
		$this->setStringsInMOFile( Strings::DOMAIN, $locale, [] );

		$this->assertEmpty( Strings::fromMo( $locale ) );
	}
}