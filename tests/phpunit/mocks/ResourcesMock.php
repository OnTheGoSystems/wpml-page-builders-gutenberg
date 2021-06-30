<?php

namespace WPML\ST\WP\App;

use tad\FunctionMocker\FunctionMocker;

trait ResourcesMock {

	private $app;

	public function setUpResources() {
		FunctionMocker::replace( 'WPML\ST\WP\App\Resources::enqueueApp', function( $app ) {
			return function ( $localizeData ) use ( $app ) {
				$this->app[ $app ] = $localizeData;
			};
		});
	}

	public function assertEnqueueAppCalled( $app, $localizeData ) {
		$this->assertEquals( $this->app[$app], $localizeData );
	}
}
