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
		$integration1 = $this->get_integration( true );
		$integration2 = $this->get_integration( true );

		$subject = new Integration_Composite( [ $integration1, $integration2 ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @expectedException \Exception
	 */
	public function it_should_throw_an_exception_if_one_class_does_not_implement_integration_interface() {
		$integration1 = $this->get_integration( false );
		$integration2 = $this->getMockBuilder( 'SomeClass' )->getMock();

		$subject = new Integration_Composite( [ $integration1, $integration2 ] );

		$subject->add_hooks();
	}

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
