<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

/**
 * @group reusable-blocks
 */
class TestBasket extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_creates_basket_and_passes_on_data() {

		$data    = [ 'some', 'data' ];
		$subject = new Basket();

		$tm_basket = \Mockery::mock( '\WPML_Translation_Basket' );
		$tm_basket->shouldReceive( 'update_basket' )->once()->with( $data );
		\WP_Mock::userFunction( 'WPML\Container\make',
			[
				'args'   => [ '\WPML_Translation_Basket' ],
				'return' => $tm_basket
			]
		);
		$subject->update_basket( $data );

	}
}

