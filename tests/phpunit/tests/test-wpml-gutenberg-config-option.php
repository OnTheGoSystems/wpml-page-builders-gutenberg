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

		$block_type            = 'block/type';
		$block_xpath           = array( array( 'xpath1' ), array( 'xpath2' ) );
		$expected_block_config = array(
			'xpath' => array( 'xpath1', 'xpath2' ),
		);

		$block_data = array(
			'attr'  => array( 'type' => $block_type, 'translate' => '1' ),
			'xpath' => $block_xpath,
		);

		$config_settings = array(
			'wpml-config' => array(
				'gutenberg-blocks' => array(
					'gutenberg-block' => array( $block_data ),
				),
			),
		);

		\WP_Mock::userFunction( 'update_option',
		                        array(
			                        'times' => 1,
			                        'args'  => array(
				                        WPML_Gutenberg_Config_Option::OPTION,
				                        array( $block_type => $expected_block_config ),
			                        ),
		                        ) );

		$subject->update_from_config( $config_settings );
	}

	/**
	 * @test
	 * @group wpmlcore-6606
	 */
	public function it_updates_option_from_config_with_xpath_and_key() {
		$subject = new WPML_Gutenberg_Config_Option();

		$block_type   = 'block/type';
		$block_xpaths = array( array( 'xpath1' ), array( 'xpath2' ) );
		$block_keys   = array(
			'value' => '',
			'attr' => array( 'name'  => 'key1' ),
		);

		$expected_block_config = array(
			'xpath' => array( 'xpath1', 'xpath2' ),
			'key'   => array( 'key1' => 1 ),
		);

		$block_data = array(
			'attr'  => array( 'type' => $block_type, 'translate' => '1' ),
			'xpath' => $block_xpaths,
			'key'   => $block_keys,
		);

		$config_settings = array(
			'wpml-config' => array(
				'gutenberg-blocks' => array(
					'gutenberg-block' => array( $block_data ),
				),
			),
		);

		\WP_Mock::userFunction( 'update_option',
		                        array(
			                        'times' => 1,
			                        'args'  => array(
				                        WPML_Gutenberg_Config_Option::OPTION,
				                        array( $block_type => $expected_block_config ),
			                        ),
		                        ) );

		$subject->update_from_config( $config_settings );
	}

	/**
	 * @test
	 * @group wpmlcore-6606
	 */
	public function it_updates_option_from_config_with_recursive_keys() {
		$subject = new WPML_Gutenberg_Config_Option();

		$block_type = 'block/type';
		$block_keys = array(
			array(
				'value' => '',
				'attr'  => array( 'name' => 'key1' ),
			),
			array(
				'value' => '',
				'attr'  => array( 'name' => 'key2' ),
				'key'   => array(
					array(
						'value' => '',
						'attr'  => array( 'name' => 'key21' ),
					),
					array(
						'value' => '',
						'attr'  => array( 'name' => 'key22' ),
						'key'   => array(
							'value' => '',
							'attr'  => array( 'name' => 'key221' ),
						),
					),
				),
			),
			array(
				'value' => '',
				'attr'  => array( 'name' => 'key3' ),
				'key'   => array(
					'value' => '',
					'attr'  => array( 'name' => 'key31' ),
				),
			),
		);

		$expected_block_config = array(
			'xpath' => array(),
			'key'   => array(
				'key1' => 1,
				'key2' => array(
					'key21' => 1,
					'key22' => array(
						'key221' => 1,
					),
				),
				'key3' => array(
					'key31' => 1,
				),
			),
		);

		$block_data = array(
			'attr'  => array( 'type' => $block_type, 'translate' => '1' ),
			'xpath' => array(),
			'key'   => $block_keys,
		);

		$config_settings = array(
			'wpml-config' => array(
				'gutenberg-blocks' => array(
					'gutenberg-block' => array( $block_data ),
				),
			),
		);

		\WP_Mock::userFunction( 'update_option',
		                        array(
			                        'times' => 1,
			                        'args'  => array(
				                        WPML_Gutenberg_Config_Option::OPTION,
				                        array( $block_type => $expected_block_config ),
			                        ),
		                        ) );

		$subject->update_from_config( $config_settings );
	}

	/**
	 * @test
	 */
	public function it_gets_the_option() {
		$subject = new WPML_Gutenberg_Config_Option();

		$option_data = array( 'some', 'data' );

		\WP_Mock::userFunction( 'get_option',
		                        array(
			                        'times'  => 1,
			                        'args'   => array( WPML_Gutenberg_Config_Option::OPTION, array() ),
			                        'return' => $option_data,
		                        ) );

		$this->assertEquals( $option_data, $subject->get() );
	}
}
