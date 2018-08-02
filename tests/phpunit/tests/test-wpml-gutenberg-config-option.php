<?php

/**
 * Class Test_WPML_Gutenberg_Config_Option
 *
 * @group page-builders
 * @group gutenberg
 */
class Test_WPML_Gutenberg_Integration_Config_Option extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_updates_option_from_config() {
		$subject = new WPML_Gutenberg_Config_Option();

		$block_type = 'block/type';
		$block_xpath = array( array( 'xpath1' ), array( 'xpath2' ) );
		$expected_block_path = array( 'xpath1', 'xpath2' );

		$block_data = array(
			'attr' => array( 'type' => $block_type ),
			'xpath' => $block_xpath,
		);

		$config_settings = array(
			'wpml-config' => array(
				'gutenberg-blocks' => array(
					'gutenberg-block' => array( $block_data ),
				)
			)
		);

		\WP_Mock::userFunction( 'update_option',
			array(
				'times' => 1,
				'args' => array( WPML_Gutenberg_Config_Option::OPTION, array( $block_type => $expected_block_path ) )
			)
		);


		$subject->update_from_config( $config_settings );

	}

	/**
	 * @test
	 */
	public function it_gets_the_option() {
		$subject = new WPML_Gutenberg_Config_Option();

		$option_data = array( 'some', 'data');

		\WP_Mock::userFunction( 'get_option',
			array(
				'times' => 1,
				'args' => array( WPML_Gutenberg_Config_Option::OPTION, array() ),
				'return' => $option_data,
			)
		);

		$this->assertEquals( $option_data, $subject->get() );
	}
}
