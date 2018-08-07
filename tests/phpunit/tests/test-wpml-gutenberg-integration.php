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

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )->andReturn( array() );

		$subject = new WPML_Gutenberg_Integration(
			new WPML_Gutenberg_Strings_In_Block( $config_option ),
			$config_option
		);

		$post               = \Mockery::mock( 'WP_Post' );
		$post->post_content = 'post content';

		$package = array(
			'kind' => WPML_Gutenberg_Integration::PACKAGE_ID,
		);

		$blocks                                = array();
		$blocks['normal block']                = array(
			'blockName' => 'some name',
			'innerHTML' => 'some block content',
		);
		$blocks['block without name']          = array(
			'innerHTML' => 'some content',
		);
		$blocks['block with empty content']    = array(
			'blockName' => 'some name',
			'innerHTML' => '',
		);
		$blocks['block with only white space'] = array(
			'blockName' => 'some name',
			'innerHTML' => "\n\r\t",
		);
		$blocks['columns block']               = array(
			'blockName'   => 'columns',
			'innerBlocks' => array(
				array(
					'blockName' => 'inner block',
					'innerHTML' => 'inner block html',
				)

			),
			'innerHTML'   => 'some block content',
		);

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

	private function check_block_is_registered( $block, $type, $package ) {
		$block_name = isset( $block['blockName'] ) ? $block['blockName'] : '';

		$blocks_that_should_be_registered = array( 'normal block', 'columns block', 'inner block' );

		$this->expectAction( 'wpml_register_string',
			array(
				$block['innerHTML'],
				md5( $block_name . $block['innerHTML'] ),
				$package,
				$block_name,
				'VISUAL'
			),
			in_array( $type, $blocks_that_should_be_registered ) ? 1 : 0
		);

		if ( isset( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $type => $block ) {
				$this->check_block_is_registered( $block, $type, $package );
			}
		}

	}

	/**
	 * @test
	 */
	public function it_updates_translated_page() {

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )->andReturn( array() );

		$subject = new WPML_Gutenberg_Integration(
			new WPML_Gutenberg_Strings_In_Block( $config_option ),
			$config_option
		);

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

		$blocks   = array();
		$blocks[] = array(
			'blockName'   => $core_block_name,
			'attrs'       => $attributes,
			'innerHTML'   => $original_block_inner_HTML,
			'innerBlocks' => array()
		);

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
	 */
	public function it_updates_inner_blocks() {
		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )->andReturn( array() );

		$subject = new WPML_Gutenberg_Integration(
			new WPML_Gutenberg_Strings_In_Block( $config_option ),
			$config_option
		);

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

		$blocks   = array();
		$blocks[] = array(
			'blockName'   => 'core/column',
			'innerHTML'   => '<div class="wp-block-column"></div>',
			'attrs'       => array(),
			'innerBlocks' => array(
				array(
					'blockName' => $core_block_name,
					'attrs'     => $attributes,
					'innerHTML' => $original_block_inner_HTML,
				)
			),
		);

		\WP_Mock::userFunction( 'gutenberg_parse_blocks',
			array(
				'args'   => array( $original_post->post_content ),
				'return' => $blocks,
			)
		);

		$rendered_block = "<!-- wp:column --><div class=\"wp-block-column\">";
		$rendered_block .= '<!-- wp:' . $block_name . ' ' . json_encode( $attributes ) . ' -->' . $translated_block_inner_HTML . '<!-- /wp:' . $block_name . ' -->';
		$rendered_block .= "</div><!-- /wp:column -->";

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
}
