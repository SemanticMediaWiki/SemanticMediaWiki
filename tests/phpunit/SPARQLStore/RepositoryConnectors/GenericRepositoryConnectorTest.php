<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector;
use SMW\SPARQLStore\RepositoryClient;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
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
		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$repositoryClient = new RepositoryClient(
			'http://foo/myDefaultGraph',
			'http://localhost:9999/query',
			'http://localhost:9999/update',
			'http://localhost:9999/data'
		);

		$repositoryClient->setFeatureSet( SMW_SPARQL_CONNECTION_PING );

		$instance = new GenericRepositoryConnector(
			$repositoryClient,
			$httpRequest
		);

		$this->assertTrue(
			$instance->shouldPing()
		);
	}

	/**
	 * @dataProvider endpointProvider
	 */
	public function testGetEndpoint( $endpoint, $expected ) {
		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query',
				'http://localhost:9999/update',
				'http://localhost:9999/data'
			),
			$httpRequest
		);

		$this->assertEquals(
			$expected,
			$instance->getEndpoint( $endpoint )
		);
	}

	public function testGetLastErrorCode() {
		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 42 );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query',
				'http://localhost:9999/update'
			),
			$httpRequest
		);

		$this->assertEquals(
			42,
			$instance->getLastErrorCode()
		);
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
