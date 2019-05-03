<?php

namespace WPML\PB\Gutenberg;

class Reusable_Blocks_Batch_Handler {

	/** @var Reusable_Blocks */
	private $reusable_blocks;

	/** @var Reusable_Blocks_Translation */
	private $reusable_blocks_translation;

	public function __construct(
		Reusable_Blocks $reusable_blocks,
		Reusable_Blocks_Translation $reusable_blocks_translation
	) {
		$this->reusable_blocks             = $reusable_blocks;
		$this->reusable_blocks_translation = $reusable_blocks_translation;
	}

	public function add_blocks( \WPML_TM_Translation_Batch $batch ) {
		$blocks = \collect( $batch->get_elements() )
			->map( [ $this, 'find_blocks_in_batch_element' ] )
			->flatten( 1 )
			->unique( 'block_id' );

		/**
		 * [
		 *  [
		 *      'block_id'     => 1,
		 *      'target_langs' => ['fr' => 1, 'de' => 1],
		 *      'source_lang'  => 'en',
		 *  ],
		 *  [
		 *      'block_id'     => 2,
		 *      'target_langs' => ['de' => 1],
		 *      'source_lang'  => 'en',
		 *  ],
		 * ]
		 */
		$blocks->map( [ $this, 'select_target_langs' ] )
			->each(
				function( $block ) use ( $batch ) {
					if ( ! empty( $block['target_langs'] ) ) {
						$new_element = new \WPML_TM_Translation_Batch_Element(
							$block['block_id'],
							'post',
							$block['source_lang'],
							$block['target_langs']
						);

						$batch->add_element( $new_element );
					}
				}
			);

		return $batch;
	}

	/**
	 * @param \WPML_TM_Translation_Batch_Element $element
	 *
	 * @return array
	 */
	public function find_blocks_in_batch_element( \WPML_TM_Translation_Batch_Element $element ) {
		if ( $element->get_element_type() !== 'post' ) {
			return [];
		}

		return \collect( $this->reusable_blocks->get_ids_from_post( $element->get_element_id() ) )
			->map( function( $block_id ) use ( $element ) {
				return [
					'block_id'      => $block_id,
					'source_lang'   => $element->get_source_lang(),
					'target_langs'  => $element->get_target_langs(),
				];
			} )->toArray();
	}

	/**
	 * We will remove target langs that do not require a job
	 * for the reusable block.
	 *
	 * @param array $block
	 *
	 * @return array
	 */
	public function select_target_langs( array $block ) {
		foreach ( array_keys( $block['target_langs'] ) as $target_lang ) {
			if ( ! $this->needs_job( $block['block_id'], $target_lang ) ) {
				unset( $block['target_langs'][ $target_lang ] );
			}
		}

		return $block;
	}

	/**
	 * @param int    $block_id
	 * @param string $target_lang
	 *
	 * @return bool
	 */
	private function needs_job( $block_id, $target_lang ) {
		$needs_job     = true;
		$translated_id = $this->reusable_blocks_translation->convert_block_id( $block_id, false, $target_lang );

		if ( $translated_id ) {
			$needs_job = (bool) wpml_get_post_status_helper()->needs_update( $translated_id );
		}

		return $needs_job;
	}
}
