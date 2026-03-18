<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
use SMW\SPARQLStore\RepositoryClient;
use SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector;
use StatusValue;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class GenericRepositoryConnectorTest extends ElementaryRepositoryConnectorTest {

	public function getRepositoryConnectors() {
		return [
			GenericRepositoryConnector::class
		];
	}

	public function testShouldPing() {
		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );

		$repositoryClient = new RepositoryClient(
			'http://foo/myDefaultGraph',
			'http://localhost:9999/query',
			'http://localhost:9999/update',
			'http://localhost:9999/data'
		);

		$repositoryClient->setFeatureSet( SMW_SPARQL_CONNECTION_PING );

		$instance = new GenericRepositoryConnector(
			$repositoryClient,
			$httpRequestFactory
		);

		$this->assertTrue(
			$instance->shouldPing()
		);
	}

	/**
	 * @dataProvider endpointProvider
	 */
	public function testGetEndpoint( $endpoint, $expected ) {
		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query',
				'http://localhost:9999/update',
				'http://localhost:9999/data'
			),
			$httpRequestFactory
		);

		$this->assertEquals(
			$expected,
			$instance->getEndpoint( $endpoint )
		);
	}

	public function testGetLastErrorCode() {
		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query',
				'http://localhost:9999/update'
			),
			$httpRequestFactory
		);

		$this->assertSame(
			0,
			$instance->getLastErrorCode()
		);
	}

	public function testPingQueryEndpointReturnsTrue() {
		$mockRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$mockRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

		$mockRequest->method( 'getStatus' )
			->willReturn( 200 );

		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );
		$httpRequestFactory->method( 'create' )
			->willReturn( $mockRequest );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query',
				'http://localhost:9999/update'
			),
			$httpRequestFactory
		);

		$this->assertTrue( $instance->ping() );
	}

	/**
	 * @dataProvider pingAliveHttpCodeProvider
	 */
	public function testPingQueryEndpointTreatsErrorCodesAsAlive( int $httpCode ) {
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

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query',
				'http://localhost:9999/update'
			),
			$httpRequestFactory
		);

		$this->assertTrue(
			$instance->ping(),
			"HTTP $httpCode from a SPARQL endpoint should be treated as alive"
		);
	}

	public function testPingQueryEndpointReturnsFalseOnConnectionFailure() {
		$mockRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$mockRequest->method( 'execute' )
			->willReturn( StatusValue::newFatal( 'http-request-error' ) );

		$mockRequest->method( 'getStatus' )
			->willReturn( 0 );

		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );
		$httpRequestFactory->method( 'create' )
			->willReturn( $mockRequest );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query',
				'http://localhost:9999/update'
			),
			$httpRequestFactory
		);

		$this->assertFalse( $instance->ping() );
	}

	public function testPingUpdateEndpointReturnsFalseWhenEmpty() {
		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query',
				''
			),
			$httpRequestFactory
		);

		$this->assertFalse(
			$instance->ping( GenericRepositoryConnector::ENDP_UPDATE )
		);
	}

	public static function pingAliveHttpCodeProvider(): array {
		return [
			'HTTP 500 means alive' => [ 500 ],
			'HTTP 400 means alive' => [ 400 ],
		];
	}

	public function endpointProvider() {
		yield GenericRepositoryConnector::UPDATE_ENDPOINT => [
			GenericRepositoryConnector::UPDATE_ENDPOINT,
			'http://localhost:9999/update'
		];

		yield GenericRepositoryConnector::QUERY_ENDPOINT => [
			GenericRepositoryConnector::QUERY_ENDPOINT,
			'http://localhost:9999/query'
		];

		yield GenericRepositoryConnector::DATA_ENDPOINT => [
			GenericRepositoryConnector::DATA_ENDPOINT,
			'http://localhost:9999/data'
		];

		yield 'unknown' => [
			'foo',
			null
		];
	}

}
