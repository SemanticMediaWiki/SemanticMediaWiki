<?php

namespace SMW\Tests\MediaWiki;

use DatabaseBase;
use ReflectionClass;
use SMW\MediaWiki\DBLoadBalancerConnectionProvider;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\DBLoadBalancerConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class DBLoadBalancerConnectionProviderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DBLoadBalancerConnectionProvider::class,
			new DBLoadBalancerConnectionProvider( DB_SLAVE )
		);
	}

	public function testGetAndReleaseConnection() {

		$instance = new DBLoadBalancerConnectionProvider(
			DB_SLAVE
		);

		$connection = $instance->getConnection();

		$this->assertInstanceOf(
			'DatabaseBase',
			$instance->getConnection()
		);

		$this->assertTrue(
			$instance->getConnection() === $connection
		);

		$instance->releaseConnection();
	}

	public function testGetConnectionThrowsException() {

		$this->setExpectedException( 'RuntimeException' );

		$instance = new DBLoadBalancerConnectionProvider(
			DB_SLAVE
		);

		$reflector = new ReflectionClass(
			DBLoadBalancerConnectionProvider::class
		);

		$connection = $reflector->getProperty( 'connection' );
		$connection->setAccessible( true );
		$connection->setValue( $instance, 'invalid' );

		$this->assertInstanceOf(
			'DatabaseBase',
			$instance->getConnection()
		);
	}

}
