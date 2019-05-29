<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

class ManageBatch extends Manage {

	/** @var Notice $notice */
	private $notice;

	public function __construct(
		Blocks $blocks,
		Translation $translation,
		Notice $notice
	) {
		parent::__construct( $blocks, $translation );
		$this->notice = $notice;
	}

	public function addBlocks( \WPML_TM_Translation_Batch $batch ) {
		$blocks = $this->getBlocksFromPostElements( \collect( $batch->get_elements() ) );

		$blocks_to_add = $this->getBlockElementsToAdd( $blocks );

		if ( $blocks_to_add->isEmpty() ) {
			return $batch;
		}

		add_action( 'wpml_added_translation_jobs', [ $this, 'notifyExtraJobsToTranslator' ] );

		$blocks_to_add->each(
				function( $block ) use ( $batch ) {
					$new_element = new \WPML_TM_Translation_Batch_Element(
						$block->block_id,
						'post',
						$block->source_lang,
						$block->target_langs
					);

					$batch->add_element( $new_element );
				}
			);

		return $batch;
	}

	public function notifyExtraJobsToTranslator( array $added_jobs ) {
		if ( isset( $added_jobs['local'] ) ) {
			$this->notice->addJobsCreatedAutomatically( $added_jobs['local'] );
		}
	}
}
