<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

/**
 * @group reusable-blocks
 */
class TestJobLinks extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_should_get_job_links() {
		$post_job_id    = '100';
		$block1_job_id  = '101';
		$block2_job_id  = '102';
		$invalid_job_id = '103';

		$job_ids = [
			$post_job_id,
			$block1_job_id,
			$block2_job_id,
			$invalid_job_id,
		];

		$block1_job = $this->getJob( $block1_job_id, 'Block 1', 'wp_block' );
		$block2_job = $this->getJob( $block2_job_id, 'Block 2', 'wp_block' );

		$jobs_map = [
			[ $post_job_id, $this->getJob( $post_job_id, 'The post', 'post' ) ],
			[ $block1_job_id, $block1_job ],
			[ $block2_job_id, $block2_job ],
			[ $invalid_job_id, false ],
		];

		$job_factory = $this->getJobFactory( $jobs_map );

		\WP_Mock::passthruFunction( 'admin_url' );

		$this->addEditURLFilterExpectation( [ $block1_job_id, $block2_job_id ] );

		$subject = $this->getSubject( $job_factory );

		$links = $subject->get( $job_ids );

		$this->assertCount( 2, $links );
		$this->assertEquals( $this->getExpectedJobLink( $block1_job ), $links->get( 1 ) );
		$this->assertEquals( $this->getExpectedJobLink( $block2_job ), $links->get( 2 ) );
	}

	/**
	 * @param \WPML_Translation_Job_Factory $job_factory
	 *
	 * @return JobLinks
	 */
	private function getSubject( \WPML_Translation_Job_Factory $job_factory ) {
		return new JobLinks( $job_factory );
	}

	/**
	 * @param array $jobs_map
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|\WPML_Translation_Job_Factory
	 */
	private function getJobFactory( array $jobs_map ) {
		$factory = $this->getMockBuilder( '\WPML_Translation_Job_Factory' )
			->setMethods( [ 'get_translation_job' ] )
			->disableOriginalConstructor()->getMock();
		$factory->method( 'get_translation_job' )->willReturnMap( $jobs_map );

		return $factory;
	}

	/**
	 * @param int $id
	 * @param string $title
	 * @param string $post_type
	 *
	 * @return \stdClass
	 */
	private function getJob( $id, $title, $post_type ) {
		return (object) [
			'job_id'             => $id,
			'title'              => $title,
			'original_post_type' => 'post_' . $post_type,
		];
	}

	/**
	 * @param int $job_id
	 *
	 * @return string
	 */
	private function getEditURL( $job_id ) {
		return 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&job_id=' . $job_id;
	}

	/**
	 * @param $job_id
	 *
	 * @return string
	 */
	private function getEditURLFiltered( $job_id ) {
		return 'filtered_url_for_job_id_' . $job_id;
	}

	private function addEditURLFilterExpectation( array $job_ids ) {
		foreach ( $job_ids as $job_id ) {
			\WP_Mock::onFilter( 'icl_job_edit_url' )
			        ->with( $this->getEditURL( $job_id ), $job_id )
			        ->reply( $this->getEditURLFiltered( $job_id ) );
		}
	}

	/**
	 * @param \stdClass $job
	 *
	 * @return string
	 */
	private function getExpectedJobLink( \stdClass $job ) {
		return '<a href="' . $this->getEditURLFiltered( $job->job_id ) . '" target="_blank">' . $job->title . '</a>';
	}
}
