<?php

/**
 * Class Test_WPML_Gutenberg_Integration
 *
 * @group page-builders
 * @group gutenberg
 */
class Test_WPML_Gutenberg_Integration extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_adds_hooks() {
		$config_option = new WPML_Gutenberg_Config_Option();

		$subject = new WPML_Gutenberg_Integration(
			new WPML_Gutenberg_Strings_In_Block( $config_option ),
			$config_option
		);

		WP_Mock::expectFilterAdded( 'wpml_page_builder_support_required', array(
			$subject,
			'page_builder_support_required'
		), 10, 1 );
		WP_Mock::expectActionAdded( 'wpml_page_builder_register_strings', array(
			$subject,
			'register_strings'
		), 10, 2 );
		WP_Mock::expectActionAdded( 'wpml_page_builder_string_translated', array(
			$subject,
			'string_translated'
		), 10, 5 );
		WP_Mock::expectFilterAdded( 'wpml_config_array', array( $subject, 'wpml_config_filter' ) );
		WP_Mock::expectFilterAdded( 'wpml_pb_should_body_be_translated', array(
			$subject,
			'should_body_be_translated_filter'
		), PHP_INT_MAX, 3 );

		$subject->add_hooks();
	}

	public function it_requires_support() {

		$subject = new WPML_Gutenberg_Integration(
			new WPML_Gutenberg_Strings_In_Block(),
			new WPML_Gutenberg_Config_Option()
		);

		$plugins = $subject->page_builder_support_required( array() );

		$this->assertCount( 1, $plugins );
		$this->assertEquals( 'Gutenberg', $plugins[0] );

	}

	/**
	 * @test
	 */
	public function it_registers_strings() {
		$subject = $this->get_subject();

		$post               = \Mockery::mock( 'WP_Post' );
		$post->post_content = 'post content';

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
	 */
	public function it_updates_translated_page() {
		$subject = $this->get_subject();

		$original_post               = \Mockery::mock( 'WP_Post' );
		$original_post->post_content = 'Post content';

		$translated_post_id = 22;

		$target_lang = 'de';

		$block_name                  = 'some-block-name';
		$core_block_name             = 'core/' . $block_name; // Gutenberg prefixes with 'core/'
		$attributes                  = array( 'att_1' => 'value_1', 'att_2' => 'value_2' );
		$original_block_inner_HTML   = 'some block content';
		$translated_block_inner_HTML = 'some block content ( TRANSLATED )';


		$strings = array(
			md5( $core_block_name . $original_block_inner_HTML ) => array(
				$target_lang => array(
					'value'  => $translated_block_inner_HTML,
					'status' => (string) ICL_TM_COMPLETE,
				)
			)
		);

		$blocks             = array();
		$block              = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName   = $core_block_name;
		$block->attrs       = $attributes;
		$block->innerHTML   = $original_block_inner_HTML;
		$block->innerBlocks = array();
		$blocks[]           = $block;

		\WP_Mock::userFunction( 'gutenberg_parse_blocks',
			array(
				'args'   => array( $original_post->post_content ),
				'return' => $blocks,
			)
		);

		$rendered_block = '<!-- wp:' . $block_name . ' ' . json_encode( $attributes ) . ' -->' . $translated_block_inner_HTML . '<!-- /wp:' . $block_name . ' -->';

		\WP_Mock::userFunction( 'wp_update_post',
			array(
				'times' => 1,
				'args'  => array( array( 'ID' => $translated_post_id, 'post_content' => $rendered_block ) ),
			) );

		$subject->string_translated(
			WPML_Gutenberg_Integration::PACKAGE_ID,
			$translated_post_id,
			$original_post,
			$strings,
			$target_lang
		);

	}

	/**
	 * @test
	 *
	 * @dataProvider inner_html_provider
	 */
	public function it_updates_inner_blocks( $inner_html, $inner_html_before, $inner_html_after ) {
		$subject = $this->get_subject();

		$original_post               = \Mockery::mock( 'WP_Post' );
		$original_post->post_content = 'Post content';

		$translated_post_id = 22;

		$target_lang = 'de';

		$block_name                  = 'some-block-name';
		$core_block_name             = 'core/' . $block_name; // Gutenberg prefixes with 'core/'
		$attributes                  = array( 'att_1' => 'value_1', 'att_2' => 'value_2' );
		$original_block_inner_HTML   = 'some block content';
		$translated_block_inner_HTML = 'some block content ( TRANSLATED )';


		$strings = array(
			md5( $core_block_name . $original_block_inner_HTML ) => array(
				$target_lang => array(
					'value'  => $translated_block_inner_HTML,
					'status' => (string) ICL_TM_COMPLETE,
				)
			)
		);

		$blocks = array();

		$inner_block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$inner_block->blockName = $core_block_name;
		$inner_block->attrs     = $attributes;
		$inner_block->innerHTML = $original_block_inner_HTML;

		$column_block = \Mockery::mock( 'WP_Block_Parser_Block' );
		$column_block->blockName  = 'core/column';
			$column_block->innerHTML   = $inner_html;
			$column_block->attrs       = array();
			$column_block->innerBlocks = array( $inner_block );

		$blocks[] = $column_block;

		\WP_Mock::userFunction( 'gutenberg_parse_blocks',
			array(
				'args'   => array( $original_post->post_content ),
				'return' => $blocks,
			)
		);

		$rendered_block = "<!-- wp:column -->";
		$rendered_block .= $inner_html_before;
		$rendered_block .= '<!-- wp:' . $block_name . ' ' . json_encode( $attributes ) . ' -->' . $translated_block_inner_HTML . '<!-- /wp:' . $block_name . ' -->';
		$rendered_block .= $inner_html_after;
		$rendered_block .= "<!-- /wp:column -->";

		\WP_Mock::userFunction( 'wp_update_post',
			array(
				'times' => 1,
				'args'  => array( array( 'ID' => $translated_post_id, 'post_content' => $rendered_block ) ),
			) );

		$subject->string_translated(
			WPML_Gutenberg_Integration::PACKAGE_ID,
			$translated_post_id,
			$original_post,
			$strings,
			$target_lang
		);

	}

	public function inner_html_provider() {

		return array(
			array( '<div class="wp-block-column"></div>', '<div class="wp-block-column">', '</div>' ),
			array( "<div class=\"wp-block-column\">\n\n</div>", "<div class=\"wp-block-column\">\n", "\n</div>" ),
			array(
				"<div class=\"wp-block-column\">\r\n\r\n</div>",
				"<div class=\"wp-block-column\">\r\n",
				"\r\n</div>"
			)
		);
	}

	/**
	 * @test
	 * @group wpmlcore-5923
	 */
	public function it_should_not_alter_body_be_translated_if_context_is_translate_images_in_post_content() {
		$post = $this->get_post( '<!-- wp:core -->Some content<! /wp:core -->' );

		$subject = $this->get_subject();

		$this->assertFalse( $subject->should_body_be_translated_filter( false, $post, 'some_context' ) );
		$this->assertTrue( $subject->should_body_be_translated_filter( true, $post, 'some_context' ) );
	}

	/**
	 * @test
	 * @group wpmlcore-5923
	 */
	public function it_should_not_alter_body_be_translated_if_not_a_gutenberg_post() {
		$post = $this->get_post( 'Hello [shortcode]there![/shortcode]' );

		$subject = $this->get_subject();

		$this->assertFalse( $subject->should_body_be_translated_filter( false, $post, 'translate_images_in_post_content' ) );
		$this->assertTrue( $subject->should_body_be_translated_filter( true, $post, 'translate_images_in_post_content' ) );
	}

	/**
	 * @test
	 * @group wpmlcore-5923
	 */
	public function it_should_return_true_for_body_be_translated() {
		$post = $this->get_post( '<!-- wp:core -->Some content<! /wp:core -->' );

		$subject = $this->get_subject();

		$this->assertTrue( $subject->should_body_be_translated_filter( false, $post, 'translate_images_in_post_content' ) );
		$this->assertTrue( $subject->should_body_be_translated_filter( true, $post, 'translate_images_in_post_content' ) );
	}

	public function get_subject( $config_option = null ) {
		if ( ! $config_option ) {
			$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
			$config_option->shouldReceive( 'get' )->andReturn( array() );
		}

		return new WPML_Gutenberg_Integration(
			new WPML_Gutenberg_Strings_In_Block( $config_option ),
			$config_option
		);
	}

	/**
	 * @param string content
	 *
	 * @return WP_Post|PHPUnit_Framework_MockObject_MockObject
	 */
	private function get_post( $content ) {
		$post = $this->getMockBuilder( 'WP_Post' )->getMock();
		$post->post_content = $content;
		return $post;
	}
}
