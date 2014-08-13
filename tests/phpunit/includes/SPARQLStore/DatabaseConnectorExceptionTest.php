<?php

namespace SMW\Tests\SPARQLStore;

/**
 * @covers \SMW\SPARQLStore\FusekiHttpDatabaseConnector
 * @covers \SMW\SPARQLStore\FourstoreHttpDatabaseConnector
 * @covers \SMW\SPARQLStore\VirtuosoHttpDatabaseConnector
 * @covers \SMW\SPARQLStore\GenericHttpDatabaseConnector
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class DatabaseConnectorExceptionTest extends \PHPUnit_Framework_TestCase {

	private $defaultGraph;

	private $databaseConnectors = array(
		'\SMW\SPARQLStore\GenericHttpDatabaseConnector',
		'\SMW\SPARQLStore\FusekiHttpDatabaseConnector',
		'\SMW\SPARQLStore\FourstoreHttpDatabaseConnector',
		'\SMW\SPARQLStore\VirtuosoHttpDatabaseConnector',

		// Legacy and should be removed once obsolete
		'SMWSparqlDatabase4Store',
		'SMWSparqlDatabaseVirtuoso',
		'SMWSparqlDatabase'
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
			'\SMW\SPARQLStore\GenericHttpDatabaseConnector',
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

		$this->setExpectedException( '\SMW\SPARQLStore\Exception\BadHttpDatabaseResponseException' );
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

		$this->setExpectedException( '\SMW\SPARQLStore\Exception\BadHttpDatabaseResponseException' );
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

		$this->setExpectedException( '\SMW\SPARQLStore\Exception\BadHttpDatabaseResponseException' );
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

		$provider = array();

		foreach ( $this->databaseConnectors as $databaseConnector ) {
			$provider[] = array( $databaseConnector );
		}

		return $provider;
	}

}
