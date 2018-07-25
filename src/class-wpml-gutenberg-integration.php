<?php

class WPML_Gutenberg_Integration {

	const PACKAGE_ID = 'Gutenberg';

	public function add_hooks() {
		add_filter( 'wpml_page_builder_support_required', array( $this, 'page_builder_support_required' ), 10, 1 );
		add_action( 'wpml_page_builder_register_strings', array( $this, 'register_strings' ), 10, 2 );
	}

	function page_builder_support_required( $plugins ) {
		$plugins[] = self::PACKAGE_ID;

		return $plugins;
	}

	function register_strings( WP_Post $post, $package_data ) {
		global $sample_page_builder_json;

		if ( self::PACKAGE_ID === $package_data['kind'] ) {

			$blocks = gutenberg_parse_blocks( $post->post_content );

			foreach ( $blocks as $block ) {
				$string_id = $this->get_string_id( $block );

				if ( $string_id ) {

					do_action(
						'wpml_register_string',
						$block['innerHTML'],
						$string_id,
						$package_data,
						$block['blockName'],
						'VISUAL'
					);

				}
			}
		}
	}

	private function get_string_id( $block ) {
		if ( isset( $block['blockName'], $block['innerHTML'] ) && '' !== trim( $block['innerHTML'] ) ) {
			return md5( $block['blockName'] . $block['innerHTML'] );
		} else {
			return null;
		}
	}

}