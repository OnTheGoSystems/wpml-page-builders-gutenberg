<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

/**
 * @group reusable-blocks
 */
class TestManageBatch extends \OTGS_TestCase {

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
		$reusable_block_2_fr         = $reusable_block_2; // not translated

		$elements = [
			$this->getBatchElement( 999, 'not-a-post', $source_lang, $target_langs ),
			$this->getBatchElement( $post_with_reusable_blocks_1, 'post', $source_lang, $target_langs ),
			$this->getBatchElement( $post_with_no_blocks, 'post', $source_lang, $target_langs ),
			$this->getBatchElement( $post_with_reusable_blocks_2, 'post', $source_lang, [ 'fr' => 1 ] ),
		];

		$new_element_1 = $this->getBatchElement( $reusable_block_1_fr, 'post', $source_lang, [ 'fr' => 1 ] );
		$new_element_2 = $this->getBatchElement( $reusable_block_2_fr, 'post', $source_lang, [ 'fr' => 1 ] );

		$batch = $this->getBatch( $elements );
		$batch->expects( $this->exactly( 2 ) )
		      ->method( 'add_element' )
		      ->withConsecutive( $new_element_1, $new_element_2 );

		$post_to_reusable_blocks = [
			[ $post_with_reusable_blocks_1, [ $reusable_block_1 ] ],
			[ $post_with_reusable_blocks_2, [ $reusable_block_2, $reusable_block_1 ] ],
		];

		$blocks_mock = $this->getBlocks();
		$blocks_mock->method( 'getChildrenIdsFromPost' )->willReturnMap( $post_to_reusable_blocks );

		$convert_block_id = [
			[ $reusable_block_1, 'fr', $reusable_block_1_fr ],
			[ $reusable_block_1, 'de', $reusable_block_1_de ],
			[ $reusable_block_2, 'fr', $reusable_block_2_fr ],
		];

		$translation_mock = $this->getTranslation();
		$translation_mock->method( 'convertBlockId' )->willReturnMap( $convert_block_id );

		$needs_update = [
			[ $reusable_block_1_fr, 1 ],
			[ $reusable_block_1_de, false ],
		];

		$status_helper = $this->getPostStatusHelper();
		$status_helper->method( 'needs_update' )->willReturnMap( $needs_update );

		\WP_Mock::userFunction( 'wpml_get_post_status_helper', [
			'return' => $status_helper,
		] );

		$subject = $this->getSubject( $blocks_mock, $translation_mock );

		$new_batch = $subject->addBlocks( $batch );

		$this->assertSame( $batch, $new_batch );
	}

	public function getSubject( $blocks, $translation ) {
		return new ManageBatch( $blocks, $translation );
	}

	private function getBlocks() {
		return $this->getMockBuilder( Blocks::class )
			->setMethods( [ 'getChildrenIdsFromPost' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function getTranslation() {
		return $this->getMockBuilder( Translation::class )
			->setMethods( [ 'convertBlockId' ] )
			->disableOriginalConstructor()->getMock();
	}

	private function getBatch( array $elements ) {
		$batch = $this->getMockBuilder( '\WPML_TM_Translation_Batch' )
			->setMethods( [ 'get_elements', 'add_element' ] )
			->disableOriginalConstructor()->getMock();
		$batch->method( 'get_elements' )->willReturn( $elements );

		return $batch;
	}

	private function getBatchElement( $id, $type, $source_lang, array $target_langs ) {
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

	private function getPostStatusHelper() {
		return $this->getMockBuilder( '\WPML_Post_Status' )
			->setMethods( [ 'needs_update' ] )
			->disableOriginalConstructor()->getMock();
	}
}
