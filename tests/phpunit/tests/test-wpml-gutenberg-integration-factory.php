<?php

use WPML\PB\Gutenberg\ReusableBlocks\Translation;
use tad\FunctionMocker\FunctionMocker;

/**
 * Class Test_WPML_Gutenberg_Integration_Factory
 *
 * @group page-builders
 * @group gutenberg
 */
class Test_WPML_Gutenberg_Integration_Factory extends OTGS_TestCase {

	/**
	 * @test
	 * @dataProvider dp_is_admin
	 *
	 * @param bool $is_admin
	 */
	public function it_creates_when_reusable_blocks_are_NOT_translatable( $is_admin ) {
		global $sitepress, $wpdb;

		\WP_Mock::userFunction( 'is_admin', [
			'return' => $is_admin,
		] );

		$sitepress = \Mockery::mock( 'SitePress' );
		$sitepress->shouldReceive( 'is_translated_post_type' )
		          ->with( WPML\PB\Gutenberg\ReusableBlocks\Translation::POST_TYPE )
		          ->andReturn( false );
		$sitepress->shouldReceive( 'get_active_languages' )->andReturn( [] );

		$wpdb      = \Mockery::mock( 'wpdb' );
		\Mockery::mock( 'WPML_ST_String_Factory' );
		\Mockery::mock( 'WPML_PB_Reuse_Translations' );
		\Mockery::mock( 'WPML_PB_String_Translation' );
		\Mockery::mock( '\WPML\TM\Container\Config' );
		$this->expect_container_make( 1, 'WPML_Translate_Link_Targets' );
		$translateLinks = \Mockery::mock( 'alias:WPML\PB\TranslateLinks' );
		$translateLinks->shouldReceive( 'getTranslatorForString' )->andReturn( function() {} );

		$this->expect_share_main_integration();

		$this->expect_container_make( 0, '\WPML\PB\Gutenberg\ReusableBlocks\Integration', '\WPML\PB\Gutenberg\Integration' );
		$this->expect_container_make( 0, '\WPML\PB\Gutenberg\ReusableBlocks\AdminIntegration', '\WPML\PB\Gutenberg\Integration' );

		$factory = new WPML_Gutenberg_Integration_Factory();

		$this->assertInstanceOf( \WPML\PB\Gutenberg\Integration::class, $factory->create() );

		unset( $sitepress );
	}

	/**
	 * @test
	 */
	public function it_creates_gutenberg_integration() {
		global $sitepress, $wpdb;

		\WP_Mock::userFunction( 'is_admin', [ 'return' => true ] );

		$sitepress = \Mockery::mock( 'SitePress' );
		$sitepress->shouldReceive( 'is_translated_post_type' )
		          ->with( WPML\PB\Gutenberg\ReusableBlocks\Translation::POST_TYPE )
		          ->andReturn( false );
		$sitepress->shouldReceive( 'get_active_languages' )->andReturn( [] );

		$wpdb      = \Mockery::mock( 'wpdb' );
		\Mockery::mock( 'WPML_ST_String_Factory' );
		\Mockery::mock( 'WPML_PB_Reuse_Translations' );
		\Mockery::mock( 'WPML_PB_String_Translation' );
		\Mockery::mock( '\WPML\TM\Container\Config' );
		$this->expect_container_make( 1, 'WPML_Translate_Link_Targets' );
		$translateLinks = \Mockery::mock( 'alias:WPML\PB\TranslateLinks' );
		$translateLinks->shouldReceive( 'getTranslatorForString' )->andReturn( function() {} );

		$factory = new WPML_Gutenberg_Integration_Factory();

		$this->assertInstanceOf( \WPML_Gutenberg_Integration::class, $factory->create_gutenberg_integration() );
	}


	/**
	 * @test
	 * @dataProvider dp_is_admin
	 *
	 * @param bool $is_admin
	 */
	public function it_creates_when_reusable_blocks_are_translatable( $is_admin ) {
		global $sitepress, $wpdb;

		$this->getMockBuilder( '\WPML_Translation_Basket' )->disableOriginalConstructor()->getMock();

		\WP_Mock::userFunction( 'is_admin', [
			'return' => $is_admin,
		] );

		$sitepress = \Mockery::mock( 'SitePress' );
		$sitepress->shouldReceive( 'is_translated_post_type' )
		          ->with( WPML\PB\Gutenberg\ReusableBlocks\Translation::POST_TYPE )
		          ->andReturn( true );
		$sitepress->shouldReceive( 'get_active_languages' )->andReturn( [] );

		$wpdb      = \Mockery::mock( 'wpdb' );
		\Mockery::mock( 'WPML_ST_String_Factory' );
		\Mockery::mock( 'WPML_PB_Reuse_Translations' );
		\Mockery::mock( 'WPML\PB\Gutenberg\StringsInBlock\StringsInBlock' );
		\Mockery::mock( '\WPML\TM\Container\Config' );
		$this->expect_container_make( 1, 'WPML_Translate_Link_Targets' );
		$translateLinks = \Mockery::mock( 'alias:WPML\PB\TranslateLinks' );
		$translateLinks->shouldReceive( 'getTranslatorForString' )->andReturn( function() {} );

		$this->expect_share_main_integration();

		$this->expect_container_make( 1, '\WPML\PB\Gutenberg\ReusableBlocks\Integration', 'WPML\PB\Gutenberg\Integration' );
		$this->expect_container_make( (int) $is_admin, '\WPML\PB\Gutenberg\ReusableBlocks\AdminIntegration', '\WPML\PB\Gutenberg\Integration' );
		$this->expect_container_make( (int) ! $is_admin, \WPML\PB\Gutenberg\Widgets\Block\DisplayTranslation::class, '\WPML\PB\Gutenberg\Integration' );
		$this->expect_container_make( 1, \WPML\PB\Gutenberg\Widgets\Block\RegisterStrings::class, '\WPML\PB\Gutenberg\Integration' );

		$factory = new WPML_Gutenberg_Integration_Factory();

		$this->assertInstanceOf( \WPML\PB\Gutenberg\Integration::class, $factory->create() );

		unset( $sitepress );
	}

	public function dp_is_admin() {
		return [
			[ true ],
			[ false ],
		];
	}

	private function expect_container_make( $times, $class, $interface = null ) {
		if ( $interface ) {
			$mock = \Mockery::mock( $interface );
		} else {
			$mock = \Mockery::mock( $class );
		}

		\WP_Mock::userFunction( 'WPML\Container\make', [
			'times'  => $times,
			'args'   => [ $class ],
			'return' => $mock,
		] );
	}

	private function expect_share_main_integration() {
		\WP_Mock::userFunction( 'WPML\Container\share', [
			'times'  => 1,
			'args'   => [ \WP_Mock\Functions::type( 'array' ) ],
		] );
	}
}
