<?php

namespace SMW\Tests\Unit\SPARQLStore\RepositoryConnectors;

use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
use PHPUnit\Framework\TestCase;
use SMW\SPARQLStore\QueryEngine\RepositoryResult;
use SMW\SPARQLStore\RepositoryClient;
use SMW\Tests\Utils\Fixtures\Results\FakeRawResultProvider;
use StatusValue;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ElementaryRepositoryConnectorTest extends TestCase {

	public function getRepositoryConnectors() {
		return [];
	}

	protected function createMockHttpRequestFactory( MWHttpRequest $mockRequest ): HttpRequestFactory {
		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );

		$httpRequestFactory->method( 'create' )
			->willReturn( $mockRequest );

		return $httpRequestFactory;
	}

	protected function createSuccessfulMockRequest( string $content = '' ): MWHttpRequest {
		$mockRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$mockRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

		$mockRequest->method( 'getContent' )
			->willReturn( $content );

		$mockRequest->method( 'getStatus' )
			->willReturn( 200 );

		return $mockRequest;
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameForAskProvider
	 *
	 * @see https://www.w3.org/TR/rdf-sparql-query/#ask
	 */
	public function testAskToQueryEndpointOnMockedHttpRequest( $httpDatabaseConnector, $expectedPostField ) {
		$rawResultProvider = new FakeRawResultProvider();

		$mockRequest = $this->createSuccessfulMockRequest(
			$rawResultProvider->getEmptySparqlResultXml()
		);

		$httpRequestFactory = $this->createMockHttpRequestFactory( $mockRequest );

		$instance = new $httpDatabaseConnector(
			new RepositoryClient( 'http://foo/myDefaultGraph', 'http://localhost:9999/query' ),
			$httpRequestFactory
		);

		$repositoryResult = $instance->ask(
			'?x foaf:name "Foo"',
			[ 'foaf' => 'http://xmlns.com/foaf/0.1/>' ]
		);

		$this->assertInstanceOf(
			RepositoryResult::class,
			$repositoryResult
		);
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameForDeleteProvider
	 *
	 * @see http://www.w3.org/TR/sparql11-update/#deleteInsert
	 */
	public function testDeleteToUpdateEndpointOnMockedHttpRequest( $httpDatabaseConnector, $expectedPostField ) {
		$mockRequest = $this->createSuccessfulMockRequest();
		$httpRequestFactory = $this->createMockHttpRequestFactory( $mockRequest );

		$instance = new $httpDatabaseConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query',
				'http://localhost:9999/update'
			),
			$httpRequestFactory
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
		$mockRequest = $this->createSuccessfulMockRequest();
		$httpRequestFactory = $this->createMockHttpRequestFactory( $mockRequest );

		$instance = new $httpDatabaseConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query',
				'http://localhost:9999/update',
				'http://localhost:9999/data'
			),
			$httpRequestFactory
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
				case 'SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector':
					$expectedPostField = '&default-graph-uri=' . $encodedDefaultGraph . '&output=xml';
					break;
				case 'SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector':
					$expectedPostField = "&restricted=1" . '&default-graph-uri=' . $encodedDefaultGraph;
					break;
				default:
					$expectedPostField = '&default-graph-uri=' . $encodedDefaultGraph;
					break;
			}

			$provider[] = [ $repositoryConnector, $expectedPostField ];
		}

		return $provider;
	}

	public function httpDatabaseConnectorInstanceNameForDeleteProvider() {
		$provider = [];

		foreach ( $this->getRepositoryConnectors() as $repositoryConnector ) {

			switch ( $repositoryConnector ) {
				case 'SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector':
					$expectedPostField = 'query=';
					break;
				default:
					$expectedPostField = 'update=';
					break;
			}

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
