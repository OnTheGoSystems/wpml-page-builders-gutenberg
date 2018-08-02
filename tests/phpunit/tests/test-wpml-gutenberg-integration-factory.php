<?php

/**
 * Class Test_WPML_Gutenberg_Integration_Factory
 *
 * @group page-builders
 * @group gutenberg
 */
class Test_WPML_Gutenberg_Integration_Factory extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_creates() {
		$factory = new WPML_Gutenberg_Integration_Factory();

		$this->assertInstanceOf( 'WPML_Gutenberg_Integration', $factory->create() );
	}
}
