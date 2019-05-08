<?php

namespace WPML\PB\Gutenberg;

class Reusable_Blocks_Batch_Handler extends Reusable_Blocks_Handler {

	public function add_blocks( \WPML_TM_Translation_Batch $batch ) {
		$blocks = $this->get_blocks_from_post_elements( \collect( $batch->get_elements() ) );

		$this->get_block_elements_to_add( $blocks )->each(
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
}
