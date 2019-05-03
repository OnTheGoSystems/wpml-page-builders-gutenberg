<?php

class WPML_Gutenberg_Integration_Factory {

	/** @return \WPML\PB\Gutenberg\Integration */
	public function create() {
		/**
		 * @var SitePress $sitepress
		 * @var wpdb      $wpdb
		 */
		global $sitepress, $wpdb;

		$config_option = new WPML_Gutenberg_Config_Option();

		$string_parsers = [
			new WPML\PB\Gutenberg\StringsInBlock\HTML( $config_option ),
			new WPML\PB\Gutenberg\StringsInBlock\Attributes( $config_option ),
		];

		$strings_in_block = new WPML\PB\Gutenberg\StringsInBlock\Collection( $string_parsers );

		$integrations = new WPML\PB\Gutenberg\Integration_Composite();

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

		$reusable_blocks_translation = new WPML\PB\Gutenberg\Reusable_Blocks_Translation( $sitepress );

		$integrations->add(
			new WPML\PB\Gutenberg\Reusable_Blocks_Integration( $reusable_blocks_translation )
		);

		if ( is_admin() ) {
			$integrations->add(
				new WPML\PB\Gutenberg\Reusable_Blocks_Admin_Integration(
					new WPML\PB\Gutenberg\Reusable_Blocks_Batch_Handler(
						new WPML\PB\Gutenberg\Reusable_Blocks(),
						$reusable_blocks_translation
					)
				)
			);
		}

		return $integrations;
	}
}
