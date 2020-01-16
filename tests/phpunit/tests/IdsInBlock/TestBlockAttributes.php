<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

/**
 * @group convert-ids-in-block
 */
class TestBlockAttributes extends \OTGS_TestCase {

	public function tearDown() {
		unset( $GLOBALS['sitepress'] );
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function itShouldConvert() {
		$name        = 'foo';
		$id          = 123;
		$convertedId = 456;
		$slug        = 'page';

		$config = [
			[ 'name' => $name, 'slug' => $slug, 'type' => 'post' ],
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

		$this->mockConvertIds( $id, $convertedId, $slug );

		\Mockery::mock( 'WP_Block_Parser_Block' );

		$subject = new BlockAttributes( $config );

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
