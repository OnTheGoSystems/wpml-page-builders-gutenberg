<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

/**
 * @group reusable-blocks
 */
class TestNotice extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_should_add_notice_with_reusable_blocks_job_links() {
		$job_id_1 = '123';
		$job_id_2 = '456';

		$job_ids = [ $job_id_1, $job_id_2 ];

		$first_line_plural = 'We automatically created translation jobs for the reusable blocks:';

		\WP_Mock::userFunction( '_n', [
			'args' => [
				'We automatically created a translation job for the reusable block:',
				$first_line_plural,
				count( $job_ids ),
				'sitepress'
			],
			'return' => $first_line_plural,
		] );

		$expected_text = '<p>' . $first_line_plural . '</p>';
		$expected_text .= '<ul><li>'. $this->getLink( $job_id_1 ) . '</li><li>' . $this->getLink( $job_id_2 ) . '</li></ul>';

		$notices = $this->getExpectedNoticesMock( $expected_text );

		$links = \collect( [ $this->getLink( $job_id_1 ), $this->getLink( $job_id_2 ) ] );

		$job_links = $this->getJobLinksMock( $job_ids, $links );

		$subject = $this->getSubject( $notices, $job_links );

		$subject->addJobsCreatedAutomatically( $job_ids );
	}

	/**
	 * @param \WPML_Notices $notices
	 * @param JobLinks      $job_links
	 *
	 * @return Notice
	 */
	private function getSubject( \WPML_Notices $notices, JobLinks $job_links ) {
		return new Notice( $notices, $job_links );
	}

	/**
	 * @param string $expected_text
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|\WPML_Notice
	 */
	private function getExpectedNoticesMock( $expected_text ) {
		$notice = $this->getMockBuilder( '\WPML_Notice' )
		               ->setMethods( [ 'set_flash', 'set_restrict_to_screen_ids', 'set_css_class_types' ] )
		               ->disableOriginalConstructor()->getMock();
		$notice->expects( $this->once() )->method( 'set_flash' )->with( true );
		$notice->expects( $this->once() )->method( 'set_restrict_to_screen_ids' )->with( [ 'post', 'edit-post' ] );
		$notice->expects( $this->once() )->method( 'set_css_class_types' )->with( 'notice-info' );

		$notices = $this->getMockBuilder( '\WPML_Notices' )
		                ->setMethods( [ 'create_notice', 'add_notice' ] )
		                ->disableOriginalConstructor()->getMock();
		$notices->method( 'create_notice' )
		        ->with( 'automatic-jobs', $expected_text, Notice::class )
		        ->willReturn( $notice );
		$notices->expects( $this->once() )->method( 'add_notice' )->with( $notice );

		return $notices;
	}

	/**
	 * @param array                          $job_ids
	 * @param \Illuminate\Support\Collection $links
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|JobLinks
	 */
	private function getJobLinksMock( array $job_ids, \Illuminate\Support\Collection $links ) {
		$job_links = $this->getMockBuilder( JobLinks::class )
			->setMethods( [ 'get' ] )
			->disableOriginalConstructor()->getMock();
		$job_links->method( 'get' )->with( $job_ids )->willReturn( $links );

		return $job_links;
	}

	/**
	 * @param int $job_id
	 *
	 * @return string
	 */
	private function getLink( $job_id ) {
		return 'the_link_for_job_id_' . $job_id;
	}
}
