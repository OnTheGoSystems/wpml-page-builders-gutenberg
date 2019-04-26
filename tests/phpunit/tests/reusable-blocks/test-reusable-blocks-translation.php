<?php

namespace WPML\PB\Gutenberg;

/**
 * @group reusable-blocks
 */
class Test_Reusable_Blocks_Translation extends \OTGS_TestCase {

	/**
	 * @test
	 * @group wpmlcore-6564
	 */
	public function it_should_not_create_post_if_exists() {
		$original_block_id = 123;
		$lang              = 'fr';

		$sitepress = $this->get_sitepress();
		$sitepress->expects( $this->never() )->method( 'set_element_language_details' );

		$block_element = $this->get_post_element();
		$block_element->method( 'get_translation' )
			->with( $lang )->willReturn( $this->get_post_element() );

		$element_factory = $this->get_element_factory();
		$element_factory->method( 'create_post' )->with( $original_block_id )->willReturn( $block_element );

		$subject = $this->get_subject( $sitepress, $element_factory );

		$subject->create_post( $original_block_id, $lang );
	}

	/**
	 * @test
	 * @group wpmlcore-6564
	 */
	public function it_should_create_post() {
		$original_block_id   = 123;
		$lang                = 'fr';
		$translated_block_id = 456;
		$trid                = 789;

		$original_block = [
			'ID'           => $original_block_id,
			'post_content' => 'some content'
		];

		$translated_block = $original_block;
		unset( $translated_block['ID'] );

		\WP_Mock::userFunction( 'get_post', [
			'args'   => [ $original_block_id, ARRAY_A ],
			'return' => $original_block,
		]);

		$create_post_helper = $this->get_create_post_helper();
		$create_post_helper->method( 'insert_post' )->with( $translated_block, $lang )->willReturn( $translated_block_id );

		\WP_Mock::userFunction( 'wpml_get_create_post_helper', [
			'return' => $create_post_helper,
		]);

		$sitepress = $this->get_sitepress();
		$sitepress->expects( $this->once() )->method( 'set_element_language_details' )
			->with( $translated_block_id, 'post_wp_block', $trid, $lang );

		$block_element = $this->get_post_element();
		$block_element->method( 'get_translation' )
			->with( $lang )->willReturn( null );
		$block_element->method( 'get_trid' )->willReturn( $trid );

		$element_factory = $this->get_element_factory();
		$element_factory->method( 'create_post' )->with( $original_block_id )->willReturn( $block_element );

		$subject = $this->get_subject( $sitepress, $element_factory );

		$subject->create_post( $original_block_id, $lang );
	}

	private function get_subject( $sitepress = null, $element_factory = null ) {
		$sitepress = $sitepress ? $sitepress : $this->get_sitepress();
		$element_factory = $element_factory ? $element_factory : $this->get_element_factory();
		return new Reusable_Blocks_Translation( $sitepress, $element_factory );
	}

	private function get_sitepress() {
		return $this->getMockBuilder( \SitePress::class )
			->setMethods( [ 'set_element_language_details' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function get_element_factory() {
		return $this->getMockBuilder( \WPML_Translation_Element_Factory::class )
			->setMethods( [ 'create_post' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function get_post_element() {
		return $this->getMockBuilder( \WPML_Post_Element::class )
			->setMethods( [ 'get_translation', 'get_trid' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function get_create_post_helper() {
		return $this->getMockBuilder( \WPML_Create_Post_Helper::class )
			->setMethods( [ 'insert_post' ] )
			->disableOriginalConstructor()->getMock();
	}
}
