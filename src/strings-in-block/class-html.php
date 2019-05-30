<?php

namespace WPML\PB\Gutenberg\StringsInBlock;

class HTML extends Base {

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return array
	 */
	public function find( \WP_Block_Parser_Block $block ) {
		$strings = array();

		$block_queries = $this->get_block_queries( $block );

		if ( is_array( $block_queries ) && isset( $block->innerHTML ) ) {

			$xpath = $this->get_domxpath( $block->innerHTML );

			foreach ( $block_queries as $query ) {
				$elements = $xpath->query( $query );
				foreach ( $elements as $element ) {
					list( $text, $type ) = $this->get_inner_HTML( $element );
					if ( $text ) {
						$string_id = $this->get_string_id( $block->blockName, $text );
						$strings[] = $this->build_string( $string_id, $block->blockName, $text, $type );
					}
				}
			}

		} else {

			$string_id = $this->get_block_string_id( $block );
			if ( $string_id ) {
				$strings[] = $this->build_string( $string_id, $block->blockName, $block->innerHTML, 'VISUAL' );
			}

		}

		return $strings;
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 * @param array                  $string_translations
	 * @param string                 $lang
	 *
	 * @return \WP_Block_Parser_Block
	 */
	public function update( \WP_Block_Parser_Block $block, array $string_translations, $lang ) {

		$block_queries = $this->get_block_queries( $block );

		if ( $block_queries && isset( $block->innerHTML ) ) {

			$dom   = $this->get_dom( $block->innerHTML );
			$xpath = new \DOMXPath( $dom );
			foreach ( $block_queries as $query ) {
				$elements = $xpath->query( $query );
				foreach ( $elements as $element ) {
					list( $text, ) = $this->get_inner_HTML( $element );
					$string_id = $this->get_string_id( $block->blockName, $text );
					if (
						isset( $string_translations[ $string_id ][ $lang ] ) &&
						ICL_TM_COMPLETE == $string_translations[ $string_id ][ $lang ]['status']
					) {
						$translation = $string_translations[ $string_id ][ $lang ]['value'];
						$block = $this->update_string_in_innerContent( $block, $element, $translation );
						$this->set_element_value( $element, $translation );
					}
				}
			}
			list( $block->innerHTML, ) = $this->get_inner_HTML( $dom->documentElement );

		} else {

			$string_id = $this->get_block_string_id( $block );
			if (
				isset( $string_translations[ $string_id ][ $lang ] ) &&
				ICL_TM_COMPLETE == $string_translations[ $string_id ][ $lang ]['status']
			) {
				$block->innerHTML = $string_translations[ $string_id ][ $lang ]['value'];
			}

		}

		return $block;
	}

	/**
	 * This is required when a block has innerBlocks and translatable content at the root.
	 * Unfortunately we cannot use the DOM because we have only HTML extracts which
	 * are not valid taken independently.
	 *
	 * e.g. {
	 *          innerContent => [
	 *              '<div><p>The title</p>',
	 *              null,
	 *              '\n\n',
	 *              null,
	 *              '</div>'
	 *          ]
	 *      }
	 *
	 * @param \WP_Block_Parser_Block $block
	 * @param \DOMNode               $element
	 * @param string                 $translation
	 *
	 * @return \WP_Block_Parser_Block
	 */
	private function update_string_in_innerContent( \WP_Block_Parser_Block $block, \DOMNode $element, $translation ) {
		if ( empty( $block->innerContent ) ) {
			return $block;
		}

		if ( $element instanceof \DOMAttr ) {
			$search = '/(")(' . $element->nodeValue . ')(")/';
		} else {
			$search = '/(>)(' . $element->nodeValue . ')(<)/';
		}

		foreach ( $block->innerContent as &$inner_content ) {
			if ( $inner_content ) {
				$inner_content = preg_replace( $search, '$1' . $translation . '$3', $inner_content );
			}
		}

		return $block;
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return null|string
	 */
	private function get_block_string_id( \WP_Block_Parser_Block $block ) {
		if ( isset( $block->blockName, $block->innerHTML ) && '' !== trim( $block->innerHTML ) ) {
			return $this->get_string_id( $block->blockName, $block->innerHTML );
		} else {
			return null;
		}
	}

	/**
	 * @param \DOMNode $element
	 *
	 * @return array
	 */
	private function get_inner_HTML( \DOMNode $element ) {
		$innerHTML = "";
		$children  = $element->childNodes;

		foreach ( $children as $child ) {
			$innerHTML .= $element->ownerDocument->saveHTML( $child );
		}

		$type = $this->get_string_type( $innerHTML );

		if ( 'VISUAL' !== $type ) {
			$innerHTML = html_entity_decode( $innerHTML );
		}

		return array( $innerHTML, $type );
	}

	/**
	 * @param \DOMNode $element
	 * @param string  $value
	 */
	private function set_element_value( \DOMNode $element, $value ) {
		if ( $element instanceof \DOMAttr ) {
			$element->parentNode->setAttribute( $element->name, $value );
		} else {
			$clone = $this->clone_node_without_children( $element );

			$fragment = $this->get_dom( $value )->firstChild; // Skip the wrapping div
			foreach ( $fragment->childNodes as $child ) {
				$clone->appendChild( $element->ownerDocument->importNode( $child, true ) );
			}

			$element->parentNode->replaceChild( $clone, $element );
		}
	}

	/**
	 * @param \DOMNode $element
	 *
	 * @return \DOMNode
	 */
	private function clone_node_without_children( \DOMNode $element ) {
		return $element->cloneNode( false );
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 *
	 * @return array|null
	 */
	private function get_block_queries( \WP_Block_Parser_Block $block ) {
		return $this->get_block_config( $block, 'xpath' );
	}

	/**
	 * @param string $html
	 *
	 * @return \DOMDocument
	 */
	private function get_dom( $html ) {
		$dom = new \DOMDocument();
		\libxml_use_internal_errors( true );
		$html = mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' );
		$dom->loadHTML( '<div>' . $html . '</div>' );
		\libxml_clear_errors();

		// Remove doc type and <html> <body> wrappers
		$dom->removeChild( $dom->doctype );
		$dom->replaceChild( $dom->firstChild->firstChild->firstChild, $dom->firstChild );

		return $dom;
	}

	/**
	 * @param string $html
	 *
	 * @return \DOMXPath
	 */
	private function get_domxpath( $html ) {
		$dom = $this->get_dom( $html );

		return new \DOMXPath( $dom );
	}
}
