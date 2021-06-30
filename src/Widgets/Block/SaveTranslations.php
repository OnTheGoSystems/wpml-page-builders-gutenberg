<?php

namespace WPML\PB\Gutenberg\Widgets\Block;

use WPML\PB\Gutenberg\Widgets\Block\Fns as WidgetFns;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\Option;
use WPML\ST\API\Fns as StrFns;
use function WPML\Container\make;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class SaveTranslations implements \IWPML_AJAX_Action, \IWPML_Backend_Action {

	public function add_hooks() {
		Hooks::onAction( 'icl_st_add_string_translation', 10, 1 )
		     ->then( spreadArgs( function ( $stringTranslationId ) {
			     $this->getString( $stringTranslationId )
			          ->filter( Relation::propEq( 'context', 'block-Widget' ) )
			          ->map( function ( $string ) {
				          self::generateOptionForLanguage( $this->loadBlocks(), Obj::prop( 'language', $string ), make(WidgetFns::class)->getBlockWidgetStrings() );
			          } );
		     } ) );
	}

	/**
	 * @param \WP_Block_Parser_Block[] $blocks
	 * @param string $lang
	 * @param array $stringTranslations
	 */
	public function generateOptionForLanguage( array $blocks, $lang, $stringTranslations ) {
		$gutenbergIntegration = make( 'WPML_Gutenberg_Integration_Factory' )->create_gutenberg_integration();
		$translatedBlocks     = $gutenbergIntegration->update_block_translations( $blocks, $stringTranslations, $lang );
		$translatedBlocks     = Fns::map( [ $gutenbergIntegration, 'render_block' ], $translatedBlocks );

		$translatedBlocks = Fns::map( pipe( Lst::makePair( Fns::__, null ), Lst::zipObj( [ 'content', 'title' ] ) ), $translatedBlocks );
		Option::update( 'widget_block_' . $lang, $translatedBlocks );
	}

	public function getString( $stringTranslationId ) {
		return Maybe::of( $stringTranslationId )
		            ->map( StrFns::getStringTranslationById() )
		            ->chain( function ( $stringTranslation ) {
			            return Maybe::of( $stringTranslation )
			                        ->map( pipe( Obj::prop( 'string_id' ), StrFns::getStringById() ) )
			                        ->map( Obj::assoc( 'language', Obj::prop( 'language', $stringTranslation ) ) );
		            } );
	}

	public function loadBlocks() {
		$getContent = Logic::ifElse( 'is_scalar', Fns::always( null ), Obj::prop( 'content' ) );

		$parseBlocks = pipe(
			Fns::map( pipe( $getContent, [ 'WPML_Gutenberg_Integration', 'parse_blocks' ] ) ),
			Lst::flattenToDepth( 1 )
		);

		return $parseBlocks( Option::get( 'widget_block' ) );
	}
}