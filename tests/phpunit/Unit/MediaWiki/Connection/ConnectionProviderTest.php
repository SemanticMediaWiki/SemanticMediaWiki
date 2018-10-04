<?php

namespace SMW\Tests\MediaWiki\Connection;

use SMW\MediaWiki\Connection\ConnectionProvider;
use SMW\Tests\TestEnvironment;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\Connection\ConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ConnectionProviderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConnectionProvider::class,
			new ConnectionProvider()
		);
	}

	public function testGetConnection() {

		$instance = new ConnectionProvider();
		$instance->setLogger(
			TestEnvironment::newSpyLogger()
		);

		$connection = $instance->getConnection();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Database',
			$connection
		);

		$this->assertSame(
			$connection,
			$instance->getConnection()
		);

		$instance->releaseConnection();

		$this->assertNotSame(
			$connection,
			$instance->getConnection()
		);
	}

	public function testGetConnectionOnFixedConfWithSameIndex() {

		$instance = new ConnectionProvider(
			'foo'
		);

		$instance->setLogger(
			TestEnvironment::newSpyLogger()
		);

		$conf = [
			'foo' => [
				'read' => 'Bar',
				'write' => 'Bar'
			]
		];

		$instance->setLocalConnectionConf( $conf );

		$connection = $instance->getConnection();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Database',
			$connection
		);

		$this->assertSame(
			$connection,
			$instance->getConnection()
		);

		$instance->releaseConnection();

		$this->assertNotSame(
			$connection,
			$instance->getConnection()
		);
	}

	public function testGetConnectionOnCallback() {

		$db = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ConnectionProvider(
			'foo'
		);

		$conf = [
			'foo' => [
				'callback'  => function() use( $db ) {
					return $db;
				}
			]
		];

		$instance->setLocalConnectionConf( $conf );

		$connection = $instance->getConnection();

		$this->assertSame(
			$db,
			$instance->getConnection()
		);

		$instance->releaseConnection();
	}

	public function testGetConnectionOnIncompleteConfThrowsException() {

		$instance = new ConnectionProvider(
			'foo'
		);

		$conf = [
			'foo' => [
				'read' => 'Foo'
			]
		];

		$instance->setLocalConnectionConf( $conf );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection();
	}

}
