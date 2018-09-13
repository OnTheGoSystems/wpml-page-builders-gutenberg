<?php

/**
 * Class WPML_Gutenberg_Integration
 */
class WPML_Gutenberg_Integration {

	const PACKAGE_ID = 'Gutenberg';

	/**
	 * @var WPML_Gutenberg_Strings_In_Block
	 */
	private $strings_in_blocks;

	/**
	 * @var WPML_Gutenberg_Config_Option
	 */
	private $config_option;

	/**
	 * WPML_Gutenberg_Integration constructor.
	 *
	 * @param WPML_Gutenberg_Strings_In_Block $strings_in_block
	 * @param WPML_Gutenberg_Config_Option $config_option
	 */
	public function __construct(
		WPML_Gutenberg_Strings_In_Block $strings_in_block,
		WPML_Gutenberg_Config_Option $config_option
	) {
		$this->strings_in_blocks = $strings_in_block;
		$this->config_option     = $config_option;
	}

	public function add_hooks() {
		add_filter( 'wpml_page_builder_support_required', array( $this, 'page_builder_support_required' ), 10, 1 );
		add_action( 'wpml_page_builder_register_strings', array( $this, 'register_strings' ), 10, 2 );
		add_action( 'wpml_page_builder_string_translated', array( $this, 'string_translated' ), 10, 5 );
		add_filter( 'wpml_config_array', array( $this, 'wpml_config_filter' ) );
	}

	/**
	 * @param array $plugins
	 *
	 * @return array
	 */
	function page_builder_support_required( $plugins ) {
		$plugins[] = self::PACKAGE_ID;

		return $plugins;
	}

	/**
	 * @param WP_Post $post
	 * @param array $package_data
	 */
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

	/**
	 * @param array $blocks
	 * @param array $package_data
	 */
	private function register_blocks( array $blocks, array $package_data ) {

		foreach ( $blocks as $block ) {

			if ( $block instanceof WP_Block_Parser_Block ) {
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

				if ( isset( $block->innerBlocks ) ) {
					$this->register_blocks( $block->innerBlocks, $package_data );
				}
			}
		}
	}

	/**
	 * @param string $package_kind
	 * @param int $translated_post_id
	 * @param WP_Post $original_post
	 * @param array $string_translations
	 * @param string $lang
	 */
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
				$content .= $this->render_block( $block );
			}

			wp_update_post( array( 'ID' => $translated_post_id, 'post_content' => $content ) );

		}

	}

	/**
	 * @param array $blocks
	 * @param array $string_translations
	 * @param string $lang
	 *
	 * @return array
	 */
	private function update_block_translations( $blocks, $string_translations, $lang ) {
		foreach ( $blocks as &$block ) {
			if ( $block instanceof WP_Block_Parser_Block ) {
				$block = $this->strings_in_blocks->update( $block, $string_translations, $lang );

				if ( isset( $block->blockName ) && 'core/block' === $block->blockName ) {
					$block->attrs['ref'] = apply_filters( 'wpml_object_id', $block->attrs['ref'], 'wp_block', true, $lang );
				}
				if ( isset( $block->innerBlocks ) ) {
					$block->innerBlocks = $this->update_block_translations(
						$block->innerBlocks,
						$string_translations,
						$lang
					);
				}
			}
		}

		return $blocks;
	}

	/**
	 * @param array|WP_Block_Parser_Block $block
	 *
	 * @return string
	 */
	private function render_block( $block ) {
		$content = '';

		if ( $block instanceof WP_Block_Parser_Block ) {
			$block_type = preg_replace( '/^core\//', '', $block->blockName );

			$block_attributes = '';
			if ( $block->attrs ) {
				$block_attributes = ' ' . json_encode( $block->attrs );
			}
			$content .= '<!-- wp:' . $block_type . $block_attributes . ' -->';

			$content .= $this->render_inner_HTML( $block );

			$content .= '<!-- /wp:' . $block_type . ' -->';

		} else {
			$content .= $block['innerHTML'];
		}

		return $content;

	}

	/**
	 * @param array $block
	 *
	 * @return string
	 */
	private function render_inner_HTML( $block ) {

		if ( isset ( $block->innerBlocks ) && count( $block->innerBlocks ) ) {
			$inner_html_parts = $this->guess_inner_HTML_parts( $block );

			$content = $inner_html_parts[0];

			foreach ( $block->innerBlocks as $inner_block ) {
				$content .= $this->render_block( $inner_block );
			}

			$content .= $inner_html_parts[1];

		} else {
			$content = $block->innerHTML;
		}

		return $content;

	}

	/**
	 * The gutenberg parser doesn't handle inner blocks correctly
	 * It should really return the HTML before and after the blocks
	 * We're just guessing what it is here
	 * The usual innerHTML would be: <div class="xxx"></div>
	 * The columns block also includes new lines: <div class="xxx">\n\n</div>
	 * So we try to split at ></ and also include white space and new lines between the tags
	 *
	 * @param array $block
	 *
	 * @return array
	 */
	private function guess_inner_HTML_parts( $block ) {
		$inner_HTML = $block->innerHTML;

		$parts = array( $inner_HTML, '' );

		preg_match( '#>\s*</#', $inner_HTML, $matches );

		if ( count( $matches ) === 1 ) {
			$parts = explode( $matches[0], $inner_HTML );
			if ( count( $parts ) === 2 ) {
				$match_mid_point = 1 + ( strlen( $matches[0] ) - 3 ) / 2;
				// This is the first ">" char plus half the remaining between the tags

				$parts[0]        .= substr( $matches[0], 0, $match_mid_point );
				$parts[1]        = substr( $matches[0], $match_mid_point ) . $parts[1];
			}
		}

		return $parts;
	}

	/**
	 * @param array $config_data
	 *
	 * @return array
	 */
	public function wpml_config_filter( $config_data ) {
		$this->config_option->update_from_config( $config_data );

		return $config_data;
	}

}