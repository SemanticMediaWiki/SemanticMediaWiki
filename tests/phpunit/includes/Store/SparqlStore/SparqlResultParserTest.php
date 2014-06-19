<?php

namespace SMW\Tests\Store\SparqlStore;

use SMWSparqlResultParser as SparqlResultParser;

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
 * @since 1.9.3
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
	 * @dataProvider xmlResultProvider
	 */
	public function testMakeResultFromXml( $xmlQueryResult, $expectedResultRowItemInstance ) {

		$instance = new SparqlResultParser();
		$resultWrapper = $instance->makeResultFromXml( $xmlQueryResult );

		$this->assertInstanceOf(
			'\SMWSparqlResultWrapper',
			$resultWrapper
		);

		$this->assertResultWrapper(
			$expectedResultRowItemInstance,
			$resultWrapper
		);
	}

	protected function assertResultWrapper( $expectedResultRowItemInstance, $results ) {

		foreach ( $results as $row ) {
			$this->assertResultRow( $expectedResultRowItemInstance, $row );
		}
	}

	protected function assertResultRow( $expectedItemInstance, $row ) {

		foreach ( $row as $item ) {

			if ( $item === null ) {
				continue;
			}

			$this->assertInstanceOf( $expectedItemInstance, $item );
		}
	}

	public function xmlResultProvider() {

		$provider = array();

		#0
		$provider[] = array(
			'<?xml version="1.0"?>
			<sparql xmlns="http://www.w3.org/2005/sparql-results#">
				<head>
					<variable name="result"/>
				</head>
				<results>
					<result>
						<binding name="result">
							<uri>http://example.org/id/Lorem_ipsum</uri>
						</binding>
					</result>
				</results>
			</sparql>',
			'SMWExpResource'
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
						<binding name="s">
							<literal>Has foo</literal>
						</binding>
					</result>
				</results>
			</sparql>',
			'SMWExpLiteral'
		);

		return $provider;
	}

}
