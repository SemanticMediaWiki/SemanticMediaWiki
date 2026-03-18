<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use Onoi\HttpRequest\HttpRequest;
use SMW\SPARQLStore\RepositoryClient;
use SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector;
use SMW\Tests\Utils\Fixtures\Results\FakeRawResultProvider;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
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

		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'setOption' )
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

	public function testDoQueryAddsOutputXmlParameter() {
		$rawResultProvider = new FakeRawResultProvider();
		$capturedOptions = [];

		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->atLeastOnce() )
			->method( 'setOption' )
			->willReturnCallback( static function ( $option, $value ) use ( &$capturedOptions ) {
				$capturedOptions[$option] = $value;
				return true;
			} );

		$httpRequest->method( 'execute' )
			->willReturn( $rawResultProvider->getEmptySparqlResultXml() );

		$httpRequest->method( 'getLastErrorCode' )
			->willReturn( 0 );

		$instance = new FusekiRepositoryConnector(
			new RepositoryClient( 'http://foo/graph', 'http://localhost/query' ),
			$httpRequest
		);

		$instance->doQuery( 'SELECT ?s WHERE { ?s ?p ?o }' );

		$this->assertStringContainsString( '&output=xml', $capturedOptions[CURLOPT_POSTFIELDS] );
	}

}
