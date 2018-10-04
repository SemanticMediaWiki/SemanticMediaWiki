<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use SMW\SPARQLStore\RepositoryClient;
use SMW\Tests\Utils\Fixtures\Results\FakeRawResultProvider;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ElementaryRepositoryConnectorTest extends \PHPUnit_Framework_TestCase {

	public function getRepositoryConnectors() {
		// Legacy and should be removed once obsolete
		return [
			'SMWSparqlDatabase'
		];
	}

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
			[ 'foaf' => 'http://xmlns.com/foaf/0.1/>' ]
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

		$provider = [];
		$encodedDefaultGraph = urlencode( 'http://foo/myDefaultGraph' );

		foreach ( $this->getRepositoryConnectors() as $repositoryConnector ) {

			switch ( $repositoryConnector ) {
				case '\SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector':
					$expectedPostField = '&default-graph-uri=' . $encodedDefaultGraph . '&output=xml';
					break;
				case 'SMWSparqlDatabase4Store':
				case '\SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector':
					$expectedPostField = "&restricted=1" . '&default-graph-uri=' . $encodedDefaultGraph;
					break;
				default:
					$expectedPostField = '&default-graph-uri=' . $encodedDefaultGraph;
					break;
			};

			$provider[] = [ $repositoryConnector, $expectedPostField ];
		}

		return $provider;
	}

	public function httpDatabaseConnectorInstanceNameForDeleteProvider() {

		$provider = [];

		foreach ( $this->getRepositoryConnectors() as $repositoryConnector ) {

			switch ( $repositoryConnector ) {
				case 'SMWSparqlDatabaseVirtuoso':
				case 'SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector':
					$expectedPostField = 'query=';
					break;
				default:
					$expectedPostField = 'update=';
					break;
			};

			$provider[] = [ $repositoryConnector, $expectedPostField ];
		}

		return $provider;
	}

	public function httpDatabaseConnectorInstanceNameForInsertProvider() {

		$provider = [];

		foreach ( $this->getRepositoryConnectors() as $repositoryConnector ) {
			$provider[] = [ $repositoryConnector ];
		}

		return $provider;
	}

}
