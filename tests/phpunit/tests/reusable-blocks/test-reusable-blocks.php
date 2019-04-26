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
	 * @group wpmlcore-6564
	 */
	public function it_should_get_ids_from_job() {
		$post_id            = 123;
		$post_package_id    = 1230;
		$post_package       = (object) [ 'post_id' => $post_id ];
		$block_id           = 456;
		$block_package_id   = 4560;
		$block_package      = (object) [ 'post_id' => $block_id ];
		$missing_package_id = 789;

		$job = (object) [
			'original_doc_id' => $post_id,
			'elements' => [
				(object) [ 'field_type' => 'title' ],
				(object) [ 'field_type' => 'body' ],
				// 2 string fields from the main post
				(object) [ 'field_type' => 'package-string-' . $post_package_id . '-999' ],
				(object) [ 'field_type' => 'package-string-' . $post_package_id . '-998' ],
				// 2 string fields from the reusable block
				(object) [ 'field_type' => 'package-string-' . $block_package_id . '-997' ],
				(object) [ 'field_type' => 'package-string-' . $block_package_id . '-996' ],
				// field from a missing package
				(object) [ 'field_type' => 'package-string-' . $missing_package_id . '-995' ],
			],
		];

		\WP_Mock::onFilter( 'wpml_st_get_string_package' )
		        ->with( false, $block_package_id )
		        ->reply( $block_package );

		\WP_Mock::onFilter( 'wpml_st_get_string_package' )
		        ->with( false, $post_package_id )
		        ->reply( $post_package );

		$subject = new Reusable_Blocks();

		$this->assertEquals(
			[ $block_id ],
			$subject->get_ids_from_job( $job )
		);
	}
}
