<?php

namespace SMW\Tests\Connection;

use SMW\Connection\ConnRef;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Connection\ConnRef
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConnRefTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConnRef::class,
			new ConnRef( [] )
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

		$instance = new ConnRef(
			[
				'Foo' => $connectionProvider
			]
		);

		$this->assertEquals(
			'Bar',
			$instance->getConnection( 'Foo' )
		);

		$instance->releaseConnections();
	}

	public function testNoMatchableConnectionProviderThrowsException() {

		$instance = new ConnRef(
			[
				'Foo' => 'Bar'
			]
		);

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection( 'Foo' );
	}

	public function testNoMatchableKeyThrowsException() {

		$instance = new ConnRef( [] );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection( 'Foo' );
	}

}
