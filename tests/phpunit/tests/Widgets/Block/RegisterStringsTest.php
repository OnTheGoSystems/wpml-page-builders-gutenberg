<?php

namespace WPML\PB\Gutenberg\Widgets\Block;

use WPML\API\MakeMock;
use WPML\Element\API\LanguagesMock;
use WPML\LIB\WP\OnActionMock;
use WPML\LIB\WP\WPDBMock;

class RegisterStringsTest extends \OTGS_TestCase {
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
	public function it_registers_strings_when_widget_blocks_are_updated() {
		$this->setActiveLanguages( [ 'fr', 'de' ] );

		$subject = new RegisterStrings();

		$newValue = [
			2              => [
				'content' => '<p>My widget text 1</p>',
				'title'   => null,
			],
			3              => [
				'content' => '<p>My widget text 2</p>',
				'title'   => null,
			],
			'_multiwidget' => 1,
		];
		$blocks   = [
			2 => (object) [
				'blockName'    => 'core/paragraph',
				'attrs'        => [],
				'innerHTML'    => $newValue[2]['content'],
				'innerContent' => [
					$newValue[2]['content']
				],
			],
			3 => (object) [
				'blockName'    => 'core/paragraph',
				'attrs'        => [],
				'innerHTML'    => $newValue[3]['content'],
				'innerContent' => [
					$newValue[3]['content']
				],
			],
		];
		$this->addContentToParse( $newValue[2]['content'], $blocks[2] );
		$this->addContentToParse( $newValue[3]['content'], $blocks[3] );

		$this->setBlockWidgetStrings( [
			md5( 'Some text' )   => [
				'fr' => [
					'value'  => 'FR Some text',
					'status' => 10,
				],
				'de' => [
					'value'  => 'DE Some text',
					'status' => 10,
				],
			],
			md5( 'Some text 2' ) => [
				'fr' => [
					'value'  => 'FR Some text 2',
					'status' => 10,
				]
			]
		] );

		$this->gutenbergIntegration->shouldReceive( 'register_strings_from_widget' )
		                           ->with( [ $blocks[2], $blocks[3] ], Strings::createPackage() );

		$subject->add_hooks();
		$this->runAction( 'update_option_widget_block', [], $newValue );
	}
}