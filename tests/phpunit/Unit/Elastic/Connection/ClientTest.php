<?php

namespace SMW\Tests\Unit\Elastic\Connection;

use PHPUnit\Framework\TestCase;
use SMW\Elastic\Config;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Connection\LockManager;
use SMW\Elastic\Exception\ReplicationException;

/**
 * @covers \SMW\Elastic\Connection\Client
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ClientTest extends TestCase {

	private $elasticClient;
	private $lockManager;

	protected function setUp(): void {
		if ( !class_exists( '\Elasticsearch\Client' ) ) {
			$this->markTestSkipped( "elasticsearch-php dependency is not available." );
		}

		$this->elasticClient = $this->getMockBuilder( '\Elasticsearch\Client' )
			->disableOriginalConstructor()
			->getMock();

		$this->lockManager = $this->getMockBuilder( LockManager::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Client::class,
			new Client( $this->elasticClient, $this->lockManager )
		);
	}

	public function testHasMaintenanceLock() {
		$this->lockManager->expects( $this->once() )
			->method( 'hasMaintenanceLock' );

		$instance = new Client(
			$this->elasticClient,
			$this->lockManager
		);

		$instance->hasMaintenanceLock();
	}

	public function testSetMaintenanceLock() {
		$this->lockManager->expects( $this->once() )
			->method( 'setMaintenanceLock' );

		$instance = new Client(
			$this->elasticClient,
			$this->lockManager
		);

		$instance->setMaintenanceLock();
	}

	public function testBulkOnIllegalArgumentErrorThrowsReplicationException() {
		$options = new Config(
			[
				'replication' => [
					'throw.exception.on.illegal.argument.error' => true
				]
			]
		);

		$response = '{"took":67,"errors":true,"items":[{"index":{"_index":"smw-data-test","_type":"data","_id":"14099","status":400,"error":{"type":"illegal_argument_exception","reason":"Limit of total fields [20] in index [smw-data-test] has been exceeded"}}}]}';

		$this->elasticClient->expects( $this->once() )
			->method( 'bulk' )
			->willReturn( json_decode( $response, true ) );

		$instance = new Client(
			$this->elasticClient,
			$this->lockManager,
			$options
		);

		$params = [
			'index' => 'foo'
		];

		$this->expectException( ReplicationException::class );
		$instance->bulk( $params );
	}

	public function testIsOpenSearchWhenTheServerNamesItsDistribution() {
		$instance = $this->newClientAnswering( [
			'version' => [ 'distribution' => 'opensearch', 'number' => '2.19.5' ],
			'tagline' => 'The OpenSearch Project: https://opensearch.org/'
		] );

		$this->assertTrue( $instance->isOpenSearch() );
	}

	public function testIsOpenSearchByTaglineWhenCompatibilityModeHidesTheDistribution() {
		$instance = $this->newClientAnswering( self::COMPATIBILITY_MODE_INFO );

		$this->assertTrue( $instance->isOpenSearch() );
	}

	public function testIsNotOpenSearchForElasticsearch() {
		$instance = $this->newClientAnswering( [
			'version' => [ 'number' => '7.10.2', 'build_flavor' => 'default' ],
			'tagline' => 'You Know, for Search'
		] );

		$this->assertFalse( $instance->isOpenSearch() );
	}

	public function testGetVersionReportsTheNodeVersionWhenCompatibilityModeSpoofsIt() {
		$this->nodesReportVersion( '2.19.5' );

		$instance = $this->newClientAnswering( self::COMPATIBILITY_MODE_INFO, $this->newDefaultStoreConfig() );

		$this->assertSame( '2.19.5', $instance->getVersion() );
	}

	public function testGetVersionKeepsTheMainResponseVersionForElasticsearch() {
		$this->nodesReportVersion( '9.9.9' );

		$instance = $this->newClientAnswering(
			[ 'version' => [ 'number' => '7.10.2' ], 'tagline' => 'You Know, for Search' ],
			$this->newDefaultStoreConfig()
		);

		$this->assertSame( '7.10.2', $instance->getVersion() );
	}

	public function testGetSoftwareInfoNamesOpenSearchInCompatibilityMode() {
		$this->nodesReportVersion( '2.19.5' );

		$instance = $this->newClientAnswering( self::COMPATIBILITY_MODE_INFO, $this->newDefaultStoreConfig() );

		$this->assertEquals(
			[ 'component' => '[https://opensearch.org OpenSearch]', 'version' => '2.19.5' ],
			$instance->getSoftwareInfo()
		);
	}

	/**
	 * What OpenSearch 2.x answers to `GET /` with `compatibility.override_main_response_version`
	 * enabled: no `distribution`, the version number of Elasticsearch 7.10.2, its own tagline.
	 */
	private const COMPATIBILITY_MODE_INFO = [
		'version' => [ 'number' => '7.10.2', 'build_type' => 'tar' ],
		'tagline' => 'The OpenSearch Project: https://opensearch.org/'
	];

	private function newClientAnswering( array $info, ?Config $config = null ): Client {
		$this->elasticClient->method( 'ping' )
			->willReturn( true );

		$this->elasticClient->method( 'info' )
			->willReturn( $info );

		$instance = new Client( $this->elasticClient, $this->lockManager, $config );

		// The ping result is cached across instances
		$instance->clear();

		return $instance;
	}

	private function nodesReportVersion( string $version ): void {
		$nodes = $this->getMockBuilder( '\Elasticsearch\Namespaces\NodesNamespace' )
			->disableOriginalConstructor()
			->getMock();

		$nodes->method( 'info' )
			->willReturn( [ 'nodes' => [ 'node-id' => [ 'version' => $version ] ] ] );

		$this->elasticClient->method( 'nodes' )
			->willReturn( $nodes );
	}

	private function newDefaultStoreConfig(): Config {
		return new Config( [ Config::DEFAULT_STORE => 'SMWElasticStore' ] );
	}

}
