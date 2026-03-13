<?php

namespace SMW\Tests;

use SMW\NamespaceUriFinder;

/**
 * @covers \SMW\NamespaceUriFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class NamespaceUriFinderTest extends \PHPUnit\Framework\TestCase {

	public function testGetUriForUnknownNamespaceKeyReturnsBool() {
		$this->assertIsBool(
			NamespaceUriFinder::getUri( 'Foo' )
		);
	}

	/**
	 * @dataProvider namespaceProvider
	 */
	public function testGetUriForNamespaceKeyReturnsString( $key ) {
		$this->assertIsString(
			NamespaceUriFinder::getUri( $key )
		);
	}

	public function namespaceProvider() {
		$ns = [
			'owl', 'rdf', 'rdfs', 'swivt', 'xsd', 'skos', 'foaf', 'dc',
			'OWL'
		];

		$provider = [];

		foreach ( $ns as $key ) {
			$provider[] = [ $key ];
		}

		return $provider;
	}

}
