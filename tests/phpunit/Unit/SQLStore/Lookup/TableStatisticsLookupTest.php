<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Connection\Query;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\Lookup\TableStatisticsLookup;
use SMW\SQLStore\SQLStore;

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

	private $store;
	private $connection;
	private $query;

	protected function setUp(): void {
		$this->query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->willReturn( $this->query );

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
		$this->query->expects( $this->any() )
			->method( 'execute' )
			->willReturn( [] );

		$this->connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [] );

		$this->connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( (object)[ 'count' => 0 ] );

		$instance = new TableStatisticsLookup(
			$this->store
		);

		$this->assertIsArray(

			$instance->getStats()
		);
	}

	public function testGet_last_id() {
		$this->connection->expects( $this->any() )
			->method( 'selectField' )
			->with(
				$this->anything(),
				$this->stringContains( 'MAX(smw_id)' ) )
			->willReturn( "42" );

		$instance = new TableStatisticsLookup(
			$this->store
		);

		$this->assertEquals(
			42,
			$instance->get( 'last_id' )
		);
	}

	public function testGet_rows_total_count() {
		$this->connection->expects( $this->any() )
			->method( 'selectField' )
			->with(
				$this->anything(),
				$this->stringContains( 'Count(*)' ) )
			->willReturn( "42" );

		$instance = new TableStatisticsLookup(
			$this->store
		);

		$this->assertEquals(
			42,
			$instance->get( 'rows_total_count' )
		);
	}

}
