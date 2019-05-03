<?php

namespace WPML\PB\Gutenberg;

/**
 * @group reusable-blocks
 */
class Test_Reusable_Blocks_Batch_Handler extends \OTGS_TestCase {

	/**
	 * @test
	 * @group wpmlcore-6580
	 */
	public function it_should_add_blocks() {
		$source_lang                 = 'en';
		$target_langs                = [ 'fr' => 1, 'de' => 1 ];
		$post_with_reusable_blocks_1 = 123;
		$post_with_reusable_blocks_2 = 456;
		$post_with_no_blocks         = 789;
		$reusable_block_1            = 1001;
		$reusable_block_2            = 1002;
		$reusable_block_1_fr         = 1101; // needs update
		$reusable_block_1_de         = 1102; // does not need update
		$reusable_block_2_fr         = null; // not translated

		$elements = [
			$this->get_batch_element( 999, 'not-a-post', $source_lang, $target_langs ),
			$this->get_batch_element( $post_with_reusable_blocks_1, 'post', $source_lang, $target_langs ),
			$this->get_batch_element( $post_with_no_blocks, 'post', $source_lang, $target_langs ),
			$this->get_batch_element( $post_with_reusable_blocks_2, 'post', $source_lang, [ 'fr' => 1 ] ),
		];

		$new_element_1 = $this->get_batch_element( $reusable_block_1_fr, 'post', $source_lang, [ 'fr' => 1 ] );
		$new_element_2 = $this->get_batch_element( $reusable_block_2_fr, 'post', $source_lang, [ 'fr' => 1 ] );

		$batch = $this->get_batch( $elements );
		$batch->expects( $this->exactly( 2 ) )
		      ->method( 'add_element' )
		      ->withConsecutive( $new_element_1, $new_element_2 );

		$post_to_reusable_blocks = [
			[ $post_with_reusable_blocks_1, [ $reusable_block_1 ] ],
			[ $post_with_reusable_blocks_2, [ $reusable_block_2, $reusable_block_1 ] ],
		];

		$blocks_mock = $this->get_reusable_blocks();
		$blocks_mock->method( 'get_ids_from_post' )->willReturnMap( $post_to_reusable_blocks );

		$convert_block_id = [
			[ $reusable_block_1, false, 'fr', $reusable_block_1_fr ],
			[ $reusable_block_1, false, 'de', $reusable_block_1_de ],
			[ $reusable_block_2, false, 'fr', $reusable_block_2_fr ],
		];

		$translation_mock = $this->get_reusable_blocks_translation();
		$translation_mock->method( 'convert_block_id' )->willReturnMap( $convert_block_id );

		$needs_update = [
			[ $reusable_block_1_fr, 1 ],
			[ $reusable_block_1_de, false ],
		];

		$status_helper = $this->get_post_status_helper();
		$status_helper->method( 'needs_update' )->willReturnMap( $needs_update );

		\WP_Mock::userFunction( 'wpml_get_post_status_helper', [
			'return' => $status_helper,
		] );

		$subject = $this->get_subject( $blocks_mock, $translation_mock );

		$new_batch = $subject->add_blocks( $batch );
	}

	public function get_subject( $blocks, $translation ) {
		return new Reusable_Blocks_Batch_Handler( $blocks, $translation );
	}

	private function get_reusable_blocks() {
		return $this->getMockBuilder( Reusable_Blocks::class )
			->setMethods( [ 'get_ids_from_post' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function get_reusable_blocks_translation() {
		return $this->getMockBuilder( Reusable_Blocks_Translation::class )
			->setMethods( [ 'convert_block_id' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function get_batch( array $elements ) {
		$batch = $this->getMockBuilder( '\WPML_TM_Translation_Batch' )
			->setMethods( [ 'get_elements', 'add_element' ] )
			->disableOriginalConstructor()->getMock();
		$batch->method( 'get_elements' )->willReturn( $elements );

		return $batch;
	}

	private function get_batch_element( $id, $type, $source_lang, array $target_langs ) {
		$element = $this->getMockBuilder( '\WPML_TM_Translation_Batch_Element' )
			->setMethods(
				[
					'get_element_id',
					'get_element_type',
					'get_source_lang',
					'get_target_langs',
				]
			)
			->disableOriginalConstructor()->getMock();
		$element->method( 'get_element_id' )->willReturn( $id );
		$element->method( 'get_element_type' )->willReturn( $type );
		$element->method( 'get_source_lang' )->willReturn( $source_lang );
		$element->method( 'get_target_langs' )->willReturn( $target_langs );

		return $element;
	}

	private function get_post_status_helper() {
		return $this->getMockBuilder( '\WPML_Post_Status' )
			->setMethods( [ 'needs_update' ] )
			->disableOriginalConstructor()->getMock();
	}
}
