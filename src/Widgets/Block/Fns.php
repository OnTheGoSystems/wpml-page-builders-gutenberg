<?php

namespace WPML\PB\Gutenberg\Widgets\Block;

class Fns {

	const STRINGS_DOMAIN = 'block-Widget';

	/**
	 * @return array
	 */
	public function getBlockWidgetStrings() {
		$packageId = $this->getPackageId();
		if ( ! $packageId ) {
			return [];
		}

		$package = new \WPML_Package( $packageId );

		return $package->get_translated_strings( [] );
	}

	private function getPackageId() {
		global $wpdb;

		$sql = "SELECT string_package_id FROM {$wpdb->prefix}icl_strings WHERE `context` = %s";

		return $wpdb->get_var( $wpdb->prepare( $sql, self::STRINGS_DOMAIN ) );
	}
}