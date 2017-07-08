<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\DatabaseConnectionProvider;

/**
 * @covers \SMW\MediaWiki\DatabaseConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class DatabaseConnectionProviderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\MediaWiki\DatabaseConnectionProvider',
			new DatabaseConnectionProvider()
		);
	}

	public function testGetConnection() {

		$instance = new DatabaseConnectionProvider();

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

		$instance = new DatabaseConnectionProvider(
			'foo'
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

		$instance = new DatabaseConnectionProvider(
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

		$instance = new DatabaseConnectionProvider(
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
