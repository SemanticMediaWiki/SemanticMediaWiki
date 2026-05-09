<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\Lookup\TableStatisticsLookup;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\Lookup\TableStatisticsLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.01
 *
 * @author mwjames
 */
class TableStatisticsLookupTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	private $store;
	private $connection;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TableStatisticsLookup::class,
			new TableStatisticsLookup( $this->store )
		);
	}

	public function testGetStats() {
		$rows = [ (object)[ 'smw_namespace' => NS_MAIN, 'count' => 0 ] ];

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( $rows ) {
				return $this->createMockSelectQueryBuilder( $rows );
			} );

		$instance = new TableStatisticsLookup(
			$this->store
		);

		$this->assertIsArray(
			$instance->getStats()
		);
	}

	public function testGet_last_id() {
		$selectedFields = [];
		$throwawayWhere = [];

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( &$selectedFields, &$throwawayWhere ) {
				return $this->createMockSelectQueryBuilder( [ [ '42' ] ], $throwawayWhere, $selectedFields );
			} );

		$instance = new TableStatisticsLookup(
			$this->store
		);

		$this->assertEquals(
			42,
			$instance->get( 'last_id' )
		);

		$this->assertContains( 'MAX(smw_id)', $selectedFields );
	}

	public function testGet_rows_total_count() {
		$selectedFields = [];
		$throwawayWhere = [];

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( &$selectedFields, &$throwawayWhere ) {
				return $this->createMockSelectQueryBuilder( [ [ '42' ] ], $throwawayWhere, $selectedFields );
			} );

		$instance = new TableStatisticsLookup(
			$this->store
		);

		$this->assertEquals(
			42,
			$instance->get( 'rows_total_count' )
		);

		$this->assertContains( 'Count(*)', $selectedFields );
	}

}
