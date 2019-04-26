<?php

namespace WPML\PB\Gutenberg;

class Reusable_Blocks_Integration implements Integration{

	/** @var Reusable_Blocks $reusable_blocks */
	private $reusable_blocks;

	/** @var Reusable_Blocks_Translation $reusable_blocks_translation */
	private $reusable_blocks_translation;

	public function __construct(
		Reusable_Blocks $reusable_blocks,
		Reusable_Blocks_Translation $reusable_blocks_translation
	) {
		$this->reusable_blocks             = $reusable_blocks;
		$this->reusable_blocks_translation = $reusable_blocks_translation;
	}

	public function add_hooks() {
		add_filter( 'wpml_st_get_post_string_packages', [ $this, 'add_reusable_block_packages' ], PHP_INT_MAX, 2 );
		add_action( 'wpml_translation_job_saved', [ $this, 'create_reusable_blocks_translation_post' ], 10, 3 );
	}

	/**
	 * @param array $packages
	 * @param int   $post_id
	 *
	 * @return array
	 */
	public function add_reusable_block_packages( $packages, $post_id ) {
		remove_filter( 'wpml_st_get_post_string_packages', [ $this, 'add_reusable_block_packages' ], PHP_INT_MAX );

		foreach ( $this->reusable_blocks->get_ids_from_post( $post_id ) as $block_id ) {
			$block_packages = apply_filters( 'wpml_st_get_post_string_packages', [], $block_id );
			$packages       = $packages + $block_packages;
		}

		add_filter( 'wpml_st_get_post_string_packages', [ $this, 'add_reusable_block_packages' ], PHP_INT_MAX, 2 );

		return $packages;
	}

	/**
	 * @param int       $new_post_id
	 * @param array     $fields
	 * @param \stdClass $job
	 */
	public function create_reusable_blocks_translation_post( $new_post_id, array $fields, \stdClass $job ) {
		if ( 'post' !== $job->element_type_prefix ) {
			return;
		}

		$original_block_ids = $this->reusable_blocks->get_ids_from_job( $job );

		foreach ( $original_block_ids as $original_block_id ) {
			$this->reusable_blocks_translation->create_post( $original_block_id, $job->language_code );
		}
	}
}
