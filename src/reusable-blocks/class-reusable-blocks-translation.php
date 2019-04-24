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
			$block['attrs']['ref'] = $this->sitepress->get_object_id(
				$block['attrs']['ref'],
				self::POST_TYPE,
				true,
				$lang
			);
		}

		return $block;
	}
}
