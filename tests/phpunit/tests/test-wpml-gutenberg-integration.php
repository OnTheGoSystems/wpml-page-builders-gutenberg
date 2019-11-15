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
	public function it_should_implement_integration_interface() {
		$this->assertInstanceOf( \WPML\PB\Gutenberg\Integration::class, $this->get_subject() );
	}

	/**
	 * @test
	 */
	public function it_adds_hooks() {
		\Mockery::mock( 'WP_Post' );

		$config_option = new WPML_Gutenberg_Config_Option();

		$subject = new WPML_Gutenberg_Integration(
			$this->getStringsInBlock( $config_option ),
			$config_option,
			$this->get_strings_registration()
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

		WP_Mock::expectFilterAdded( 'wpml_get_translatable_types', array( $subject, 'remove_package_strings_type_filter' ), 11 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_requires_support() {
		\Mockery::mock( 'WP_Post' );

		$config_option = new WPML_Gutenberg_Config_Option();

		$subject = new WPML_Gutenberg_Integration(
			$this->getStringsInBlock( $config_option ),
			$config_option,
			$this->get_strings_registration()
		);

		$plugins = $subject->page_builder_support_required( array() );

		$this->assertCount( 1, $plugins );
		$this->assertEquals( 'Gutenberg', $plugins[0] );

	}

	/**
	 * @test
	 */
	public function it_should_not_register_strings_if_not_a_post_built_with_the_block_editor() {
		\Mockery::mock( 'WP_Post' );

		$string_registration = $this->get_strings_registration();
		$string_registration->expects( $this->never() )->method( 'register_strings' );

		$subject = $this->get_subject( null, $string_registration );

		$post               = \Mockery::mock( 'WP_Post' );
		$post->post_content = 'post content with no block meta comment';

		$package = array(
			'kind' => WPML_Gutenberg_Integration::PACKAGE_ID,
		);

		$subject->register_strings( $post, $package );

	}

	/**
	 * @test
	 */
	public function it_registers_strings() {
		$post               = \Mockery::mock( 'WP_Post' );
		$post->post_content = '<!-- wp:something -->post content<!-- /wp:something -->';

		$package = array(
			'kind' => WPML_Gutenberg_Integration::PACKAGE_ID,
		);

		$strings_registration = $this->get_strings_registration();
		$strings_registration->expects( $this->once() )
		                    ->method( 'register_strings' )
		                    ->with( $post, $package );

		$subject = $this->get_subject( null, $strings_registration );

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
	 * @group wpmlcore-6221
	 */
	public function it_updates_translated_page() {
		$original_post               = \Mockery::mock( 'WP_Post' );
		$original_post->post_content = 'Post content';

		$translated_post_id = 22;

		$target_lang = 'de';

		$block_name                  = 'some-block-name';
		$core_block_name             = 'core/' . $block_name; // Gutenberg prefixes with 'core/'
		$attributes                  = array(
			'att_1' => 'value_1',
			'att_2' => 'value_2',
			'att_3' => 'polish żółć',
			'att_4' => 'Value with "quotes"',
		);
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

		$rendered_block = '<!-- wp:' . $block_name . ' ' . json_encode( $attributes, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . ' -->' . $translated_block_inner_HTML . '<!-- /wp:' . $block_name . ' -->';

		\WP_Mock::userFunction( 'wpml_update_escaped_post', [
			'times' => 1,
			'args'  => [
				[ 'ID' => $translated_post_id, 'post_content' => $rendered_block ],
				$target_lang
			],
		] );

		$subject = $this->get_subject();

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
	 * @group wpmlcore-5996
	 * @group wpmlcore-6221
	 */
	public function it_updates_translated_page_with_empty_attributes_in_block() {
		$original_post               = \Mockery::mock( 'WP_Post' );
		$original_post->post_content = 'Post content';

		$translated_post_id = 22;

		$target_lang = 'de';

		$block_name                  = 'some-block-name';
		$core_block_name             = 'core/' . $block_name; // Gutenberg prefixes with 'core/'
		$attributes                  = new stdClass();
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

		$rendered_block = '<!-- wp:' . $block_name . ' -->' . $translated_block_inner_HTML . '<!-- /wp:' . $block_name . ' -->';

		\WP_Mock::userFunction( 'wpml_update_escaped_post', [
				'times' => 1,
				'args'  => [
					[ 'ID' => $translated_post_id, 'post_content' => $rendered_block ],
					$target_lang
				],
		] );

		$subject = $this->get_subject();

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
	 * @group wpmlcore-6221
	 *
	 * @dataProvider inner_html_provider
	 */
	public function it_updates_inner_blocks_with_guess_parts( $inner_html, $inner_html_before, $inner_html_after ) {
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

		\WP_Mock::userFunction( 'wpml_update_escaped_post', [
			'times' => 1,
			'args'  => [
				[ 'ID' => $translated_post_id, 'post_content' => $rendered_block ],
				$target_lang,
			],
		] );

		$subject = $this->get_subject();

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
	 * @group wpmlcore-6074
	 * @group wpmlcore-6221
	 */
	public function it_updates_media_text_inner_blocks() {
		$inner_html = '<div class="wp-block-media-text__content"></div>';
		$inner_html_before = '<div class="wp-block-media-text__content">';
		$inner_html_after = '</div>';

		$original_post               = \Mockery::mock( 'WP_Post' );
		$original_post->post_content = 'Post content';

		$translated_post_id = 22;

		$target_lang = 'de';

		$block_name                  = 'media-text';
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
		$column_block->blockName  = 'core/' . $block_name;
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

		$rendered_block = "<!-- wp:media-text -->";
		$rendered_block .= $inner_html_before;
		$rendered_block .= '<!-- wp:' . $block_name . ' ' . json_encode( $attributes ) . ' -->' . $translated_block_inner_HTML . '<!-- /wp:' . $block_name . ' -->';
		$rendered_block .= $inner_html_after;
		$rendered_block .= "<!-- /wp:media-text -->";

		\WP_Mock::userFunction( 'wpml_update_escaped_post', [
			'times' => 1,
			'args'  => [
				[ 'ID' => $translated_post_id, 'post_content' => $rendered_block ],
				$target_lang,
			],
		] );

		$subject = $this->get_subject();

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
	 * @group wpmlcore-6330
	 */
	public function it_updates_inner_blocks_with_innerContent() {
		$original_post               = \Mockery::mock( 'WP_Post' );
		$original_post->post_content = 'Post content';

		$translated_post_id = 22;

		$target_lang = 'de';

		$block_name                    = 'some-block-name';
		$core_block_name               = 'core/' . $block_name; // Gutenberg prefixes with 'core/'
		$attributes                    = array( 'att_1' => 'value_1', );
		$original_block_inner_HTML_1   = 'some inner block content 1';
		$translated_block_inner_HTML_1 = 'some inner block content 1 ( TRANSLATED )';
		$original_block_inner_HTML_2   = 'some inner block content 2';
		$translated_block_inner_HTML_2 = 'some inner block content 2 ( TRANSLATED )';


		$strings = array(
			md5( $core_block_name . $original_block_inner_HTML_1 ) => array(
				$target_lang => array(
					'value'  => $translated_block_inner_HTML_1,
					'status' => (string) ICL_TM_COMPLETE,
				)
			),
			md5( $core_block_name . $original_block_inner_HTML_2 ) => array(
				$target_lang => array(
					'value'  => $translated_block_inner_HTML_2,
					'status' => (string) ICL_TM_COMPLETE,
				)
			),
		);

		$inner_block_1            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$inner_block_1->blockName = $core_block_name;
		$inner_block_1->attrs     = array();
		$inner_block_1->innerHTML = $original_block_inner_HTML_1;

		$inner_block_2            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$inner_block_2->blockName = $core_block_name;
		$inner_block_2->attrs     = array();
		$inner_block_2->innerHTML = $original_block_inner_HTML_2;

		$parent_block               = \Mockery::mock( 'WP_Block_Parser_Block' );
		$parent_block->blockName    = $core_block_name;
		$parent_block->attrs        = $attributes;
		$parent_block->innerHTML    = 'Some string we do not rely on';
		$parent_block->innerBlocks  = array( $inner_block_1, $inner_block_2 );
		$parent_block->innerContent = array( 'before', null, 'inner', null, 'after' );

		$blocks = array( $parent_block );


		\WP_Mock::userFunction( 'gutenberg_parse_blocks',
		                        array(
			                        'args'   => array( $original_post->post_content ),
			                        'return' => $blocks,
		                        )
		);

		$rendered_inner_block_1   = '<!-- wp:' . $block_name . ' -->' . $translated_block_inner_HTML_1 . '<!-- /wp:' . $block_name . ' -->';
		$rendered_inner_block_2   = '<!-- wp:' . $block_name . ' -->' . $translated_block_inner_HTML_2 . '<!-- /wp:' . $block_name . ' -->';
		$translated_inner_content = 'before' . $rendered_inner_block_1 . 'inner' . $rendered_inner_block_2 . 'after';

		$rendered_translated_content = '<!-- wp:' . $block_name . ' ' . json_encode( $attributes ) . ' -->' . $translated_inner_content . '<!-- /wp:' . $block_name . ' -->';

		\WP_Mock::userFunction( 'wpml_update_escaped_post', [
			'times' => 1,
			'args'  => [
				[ 'ID' => $translated_post_id, 'post_content' => $rendered_translated_content ],
				$target_lang,
			],
		] );

		$subject = $this->get_subject();

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
	 * @group wpmlcore-6627
	 */
	public function it_updates_root_content_and_inner_blocks() {
		$original_post               = \Mockery::mock( 'WP_Post' );
		$original_post->post_content = 'Post content / Will be replaced with parsed blocks';

		$translated_post_id = 123;
		$target_lang        = 'de';

		$parent_block_name      = 'some-block-name';
		$core_parent_block_name = 'core/' . $parent_block_name; // Gutenberg prefixes with 'core/'
		$p_block_name           = 'paragraph';
		$core_p_block_name      = 'core/' . $p_block_name; // Gutenberg prefixes with 'core/'

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn(
		              	[
			                $core_parent_block_name => [
			                	'xpath' => [
			                	    '//summary',
				                    '//details/@data-attr',
			                    ]
			                ],
			                $core_p_block_name    => [ 'xpath' => [ '//p' ] ],
		                ]
		              );

		$title             = 'My title';
		$title_translation = 'DE My title';

		$attr             = 'My Attribute';
		$attr_translation = 'DE My Attribute';

		$paragraph_text             = 'My paragraph.';
		$paragraph_text_translation = 'DE My paragraph.';

		$strings = array(
			md5( $core_parent_block_name . $title ) => [
				$target_lang => [
					'value'  => $title_translation,
					'status' => (string) ICL_TM_COMPLETE,
				]
			],
			md5( $core_parent_block_name . $attr )  => [
				$target_lang => [
					'value'  => $attr_translation,
					'status' => (string) ICL_TM_COMPLETE,
				]
			],
			md5( $core_p_block_name . $paragraph_text )  => [
				$target_lang => [
					'value'  => $paragraph_text_translation,
					'status' => (string) ICL_TM_COMPLETE,
				],
			],
		);

		$block_paragraph               = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block_paragraph->blockName    = $core_p_block_name;
		$block_paragraph->attrs        = [];
		$block_paragraph->innerHTML    = '<p>' . $paragraph_text . '</p>';
		$block_paragraph->innerContent = [ $block_paragraph->innerHTML ];

		$parent_contents = $this->get_parent_block_contents( $title, $attr );

		$parent_block               = \Mockery::mock( 'WP_Block_Parser_Block' );
		$parent_block->blockName    = $core_parent_block_name;
		$parent_block->attrs        = [];
		$parent_block->innerHTML    = $parent_contents[0]
		                              . $this->get_content_with_block_meta_data( $p_block_name, $block_paragraph->innerHTML )
		                              . $parent_contents[1];

		$parent_block->innerBlocks  = [ $block_paragraph ];
		$parent_block->innerContent = [ $parent_contents[0], null, $parent_contents[1] ];


		\WP_Mock::userFunction( 'gutenberg_parse_blocks',
		                        [
			                        'args'   => [ $original_post->post_content ],
			                        'return' => [ $parent_block ],
		                        ]
		);

		$rendered_paragraph          = $this->get_content_with_block_meta_data( $p_block_name, '<p>' . $paragraph_text_translation . '</p>' );
		$translated_parent_contents  = $this->get_parent_block_contents( $title_translation, $attr_translation );
		$translated_inner_content    = $translated_parent_contents[0] . $rendered_paragraph . $translated_parent_contents[1];
		$rendered_translated_content = $this->get_content_with_block_meta_data( $parent_block_name, $translated_inner_content );

		\WP_Mock::userFunction( 'wpml_update_escaped_post', [
			'times' => 1,
			'args'  => [
				[ 'ID' => $translated_post_id, 'post_content' => $rendered_translated_content ],
				$target_lang,
			],
		] );

		$subject = $this->get_subject( $config_option );

		$subject->string_translated(
			WPML_Gutenberg_Integration::PACKAGE_ID,
			$translated_post_id,
			$original_post,
			$strings,
			$target_lang
		);

	}

	/**
	 * @param string $title
	 * @param string $attr
	 *
	 * @return array
	 */
	private function get_parent_block_contents( $title, $attr ) {
		return [
			'<div class="some-block">
			<details data-attr="' . $attr . '">
			<summary class="some-block-title">' . $title . '</summary>
			<div class="some-block-text">',
			'</div>
			</details>
			</div>',
		];
	}

	/**
	 * @param string $block_name
	 * @param string $content
	 *
	 * @return string
	 */
	private function get_content_with_block_meta_data( $block_name, $content ) {
		return '<!-- wp:' . $block_name . ' -->' . $content . '<!-- /wp:' . $block_name . ' -->';
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

	/**
	 * @test
	 * @group wpmlcore-6102
	 */
	public function it_should_remove_gutenberg_string_package_from_tm_filters() {
		$types = array(
			'post'      => array(),
			'gutenberg' => array(),
			'page'      => array(),
		);

		$expected_types = $types;
		unset( $expected_types['gutenberg'] );

		$subject = $this->get_subject();

		$this->assertEquals(
			$expected_types,
			$subject->remove_package_strings_type_filter( $types )
		);
	}

	public function get_subject( $config_option = null, $strings_registration = null ) {
		if ( ! $config_option ) {
			$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
			$config_option->shouldReceive( 'get' )->andReturn( array() );
		}

		$strings_registration = $strings_registration ? $strings_registration : $this->get_strings_registration();

		return new WPML_Gutenberg_Integration(
			$this->getStringsInBlock( $config_option ),
			$config_option,
			$strings_registration
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

	private function get_sitepress() {
		return $this->getMockBuilder( 'SitePress' )
			->setMethods( array( 'switch_lang' ) )
			->disableOriginalConstructor()->getMock();
	}

	private function get_strings_registration() {
		return $this->getMockBuilder( 'WPML_Gutenberg_Strings_Registration' )
			->setMethods( array( 'register_strings' ) )
			->disableOriginalConstructor()->getMock();
	}

	private function getStringsInBlock( $config_option ) {
		$string_parsers = [
			new WPML\PB\Gutenberg\StringsInBlock\HTML( $config_option ),
			new WPML\PB\Gutenberg\StringsInBlock\Attributes( $config_option ),
		];

		return new WPML\PB\Gutenberg\StringsInBlock\Collection( $string_parsers );
	}
}
