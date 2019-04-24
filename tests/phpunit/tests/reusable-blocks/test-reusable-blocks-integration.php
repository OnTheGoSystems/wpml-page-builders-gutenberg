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

		\WP_Mock::expectFilterAdded( 'wpml_st_get_post_string_packages', [ $subject, 'add_reusable_block_packages' ], PHP_INT_MAX, 2 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @group wpmlcore-6563
	 */
	public function it_should_add_reusable_block_packages() {
		$packages          = [ 1230 => 'package 1230' ];
		$post_id           = 123;
		$reusable_block_id = 456;

		$reusable_block_packages = [ 4560 => 'package 4560' ];

		$expected_packages = $packages + $reusable_block_packages;

		\WP_Mock::onFilter( 'wpml_st_get_post_string_packages' )
			->with( [], $reusable_block_id )
			->reply( $reusable_block_packages );

		$reusable_blocks = $this->get_reusable_blocks();
		$reusable_blocks->method( 'get_ids' )->with( $post_id )->willReturn( [ $reusable_block_id ] );

		$subject = $this->get_subject( $reusable_blocks );

		\WP_Mock::userFunction( 'remove_filter', [
			'times' => 1,
			'args'  => [ 'wpml_st_get_post_string_packages', [ $subject, 'add_reusable_block_packages' ], PHP_INT_MAX ],
		]);

		\WP_Mock::expectFilterAdded( 'wpml_st_get_post_string_packages', [ $subject, 'add_reusable_block_packages' ], PHP_INT_MAX, 2 );

		$actual_packages = $subject->add_reusable_block_packages( $packages, $post_id );

		$this->assertCount( 2, $actual_packages );
		$this->assertArrayHasKey( 1230, $actual_packages );
		$this->assertArrayHasKey( 4560, $actual_packages );
		$this->assertEquals( $expected_packages, $actual_packages );
	}

	private function get_subject( $reusable_blocks = null ) {
		$reusable_blocks = $reusable_blocks ? $reusable_blocks : $this->get_reusable_blocks();
		return new Reusable_Blocks_Integration( $reusable_blocks );
	}

	private function get_reusable_blocks() {
		return $this->getMockBuilder( '\WPML\PB\Gutenberg\Reusable_Blocks' )
			->setMethods( [ 'get_ids' ] )
			->disableOriginalConstructor()->getMock();
	}
}
