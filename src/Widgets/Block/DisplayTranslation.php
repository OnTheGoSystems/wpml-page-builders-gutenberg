<?php

namespace WPML\PB\Gutenberg\Widgets\Block;

use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\LIB\WP\Hooks;
use function WPML\Container\make;
use function WPML\FP\spreadArgs;

class DisplayTranslation implements \IWPML_Frontend_Action, \WPML\PB\Gutenberg\Integration {

	const PRIORITY_BEFORE_REMOVE_BLOCK_MARKUP = 0;

	public function add_hooks() {
		$getStringsFromMOFile = Fns::memorize( [ Strings::class, 'fromMo' ] );

		Hooks::onFilter( 'widget_block_content', self::PRIORITY_BEFORE_REMOVE_BLOCK_MARKUP )
		     ->then( spreadArgs( function ( $content ) use ( $getStringsFromMOFile ) {
			     $strings = $getStringsFromMOFile( \get_locale() );

			     $imageTranslator = make( \WPML_Media_Translated_Images_Update::class );

			     if ( $imageTranslator ) {
				     $content = $imageTranslator->replace_images_with_translations( $content, Languages::getCurrentCode() );
			     }

			     return make( \WPML_Gutenberg_Integration::class )
				     ->replace_strings_in_blocks( $content, $strings, Languages::getCurrentCode() );
		     } ) );
	}
}
