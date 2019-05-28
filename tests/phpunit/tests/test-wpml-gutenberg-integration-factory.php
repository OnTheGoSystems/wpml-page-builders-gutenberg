<?php

/**
 * Class Test_WPML_Gutenberg_Integration_Factory
 *
 * @group page-builders
 * @group gutenberg
 */
class Test_WPML_Gutenberg_Integration_Factory extends OTGS_TestCase {

	public function tearDown() {
		global $wpml_translation_job_factory;
		unset( $wpml_translation_job_factory );
		parent::tearDown();
	}
	
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

		$this->expect_container_make( 0, '\WPML\PB\Gutenberg\ReusableBlocks\Integration', '\WPML\PB\Gutenberg\Integration' );
		$this->expect_container_share( 0 );
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

		$this->expect_container_make( 1, '\WPML\PB\Gutenberg\ReusableBlocks\Integration', 'WPML\PB\Gutenberg\Integration' );
		$this->expect_container_share( (int) $is_admin );
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

	private function expect_container_share( $times ) {
		global $wpml_translation_job_factory;

		$wpml_translation_job_factory = $this->createMock( '\WPML_Translation_Job_Factory' );
		$admin_notices                = $this->createMock( '\WPML_Notices' );

		\WP_Mock::userFunction( 'wpml_get_admin_notices', [
			'return' => $admin_notices,
		] );

		\WP_Mock::userFunction( 'WPML\Container\share', [
			'times'  => $times,
			'args'   => [ [ $wpml_translation_job_factory, $admin_notices ] ],
		] );
	}
}
