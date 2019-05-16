<?php

namespace WPML\PB\Gutenberg\StringsInBlock;

class Attributes extends Base {

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	public function find( \WP_Block_Parser_Block $block ) {
		$strings = [];
		$attrs   = $this->getAttributes( $block );

		if ( $attrs ) {
			$keys    = $this->getKeyConfig( $block );
			$strings = $this->findStringsRecursively( $attrs, $keys, $block->blockName );
		}

		return $strings;
	}

	/**
	 * @param array  $attrs
	 * @param array  $config_keys
	 * @param string $block_name
	 *
	 * @return array
	 */
	private function findStringsRecursively( array $attrs, array $config_keys, $block_name ) {
		$strings = [];

		foreach ( $attrs as $attr_key => $attr_value ) {
			$matching_key = $this->getMatchingConfigKey( $attr_key, $config_keys );

			if ( ! $matching_key ) {
				continue;
			}

			if ( is_array( $attr_value ) ) {
				$next_level_keys = is_array( $config_keys[ $matching_key ] )
					? $config_keys[ $matching_key ] : [ '*' => 1 ];

				$strings = array_merge(
					$strings,
					$this->findStringsRecursively( $attr_value, $next_level_keys, $block_name )
				);
			} elseif ( ! is_numeric( $attr_value ) ) {
				$type      = $this->get_string_type( $attr_value );
				$string_id = $this->get_string_id( $block_name, $attr_value );
				$strings[] = $this->build_string( $string_id, $block_name, $attr_value, $type );
			}
		}

		return $strings;
	}

	/**
	 * @param string $attr_key
	 * @param array  $config_keys
	 *
	 * @return string|null
	 */
	private function getMatchingConfigKey( $attr_key, array $config_keys ) {
		if ( isset( $config_keys[ $attr_key ] ) ) {
			return $attr_key;
		}

		/**
		 * If we don't find an exactly matching key,
		 * we'll try to find a key with a wildcard.
		 */
		foreach ( array_keys( $config_keys ) as $config_key ) {

			if ( preg_match( $this->getRegex( $config_key ), $attr_key ) ) {
				return $config_key;
			}
		}

		return null;
	}

	/**
	 * If the config key is not already a regex
	 * we will replace the wildcard (*) and make it a valid regex.
	 *
	 * @param string $config_key
	 *
	 * @return string
	 */
	private function getRegex( $config_key ) {
		if ( @preg_match( $config_key, '' ) !== false ) {
			return $config_key;
		}

		return '/' . str_replace( '*', 'S+', preg_quote( $config_key, '/' ) ) . '/';
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 * @param array                  $string_translations
	 * @param string                 $lang
	 *
	 * @return \WP_Block_Parser_Block
	 */
	public function update( \WP_Block_Parser_Block $block, array $string_translations, $lang ) {
		$attrs = $this->getAttributes( $block );

		if ( $attrs ) {
			$keys         = $this->getKeyConfig( $block );
			$block->attrs = $this->updateStringsRecursively( $attrs, $keys, $string_translations, $lang, $block->blockName );
		}

		return $block;
	}

	/**
	 * @param array  $attrs
	 * @param array  $config_keys
	 * @param array  $translations
	 * @param string $lang
	 * @param string $block_name
	 *
	 * @return array
	 */
	public function updateStringsRecursively( array $attrs, array $config_keys, array $translations, $lang, $block_name ) {
		foreach ( $attrs as $attr_key => $attr_value ) {
			$matching_key = $this->getMatchingConfigKey( $attr_key, $config_keys );

			if ( ! $matching_key ) {
				continue;
			}

			if ( is_array( $attr_value ) ) {
				$next_level_keys = is_array( $config_keys[ $matching_key ] )
					? $config_keys[ $matching_key ] : [ '*' => 1 ];

				$attrs[ $attr_key ] = $this->updateStringsRecursively( $attr_value, $next_level_keys, $translations, $lang, $block_name );
			} else {
				$string_id = $this->get_string_id( $block_name, $attr_value );

				if (
					isset( $translations[ $string_id ][ $lang ] ) &&
					ICL_TM_COMPLETE == $translations[ $string_id ][ $lang ]['status']
				) {
					$attrs[ $attr_key ] = $translations[ $string_id ][ $lang ]['value'];
				}
			}
		}

		return $attrs;
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	private function getAttributes( \WP_Block_Parser_Block $block ) {
		return ! empty( $block->attrs ) && is_array( $block->attrs ) ? $block->attrs : [];
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	private function getKeyConfig( \WP_Block_Parser_Block $block ) {
		$config = $this->get_block_config( $block, 'key' );

		return $config ? $config : [];
	}
}
