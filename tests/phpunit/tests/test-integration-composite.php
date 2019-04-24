<?php

namespace WPML\PB\Gutenberg;

class Test_Integration_Composite extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_should_implement_integration_interface() {
		$this->assertInstanceOf( Integration::class, new Integration_Composite( [] ) );
	}

	/**
	 * @test
	 */
	public function it_should_add_hooks() {
		$subject = new Integration_Composite();

		$subject->add( $this->get_integration( true ) );
		$subject->add( $this->get_integration( true ) );

		$subject->add_hooks();
	}

	/**
	 * @param bool $expect_add_hooks
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|Integration
	 */
	private function get_integration( $expect_add_hooks ) {
		$integration = $this->getMockBuilder( Integration::class )
		                    ->setMethods( [ 'add_hooks' ] )
		                    ->disableOriginalConstructor()->getMock();

		if ( $expect_add_hooks ) {
			$integration->expects( $this->once() )->method( 'add_hooks' );
		}

		return $integration;
	}
}
