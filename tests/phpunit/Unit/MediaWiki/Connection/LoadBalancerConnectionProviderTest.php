<?php

namespace SMW\Tests\MediaWiki\Connection;

use DatabaseBase;
use ReflectionClass;
use SMW\Tests\PHPUnitCompat;
use SMW\MediaWiki\Connection\LoadBalancerConnectionProvider;
use SMW\Tests\TestEnvironment;

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

	private $loadBalancer;

	protected function setUp() {

		$this->loadBalancer = $this->getMockBuilder( '\LoadBalancer' )
			->disableOriginalConstructor()
			->getMock();

		$testEnvironment = new TestEnvironment();
		$testEnvironment->registerObject( 'DBLoadBalancer', $this->loadBalancer );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			LoadBalancerConnectionProvider::class,
			new LoadBalancerConnectionProvider( DB_REPLICA )
		);
	}

	public function testGetAndReleaseConnection() {

		$database = $this->getMockBuilder( '\IDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$this->loadBalancer->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$instance = new LoadBalancerConnectionProvider(
			DB_REPLICA
		);

		$instance->asConnectionRef( false );

		$connection = $instance->getConnection();

		$this->assertInstanceOf(
			'\IDatabase',
			$instance->getConnection()
		);

		$this->assertTrue(
			$instance->getConnection() === $connection
		);

		$instance->releaseConnection();
	}

	public function testGetAndReleaseConnectionRef() {

		$database = $this->getMockBuilder( '\IDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$this->loadBalancer->expects( $this->once() )
			->method( 'getConnectionRef' )
			->will( $this->returnValue( $database ) );

		$instance = new LoadBalancerConnectionProvider(
			DB_REPLICA
		);

		$connection = $instance->getConnection();

		$this->assertInstanceOf(
			'\IDatabase',
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
