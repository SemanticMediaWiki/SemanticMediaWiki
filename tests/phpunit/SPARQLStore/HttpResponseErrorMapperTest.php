<?php

namespace SMW\Tests\SPARQLStore;

use Onoi\HttpRequest\HttpRequest;
use PHPUnit\Framework\TestCase;
use SMW\SPARQLStore\Exception\BadHttpEndpointResponseException;
use SMW\SPARQLStore\Exception\HttpEndpointConnectionException;
use SMW\SPARQLStore\HttpResponseErrorMapper;

/**
 * @covers \SMW\SPARQLStore\HttpResponseErrorMapper
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class HttpResponseErrorMapperTest extends TestCase {

	public function testCanConstruct() {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			HttpResponseErrorMapper::class,
			new HttpResponseErrorMapper( $httpRequest )
		);
	}

	/**
	 * @dataProvider curlErrorCodeThatNotThrowsExceptionProvider
	 */
	public function testResponseToHttpRequestThatNotThrowsException( $curlErrorCode ) {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( $curlErrorCode );

		$instance = new HttpResponseErrorMapper( $httpRequest );
		$instance->mapErrorResponse( 'Foo', 'Bar' );
	}

	public function testResponseToHttpRequestForInvalidErrorCodeThrowsException() {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 99999 );

		$instance = new HttpResponseErrorMapper( $httpRequest );

		$this->expectException( HttpEndpointConnectionException::class );
		$instance->mapErrorResponse( 'Foo', 'Bar' );
	}

	/**
	 * @dataProvider httpCodeThatThrowsExceptionProvider
	 */
	public function testResponseToHttpRequesForHttpErrorThatThrowsException( $httpErrorCode ) {
		// PHP doesn't know CURLE_HTTP_RETURNED_ERROR therefore using 22
		// http://curl.haxx.se/libcurl/c/libcurl-errors.html

		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 22 );

		$httpRequest->expects( $this->once() )
			->method( 'getLastTransferInfo' )
			->with( CURLINFO_HTTP_CODE )
			->willReturn( $httpErrorCode );

		$instance = new HttpResponseErrorMapper( $httpRequest );

		$this->expectException( BadHttpEndpointResponseException::class );
		$instance->mapErrorResponse( 'Foo', 'Bar' );
	}

	public function testResponseToHttpRequesForHttpErrorThatNotThrowsException() {
		$httpRequest = $this->getMockBuilder( HttpRequest::class )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->willReturn( 22 );

		$httpRequest->expects( $this->once() )
			->method( 'getLastTransferInfo' )
			->with( CURLINFO_HTTP_CODE )
			->willReturn( 404 );

		$instance = new HttpResponseErrorMapper( $httpRequest );
		$instance->mapErrorResponse( 'Foo', 'Bar' );
	}

	public function curlErrorCodeThatNotThrowsExceptionProvider() {
		$provider = [
			[ CURLE_GOT_NOTHING ],
			[ CURLE_COULDNT_CONNECT ]
		];

		return $provider;
	}

	public function httpCodeThatThrowsExceptionProvider() {
		$provider = [
			[ 400 ],
			[ 500 ]
		];

		return $provider;
	}
}
