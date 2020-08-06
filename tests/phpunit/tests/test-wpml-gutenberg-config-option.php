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
		$block_xpaths = [ [ 'xpath1' ], [ 'xpath2' ] ];
		$block_keys = [
			'value' => '',
			'attr'  => [
				'name'          => 'key1',
				'search-method' => 'regex',
				'label'         => 'Key label',
			],
		];

		$expected_block_config = [
			'xpath' => [ 'xpath1', 'xpath2' ],
			'key'   => [ 'key1' => [ 'search-method' => 'regex', 'label' => 'Key label' ] ],
			'label' => 'Block label',
		];

		$block_data = [
			'attr'  => [ 'type' => $block_type, 'translate' => '1', 'label' => 'Block label' ],
			'xpath' => $block_xpaths,
			'key'   => $block_keys,
		];

		$config_settings = [
			'wpml-config' => [
				'gutenberg-blocks' => [
					'gutenberg-block' => [ $block_data ],
				],
			],
		];

		\WP_Mock::userFunction( 'update_option',
			[
				'times' => 1,
				'args'  => [
					WPML_Gutenberg_Config_Option::OPTION,
					[ $block_type => $expected_block_config ],
				],
			] );

		$subject->update_from_config( $config_settings );
	}



	/**
	 * @test
	 * @group wpmlcore-7069
	 */
	public function it_updates_option_from_config_with_only_one_xpath_containing_type_attribute() {
		$subject = new WPML_Gutenberg_Config_Option();

		$blockType = 'block/type';
		$xpath     = '//my/xpath';
		$type      = 'link';

		$blockDate = [
			'attr'  => [ 'type' => $blockType, 'translate' => '1' ],
			'xpath' => [
				// For a single element, it's not wrapped in a array
				// For multiple elements, each is wrapped in an array.
				'value' => $xpath,
				'attr'  => [ 'type' => $type, 'label' => 'XPath Label' ],
			],
		];

		$expectedBlockConfig = [
			'xpath' => [
				[
					'value' => $xpath,
					'type'  => strtoupper( $type ),
					'label' => 'XPath Label'
				],
			],
		];

		$configSettings = [
			'wpml-config' => [
				'gutenberg-blocks' => [
					'gutenberg-block' => [ $blockDate ],
				],
			],
		];

		\WP_Mock::userFunction( 'update_option',
			[
				'times' => 1,
				'args'  => [
					WPML_Gutenberg_Config_Option::OPTION,
					[ $blockType => $expectedBlockConfig ],
				],
			] );

		$subject->update_from_config( $configSettings );
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
				'attr'  => array(
					'name'          => 'key1',
					'search-method' => 'regex',
				),
			),
			array(
				'value' => '',
				'attr'  => array(
					'name'          => 'key2',
					'search-method' => 'wildcards',
				),
				'key'   => array(
					array(
						'value' => '',
						'attr'  => array(
							'name'          => 'key21',
							'search-method' => 'wildcards',
						),
					),
					array(
						'value' => '',
						'attr'  => array(
							'name'          => 'key22',
						),
						'key'   => array(
							'value' => '',
							'attr'  => array(
								'name'          => 'key221',
								'search-method' => 'regex',
							),
						),
					),
				),
			),
			array(
				'value' => '',
				'attr'  => array(
					'name'          => 'key3',
					'search-method' => 'wildcards',
				),
				'key'   => array(
					'value' => '',
					'attr'  => array( 'name' => 'key31' ), // no search-method
				),
			),
		);

		$expected_block_config = array(
			'xpath' => array(),
			'key'   => array(
				'key1' => array( 'search-method' => 'regex' ),
				'key2' => array(
					'search-method' => 'wildcards',
					'children'      => array(
						'key21' => array( 'search-method' => 'wildcards' ),
						'key22' => array(
							'children' => array(
								'key221' => array( 'search-method' => 'regex' ),
							),
						),
					),
				),
				'key3' => array(
					'search-method' => 'wildcards',
					'children'      => array(
						'key31' => array(),
					),
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
