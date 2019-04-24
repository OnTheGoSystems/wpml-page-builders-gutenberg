<?php

namespace WPML\PB\Gutenberg;

/**
 * @group reusable-blocks
 */
class Test_Reusable_Blocks extends \OTGS_TestCase {

	/**
	 * @test
	 * @group wpmlcore-6563
	 */
	public function it_should_get() {
		$GLOBALS['wp_version'] = '5.1.0';

		$post_id  = 123;
		$block_id = 456;

		$post = $this->getMockBuilder( 'WP_Post' )
			->disableOriginalConstructor()->getMock();
		$post->post_content = 'some block content';

		$blocks = [
			// Valid reusable block
			[
				'blockName' => 'core/block',
				'attrs'     => [ 'ref' => (string) $block_id ],
			],
			// Not wp block
			[
				'blockName' => 'not-wp/block',
				'attrs'     => [ 'ref' => 987 ],
			],
			// Not numerical ref
			[
				'blockName' => 'core/block',
				'attrs'     => [ 'ref' => 'something' ],
			],
			// No "ref"
			[
				'blockName' => 'core/block',
				'attrs'     => [ 'foo' => 'bar' ],
			],
			// No "attrs"
			[
				'blockName' => 'core/block',
			],
		];

		\WP_Mock::userFunction( 'get_post', [
			'args'   => [ $post_id ],
			'return' => $post,
		]);

		\WP_Mock::userFunction( 'parse_blocks', [
			'args'   => [ $post->post_content ],
			'return' => $blocks,
		]);

		$subject = new Reusable_Blocks();

		$this->assertEquals(
			[ $block_id ],
			$subject->get_ids( $post_id )
		);

		unset( $GLOBALS['wp_version'] );
	}
}
