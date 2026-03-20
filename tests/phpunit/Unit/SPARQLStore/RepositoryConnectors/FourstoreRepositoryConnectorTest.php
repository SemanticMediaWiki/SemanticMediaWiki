<?php

namespace SMW\Tests\Unit\SPARQLStore\RepositoryConnectors;

use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
use SMW\SPARQLStore\RepositoryClient;
use SMW\SPARQLStore\RepositoryConnectors\FourstoreRepositoryConnector;
use SMW\Tests\Utils\Fixtures\Results\FakeRawResultProvider;
use StatusValue;

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

		$instance = new FourstoreRepositoryConnector(
			new RepositoryClient( 'http://foo/graph', 'http://localhost/query' ),
			$httpRequestFactory
		);

		$instance->doQuery( 'ASK { ?s ?p ?o }' );

		$this->assertStringContainsString( '&restricted=1', $capturedOptions['postData'] );
	}

	public function testDoHttpPostUsesFormEncodedParams() {
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

		$instance = new FourstoreRepositoryConnector(
			new RepositoryClient(
				'http://foo/graph',
				'http://localhost/query',
				'http://localhost/update',
				'http://localhost/data'
			),
			$httpRequestFactory
		);

		$instance->doHttpPost( '<s> <p> <o> .' );

		// Fourstore uses form-encoded data= param
		$this->assertStringContainsString( 'data=', $capturedOptions['postData'] );
		$this->assertStringContainsString( 'mime-type=application/x-turtle', $capturedOptions['postData'] );
	}

	public function testDoUpdateOmitsCharsetFromContentType() {
		$capturedHeaders = [];

		$mockRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$mockRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

		$mockRequest->method( 'getStatus' )
			->willReturn( 200 );

		$mockRequest->expects( $this->atLeastOnce() )
			->method( 'setHeader' )
			->willReturnCallback( static function ( $name, $value ) use ( &$capturedHeaders ) {
				$capturedHeaders[$name] = $value;
			} );

		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );

		$httpRequestFactory->expects( $this->once() )
			->method( 'create' )
			->willReturn( $mockRequest );

		$instance = new FourstoreRepositoryConnector(
			new RepositoryClient( '', 'http://localhost/query', 'http://localhost/update' ),
			$httpRequestFactory
		);

		$instance->doUpdate( 'INSERT DATA { <s> <p> <o> }' );

		// 4Store breaks when charset is included
		$this->assertSame(
			'application/x-www-form-urlencoded',
			$capturedHeaders['Content-Type']
		);
	}

}
