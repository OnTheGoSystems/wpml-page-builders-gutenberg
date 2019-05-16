<?php

namespace WPML\PB\Gutenberg\StringsInBlock;

/**
 * @group page-builders
 * @group gutenberg
 * @group strings-in-block
 */
class TestComposite extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_should_find() {
		$block = $this->getBlock();

		$parsers        = [];
		$parsed_strings = [];

		for ( $i = 0; $i < 3; $i++ ) {
			$parser_strings = [ 'String for parser' . $i ];

			$parsers[ $i ] = $this->getParser();
			$parsers[ $i ]->expects( $this->once() )
			              ->method( 'find' )
			              ->with( $block )
			              ->willReturn( $parser_strings );

			$parsed_strings = array_merge( $parsed_strings, $parser_strings );
		}

		$filtered_strings = [ 'filtered_strings' ];

		\WP_Mock::onFilter( 'wpml_found_strings_in_block' )
			->with( $parsed_strings, $block )
			->reply( $filtered_strings );

		$subject = new Composite( $parsers );

		$found_strings = $subject->find( $block );

		$this->assertEquals( $filtered_strings, $found_strings );
	}

	/**
	 * @test
	 */
	public function it_should_update() {
		$block               = $this->getBlock();
		$string_translations = [ 'some string translations' ];
		$lang                = 'fr';

		for ( $i = 0; $i < 3; $i++ ) {
			$parsers[ $i ] = $this->getParser();
			$parsers[ $i ]->expects( $this->once() )
			              ->method( 'update' )
			              ->with( $block )
			              ->willReturn( $block );
		}

		$filtered_block = $this->getBlock();

		\WP_Mock::onFilter( 'wpml_update_strings_in_block' )
		        ->with( $block, $string_translations, $lang )
		        ->reply( $filtered_block );

		$subject = new Composite( $parsers );

		$this->assertSame(
			$filtered_block,
			$subject->update( $block, $string_translations, $lang )
		);
	}

	private function getBlock() {
		return $this->getMockBuilder( '\WP_Block_Parser_Block' )
			->disableOriginalConstructor()->getMock();
	}

	private function getParser() {
		return $this->getMockBuilder( 'WPML\PB\Gutenberg\StringsInBlock\StringsInBlock' )
			->disableOriginalConstructor()->getMock();
	}
}
