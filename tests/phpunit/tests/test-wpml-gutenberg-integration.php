<?php

/**
 * Class Test_WPML_Gutenberg_Integration
 *
 * @group page-builders
 * @group gutenberg
 */
class Test_WPML_Gutenberg_Integration extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_adds_hooks() {
		$subject = new WPML_Gutenberg_Integration();
		$subject->add_hooks();
	}
}