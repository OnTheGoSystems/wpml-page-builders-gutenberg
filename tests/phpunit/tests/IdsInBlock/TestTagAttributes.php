<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

/**
 * @group convert-ids-in-block
 */
class TestTagAttributes extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldConvert() {
		$id          = 123;
		$convertedId = 456;
		$class = 'my-class';
		$attribute = 'data-my-id';
		$type = 'page';

		$config = [
			[ 'xpath' => '//*[contains(@class, "' . $class . '")]/@' . $attribute, 'type' => $type ],
		];

		$getBlock = function( $id ) use ( $class, $attribute ) {
			$html = '<div class="something ' . $class . '" ' . $attribute . '="' . $id . '"></div>';

			return [
				'blockName'    => 'some-name',
				'attrs'        => [],
				'innerBlocks'  => [],
				'innerHTML'    => $html,
				'innerContent' => [ $html ],
			];
		};

		$block         = $getBlock( $id );
		$expectedBlock = $getBlock( $convertedId );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $id, $type ],
			'return' => $convertedId,
		] );

		$subject = new TagAttributes( $config );

		$this->assertEquals( $expectedBlock, $subject->convert( $block ) );
	}
}