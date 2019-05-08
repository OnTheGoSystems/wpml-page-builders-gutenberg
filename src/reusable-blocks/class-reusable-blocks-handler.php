<?php

namespace WPML\PB\Gutenberg;

abstract class Reusable_Blocks_Handler {

	/** @var Reusable_Blocks */
	protected $reusable_blocks;

	/** @var Reusable_Blocks_Translation */
	protected $reusable_blocks_translation;

	public function __construct(
		Reusable_Blocks $reusable_blocks,
		Reusable_Blocks_Translation $reusable_blocks_translation
	) {
		$this->reusable_blocks             = $reusable_blocks;
		$this->reusable_blocks_translation = $reusable_blocks_translation;
	}

	/**
	 * @param \Illuminate\Support\Collection $blocks
	 *
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
	 *
	 * @return \Illuminate\Support\Collection
	 */
	protected function get_block_elements_to_add( $blocks ) {
		return $blocks->map( [ $this, 'select_target_langs' ] )
			->reject( function( $block ) { return empty( $block->target_langs ); } );
	}

	/**
	 * @param \Illuminate\Support\Collection $post_elements
	 *
	 * @return \Illuminate\Support\Collection
	 */
	protected function get_blocks_from_post_elements( \Illuminate\Support\Collection $post_elements ) {
		return $post_elements->map( [ $this, 'find_blocks_in_element' ] )
		                     ->flatten( 1 )
		                     ->unique( 'block_id' );
	}

	/**
	 * @param \WPML_TM_Translation_Batch_Element|Reusable_Blocks_Basket_Element $element
	 *
	 * @return array
	 */
	public function find_blocks_in_element( $element ) {
		if (
			! $element instanceof \WPML_TM_Translation_Batch_Element
			&& ! $element instanceof Reusable_Blocks_Basket_Element
		) {
			throw new \RuntimeException( '$element must be an instance of \WPML_TM_Translation_Batch_Element or Reusable_Blocks_Basket_Element.' );
		}

		if ( $element->get_element_type() !== 'post' ) {
			return [];
		}

		return \collect( $this->reusable_blocks->get_ids_from_post( $element->get_element_id() ) )
			->map( function( $block_id ) use ( $element ) {
				return (object) [
					'block_id'      => $block_id,
					'source_lang'   => $element->get_source_lang(),
					'target_langs'  => $element->get_target_langs(),
				];
			} )->toArray();
	}

	/**
	 * @param int    $block_id
	 * @param string $target_lang
	 *
	 * @return bool
	 */
	protected function requires_translation( $block_id, $target_lang ) {
		$needs_job     = true;
		$translated_id = $this->reusable_blocks_translation->convert_block_id( $block_id, $target_lang );

		if ( $translated_id !== $block_id ) {
			$needs_job = (bool) wpml_get_post_status_helper()->needs_update( $translated_id );
		}

		return $needs_job;
	}

	/**
	 * We will remove target langs that do not require a job
	 * for the reusable block.
	 *
	 * @param \stdClass $block
	 *
	 * @return \stdClass
	 */
	public function select_target_langs( \stdClass $block ) {
		$block->target_langs = collect( $block->target_langs )
			->filter( function ( $unused, $target_lang )  use ( $block ) {
				return $this->requires_translation( $block->block_id, $target_lang );
			} )
			->toArray();

		return $block;
	}
}
