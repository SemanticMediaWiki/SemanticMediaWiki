<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
use PHPUnit\Framework\TestCase;
use SMW\SPARQLStore\Exception\BadHttpEndpointResponseException;
use SMW\SPARQLStore\RepositoryClient;
use SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector;
use SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector;
use SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector;
use SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector;
use StatusValue;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector
 * @covers \SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector
 *
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class RepositoryConnectorsExceptionTest extends TestCase {

	private $defaultGraph;

	private $databaseConnectors = [
		GenericRepositoryConnector::class,
		FusekiRepositoryConnector::class,
		FourstoreRepositoryConnector::class,
		VirtuosoRepositoryConnector::class,
	];

	protected function setUp(): void {
		parent::setUp();

		$this->defaultGraph = 'http://foo/myDefaultGraph';
	}

	private function createHttpRequestFactory(): HttpRequestFactory {
		return $this->createMock( HttpRequestFactory::class );
	}

	private function createFailingHttpRequestFactory( int $httpCode ): HttpRequestFactory {
		$mockRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$mockRequest->method( 'execute' )
			->willReturn( StatusValue::newFatal( 'http-request-error' ) );

		$mockRequest->method( 'getStatus' )
			->willReturn( $httpCode );

		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );

		$httpRequestFactory->method( 'create' )
			->willReturn( $mockRequest );

		return $httpRequestFactory;
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testCanConstruct( $httpConnector ) {
		$this->assertInstanceOf(
			GenericRepositoryConnector::class,
			new $httpConnector( new RepositoryClient( $this->defaultGraph, '' ), $this->createHttpRequestFactory() )
		);
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoQueryForEmptyQueryEndpointThrowsException( $httpConnector ) {
		$instance = new $httpConnector(
			new RepositoryClient( $this->defaultGraph, '' ),
			$this->createHttpRequestFactory()
		);

		$this->expectException( BadHttpEndpointResponseException::class );
		$instance->doQuery( '' );
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoUpdateForEmptyUpdateEndpointThrowsException( $httpConnector ) {
		$instance = new $httpConnector(
			new RepositoryClient( $this->defaultGraph, '', '' ),
			$this->createHttpRequestFactory()
		);

		$this->expectException( BadHttpEndpointResponseException::class );
		$instance->doUpdate( '' );
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoHttpPostForEmptyDataEndpointThrowsException( $httpConnector ) {
		$instance = new $httpConnector(
			new RepositoryClient( $this->defaultGraph, '', '', '' ),
			$this->createHttpRequestFactory()
		);

		$this->expectException( BadHttpEndpointResponseException::class );
		$instance->doHttpPost( '' );
	}

	/**
	 * @dataProvider httpDatabaseConnectorInstanceNameProvider
	 */
	public function testDoHttpPostForUnreachableDataEndpointThrowsException( $httpConnector ) {
		$httpRequestFactory = $this->createFailingHttpRequestFactory( 500 );

		$instance = new $httpConnector(
			new RepositoryClient( $this->defaultGraph, '', '', 'unreachableDataEndpoint' ),
			$httpRequestFactory
		);

		$this->expectException( 'Exception' );
		$instance->doHttpPost( '' );
	}

	public function httpDatabaseConnectorInstanceNameProvider() {
		$provider = [];

		foreach ( $this->databaseConnectors as $databaseConnector ) {
			$provider[] = [ $databaseConnector ];
		}

		return $provider;
	}

}
