<?php

namespace WPML\PB\Gutenberg;

/**
 * @group reusable-blocks
 */
class Test_Reusable_Blocks_Translation extends \OTGS_TestCase {

	/**
	 * @test
	 * @dataProvider dp_invalid_reusable_blocks
	 * @group wpmlcore-6565
	 *
	 * @param array $block
	 */
	public function it_should_not_convert_block_if_not_a_reusable( $block ) {
		$sitepress = $this->get_sitepress();
		$sitepress->expects( $this->never() )->method( 'get_object_id' );

		$subject = $this->get_subject( $sitepress );

		$this->assertEquals(
			$block,
			$subject->convert_block( $block )
		);
	}

	public static function dp_invalid_reusable_blocks() {
		return [
			'not reusable block' => [
				[
					'blockName' => 'not-wp/block',
					'attrs'     => [ 'ref' => 987 ],
				],
			],
			'not numerical ref' => [
				[
					'blockName' => 'core/block',
					'attrs'     => [ 'ref' => 'something' ],
				],
			],
			'no ref' => [
				[
					'blockName' => 'core/block',
					'attrs'     => [ 'foo' => 'bar' ],
				],
			],
			'no attrs' => [
				[
					'blockName' => 'core/block',
				],
			],
		];
	}

	/**
	 * @test
	 * @group wpmlcore-6565
	 */
	public function it_should_convert_block() {
		$lang                = 'fr';
		$original_block_id   = 123;
		$translated_block_id = 456;

		$block = [
			'blockName' => 'core/block',
			'attrs'     => [
				'ref' => $original_block_id	,
			],
		];

		$converted_block = [
			'blockName' => 'core/block',
			'attrs'     => [
				'ref' => $translated_block_id	,
			],
		];

		$sitepress = $this->get_sitepress();
		$sitepress->method( 'get_object_id' )
				->with( $original_block_id, Reusable_Blocks_Translation::POST_TYPE, true, $lang )
				->willReturn( $translated_block_id );

		$subject = $this->get_subject( $sitepress );

		$this->assertEquals(
			$converted_block,
			$subject->convert_block( $block, $lang )
		);
	}
	
	private function get_subject( $sitepress = null ) {
		$sitepress = $sitepress ? $sitepress : $this->get_sitepress();
		return new Reusable_Blocks_Translation( $sitepress );
	}

	private function get_sitepress() {
		return $this->getMockBuilder( \SitePress::class )
			->setMethods( [ 'set_element_language_details', 'get_object_id' ] )
			->disableOriginalConstructor()->getMock();
	}
}
