<?php

namespace SMW\Tests\Unit\Maintenance;

use Onoi\Cache\Cache;
use Onoi\MessageReporter\MessageReporter;
use PHPUnit\Framework\TestCase;
use SMW\Maintenance\DuplicateEntitiesDisposer;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\Maintenance\DuplicateEntitiesDisposer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class DuplicateEntitiesDisposerTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $store;
	private $cache;
	private $messageReporter;
	private $connection;

	protected function setUp(): void {
		$propertyTableIdReferenceDisposer = $this->getMockBuilder( PropertyTableIdReferenceDisposer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'service' )
			->with( 'PropertyTableIdReferenceDisposer' )
			->willReturn( $propertyTableIdReferenceDisposer );

		$this->messageReporter = $this->getMockBuilder( MessageReporter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DuplicateEntitiesDisposer::class,
			new DuplicateEntitiesDisposer( $this->store )
		);
	}

	public function testFindDuplicateEntityRecords() {
		$idTable = $this->getMockBuilder( '\stdClss' )
			->disableOriginalConstructor()
			->setMethods( [ 'findDuplicates' ] )
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new DuplicateEntitiesDisposer(
			$this->store
		);

		$instance->findDuplicates();
	}

	public function testVerifyAndDispose_NoDuplicates() {
		$this->store->expects( $this->never() )
			->method( 'getConnection' );

		$instance = new DuplicateEntitiesDisposer(
			$this->store
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->verifyAndDispose( [] );
	}

	public function testVerifyAndDispose_NonIterable() {
		$this->store->expects( $this->never() )
			->method( 'getConnection' );

		$instance = new DuplicateEntitiesDisposer(
			$this->store
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->verifyAndDispose( 'Foo' );
	}

	public function testVerifyAndDispose_NoDuplicates_WithCache() {
		$this->store->expects( $this->never() )
			->method( 'getConnection' );

		$this->cache->expects( $this->atLeastOnce() )
			->method( 'delete' );

		$instance = new DuplicateEntitiesDisposer(
			$this->store,
			$this->cache
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->verifyAndDispose( [] );
	}

	public function testVerifyAndDispose_WithDuplicateRecord() {
		$record = [
			'smw_title' => 'Foo',
			'smw_namespace' => 0,
			'smw_iw' => '',
			'smw_subobject' => ''
		];

		$row = new stdClass;
		$row->smw_id = 42;

		$whereConditions = [];
		$this->connection->method( 'newSelectQueryBuilder' )
			->willReturnCallback(
				function () use ( $row, &$whereConditions ) {
					return $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );
				}
			);

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new DuplicateEntitiesDisposer(
			$this->store
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$duplicates = [
			SQLStore::ID_TABLE => [ $record ]
		];

		$instance->verifyAndDispose( $duplicates );

		$this->assertSame( [ $record ], $whereConditions );
	}

	public function testWikipageTableBatchesInsertsIntoSingleBuilder() {
		$duplicates = [
			[ 's_id' => 11, 'p_id' => 21, 'o_id' => 31 ],
			[ 's_id' => 12, 'p_id' => 22, 'o_id' => 32 ],
			[ 's_id' => 13, 'p_id' => 23, 'o_id' => 33 ],
		];

		$insertTables = [];
		$insertRows = [];
		$insertCallCount = 0;
		$this->connection->method( 'newInsertQueryBuilder' )
			->willReturnCallback(
				function () use ( &$insertTables, &$insertRows, &$insertCallCount ) {
					$insertCallCount++;
					return $this->createMockInsertQueryBuilder( $insertTables, $insertRows );
				}
			);

		$deleteTables = [];
		$deleteWheres = [];
		$this->connection->method( 'newDeleteQueryBuilder' )
			->willReturnCallback(
				function () use ( &$deleteTables, &$deleteWheres ) {
					return $this->createMockDeleteQueryBuilder( $deleteTables, $deleteWheres );
				}
			);

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new DuplicateEntitiesDisposer( $this->store );
		$instance->setMessageReporter( $this->messageReporter );

		$instance->verifyAndDispose( [ 'smw_di_wikipage' => $duplicates ] );

		// One shared InsertQueryBuilder for all three duplicates.
		$this->assertSame( 1, $insertCallCount );
		$this->assertSame( [ 'smw_di_wikipage' ], $insertTables );
		$this->assertSame( $duplicates, $insertRows );

		// One DELETE per duplicate, each with the row's composite WHERE.
		$this->assertCount( 3, $deleteWheres );
		$this->assertSame( $duplicates[0], $deleteWheres[0] );
	}

	public function testRediTableBatchesInsertsAndSkipsEmptyTitleRows() {
		// Mix of normal duplicates and an empty-title row that must be
		// DELETEd-only (the canonical re-INSERT is skipped via continue).
		$normal1 = [ 's_title' => 'Foo', 's_namespace' => 0, 'o_id' => 100 ];
		$emptyTitle = [ 's_title' => '', 's_namespace' => 0, 'o_id' => 200 ];
		$normal2 = [ 's_title' => 'Bar', 's_namespace' => 0, 'o_id' => 300 ];
		$duplicates = [ $normal1, $emptyTitle, $normal2 ];

		$insertTables = [];
		$insertRows = [];
		$insertCallCount = 0;
		$this->connection->method( 'newInsertQueryBuilder' )
			->willReturnCallback(
				function () use ( &$insertTables, &$insertRows, &$insertCallCount ) {
					$insertCallCount++;
					return $this->createMockInsertQueryBuilder( $insertTables, $insertRows );
				}
			);

		$deleteTables = [];
		$deleteWheres = [];
		$this->connection->method( 'newDeleteQueryBuilder' )
			->willReturnCallback(
				function () use ( &$deleteTables, &$deleteWheres ) {
					return $this->createMockDeleteQueryBuilder( $deleteTables, $deleteWheres );
				}
			);

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new DuplicateEntitiesDisposer( $this->store );
		$instance->setMessageReporter( $this->messageReporter );

		$instance->verifyAndDispose( [ RedirectStore::TABLE_NAME => $duplicates ] );

		// One shared builder; all three DELETEs run; only the two non-empty
		// rows accumulate into the INSERT batch.
		$this->assertSame( 1, $insertCallCount );
		$this->assertSame( [ RedirectStore::TABLE_NAME ], $insertTables );
		$this->assertSame( [ $normal1, $normal2 ], $insertRows );
		$this->assertCount( 3, $deleteWheres );
	}

}
