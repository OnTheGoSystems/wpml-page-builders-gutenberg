<?php

namespace WPML\PB\Gutenberg;

class Reusable_Blocks_Integration implements Integration{

	/** @var Reusable_Blocks_Translation $reusable_blocks_translation */
	private $reusable_blocks_translation;

	public function __construct(
		Reusable_Blocks_Translation $reusable_blocks_translation
	) {
		$this->reusable_blocks_translation = $reusable_blocks_translation;
	}

	public function add_hooks() {
		add_filter( 'render_block_data', [ $this, 'convert_reusable_block' ] );
	}

	/**
	 * Converts the block in the current language
	 *
	 * @param array $block
	 *
	 * @return array
	 */
	public function convert_reusable_block( array $block ) {
		return $this->reusable_blocks_translation->convert_block( $block );
	}
}
