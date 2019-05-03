<?php

namespace WPML\PB\Gutenberg;

/**
 * @group reusable-blocks
 */
class Test_Reusable_Blocks extends \OTGS_TestCase {

	/**
	 * @test
	 * @dataProvider dp_invalid_reusable_blocks
	 * @group wpmlcore-6565
	 *
	 * @param array $block
	 */
	public function it_should_return_false_if_NOT_reusable_block( $block ) {
		$this->assertFalse( Reusable_Blocks::is_reusable( $block ) );

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
	public function it_should_return_true_if_is_reusable_block() {
		$block = [
			'blockName' => 'core/block',
			'attrs'     => [ 'ref' => 987 ],
		];

		$this->assertTrue( Reusable_Blocks::is_reusable( $block ) );
	}

	/**
	* @test
	* @group wpmlcore-6580
	*/
	public function it_should_get_ids_from_post() {
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
			$subject->get_ids_from_post( $post_id )
		);
		unset( $GLOBALS['wp_version'] );
	}

	/**
	 * @test
	 * @group wpmlcore-6565
	 */
	public function it_should_return_an_empty_array_if_post_does_not_exist() {
		$post_id  = 123;

		\WP_Mock::userFunction( 'get_post', [
			'args'   => [ $post_id ],
			'return' => null,
		]);

		$subject = new Reusable_Blocks();

		$this->assertEmpty( $subject->get_ids_from_post( $post_id ) );
	}
}
