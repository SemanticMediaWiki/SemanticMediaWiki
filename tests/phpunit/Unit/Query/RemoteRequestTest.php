<?php

namespace SMW\Tests\Unit\Query;

use MediaWiki\Http\HttpRequestFactory;
use MWHttpRequest;
use PHPUnit\Framework\TestCase;
use SMW\Query\Query;
use SMW\Query\RemoteRequest;
use SMW\Query\Result\StringResult;
use StatusValue;
use Wikimedia\ObjectCache\WANObjectCache;

/**
 * @covers \SMW\Query\RemoteRequest
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class RemoteRequestTest extends TestCase {

	private $httpRequestFactory;
	private $query;
	private $cache;

	protected function setUp(): void {
		$this->httpRequestFactory = $this->createMock( HttpRequestFactory::class );
		$this->cache = $this->createMock( WANObjectCache::class );

		$this->query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RemoteRequest::class,
			new RemoteRequest( [], $this->httpRequestFactory, $this->cache )
		);
	}

	public function testGetQueryResult_CannotConnect() {
		$mockRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$mockRequest->method( 'execute' )
			->willReturn( StatusValue::newFatal( 'http-request-error' ) );

		$this->httpRequestFactory->expects( $this->once() )
			->method( 'create' )
			->willReturn( $mockRequest );

		$parameters = [
			'url' => 'http://example.org/Foo'
		];

		$instance = new RemoteRequest(
			$parameters,
			$this->httpRequestFactory,
			$this->cache
		);

		$this->assertStringContainsString(
			'smw-remote-source-unavailable',
			$instance->getQueryResult( $this->query )
		);
	}

	public function testGetQueryResult_Connect() {
		$output = 'Foobar <!--COUNT:42--><!--FURTHERRESULTS:1-->' . RemoteRequest::REQUEST_ID;

		$pingRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$pingRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

		$fetchRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$fetchRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

		$fetchRequest->method( 'getContent' )
			->willReturn( $output );

		$this->httpRequestFactory->expects( $this->exactly( 2 ) )
			->method( 'create' )
			->willReturnOnConsecutiveCalls( $pingRequest, $fetchRequest );

		$parameters = [
			'url' => 'http://example.org/Foo'
		];

		$instance = new RemoteRequest(
			$parameters,
			$this->httpRequestFactory,
			$this->cache
		);

		$instance->clear();
		$res = $instance->getQueryResult( $this->query );

		$this->assertInstanceOf(
			StringResult::class,
			$res
		);

		$this->assertSame(
			42,
			$res->getCount()
		);

		$this->assertTrue(
			$res->hasFurtherResults()
		);
	}

	public function testGetQueryResultSetsCorrectPostParams() {
		$output = 'Result' . RemoteRequest::REQUEST_ID;

		$pingRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$pingRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

		$capturedOptions = [];

		$fetchRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$fetchRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

		$fetchRequest->method( 'getContent' )
			->willReturn( $output );

		$this->httpRequestFactory->expects( $this->exactly( 2 ) )
			->method( 'create' )
			->willReturnCallback( static function ( $url, $options ) use ( &$capturedOptions, $pingRequest, $fetchRequest ) {
				if ( $options['method'] === 'HEAD' ) {
					return $pingRequest;
				}
				$capturedOptions = $options;
				return $fetchRequest;
			} );

		$this->query->method( 'toArray' )
			->willReturn( [
				'conditions' => '[[Category:Foo]]',
				'printouts' => [ '?Bar', '?Baz' ],
				'parameters' => [ 'limit' => '10' ]
			] );

		$this->query->method( 'getOption' )
			->with( 'query.params' )
			->willReturn( [ 'format' => 'table' ] );

		$this->query->method( 'isEmbedded' )
			->willReturn( false );

		$this->query->method( 'getQueryMode' )
			->willReturn( 0 );

		$parameters = [
			'url' => 'http://example.org/wiki',
			'smwgRemoteReqFeatures' => 0
		];

		$instance = new RemoteRequest( $parameters, $this->httpRequestFactory, $this->cache );
		$instance->clear();
		$instance->getQueryResult( $this->query );

		$this->assertSame( 'POST', $capturedOptions['method'] );
		$this->assertFalse( $capturedOptions['sslVerifyCert'] );
		$this->assertStringContainsString( 'q=' . urlencode( '[[Category:Foo]]' ), $capturedOptions['postData'] );
		$this->assertStringContainsString( 'po=' . urlencode( '?Bar|?Baz' ), $capturedOptions['postData'] );
	}

	public function testCanConnectCachesPingResult() {
		$output = 'Result' . RemoteRequest::REQUEST_ID;

		$pingRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$pingRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

		$fetchRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$fetchRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

		$fetchRequest->method( 'getContent' )
			->willReturn( $output );

		// ping only creates one HEAD request, subsequent calls reuse cached result
		$this->httpRequestFactory->method( 'create' )
			->willReturnCallback( static function ( $url, $options ) use ( $pingRequest, $fetchRequest ) {
				if ( isset( $options['method'] ) && $options['method'] === 'HEAD' ) {
					return $pingRequest;
				}
				return $fetchRequest;
			} );

		$this->query->method( 'toArray' )->willReturn( [] );
		$this->query->method( 'getOption' )->willReturn( [] );
		$this->query->method( 'isEmbedded' )->willReturn( false );
		$this->query->method( 'getQueryMode' )->willReturn( 0 );

		$parameters = [ 'url' => 'http://example.org/wiki' ];

		$instance = new RemoteRequest( $parameters, $this->httpRequestFactory, $this->cache );
		$instance->clear();

		// First call pings, second call uses cached result
		$res1 = $instance->getQueryResult( $this->query );
		$res2 = $instance->getQueryResult( $this->query );

		$this->assertInstanceOf( StringResult::class, $res1 );
		$this->assertInstanceOf( StringResult::class, $res2 );
	}

	public function testGetQueryResultWithFetchHttpFailure() {
		$pingRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$pingRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

		$fetchRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$fetchRequest->method( 'execute' )
			->willReturn( StatusValue::newFatal( 'http-request-error' ) );

		$fetchRequest->method( 'getContent' )
			->willReturn( '' );

		$this->httpRequestFactory->method( 'create' )
			->willReturnCallback( static function ( $url, $options ) use ( $pingRequest, $fetchRequest ) {
				if ( $options['method'] === 'HEAD' ) {
					return $pingRequest;
				}
				return $fetchRequest;
			} );

		$this->query->method( 'toArray' )->willReturn( [] );
		$this->query->method( 'getOption' )->willReturn( [] );
		$this->query->method( 'isEmbedded' )->willReturn( false );
		$this->query->method( 'getQueryMode' )->willReturn( 0 );
		$this->query->method( 'getQuerySource' )->willReturn( 'remote-wiki' );

		$parameters = [ 'url' => 'http://example.org/wiki' ];

		$instance = new RemoteRequest( $parameters, $this->httpRequestFactory, $this->cache );
		$instance->clear();

		$result = $instance->getQueryResult( $this->query );

		$this->assertInstanceOf( StringResult::class, $result );
		$this->assertStringContainsString( 'smw-remote-source-unmatched-id', $result->getFormattedResult() );
	}

	public function testGetQueryResultWithMissingRequestId() {
		$pingRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$pingRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

		$fetchRequest = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$fetchRequest->method( 'execute' )
			->willReturn( StatusValue::newGood() );

		$fetchRequest->method( 'getContent' )
			->willReturn( 'Some random output without the magic ID' );

		$this->httpRequestFactory->method( 'create' )
			->willReturnCallback( static function ( $url, $options ) use ( $pingRequest, $fetchRequest ) {
				if ( isset( $options['method'] ) && $options['method'] === 'HEAD' ) {
					return $pingRequest;
				}
				return $fetchRequest;
			} );

		$this->query->method( 'toArray' )->willReturn( [] );
		$this->query->method( 'getOption' )->willReturn( [] );
		$this->query->method( 'isEmbedded' )->willReturn( false );
		$this->query->method( 'getQueryMode' )->willReturn( 0 );
		$this->query->method( 'getQuerySource' )->willReturn( 'remote-wiki' );

		$parameters = [ 'url' => 'http://example.org/wiki' ];

		$instance = new RemoteRequest( $parameters, $this->httpRequestFactory, $this->cache );
		$instance->clear();

		$result = $instance->getQueryResult( $this->query );

		$this->assertInstanceOf( StringResult::class, $result );
		$this->assertStringContainsString( 'smw-remote-source-unmatched-id', $result->getFormattedResult() );
	}

}
