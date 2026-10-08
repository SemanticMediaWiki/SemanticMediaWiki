<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use PHPUnit\Framework\TestCase;
use SMW\Elastic\Config;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Connection\LockManager;
use SMW\MediaWiki\Hooks\SoftwareInfo;
use SMW\Store;

/**
 * @covers \SMW\MediaWiki\Hooks\SoftwareInfo
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.3.2
 */
class SoftwareInfoTest extends TestCase {

	/**
	 * The entry CirrusSearch adds for the same server
	 */
	private const CIRRUS_SEARCH_ENTRY = '[https://www.elastic.co/elasticsearch Elasticsearch]';

	protected function setUp(): void {
		if ( !class_exists( '\Elasticsearch\Client' ) ) {
			$this->markTestSkipped( "elasticsearch-php dependency is not available." );
		}
	}

	public function testListsTheElasticsearchServer() {
		$software = [];

		$this->newSoftwareInfo()->onSoftwareInfo( $software );

		$this->assertSame( [ self::CIRRUS_SEARCH_ENTRY => '7.10.2' ], $software );
	}

	public function testDoesNotListTheServerCirrusSearchAlreadyListed() {
		$software = [ self::CIRRUS_SEARCH_ENTRY => '7.10.2' ];

		$this->newSoftwareInfo()->onSoftwareInfo( $software );

		$this->assertCount( 1, $software );
	}

	private function newSoftwareInfo(): SoftwareInfo {
		$store = $this->createMock( Store::class );

		$store->method( 'getConnection' )
			->willReturn( $this->newElasticsearchConnection() );

		return new SoftwareInfo( $store );
	}

	private function newElasticsearchConnection(): Client {
		$elasticClient = $this->getMockBuilder( '\Elasticsearch\Client' )
			->disableOriginalConstructor()
			->getMock();

		$elasticClient->method( 'ping' )
			->willReturn( true );

		$elasticClient->method( 'info' )
			->willReturn( [ 'version' => [ 'number' => '7.10.2' ], 'tagline' => 'You Know, for Search' ] );

		$connection = new Client(
			$elasticClient,
			$this->createMock( LockManager::class ),
			new Config( [ Config::DEFAULT_STORE => 'SMWElasticStore' ] )
		);

		// The ping result is cached across instances
		$connection->clear();

		return $connection;
	}

}
