<?php

namespace WPML\PB\Gutenberg;

class Reusable_Blocks_Admin_Integration implements Integration {

	/** @var Reusable_Blocks_Batch_Handler $batch_handler */
	private $batch_handler;

	public function __construct( Reusable_Blocks_Batch_Handler $batch_handler ) {
		$this->batch_handler = $batch_handler;
	}

	public function add_hooks() {
		add_action( 'wpml_send_jobs_batch', [ $this, 'add_reusable_elements' ] );
	}

	/**
	 * Add reusable block elements that are used inside
	 * the post elements already in the batch.
	 *
	 * @param \WPML_TM_Translation_Batch $batch
	 *
	 * @return \WPML_TM_Translation_Batch
	 */
	public function add_reusable_elements( \WPML_TM_Translation_Batch $batch ) {
		return $this->batch_handler->add_blocks( $batch );
	}
}
