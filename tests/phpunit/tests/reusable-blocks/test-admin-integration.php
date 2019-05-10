<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

/**
 * @group reusable-blocks
 */
class TestAdminIntegration extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_should_add_hooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'wpml_send_jobs_batch', [ $subject, 'addBlocksToBatch' ] );
		\WP_Mock::expectActionAdded( 'wpml_tm_add_to_basket', [ $subject, 'addBlocksToBasket' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_add_hooks_when_submitting_the_basket() {
		$_POST = [ 'action' => 'send_basket_item' ];

		$subject = $this->getSubject();

		\WP_Mock::expectFilterNotAdded( 'wpml_send_jobs_batch', [ $subject, 'addBlocksToBatch' ] );
		\WP_Mock::expectActionAdded( 'wpml_tm_add_to_basket', [ $subject, 'addBlocksToBasket' ] );

		$subject->add_hooks();

		unset( $_POST );
	}

	/**
	 * @test
	 * @group wpmlcore-6580
	 */
	public function it_should_add_blocks_to_batch() {
		$original_batch = $this->getBatch();
		$expected_batch = $this->getBatch();

		$manage_batch = $this->getManageBatch();
		$manage_batch->method( 'addBlocks' )->with( $original_batch )->willReturn( $expected_batch );

		$subject = $this->getSubject( $manage_batch );

		$this->assertSame(
			$expected_batch,
			$subject->addBlocksToBatch( $original_batch )
		);
	}

	/**
	 * @test
	 * @group wpmlcore-6590
	 */
	public function it_should_add_blocks_to_basket() {
		$data = [ 'some basket data' ];

		$manage_basket = $this->getManageBasket();
		$manage_basket->expects( $this->once() )->method( 'addBlocks' )->with( $data );

		$subject = $this->getSubject( null, $manage_basket );

		$subject->addBlocksToBasket( $data );
	}

	private function getSubject( $manage_batch = null, $manage_basket = null ) {
		$manage_batch  = $manage_batch ? $manage_batch : $this->getManageBatch();
		$manage_basket = $manage_basket ? $manage_basket : $this->getManageBasket();
		return new AdminIntegration( $manage_batch, $manage_basket );
	}

	private function getManageBatch() {
		return $this->getMockBuilder( ManageBatch::class )
			->setMethods( [ 'addBlocks' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function getManageBasket() {
		return $this->getMockBuilder( ManageBasket::class )
            ->setMethods( [ 'addBlocks' ] )
            ->disableOriginalConstructor()->getMock();
	}

	private function getBatch() {
		return $this->getMockBuilder( '\WPML_TM_Translation_Batch' )
		     ->disableOriginalConstructor()->getMock();
	}
}
