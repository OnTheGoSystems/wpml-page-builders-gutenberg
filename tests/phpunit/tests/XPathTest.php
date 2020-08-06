<?php

namespace WPML\PB\Gutenberg;

class XPathTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @test
	 */
	public function it_normalizes_array() {
		$array = [ 'value' => 'data' ];
		$this->assertEquals( $array, XPath::normalize( $array ) );
	}

	/**
	 * @test
	 */
	public function it_handles_type_attribute() {
		$array = [ 'value' => 'data', 'attr' => ['type' => 'link'] ];
		$this->assertEquals( [ 'value' => [ 'value' => 'data', 'type' => 'LINK' ] ], XPath::normalize( $array ) );
	}

	/**
	 * @test
	 */
	public function it_handles_label_attrib() {
		$array = [ 'value' => 'data', 'attr' => ['label' => 'My label'] ];
		$this->assertEquals( [ 'value' => [ 'value' => 'data', 'label' => 'My label' ] ], XPath::normalize( $array ) );
	}

	/**
	 * @test
	 */
	public function it_parses_query_as_string() {
		$string = 'data';
		$this->assertEquals( [ $string, '', '' ], XPath::parse( $string ) );
	}

	/**
	 * @test
	 */
	public function it_parses_query_with_type() {
		$query = [ 'value' => 'data', 'type' => 'LINK', 'label' => 'My label' ];
		$this->assertEquals( [ 'data', 'LINK', 'My label' ], XPath::parse( $query ) );
	}

}
