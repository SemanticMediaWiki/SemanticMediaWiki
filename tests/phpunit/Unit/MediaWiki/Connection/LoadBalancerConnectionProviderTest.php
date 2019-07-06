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
			new LoadBalancerConnectionProvider( DB_REPLICA )
		);
	}

	public function testGetAndReleaseConnection() {

		$instance = new LoadBalancerConnectionProvider(
			DB_REPLICA
		);

		$instance->asConnectionRef( false );

		$connection = $instance->getConnection();

		$this->assertInstanceOf(
			'\DatabaseBase',
			$instance->getConnection()
		);

		$this->assertTrue(
			$instance->getConnection() === $connection
		);

		$instance->releaseConnection();
	}

	public function testGetInvalidConnectionFromLoadBalancerThrowsException() {

		$loadBalancer = $this->getMockBuilder( '\LoadBalancer' )
			->disableOriginalConstructor()
			->getMock();

		$loadBalancer->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( 'Bar' ) );

		$instance = new LoadBalancerConnectionProvider(
			DB_REPLICA
		);

		$instance->setLoadBalancer( $loadBalancer );
		$instance->asConnectionRef( false );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getConnection();
	}

}
