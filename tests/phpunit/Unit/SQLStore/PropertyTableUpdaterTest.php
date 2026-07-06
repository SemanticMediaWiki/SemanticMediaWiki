<?php

namespace SMW\Tests\Unit\SQLStore;

use PHPUnit\Framework\TestCase;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\MediaWiki\Connection\Database;
use SMW\Parameters;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\Exception\TableMissingIdFieldException;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\PropertyTableUpdater;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\PropertyTableUpdater
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PropertyTableUpdaterTest extends TestCase {

	use MockWriteQueryBuilderTrait;

	private $store;
	private $idTable;
	private $connection;
	private $propertyTable;
	private $propertyStatisticsStore;
	private $propertyChangeListener;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->idTable );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyStatisticsStore = $this->getMockBuilder( PropertyStatisticsStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyChangeListener = $this->getMockBuilder( PropertyChangeListener::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyTableUpdater::class,
			new PropertyTableUpdater( $this->store, $this->propertyStatisticsStore )
		);
	}

	public function testUpdate_OnEmptyInsertRows() {
		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$this->propertyStatisticsStore->expects( $this->once() )
			->method( 'addToUsageCounts' );

		$instance = new PropertyTableUpdater(
			$this->store,
			$this->propertyStatisticsStore
		);

		$params = new Parameters(
			[
				'insert_rows' => [],
				'delete_rows' => [],
				'new_hashes'  => []
			]
		);

		$instance->update( 42, $params );
	}

	public function testUpdate_WithInsertRows() {
		$insertTables = $insertRows = [];
		$insertBuilder = $this->createMockInsertQueryBuilder( $insertTables, $insertRows );

		$deleteTables = $deleteWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder( $deleteTables, $deleteWheres );

		$updateBuilder = $this->createMockUpdateQueryBuilder();

		$this->connection->expects( $this->once() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$this->connection->expects( $this->once() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$this->connection->expects( $this->any() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$this->idTable->expects( $this->once() )
			->method( 'setPropertyTableHashes' );

		$this->propertyTable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$this->propertyTable->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'table_foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'table_foo' => $this->propertyTable ] );

		$this->propertyStatisticsStore->expects( $this->once() )
			->method( 'addToUsageCounts' )
			->with( $this->equalTo( [ 99998 => -1, 99999 => 1 ] ) );

		$instance = new PropertyTableUpdater(
			$this->store,
			$this->propertyStatisticsStore
		);

		$instance->setPropertyChangeListener(
			$this->propertyChangeListener
		);

		$params = new Parameters(
			[
				'insert_rows' => [
					'table_foo' => [
						[ 's_id' => 1001, 'p_id' => 99999 ]
					]
				],
				'delete_rows' => [
					'table_foo' => [
						[ 's_id' => 1001, 'p_id' => 99998 ]
					]
				],
				'new_hashes'  => []
			]
		);

		$instance->update( 42, $params );

		$this->assertContains( 'table_foo', $insertTables );
		$this->assertContains( [ [ 's_id' => 1001, 'p_id' => 99999 ] ], $insertRows );
		$this->assertContains( 'table_foo', $deleteTables );
	}

	public function testUpdate_Touched() {
		$this->connection->expects( $this->once() )
			->method( 'timestamp' )
			->willReturn( '19700101000000' );

		$insertBuilder = $this->createMockInsertQueryBuilder();
		$deleteBuilder = $this->createMockDeleteQueryBuilder();

		$updateTables = $updateSets = $updateWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder( $updateTables, $updateSets, $updateWheres );

		$this->connection->expects( $this->any() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$this->connection->expects( $this->any() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$this->connection->expects( $this->once() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$this->propertyTable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$this->propertyTable->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'table_foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'table_foo' => $this->propertyTable ] );

		$instance = new PropertyTableUpdater(
			$this->store,
			$this->propertyStatisticsStore
		);

		$instance->setPropertyChangeListener(
			$this->propertyChangeListener
		);

		$params = new Parameters(
			[
				'insert_rows' => [
					'table_foo' => [
						[ 's_id' => 1001, 'p_id' => 99999 ]
					]
				],
				'delete_rows' => [
					'table_foo' => [
						[ 's_id' => 1001, 'p_id' => 99998 ]
					]
				],
				'new_hashes'  => []
			]
		);

		$instance->update( 42, $params );

		$this->assertContains( SQLStore::ID_TABLE, $updateTables );
		$this->assertContains( [ 'smw_touched' => '19700101000000' ], $updateSets );
		$this->assertContains( [ 'smw_id' => [ 1001, 99999, 99998 ] ], $updateWheres );
	}

	public function testUpdate_WithInsertRowsButMissingIdFieldThrowsException() {
		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'table_foo' => $this->propertyTable ] );

		$instance = new PropertyTableUpdater(
			$this->store,
			$this->propertyStatisticsStore
		);

		$params = new Parameters(
			[
				'insert_rows' => [
					'table_foo' => []
				],
				'delete_rows' => [
					'table_foo' => []
				],
				'new_hashes'  => []
			]
		);

		$this->expectException( TableMissingIdFieldException::class );
		$instance->update( 42, $params );
	}

}
