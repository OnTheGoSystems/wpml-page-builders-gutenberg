<?php

namespace WPML\PB\Gutenberg;

class Reusable_Blocks {

	/**
	 * @param int $post_id
	 *
	 * @return array
	 */
	public function get_ids_from_post( $post_id ) {
		$post = get_post( $post_id );

		if ( $post ) {
			$blocks = \collect( \WPML_Gutenberg_Integration::parse_blocks( $post->post_content ) );

			return $blocks->filter( function( $block ) {
				return 'core/block' === $block['blockName']
				       && isset( $block['attrs']['ref'] )
				       && is_numeric( $block['attrs']['ref'] );
			})->map( function( $block ) {
				return (int) $block['attrs']['ref'];
			})->toArray();
		}

		return [];
	}

	/**
	 * @param \stdClass $job
	 *
	 * @return array
	 */
	public function get_ids_from_job( \stdClass $job ) {
		return \collect( $job->elements )->map(
			function( $field ) {
				preg_match( '/package-string-(\d+)-\d+/', $field->field_type, $matches );

				$package_id = isset( $matches[1] ) ? (int) $matches[1] : null;

				if ( $package_id ) {
					return $package_id;
				}

				return null;
			}
		)->filter()->unique()->map(
			function( $package_id ) use ( $job ) {
				/** @var \WPML_Package $package */
				$package = apply_filters( 'wpml_st_get_string_package', false, $package_id );

				if ( isset( $package->post_id ) && $job->original_doc_id != $package->post_id ) {
					return (int) $package->post_id;
				}

				return null;
			}
		)->filter()->values()->toArray();
	}
}
