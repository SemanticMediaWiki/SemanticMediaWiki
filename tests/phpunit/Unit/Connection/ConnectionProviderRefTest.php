<?php

namespace SMW\Tests\Connection;

use SMW\Connection\ConnectionProviderRef;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Connection\ConnectionProviderRef
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConnectionProviderRefTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConnectionProviderRef::class,
			new ConnectionProviderRef( [] )
		);
	}

	public function testGetAndReleaseConnection() {

		$connectionProvider = $this->getMockBuilder( '\SMW\Connection\ConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( 'Bar' ) );

		$connectionProvider->expects( $this->once() )
			->method( 'releaseConnection' );

		$instance = new ConnectionProviderRef(
			[
				'Foo' => $connectionProvider
			]
		);

		$this->assertEquals(
			'Bar',
			$instance->getConnection( 'Foo' )
		);

		$instance->releaseConnection();
	}

	public function testNoMatchableConnectionProviderThrowsException() {

		$instance = new ConnectionProviderRef(
			[
				'Foo' => 'Bar'
			]
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection( 'Foo' );
	}

	public function testNoMatchableKeyThrowsException() {

		$instance = new ConnectionProviderRef( [] );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection( 'Foo' );
	}

}
