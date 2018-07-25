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
		$subject = new WPML_Gutenberg_Integration();

		WP_Mock::expectFilterAdded( 'wpml_page_builder_support_required', array(
			$subject,
			'page_builder_support_required'
		), 10, 1 );
		WP_Mock::expectActionAdded( 'wpml_page_builder_register_strings', array(
			$subject,
			'register_strings'
		), 10, 2 );

		$subject->add_hooks();
	}

	public function it_requires_support() {

		$subject = new WPML_Gutenberg_Integration();

		$plugins = $subject->page_builder_support_required( array() );

		$this->assertCount( 1, $plugins );
		$this->assertEquals( 'Gutenberg', $plugins[0] );

	}

	/**
	 * @test
	 */
	public function it_registers_strings() {

		$subject = new WPML_Gutenberg_Integration();

		$post               = \Mockery::mock( 'WP_Post' );
		$post->post_content = 'post content';

		$package = array(
			'kind' => WPML_Gutenberg_Integration::PACKAGE_ID,
		);

		$blocks   = array();
		$blocks['normal block'] = array(
			'blockName' => 'some name',
			'innerHTML' => 'some block content',
		);
		$blocks['block without name'] = array(
			'innerHTML' => 'some content',
		);
		$blocks['block with empty content'] = array(
			'blockName' => 'some name',
			'innerHTML' => '',
		);
		$blocks['block with only white space'] = array(
			'blockName' => 'some name',
			'innerHTML' => "\n\r\t",
		);

		\WP_Mock::userFunction( 'gutenberg_parse_blocks',
			array(
				'times'  => 1,
				'args'   => array( $post->post_content ),
				'return' => $blocks,
			)
		);

		foreach ( $blocks as $type => $block ) {
			$block_name = isset( $block['blockName'] ) ? $block['blockName'] : '';

			$this->expectAction( 'wpml_register_string',
				array(
					$block['innerHTML'],
					md5( $block_name . $block['innerHTML'] ),
					$package,
					$block_name,
					'VISUAL'
				),
				'normal block' === $type ? 1 : 0
			);
		}

		\WP_Mock::expectAction( 'wpml_start_string_package_registration', $package );
		\WP_Mock::expectAction( 'wpml_delete_unused_package_strings', $package );

		$subject->register_strings( $post, $package );

	}
}