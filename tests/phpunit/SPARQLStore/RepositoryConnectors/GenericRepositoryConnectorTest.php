<?php

namespace SMW\Tests\SPARQLStore\RepositoryConnectors;

use Onoi\HttpRequest\HttpRequest;
use SMW\SPARQLStore\QueryEngine\RepositoryResult;
use SMW\SPARQLStore\RepositoryClient;
use SMW\SPARQLStore\RepositoryConnectors\GenericRepositoryConnector;
use SMW\Tests\Utils\Fixtures\Results\FakeRawResultProvider;

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
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
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
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
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
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
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

	public function testConstructorSetsCurlOptions() {
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

		new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query'
			),
			$httpRequest
		);

		$this->assertFalse( $capturedOptions[CURLOPT_FORBID_REUSE] );
		$this->assertFalse( $capturedOptions[CURLOPT_FRESH_CONNECT] );
		$this->assertTrue( $capturedOptions[CURLOPT_RETURNTRANSFER] );
		$this->assertTrue( $capturedOptions[CURLOPT_FAILONERROR] );
		$this->assertEquals( 10, $capturedOptions[CURLOPT_CONNECTTIMEOUT] );
	}

	public function testPingQueryEndpointSuccess() {
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

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( '' );

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 0 );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost/query'
			),
			$httpRequest
		);

		$this->assertTrue( $instance->ping( GenericRepositoryConnector::ENDP_QUERY ) );
		$this->assertEquals( 'http://localhost/query', $capturedOptions[CURLOPT_URL] );
		$this->assertTrue( $capturedOptions[CURLOPT_NOBODY] );
		$this->assertTrue( $capturedOptions[CURLOPT_POST] );
	}

	public function testPingQueryEndpointHttp500IsAlive() {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'setOption' )
			->willReturn( true );

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( '' );

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 22 );

		$httpRequest->expects( $this->once() )
			->method( 'getLastTransferInfo' )
			->with( CURLINFO_HTTP_CODE )
			->willReturn( 500 );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost/query'
			),
			$httpRequest
		);

		$this->assertTrue( $instance->ping( GenericRepositoryConnector::ENDP_QUERY ) );
	}

	public function testPingQueryEndpointHttp400IsAlive() {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'setOption' )
			->willReturn( true );

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( '' );

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 22 );

		$httpRequest->expects( $this->once() )
			->method( 'getLastTransferInfo' )
			->with( CURLINFO_HTTP_CODE )
			->willReturn( 400 );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost/query'
			),
			$httpRequest
		);

		$this->assertTrue( $instance->ping( GenericRepositoryConnector::ENDP_QUERY ) );
	}

	public function testPingQueryEndpointOtherHttpCodeIsNotAlive() {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'setOption' )
			->willReturn( true );

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( '' );

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 22 );

		$httpRequest->expects( $this->once() )
			->method( 'getLastTransferInfo' )
			->with( CURLINFO_HTTP_CODE )
			->willReturn( 403 );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost/query'
			),
			$httpRequest
		);

		$this->assertFalse( $instance->ping( GenericRepositoryConnector::ENDP_QUERY ) );
	}

	public function testPingUpdateEndpointSuccess() {
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

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( '' );

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 0 );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost/query',
				'http://localhost/update'
			),
			$httpRequest
		);

		$this->assertTrue( $instance->ping( GenericRepositoryConnector::ENDP_UPDATE ) );
		$this->assertEquals( 'http://localhost/update', $capturedOptions[CURLOPT_URL] );
		$this->assertFalse( $capturedOptions[CURLOPT_NOBODY] );
	}

	public function testPingUpdateEndpointEmptyReturnsFalse() {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'setOption' )
			->willReturn( true );

		$httpRequest->expects( $this->never() )
			->method( 'execute' );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost/query',
				''
			),
			$httpRequest
		);

		$this->assertFalse( $instance->ping( GenericRepositoryConnector::ENDP_UPDATE ) );
	}

	public function testPingDataEndpointEmptyReturnsFalse() {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'setOption' )
			->willReturn( true );

		$httpRequest->expects( $this->never() )
			->method( 'execute' );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost/query',
				'',
				''
			),
			$httpRequest
		);

		$this->assertFalse( $instance->ping( GenericRepositoryConnector::ENDP_DATA ) );
	}

	public function testPingDataEndpointDelegatesToDoHttpPost() {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'setOption' )
			->willReturn( true );

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( '' );

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 0 );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost/query',
				'',
				'http://localhost/data'
			),
			$httpRequest
		);

		$this->assertTrue( $instance->ping( GenericRepositoryConnector::ENDP_DATA ) );
	}

	public function testDoQuerySuccessSetsCorrectOptionsAndParsesResponse() {
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

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( $rawResultProvider->getEmptySparqlResultXml() );

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 0 );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost:9999/query'
			),
			$httpRequest
		);

		$sparql = 'SELECT ?s WHERE { ?s ?p ?o }';
		$result = $instance->doQuery( $sparql );

		$this->assertInstanceOf( RepositoryResult::class, $result );
		$this->assertSame( 'http://localhost:9999/query', $capturedOptions[CURLOPT_URL] );
		$this->assertTrue( $capturedOptions[CURLOPT_POST] );
		$this->assertSame(
			[
				'Accept: application/sparql-results+xml,application/xml;q=0.8',
				'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'
			],
			$capturedOptions[CURLOPT_HTTPHEADER]
		);

		$expectedPostFields = 'query=' . urlencode( $sparql ) .
			'&default-graph-uri=' . urlencode( 'http://foo/myDefaultGraph' );
		$this->assertSame( $expectedPostFields, $capturedOptions[CURLOPT_POSTFIELDS] );
	}

	public function testDoQuerySuccessWithoutDefaultGraph() {
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

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( $rawResultProvider->getEmptySparqlResultXml() );

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 0 );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient( '', 'http://localhost:9999/query' ),
			$httpRequest
		);

		$sparql = 'ASK { ?s ?p ?o }';
		$instance->doQuery( $sparql );

		$this->assertSame( 'query=' . urlencode( $sparql ), $capturedOptions[CURLOPT_POSTFIELDS] );
	}

	public function testDoQueryErrorReturnsUnreachableResult() {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'setOption' )
			->willReturn( true );

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( '' );

		$httpRequest->method( 'getLastErrorCode' )
			->willReturn( CURLE_COULDNT_CONNECT );

		$httpRequest->method( 'getLastError' )
			->willReturn( 'Connection refused' );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient( '', 'http://localhost:9999/query' ),
			$httpRequest
		);

		$result = $instance->doQuery( 'SELECT ?s WHERE { ?s ?p ?o }' );

		$this->assertInstanceOf( RepositoryResult::class, $result );
		$this->assertSame( RepositoryResult::ERROR_UNREACHABLE, $result->getErrorCode() );
	}

	public function testDoUpdateSuccessSetsCorrectOptions() {
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

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( '' );

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 0 );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost/query',
				'http://localhost/update'
			),
			$httpRequest
		);

		$sparql = 'DELETE { ?s ?p ?o } WHERE { ?s ?p ?o }';
		$this->assertTrue( $instance->doUpdate( $sparql ) );

		$this->assertSame( 'http://localhost/update', $capturedOptions[CURLOPT_URL] );
		$this->assertTrue( $capturedOptions[CURLOPT_POST] );
		$this->assertSame( 'update=' . urlencode( $sparql ), $capturedOptions[CURLOPT_POSTFIELDS] );
		$this->assertSame(
			[ 'Content-Type: application/x-www-form-urlencoded;charset=UTF-8' ],
			$capturedOptions[CURLOPT_HTTPHEADER]
		);
	}

	public function testDoUpdateErrorReturnsFalse() {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'setOption' )
			->willReturn( true );

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( '' );

		$httpRequest->method( 'getLastErrorCode' )
			->willReturn( CURLE_COULDNT_CONNECT );

		$httpRequest->method( 'getLastError' )
			->willReturn( 'Connection refused' );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient( '', 'http://localhost/query', 'http://localhost/update' ),
			$httpRequest
		);

		$this->assertFalse( $instance->doUpdate( 'INSERT DATA { <s> <p> <o> }' ) );
	}

	public function testDoHttpPostSuccessSetsCorrectOptions() {
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

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( '' );

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 0 );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost/query',
				'http://localhost/update',
				'http://localhost/data'
			),
			$httpRequest
		);

		$payload = '<http://example.org/s> <http://example.org/p> "object" .';
		$this->assertTrue( $instance->doHttpPost( $payload ) );

		$expectedUrl = 'http://localhost/data?graph=' . urlencode( 'http://foo/myDefaultGraph' );
		$this->assertSame( $expectedUrl, $capturedOptions[CURLOPT_URL] );
		$this->assertTrue( $capturedOptions[CURLOPT_POST] );
		$this->assertSame( [ 'Content-Type: application/x-turtle' ], $capturedOptions[CURLOPT_HTTPHEADER] );
		$this->assertIsResource( $capturedOptions[CURLOPT_INFILE] );
		$this->assertSame( strlen( $payload ), $capturedOptions[CURLOPT_INFILESIZE] );
	}

	public function testDoHttpPostWithoutDefaultGraphUsesDefaultParam() {
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

		$instance = new GenericRepositoryConnector(
			new RepositoryClient( '', 'http://localhost/query', '', 'http://localhost/data' ),
			$httpRequest
		);

		$instance->doHttpPost( 'some turtle data' );

		$this->assertSame( 'http://localhost/data?default', $capturedOptions[CURLOPT_URL] );
	}

	public function testDoHttpPostErrorReturnsFalse() {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->any() )
			->method( 'setOption' )
			->willReturn( true );

		$httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( '' );

		$httpRequest->method( 'getLastErrorCode' )
			->willReturn( CURLE_COULDNT_CONNECT );

		$httpRequest->method( 'getLastError' )
			->willReturn( 'Connection refused' );

		$instance = new GenericRepositoryConnector(
			new RepositoryClient( '', 'http://localhost/query', '', 'http://localhost/data' ),
			$httpRequest
		);

		$this->assertFalse( $instance->doHttpPost( 'data' ) );
	}

	public function testInsertDataRoutesToDoHttpPostWhenDataEndpointSet() {
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

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost/query',
				'http://localhost/update',
				'http://localhost/data'
			),
			$httpRequest
		);

		$this->assertTrue( $instance->insertData( 'property:Foo wiki:Bar;' ) );
		$this->assertStringStartsWith( 'http://localhost/data', $capturedOptions[CURLOPT_URL] );
	}

	public function testInsertDataRoutesToDoUpdateWhenNoDataEndpoint() {
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

		$instance = new GenericRepositoryConnector(
			new RepositoryClient(
				'http://foo/myDefaultGraph',
				'http://localhost/query',
				'http://localhost/update',
				''
			),
			$httpRequest
		);

		$this->assertTrue( $instance->insertData( '<s> <p> <o> .' ) );
		$this->assertSame( 'http://localhost/update', $capturedOptions[CURLOPT_URL] );
		$this->assertStringContainsString( 'INSERT+DATA', $capturedOptions[CURLOPT_POSTFIELDS] );
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
