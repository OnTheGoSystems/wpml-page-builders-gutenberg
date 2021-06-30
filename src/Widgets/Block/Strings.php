<?php

namespace WPML\PB\Gutenberg\Widgets\Block;

use WPML\Element\API\Languages;
use WPML\FP\Obj;
use WPML\ST\TranslationFile\Manager;
use function WPML\Container\make;

class Strings {

	const PACKAGE_KIND      = 'Block';
	const PACKAGE_KIND_SLUG = 'block';
	const PACKAGE_NAME      = 'widget';
	const PACKAGE_TITLE     = 'Widget';

	const DOMAIN = self::PACKAGE_KIND_SLUG . '-' . self::PACKAGE_NAME;

	/**
	 * @param string $locale
	 *
	 * @return array
	 * @throws \WPML\Auryn\InjectionException
	 */
	public static function fromMo( $locale ) {
		$moFilePath = Manager::getSubdir() . '/' . self::DOMAIN . "-$locale.mo";

		if ( ! file_exists( $moFilePath ) ) {
			return [];
		}

		$mo = make( \MO::class );
		$mo->import_from_file( $moFilePath );

		$langCode = Languages::localeToCode( $locale );

		return wpml_collect( $mo->entries )
			->map( Obj::path( [ 'translations', 0 ] ) )
			->filter()
			->map( function( $value ) use ( $langCode ) {
				return [
						$langCode => [
							'value'  => $value,
							'status' => ICL_STRING_TRANSLATION_COMPLETE,
						],
					];
				} )
			->toArray();
	}

	public static function createPackage() {
		return [
			'kind'      => self::PACKAGE_KIND,
			'kind_slug' => self::PACKAGE_KIND_SLUG,
			'name'      => self::PACKAGE_NAME,
			'title'     => self::PACKAGE_TITLE,
			'post_id'   => null,
		];
	}
}
