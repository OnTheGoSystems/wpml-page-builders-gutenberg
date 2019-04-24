<?php

namespace WPML\PB\Gutenberg;

class Reusable_Blocks_Integration implements Integration{

	/** @var Reusable_Blocks $reusable_blocks */
	private $reusable_blocks;

	public function __construct( Reusable_Blocks $reusable_blocks ) {
		$this->reusable_blocks = $reusable_blocks;
	}

	public function add_hooks() {
		add_filter( 'wpml_st_get_post_string_packages', [ $this, 'add_reusable_block_packages' ], PHP_INT_MAX, 2 );
	}

	/**
	 * @param array $packages
	 * @param int   $post_id
	 *
	 * @return array
	 */
	public function add_reusable_block_packages( $packages, $post_id ) {
		remove_filter( 'wpml_st_get_post_string_packages', [ $this, 'add_reusable_block_packages' ], PHP_INT_MAX );

		foreach ( $this->reusable_blocks->get_ids( $post_id ) as $block_id ) {
			$block_packages = apply_filters( 'wpml_st_get_post_string_packages', [], $block_id );
			$packages       = $packages + $block_packages;
		}

		add_filter( 'wpml_st_get_post_string_packages', [ $this, 'add_reusable_block_packages' ], PHP_INT_MAX, 2 );

		return $packages;
	}
}
