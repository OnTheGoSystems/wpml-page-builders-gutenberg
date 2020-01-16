<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

/**
 * @group convert-ids-in-block
 */
class TestBase extends \OTGS_TestCase {

	public function tearDown() {
		unset( $GLOBALS['sitepress'] );
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function itShouldConvertAndNotAlterBlock() {
		$block = [ 'the-block' ];

		$subject = new Base();

		$this->assertEquals( $block, $subject->convert( $block ) );
	}

	/**
	 * @test
	 */
	public function itShouldConvertOneId() {
		$originalId  = 123;
		$convertedId = 456;
		$slug        = 'page';
		$type        = 'post';

		$this->mockDisplayAsTranslated( $type, $slug, false );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $originalId, $slug ],
			'return' => $convertedId,
		] );

		$this->assertSame( $convertedId, Base::convertIds( $originalId, $slug, $type ) );
	}

	/**
	 * @test
	 */
	public function itShouldConvertMultipleIds() {
		$originalId1  = 123;
		$convertedId1 = 456;
		$originalId2  = 1000;
		$convertedId2 = 1001;
		$slug         = 'page';
		$type         = 'post';

		$this->mockDisplayAsTranslated( $type, $slug, false );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $originalId1, $slug ],
			'return' => $convertedId1,
		] );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $originalId2, $slug ],
			'return' => $convertedId2,
		] );

		$this->assertSame(
			[ $convertedId1, $convertedId2 ],
			Base::convertIds( [ $originalId1, $originalId2 ], $slug, $type )
		);
	}

	/**
	 * @test
	 */
	public function itShouldConvertOneIdAndReturnZeroInsteadOfNull() {
		$originalId  = 123;
		$slug        = 'page';
		$type        = 'post';

		$this->mockDisplayAsTranslated( $type, $slug, false );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $originalId, $slug ],
			'return' => null,
		] );

		$this->assertSame( 0, Base::convertIds( $originalId, $slug, $type ) );
	}

	/**
	 * @test
	 */
	public function itShouldConvertOneTaxonomyTermWithDisplayedAsTranslated() {
		$originalId  = 123;
		$convertedId = 456;
		$slug        = 'city';
		$type        = 'taxonomy';

		$this->mockDisplayAsTranslated( $type, $slug, true );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $originalId, $slug ],
			'return' => $convertedId,
		] );

		$this->assertSame( $convertedId, Base::convertIds( $originalId, $slug, $type ) );
	}

	/**
	 * @test
	 */
	public function itShouldConvertAndReturnOriginalIfConvertedIsNullAndDisplayedAsTranslated() {
		$originalId = 123;
		$slug       = 'city';
		$type       = 'taxonomy';

		$this->mockDisplayAsTranslated( $type, $slug, true );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $originalId, $slug ],
			'return' => null,
		] );

		$this->assertSame( $originalId, Base::convertIds( $originalId, $slug, $type ) );
	}

	private function mockDisplayAsTranslated( $type, $slug, $isDisplayAsTranslated ) {
		global $sitepress;

		$sitepress = $this->getMockBuilder( '\SitePress' )
			->setMethods( [ 'is_display_as_translated_post_type', 'is_display_as_translated_taxonomy' ] )
			->disableOriginalConstructor()->getMock();

		if ( 'post' === $type ) {
			$sitepress->method( 'is_display_as_translated_post_type' )
				->with( $slug )
				->willReturn( $isDisplayAsTranslated );
		} else {
			$sitepress->method( 'is_display_as_translated_taxonomy' )
				->with( $slug )
				->willReturn( $isDisplayAsTranslated );
		}
	}
}