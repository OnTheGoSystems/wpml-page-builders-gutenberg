<?php

namespace WPML\PB\Gutenberg;

class Reusable_Blocks {

	/**
	 * @param array $block
	 *
	 * @return bool
	 */
	public static function is_reusable( array $block ) {
		return 'core/block' === $block['blockName']
		       && isset( $block['attrs']['ref'] )
		       && is_numeric( $block['attrs']['ref'] );
	}
}
