<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\HttpResponseErrorMapper;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SPARQLStore\HttpResponseErrorMapper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class HttpResponseErrorMapperTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
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

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->will( $this->returnValue( $curlErrorCode ) );

		$instance = new HttpResponseErrorMapper( $httpRequest );
		$instance->mapErrorResponse( 'Foo', 'Bar' );
	}

	public function testResponseToHttpRequestForInvalidErrorCodeThrowsException() {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->will( $this->returnValue( 99999 ) );

		$instance = new HttpResponseErrorMapper( $httpRequest );

		$this->setExpectedException( '\SMW\SPARQLStore\Exception\HttpEndpointConnectionException' );
		$instance->mapErrorResponse( 'Foo', 'Bar' );
	}

	/**
	 * @dataProvider httpCodeThatThrowsExceptionProvider
	 */
	public function testResponseToHttpRequesForHttpErrorThatThrowsException( $httpErrorCode ) {

		// PHP doesn't know CURLE_HTTP_RETURNED_ERROR therefore using 22
		// http://curl.haxx.se/libcurl/c/libcurl-errors.html

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->will( $this->returnValue( 22 ) );

		$httpRequest->expects( $this->once() )
			->method( 'getLastTransferInfo' )
			->with( $this->equalTo( CURLINFO_HTTP_CODE ) )
			->will( $this->returnValue( $httpErrorCode ) );

		$instance = new HttpResponseErrorMapper( $httpRequest );

		$this->setExpectedException( '\SMW\SPARQLStore\Exception\BadHttpEndpointResponseException' );
		$instance->mapErrorResponse( 'Foo', 'Bar' );
	}

	public function testResponseToHttpRequesForHttpErrorThatNotThrowsException() {

		$httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
			->disableOriginalConstructor()
			->getMock();

		$httpRequest->expects( $this->once() )
			->method( 'getLastErrorCode' )
			->will( $this->returnValue( 22 ) );

		$httpRequest->expects( $this->once() )
			->method( 'getLastTransferInfo' )
			->with( $this->equalTo( CURLINFO_HTTP_CODE ) )
			->will( $this->returnValue( 404 ) );

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
