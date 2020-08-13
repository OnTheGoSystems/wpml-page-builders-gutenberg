<?php

namespace WPML\PB\Gutenberg\StringsInBlock\DOMHandler;

class HtmlBlock extends DOMHandle {

	/**
	 * @param \DOMNode $element
	 * @param string   $context
	 *
	 * @return string
	 */
	protected function getInnerHTMLFromChildNodes( \DOMNode $element, $context ) {
		$innerHTML = '';
		$children  = $element->childNodes;

		foreach ( $children as $child ) {
			$innerHTML .= $this->getAsHTML5( $child );
		}

		return $innerHTML;
	}

	/**
	 * @param \DOMNode $clone
	 * @param \DOMNode $element
	 */
	protected function appendExtraChildNodes( \DOMNode $clone, \DOMNode $element ) {

	}

	/**
	 * @param \DOMNode $element
	 * @param string   $context
	 *
	 * @return array
	 */
	protected function getInnerHTML( \DOMNode $element, $context ) {
		$innerHTML = $element instanceof \DOMText
			? $element->nodeValue
			: $this->getInnerHTMLFromChildNodes( $element, $context );

		return [
			$this->removeCdataFromStyleTag( html_entity_decode( $innerHTML ) ),
			'AREA'
		];
	}
}
