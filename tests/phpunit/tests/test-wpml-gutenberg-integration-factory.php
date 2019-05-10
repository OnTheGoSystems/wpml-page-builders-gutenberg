<?php

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
	public function it_creates( $is_admin ) {
		global $sitepress, $wpdb;

		\WP_Mock::userFunction( 'is_admin', [
			'return' => $is_admin,
		] );

		$sitepress = \Mockery::mock( 'SitePress' );
		$wpdb      = \Mockery::mock( 'wpdb' );
		\Mockery::mock( 'WPML_ST_String_Factory' );
		\Mockery::mock( 'WPML_PB_Reuse_Translations' );
		\Mockery::mock( 'WPML\PB\Gutenberg\StringsInBlock\StringsInBlock' );

		\WP_Mock::userFunction( 'WPML\Container\make', [
			'args'   => [ '\WPML_Translation_Basket' ],
			'return' => $this->getMockBuilder( '\WPML_Translation_Basket' )->getMock(),
		] );

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
}
