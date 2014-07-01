<?php

namespace SMW\Tests\SPARQLStore;

/**
 * @covers \SMW\SPARQLStore\FusekiHttpDatabaseConnector
 * @covers \SMWSparqlDatabase4Store
 * @covers \SMWSparqlDatabaseVirtuoso
 * @covers \SMWSparqlDatabase
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9.3
 *
 * @author mwjames
 */
class DatabaseConnectorExceptionTest extends \PHPUnit_Framework_TestCase {

	private $defaultGraph;

	private $databaseConnectors = array(
		'SMWSparqlDatabase',
		'\SMW\SPARQLStore\FusekiHttpDatabaseConnector',
		'SMWSparqlDatabase4Store',
		'SMWSparqlDatabaseVirtuoso'
	);

	protected function setUp() {
		parent::setUp();

		$this->defaultGraph = 'http://foo/myDefaultGraph';
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testCanConstruct( $httpConnector ) {

		$this->assertInstanceOf(
			'\SMWSparqlDatabase',
			new $httpConnector( $this->defaultGraph, '' )
		);
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoQueryForEmptyQueryEndpointThrowsException( $httpConnector ) {

		$instance = new $httpConnector(
			$this->defaultGraph,
			''
		);

		$this->setExpectedException( '\SMW\SPARQLStore\BadHttpDatabaseResponseException' );
		$instance->doQuery( '' );
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoUpdateForEmptyUpdateEndpointThrowsException( $httpConnector ) {

		$instance = new $httpConnector(
			$this->defaultGraph,
			'',
			''
		);

		$this->setExpectedException( '\SMW\SPARQLStore\BadHttpDatabaseResponseException' );
		$instance->doUpdate( '' );
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoHttpPostForEmptyDataEndpointThrowsException( $httpConnector ) {

		$instance = new $httpConnector(
			$this->defaultGraph,
			'',
			'',
			''
		);

		$this->setExpectedException( '\SMW\SPARQLStore\BadHttpDatabaseResponseException' );
		$instance->doHttpPost( '' );
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoHttpPostForUnreachableDataEndpointThrowsException( $httpConnector ) {

		$instance = new $httpConnector(
			$this->defaultGraph,
			'',
			'',
			'unreachableDataEndpoint'
		);

		$this->setExpectedException( 'Exception' );
		$instance->doHttpPost( '' );
	}

	public function httpDatabaseConnectorInstanceNameProvider() {

		foreach ( $this->databaseConnectors as $databaseConnector ) {
			$provider[] = array( $databaseConnector );
		}

		return $provider;
	}

}
