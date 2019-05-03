<?php

namespace WPML\PB\Gutenberg;

/**
 * @group reusable-blocks
 */
class Test_Reusable_Blocks_Admin_Integration extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_should_add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'wpml_send_jobs_batch', [ $subject, 'add_reusable_elements' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @group wpmlcore-6580
	 */
	public function it_should_add_blocks() {
		$original_batch = $this->get_batch();
		$expected_batch = $this->get_batch();

		$batch_handler = $this->get_batch_handler();
		$batch_handler->method( 'add_blocks' )->with( $original_batch )->willReturn( $expected_batch );

		$subject = $this->get_subject( $batch_handler );

		$this->assertSame(
			$expected_batch,
			$subject->add_reusable_elements( $original_batch )
		);
	}

	private function get_subject( $batch_handler = null ) {
		$batch_handler = $batch_handler ? $batch_handler : $this->get_batch_handler();
		return new Reusable_Blocks_Admin_Integration( $batch_handler );
	}

	private function get_batch_handler() {
		return $this->getMockBuilder( Reusable_Blocks_Batch_Handler::class )
			->setMethods( [ 'add_blocks' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function get_batch() {
		return $this->getMockBuilder( '\WPML_TM_Translation_Batch' )
		     ->disableOriginalConstructor()->getMock();
	}
}
