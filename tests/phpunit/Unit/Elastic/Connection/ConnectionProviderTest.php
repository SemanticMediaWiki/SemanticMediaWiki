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
	private $lockManager;

	protected function setUp() {

		$this->logger = $this->getMockBuilder( '\Psr\Log\LoggerInterface' )
			->disableOriginalConstructor()
			->getMock();

		$this->lockManager = $this->getMockBuilder( '\SMW\Elastic\Connection\LockManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->options = new Options();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConnectionProvider::class,
			new ConnectionProvider( $this->lockManager, $this->options )
		);
	}

	public function testGetConnection_MissingEndpointsThrowsException() {

		$options = new Options (
			[
				'is.elasticstore' => true
			]
		);

		$instance = new ConnectionProvider(
			$this->lockManager,
			$options
		);

		$this->setExpectedException( '\SMW\Elastic\Exception\MissingEndpointConfigException' );
		$instance->getConnection();
	}

	public function testGetConnection_DummyClient() {

		$options = new Options (
			[
				'is.elasticstore' => false,
				'endpoints' => [ 'foo' ]
			]
		);

		$instance = new ConnectionProvider(
			$this->lockManager,
			$options
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
				'endpoints' => [ 'foo' ]
			]
		);

		$instance = new ConnectionProvider(
			$this->lockManager,
			$options
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

		$options = new Options (
			[
				'is.elasticstore' => true,
				'endpoints' => [ 'foo' ]
			]
		);

		$instance = new ConnectionProvider(
			$this->lockManager,
			$options
		);

		$this->setExpectedException( '\SMW\Elastic\Exception\ClientBuilderNotFoundException' );
		$instance->getConnection();
	}

}
