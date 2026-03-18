<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
use SMW\SPARQLStore\RepositoryClient;
use SMW\SPARQLStore\RepositoryConnectors\VirtuosoRepositoryConnector;
use StatusValue;

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

		$mockRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$mockRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

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

		$instance = new VirtuosoRepositoryConnector(
			new RepositoryClient( '', 'http://localhost/query', 'http://localhost/update' ),
			$httpRequestFactory
		);

		$instance->doUpdate( 'DELETE { ?s ?p ?o } WHERE { ?s ?p ?o }' );

		// Virtuoso uses 'query=' not 'update='
		$this->assertStringStartsWith( 'query=', $capturedOptions['postData'] );
	}

	public function testDeleteUsesFromSyntax() {
		$capturedOptions = [];

		$mockRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$mockRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

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

		$instance = new VirtuosoRepositoryConnector(
			new RepositoryClient(
				'http://foo/graph',
				'http://localhost/query',
				'http://localhost/update'
			),
			$httpRequestFactory
		);

		$instance->delete( '?s ?p ?o', '?s ?p ?o' );

		// Virtuoso uses DELETE FROM <graph> not WITH <graph> DELETE
		$decoded = urldecode( $capturedOptions['postData'] );
		$this->assertStringContainsString( 'DELETE FROM <http://foo/graph>', $decoded );
	}

}
