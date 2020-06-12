<?php

namespace WPML\PB\Gutenberg\Media;

class Integration implements \WPML\PB\Gutenberg\Integration {

	public function add_hooks() {
		add_filter( 'wpml_media_replaced_images_in_text', [ $this, 'mediaReplacedImagesInText' ], 10, 4 );
	}

	public function mediaReplacedImagesInText( $text, $targetLanguage, $sourceLanguage, $imgs ) {
		/**
		 * 1. Parse blocks
		 * 2. For each image block, convert the image attribute ID
		 * 3. Render block (to get back to text)
		 */

		/** @var \WP_Block_Parser_Block[] $blocks */
		$blocks = \WPML_Gutenberg_Integration::parse_blocks( $text );

		$blocks = $this->replaceIdsInBlockAttributes( $blocks, $targetLanguage, $sourceLanguage, $imgs );

		$newText = '';

		foreach ( $blocks as $block ) {
			$newText .= \WPML_Gutenberg_Integration::render_block( $block );
		}

		return $newText;
	}

	/**
	 * @param \WP_Block_Parser_Block[] $blocks
	 * @param $targetLanguage
	 * @param $sourceLanguage
	 * @param $imgs
	 *
	 * @return mixed
	 */
	private function replaceIdsInBlockAttributes( $blocks, $targetLanguage, $sourceLanguage, $imgs ) {
		foreach ( $blocks as $block ) {

			if ( 'image' === $block->blockName && isset( $block->attrs['id'] ) ) {
				// Convert id
			}
		}

		return $blocks;
	}
}
