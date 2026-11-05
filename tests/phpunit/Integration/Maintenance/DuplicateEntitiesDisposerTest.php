<?php

namespace SMW\Tests\Integration\Maintenance;

use RuntimeException;
use SMW\Maintenance\DuplicateEntitiesDisposer;
use SMW\Services\ServicesFactory;
use SMW\SQLStore\RedirectStore;
use SMW\SQLStore\SQLStore;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Utils\PeriodicStatsFlusher;
use Wikimedia\Stats\StatsFactory;

/**
 * Needs the database, as the disposer finds and replaces duplicated rows in the store's tables.
 *
 * @covers \SMW\Maintenance\DuplicateEntitiesDisposer
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class DuplicateEntitiesDisposerTest extends SMWIntegrationTestCase {

	private const MORE_THAN_ONE_BATCH = 1001;

	/**
	 * @dataProvider tableProvider
	 */
	public function testKeepsOneCopyOfEachDuplicatedRow( string $table, callable $newRow ): void {
		$rows = $this->insertDuplicatedRows( $table, $newRow, 2 );

		$this->dispose( $this->newDisposer(), $table );

		$this->assertSame( [ 1, 1 ], $this->countCopies( $table, $rows ) );
	}

	/**
	 * @dataProvider tableProvider
	 */
	public function testKeepsOneCopyOfRowsBeyondTheFirstBatch( string $table, callable $newRow ): void {
		$rows = $this->insertDuplicatedRows( $table, $newRow, self::MORE_THAN_ONE_BATCH );

		$this->dispose( $this->newDisposer(), $table );

		$this->assertSame( self::MORE_THAN_ONE_BATCH, $this->countRows( $table, $rows ) );
	}

	/**
	 * @dataProvider tableProvider
	 */
	public function testKeepsEveryRowWhenTheDisposalStopsPartway( string $table, callable $newRow ): void {
		$rows = $this->insertDuplicatedRows( $table, $newRow, 2 );
		$disposer = $this->newDisposer();
		$disposer->setStatsFlusher( $this->newStatsFlusherFailingOnSecondTick() );

		$this->assertSame( 'Stopped', $this->disposeUntilStopped( $disposer, $table ) );
		$this->assertSame( [ 2, 2 ], $this->countCopies( $table, $rows ) );
	}

	public static function tableProvider(): iterable {
		yield 'property values' => [
			'smw_di_wikipage',
			static fn ( int $id ) => [ 's_id' => $id, 'p_id' => $id, 'o_id' => $id ]
		];

		yield 'redirects' => [
			RedirectStore::TABLE_NAME,
			static fn ( int $id ) => [ 's_title' => "Redirect_$id", 's_namespace' => NS_MAIN, 'o_id' => $id ]
		];
	}

	private function newDisposer(): DuplicateEntitiesDisposer {
		return ServicesFactory::getInstance()->newMaintenanceFactory()->newDuplicateEntitiesDisposer( $this->getStore() );
	}

	/**
	 * Disposes of the duplicates in the given table only, so that duplicates elsewhere cannot affect the test
	 */
	private function dispose( DuplicateEntitiesDisposer $disposer, string $table ): void {
		$disposer->verifyAndDispose( [ $table => $disposer->findDuplicates()[$table] ] );
	}

	/**
	 * @return string The message of the exception that stopped the disposal
	 */
	private function disposeUntilStopped( DuplicateEntitiesDisposer $disposer, string $table ): string {
		try {
			$this->dispose( $disposer, $table );
		} catch ( RuntimeException $e ) {
			return $e->getMessage();
		}

		return 'Not stopped';
	}

	/**
	 * Stops the disposal after it has handled the first duplicated row
	 */
	private function newStatsFlusherFailingOnSecondTick(): PeriodicStatsFlusher {
		return new class( StatsFactory::newNull() ) extends PeriodicStatsFlusher {

			private int $ticks = 0;

			public function tick(): void {
				$this->ticks++;

				if ( $this->ticks === 2 ) {
					throw new RuntimeException( 'Stopped' );
				}
			}

		};
	}

	/**
	 * @return array[] The rows, each inserted twice
	 */
	private function insertDuplicatedRows( string $table, callable $newRow, int $count ): array {
		$rows = array_map( $newRow, $this->insertEntities( $count ) );

		$this->insertRows( $table, array_merge( $rows, $rows ) );

		return $rows;
	}

	/**
	 * @return int[] The IDs of the new entities
	 */
	private function insertEntities( int $count ): array {
		$connection = $this->getStore()->getConnection( 'mw.db' );
		$lastId = (int)$connection->newSelectQueryBuilder()
			->select( 'MAX(smw_id)' )
			->from( SQLStore::ID_TABLE )
			->caller( __METHOD__ )
			->fetchField();

		$entities = [];

		for ( $i = 1; $i <= $count; $i++ ) {
			$entities[] = [
				'smw_namespace' => NS_MAIN,
				'smw_title' => "Duplicate_$i",
				'smw_iw' => '',
				'smw_subobject' => '',
				'smw_sortkey' => "Duplicate_$i",
				'smw_sort' => "Duplicate_$i"
			];
		}

		$this->insertRows( SQLStore::ID_TABLE, $entities );

		return array_map( 'intval', $connection->newSelectQueryBuilder()
			->select( 'smw_id' )
			->from( SQLStore::ID_TABLE )
			->where( $connection->expr( 'smw_id', '>', $lastId ) )
			->caller( __METHOD__ )
			->fetchFieldValues() );
	}

	private function insertRows( string $table, array $rows ): void {
		$this->getStore()->getConnection( 'mw.db' )->newInsertQueryBuilder()
			->insertInto( $table )
			->rows( $rows )
			->caller( __METHOD__ )
			->execute();
	}

	/**
	 * @return int[] The number of copies of each row
	 */
	private function countCopies( string $table, array $rows ): array {
		return array_map( fn ( array $row ) => $this->countWhere( $table, $row ), $rows );
	}

	/**
	 * @return int The number of rows that refer to the rows' entities
	 */
	private function countRows( string $table, array $rows ): int {
		return $this->countWhere( $table, [ 'o_id' => array_column( $rows, 'o_id' ) ] );
	}

	private function countWhere( string $table, array $conditions ): int {
		return (int)$this->getStore()->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( $table )
			->where( $conditions )
			->caller( __METHOD__ )
			->fetchField();
	}

}
