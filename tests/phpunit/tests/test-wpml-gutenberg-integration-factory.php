<?php

use WPML\PB\Gutenberg\ReusableBlocks\Translation;

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

		$wpdb      = \Mockery::mock( 'wpdb' );
		\Mockery::mock( 'WPML_ST_String_Factory' );
		\Mockery::mock( 'WPML_PB_Reuse_Translations' );
		\Mockery::mock( 'WPML_PB_String_Translation' );
		\Mockery::mock( '\WPML\TM\Container\Config' );

		\WP_Mock::userFunction( 'did_action', [
			'args'   => [ 'wpml_after_tm_loaded' ],
			'return' => true,
		] );

		$this->expect_container_make( 0, '\WPML\PB\Gutenberg\ReusableBlocks\Integration', '\WPML\PB\Gutenberg\Integration' );
		$this->expect_container_make( 0, '\WPML\PB\Gutenberg\ReusableBlocks\AdminIntegration', '\WPML\PB\Gutenberg\Integration' );

		$factory = new WPML_Gutenberg_Integration_Factory();

		$this->assertInstanceOf( \WPML\PB\Gutenberg\Integration::class, $factory->create() );

		unset( $sitepress );
	}

	/**
	 * @test
	 * @dataProvider dp_is_admin
	 * @runInSeparateProcess
	 * @preserveGlobalState false
	 *
	 * @param bool $is_admin
	 */
	public function it_creates_when_blocks_are_translatable_and_TM_DIC_is_missing( $is_admin ) {
		global $sitepress, $wpdb;

		\WP_Mock::userFunction( 'is_admin', [
			'return' => $is_admin,
		] );

		$sitepress = \Mockery::mock( 'SitePress' );
		$sitepress->shouldReceive( 'is_translated_post_type' )
		          ->with( WPML\PB\Gutenberg\ReusableBlocks\Translation::POST_TYPE )
		          ->andReturn( true );

		$wpdb      = \Mockery::mock( 'wpdb' );
		\Mockery::mock( 'WPML_ST_String_Factory' );
		\Mockery::mock( 'WPML_PB_Reuse_Translations' );
		\Mockery::mock( 'WPML_PB_String_Translation' );

		\WP_Mock::userFunction( 'did_action', [
			'args'   => [ 'wpml_after_tm_loaded' ],
			'return' => true,
		] );

		$this->expect_container_make( 0, '\WPML\PB\Gutenberg\ReusableBlocks\Integration', '\WPML\PB\Gutenberg\Integration' );
		$this->expect_container_make( 0, '\WPML\PB\Gutenberg\ReusableBlocks\AdminIntegration', '\WPML\PB\Gutenberg\Integration' );

		$factory = new WPML_Gutenberg_Integration_Factory();

		$this->assertInstanceOf( \WPML\PB\Gutenberg\Integration::class, $factory->create() );

		unset( $sitepress );
	}

	/**
	 * @test
	 * @group wpmltm-3548
	 * @dataProvider dp_is_admin
	 *
	 * @param bool $is_admin
	 */
	public function it_creates_when_blocks_are_translatable_and_tm_is_not_loaded( $is_admin ) {
		global $sitepress, $wpdb;

		\WP_Mock::userFunction( 'is_admin', [
			'return' => $is_admin,
		] );

		$sitepress = \Mockery::mock( 'SitePress' );
		$sitepress->shouldReceive( 'is_translated_post_type' )
		          ->with( WPML\PB\Gutenberg\ReusableBlocks\Translation::POST_TYPE )
		          ->andReturn( true );

		$wpdb      = \Mockery::mock( 'wpdb' );
		\Mockery::mock( 'WPML_ST_String_Factory' );
		\Mockery::mock( 'WPML_PB_Reuse_Translations' );
		\Mockery::mock( 'WPML_PB_String_Translation' );
		\Mockery::mock( '\WPML\TM\Container\Config' );

		\WP_Mock::userFunction( 'did_action', [
			'args'   => [ 'wpml_after_tm_loaded' ],
			'return' => false,
		] );

		$this->expect_container_make( 0, '\WPML\PB\Gutenberg\ReusableBlocks\Integration', '\WPML\PB\Gutenberg\Integration' );
		$this->expect_container_make( 0, '\WPML\PB\Gutenberg\ReusableBlocks\AdminIntegration', '\WPML\PB\Gutenberg\Integration' );

		$factory = new WPML_Gutenberg_Integration_Factory();

		$this->assertInstanceOf( \WPML\PB\Gutenberg\Integration::class, $factory->create() );

		unset( $sitepress );
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

		$wpdb      = \Mockery::mock( 'wpdb' );
		\Mockery::mock( 'WPML_ST_String_Factory' );
		\Mockery::mock( 'WPML_PB_Reuse_Translations' );
		\Mockery::mock( 'WPML\PB\Gutenberg\StringsInBlock\StringsInBlock' );
		\Mockery::mock( '\WPML\TM\Container\Config' );

		\WP_Mock::userFunction( 'did_action', [
			'args'   => [ 'wpml_after_tm_loaded' ],
			'return' => true,
		] );

		$this->expect_container_make( 1, '\WPML\PB\Gutenberg\ReusableBlocks\Integration', 'WPML\PB\Gutenberg\Integration' );
		$this->expect_container_make( (int) $is_admin, '\WPML\PB\Gutenberg\ReusableBlocks\AdminIntegration', '\WPML\PB\Gutenberg\Integration' );

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
}
