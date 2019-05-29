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
		$job_ids = [
			'100',
			'101',
			'102',
			'103',
		];

		$jobs_map = [
			[ $job_ids[0], $this->getJob( $job_ids[0], 'The post', 'post' ) ],
			[ $job_ids[1], $this->getJob( $job_ids[1], 'Block 1', 'wp_block' ) ],
			[ $job_ids[2], $this->getJob( $job_ids[2], 'Block 2', 'wp_block' ) ],
			[ $job_ids[3], false ],
		];

		$edit_url_1 = $this->getEditURL( $job_ids[1] );
		$edit_url_2 = $this->getEditURL( $job_ids[2] );

		\WP_Mock::passthruFunction( 'admin_url' );

		\WP_Mock::onFilter( 'icl_job_edit_url' )
			->with( $edit_url_1, $job_ids[1] )
			->reply( $edit_url_1 . '#filtered' );

		\WP_Mock::onFilter( 'icl_job_edit_url' )
			->with( $edit_url_2, $job_ids[2] )
			->reply( $edit_url_2 . '#filtered' );

		\WP_Mock::userFunction( '_n', [
			'args' => [
				'We automatically created a translation job for the reusable block:',
				'We automatically created translation jobs for the reusable blocks:',
				2,
				'sitepress'
			],
			'return' => 'We automatically created translation jobs for the reusable blocks:',
		] );

		$expected_text = '<p>We automatically created translation jobs for the reusable blocks:</p>';
		$expected_text .= '<ul><li><a href="' . $edit_url_1 . '#filtered" target="_blank">' . $jobs_map[1][1]->title .'</a></li><li><a href="' . $edit_url_2 . '#filtered" target="_blank">' . $jobs_map[2][1]->title .'</a></li></ul>';

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


		$job_factory = $this->getMockBuilder( '\WPML_Translation_Job_Factory' )
			->setMethods( [ 'get_translation_job' ] )
			->disableOriginalConstructor()->getMock();
		$job_factory->method( 'get_translation_job' )->willReturnMap( $jobs_map );

		$subject = new Notice( $notices, $job_factory );

		$subject->addJobsCreatedAutomatically( $job_ids );
	}

	private function getJob( $id, $title, $post_type ) {
		return (object) [
			'job_id'             => $id,
			'title'              => $title,
			'original_post_type' => 'post_' . $post_type,
		];
	}

	private function getEditURL( $job_id ) {
		return 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&job_id=' . $job_id;
	}
}
