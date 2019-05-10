<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

/**
 * @group reusable-blocks
 */
class TestIntegration extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_should_implement_integration_interface() {
		$this->assertInstanceOf( \WPML\PB\Gutenberg\Integration::class, $this->getSubject() );
	}

	/**
	 * @test
	 * @group wpmlcore-6563
	 */
	public function it_should_add_hooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'render_block_data', [ $subject, 'convertReusableBlock' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @group wpmlcore-6565
	 */
	public function it_should_convert_reusable_block() {
		$block           = [ 'block with original ref' ];
		$converted_block = [ 'block with ref converted in the current lang' ];

		$reusable_blocks_translation = $this->getTranslation();
		$reusable_blocks_translation->method( 'convertBlock' )
			->with( $block )->willReturn( $converted_block );

		$subject = $this->getSubject( $reusable_blocks_translation );

		$this->assertEquals(
			$converted_block,
			$subject->convertReusableBlock( $block )
		);
	}

	private function getSubject( $reusable_blocks_translation = null ) {
		$reusable_blocks_translation = $reusable_blocks_translation
			? $reusable_blocks_translation : $this->getTranslation();

		return new Integration( $reusable_blocks_translation );
	}

	private function getTranslation() {
		return $this->getMockBuilder( Translation::class )
			->setMethods( [ 'convertBlock' ] )
			->disableOriginalConstructor()->getMock();
	}
}
