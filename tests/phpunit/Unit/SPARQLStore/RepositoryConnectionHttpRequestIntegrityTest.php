<?php

namespace SMW\Tests\SPARQLStore;

use SMW\Tests\Utils\Fixtures\Results\FakeRawResultProvider;
use SMW\SPARQLStore\RepositoryClient;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnector\FusekiHttpRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnector\FourstoreHttpRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnector\VirtuosoHttpRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnector\GenericHttpRepositoryConnector
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryConnectionHttpRequestIntegrityTest extends \PHPUnit_Framework_TestCase {

	private $databaseConnectors = array(
		'\SMW\SPARQLStore\RepositoryConnector\GenericHttpRepositoryConnector',
		'\SMW\SPARQLStore\RepositoryConnector\FusekiHttpRepositoryConnector',
		'\SMW\SPARQLStore\RepositoryConnector\FourstoreHttpRepositoryConnector',
		'\SMW\SPARQLStore\RepositoryConnector\VirtuosoHttpRepositoryConnector',

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

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->at( 8 ) )
			->method( 'setOption' )
			->with(
				$this->equalTo( CURLOPT_POSTFIELDS ),
				$this->stringContains( $expectedPostField ) )
			->will( $this->returnValue( true ) );

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->will( $this->returnValue( $rawResultProvider->getEmptySparqlResultXml() ) );

		$instance = new $httpDatabaseConnector(
			new RepositoryClient( 'http://foo/myDefaultGraph', 'http://localhost:9999/query' ),
			$httpRequest
		);

		$repositoryResult = $instance->ask(
			'?x foaf:name "Foo"',
			array( 'foaf' => 'http://xmlns.com/foaf/0.1/>' )
		);

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\RepositoryResult',
			$repositoryResult
		);
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameForDeleteProvider
	 *
	 * @see http://www.w3.org/TR/sparql11-update/#deleteInsert
	 */
	public function testDeleteToUpdateEndpointOnMockedHttpRequest( $httpDatabaseConnector, $expectedPostField ) {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->at( 7 ) )
			->method( 'setOption' )
			->with(
				$this->equalTo( CURLOPT_POSTFIELDS ),
				$this->stringContains( $expectedPostField ) )
			->will( $this->returnValue( true ) );

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->will( $this->returnValue( 0 ) );

		$instance = new $httpDatabaseConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query',
				'http://localhost:9999/update'
			),
			$httpRequest
		);

		$this->assertTrue(
			$instance->delete( 'wiki:Foo ?p ?o', 'wiki:Foo ?p ?o' )
		);
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameForInsertProvider
	 *
	 * @see http://www.w3.org/TR/sparql11-http-rdf-update/#http-post
	 */
	public function testInsertViaHttpPostToDataPointOnMockedHttpRequest( $httpDatabaseConnector ) {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->will( $this->returnValue( 0 ) );

		$instance = new $httpDatabaseConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query',
				'http://localhost:9999/update',
				'http://localhost:9999/data'
			),
			$httpRequest
		);

		$this->assertTrue(
			$instance->insertData( 'property:Foo  wiki:Bar;' )
		);
	}

	public function httpDatabaseConnectorInstanceNameForAskProvider() {

		$provider = array();
		$encodedDefaultGraph = urlencode( 'http://foo/myDefaultGraph' );

		foreach ( $this->databaseConnectors as $databaseConnector ) {

			switch ( $databaseConnector ) {
				case '\SMW\SPARQLStore\RepositoryConnector\FusekiHttpRepositoryConnector':
					$expectedPostField = '&default-graph-uri=' . $encodedDefaultGraph . '&output=xml';
					break;
				case 'SMWSparqlDatabase4Store':
				case '\SMW\SPARQLStore\RepositoryConnector\FourstoreHttpRepositoryConnector':
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
				case '\SMW\SPARQLStore\RepositoryConnector\VirtuosoHttpRepositoryConnector':
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
