<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\DBConnectionProvider;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\DBConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class DBConnectionProviderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DBConnectionProvider::class,
			new DBConnectionProvider()
		);
	}

	public function testGetConnection() {

		$instance = new DBConnectionProvider();
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

		$instance = new DBConnectionProvider(
			'foo'
		);

		$instance->setLogger(
			TestEnvironment::newSpyLogger()
		);

		$conf = array(
			'foo' => array(
				'read' => 'Bar',
				'write' => 'Bar'
			)
		);

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

		$instance = new DBConnectionProvider(
			'foo'
		);

		$conf = array(
			'foo' => array(
				'callback'  => function() use( $db ) {
					return $db;
				}
			)
		);

		$instance->setLocalConnectionConf( $conf );

		$connection = $instance->getConnection();

		$this->assertSame(
			$db,
			$instance->getConnection()
		);

		$instance->releaseConnection();
	}

	public function testGetConnectionOnIncompleteConfThrowsException() {

		$instance = new DBConnectionProvider(
			'foo'
		);

		$conf = array(
			'foo' => array(
				'read' => 'Foo'
			)
		);

		$instance->setLocalConnectionConf( $conf );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection();
	}

}
