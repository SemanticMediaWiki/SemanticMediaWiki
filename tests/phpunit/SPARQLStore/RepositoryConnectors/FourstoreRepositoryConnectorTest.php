<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use Onoi\HttpRequest\HttpRequest;
use SMW\SPARQLStore\RepositoryClient;
use SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector;
use SMW\Tests\Utils\Fixtures\Results\FakeRawResultProvider;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 */
class FourstoreRepositoryConnectorTest extends ElementaryRepositoryConnectorTest {

	public function getRepositoryConnectors() {
		return [
			FourstoreRepositoryConnector::class
		];
	}

	public function testDoQueryAddsRestrictedParameter() {
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

		$instance = new FourstoreRepositoryConnector(
			new RepositoryClient( 'http://foo/graph', 'http://localhost/query' ),
			$httpRequest
		);

		$instance->doQuery( 'ASK { ?s ?p ?o }' );

		$this->assertStringContainsString( '&restricted=1', $capturedOptions[CURLOPT_POSTFIELDS] );
	}

	public function testDoHttpPostUsesFormEncodedParams() {
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

		$httpRequest->method( 'getLastErrorCode' )->willReturn( 0 );

		$instance = new FourstoreRepositoryConnector(
			new RepositoryClient(
				'http://foo/graph',
				'http://localhost/query',
				'http://localhost/update',
				'http://localhost/data'
			),
			$httpRequest
		);

		$instance->doHttpPost( '<s> <p> <o> .' );

		// Fourstore uses form-encoded data= param, not CURLOPT_INFILE
		$this->assertStringContainsString( 'data=', $capturedOptions[CURLOPT_POSTFIELDS] );
		$this->assertStringContainsString( 'mime-type=application/x-turtle', $capturedOptions[CURLOPT_POSTFIELDS] );
		$this->assertArrayNotHasKey( CURLOPT_INFILE, $capturedOptions );
	}

	public function testDoUpdateOmitsCharsetFromContentType() {
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

		$httpRequest->method( 'getLastErrorCode' )->willReturn( 0 );

		$instance = new FourstoreRepositoryConnector(
			new RepositoryClient( '', 'http://localhost/query', 'http://localhost/update' ),
			$httpRequest
		);

		$instance->doUpdate( 'INSERT DATA { <s> <p> <o> }' );

		// 4Store breaks when charset is included
		$this->assertSame(
			[ 'Content-Type: application/x-www-form-urlencoded' ],
			$capturedOptions[CURLOPT_HTTPHEADER]
		);
	}

}
