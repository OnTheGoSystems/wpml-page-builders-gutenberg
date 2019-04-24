<?php

namespace WPML\PB\Gutenberg;

/**
 * @group reusable-blocks
 */
class Test_Reusable_Blocks_Integration extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_should_implement_integration_interface() {
		$this->assertInstanceOf( Integration::class, $this->get_subject() );
	}

	/**
	 * @test
	 * @group wpmlcore-6563
	 */
	public function it_should_add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'render_block_data', [ $subject, 'convert_reusable_block' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @group wpmlcore-6565
	 */
	public function it_should_convert_reusable_block() {
		$block           = [ 'block with original ref' ];
		$converted_block = [ 'block with ref converted in the current lang' ];

		$reusable_blocks_translation = $this->get_reusable_blocks_translation();
		$reusable_blocks_translation->method( 'convert_block' )
			->with( $block )->willReturn( $converted_block );

		$subject = $this->get_subject( $reusable_blocks_translation );

		$this->assertEquals(
			$converted_block,
			$subject->convert_reusable_block( $block )
		);
	}

	private function get_subject( $reusable_blocks_translation = null ) {
		$reusable_blocks_translation = $reusable_blocks_translation
			? $reusable_blocks_translation : $this->get_reusable_blocks_translation();

		return new Reusable_Blocks_Integration( $reusable_blocks_translation );
	}

	private function get_reusable_blocks_translation() {
		return $this->getMockBuilder( '\WPML\PB\Gutenberg\Reusable_Blocks_Translation' )
			->setMethods( [ 'create_post', 'convert_block' ] )
			->disableOriginalConstructor()->getMock();
	}
}
