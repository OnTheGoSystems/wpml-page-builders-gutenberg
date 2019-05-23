<?php

namespace WPML\PB\Gutenberg\StringsInBlock;

/**
 * @group page-builders
 * @group gutenberg
 * @group strings-in-block
 */
class TestAttributes extends \OTGS_TestCase {

	const BLOCK_NAMESPACE = 'block';
	const BLOCK_NAME      = 'block/type';

	/**
	 * @test
	 */
	public function it_should_not_find_attribute_strings_if_blockname_is_not_set() {
		$config_array = [
			self::BLOCK_NAME => [
				'key' => [],
			],
		];

		$block = $this->getBlock();
		$block->attrs = [ 'foo' => 'bar' ];

		$config  = $this->getConfig( $config_array );
		$subject = $this->getSubject( $config );

		$strings = $subject->find( $block );

		$this->assertEmpty( $strings );
	}

	/**
	 * @test
	 */
	public function it_should_find_attribute_strings() {
		$config_array = [
			self::BLOCK_NAME => [
				'key' => [
					'*1'   => [
						'search-method' => \WPML_Gutenberg_Config_Option::SEARCH_METHOD_WILDCARD,
						'children'      => [ 'key12' => [] ],
					],
					'key2' => [
						'children' => [
							'key22' => [
								'children' => [ 'key*' => [] ],
							],
							'key*'  => [],
						],
					],
					'key3' => [],
				],
			],
			self::BLOCK_NAMESPACE => [
				'key' => []
			],
		];

		$block = $this->getBlock();
		$block->blockName = self::BLOCK_NAME;
		$block->attrs = [
			'key1' => [
				'key11' => [
					'key111' => 'String for key111',
					'key112' => 'String for key112',
				],
				'key12' => 'String for key12', // registrered
			],
			'key2' => [
				'key21' => [
					'key211' => "String for key211\nSecond line", // registrered
					'key212' => 'String for key212<br>second line', // registrered
				],
				'key22' => 'String for key22', // registrered
			],
			'key3' => 'String for key3', // registered
			'key4' => 'String for key4',
		];

		$config  = $this->getConfig( $config_array );
		$subject = $this->getSubject( $config );

		$strings = $subject->find( $block );

		$this->assertCount( 5, $strings );
		$this->checkString( $strings[0], 'String for key12', 'LINE' );
		$this->checkString( $strings[1], "String for key211\nSecond line", 'AREA' );
		$this->checkString( $strings[2], 'String for key212<br>second line', 'VISUAL' );
		$this->checkString( $strings[3], 'String for key22', 'LINE' );
		$this->checkString( $strings[4], 'String for key3', 'LINE' );
	}

	/**
	 * @test
	 */
	public function it_should_find_attribute_strings_with_regex() {
		$config_array = [
			self::BLOCK_NAME => [
				'key' => [
					// all strings not starting with _
					'/^[^_]\S+$/' => [
						'search-method' => \WPML_Gutenberg_Config_Option::SEARCH_METHOD_REGEX,
					],
				],
			],
		];

		$block = $this->getBlock();
		$block->blockName = self::BLOCK_NAME;
		$block->attrs = [
			'_something' => 'String for _something',
			'something'  => 'String for something',
		];

		$config  = $this->getConfig( $config_array );
		$subject = $this->getSubject( $config );

		$strings = $subject->find( $block );

		$this->assertCount( 1, $strings );
		$this->checkString( $strings[0], 'String for something', 'LINE' );
	}

	/**
	 * @test
	 */
	public function it_should_not_register_numbers() {
		$config_array = [
			self::BLOCK_NAME => [
				'key' => [
					'key1' => [],
					'key2' => [],
				],
			],
		];

		$block = $this->getBlock();
		$block->blockName = self::BLOCK_NAME;
		$block->attrs = [
			'key1' => '123',
			'key2' => 123,
		];

		$config  = $this->getConfig( $config_array );
		$subject = $this->getSubject( $config );

		$strings = $subject->find( $block );

		$this->assertCount( 0, $strings );
	}

	/**
	 * @test
	 */
	public function it_should_find_attribute_strings_with_config_from_block_namespace() {
		$config_array = [
			self::BLOCK_NAMESPACE => [
				'key' => [
					'foo' => [],
				],
			],
		];

		$block = $this->getBlock();
		$block->blockName = self::BLOCK_NAME;
		$block->attrs = [
			'foo' => 'String for foo',
			'bar' => 'String for bar',
		];

		$config  = $this->getConfig( $config_array );
		$subject = $this->getSubject( $config );

		$strings = $subject->find( $block );

		$this->assertCount( 1, $strings );
		$this->checkString( $strings[0], 'String for foo', 'LINE' );
	}

	private function checkString( \stdClass $string, $value, $type ) {
		$this->assertEquals( md5( self::BLOCK_NAME . $value ), $string->id, $value );
		$this->assertEquals( self::BLOCK_NAME, $string->name );
		$this->assertEquals( $value, $string->value );
		$this->assertEquals( $type, $string->type );
	}

	/**
	 * @test
	 */
	public function it_should_not_update_attributes_if_blockname_is_not_set() {
		$config_array = [
			self::BLOCK_NAME => [
				'key' => [],
			],
		];

		$block = $this->getBlock();
		$block->attrs = [ 'foo' => 'bar' ];

		$config  = $this->getConfig( $config_array );
		$subject = $this->getSubject( $config );

		$updated_block = $subject->update( $block, [], 'fr' );

		$this->assertEquals( $block, $updated_block );
	}

	/**
	 * @test
	 */
	public function it_should_update_attributes() {
		$lang = 'fr';

		$config_array = [
			self::BLOCK_NAME => [
				'key' => [
					'*1'   => [
						'search-method' => \WPML_Gutenberg_Config_Option::SEARCH_METHOD_WILDCARD,
						'children'      => [ 'key12' => [] ]	,
					],
					'key2' => [
						'children' => [
							'key22' => [
								'children' => [ 'key*' => [] ],
							],
							'key*'  => [],
						],
					],
					'key3' => [],
				],
			],
			self::BLOCK_NAMESPACE => [
				'key' => []
			],
		];

		$block = $this->getBlock();
		$block->blockName = self::BLOCK_NAME;
		$block->attrs = [
			'key1' => [
				'key11' => [
					'key111' => 'Original string A',
					'key112' => 'Original string B',
				],
				'key12' => 'Original string A', // translated
			],
			'key2' => [
				'key21' => [
					'key211' => "Original string B", // translated
					'key212' => 'Original string A', // translated
				],
				'key22' => 'Original string B', // translated
			],
			'key3' => 'Original string A', // translated
			'key4' => 'Original string B',
			'key5' => 'Original string C', // not translated because status in progress
		];

		$translations = [
			md5( self::BLOCK_NAME . 'Original string A' ) => [
				$lang => [
					'status' => ICL_TM_COMPLETE,
					'value'  => 'Translated string A',
				],
			],
			md5( self::BLOCK_NAME . 'Original string B' ) => [
				$lang => [
					'status' => ICL_TM_COMPLETE,
					'value'  => 'Translated string B',
				],
			],
			md5( self::BLOCK_NAME . 'Original string C' ) => [
				$lang => [
					'status' => ICL_TM_IN_PROGRESS,
					'value'  => 'Translated string C',
				],
			],
		];

		$expected_attrs = [
			'key1' => [
				'key11' => [
					'key111' => 'Original string A',
					'key112' => 'Original string B',
				],
				'key12' => 'Translated string A', // translated
			],
			'key2' => [
				'key21' => [
					'key211' => "Translated string B", // translated
					'key212' => 'Translated string A', // translated
				],
				'key22' => 'Translated string B', // translated
			],
			'key3' => 'Translated string A', // translated
			'key4' => 'Original string B',
			'key5' => 'Original string C', // not translated because status in progress
		];

		$config  = $this->getConfig( $config_array );
		$subject = $this->getSubject( $config );

		$updated_block = $subject->update( $block, $translations, $lang );

		$this->assertEquals( $expected_attrs, $updated_block->attrs );
	}

	/**
	 * @test
	 */
	public function it_should_update_attributes_with_regex() {
		$lang = 'fr';

		$config_array = [
			self::BLOCK_NAME => [
				'key' => [
					// all strings not starting with _
					'/^[^_]\S+$/' => [
						'search-method' => \WPML_Gutenberg_Config_Option::SEARCH_METHOD_REGEX,
					],
				],
			],
		];

		$block = $this->getBlock();
		$block->blockName = self::BLOCK_NAME;
		$block->attrs = [
			'_something' => 'Original string A',
			'something'  => 'Original string A',
		];

		$translations = [
			md5( self::BLOCK_NAME . 'Original string A' ) => [
				$lang => [
					'status' => ICL_TM_COMPLETE,
					'value'  => 'Translated string A',
				],
			],
		];

		$expected_attrs = [
			'_something' => 'Original string A',
			'something'  => 'Translated string A',
		];

		$config  = $this->getConfig( $config_array );
		$subject = $this->getSubject( $config );

		$updated_block = $subject->update( $block, $translations, $lang );

		$this->assertEquals( $expected_attrs, $updated_block->attrs );
	}

	/**
	 * @test
	 */
	public function it_should_update_attributes_with_config_from_block_namespace() {
		$lang = 'fr';

		$config_array = [
			self::BLOCK_NAMESPACE => [
				'key' => [
					'foo' => [],
				],
			],
		];

		$block = $this->getBlock();
		$block->blockName = self::BLOCK_NAME;
		$block->attrs = [
			'foo' => 'String for foo',
			'bar' => 'String for bar',
		];

		$translations = [
			md5( self::BLOCK_NAME . 'String for foo' ) => [
				$lang => [
					'status' => ICL_TM_COMPLETE,
					'value'  => 'Translated string for foo',
				],
			],
		];

		$expected_attrs = [
			'foo' => 'Translated string for foo',
			'bar' => 'String for bar',
		];

		$config  = $this->getConfig( $config_array );
		$subject = $this->getSubject( $config );

		$updated_block = $subject->update( $block, $translations, $lang );

		$this->assertEquals( $expected_attrs, $updated_block->attrs );
	}

	private function getSubject( $config ) {
		return new Attributes( $config );
	}

	private function getConfig( array $config_array ) {
		$config = $this->getMockBuilder( '\WPML_Gutenberg_Config_Option' )
			->setMethods( [ 'get' ] )->disableOriginalConstructor()->getMock();
		$config->method( 'get' )->willReturn( $config_array );

		return $config;
	}

	private function getBlock() {
		$block = $this->getMockBuilder( '\WP_Block_Parser_Block' )
		            ->disableOriginalConstructor()->getMock();

		$block->blockName = null;
		$block->attrs     = null;

		return $block;
	}
}
