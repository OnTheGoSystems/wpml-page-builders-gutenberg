<?php
/**
 * Test_WPML_Gutenberg_String_Registration class file.
 *
 * @package wpml-page-builders-gutenberg
 */

/**
 * Class Test_WPML_Gutenberg_String_Registration
 *
 * @group string-registration
 */
class Test_WPML_Gutenberg_String_Registration extends OTGS_TestCase {

	/**
	 * Test string registration.
	 *
	 * @test
	 */
	public function it_registers_strings() {
		$post               = \Mockery::mock( 'WP_Post' );
		$post->post_content = 'post content is not relevant in this test';

		$package = array(
			'kind' => WPML_Gutenberg_Integration::PACKAGE_ID,
		);

		$blocks = array(
			'normal block'                => $this->get_block( 'some name', 'some block content' ),
			'block without name'          => $this->get_block( '', 'some content' ),
			'block with empty content'    => $this->get_block( 'some name' ),
			'block with only white space' => $this->get_block( 'some name', "\n\r\t" ),
		);

		$inner_block = $this->get_block( 'inner block', 'inner block html' );

		$blocks['columns block']              = $this->get_block( 'columns', 'some block content' );
		$blocks['columns block']->innerBlocks = array( $inner_block );

		\WP_Mock::userFunction(
			'gutenberg_parse_blocks',
			array(
				'times'  => 1,
				'args'   => array( $post->post_content ),
				'return' => $blocks,
			)
		);

		foreach ( $blocks as $type => $block ) {
			$this->check_block_is_registered( $block, $type, $package );
		}

		\WP_Mock::expectAction( 'wpml_start_string_package_registration', $package );
		\WP_Mock::expectAction( 'wpml_delete_unused_package_strings', $package );

		$string_factory = $this->get_string_factory();

		$subject = $this->get_subject( $string_factory );

		$subject->register_strings( $post, $package );
	}

	/**
	 * Check if block is registered.
	 *
	 * @param WP_Block_Parser_Block $block   Block.
	 * @param string                $type    Block type.
	 * @param array                 $package Translation package.
	 */
	private function check_block_is_registered( WP_Block_Parser_Block $block, $type, $package ) {
		$block_name = isset( $block->blockName ) ? $block->blockName : '';

		$blocks_that_should_be_registered = array( 'normal block', 'columns block', 'inner block' );

		$times = 1;
		if ( $type ) {
			$times = in_array( $type, $blocks_that_should_be_registered, true ) ? 1 : 0;
		}

		$this->expectAction(
			'wpml_register_string',
			array(
				$block->innerHTML,
				md5( $block_name . $block->innerHTML ),
				$package,
				$block_name,
				'VISUAL',
			),
			$times
		);

		if ( isset( $block->innerBlocks ) ) {
			foreach ( $block->innerBlocks as $type => $block ) {
				$this->check_block_is_registered( $block, $type, $package );
			}
		}
	}

	/**
	 * Test set string location.
	 *
	 * @test
	 * @group wpmlcore-6325
	 */
	public function it_registers_strings_and_set_location() {
		$post               = \Mockery::mock( 'WP_Post' );
		$post->post_content = 'post content is not relevant in this test';

		$package = array(
			'kind' => WPML_Gutenberg_Integration::PACKAGE_ID,
		);

		$blocks = array(
			'block 1' => $this->get_block( 'some name 1', 'some block content 1' ),
			'block 2' => $this->get_block( 'some name 2', 'some block content 2' ),
		);

		$string_name_1 = md5( $blocks['block 1']->blockName . $blocks['block 1']->innerHTML );
		$string_id_1   = 123;

		$string_name_2 = md5( $blocks['block 2']->blockName . $blocks['block 2']->innerHTML );
		$string_id_2   = 456;

		\WP_Mock::userFunction(
			'gutenberg_parse_blocks',
			array(
				'times'  => 1,
				'args'   => array( $post->post_content ),
				'return' => $blocks,
			)
		);

		\WP_Mock::onFilter( 'wpml_string_id_from_package' )
		        ->with( 0, $package, $string_name_1, $blocks['block 1']->innerHTML )
		        ->reply( $string_id_1 );

		\WP_Mock::onFilter( 'wpml_string_id_from_package' )
		        ->with( 0, $package, $string_name_2, $blocks['block 2']->innerHTML )
		        ->reply( $string_id_2 );

		$strings_map = array(
			array( $string_id_1, $this->get_string( 1 ) ),
			array( $string_id_2, $this->get_string( 2 ) ),
		);

		$string_factory = $this->get_string_factory();
		$string_factory->method( 'find_by_id' )->willReturnMap( $strings_map );


		$subject = $this->get_subject( $string_factory );

		$subject->register_strings( $post, $package );
	}

	/**
	 * Test set wrap tag.
	 *
	 * @test
	 * @group wpmltm-3081
	 */
	public function it_registers_strings_and_set_wrap_tag() {
		$post               = \Mockery::mock( 'WP_Post' );
		$post->post_content = 'post content is not relevant in this test';

		$package = array(
			'kind' => WPML_Gutenberg_Integration::PACKAGE_ID,
		);

		$blocks = array(
			'block 1' => $this->get_block( 'core/heading', 'some block content 1', 1 ),
			'block 2' => $this->get_block( 'core/heading', 'some block content 2', 2 ),
		);

		$string_name_1 = md5( $blocks['block 1']->blockName . $blocks['block 1']->innerHTML );
		$string_id_1   = 123;

		$string_name_2 = md5( $blocks['block 2']->blockName . $blocks['block 2']->innerHTML );
		$string_id_2   = 456;

		\WP_Mock::userFunction(
			'gutenberg_parse_blocks',
			array(
				'times'  => 1,
				'args'   => array( $post->post_content ),
				'return' => $blocks,
			)
		);

		\WP_Mock::onFilter( 'wpml_string_id_from_package' )
		        ->with( 0, $package, $string_name_1, $blocks['block 1']->innerHTML )
		        ->reply( $string_id_1 );

		\WP_Mock::onFilter( 'wpml_string_id_from_package' )
		        ->with( 0, $package, $string_name_2, $blocks['block 2']->innerHTML )
		        ->reply( $string_id_2 );

		$strings_map = array(
			array( $string_id_1, $this->get_string_with_wrap_tag( 'h1' ) ),
			array( $string_id_2, $this->get_string_with_wrap_tag( 'h2' ) ),
		);

		$string_factory = $this->get_string_factory();
		$string_factory->method( 'find_by_id' )->willReturnMap( $strings_map );


		$subject = $this->get_subject( $string_factory );

		$subject->register_strings( $post, $package );
	}

	/**
	 * Test reuse translation.
	 *
	 * @test
	 */
	public function it_register_strings_and_reuse_translations() {
		$post               = \Mockery::mock( 'WP_Post' );
		$post->post_content = 'post content is not relevant in this test';

		$package = array(
			'kind' => WPML_Gutenberg_Integration::PACKAGE_ID,
		);

		$string_value_1   = 'some block content 1';
		$new_string_value = 'some NEW block content';

		$blocks = array(
			'block 1' => $this->get_block( 'some name 1', $string_value_1 ),
			'block 2' => $this->get_block( 'some name NEW', $new_string_value ),
		);

		$old_string_value = 'some OLD block';

		\WP_Mock::userFunction(
			'gutenberg_parse_blocks',
			array(
				'times'  => 1,
				'args'   => array( $post->post_content ),
				'return' => $blocks,
			)
		);

		$original_strings = array(
			$this->get_string_hash( $old_string_value ) => array( 'value' => $old_string_value ),
			$this->get_string_hash( $string_value_1 )   => array( 'value' => $string_value_1 ),
		);

		$current_strings = $original_strings;

		$current_strings[ $this->get_string_hash( $new_string_value ) ] = array( 'value' => $new_string_value );

		$leftover_strings = array(
			$this->get_string_hash( $old_string_value ) => array( 'value' => $old_string_value ),
		);

		$string_translation = $this->get_string_translation();
		$string_translation
			->method( 'get_package_strings' )
			->with( $package )
			->willReturnOnConsecutiveCalls( $original_strings, $current_strings );

		$reuse_translations = $this->get_reuse_translation();
		$reuse_translations
			->expects( $this->once() )
			->method( 'find_and_reuse_translations' )
			->with( $original_strings, $current_strings, $leftover_strings );

		$subject = $this->get_subject( null, $reuse_translations, $string_translation );

		$subject->register_strings( $post, $package );
	}

	/**
	 * Get test subject.
	 *
	 * @param WPML_ST_String_Factory|PHPUnit_Framework_MockObject_MockObject     $string_factory     String factory
	 *                                                                                               object.
	 * @param WPML_PB_Reuse_Translations|PHPUnit_Framework_MockObject_MockObject $reuse_translations Reuse translation
	 *                                                                                               object.
	 * @param WPML_PB_String_Translation|PHPUnit_Framework_MockObject_MockObject $string_translation String translation
	 *                                                                                               object.
	 *
	 * @return WPML_Gutenberg_Strings_Registration
	 */
	private function get_subject( $string_factory = null, $reuse_translations = null, $string_translation = null ) {
		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )->andReturn( array() );
		$strings_in_block   = $this->getStringsInBlock( $config_option );
		$string_factory     = $string_factory ? $string_factory : $this->get_string_factory();
		$reuse_translations = $reuse_translations ? $reuse_translations : $this->get_reuse_translation();
		$string_translation = $string_translation ? $string_translation : $this->get_string_translation( true );

		return new WPML_Gutenberg_Strings_Registration(
			$strings_in_block,
			$string_factory,
			$reuse_translations,
			$string_translation
		);
	}

	/**
	 * Get string factory mock.
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	private function get_string_factory() {
		return $this->getMockBuilder( 'WPML_ST_String_Factory' )
		            ->setMethods( array( 'find_by_id' ) )
		            ->disableOriginalConstructor()->getMock();
	}

	/**
	 * Get WPML_ST_String mock with expected location.
	 *
	 * @param int $expected_location Expected string location.
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject|WPML_ST_String
	 */
	private function get_string( $expected_location ) {
		$string = $this->getMockBuilder( 'WPML_ST_String' )
		               ->setMethods( array( 'set_location' ) )
		               ->disableOriginalConstructor()->getMock();
		$string->expects( $this->once() )->method( 'set_location' )->with( $expected_location );

		return $string;
	}

	/**
	 * Get WPML_ST_String mock with expected wrap tag.
	 *
	 * @param string $expected_wrap_tag Expected wrap tag.
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject|WPML_ST_String
	 */
	private function get_string_with_wrap_tag( $expected_wrap_tag ) {
		$string = $this->getMockBuilder( 'WPML_ST_String' )
		               ->setMethods( array( 'set_location', 'set_wrap_tag' ) )
		               ->disableOriginalConstructor()->getMock();
		$string->expects( $this->once() )->method( 'set_location' );
		$string->expects( $this->once() )->method( 'set_wrap_tag' )->with( $expected_wrap_tag );

		return $string;
	}

	/**
	 * Get WPML_PB_Reuse_Translations mock.
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	private function get_reuse_translation() {
		return $this->getMockBuilder( 'WPML_PB_Reuse_Translations' )
		            ->setMethods( array( 'find_and_reuse_translations' ) )
		            ->disableOriginalConstructor()->getMock();
	}

	/**
	 * Get WPML_PB_String_Translation mock.
	 *
	 * @param bool $passthru If pass through is set.
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	private function get_string_translation( $passthru = false ) {
		$string_translation = $this
			->getMockBuilder( 'WPML_PB_String_Translation' )
			->setMethods( array( 'get_package_strings', 'get_string_hash' ) )
			->disableOriginalConstructor()->getMock();
		$string_translation->method( 'get_string_hash' )->willReturnCallback( array( $this, 'get_string_hash' ) );

		if ( $passthru ) {
			$string_translation->method( 'get_package_strings' )->willReturn( array() );
		}

		return $string_translation;
	}

	/**
	 * Get string hash.
	 *
	 * @param string $string_value Content of the string.
	 *
	 * @return string
	 */
	public function get_string_hash( $string_value ) {
		return md5( $string_value );
	}

	/**
	 * Helper function to create a block.
	 *
	 * @param string $name  Block name.
	 * @param string $html  Block html.
	 * @param int    $level Block heading level.
	 *
	 * @return \WP_Block_Parser_Block|\Mockery\MockInterface
	 */
	private function get_block( $name = '', $html = '', $level = 0 ) {
		/**
		 * Block mock.
		 *
		 * @var \WP_Block_Parser_Block|\Mockery\MockInterface $block
		 */
		$block = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = null;
		$block->attrs     = null;

		if ( $name ) {
			$block->blockName = $name;
		}

		$block->innerHTML = $html; // Must present in block object.

		if ( $level ) {
			$block->attrs['level'] = $level;
		}

		return $block;
	}

	private function getStringsInBlock( $config_option ) {
		$string_parsers = [
			new WPML\PB\Gutenberg\StringsInBlock\HTML( $config_option ),
			new WPML\PB\Gutenberg\StringsInBlock\Attributes( $config_option ),
		];

		return new WPML\PB\Gutenberg\StringsInBlock\Collection( $string_parsers );
	}
}
