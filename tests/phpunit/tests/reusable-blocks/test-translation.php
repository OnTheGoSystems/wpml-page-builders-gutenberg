<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

/**
 * @group reusable-blocks
 */
class TestTranslation extends \OTGS_TestCase {

	/**
	 * @test
	 * @dataProvider dp_invalid_reusable_blocks
	 * @group wpmlcore-6565
	 *
	 * @param array $block
	 */
	public function it_should_not_convert_block_if_not_a_reusable( $block ) {
		$sitepress = $this->getSitepress();
		$sitepress->expects( $this->never() )->method( 'get_object_id' );

		$subject = $this->getSubject( $sitepress );

		$this->assertEquals(
			$block,
			$subject->convertBlock( $block )
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

		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_object_id' )
				->with( $original_block_id, Translation::POST_TYPE, true, $lang )
				->willReturn( $translated_block_id );

		$subject = $this->getSubject( $sitepress );

		$this->assertEquals(
			$converted_block,
			$subject->convertBlock( $block, $lang )
		);
	}

	/**
	 * @test
	 * @dataProvider dp_post_details
	 * @group wpmlcore-6689
	 *
	 * @param null|\stdClass $post_details
	 * @param null|string    $expected_lang
	 */
	public function it_should_get_source_lang_from_post_id( $post_details, $expected_lang ) {
		$post_id   = 123;
		$post_type = 'page';

		\WP_Mock::userFunction( 'get_post_type', [
			'args'   => [ $post_id ],
			'return' => $post_type,
		] );

		$sitepress = $this->getSitepress();
		$sitepress->method( 'get_element_language_details' )
			->with( $post_id, 'post_' . $post_type )->willReturn( $post_details );

		$subject = $this->getSubject( $sitepress );

		$this->assertEquals( $expected_lang, $subject->getSourceLang( $post_id ) );
	}
	
	public function dp_post_details() {
		return [
			[ (object) [ 'source_language_code' => 'fr' ], 'fr' ],
			[ false, null ],
		];
	}
	
	private function getSubject( $sitepress = null ) {
		$sitepress = $sitepress ? $sitepress : $this->getSitepress();
		return new Translation( $sitepress );
	}

	private function getSitepress() {
		return $this->getMockBuilder( \SitePress::class )
			->setMethods( [ 'get_element_language_details', 'get_object_id' ] )
			->disableOriginalConstructor()->getMock();
	}
}
