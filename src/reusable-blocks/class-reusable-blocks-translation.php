<?php

namespace WPML\PB\Gutenberg;

class Reusable_Blocks_Translation {

	const POST_TYPE = 'wp_block';

	/** @var \SitePress $sitepress */
	private $sitepress;

	/**
	 * @param \SitePress $sitepress
	 */
	public function __construct( \SitePress $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * @param array        $block
	 * @param null|string  $lang
	 *
	 * @return array
	 */
	public function convert_block( array $block, $lang = null ) {
		if ( Reusable_Blocks::is_reusable( $block ) ) {
			$block['attrs']['ref'] = $this->convert_block_id( $block['attrs']['ref'], true, $lang );
		}

		return $block;
	}

	/**
	 * @param int         $block_id
	 * @param bool        $original_if_missing
	 * @param string|null $lang
	 *
	 * @return
	 */
	public function convert_block_id( $block_id, $original_if_missing, $lang = null ) {
		return $this->sitepress->get_object_id( $block_id, self::POST_TYPE, $original_if_missing, $lang );
	}
}
