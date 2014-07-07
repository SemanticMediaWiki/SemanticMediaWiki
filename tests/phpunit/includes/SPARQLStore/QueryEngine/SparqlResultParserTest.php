<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMWSparqlResultParser as SparqlResultParser;

use SMWExpResource as ExpResource;
use SMWExpLiteral as ExpLiteral;

/**
 * @covers \SMWSparqlResultParser
 *
 * @ingroup Test
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
class SparqlResultParserTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMWSparqlResultParser',
			new SparqlResultParser()
		);
	}

	/**
	 * @dataProvider rawXmlResultDocumentProvider
	 */
	public function testXmlParseForSingleResultRow( $xmlQueryResult, $expectedResultRowItemInstance ) {

		$instance = new SparqlResultParser();
		$resultFormat = $instance->makeResultFromXml( $xmlQueryResult );

		$this->assertInstanceOf(
			'\SMWSparqlResultWrapper',
			$resultFormat
		);

		$this->assertResultFormat(
			$expectedResultRowItemInstance,
			$resultFormat
		);
	}

	public function testXmlParseForMultipleResultRows() {

		$rawXmlResultDocument =
			'<?xml version="1.0"?>
			<sparql xmlns="http://www.w3.org/2005/sparql-results#">
				<head>
					<variable name="result"/>
				</head>
				<results>
					<result>
						<binding name="result"><uri>http://example.org/id/Foo</uri></binding>
					</result>
					<result>
						<binding name="result"><uri>http://example.org/id/Bar</uri></binding>
					</result>
					<result>
						<binding name="result"><literal datatype="http://www.w3.org/2001/XMLSchema#string">Foo</literal></binding>
					</result>
				</results>
			</sparql>';

		$expectedResultRowItemInstances = array(
			new ExpResource( 'http://example.org/id/Foo' ),
			new ExpResource( 'http://example.org/id/Bar' ),
			new ExpLiteral( 'Foo', 'http://www.w3.org/2001/XMLSchema#string' )
		);

		$instance = new SparqlResultParser();
		$resultFormat = $instance->makeResultFromXml( $rawXmlResultDocument );

		$this->assertResultFormat(
			$expectedResultRowItemInstances,
			$resultFormat
		);
	}

	protected function assertResultFormat( $expectedResultRowItemInstance, $results ) {

		if ( !is_array( $expectedResultRowItemInstance ) ) {
			$expectedResultRowItemInstance =  array( $expectedResultRowItemInstance );
		}

		foreach ( $results as $key => $row ) {
			$this->assertResultRow( $expectedResultRowItemInstance[ $key ], $row );
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

		#0
		$provider[] = array(
			'<?xml version="1.0"?>
			<sparql xmlns="http://www.w3.org/2005/sparql-results#">
				<head>
					<variable name="result"/>
				</head>
				<results>
					<result>
						<binding name="result"><uri>http://example.org/id/Lorem_ipsum</uri></binding>
					</result>
				</results>
			</sparql>',
			new ExpResource( 'http://example.org/id/Lorem_ipsum' )
		);

		#1
		$provider[] = array(
			'<?xml version="1.0"?>
			<sparql xmlns="http://www.w3.org/2005/sparql-results#">
				<head>
					<variable name="s"/>
					<variable name="r"/>
				</head>
				<results>
				</results>
			</sparql>',
			null
		);

		#2 @bug 62218
		$provider[] = array(
			'<?xml version="1.0"?>
			<sparql xmlns="http://www.w3.org/2005/sparql-results#">
				<head>
					<variable name="s"/>
					<variable name="r"/>
				</head>
				<results>
					<result>
						<binding name="s"><literal>Has foo</literal></binding>
					</result>
				</results>
			</sparql>',
			new ExpLiteral( 'Has foo' )
		);

		#3 @see http://www.w3.org/2009/sparql/xml-results/output2.srx
		$provider[] = array( '
			<?xml version="1.0"?>
			<sparql xmlns="http://www.w3.org/2005/sparql-results#">
				<head>
					<link href="example2.rq" />
				</head>
				<boolean>true</boolean>
			</sparql>',
			new ExpLiteral( 'true', 'http://www.w3.org/2001/XMLSchema#boolean' )
		);

		#4
		$provider[] = array(
			'<?xml version="1.0"?>
			<sparql xmlns="http://www.w3.org/2005/sparql-results#">
				<head>
					<variable name="result"/>
				</head>
				<results>
					<result>
						<binding name="result"><literal datatype="http://www.w3.org/2001/XMLSchema#string">Foo</literal></binding>
					</result>
				</results>
			</sparql>',
			new ExpLiteral( 'Foo', 'http://www.w3.org/2001/XMLSchema#string' )
		);

		return $provider;
	}

}
