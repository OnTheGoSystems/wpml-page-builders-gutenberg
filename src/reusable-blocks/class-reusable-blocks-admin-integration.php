<?php

namespace WPML\PB\Gutenberg;

class Reusable_Blocks_Admin_Integration implements Integration {

	/** @var Reusable_Blocks_Batch_Handler $batch_handler */
	private $batch_handler;

	/** @var Reusable_Blocks_Basket_Handler $basket_handler */
	private $basket_handler;

	public function __construct(
		Reusable_Blocks_Batch_Handler $batch_handler,
		Reusable_Blocks_Basket_Handler $basket_handler
	) {
		$this->batch_handler  = $batch_handler;
		$this->basket_handler = $basket_handler;
	}

	public function add_hooks() {
		/**
		 * The reusable blocks are already added to the basket.
		 * We don't want to automatically add it again if
		 * the user manually removed it.
		 */
		if ( ! $this->is_submitting_basket() ) {
			add_filter( 'wpml_send_jobs_batch', [ $this, 'add_blocks_to_batch' ] );
		}

		add_action( 'wpml_tm_add_to_basket', [ $this, 'add_blocks_to_basket' ] );
	}

	private function is_submitting_basket() {
		return isset( $_POST['action'] ) && 'send_basket_item' === $_POST['action'];
	}

	/**
	 * Add reusable block elements that are used inside
	 * the post elements already in the batch.
	 *
	 * @param \WPML_TM_Translation_Batch $batch
	 *
	 * @return \WPML_TM_Translation_Batch
	 */
	public function add_blocks_to_batch( \WPML_TM_Translation_Batch $batch ) {
		return $this->batch_handler->add_blocks( $batch );
	}

	/**
	 * Add reusable blocks that are used in the
	 * post items in the basket.
	 *
	 * @param array $data
	 */
	public function add_blocks_to_basket( array $data ) {
		$this->basket_handler->add_blocks( $data );
	}
}
