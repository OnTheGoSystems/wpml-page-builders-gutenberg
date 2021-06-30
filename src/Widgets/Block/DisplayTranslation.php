<?php

namespace WPML\PB\Gutenberg\Widgets\Block;

use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\Option;
use function WPML\FP\spreadArgs;

class DisplayTranslation implements \IWPML_Frontend_Action {

	public function add_hooks() {

		Hooks::onFilter( 'option_widget_block', 10, 1 )
		     ->then( spreadArgs( function ( $content ) {
				global $sitepress;
			     return Option::getOr( 'widget_block_' . $sitepress->get_current_language(), $content );
		     } ) );
	}
}