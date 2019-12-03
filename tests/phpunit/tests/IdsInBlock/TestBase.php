<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

/**
 * @group convert-ids-in-block
 */
class TestBase extends \OTGS_TestCase {

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
		$type        = 'page';

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $originalId, $type ],
			'return' => $convertedId,
		] );

		$this->assertSame( $convertedId, Base::convertIds( $originalId, $type ) );
	}

	/**
	 * @test
	 */
	public function itShouldConvertMultipleIds() {
		$originalId1  = 123;
		$convertedId1 = 456;
		$originalId2  = 1000;
		$convertedId2 = 1001;
		$type        = 'page';

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $originalId1, $type ],
			'return' => $convertedId1,
		] );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $originalId2, $type ],
			'return' => $convertedId2,
		] );

		$this->assertSame(
			[ $convertedId1, $convertedId2 ],
			Base::convertIds( [ $originalId1, $originalId2 ], $type )
		);
	}

	/**
	 * @test
	 */
	public function itShouldConvertOneIdAndReturnZeroInsteadOfNull() {
		$originalId  = 123;
		$type        = 'page';

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'   => [ $originalId, $type ],
			'return' => null,
		] );

		$this->assertSame( 0, Base::convertIds( $originalId, $type ) );
	}
}