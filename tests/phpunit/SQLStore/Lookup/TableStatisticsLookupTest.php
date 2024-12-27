<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\SQLStore\Lookup\TableStatisticsLookup;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\Lookup\TableStatisticsLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.01
 *
 * @author mwjames
 */
class TableStatisticsLookupTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $connection;
	private $query;

	protected function setUp(): void {
		$this->query = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Query' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'newQuery' )
			->willReturn( $this->query );

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
