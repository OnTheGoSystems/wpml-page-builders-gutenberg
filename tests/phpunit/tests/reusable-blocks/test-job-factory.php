<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

/**
 * @group reusable-blocks
 */
class TestJobFactory extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_creates_job_factory_and_gets_translation_job() {

		$job_id   = 123;
		$job_data = [ 'some', 'data' ];

		$subject = new JobFactory();

		$tm_job_factory = \Mockery::mock( '\WPML_Translation_Job_Factory' );
		$tm_job_factory->shouldReceive( 'get_translation_job' )->with( $job_id )->andReturn( $job_data );
		\WP_Mock::userFunction( 'WPML\Container\make',
			[
				'args'   => [ '\WPML_Translation_Job_Factory' ],
				'return' => $tm_job_factory
			]
		);
		$this->assertEquals( $job_data, $subject->get_translation_job( $job_id ) );

	}
}

