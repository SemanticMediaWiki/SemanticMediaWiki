<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
use SMW\SPARQLStore\RepositoryClient;
use SMW\SPARQLStore\RepositoryConnectors\FusekiRepositoryConnector;
use SMW\Tests\Utils\Fixtures\Results\FakeRawResultProvider;
use StatusValue;

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

		$mockRequest = $this->createSuccessfulMockRequest( $data );
		$httpRequestFactory = $this->createMockHttpRequestFactory( $mockRequest );

		$instance = new FusekiRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://usr:pass@localhost:9999/query',
				'http://localhost:9999/update'
			),
			$httpRequestFactory
		);

		$this->assertSame(
			'3.2',
			$instance->getVersion()
		);
	}

	public function testDoQueryAddsOutputXmlParameter() {
		$rawResultProvider = new FakeRawResultProvider();
		$capturedOptions = [];

		$mockRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$mockRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

		$mockRequest->method( 'getContent' )
			->willReturn( $rawResultProvider->getEmptySparqlResultXml() );

		$mockRequest->method( 'getStatus' )
			->willReturn( 200 );

		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );

		$httpRequestFactory->expects( $this->once() )
			->method( 'create' )
			->with(
				$this->anything(),
				$this->callback( static function ( $options ) use ( &$capturedOptions ) {
					$capturedOptions = $options;
					return true;
				} ),
				$this->anything()
			)
			->willReturn( $mockRequest );

		$instance = new FusekiRepositoryConnector(
			new RepositoryClient( 'http://foo/graph', 'http://localhost/query' ),
			$httpRequestFactory
		);

		$instance->doQuery( 'SELECT ?s WHERE { ?s ?p ?o }' );

		$this->assertStringContainsString( '&output=xml', $capturedOptions['postData'] );
	}

}
