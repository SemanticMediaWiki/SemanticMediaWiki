<?php

namespace SMW\Tests\Unit\MediaWiki\Connection;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\LoadBalancerConnectionProvider;
use SMW\Tests\TestEnvironment;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @covers \SMW\MediaWiki\Connection\LoadBalancerConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   1.9
 *
 * @author mwjames
 */
class LoadBalancerConnectionProviderTest extends TestCase {

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
		$database = $this->getMockBuilder( IDatabase::class )
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
			IDatabase::class,
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

		// It will throw an exception as wrong php type is used.
		$this->expectException( 'TypeError' );
		$instance->getConnection();
	}

}
