<?php

namespace WPML\PB\Gutenberg;

class Integration_Composite implements Integration {

	/**
	 * @var Integration[] $integrations
	 */
	private $integrations;

	/**
	 * @param Integration[] $integrations
	 */
	public function __construct( array $integrations ) {
		$this->integrations = $integrations;
	}

	public function add_hooks() {
		foreach ( $this->integrations as $integration ) {

			if ( ! $integration instanceof Integration ) {
				throw new \Exception( 'The class ' . get_class( $integration ) . ' must implement the Integration interface' );
			}

			$integration->add_hooks();
		}
	}

}
