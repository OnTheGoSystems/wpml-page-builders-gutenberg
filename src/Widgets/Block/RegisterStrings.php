<?php

namespace WPML\PB\Gutenberg\Widgets\Block;

use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use WPML\PB\Gutenberg\Widgets\Block\Fns as WidgetFns;
use function WPML\Container\make;
use function WPML\FP\curryN;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class RegisterStrings implements \IWPML_REST_Action {
	public function add_hooks() {
		$registerStrings = Fns::memorize( function ( $oldValue, $newValue ) {
			$gutenbergIntegration = make( 'WPML_Gutenberg_Integration_Factory' )->create_gutenberg_integration();
			$blocks               = $this->getBlocks( $gutenbergIntegration, $newValue );

			$gutenbergIntegration->register_strings_from_widget( $blocks, $this->createPackage() );
			$this->regenerateTranslatedOptions( $blocks );
		} );

		Hooks::onAction( 'update_option_widget_block', 10, 2 )
		     ->then( spreadArgs( $registerStrings ) );
	}

	private function regenerateTranslatedOptions( $blocks ) {
		$stringToTranslate = make( WidgetFns::class )->getBlockWidgetStrings();
		$generate          = curryN( 3, [ make( SaveTranslations::class ), 'generateOptionForLanguage' ] );

		Fns::map( $generate( $blocks, Fns::__, $stringToTranslate ), Languages::getSecondaryCodes() );
	}

	private function getBlocks( $gutenbergIntegration, $options ) {
		$getContent = Logic::ifElse( 'is_scalar', Fns::always( null ), Obj::prop( 'content' ) );

		$fn = pipe(
			Fns::map( $getContent ),
			Fns::filter( Fns::identity() ),
			Fns::unary( 'array_unique' ),
			Fns::map( [ $gutenbergIntegration, 'parse_blocks' ] ),
			Lst::flattenToDepth( 1 )
		);

		return $fn( $options );
	}

	public function createPackage() {
		return [
			'kind'    => 'Block',
			'name'    => 'Widget',
			'title'   => 'Widget',
			'post_id' => null,
		];
	}
}