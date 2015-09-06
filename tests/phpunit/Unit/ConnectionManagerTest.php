<?php

namespace SMW\Tests;

use SMW\ConnectionManager;

/**
 * @covers \SMW\ConnectionManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConnectionManagerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\ConnectionManager',
			new ConnectionManager()
		);
	}

	public function testMwDBSQLConnectionProvidedBySetupRegistration() {

		$instance = new ConnectionManager();
		$instance->releaseConnections();

		$connection = $instance->getConnection( 'mw.db' );

		$this->assertSame(
			$connection,
			$instance->getConnection( 'mw.db' )
		);

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Database',
			$connection
		);

		$instance->releaseConnections();

		$this->assertNotSame(
			$connection,
			$instance->getConnection( 'mw.db' )
		);
	}

	public function testUnregisteredConnectionTypeThrowsException() {

		$instance = new ConnectionManager();

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection( 'mw.master' );
	}

	public function testRegisterConnectionProvider() {

		$connectionProvider = $this->getMockBuilder( '\SMW\DBConnectionProvider' )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->once() )
			->method( 'getConnection' );

		$instance = new ConnectionManager();
		$instance->registerConnectionProvider( 'foo', $connectionProvider );

		$instance->getConnection( 'FOO' );
	}

}
