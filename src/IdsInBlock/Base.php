<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

class Base {

	/**
	 * @param array $block
	 *
	 * @return array
	 */
	public function convert( array $block ) {
		return $block;
	}

	/**
	 * @param array|int $ids
	 * @param string    $elementType
	 *
	 * @return array|int
	 */
	public static function convertIds( $ids, $elementType ) {
		$getTranslation = function( $id ) use ( $elementType ) {
			return (int) wpml_object_id_filter( $id, $elementType );
		};

		if ( is_array( $ids ) ) {
			return wpml_collect( $ids )->map( $getTranslation )->toArray();
		}

		return $getTranslation( $ids );
	}
}
