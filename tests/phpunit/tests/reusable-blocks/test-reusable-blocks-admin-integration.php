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

		\WP_Mock::expectFilterAdded( 'wpml_send_jobs_batch', [ $subject, 'add_blocks_to_batch' ] );
		\WP_Mock::expectActionAdded( 'wpml_tm_add_to_basket', [ $subject, 'add_blocks_to_basket' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_add_hooks_when_submitting_the_basket() {
		$_POST = [ 'action' => 'send_basket_item' ];

		$subject = $this->get_subject();

		\WP_Mock::expectFilterNotAdded( 'wpml_send_jobs_batch', [ $subject, 'add_reusable_elements' ] );
		\WP_Mock::expectActionAdded( 'wpml_tm_add_to_basket', [ $subject, 'add_blocks_to_basket' ] );

		$subject->add_hooks();

		unset( $_POST );
	}

	/**
	 * @test
	 * @group wpmlcore-6580
	 */
	public function it_should_add_blocks_to_batch() {
		$original_batch = $this->get_batch();
		$expected_batch = $this->get_batch();

		$batch_handler = $this->get_batch_handler();
		$batch_handler->method( 'add_blocks' )->with( $original_batch )->willReturn( $expected_batch );

		$subject = $this->get_subject( $batch_handler );

		$this->assertSame(
			$expected_batch,
			$subject->add_blocks_to_batch( $original_batch )
		);
	}

	/**
	 * @test
	 * @group wpmlcore-6590
	 */
	public function it_should_add_blocks_to_basket() {
		$data = [ 'some basket data' ];

		$basket_handler = $this->get_basket_handler();
		$basket_handler->expects( $this->once() )->method( 'add_blocks' )->with( $data );

		$subject = $this->get_subject( null, $basket_handler );

		$subject->add_blocks_to_basket( $data );
	}

	private function get_subject( $batch_handler = null, $basket_handler = null ) {
		$batch_handler  = $batch_handler ? $batch_handler : $this->get_batch_handler();
		$basket_handler = $basket_handler ? $basket_handler : $this->get_basket_handler();
		return new Reusable_Blocks_Admin_Integration( $batch_handler, $basket_handler );
	}

	private function get_batch_handler() {
		return $this->getMockBuilder( Reusable_Blocks_Batch_Handler::class )
			->setMethods( [ 'add_blocks' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function get_basket_handler() {
		return $this->getMockBuilder( Reusable_Blocks_Basket_Handler::class )
		            ->disableOriginalConstructor()->getMock();
	}

	private function get_batch() {
		return $this->getMockBuilder( '\WPML_TM_Translation_Batch' )
		     ->disableOriginalConstructor()->getMock();
	}
}
