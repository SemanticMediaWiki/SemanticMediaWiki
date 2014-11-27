<?php

namespace SMW\Tests\Utils\Fixtures\Results;

use RuntimeException;

/**
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-sparql
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class FakeRawResultProvider {

	/**
	 * @see https://www.w3.org/TR/rdf-sparql-query/#ask
	 */
	public function getEmptySparqlResultXml() {
		return $this->getFixtureContentsFor( 'empty-sparql-result.xml' );
	}

	/**
	 * @see @see http://www.w3.org/2009/sparql/xml-results/output2.srx
	 */
	public function getBooleanSparqlResultXml() {
		return $this->getFixtureContentsFor( 'boolean-sparql-result.xml' );
	}

	public function getStringTypeLiteralSparqlResultXml() {
		return $this->getFixtureContentsFor( 'string-type-literal-sparql-result.xml' );
	}

	public function getUriResourceSparqlResultXml() {
		return $this->getFixtureContentsFor( 'uri-resource-sparql-result.xml' );
	}

	public function getNonTypeLiteralResultXml() {
		return $this->getFixtureContentsFor( 'nontype-literal-sparql-result.xml' );
	}

	public function getIntegerTypeLiteralSparqlResultXml() {
		return $this->getFixtureContentsFor( 'integer-type-literal-sparql-result.xml' );
	}

	public function getMixedRowsSparqlResultXml() {
		return $this->getFixtureContentsFor( 'mixed-rows-sparql-result.xml' );
	}

	public function getInvalidSparqlResultXml() {
		return $this->getFixtureContentsFor( 'invalid-sparql-result.xml' );
	}

	private function getFixtureContentsFor( $fixture ) {
		if ( $file = $this->isReadableFile( $this->getFixtureLocation() . $fixture ) ) {
			return file_get_contents( $file );
		}
	}

	private function getFixtureLocation() {
		return __DIR__ . '/' . 'XML' . '/';
	}

	private function isReadableFile( $file ) {

		if ( is_readable( $file ) ) {
			return $file;
		}

		throw new RuntimeException( "{$file} is not accessible" );
	}

}
