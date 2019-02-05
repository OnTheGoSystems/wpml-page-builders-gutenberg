<?php

/**
 * @group string-registration
 */
class Test_WPML_Gutenberg_String_Registration extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_registers_strings() {
		$post               = \Mockery::mock( 'WP_Post' );
		$post->post_content = '<!-- wp:something -->post content<!-- /wp:something -->';

		$package = array(
			'kind' => WPML_Gutenberg_Integration::PACKAGE_ID,
		);

		$blocks = array();

		$blocks['normal block']            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$blocks['normal block']->blockName = 'some name';
		$blocks['normal block']->innerHTML = 'some block content';

		$blocks['block without name']            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$blocks['block without name']->innerHTML = 'some content';

		$blocks['block with empty content']            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$blocks['block with empty content']->blockName = 'some name';
		$blocks['block with empty content']->innerHTML = '';

		$blocks['block with only white space']            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$blocks['block with only white space']->blockName = 'some name';
		$blocks['block with only white space']->innerHTML = "\n\r\t";

		$inner_block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$inner_block->blockName = 'inner block';
		$inner_block->innerHTML = 'inner block html';

		$blocks['columns block']              = \Mockery::mock( 'WP_Block_Parser_Block' );
		$blocks['columns block']->blockName   = 'columns';
		$blocks['columns block']->innerBlocks = array( $inner_block );
		$blocks['columns block']->innerHTML   = 'some block content';

		\WP_Mock::userFunction( 'gutenberg_parse_blocks',
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

		$string_factory   = $this->get_string_factory();

		$subject = $this->get_subject( $string_factory );

		$subject->register_strings( $post, $package );
	}

	private function check_block_is_registered( WP_Block_Parser_Block $block, $type, $package ) {
		$block_name = isset( $block->blockName ) ? $block->blockName : '';

		$blocks_that_should_be_registered = array( 'normal block', 'columns block', 'inner block' );

		$this->expectAction( 'wpml_register_string',
		                     array(
			                     $block->innerHTML,
			                     md5( $block_name . $block->innerHTML ),
			                     $package,
			                     $block_name,
			                     'VISUAL'
		                     ),
		                     in_array( $type, $blocks_that_should_be_registered ) ? 1 : 0
		);

		if ( isset( $block->innerBlocks ) ) {
			foreach ( $block->innerBlocks as $type => $block ) {
				$this->check_block_is_registered( $block, $type, $package );
			}
		}
	}

	/**
	 * @test
	 * @group wpmlcore-6325
	 */
	public function it_registers_strings_and_set_location() {
		$post               = \Mockery::mock( 'WP_Post' );
		$post->post_content = '<!-- wp:something -->post content<!-- /wp:something -->';

		$package = array(
			'kind' => WPML_Gutenberg_Integration::PACKAGE_ID,
		);

		$blocks = array();

		$blocks['block 1']            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$blocks['block 1']->blockName = 'some name 1';
		$blocks['block 1']->innerHTML = 'some block content 1';
		$string_name_1                = md5( $blocks['block 1']->blockName . $blocks['block 1']->innerHTML );
		$string_id_1                  = 123;

		$blocks['block 2']            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$blocks['block 2']->blockName = 'some name 2';
		$blocks['block 2']->innerHTML = 'some block content 2';
		$string_name_2                = md5( $blocks['block 2']->blockName . $blocks['block 2']->innerHTML );
		$string_id_2                  = 456;

		\WP_Mock::userFunction( 'gutenberg_parse_blocks',
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

	private function get_subject( $string_factory = null ) {
		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )->andReturn( array() );
		$strings_in_block = new WPML_Gutenberg_Strings_In_Block( $config_option );
		$string_factory   = $string_factory ? $string_factory : $this->get_string_factory();

		return new WPML_Gutenberg_Strings_Registration( $strings_in_block, $string_factory );
	}

	private function get_string_factory() {
		return $this->getMockBuilder( 'WPML_ST_String_Factory' )
            ->setMethods( array( 'find_by_id' ) )
            ->disableOriginalConstructor()->getMock();
	}

	/**
	 * @param int $expected_location
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
}
