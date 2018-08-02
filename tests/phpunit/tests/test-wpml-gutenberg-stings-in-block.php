<?php

/**
 * Class Test_WPML_Gutenberg_Strings_In_Block
 *
 * @group page-builders
 * @group gutenberg
 */
class Test_WPML_Gutenberg_Strings_In_Block extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_finds_paragraph() {

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/paragraph' => array( '//p' ) ) );

		$strings_in_block = new WPML_Gutenberg_Strings_In_Block( $config_option );

		$paragraph = 'some paragraph';

		$block = array(
			'blockName' => 'core/paragraph',
			'innerHTML' => '<p>' . $paragraph . '</p>',
		);

		$strings = $strings_in_block->find( $block );

		$this->assertCount( 1, $strings );

		$string = $strings[0];

		$this->assertEquals( $block['blockName'], $string->name );
		$this->assertEquals( $paragraph, $string->value );
		$this->assertEquals( 'LINE', $string->type );

		$paragraph = '<strong>some</strong> paragraph';

		$block = array(
			'blockName' => 'core/paragraph',
			'innerHTML' => '<p>' . $paragraph . '</p>',
		);

		$strings = $strings_in_block->find( $block );

		$this->assertCount( 1, $strings );

		$string = $strings[0];

		$this->assertEquals( $block['blockName'], $string->name );
		$this->assertEquals( $paragraph, $string->value );
		$this->assertEquals( 'VISUAL', $string->type );


	}

	/**
	 * @test
	 */
	public function it_finds_image() {
		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/image' => array( '//figure/figcaption', '//figure/img/@alt' ) ) );

		$strings_in_block = new WPML_Gutenberg_Strings_In_Block( $config_option );

		$alt_text = 'alt text';
		$caption  = 'caption';

		$block = array(
			'blockName' => 'core/image',
			'innerHTML' => '<figure class="wp-block-image"><img src="xxx" alt="' . $alt_text . '" class="xxx" /><figcaption>' . $caption . '</figcaption></figure>',
		);

		$strings = $strings_in_block->find( $block );

		$this->assertCount( 2, $strings );

		$string = $strings[0];

		$this->assertEquals( $block['blockName'], $string->name );
		$this->assertEquals( $caption, $string->value );
		$this->assertEquals( 'LINE', $string->type );

		$string = $strings[1];

		$this->assertEquals( $block['blockName'], $string->name );
		$this->assertEquals( $alt_text, $string->value );
		$this->assertEquals( 'LINE', $string->type );

	}

	/**
	 * @test
	 */
	public function it_updates_paragraph() {

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/paragraph' => array( '//p' ) ) );

		$strings_in_block = new WPML_Gutenberg_Strings_In_Block( $config_option );

		$block_name = 'core/paragraph';

		$target_lang                 = 'de';
		$original_block_inner_HTML   = 'some block content';
		$translated_block_inner_HTML = 'some block content ( TRANSLATED )';


		$strings = array(
			md5( $block_name . $original_block_inner_HTML ) => array(
				$target_lang => array(
					'value'  => $translated_block_inner_HTML,
					'status' => (string) ICL_TM_COMPLETE,
				)
			)
		);

		$block = array(
			'blockName' => $block_name,
			'innerHTML' => '<p>' . $original_block_inner_HTML . '</p>',
		);

		$updated_block = $strings_in_block->update( $block, $strings, $target_lang );

		$this->assertEquals( '<p>' . $translated_block_inner_HTML . '</p>', $updated_block['innerHTML'] );

	}

	/**
	 * @test
	 */
	public function it_updates_image() {

		$config_option = \Mockery::mock( 'WPML_Gutenberg_Config_Option' );
		$config_option->shouldReceive( 'get' )
		              ->andReturn( array( 'core/image' => array( '//figure/figcaption', '//figure/img/@alt' ) ) );

		$strings_in_block = new WPML_Gutenberg_Strings_In_Block( $config_option );

		$block_name = 'core/image';

		$alt_text            = 'alt text';
		$alt_text_translated = 'alt text ( TRANSLATED )';
		$caption             = 'caption';
		$caption_translated  = 'caption ( TRANSLATED )';

		$target_lang                 = 'de';


		$strings = array(
			md5( $block_name . $alt_text ) => array(
				$target_lang => array(
					'value'  => $alt_text_translated,
					'status' => (string) ICL_TM_COMPLETE,
				)
			),
			md5( $block_name . $caption ) => array(
			$target_lang => array(
				'value'  => $caption_translated,
				'status' => (string) ICL_TM_COMPLETE,
			)
		)
		);

		$block = array(
			'blockName' => $block_name,
			'innerHTML' => '<figure class="wp-block-image"><img src="xxx" alt="' . $alt_text . '" class="xxx" /><figcaption>' . $caption . '</figcaption></figure>',
		);

		$updated_block = $strings_in_block->update( $block, $strings, $target_lang );

		$this->assertEquals(
			'<figure class="wp-block-image"><img src="xxx" alt="' . $alt_text_translated . '" class="xxx"><figcaption>' . $caption_translated . '</figcaption></figure>',
			$updated_block['innerHTML']
		);

	}

}
