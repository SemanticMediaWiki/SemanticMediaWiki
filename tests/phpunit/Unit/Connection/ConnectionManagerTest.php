<?php

namespace SMW\Tests\Unit\Connection;

use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\Connection\ConnectionProvider;
use SMW\MediaWiki\Connection\Database;

/**
 * @covers \SMW\Connection\ConnectionManager
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class ConnectionManagerTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConnectionManager::class,
			new ConnectionManager()
		);
	}

	public function testDefaultRegisteredConnectionProvided() {
		$instance = new ConnectionManager();
		$instance->releaseConnections();

		$connection = $instance->getConnection( 'mw.db' );

		$this->assertSame(
			$connection,
			$instance->getConnection( 'mw.db' )
		);

		$this->assertInstanceOf(
			Database::class,
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

		$this->expectException( 'RuntimeException' );
		$instance->getConnection( 'mw.master' );
	}

	public function testRegisterConnectionProvider() {
		$connectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->once() )
			->method( 'getConnection' );

		$instance = new ConnectionManager();
		$instance->registerConnectionProvider( 'foo', $connectionProvider );

		$instance->getConnection( 'FOO' );
	}

	public function testRegisterCallbackConnection() {
		$connectionProvider = $this->getMockBuilder( ConnectionProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionProvider->expects( $this->once() )
			->method( 'getConnection' );

		$callback = static function () use( $connectionProvider ) {
			return $connectionProvider->getConnection();
		};

		$instance = new ConnectionManager();
		$instance->registerCallbackConnection( 'foo', $callback );

		$instance->getConnection( 'FOO' );
	}

	public function testRegisterCallbackConnectionMemoizesAcrossCalls() {
		$invocations = 0;
		$callback = static function () use ( &$invocations ) {
			$invocations++;
			return new \stdClass();
		};

		$instance = new ConnectionManager();
		$instance->registerCallbackConnection( 'memo', $callback );

		$first = $instance->getConnection( 'memo' );

		$this->assertSame( $first, $instance->getConnection( 'memo' ) );
		$this->assertSame( 1, $invocations );

		$instance->releaseConnections();

		$this->assertNotSame( $first, $instance->getConnection( 'memo' ) );
		$this->assertSame( 2, $invocations );
	}

}
