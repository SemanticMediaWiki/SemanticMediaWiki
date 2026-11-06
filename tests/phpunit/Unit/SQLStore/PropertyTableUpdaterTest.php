<?php

namespace SMW\Tests\Unit\SQLStore;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
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
use SMW\Tests\Utils\SpyLogger;
use Wikimedia\Rdbms\DBError;

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
	private SpyLogger $logger;

	/**
	 * @var callable[]
	 */
	private array $callbacksOnCommit = [];

	private array $usageCountWrites = [];

	protected function setUp(): void {
		parent::setUp();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'onTransactionCommitOrIdle' )
			->willReturnCallback( function ( callable $callback ): void {
				$this->callbacksOnCommit[] = $callback;
			} );

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

		$this->propertyStatisticsStore->expects( $this->any() )
			->method( 'addToUsageCounts' )
			->willReturnCallback( function ( array $changes ): bool {
				$this->usageCountWrites[] = $changes;
				return true;
			} );

		$this->propertyChangeListener = $this->getMockBuilder( PropertyChangeListener::class )
			->disableOriginalConstructor()
			->getMock();

		$this->logger = new SpyLogger();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyTableUpdater::class,
			new PropertyTableUpdater( $this->store, $this->propertyStatisticsStore, $this->logger )
		);
	}

	public function testUpdate_OnEmptyInsertRows() {
		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = $this->newPropertyTableUpdater();

		$instance->update( 42, new Parameters( [ 'insert_rows' => [], 'delete_rows' => [], 'new_hashes' => [] ] ) );
		$instance->writeSharedRowsOnCommit();

		$this->assertSame( [], $this->callbacksOnCommit );
	}

	public function testUpdate_WithInsertRows() {
		$insertTables = $insertRows = [];
		$insertBuilder = $this->createMockInsertQueryBuilder( $insertTables, $insertRows );

		$deleteTables = $deleteWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder( $deleteTables, $deleteWheres );

		$this->connection->expects( $this->once() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $insertBuilder );

		$this->connection->expects( $this->once() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$this->connection->expects( $this->any() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $this->createMockUpdateQueryBuilder() );

		$this->idTable->expects( $this->once() )
			->method( 'setPropertyTableHashes' );

		$this->usePropertyTable();

		$this->newPropertyTableUpdater()->update( 42, $this->changedValue() );

		$this->assertContains( 'table_foo', $insertTables );
		$this->assertContains( [ [ 's_id' => 1001, 'p_id' => 99999 ] ], $insertRows );
		$this->assertContains( 'table_foo', $deleteTables );
	}

	public function testSubjectIsTouchedWithinTheUpdate() {
		$updateWheres = [];
		$this->stubWrites( $updateWheres );
		$this->usePropertyTable();

		$this->newPropertyTableUpdater()->update( 42, $this->changedValue() );

		$this->assertSame( [ [ 'smw_id' => [ 1001 ] ] ], $updateWheres );
	}

	public function testPropertiesAreTouchedOnCommit() {
		$updateWheres = [];
		$this->stubWrites( $updateWheres );
		$this->runsSectionTransactions();
		$this->usePropertyTable();

		$instance = $this->newPropertyTableUpdater();
		$instance->update( 42, $this->changedValue() );
		$instance->writeSharedRowsOnCommit();
		$touchedBeforeCommit = $updateWheres;
		$this->commit();

		$this->assertSame(
			[
				'before commit' => [ [ 'smw_id' => [ 1001 ] ] ],
				'after commit' => [ [ 'smw_id' => [ 1001 ] ], [ 'smw_id' => [ 99998, 99999 ] ] ]
			],
			[ 'before commit' => $touchedBeforeCommit, 'after commit' => $updateWheres ]
		);
	}

	public function testUsageCountChangesAreWrittenOnCommit() {
		$this->stubWrites();
		$this->runsSectionTransactions();
		$this->usePropertyTable();

		$instance = $this->newPropertyTableUpdater();
		$instance->update( 42, $this->changedValue() );
		$instance->writeSharedRowsOnCommit();
		$writesBeforeCommit = $this->usageCountWrites;
		$this->commit();

		$this->assertSame(
			[ 'before commit' => [], 'after commit' => [ [ 99998 => -1, 99999 => 1 ] ] ],
			[ 'before commit' => $writesBeforeCommit, 'after commit' => $this->usageCountWrites ]
		);
	}

	public function testUsageCountChangesOfSeveralUpdatesAreWrittenTogether() {
		$this->stubWrites();
		$this->runsSectionTransactions();
		$this->usePropertyTable();

		$instance = $this->newPropertyTableUpdater();
		$instance->update( 42, $this->newParameters( [ [ 's_id' => 42, 'p_id' => 99999 ] ], [] ) );
		$instance->update( 43, $this->newParameters( [ [ 's_id' => 43, 'p_id' => 99999 ] ], [] ) );
		$instance->writeSharedRowsOnCommit();
		$this->commit();

		$this->assertSame( [ [ 99999 => 2 ] ], $this->usageCountWrites );
	}

	public function testDiscardedChangesAreNotWritten() {
		$this->stubWrites();
		$this->usePropertyTable();

		$instance = $this->newPropertyTableUpdater();
		$instance->update( 42, $this->changedValue() );
		$instance->discardSharedRows();
		$instance->writeSharedRowsOnCommit();

		$this->assertSame( [], $this->callbacksOnCommit );
	}

	public function testFailedWriteOnCommitIsLogged() {
		$this->stubWrites();
		$this->usePropertyTable();

		$this->connection->expects( $this->any() )
			->method( 'doSectionTransaction' )
			->willThrowException( new DBError( null, 'Lock wait timeout exceeded' ) );

		$instance = $this->newPropertyTableUpdater();
		$instance->update( 42, $this->changedValue() );
		$instance->writeSharedRowsOnCommit();
		$this->commit();

		$this->assertSame( [ LogLevel::WARNING ], array_column( $this->logger->getLogs(), 0 ) );
	}

	public function testUpdate_WithInsertRowsButMissingIdFieldThrowsException() {
		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'table_foo' => $this->propertyTable ] );

		$instance = $this->newPropertyTableUpdater();

		$this->expectException( TableMissingIdFieldException::class );
		$instance->update( 42, $this->newParameters( [], [] ) );
	}

	private function newPropertyTableUpdater(): PropertyTableUpdater {
		$instance = new PropertyTableUpdater( $this->store, $this->propertyStatisticsStore, $this->logger );
		$instance->setPropertyChangeListener( $this->propertyChangeListener );

		return $instance;
	}

	/**
	 * Page 1001 changes its value of property 99998 to one of property 99999.
	 */
	private function changedValue(): Parameters {
		return $this->newParameters( [ [ 's_id' => 1001, 'p_id' => 99999 ] ], [ [ 's_id' => 1001, 'p_id' => 99998 ] ] );
	}

	private function newParameters( array $insertRows, array $deleteRows ): Parameters {
		return new Parameters(
			[
				'insert_rows' => [ 'table_foo' => $insertRows ],
				'delete_rows' => [ 'table_foo' => $deleteRows ],
				'new_hashes' => []
			]
		);
	}

	private function usePropertyTable(): void {
		$this->propertyTable->expects( $this->any() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$this->propertyTable->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'table_foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'table_foo' => $this->propertyTable ] );
	}

	private function stubWrites( array &$updateWheres = [] ): void {
		$updateTables = $updateSets = [];

		$this->connection->expects( $this->any() )
			->method( 'newInsertQueryBuilder' )
			->willReturn( $this->createMockInsertQueryBuilder() );

		$this->connection->expects( $this->any() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $this->createMockDeleteQueryBuilder() );

		$this->connection->expects( $this->any() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $this->createMockUpdateQueryBuilder( $updateTables, $updateSets, $updateWheres ) );
	}

	private function runsSectionTransactions(): void {
		$this->connection->expects( $this->any() )
			->method( 'doSectionTransaction' )
			->willReturnCallback( static fn ( string $fname, callable $callback ) => $callback() );
	}

	private function commit(): void {
		foreach ( $this->callbacksOnCommit as $callback ) {
			$callback();
		}
	}

}
