<?php

namespace WPML\PB\Gutenberg\ReusableBlocks;

/**
 * @group reusable-blocks
 */
class TestManageBasket extends \OTGS_TestCase {

	/**
	 * @test
	 * @dataProvider dp_invalid_basket_data
	 * @group wpmlcore-6590
	 */
	public function it_should_NOT_add_blocks_if_missing_array_keys( $data ) {
		$subject = $this->getSubject();
		$subject->addBlocks( $data );
	}

	public function dp_invalid_basket_data() {
		return [
			[ 'post' => [], 'translate_from' => 'fr' ],
			[ 'tr_action' => [], 'translate_from' => 'fr' ],
			[ 'post' => [], 'tr_action' => [] ],
		];
	}

	/**
	 * @test
	 * @group wpmlcore-6590
	 */
	public function it_should_add_blocks() {
		$source_lang                 = 'en';
		$fr                          = 'fr';
		$de                          = 'de';
		$post_with_reusable_blocks_1 = 123;
		$post_with_reusable_blocks_2 = 456;
		$post_with_no_blocks         = 789;
		$reusable_block_1            = 1001;
		$reusable_block_2            = 1002;
		$reusable_block_3            = 1003;
		$reusable_block_1_fr         = 1101; // needs update
		$reusable_block_2_fr         = $reusable_block_2; // not translated
		$reusable_block_3_fr         = 1103; // not translated

		$data = [
			'icl_tm_action' => 'add_jobs',
			'translate_from' => $source_lang,
			'_icl_nonce_stn_' => '1899935a14',
			'_wp_http_referer' => '/wp-admin/admin.php?page=wpml-translation-management/menu/main.php&sm=dashboard',
			'post' => [
				$post_with_reusable_blocks_1 => [
					'type' => 'post',
					'checked' => $post_with_reusable_blocks_1,
				],
				$post_with_reusable_blocks_2 => [
					'type' => 'post',
					'checked' => $post_with_reusable_blocks_2,
				],
				$post_with_no_blocks => [
					'type' => 'post',
					'checked' => $post_with_no_blocks,
				],
				9999 => [ // Post not selected, missing 'checked'
					'type' => 'post',
				],
				9998 => [ // Not a post
					'type' => 'string',
				],
			],
			'tr_action' => [
				$fr => '1',
				$de => '0',
			],
			'iclnonce' => '85df237169',
		];

		$post_to_reusable_blocks = [
			[ $post_with_reusable_blocks_1, [ $reusable_block_1, $reusable_block_3 ] ],
			[ $post_with_reusable_blocks_2, [ $reusable_block_2, $reusable_block_1 ] ],
		];

		$blocks_mock = $this->getBlocks();
		$blocks_mock->method( 'getChildrenIdsFromPost' )->willReturnMap( $post_to_reusable_blocks );

		$convert_block_id = [
			[ $reusable_block_1, 'fr', $reusable_block_1_fr ],
			[ $reusable_block_2, 'fr', $reusable_block_2_fr ],
			[ $reusable_block_3, 'fr', $reusable_block_3_fr ],
		];

		$translation_mock = $this->getTranslation();
		$translation_mock->method( 'convertBlockId' )->willReturnMap( $convert_block_id );

		$needs_update = [
			[ $reusable_block_1_fr, 1 ],
			[ $reusable_block_3_fr, false ],
		];

		$status_helper = $this->getPostStatusHelper();
		$status_helper->method( 'needs_update' )->willReturnMap( $needs_update );

		\WP_Mock::userFunction( 'wpml_get_post_status_helper', [
			'return' => $status_helper,
		] );

		$basket_portion = [
			'post' => [
				$reusable_block_1 => [
					'from_lang'  => $source_lang,
					'to_langs'   => [ $fr => 1 ],
					'auto_added' => true,
				],
				$reusable_block_2 => [
					'from_lang'  => $source_lang,
					'to_langs'   => [ $fr => 1 ],
					'auto_added' => true,
				],
			],
			'source_language'	=> $data['translate_from'],
			'target_languages'	=> array_keys( $data['tr_action'] ),
		];

		$basket_mock = $this->getTranslationBasket();
		$basket_mock->expects( $this->once() )
		            ->method( 'update_basket' )
					->with( $basket_portion );

		$subject = $this->getSubject( $blocks_mock, $translation_mock, $basket_mock );

		$subject->addBlocks( $data );
	}

	/**
	 * @test
	 * @group wpmlcore-6590
	 * @expectedException \RuntimeException
	 */
	public function it_should_throw_an_exception_if_not_an_element_object() {
		$not_an_element = $this->createMock( 'Not_An_Element' );

		$subject = $this->getSubject();
		$subject->findBlocksInElement( $not_an_element );
	}

	public function getSubject( $blocks = null, $translation = null, $translation_basket = null ) {
		$blocks             = $blocks ? $blocks : $this->getBlocks();
		$translation        = $translation ? $translation : $this->getTranslation();
		$translation_basket = $translation_basket ? $translation_basket : $this->getTranslationBasket();
		return new ManageBasket( $blocks, $translation, $translation_basket );
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

	private function getTranslationBasket() {
		return $this->getMockBuilder( '\WPML_Translation_Basket' )
		            ->setMethods( [ 'update_basket' ] )
		            ->disableOriginalConstructor()->getMock();
	}

	private function getPostStatusHelper() {
		return $this->getMockBuilder( '\WPML_Post_Status' )
		            ->setMethods( [ 'needs_update' ] )
		            ->disableOriginalConstructor()->getMock();
	}
}
