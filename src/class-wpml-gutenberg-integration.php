<?php

class WPML_Gutenberg_Integration {

	const PACKAGE_ID = 'Gutenberg';

	public function __construct( WPML_Gutenberg_Strings_In_Block  $strings_in_block ) {
		$this->strings_in_blocks = $strings_in_block;
	}

	public function add_hooks() {
		add_filter( 'wpml_page_builder_support_required', array( $this, 'page_builder_support_required' ), 10, 1 );
		add_action( 'wpml_page_builder_register_strings', array( $this, 'register_strings' ), 10, 2 );
		add_action( 'wpml_page_builder_string_translated', array( $this, 'string_translated' ), 10, 5 );
	}

	function page_builder_support_required( $plugins ) {
		$plugins[] = self::PACKAGE_ID;

		return $plugins;
	}

	function register_strings( WP_Post $post, $package_data ) {

		if ( self::PACKAGE_ID === $package_data['kind'] ) {

			do_action( 'wpml_start_string_package_registration', $package_data );

			$this->register_blocks(
				gutenberg_parse_blocks( $post->post_content ),
				$package_data
			);

			do_action( 'wpml_delete_unused_package_strings', $package_data );

		}
	}

	private function register_blocks( $blocks, $package_data ) {

		foreach ( $blocks as $block ) {
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

			}

			if ( isset( $block['innerBlocks'] ) ) {
				$this->register_blocks( $block['innerBlocks'], $package_data );
			}

		}
	}

	public function string_translated(
		$package_kind,
		$translated_post_id,
		$original_post,
		$string_translations,
		$lang
	) {

		if ( self::PACKAGE_ID === $package_kind ) {
			$blocks = gutenberg_parse_blocks( $original_post->post_content );

			$blocks = $this->update_block_translations( $blocks, $string_translations, $lang );

			$content = '';
			foreach ( $blocks as $block ) {
				$content .= gutenberg_render_block( $block );
			}

			wp_update_post( array( 'ID' => $translated_post_id, 'post_content' => $content ) );

		}

	}

	private function update_block_translations( $blocks, $string_translations, $lang ) {
		foreach ( $blocks as &$block ) {
			$block = $this->strings_in_blocks->update( $block, $string_translations, $lang );

			if ( isset( $block['innerBlocks'] ) ) {
				$block['innerBlocks'] = $this->update_block_translations(
					$block['innerBlocks'],
					$string_translations,
					$lang
				);
			}
		}

		return $blocks;
	}

}