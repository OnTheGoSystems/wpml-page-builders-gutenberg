<?php

namespace WPML\PB\Gutenberg\StringsInBlock;

/**
 * @group page-builders
 * @group gutenberg
 * @group strings-in-block
 */
class TestHTML extends \OTGS_TestCase {

	/**
	 * @test
	 * @group wpmlcore-6066
	 */
	public function it_finds_paragraph() {

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/paragraph' => array( 'xpath' => array( '//p' ) ) ) );

		$strings_in_block = new HTML( $config_option );

		$paragraph = 'some paragraph &amp; special chars &amp; emoji ❤ 😀 👍️';

		$block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = 'core/paragraph';
		$block->innerHTML = '<p>' . $paragraph . '</p>';

		$strings = $strings_in_block->find( $block );

		$this->assertCount( 1, $strings );

		$string = $strings[0];

		$this->assertEquals( $block->blockName, $string->name );
		$this->assertEquals( html_entity_decode( $paragraph ), $string->value );
		$this->assertEquals( 'LINE', $string->type );

		$paragraph = '<strong>some</strong> paragraph';

		$block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = 'core/paragraph';
		$block->innerHTML = '<p>' . $paragraph . '</p>';

		$strings = $strings_in_block->find( $block );

		$this->assertCount( 1, $strings );

		$string = $strings[0];

		$this->assertEquals( $block->blockName, $string->name );
		$this->assertEquals( $paragraph, $string->value );
		$this->assertEquals( 'VISUAL', $string->type );


	}

	/**
	 * @test
	 * @group wpmlcore-6661
	 */
	public function it_does_not_find_the_column_content_if_the_block_configuration_has_no_xpath() {

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/column' => array() ) );

		$strings_in_block = new HTML( $config_option );

		$block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = 'core/column';
		$block->innerHTML = 'any data';

		$strings = $strings_in_block->find( $block );

		$this->assertCount( 0, $strings );
	}

	/**
	 * @test
	 * @group wpmlcore-6606
	 */
	public function it_does_not_find_string_if_innerHTML_is_not_set() {

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/column' => array( 'xpath' => array( '//p' ) ) ) );

		$strings_in_block = new HTML( $config_option );

		$block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = 'core/column';

		$strings = $strings_in_block->find( $block );

		$this->assertCount( 0, $strings );
	}

	/**
	 * @test
	 */
	public function it_finds_image() {
		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/image' => array( 'xpath' => array( '//figure/figcaption', '//figure/img/@alt' ) ) ) );

		$strings_in_block = new HTML( $config_option );

		$alt_text = 'alt text';
		$caption  = 'caption';

		$block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = 'core/image';
		$block->innerHTML = '<figure class="wp-block-image"><img src="xxx" alt="' . $alt_text . '" class="xxx" /><figcaption>' . $caption . '</figcaption></figure>';

		$strings = $strings_in_block->find( $block );

		$this->assertCount( 2, $strings );

		$string = $strings[0];

		$this->assertEquals( $block->blockName, $string->name );
		$this->assertEquals( $caption, $string->value );
		$this->assertEquals( 'LINE', $string->type );

		$string = $strings[1];

		$this->assertEquals( $block->blockName, $string->name );
		$this->assertEquals( $alt_text, $string->value );
		$this->assertEquals( 'LINE', $string->type );

	}

	/**
	 * @test
	 * @group wpmlcore-6613
	 */
	public function it_should_find_strings_in_nested_lists() {
		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$block_name    = 'core/list';
		$config_option->shouldReceive( 'get' )
		              ->andReturn( [ $block_name => [ 'xpath' => [ '//ul/li|//ol/li' ] ] ] );

		$strings_in_block = new HTML( $config_option );

		$parent_1       = 'Parent <strong>1</strong>';
		$child_11       = 'Child <strong>11</strong>';
		$grandchild_111 = 'Grandchild<br/>111';
		$grandchild_112 = 'Grandchild 112';
		$child_12       = 'Child 12';
		$parent_2       = 'Parent 2';
		$child_21       = 'Child 21';

		$block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = $block_name;
		$block->innerHTML = $this->get_nested_list(
			[
				$parent_1,
				$child_11,
				$grandchild_111,
				$grandchild_112,
				$child_12,
				$parent_2,
				$child_21,
			]
		);

		$strings = $strings_in_block->find( $block );

		$this->assertCount( 7, $strings );
		$this->check_string( $strings[0], $parent_1, 'VISUAL' );
		$this->check_string( $strings[1], $child_11, 'VISUAL' );
		$this->check_string( $strings[2], $grandchild_111, 'VISUAL' );
		$this->check_string( $strings[3], $grandchild_112, 'LINE' );
		$this->check_string( $strings[4], $child_12, 'LINE' );
		$this->check_string( $strings[5], $parent_2, 'LINE' );
		$this->check_string( $strings[6], $child_21, 'LINE' );
	}

	/**
	 * @param \stdClass $string
	 * @param string    $expected_value
	 * @param string    $expected_type
	 */
	private function check_string( \stdClass $string, $expected_value, $expected_type ) {
		$this->assertEquals( $expected_value, $string->value );
		$this->assertEquals( $expected_type, $string->type );
	}

	/**
	 * @test
	 */
	public function it_updates_paragraph() {

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/paragraph' => array( 'xpath' => array( '//p' ) ) ) );

		$strings_in_block = new HTML( $config_option );

		$block_name = 'core/paragraph';

		$target_lang                 = 'de';
		$original_block_inner_HTML   = 'some block content &amp; special chars<br/>';
		$translated_block_inner_HTML = 'some block content &amp; special chars ( TRANSLATED )<br/>';


		$strings = array(
			md5( $block_name . $original_block_inner_HTML ) => array(
				$target_lang => array(
					'value'  => $translated_block_inner_HTML,
					'status' => (string) ICL_TM_COMPLETE,
				)
			)
		);

		$block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = $block_name;
		$block->innerHTML = '<p>' . $original_block_inner_HTML . '</p>';

		$updated_block = $strings_in_block->update( $block, $strings, $target_lang );

		$this->assertEquals( '<p>' . $translated_block_inner_HTML . '</p>', $updated_block->innerHTML );

	}

	/**
	 * @test
	 * @group wpmlcore-6606
	 */
	public function it_does_not_update_if_missing_innerHTML() {
		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/paragraph' => array( 'xpath' => array( '//p' ) ) ) );

		$strings_in_block = new HTML( $config_option );

		$block_name = 'core/paragraph';

		$block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = $block_name;

		$updated_block = $strings_in_block->update( $block, array(), 'de' );

		$this->assertEquals( $block, $updated_block );
	}

	/**
	 * @test
	 * @group wpmlcore-6066
	 */
	public function it_updates_paragraph_with_html_entities() {

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/paragraph' => array( 'xpath' => array( '//p' ) ) ) );

		$strings_in_block = new HTML( $config_option );

		$block_name = 'core/paragraph';

		$target_lang                 = 'de';
		$original_block_inner_HTML   = 'some block content &amp; special chars &amp; emoji ❤ 😀 👍';
		$decoded_inner_HTML          = html_entity_decode( $original_block_inner_HTML );
		$translated_block_inner_HTML = 'some block content &amp; special chars &amp; emoji ❤ 😀 👍 ( TRANSLATED )';


		$strings = array(
			md5( $block_name . $decoded_inner_HTML ) => array(
				$target_lang => array(
					'value'  => html_entity_decode( $translated_block_inner_HTML ),
					'status' => (string) ICL_TM_COMPLETE,
				)
			)
		);

		$block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = $block_name;
		$block->innerHTML = '<p>' . $original_block_inner_HTML . '</p>';

		$updated_block = $strings_in_block->update( $block, $strings, $target_lang );

		$this->assertEquals( '<p>' . $translated_block_inner_HTML . '</p>', $updated_block->innerHTML );

	}

	/**
	 * @test
	 * @group wpmlcore-6066
	 */
	public function it_updates_a_visual_block_with_html_entities() {

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/visual_block' => array( 'xpath' => array( '//div' ) ) ) );

		$strings_in_block = new HTML( $config_option );

		$block_name = 'core/visual_block';

		$target_lang                 = 'de';
		$original_block_inner_HTML   = '<div>some block content &amp; special chars &amp; emoji ❤ 😀 👍</div>';
		$translated_block_inner_HTML = '<div>some block content &amp; special chars &amp; emoji ❤ 😀 👍 ( TRANSLATED )</div>';


		$strings = array(
			md5( $block_name . $original_block_inner_HTML ) => array(
				$target_lang => array(
					'value'  => $translated_block_inner_HTML,
					'status' => (string) ICL_TM_COMPLETE,
				)
			)
		);

		$block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = $block_name;
		$block->innerHTML = '<div>' . $original_block_inner_HTML . '</div>';

		$updated_block = $strings_in_block->update( $block, $strings, $target_lang );

		$this->assertEquals( '<div>' . $translated_block_inner_HTML . '</div>', $updated_block->innerHTML );

	}

	/**
	 * @test
	 */
	public function it_updates_image() {

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/image' => array( 'xpath' => array( '//figure/figcaption', '//figure/img/@alt' ) ) ) );

		$strings_in_block = new HTML( $config_option );

		$block_name = 'core/image';

		$alt_text            = 'alt text';
		$alt_text_translated = 'alt text ( TRANSLATED )';
		$caption             = 'caption';
		$caption_translated  = 'caption ( TRANSLATED )';

		$target_lang = 'de';


		$strings = array(
			md5( $block_name . $alt_text ) => array(
				$target_lang => array(
					'value'  => $alt_text_translated,
					'status' => (string) ICL_TM_COMPLETE,
				)
			),
			md5( $block_name . $caption )  => array(
				$target_lang => array(
					'value'  => $caption_translated,
					'status' => (string) ICL_TM_COMPLETE,
				)
			)
		);

		$block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = $block_name;
		$block->innerHTML = '<figure class="wp-block-image"><img src="xxx" alt="' . $alt_text . '" class="xxx"/><figcaption>' . $caption . '</figcaption></figure>';

		$updated_block = $strings_in_block->update( $block, $strings, $target_lang );

		$this->assertEquals(
			'<figure class="wp-block-image"><img src="xxx" alt="' . $alt_text_translated . '" class="xxx"/><figcaption>' . $caption_translated . '</figcaption></figure>',
			$updated_block->innerHTML
		);

	}

	/**
	 * @test
	 * @group wpmlcore-6682
	 */
	public function it_should_update_innerContent_containing_a_slash() {
		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/visual_block' => array( 'xpath' => array( '//div' ) ) ) );

		$strings_in_block = new HTML( $config_option );

		$block_name = 'core/visual_block';

		$target_lang                 = 'de';
		$original_block_inner_HTML   = '<div>Value 1/w</div>';
		$translated_block_inner_HTML = '<div>Value 1/w ( TRANSLATED )</div>';


		$strings = array(
			md5( $block_name . $original_block_inner_HTML ) => array(
				$target_lang => array(
					'value'  => $translated_block_inner_HTML,
					'status' => (string) ICL_TM_COMPLETE,
				)
			)
		);

		$block               = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName    = $block_name;
		$block->innerHTML    = '<div>' . $original_block_inner_HTML . '</div>';
		$block->innerContent = [ $block->innerHTML ];

		$updated_block = $strings_in_block->update( $block, $strings, $target_lang );

		$this->assertEquals( '<div>' . $translated_block_inner_HTML . '</div>', $updated_block->innerHTML );
	}

	/**
	 * @test
	 * @group wpmlcore-6643
	 */
	public function it_should_update_and_NOT_escape_invalid_href() {
		$block_name = 'core/some_block';

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( [ $block_name => [ 'xpath' => [ '//div' ] ] ] );

		$strings_in_block = new HTML( $config_option );

		$target_lang                 = 'de';
		$original_block_inner_HTML   = '<a href="[some-shortcode]">Click me</a>';
		$translated_block_inner_HTML = '<a href="[some-shortcode]">Click me translated</a>';


		$strings = array(
			md5( $block_name . $original_block_inner_HTML ) => array(
				$target_lang => array(
					'value'  => $translated_block_inner_HTML,
					'status' => (string) ICL_TM_COMPLETE,
				)
			)
		);

		$block               = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName    = $block_name;
		$block->innerHTML    = '<div>' . $original_block_inner_HTML . '</div>';
		$block->innerContent = [ $block->innerHTML ];

		$updated_block = $strings_in_block->update( $block, $strings, $target_lang );

		$this->assertEquals( '<div>' . $translated_block_inner_HTML . '</div>', $updated_block->innerHTML );

		$not_expected = '<div><a href="%5Bsome-shortcode%5D">Click me</a></div>';

		$this->assertNotSame( $not_expected, $updated_block->innerHTML );
	}

	/**
	 * @test
	 * @group wpmlcore-6613
	 */
	public function it_should_update_nested_lists() {
		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$block_name    = 'core/list';
		$config_option->shouldReceive( 'get' )
		              ->andReturn( [ $block_name => [ 'xpath' => [ '//ul/li|//ol/li' ] ] ] );

		$strings_in_block = new HTML( $config_option );

		$parent_1          = 'Parent <strong>1</strong>';
		$parent_1_tr       = 'TR Parent <strong>1</strong>';
		$child_11          = 'Child <strong>11</strong>';
		$child_11_tr       = 'TR Child <strong>11</strong>';
		$grandchild_111    = 'Grandchild<br/>111';
		$grandchild_111_tr = 'TR Grandchild<br/>111';
		$grandchild_112    = 'Grandchild 112';
		$grandchild_112_tr = 'TR Grandchild 112';
		$child_12          = 'Child 12';
		$child_12_tr       = 'TR Child 12';
		$parent_2          = 'Parent 2';
		$parent_2_tr       = 'TR Parent 2';
		$child_21          = 'Child 21';
		$child_21_tr       = 'TR Child 21';

		$block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = $block_name;
		$block->innerHTML = $this->get_nested_list(
			[
				$parent_1,
				$child_11,
				$grandchild_111,
				$grandchild_112,
				$child_12,
				$parent_2,
				$child_21,
			]
		);

		$expected_inner_html = $this->get_nested_list(
			[
				$parent_1_tr,
				$child_11_tr,
				$grandchild_111_tr,
				$grandchild_112_tr,
				$child_12_tr,
				$parent_2_tr,
				$child_21_tr,
			]
		);

		$target_lang = 'de';

		$strings = $this->get_translated_strings(
			$block_name,
			$target_lang,
			[
				$parent_1       => $parent_1_tr,
				$child_11       => $child_11_tr,
				$grandchild_111 => $grandchild_111_tr,
				$grandchild_112 => $grandchild_112_tr,
				$child_12       => $child_12_tr,
				$parent_2       => $parent_2_tr,
				$child_21       => $child_21_tr,
			]
		);

		$updated_block = $strings_in_block->update( $block, $strings, $target_lang );

		$this->assertEquals( $expected_inner_html, $this->normalize_markup( $updated_block->innerHTML ) );
	}

	/**
	 * @test
	 */
	public function it_does_not_output_self_closing_tags() {

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/paragraph' => array( 'xpath' => array( '//p' ) ) ) );

		$strings_in_block = new HTML( $config_option );

		$block_name = 'core/paragraph';

		$target_lang                 = 'de';
		$original_block_inner_HTML   = '<span class="SHOULD_NOT_SELF_CLOSE"></span><span class="another">Content</span>';
		$translated_block_inner_HTML = '<span class="SHOULD_NOT_SELF_CLOSE"></span><span class="another">Content (TRANSLATED)</span>';;


		$strings = array(
			md5( $block_name . $original_block_inner_HTML ) => array(
				$target_lang => array(
					'value'  => $translated_block_inner_HTML,
					'status' => (string) ICL_TM_COMPLETE,
				)
			)
		);

		$block            = \Mockery::mock( 'WP_Block_Parser_Block' );
		$block->blockName = $block_name;
		$block->innerHTML = '<p>' . $original_block_inner_HTML . '</p>';

		$updated_block = $strings_in_block->update( $block, $strings, $target_lang );

		$this->assertEquals( '<p>' . $translated_block_inner_HTML . '</p>', $updated_block->innerHTML );

	}


	/**
	 * @param array $values
	 *
	 * @return string
	 */
	private function get_nested_list( array $values ) {
		$markup = '<ul>
						<li>' . $values[0] . '
							<ol>
								<li>' . $values[1] . '
									<ul>
										<li>' . $values[2]. '</li>
										<li>' . $values[3]. '</li>
									</ul>
								</li>
								<li>' . $values[4] . '</li>
							</ol>
						</li>
						<li>' . $values[5] . '
							<ul>
								<li>' . $values[6] . '</li>
							</ul>
						</li>
					</ul>';

		return $this->normalize_markup( $markup );
	}

	/**
	 * @param string $markup
	 *
	 * @return string
	 */
	private function normalize_markup( $markup ) {
		return str_replace( [ "\t", "\n", "\r" ], '', $markup );
	}

	/**
	 * @param string $block_name
	 * @param string $target_lang
	 * @param array  $translations_map
	 *
	 * @return array
	 */
	private function get_translated_strings( $block_name, $target_lang, array $translations_map ) {
		$strings = [];

		foreach ( $translations_map as $original => $translation ) {
			$strings[ md5( $block_name . $original ) ] = [
				$target_lang => [
					'value'  => $translation,
					'status' => (string) ICL_TM_COMPLETE,
				],
			];
		}

		return $strings;
	}
}
