<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use Onoi\HttpRequest\HttpRequest;
use SMW\SPARQLStore\RepositoryClient;
use SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector;

/**
 * @covers \SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 */
class VirtuosoRepositoryConnectorTest extends ElementaryRepositoryConnectorTest {

	public function getRepositoryConnectors() {
		return [
			VirtuosoRepositoryConnector::class
		];
	}

	public function testDoUpdateUsesQueryParameter() {
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

		$instance = new VirtuosoRepositoryConnector(
			new RepositoryClient( '', 'http://localhost/query', 'http://localhost/update' ),
			$httpRequest
		);

		$instance->doUpdate( 'DELETE { ?s ?p ?o } WHERE { ?s ?p ?o }' );

		// Virtuoso uses 'query=' not 'update='
		$this->assertStringStartsWith( 'query=', $capturedOptions[CURLOPT_POSTFIELDS] );
	}

	public function testDeleteUsesFromSyntax() {
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

		$instance = new VirtuosoRepositoryConnector(
			new RepositoryClient(
				'http://foo/graph',
				'http://localhost/query',
				'http://localhost/update'
			),
			$httpRequest
		);

		$instance->delete( '?s ?p ?o', '?s ?p ?o' );

		// Virtuoso uses DELETE FROM <graph> not WITH <graph> DELETE
		$decoded = urldecode( $capturedOptions[CURLOPT_POSTFIELDS] );
		$this->assertStringContainsString( 'DELETE FROM <http://foo/graph>', $decoded );
	}

}
