<?php

namespace SMW\Tests\SPARQLStore;

use SMW\Tests\Utils\Fixtures\Results\FakeRawResultProvider;

/**
 * @covers \SMW\SPARQLStore\FusekiHttpDatabaseConnector
 * @covers \SMW\SPARQLStore\FourstoreHttpDatabaseConnector
 * @covers \SMW\SPARQLStore\VirtuosoHttpDatabaseConnector
 * @covers \SMW\SPARQLStore\GenericHttpDatabaseConnector
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
class DatabaseConnectorHttpRequestIntegrityTest extends \PHPUnit_Framework_TestCase {

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

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameForAskProvider
	 *
	 * @see https://www.w3.org/TR/rdf-sparql-query/#ask
	 */
	public function testAskToQueryEndpointOnMockedHttpRequest( $httpDatabaseConnector, $expectedPostField ) {

		$rawResultProvider = new FakeRawResultProvider();

		$httpRequest = $this->getMockBuilder( '\SMW\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->at( 3 ) )
			->method( 'setOption' )
			->with(
				$this->equalTo( CURLOPT_POSTFIELDS ),
				$this->stringContains( $expectedPostField ) )
			->will( $this->returnValue( true ) );

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->will( $this->returnValue( $rawResultProvider->getEmptySparqlResultXml() ) );

		$instance = new $httpDatabaseConnector(
			'http://foo/myDefaultGraph',
			'http://localhost:9999/query'
		);

		$instance->setHttpRequest( $httpRequest );

		$federateResultSet = $instance->ask(
			'?x foaf:name "Foo"',
			array( 'foaf' => 'http://xmlns.com/foaf/0.1/>' )
		);

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\FederateResultSet',
			$federateResultSet
		);
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameForDeleteProvider
	 *
	 * @see http://www.w3.org/TR/sparql11-update/#deleteInsert
	 */
	public function testDeleteToUpdateEndpointOnMockedHttpRequest( $httpDatabaseConnector, $expectedPostField ) {

		$httpRequest = $this->getMockBuilder( '\SMW\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->at( 2 ) )
			->method( 'setOption' )
			->with(
				$this->equalTo( CURLOPT_POSTFIELDS ),
				$this->stringContains( $expectedPostField ) )
			->will( $this->returnValue( true ) );

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->will( $this->returnValue( 0 ) );

		$instance = new $httpDatabaseConnector(
			'http://foo/myDefaultGraph',
			'http://localhost:9999/query',
			'http://localhost:9999/update'
		);

		$instance->setHttpRequest( $httpRequest );

		$this->assertTrue( $instance->delete( 'wiki:Foo ?p ?o', 'wiki:Foo ?p ?o' ) );
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameForInsertProvider
	 *
	 * @see http://www.w3.org/TR/sparql11-http-rdf-update/#http-post
	 */
	public function testInsertViaHttpPostToDataPointOnMockedHttpRequest( $httpDatabaseConnector ) {

		$httpRequest = $this->getMockBuilder( '\SMW\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->will( $this->returnValue( 0 ) );

		$instance = new $httpDatabaseConnector(
			'http://foo/myDefaultGraph',
			'http://localhost:9999/query',
			'http://localhost:9999/update',
			'http://localhost:9999/data'
		);

		$instance->setHttpRequest( $httpRequest );

		$this->assertTrue( $instance->insertData( 'property:Foo  wiki:Bar;' ) );
	}

	public function httpDatabaseConnectorInstanceNameForAskProvider() {

		$provider = array();
		$encodedDefaultGraph = urlencode( 'http://foo/myDefaultGraph' );

		foreach ( $this->databaseConnectors as $databaseConnector ) {

			switch ( $databaseConnector ) {
				case '\SMW\SPARQLStore\FusekiHttpDatabaseConnector':
					$expectedPostField = '&default-graph-uri=' . $encodedDefaultGraph . '&output=xml';
					break;
				case 'SMWSparqlDatabase4Store':
				case '\SMW\SPARQLStore\FourstoreHttpDatabaseConnector':
					$expectedPostField = "&restricted=1" . '&default-graph-uri=' . $encodedDefaultGraph;
					break;
				default:
					$expectedPostField = '&default-graph-uri=' . $encodedDefaultGraph;
					break;
			};

			$provider[] = array( $databaseConnector, $expectedPostField );
		}

		return $provider;
	}

	public function httpDatabaseConnectorInstanceNameForDeleteProvider() {

		$provider = array();

		foreach ( $this->databaseConnectors as $databaseConnector ) {

			switch ( $databaseConnector ) {
				case 'SMWSparqlDatabaseVirtuoso':
				case '\SMW\SPARQLStore\VirtuosoHttpDatabaseConnector':
					$expectedPostField = 'query=';
					break;
				default:
					$expectedPostField = 'update=';
					break;
			};

			$provider[] = array( $databaseConnector, $expectedPostField );
		}

		return $provider;
	}

	public function httpDatabaseConnectorInstanceNameForInsertProvider() {

		$provider = array();

		foreach ( $this->databaseConnectors as $databaseConnector ) {
			$provider[] = array( $databaseConnector );
		}

		return $provider;
	}

}
