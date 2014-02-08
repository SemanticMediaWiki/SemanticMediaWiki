<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\DirectDBConnectionProvider;

/**
 * @covers \SMW\MediaWiki\DirectDBConnectionProvider
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class DirectDBConnectionProviderTest extends \PHPUnit_Framework_TestCase {

	public function getClass() {
		return '\SMW\MediaWiki\DirectDBConnectionProvider';
	}

	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), new DirectDBConnectionProvider );
	}

	public function testSetGetConnection() {

		$database = $this->getMockBuilder( 'DatabaseMysql' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DirectDBConnectionProvider;
		$instance->setConnection( $database );

		$this->assertInstanceOf( 'DatabaseBase', $instance->getConnection() );
		$this->assertTrue( $database === $instance->getConnection() );

		$instance->releaseConnection();

	}

	public function testGetConnectionThrowsException() {

		$this->setExpectedException( 'RuntimeException' );

		$instance = new DirectDBConnectionProvider;
		$this->assertInstanceOf( 'DatabaseBase', $instance->getConnection() );

	}

}
