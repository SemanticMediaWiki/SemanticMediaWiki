<?php

namespace SMW\Tests\Elastic\Connection;

use SMW\Elastic\Connection\ConnectionProvider;
use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Config;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Connection\ConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConnectionProviderTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $logger;
	private $lockManager;

	protected function setUp() : void {

		$this->logger = $this->getMockBuilder( '\Psr\Log\LoggerInterface' )
			->disableOriginalConstructor()
			->getMock();

		$this->lockManager = $this->getMockBuilder( '\SMW\Elastic\Connection\LockManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->config = new Config();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConnectionProvider::class,
			new ConnectionProvider( $this->lockManager, $this->config )
		);
	}

	public function testGetConnection_MissingEndpointsThrowsException() {

		$config = new Config (
			[
				Config::DEFAULT_STORE => 'SMWElasticStore'
			]
		);

		$instance = new ConnectionProvider(
			$this->lockManager,
			$config
		);

		$this->expectException( '\SMW\Elastic\Exception\MissingEndpointConfigException' );
		$instance->getConnection();
	}

	public function testGetConnection_DummyClient() {

		$config = new Config (
			[
				Config::DEFAULT_STORE => 'SMWSQLStore',
				Config::ELASTIC_ENDPOINTS => [ 'foo' ]
			]
		);

		$instance = new ConnectionProvider(
			$this->lockManager,
			$config
		);

		$instance->setLogger( $this->logger );

		$this->assertInstanceOf(
			DummyClient::class,
			$instance->getConnection()
		);
	}

	public function testGetConnection_Client() {

		if ( !class_exists( '\Elasticsearch\ClientBuilder' ) ) {
			$this->markTestSkipped( "elasticsearch-php dependency is not available." );
		}

		$config = new Config (
			[
				Config::DEFAULT_STORE => 'SMWElasticStore',
				Config::ELASTIC_ENDPOINTS => [ 'foo' ]
			]
		);

		$instance = new ConnectionProvider(
			$this->lockManager,
			$config
		);

		$instance->setLogger( $this->logger );

		$this->assertInstanceOf(
			Client::class,
			$instance->getConnection()
		);
	}

	public function testGetConnectionThrowsExceptionWhenNotInstalled() {

		if ( class_exists( '\Elasticsearch\ClientBuilder' ) ) {
			$this->markTestSkipped( "\Elasticsearch\ClientBuilder is available, no exception is thrown" );
		}

		$config = new Config (
			[
				Config::DEFAULT_STORE => 'SMWElasticStore',
				Config::ELASTIC_ENDPOINTS => [ 'foo' ]
			]
		);

		$instance = new ConnectionProvider(
			$this->lockManager,
			$config
		);

		$this->expectException( '\SMW\Elastic\Exception\ClientBuilderNotFoundException' );
		$instance->getConnection();
	}

}
