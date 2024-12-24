<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector;
use SMW\SPARQLStore\RepositoryClient;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FusekiRepositoryConnectorTest extends ElementaryRepositoryConnectorTest {

	public function getRepositoryConnectors() {
		return [
			FusekiRepositoryConnector::class
		];
	}

	public function testGetVersion() {
		$data = json_encode( [ 'version' => '3.2' ] );

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->at( 5 ) )
			->method( 'setOption' )
			->with(
				CURLOPT_URL,
				$this->stringContains( 'http://usr:pass@localhost:9999/$/server' ) )
			->willReturn( true );

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( $data );

		$instance = new FusekiRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://usr:pass@localhost:9999/query',
				'http://localhost:9999/update'
			),
			$httpRequest
		);

		$this->assertSame(
			'3.2',
			$instance->getVersion()
		);
	}

}
