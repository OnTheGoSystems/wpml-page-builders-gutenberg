<?php

/**
 * Class WPML_Gutenberg_Strings_In_Block
 */
class WPML_Gutenberg_Strings_In_Block {

	/** @var array */
	private $block_types;

	/** @var WPML_Gutenberg_Config_Option $config_option */
	private $config_option;

	public function __construct( WPML_Gutenberg_Config_Option $config_option ) {
		$this->config_option = $config_option;
	}

	/**
	 * @param array $block
	 *
	 * @return array
	 */
	public function find( $block ) {
		$strings = array();

		$block_queries = $this->get_block_queries( $block );

		if ( $block_queries ) {

			$xpath = $this->get_domxpath( $block );

			foreach ( $block_queries as $query ) {
				$elements = $xpath->query( $query );
				foreach ( $elements as $element ) {
					list( $text, $type ) = $this->get_inner_HTML( $element );
					if ( $text ) {
						$strings[] = (object) array(
							'id'    => $this->get_string_id( $block['blockName'], $text ),
							'name'  => $block['blockName'],
							'value' => $text,
							'type'  => $type,
						);
					}
				}
			}

		} else {

			$string_id = $this->get_block_string_id( $block );
			if ( $string_id ) {
				$strings[] = (object) array(
					'id'    => $string_id,
					'name'  => $block['blockName'],
					'value' => $block['innerHTML'],
					'type'  => 'VISUAL',
				);
			}

		}

		return $strings;
	}

	/**
	 * @param array $block
	 * @param array $string_translations
	 * @param string $lang
	 *
	 * @return array
	 */
	public function update( $block, $string_translations, $lang ) {

		$block_queries = $this->get_block_queries( $block );

		if ( $block_queries ) {

			$dom   = $this->get_dom( $block );
			$xpath = new DOMXPath( $dom );
			foreach ( $block_queries as $query ) {
				$elements = $xpath->query( $query );
				foreach ( $elements as $element ) {
					list( $text, ) = $this->get_inner_HTML( $element );
					$string_id = $this->get_string_id( $block['blockName'], $text );
					if (
						isset( $string_translations[ $string_id ][ $lang ] ) &&
						ICL_TM_COMPLETE == $string_translations[ $string_id ][ $lang ]['status']
					) {
						$this->set_element_value( $element, $string_translations[ $string_id ][ $lang ]['value'] );
					}
				}
			}
			list( $block['innerHTML'], ) = $this->get_inner_HTML( $dom->documentElement );

		} else {

			$string_id = $this->get_block_string_id( $block );
			if (
				isset( $string_translations[ $string_id ][ $lang ] ) &&
				ICL_TM_COMPLETE == $string_translations[ $string_id ][ $lang ]['status']
			) {
				$block['innerHTML'] = $string_translations[ $string_id ][ $lang ]['value'];
			}

		}

		return $block;

	}

	/**
	 * @param array $block
	 *
	 * @return null|string
	 */
	private function get_block_string_id( $block ) {
		if ( isset( $block['blockName'], $block['innerHTML'] ) && '' !== trim( $block['innerHTML'] ) ) {
			return $this->get_string_id( $block['blockName'], $block['innerHTML'] );
		} else {
			return null;
		}
	}

	/**
	 * @param string $name
	 * @param string $text
	 *
	 * @return string
	 */
	private function get_string_id( $name, $text ) {
		return md5( $name . $text );
	}

	/**
	 * @param DOMNode $element
	 *
	 * @return array
	 */
	private function get_inner_HTML( DOMNode $element ) {
		$innerHTML = "";
		$children  = $element->childNodes;

		foreach ( $children as $child ) {
			$innerHTML .= $element->ownerDocument->saveHTML( $child );
		}

		$type = 'LINE';
		if ( strpos( $innerHTML, "\n" ) !== false ) {
			$type = 'AREA';
		}
		if ( strpos( $innerHTML, '<' ) !== false ) {
			$type = 'VISUAL';
		}

		return array( $innerHTML, $type );
	}

	/**
	 * @param DOMNode $element
	 * @param string $value
	 */
	private function set_element_value( DOMNode $element, $value ) {
		if ( $element instanceof DOMAttr ) {
			$element->parentNode->setAttribute( $element->name, $value );
		} else {
			$fragment = $element->ownerDocument->createDocumentFragment();
			$fragment->appendXML( $value );
			$clone = $element->cloneNode(); // Get element copy without children
			$clone->appendChild( $fragment );
			$element->parentNode->replaceChild( $clone, $element );
		}
	}

	/**
	 * @param array $block
	 *
	 * @return array|null
	 */
	private function get_block_queries( $block ) {
		if ( null === $this->block_types ) {
			$this->block_types = $this->config_option->get();
		}

		if ( isset( $block['blockName'], $block['innerHTML'] ) && array_key_exists( $block['blockName'], $this->block_types ) ) {
			return $this->block_types[ $block['blockName'] ];
		} else {
			return null;
		}
	}

	/**
	 * @param array $block
	 *
	 * @return DOMDocument
	 */
	private function get_dom( $block ) {
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<div>' . $block['innerHTML'] . '</div>');
		libxml_clear_errors();

		// Remove doc type and <html> <body> wrappers
		$dom->removeChild( $dom->doctype );
		$dom->replaceChild( $dom->firstChild->firstChild->firstChild, $dom->firstChild );

		return $dom;
	}

	/**
	 * @param array $block
	 *
	 * @return DOMXPath
	 */
	private function get_domxpath( $block ) {
		$dom = $this->get_dom( $block );

		return new DOMXPath( $dom );
	}

}
