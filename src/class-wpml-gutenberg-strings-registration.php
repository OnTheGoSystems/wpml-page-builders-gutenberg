<?php

class WPML_Gutenberg_Strings_Registration {

	/** @var WPML_Gutenberg_Strings_In_Block $strings_in_blocks */
	private $strings_in_blocks;

	/** @var WPML_ST_String_Factory $string_factory */
	private $string_factory;

	/** @var int $string_location */
	private $string_location;

	public function __construct(
		WPML_Gutenberg_Strings_In_Block $strings_in_blocks,
		WPML_ST_String_Factory $string_factory
	) {
		$this->strings_in_blocks = $strings_in_blocks;
		$this->string_factory    = $string_factory;
	}

	/**
	 * @param WP_Post $post
	 * @param array $package_data
	 */
	public function register_strings( WP_Post $post, $package_data ) {
		do_action( 'wpml_start_string_package_registration', $package_data );

		$this->string_location = 1;

		$this->register_blocks(
			WPML_Gutenberg_Integration::parse_blocks( $post->post_content ),
			$package_data
		);

		do_action( 'wpml_delete_unused_package_strings', $package_data );
	}

	/**
	 * @param array $blocks
	 * @param array $package_data
	 */
	private function register_blocks( array $blocks, array $package_data ) {

		foreach ( $blocks as $block ) {

			$block   = WPML_Gutenberg_Integration::sanitize_block( $block );
			$strings = $this->strings_in_blocks->find( $block );

			foreach ( $strings as $string ) {

				do_action(
					'wpml_register_string',
					$string->value,
					$string->id,
					$package_data,
					$string->name,
					$string->type
				);

				$this->update_string_location( $package_data, $string );
			}

			if ( isset( $block->innerBlocks ) ) {
				$this->register_blocks( $block->innerBlocks, $package_data );
			}
		}
	}

	private function update_string_location( array $package_data, stdClass $string_data ) {
		$string_id = apply_filters( 'wpml_string_id_from_package', 0, $package_data, $string_data->id, $string_data->value );
		$string    = $this->string_factory->find_by_id( $string_id );

		if ( $string_id && $string ) {
			$string->set_location( $this->string_location );
			$this->string_location++;
		}
	}
}
