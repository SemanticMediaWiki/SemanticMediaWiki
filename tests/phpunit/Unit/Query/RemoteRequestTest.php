<?php

namespace SMW\Tests\Query;

use SMW\Query\RemoteRequest;
use SMW\Tests\PHPUnitCompat;

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

	use PHPUnitCompat;

	private $httpRequest;
	private $query;

	protected function setUp() : void {

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

		$output = 'Foobar <!--COUNT:42--><!--FURTHERRESULTS:1-->' . RemoteRequest::REQUEST_ID;

		$this->httpRequest->expects( $this->once() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$this->httpRequest->expects( $this->once() )
			->method( 'execute' )
			->will( $this->returnValue( $output ) );

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
		$res = $instance->getQueryResult( $this->query );

		$this->assertInstanceOf(
			'\SMW\Query\Result\StringResult',
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
