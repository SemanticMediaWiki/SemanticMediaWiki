<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\Lookup\TableStatisticsLookup;
use SMW\SQLStore\SQLStore;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\SelectQueryBuilder;

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
		$ignored = [];

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( &$ignored ) {
				return $this->createMockSelectQueryBuilder(
					[],
					0,
					$ignored,
					(object)[ 'count' => 0 ]
				);
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

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( &$selectedFields ) {
				return $this->createMockSelectQueryBuilder( [], "42", $selectedFields );
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

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( &$selectedFields ) {
				return $this->createMockSelectQueryBuilder( [], "42", $selectedFields );
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

	/**
	 * Creates a mock SelectQueryBuilder where chained methods return $this,
	 * fetchResultSet() returns the given rows wrapped in FakeResultWrapper,
	 * fetchRow() returns the supplied row (or false), and fetchField() returns
	 * the supplied scalar value.
	 *
	 * If $selectedFields is provided (by reference), every call to select() is
	 * appended to it so tests can assert which field was queried.
	 */
	private function createMockSelectQueryBuilder(
		array $rows = [],
		$field = 0,
		array &$selectedFields = [],
		$row = false
	) {
		$queryBuilder = $this->getMockBuilder( SelectQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$chainMethods = [ 'from', 'join', 'leftJoin', 'where', 'groupBy',
			'having', 'orderBy', 'caller' ];

		foreach ( $chainMethods as $method ) {
			$queryBuilder->expects( $this->any() )
				->method( $method )
				->willReturnSelf();
		}

		$queryBuilder->expects( $this->any() )
			->method( 'select' )
			->willReturnCallback( static function ( $fields ) use ( $queryBuilder, &$selectedFields ) {
				$selectedFields[] = $fields;
				return $queryBuilder;
			} );

		$queryBuilder->expects( $this->any() )
			->method( 'newSubquery' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder(
				$rows,
				$field,
				$selectedFields,
				$row
			) );

		$queryBuilder->expects( $this->any() )
			->method( 'fetchResultSet' )
			->willReturn( new FakeResultWrapper( $rows ) );

		$queryBuilder->expects( $this->any() )
			->method( 'fetchRow' )
			->willReturn( $row );

		$queryBuilder->expects( $this->any() )
			->method( 'fetchField' )
			->willReturn( $field );

		return $queryBuilder;
	}

}
