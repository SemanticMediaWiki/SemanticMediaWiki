<?php

namespace SMW\Tests\Elastic\Connection;

use SMW\Elastic\Connection\ConnectionProvider;
use SMW\Elastic\Connection\DummyClient;
use SMW\Elastic\Connection\Client;
use SMW\Options;
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
	private $cache;

	protected function setUp() {

		$this->logger = $this->getMockBuilder( '\Psr\Log\LoggerInterface' )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->options = new Options();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConnectionProvider::class,
			new ConnectionProvider( $this->options, $this->cache  )
		);
	}

	public function testGetConnection_DummyClient() {

		$options = new Options (
			[
				'is.elasticstore' => false,
				'endpoints' => []
			]
		);

		$instance = new ConnectionProvider(
			$options,
			$this->cache
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

		$options = new Options (
			[
				'is.elasticstore' => true,
				'endpoints' => []
			]
		);

		$instance = new ConnectionProvider(
			$options,
			$this->cache
		);

		$instance->setLogger( $this->logger );

		$this->assertInstanceOf(
			Client::class,
			$instance->getConnection()
		);
	}

	public function testGetConnectionThrowsExceptionWhenNotInstalled() {

		if ( class_exists( '\Elasticsearch\ClientBuilder' ) ) {
			$this->markTestSkipped( "No exception thrown when ClientBuilder is available!" );
		}

		$options = new Options (
			[
				'is.elasticstore' => true,
				'endpoints' => []
			]
		);

		$instance = new ConnectionProvider(
			$options,
			$this->cache
		);

		$this->setExpectedException( '\SMW\Elastic\Exception\ClientBuilderNotFoundException' );
		$instance->getConnection();
	}

}
