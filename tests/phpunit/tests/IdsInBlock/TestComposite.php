<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

/**
 * @group convert-ids-in-block
 */
class TestComposite extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldConvert() {
		$initialBlock = [ 'initial-block' ];
		$blockAfterA  = [ 'block-after-A' ];
		$finalBlock   = [ 'final-block' ];

		$converters = [
			$this->getConverter( $initialBlock, $blockAfterA ),
			$this->getConverter( $blockAfterA, $finalBlock ),
		];

		$subject = new Composite( $converters );

		$this->assertEquals(
			$finalBlock,
			$subject->convert( $initialBlock )
		);
	}

	private function getConverter( array $blockIn, array $blockOut ) {
		$converter = $this->getMockBuilder( Base::class )
		     ->setMethods( [ 'convert' ] )
		     ->disableOriginalConstructor()->getMock();
		$converter->method( 'convert' )->with( $blockIn )->willReturn( $blockOut );

		return $converter;
	}
}
