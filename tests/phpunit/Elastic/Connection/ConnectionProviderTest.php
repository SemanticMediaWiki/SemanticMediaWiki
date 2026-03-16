<?php

namespace SMW\Tests\Elastic\Connection;

use Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\Elastic\Config;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Connection\ConnectionProvider;
use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\Connection\LockManager;
use SMW\Elastic\ElasticStore;
use SMW\Elastic\Exception\ClientBuilderNotFoundException;
use SMW\Elastic\Exception\MissingEndpointConfigException;

/**
 * @covers \SMW\Elastic\Connection\ConnectionProvider
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ConnectionProviderTest extends TestCase {

	private LoggerInterface $logger;
	private LockManager $lockManager;
	private Config $config;

	protected function setUp(): void {
		$this->logger = $this->createMock( LoggerInterface::class );

		$this->lockManager = $this->createMock( LockManager::class );

		$this->config = new Config();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConnectionProvider::class,
			new ConnectionProvider( $this->lockManager, $this->config )
		);
	}

	public function testGetConnection_MissingEndpointsThrowsException() {
		$config = new Config(
			[
				Config::DEFAULT_STORE => ElasticStore::class
			]
		);

		$instance = new ConnectionProvider(
			$this->lockManager,
			$config
		);

		$this->expectException( MissingEndpointConfigException::class );
		$instance->getConnection();
	}

	public function testGetConnection_DummyClient() {
		$config = new Config(
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
		if ( !class_exists( ClientBuilder::class ) ) {
			$this->markTestSkipped( "elasticsearch-php dependency is not available." );
		}

		$config = new Config(
			[
				Config::DEFAULT_STORE => ElasticStore::class,
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
		if ( class_exists( ClientBuilder::class ) ) {
			$this->markTestSkipped( "\Elasticsearch\ClientBuilder is available, no exception is thrown" );
		}

		$config = new Config(
			[
				Config::DEFAULT_STORE => ElasticStore::class,
				Config::ELASTIC_ENDPOINTS => [ 'foo' ]
			]
		);

		$instance = new ConnectionProvider(
			$this->lockManager,
			$config
		);

		$this->expectException( ClientBuilderNotFoundException::class );
		$instance->getConnection();
	}

}
