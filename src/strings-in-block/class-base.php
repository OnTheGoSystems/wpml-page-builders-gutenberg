<?php

namespace WPML\PB\Gutenberg\StringsInBlock;

abstract class Base implements StringsInBlock {

	/** @var array */
	private $block_types;

	/** @var \WPML_Gutenberg_Config_Option $config_option */
	private $config_option;

	public function __construct( \WPML_Gutenberg_Config_Option $config_option ) {
		$this->config_option = $config_option;
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 * @param string                 $type e.g. `xpath` or `key`
	 *
	 * @return array|null
	 */
	protected function get_block_config( \WP_Block_Parser_Block $block, $type ) {
		if ( null === $this->block_types ) {
			$this->block_types = $this->config_option->get();
		}

		if ( isset( $block->blockName, $this->block_types[ $block->blockName ][ $type ] ) ) {
			return $this->block_types[ $block->blockName ][ $type ];
		}

		if ( isset( $block->blockName ) ) {
			$block_name_arr  = explode( '/', $block->blockName );
			$block_namespace = reset( $block_name_arr );

			if ( isset( $this->block_types[ $block_namespace ][ $type ] ) ) {
				return $this->block_types[ $block_namespace ][ $type ];
			}
		}

		return null;
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	protected function get_string_type( $string ) {
		$type = 'LINE';

		if ( strpos( $string, "\n" ) !== false ) {
			$type = 'AREA';
		}

		if ( strpos( $string, '<' ) !== false ) {
			$type = 'VISUAL';
		}

		return $type;
	}

	/**
	 * @param string $id
	 * @param string $name
	 * @param string $text
	 * @param string $type
	 *
	 * @return object
	 */
	protected function build_string( $id, $name, $text, $type ) {
		return (object) array(
			'id'    => $id,
			'name'  => $name,
			'value' => $text,
			'type'  => $type,
		);
	}

	/**
	 * @param string $name
	 * @param string $text
	 *
	 * @return string
	 */
	protected function get_string_id( $name, $text ) {
		return md5( $name . $text );
	}
}
