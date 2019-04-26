<?php

namespace WPML\PB\Gutenberg;

class Reusable_Blocks_Translation {

	/** @var \SitePress $sitepress */
	private $sitepress;

	/** @var \WPML_Translation_Element_Factory $element_factory */
	private $element_factory;

	/**
	 * Reusable_Blocks_Translation constructor.
	 *
	 * @param \SitePress                        $sitepress
	 * @param \WPML_Translation_Element_Factory $element_factory
	 */
	public function __construct( \SitePress $sitepress, \WPML_Translation_Element_Factory $element_factory ) {
		$this->sitepress       = $sitepress;
		$this->element_factory = $element_factory;
	}

	/**
	 * If the block translation post does not exist,
	 * we'll create one by duplicating the original.
	 * The string replacement will occurs later
	 * as the standard process.
	 *
	 * @param int    $original_id
	 * @param string $lang_to
	 */
	public function create_post( $original_id, $lang_to ) {
		$block_element = $this->element_factory->create_post( $original_id );

		if ( $block_element->get_translation( $lang_to ) ) {
			return;
		}

		$postarr = get_post( $original_id, ARRAY_A );
		unset( $postarr['ID'] );

		$translation_id = wpml_get_create_post_helper()->insert_post( $postarr, $lang_to );

		$this->sitepress->set_element_language_details(
			$translation_id,
			'post_wp_block',
			$block_element->get_trid(),
			$lang_to
		);
	}
}
