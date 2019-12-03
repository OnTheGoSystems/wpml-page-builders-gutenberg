<?php

class WP_Block_Parser_Block {
	public $blockName;
	public $attrs;
	public $innerBlocks;
	public $innerHTML;
	public $innerContent;

	function __construct( $name, $attrs, $innerBlocks, $innerHTML, $innerContent ) {
		$this->blockName    = $name;
		$this->attrs        = $attrs;
		$this->innerBlocks  = $innerBlocks;
		$this->innerHTML    = $innerHTML;
		$this->innerContent = $innerContent;
	}
}