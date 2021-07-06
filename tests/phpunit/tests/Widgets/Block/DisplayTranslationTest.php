<?php

namespace phpunit\tests\Widgets\Block;


use WPML\API\MakeMock;
use WPML\Element\API\LanguagesMock;
use WPML\LIB\WP\OnActionMock;
use WPML\LIB\WP\WPDBMock;
use WPML\PB\Gutenberg\Widgets\Block\DisplayTranslation;
use WPML\PB\Gutenberg\Widgets\Block\Strings;
use WPML\PB\Gutenberg\Widgets\Block\WidgetMock;

class DisplayTranslationTest extends \OTGS_TestCase {
	use WPDBMock;
	use MakeMock;
	use OnActionMock;
	use LanguagesMock;
	use WidgetMock;

	public function setUp() {
		parent::setUp();

		$this->setUpWPDBMock();
		$this->setUpMakeMock();
		$this->setUpOnAction();
		$this->setupLanguagesMock();
		$this->setUpWidgetBlock();
	}

	/**
	 * @test
	 */
	public function it_displays_translated_widgets() {
		$content           = 'Some content my string 1 and my string 2 and my string 3';
		$translatedContent = 'Some content fr my string 1 and fr my string 2 and fr my string 3';

		$locale = 'fr_FR';
		\WP_Mock::userFunction( 'get_locale', [ 'return' => $locale ] );

		$this->setCurrentLanguage( 'fr' );
		$this->setStringsInMOFile( Strings::DOMAIN, $locale, [
			'my string 1' => 'fr my string 1',
			'my string 2' => 'fr my string 2',
			'my string 3' => 'fr my string 3',
		] );

		$strings = [
			'my string 1' => [
				'fr' => [ 'value' => 'fr my string 1', 'status' => ICL_STRING_TRANSLATION_COMPLETE ]
			],
			'my string 2' => [
				'fr' => [ 'value' => 'fr my string 2', 'status' => ICL_STRING_TRANSLATION_COMPLETE ]
			],
			'my string 3' => [
				'fr' => [ 'value' => 'fr my string 3', 'status' => ICL_STRING_TRANSLATION_COMPLETE ]
			],
		];

		$this->gutenbergIntegration->shouldReceive( 'replace_strings_in_blocks' )->with( $content, $strings, 'fr' )->andReturn( $translatedContent );

		$subject = new DisplayTranslation();
		$subject->add_hooks();

		$actualResult = $this->runFilter( 'widget_block_content', $content );
		$this->assertEquals( $translatedContent, $actualResult );
	}

	/**
	 * @test
	 */
	public function it_displays_translated_widgets_with_translated_images() {
		$content                     = 'Some content my string 1 and <img href="original_url">.';
		$contentWithTranslatedImages = 'Some content my string 1 and <img href="converted_url">.';
		$translatedContent           = 'Some content fr my string 1 and <img href="converted_url">.';

		$locale = 'fr_FR';
		\WP_Mock::userFunction( 'get_locale', [ 'return' => $locale ] );

		$this->setCurrentLanguage( 'fr' );
		$this->setStringsInMOFile( Strings::DOMAIN, $locale, [
			'my string 1' => 'fr my string 1',
		] );

		$strings = [
			'my string 1' => [
				'fr' => [ 'value' => 'fr my string 1', 'status' => ICL_STRING_TRANSLATION_COMPLETE ]
			],
		];

		$this->mockMake( \WPML_Media_Translated_Images_Update::class )
		     ->shouldReceive( 'replace_images_with_translations' )
		     ->with( $content, 'fr' )
		     ->andReturn( $contentWithTranslatedImages );

		$this->gutenbergIntegration
			->shouldReceive( 'replace_strings_in_blocks' )
			->with( $contentWithTranslatedImages, $strings, 'fr' )
			->andReturn( $translatedContent );

		$subject = new DisplayTranslation();
		$subject->add_hooks();

		$actualResult = $this->runFilter( 'widget_block_content', $content );
		$this->assertEquals( $translatedContent, $actualResult );
	}
}