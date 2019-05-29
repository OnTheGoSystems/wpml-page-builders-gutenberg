<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

class Notice {

	/** @var \WPML_Notices $notices */
	private $notices;

	/** @var \WPML_Translation_Job_Factory $job_factory */
	private $job_factory;

	public function __construct(
		\WPML_Notices $notices,
		\WPML_Translation_Job_Factory $job_factory
	) {
		$this->notices     = $notices;
		$this->job_factory = $job_factory;
	}

	public function addJobsCreatedAutomatically( array $job_ids ) {
		$job_links = $this->getBlockJobLinks( $job_ids );

		$text = '<p>' . _n(
			'We automatically created a translation job for the reusable block:',
			'We automatically created translation jobs for the reusable blocks:',
			$job_links->count(),
			'sitepress'
		) . '</p>';

		$text .= '<ul><li>' . implode( '</li><li>', $job_links->toArray() ) . '</li></ul>';

		$notice = $this->notices->create_notice( 'automatic-jobs', $text, __CLASS__ );
		$notice->set_flash( true );
		$notice->set_restrict_to_screen_ids( [ 'post', 'edit-post' ] );
		$notice->set_css_class_types( 'notice-info' );
		$this->notices->add_notice( $notice );
	}

	private function getBlockJobLinks( array $job_ids ) {
		return \collect( $job_ids )->map( function( $job_id ) {
			return $this->getJobEditLink( $job_id );
		} )->filter();
	}

	/**
	 * @param int $job_id
	 *
	 * @return string|null
	 */
	private function getJobEditLink( $job_id ) {
		$job = $this->job_factory->get_translation_job( $job_id );

		if ( ! $job || 'post_wp_block' !== $job->original_post_type ) {
			return null;
		}

		$job_edit_url = admin_url( 'admin.php?page='
		                            . WPML_TM_FOLDER
		                            . '/menu/translations-queue.php&job_id='
		                            . $job_id );
		$job_edit_url = apply_filters( 'icl_job_edit_url', $job_edit_url, $job_id );

		return '<a href="' . $job_edit_url . '" target="_blank">' . $job->title . '</a>';
	}
}
