<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

/**
 * @group convert-ids-in-block
 */
class TestTagAttributes extends \OTGS_TestCase {

	public function tearDown() {
		unset( $GLOBALS['sitepress'] );
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function itShouldConvert() {
		$id          = 123;
		$convertedId = 456;
		$class       = 'my-class';
		$attribute   = 'data-my-id';
		$slug        = 'page';

		$config = [
			[
				'xpath' => '//*[contains(@class, "' . $class . '")]/@' . $attribute,
				'slug'  => $slug,
				'type'  => 'post',
			],
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

		$this->mockConvertIds( $id, $convertedId, $slug );

		$subject = new TagAttributes( $config );

		$this->assertEquals( $expectedBlock, $subject->convert( $block ) );
	}

	private function mockConvertIds( $id, $convertedId, $slug ) {
		global $sitepress;

		$sitepress = $this->getMockBuilder( '\SitePress' )
			->setMethods( [ 'is_display_as_translated_post_type' ] )
			->disableOriginalConstructor()->getMock();

		$sitepress->method( 'is_display_as_translated_post_type' )
			->with( $slug )
			->willReturn( false );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $id, $slug ],
			'return' => $convertedId,
		] );
	}
}