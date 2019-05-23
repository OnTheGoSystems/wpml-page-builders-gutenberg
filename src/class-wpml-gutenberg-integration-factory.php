<?php

class WPML_Gutenberg_Integration_Factory {

	/** @return \WPML\PB\Gutenberg\Integration */
	public function create() {
		/**
		 * @var SitePress $sitepress
		 * @var wpdb      $wpdb
		 */
		global $sitepress, $wpdb;

		$integrations = new WPML\PB\Gutenberg\Integration_Composite();

		$config_option        = new WPML_Gutenberg_Config_Option();
		$strings_in_block     = new WPML_Gutenberg_Strings_In_Block( $config_option );
		$string_factory       = new WPML_ST_String_Factory( $wpdb );
		$strings_registration = new WPML_Gutenberg_Strings_Registration(
			$strings_in_block,
			$string_factory,
			new WPML_PB_Reuse_Translations( $string_factory ),
			new WPML_PB_String_Translation( $wpdb )
		);

		$integrations->add(
			new WPML_Gutenberg_Integration(
				$strings_in_block,
				$config_option,
				$sitepress,
				$strings_registration
			)
		);

		if ( $this->should_translate_reusable_blocks() ) {
			$integrations->add(
				WPML\Container\make( '\WPML\PB\Gutenberg\ReusableBlocks\Integration' )
			);

			if ( is_admin() ) {
				$integrations->add(
					WPML\Container\make( '\WPML\PB\Gutenberg\ReusableBlocks\AdminIntegration' )
				);
			}
		}

		return $integrations;
	}

	/** @return bool */
	private function should_translate_reusable_blocks() {
		/** @var SitePress $sitepress */
		global $sitepress;

		$is_translatable = $sitepress->is_translated_post_type(
			WPML\PB\Gutenberg\ReusableBlocks\Translation::POST_TYPE
		);

		return class_exists( '\WPML_Translation_Basket' ) && $is_translatable;
	}
}
