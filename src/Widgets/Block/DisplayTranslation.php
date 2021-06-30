<?php

namespace WPML\PB\Gutenberg\Widgets\Block;

use WPML\LIB\WP\Hooks;
use function WPML\Container\make;
use function WPML\FP\spreadArgs;

class DisplayTranslation implements \IWPML_Frontend_Action, \WPML\PB\Gutenberg\Integration {

	public function add_hooks() {
		Hooks::onFilter( 'widget_block_content', 0 )
			->then( spreadArgs( function( $content ) {
				global $sitepress;

				$strings = Strings::fromMo( get_locale() );

				return make( \WPML_Gutenberg_Integration_Factory::class )
					->create_gutenberg_integration()
					->replace_strings_in_blocks( $content, $strings, $sitepress->get_current_language() );
			} ) );
	}
}