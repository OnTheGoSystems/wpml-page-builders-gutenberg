<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

use WPML\FP\Fns;

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
		\WP_Mock::expectFilterAdded( 'render_block', Fns::withoutRecursion( Fns::identity(), [ $subject, 'reRenderInnerReusableBlock' ] ), 10, 2 );

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

	/**
	 * @test
	 * @group wpmlcore-7651
	 */
	public function itShouldNotReRenderIfNotAReusableBlock() {
		$blockContent = 'The original block content';
		$block        = [ 'foo' => 'bar' ];

		$translation = $this->getTranslation();
		$translation->expects( $this->never() )->method( 'convertBlock' );

		\WP_Mock::userFunction( 'render_block' )->never();

		$subject = $this->getSubject( $translation );

		$this->assertEquals(
			$blockContent,
			$subject->reRenderInnerReusableBlock( $blockContent, $block )
		);
	}

	/**
	 * @test
	 * @group wpmlcore-7651
	 */
	public function itShouldNotReRenderIfBlockAlreadyConverted() {
		$id           = 123;
		$blockContent = 'The original block content';
		$block        = [
			'attrs' => [
				'ref' => $id,
			],
		];

		$translation = $this->getTranslation();
		$translation->method( 'convertBlock' )
			->with( $block )
			->willReturnArgument( 0 );

		\WP_Mock::userFunction( 'render_block' )->never();

		$subject = $this->getSubject( $translation );

		$this->assertEquals(
			$blockContent,
			$subject->reRenderInnerReusableBlock( $blockContent, $block )
		);
	}

	/**
	 * @test
	 * @group wpmlcore-7651
	 */
	public function itShouldReRenderBlock() {
		$id                = 123;
		$convertedId       = 456;
		$blockContent      = 'The original block content';
		$translatedContent = 'The translated content';

		$getBlock = function( $id ) {
			return [
				'attrs' => [
					'ref' => $id,
				],
			];
		};

		$block          = $getBlock( $id );
		$convertedBlock = $getBlock( $convertedId );

		$translation = $this->getTranslation();
		$translation->method( 'convertBlock' )
			->with( $block )
			->willReturn( $convertedBlock );

		\WP_Mock::userFunction( 'render_block' )
			->with( $convertedBlock )
			->andReturn( $translatedContent );

		$subject = $this->getSubject( $translation );

		$this->assertEquals(
			$translatedContent,
			$subject->reRenderInnerReusableBlock( $blockContent, $block )
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
