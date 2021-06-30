<?php

namespace WPML\PB\Gutenberg\Widgets\Block;

use WPML\FP\Obj;


trait WidgetMock {

	private $gutenbergIntegration;
	private $contentToParse;

	private $widgetFns;
	private $blockWidgetStrings = [];

	public function setUpWidgetBlock() {
		$this->gutenbergIntegration = $this->mockMake( 'WPML_Gutenberg_Integration' );
		$this->gutenbergIntegration->shouldReceive( 'parse_blocks' )->andReturnUsing( function ( $content ) {
			return Obj::propOr( [], $content, $this->contentToParse );
		} );

		$factory = $this->mockMake( 'WPML_Gutenberg_Integration_Factory' );
		$factory->shouldReceive( 'create_gutenberg_integration' )->andReturnUsing( function () {
			return $this->gutenbergIntegration;
		} );

		$this->widgetFns = $this->mockMake( Fns::class );
		$this->widgetFns->shouldReceive( 'getBlockWidgetStrings' )->andReturnUsing( function () {
			return $this->blockWidgetStrings;
		} );
	}

	public function addContentToParse( $content, $expectedBlocks ) {
		$this->contentToParse[ $content ] = $expectedBlocks;
	}

	public function setBlockWidgetStrings( $string ) {
		$this->blockWidgetStrings = $string;
	}
}