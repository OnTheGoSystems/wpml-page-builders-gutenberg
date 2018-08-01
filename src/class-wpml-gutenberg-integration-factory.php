<?php

class WPML_Gutenberg_Integration_Factory {

	public function create() {

		return new WPML_Gutenberg_Integration( new WPML_Gutenberg_Strings_In_Block() );
	}
}