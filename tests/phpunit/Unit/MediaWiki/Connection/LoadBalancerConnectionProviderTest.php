<?php

namespace SMW\Tests\MediaWiki\Connection;

use DatabaseBase;
use ReflectionClass;
use SMW\Tests\PHPUnitCompat;
use SMW\MediaWiki\Connection\LoadBalancerConnectionProvider;

/**
 * @covers \SMW\MediaWiki\Connection\LoadBalancerConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class LoadBalancerConnectionProviderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			LoadBalancerConnectionProvider::class,
			new LoadBalancerConnectionProvider( DB_SLAVE )
		);
	}

	public function testGetAndReleaseConnection() {

		$instance = new LoadBalancerConnectionProvider(
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

		$instance = new LoadBalancerConnectionProvider(
			DB_SLAVE
		);

		$reflector = new ReflectionClass(
			LoadBalancerConnectionProvider::class
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
