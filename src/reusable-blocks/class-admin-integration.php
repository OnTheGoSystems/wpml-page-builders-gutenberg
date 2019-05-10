<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

class AdminIntegration implements \WPML\PB\Gutenberg\Integration {

	/** @var ManageBatch $manage_batch */
	private $manage_batch;

	/** @var ManageBasket $manage_basket */
	private $manage_basket;

	public function __construct(
		ManageBatch $manage_batch,
		ManageBasket $manage_basket
	) {
		$this->manage_batch  = $manage_batch;
		$this->manage_basket = $manage_basket;
	}

	public function add_hooks() {
		/**
		 * The reusable blocks are already added to the basket.
		 * We don't want to automatically add it again if
		 * the user manually removed it.
		 */
		if ( ! $this->isSubmittingBasket() ) {
			add_filter( 'wpml_send_jobs_batch', [ $this, 'addBlocksToBatch' ] );
		}

		add_action( 'wpml_tm_add_to_basket', [ $this, 'addBlocksToBasket' ] );
	}

	private function isSubmittingBasket() {
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
	public function addBlocksToBatch( \WPML_TM_Translation_Batch $batch ) {
		return $this->manage_batch->addBlocks( $batch );
	}

	/**
	 * Add reusable blocks that are used in the
	 * post items in the basket.
	 *
	 * @param array $data
	 */
	public function addBlocksToBasket( array $data ) {
		$this->manage_basket->addBlocks( $data );
	}
}
