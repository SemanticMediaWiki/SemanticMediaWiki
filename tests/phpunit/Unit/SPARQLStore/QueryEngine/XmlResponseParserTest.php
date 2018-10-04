<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\XmlResponseParser;
use SMW\Tests\Utils\Fixtures\Results\FakeRawResultProvider;
use SMWExpLiteral as ExpLiteral;
use SMWExpResource as ExpResource;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\XmlResponseParser
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class XmlResponseParserTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\XmlResponseParser',
			new XmlResponseParser()
		);
	}

	/**
	 * @dataProvider rawXmlResultDocumentProvider
	 */
	public function testXmlParse( $rawXmlResult, $expectedResultRowItemInstance ) {

		$instance = new XmlResponseParser();
		$resultFormat = $instance->parse( $rawXmlResult );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\RepositoryResult',
			$resultFormat
		);

		$this->assertResultFormat(
			$expectedResultRowItemInstance,
			$resultFormat
		);
	}

	public function testInvalidXmlThrowsException() {

		$rawResultProvider = new FakeRawResultProvider();

		$instance = new XmlResponseParser();

		$this->setExpectedException( '\SMW\SPARQLStore\Exception\XmlParserException' );
		$instance->parse( $rawResultProvider->getInvalidSparqlResultXml() );
	}

	protected function assertResultFormat( $expectedResultRowItemInstance, $results ) {

		if ( !is_array( $expectedResultRowItemInstance ) ) {
			$expectedResultRowItemInstance =  [ $expectedResultRowItemInstance ];
		}

		foreach ( $results as $key => $row ) {
			$this->assertResultRow( $expectedResultRowItemInstance[$key], $row );
		}
	}

	protected function assertResultRow( $expectedItemInstance, $row ) {

		foreach ( $row as $key => $item ) {

			if ( $item === null ) {
				continue;
			}

			$this->assertEquals( $expectedItemInstance, $item );
		}
	}

	public function rawXmlResultDocumentProvider() {

		$rawResultProvider = new FakeRawResultProvider();

		#0
		$provider[] = [
			$rawResultProvider->getUriResourceSparqlResultXml(),
			new ExpResource( 'http://example.org/id/Foo' )
		];

		#1
		$provider[] = [
			$rawResultProvider->getEmptySparqlResultXml(),
			null
		];

		#2 @bug 62218
		$provider[] = [
			$rawResultProvider->getNonTypeLiteralResultXml(),
			new ExpLiteral( 'Has foo' )
		];

		#3
		$provider[] = [
			$rawResultProvider->getBooleanSparqlResultXml(),
			new ExpLiteral( 'true', 'http://www.w3.org/2001/XMLSchema#boolean' )
		];

		#4
		$provider[] = [
			$rawResultProvider->getStringTypeLiteralSparqlResultXml(),
			new ExpLiteral( 'Foo', 'http://www.w3.org/2001/XMLSchema#string' )
		];

		#5
		$provider[] = [
			$rawResultProvider->getIntegerTypeLiteralSparqlResultXml(),
			new ExpLiteral( '1', 'http://www.w3.org/2001/XMLSchema#integer' )
		];

		#6
		$provider[] = [
			$rawResultProvider->getMixedRowsSparqlResultXml(),
			[
				new ExpResource( 'http://example.org/id/Foo' ),
				new ExpResource( 'http://example.org/id/Bar' ),
				new ExpLiteral( 'Quux', 'http://www.w3.org/2001/XMLSchema#string' )
			]
		];

		#7 #450
		$provider[] = [
			false,
			null
		];

		#8 #450
		$provider[] = [
			'false',
			null
		];

		#9 #626
		$provider[] = [
			'true',
			new ExpLiteral( 'true', 'http://www.w3.org/2001/XMLSchema#boolean' )
		];

		#10
		$provider[] = [
			'',
			new ExpLiteral( 'false', 'http://www.w3.org/2001/XMLSchema#boolean' )
		];

		#11
		$provider[] = [
			$rawResultProvider->getMixedRowsSparqlResultUtf8Xml(),
			[
				new ExpResource( 'http://example.org/id/F安o' ),
				new ExpResource( 'http://example.org/id/B定ar' ),
				new ExpLiteral( 'Quux安定', 'http://www.w3.org/2001/XMLSchema#string' )
			]
		];

		return $provider;
	}

}
