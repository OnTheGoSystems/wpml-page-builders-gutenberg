<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

/**
 * @group convert-ids-in-block
 */
class TestBlockAttributes extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldConvert() {
		$name        = 'foo';
		$id          = 123;
		$convertedId = 456;
		$type        = 'page';

		$config = [
			[ 'name' => $name, 'type' => $type ],
		];

		$getBlock = function( $id ) use ( $name ) {
			return [
				'attrs' => [
					$name       => $id,
					'something' => 'else',
				],
			];
		};

		$block         = $getBlock( $id );
		$expectedBlock = $getBlock( $convertedId );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $id, $type ],
			'return' => $convertedId,
		] );

		\Mockery::mock( 'WP_Block_Parser_Block' );

		$subject = new BlockAttributes( $config );

		$this->assertEquals( $expectedBlock, $subject->convert( $block ) );
	}
}
