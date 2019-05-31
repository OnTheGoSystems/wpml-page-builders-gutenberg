<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

/**
 * @group reusable-blocks
 */
class TestNotice extends \OTGS_TestCase {

	const POST_ID   = 123;
	const POST_TYPE = 'page';
	
	/**
	 * @test
	 * @dataProvider dp_add_notice_with_reusable_blocks_job_links
	 *
	 * @param string $return_url
	 * @param array  $restricted_screen_ids
	 */
	public function it_should_add_notice_with_reusable_blocks_job_links( $return_url, array $restricted_screen_ids ) {
		if ( $return_url ) {
			$_GET['return_url'] = $return_url;
		}
		
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

		\WP_Mock::userFunction( 'wpml_parse_url', [
			'return' => function( $url, $component ) {
				return parse_url( $url, $component );
			},
		] );

		\WP_Mock::userFunction( 'get_post_type', [
			'args'   => [ self::POST_ID ],
			'return' => self::POST_TYPE,
		] );

		$expected_text = '<p>' . $first_line_plural . '</p>';
		$expected_text .= '<ul><li>'. $this->getLink( $job_id_1 ) . '</li><li>' . $this->getLink( $job_id_2 ) . '</li></ul>';

		$notices = $this->getExpectedNoticesMock( $expected_text, $restricted_screen_ids );

		$links = \collect( [ $this->getLink( $job_id_1 ), $this->getLink( $job_id_2 ) ] );

		$job_links = $this->getJobLinksMock( $job_ids, $links );

		$subject = $this->getSubject( $notices, $job_links );

		$subject->addJobsCreatedAutomatically( $job_ids );
	}

	public function dp_add_notice_with_reusable_blocks_job_links() {
		return [
			'no return URL' => [
				null,
				[ 'post', 'edit-post' ]
			],
			'no matching parameter' => [
				'/wp-admin/post.php?foo=bar',
				[ 'post', 'edit-post' ]
			],
			'return to post editor' => [
				'/wp-admin/post.php?post=' . self::POST_ID . '&foo=bar',
				[ self::POST_TYPE ]
			],
			'return to posts list' => [
				'/wp-admin/post.php?post_type=' . self::POST_TYPE . '&foo=bar',
				[ 'edit-' . self::POST_TYPE ]
			],
		];
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
	 * @param array  $restricted_screen_ids
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|\WPML_Notice
	 */
	private function getExpectedNoticesMock( $expected_text, $restricted_screen_ids ) {
		$notice = $this->getMockBuilder( '\WPML_Notice' )
		               ->setMethods( [ 'set_flash', 'set_restrict_to_screen_ids', 'set_css_class_types' ] )
		               ->disableOriginalConstructor()->getMock();
		$notice->expects( $this->once() )->method( 'set_flash' )->with( true );
		$notice->expects( $this->once() )->method( 'set_restrict_to_screen_ids' )->with( $restricted_screen_ids );
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
