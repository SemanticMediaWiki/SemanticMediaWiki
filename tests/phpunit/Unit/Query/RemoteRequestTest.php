<?php

namespace SMW\Tests\Query;

use SMW\Query\RemoteRequest;

/**
 * @covers \SMW\Query\RemoteRequest
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RemoteRequestTest extends \PHPUnit_Framework_TestCase {

	private $httpRequest;
	private $query;

	protected function setUp() {

		$this->httpRequest = $this->getMockBuilder( '\Onoi\HttpRequest\HttpRequest' )
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
			->will( $this->returnValue( false ) );

		$parameters = [
			'url' => 'http://example.org/Foo'
		];

		$instance = new RemoteRequest(
			$parameters,
			$this->httpRequest
		);

		$this->assertContains(
			'smw-remote-source-unavailable',
			$instance->getQueryResult( $this->query )
		);
	}

	public function testGetQueryResult_Connect() {

		$this->httpRequest->expects( $this->once() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$this->httpRequest->expects( $this->once() )
			->method( 'execute' )
			->will( $this->returnValue( 'Foobar' ) );

		$this->httpRequest->expects( $this->once() )
			->method( 'getLastError' )
			->will( $this->returnValue( '' ) );

		$parameters = [
			'url' => 'http://example.org/Foo'
		];

		$instance = new RemoteRequest(
			$parameters,
			$this->httpRequest
		);

		$instance->clear();

		$this->assertInstanceOf(
			'\SMW\Query\Result\StringResult',
			$instance->getQueryResult( $this->query )
		);
	}

}
