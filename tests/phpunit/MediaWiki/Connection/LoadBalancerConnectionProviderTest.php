<?php

namespace SMW\Tests\MediaWiki\Connection;

use ReflectionClass;
use SMW\Tests\PHPUnitCompat;
use SMW\MediaWiki\Connection\LoadBalancerConnectionProvider;
use SMW\Tests\TestEnvironment;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @covers \SMW\MediaWiki\Connection\LoadBalancerConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class LoadBalancerConnectionProviderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $loadBalancer;

	protected function setUp(): void {
		$this->loadBalancer = $this->getMockBuilder( ILoadBalancer::class )
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
		$database = $this->getMockBuilder( '\Wikimedia\Rdbms\IDatabase' )
			->disableOriginalConstructor()
			->getMock();

		$this->loadBalancer->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $database );

		$instance = new LoadBalancerConnectionProvider(
			DB_REPLICA
		);

		$connection = $instance->getConnection();

		$this->assertInstanceOf(
			'\Wikimedia\Rdbms\IDatabase',
			$instance->getConnection()
		);

		$this->assertTrue(
			$instance->getConnection() === $connection
		);

		$instance->releaseConnection();
	}

	public function testGetInvalidConnectionFromLoadBalancerThrowsException() {
		$loadBalancer = $this->getMockBuilder( ILoadBalancer::class )
			->disableOriginalConstructor()
			->getMock();

		$loadBalancer->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( 'Bar' );

		$instance = new LoadBalancerConnectionProvider(
			DB_REPLICA
		);

		$instance->setLoadBalancer( $loadBalancer );

		$this->expectException( 'RuntimeException' );
		$instance->getConnection();
	}

}
