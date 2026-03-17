<?php

namespace SMW\Tests\Query;

use Onoi\HttpRequest\HttpRequest;
use PHPUnit\Framework\TestCase;
use SMW\Query\RemoteRequest;
use SMW\Query\Result\StringResult;

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

	private $httpRequest;
	private $query;

	protected function setUp(): void {
		$this->httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$this->query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RemoteRequest::class,
			new RemoteRequest( [], $this->httpRequest )
		);
	}

	public function testGetQueryResult_CannotConnect() {
		$this->httpRequest->expects( $this->once() )
			->method( 'ping' )
			->willReturn( false );

		$parameters = [
			'url' => 'http://example.org/Foo'
		];

		$instance = new RemoteRequest(
			$parameters,
			$this->httpRequest
		);

		$this->assertStringContainsString(
			'smw-remote-source-unavailable',
			$instance->getQueryResult( $this->query )
		);
	}

	public function testGetQueryResultSetsCorrectCurlOptionsAndPostParams() {
		$output = 'Result' . RemoteRequest::REQUEST_ID;
		$capturedOptions = [];

		$this->httpRequest->expects( $this->once() )
			->method( 'ping' )
			->willReturn( true );

		$this->httpRequest->expects( $this->atLeastOnce() )
			->method( 'setOption' )
			->willReturnCallback( static function ( $option, $value ) use ( &$capturedOptions ) {
				$capturedOptions[$option] = $value;
				return true;
			} );

		$this->httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( $output );

		$this->httpRequest->method( 'getLastError' )
			->willReturn( '' );

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

		$instance = new RemoteRequest( $parameters, $this->httpRequest );
		$instance->clear();
		$instance->getQueryResult( $this->query );

		$this->assertFalse( $capturedOptions[CURLOPT_SSL_VERIFYPEER] );
		$this->assertTrue( $capturedOptions[CURLOPT_POST] );
		$this->assertSame( 1, $capturedOptions[CURLOPT_RETURNTRANSFER] );

		$postFields = $capturedOptions[CURLOPT_POSTFIELDS];
		$this->assertStringContainsString( 'q=' . urlencode( '[[Category:Foo]]' ), $postFields );
		$this->assertStringContainsString( 'po=' . urlencode( '?Bar|?Baz' ), $postFields );
	}

	public function testCanConnectCachesPingResult() {
		$output = 'Result' . RemoteRequest::REQUEST_ID;

		$this->httpRequest->expects( $this->once() )
			->method( 'ping' )
			->willReturn( true );

		$this->httpRequest->method( 'setOption' )->willReturn( true );

		$this->httpRequest->method( 'execute' )
			->willReturn( $output );

		$this->httpRequest->method( 'getLastError' )
			->willReturn( '' );

		$this->query->method( 'toArray' )->willReturn( [] );
		$this->query->method( 'getOption' )->willReturn( [] );
		$this->query->method( 'isEmbedded' )->willReturn( false );
		$this->query->method( 'getQueryMode' )->willReturn( 0 );

		$parameters = [ 'url' => 'http://example.org/wiki' ];

		$instance = new RemoteRequest( $parameters, $this->httpRequest );
		$instance->clear();

		// First call pings, second call uses cached result
		$instance->getQueryResult( $this->query );
		$instance->getQueryResult( $this->query );
	}

	public function testGetQueryResultWithMissingRequestId() {
		$this->httpRequest->expects( $this->once() )
			->method( 'ping' )
			->willReturn( true );

		$this->httpRequest->method( 'setOption' )->willReturn( true );

		$this->httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( 'Some random output without the magic ID' );

		$this->httpRequest->method( 'getLastError' )
			->willReturn( '' );

		$this->query->method( 'toArray' )->willReturn( [] );
		$this->query->method( 'getOption' )->willReturn( [] );
		$this->query->method( 'isEmbedded' )->willReturn( false );
		$this->query->method( 'getQueryMode' )->willReturn( 0 );
		$this->query->method( 'getQuerySource' )->willReturn( 'remote-wiki' );

		$parameters = [ 'url' => 'http://example.org/wiki' ];

		$instance = new RemoteRequest( $parameters, $this->httpRequest );
		$instance->clear();

		$result = $instance->getQueryResult( $this->query );

		$this->assertInstanceOf( StringResult::class, $result );
		$this->assertStringContainsString( 'smw-remote-source-unmatched-id', $result->getResults() );
	}

	public function testGetQueryResultWithFetchError() {
		$this->httpRequest->expects( $this->once() )
			->method( 'ping' )
			->willReturn( true );

		$this->httpRequest->method( 'setOption' )->willReturn( true );

		$this->httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( '' );

		$this->httpRequest->method( 'getLastError' )
			->willReturn( 'Connection timed out' );

		$this->query->method( 'toArray' )->willReturn( [] );
		$this->query->method( 'getOption' )->willReturn( [] );
		$this->query->method( 'isEmbedded' )->willReturn( false );
		$this->query->method( 'getQueryMode' )->willReturn( 0 );
		$this->query->method( 'getQuerySource' )->willReturn( 'remote-wiki' );

		$parameters = [ 'url' => 'http://example.org/wiki' ];

		$instance = new RemoteRequest( $parameters, $this->httpRequest );
		$instance->clear();

		$result = $instance->getQueryResult( $this->query );

		$this->assertInstanceOf( StringResult::class, $result );
		$this->assertStringContainsString( 'smw-remote-source-unmatched-id', $result->getResults() );
	}

	public function testGetQueryResult_Connect() {
		$output = 'Foobar <!--COUNT:42--><!--FURTHERRESULTS:1-->' . RemoteRequest::REQUEST_ID;

		$this->httpRequest->expects( $this->once() )
			->method( 'ping' )
			->willReturn( true );

		$this->httpRequest->expects( $this->once() )
			->method( 'execute' )
			->willReturn( $output );

		$this->httpRequest->expects( $this->once() )
			->method( 'getLastError' )
			->willReturn( '' );

		$parameters = [
			'url' => 'http://example.org/Foo'
		];

		$instance = new RemoteRequest(
			$parameters,
			$this->httpRequest
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

}
