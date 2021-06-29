<?php

namespace WPML\PB\Gutenberg\Widgets\Block;

use tad\FunctionMocker\FunctionMocker;
use WPML\FP\Fns;
use WPML\FP\Obj;


trait WidgetMock {

	private $gutenbergIntegration;
	private $contentToParse;

	private $widgetFns;
	private $blockWidgetStrings = [];

	private $stringsInMOFile = [];

	public function setUpWidgetBlock() {
		$this->gutenbergIntegration = $this->mockMake( 'WPML_Gutenberg_Integration' );
		$this->gutenbergIntegration->shouldReceive( 'parse_blocks' )->andReturnUsing( function ( $content ) {
			return Obj::propOr( [], $content, $this->contentToParse );
		} );

		FunctionMocker::replace( Strings::class . '::loadStringsFromMOFile', function ( $domain, $locale ) {
			return Obj::pathOr( [], [ $domain, $locale ], $this->stringsInMOFile );
		} );
	}

	public function addContentToParse( $content, $expectedBlocks ) {
		$this->contentToParse[ $content ] = $expectedBlocks;
	}

	public function setBlockWidgetStrings( $string ) {
		$this->blockWidgetStrings = $string;
	}

	public function setStringsInMOFile( $domain, $locale, $strings ) {
		$translations = [];
		foreach ( $strings as $string => $translatedString ) {
			$translations[ $string ] = [ 'translations' => [ $translatedString ] ];
		}

		$this->stringsInMOFile[ $domain ][ $locale ] = $translations;
	}
}