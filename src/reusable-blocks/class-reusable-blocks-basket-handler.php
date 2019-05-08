<?php

namespace WPML\PB\Gutenberg;

class Reusable_Blocks_Basket_Handler extends Reusable_Blocks_Handler {

	/** @var \WPML_Translation_Basket $translation_basket */
	private $translation_basket;

	public function __construct(
		Reusable_Blocks $reusable_blocks,
		Reusable_Blocks_Translation $reusable_blocks_translation,
		\WPML_Translation_Basket $translation_basket
	) {
		parent::__construct( $reusable_blocks, $reusable_blocks_translation );
		$this->translation_basket = $translation_basket;
	}

	/**
	 * @param array $data
	 */
	public function add_blocks( array $data ) {
		if ( ! isset( $data['post'], $data['translate_from'], $data['tr_action'] ) ) {
			return;
		}

		$post_elements = $this->extract_added_post_elements( $data );
		$blocks        = $this->get_blocks_from_post_elements( $post_elements );
		$blocks        = $this->get_block_elements_to_add( $blocks )->toArray();

		if ( $blocks ) {
			$basket_portion = [
				'post'              => [],
				'source_language'	=> $data['translate_from'],
				'target_languages'	=> array_keys( $data['tr_action'] ),
			];

			foreach ( $blocks as $block ) {
				$basket_portion['post'][ $block->block_id ] = [
					'from_lang'  => $block->source_lang,
					'to_langs'   => $block->target_langs,
					'auto_added' => true, // This is an optional flag we can use when displaying the basket
				];
			}

			$this->translation_basket->update_basket( $basket_portion );
		}
	}

	/**
	 * @param array $data
	 *
	 * @return \Illuminate\Support\Collection
	 */
	private function extract_added_post_elements( array $data ) {
		$source_lang  = $data['translate_from'];
		$target_langs = \collect( $data['tr_action'] )
			->filter( function( $translate ) { return $translate; } )
			->map( function( $translate ) { return (int) $translate; } )
			->toArray();

		return \collect( $data['post'] )->map(
			function( $item ) use ( $source_lang, $target_langs ) {
				if (
					isset( $item['checked'], $item['type'] )
					&& 'post' === $item['type']
				) {
					return new Reusable_Blocks_Basket_Element(
						(int) $item['checked'],
						$source_lang,
						$target_langs
					);
				}

				return null;
			}
		)->filter();
	}
}
