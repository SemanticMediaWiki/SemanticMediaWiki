<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\SQLStore\PropertyStatisticsStore;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Utils\CliMsgFormatter;
use SMW\Utils\PeriodicStatsFlusher;
use stdClass;

/**
 * Simple class for rebuilding property usage statistics.
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Nischay Nahata
 */
class PropertyStatisticsRebuilder {

	private const BATCH_SIZE = 1000;

	private MessageReporter $messageReporter;

	private ?PeriodicStatsFlusher $statsFlusher = null;

	/**
	 * @since 1.9
	 */
	public function __construct(
		private readonly Store $store,
		private readonly PropertyStatisticsStore $propertyStatisticsStore,
	) {
		$this->messageReporter = MessageReporterFactory::getInstance()->newNullMessageReporter();
	}

	/**
	 * @since  2.2
	 */
	public function setMessageReporter( MessageReporter $messageReporter ): void {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 7.2.0
	 */
	public function setStatsFlusher( PeriodicStatsFlusher $statsFlusher ): void {
		$this->statsFlusher = $statsFlusher;
	}

	/**
	 * @since 1.9
	 */
	public function rebuild(): void {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		$text = [
			"Rebuilding property usage statistics by reading all registered tables",
			"and accumulate property data from stored records.",
			"\n\n",
			"Depending on the size and amount of referenced properties in registered",
			"tables, the rebuild may take a moment."
		];

		$this->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"

		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'Statistics rebuild' )
		);

		$table = SQLStore::PROPERTY_STATISTICS_TABLE;

		$this->reportMessage( "\nRebulding the `$table` statistics table ...\n" );

		$this->reportMessage(
			$cliMsgFormatter->firstCol( "... deleting all rows ...", 3 )
		);

		$this->propertyStatisticsStore->deleteAll();

		$this->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$propCount = $this->countProperties();

		$this->reportMessage(
			$cliMsgFormatter->twoCols( '... properties count:', "$propCount (rows)", 3 )
		);

		$i = 0;
		$rows = $this->fetchProperties( 0 );

		while ( $rows !== [] ) {
			$this->insertUsageCounts( $rows );
			$i += count( $rows );

			$this->reportMessage(
				$cliMsgFormatter->twoColsOverride( '... updating ...', $cliMsgFormatter->progressCompact( $i, $propCount ), 3 )
			);

			$rows = $this->fetchProperties( (int)end( $rows )->smw_id );
		}

		$this->reportMessage( "\n   ... done.\n" );
	}

	private function countProperties(): int {
		return (int)$this->store->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( SQLStore::ID_TABLE )
			->where( $this->propertyConditions() )
			->caller( __METHOD__ )
			->fetchField();
	}

	/**
	 * @return stdClass[] The next batch of properties after the given ID, in ID order
	 */
	private function fetchProperties( int $afterId ): array {
		$connection = $this->store->getConnection( 'mw.db' );

		return iterator_to_array( $connection->newSelectQueryBuilder()
			->select( [ 'smw_id', 'smw_title' ] )
			->from( SQLStore::ID_TABLE )
			->where( $this->propertyConditions() )
			->andWhere( $connection->expr( 'smw_id', '>', $afterId ) )
			->orderBy( 'smw_id' )
			->limit( self::BATCH_SIZE )
			->caller( __METHOD__ )
			->fetchResultSet() );
	}

	private function propertyConditions(): array {
		return [
			'smw_namespace' => SMW_NS_PROPERTY,
			'smw_subobject' => ''
		];
	}

	/**
	 * Counts a batch just before writing it, as edits in between would be lost: they only update existing rows
	 *
	 * @param stdClass[] $rows
	 */
	private function insertUsageCounts( array $rows ): void {
		$counts = $this->countValues( $rows );
		$connection = $this->store->getConnection( 'mw.db' );

		// One commit per batch, since each commit waits for a disk sync
		$connection->doSectionTransaction( __METHOD__, function () use ( $rows, $counts ): void {
			foreach ( $rows as $row ) {
				if ( $this->statsFlusher !== null ) {
					$this->statsFlusher->tick();
				}

				$this->propertyStatisticsStore->insertUsageCount(
					(int)$row->smw_id,
					$counts[(int)$row->smw_id] ?? [ 0, 0 ]
				);
			}
		} );
	}

	/**
	 * @param stdClass[] $rows
	 *
	 * @return array<int, int[]> The usage and null count of each property's values, by property ID
	 */
	private function countValues( array $rows ): array {
		$ids = array_map( static fn ( stdClass $row ) => (int)$row->smw_id, $rows );
		$counts = [];

		foreach ( $this->store->getPropertyTables() as $propertyTable ) {
			if ( $propertyTable->isFixedPropertyTable() ) {
				// A fixed property table holds the values of the property whose key matches
				foreach ( $rows as $row ) {
					if ( $row->smw_title === $propertyTable->getFixedProperty() ) {
						$counts[(int)$row->smw_id] = $this->addCounts(
							$counts[(int)$row->smw_id] ?? [ 0, 0 ],
							$this->countFixedTable( $propertyTable )
						);
					}
				}

				continue;
			}

			foreach ( $this->countByProperty( $propertyTable, $ids ) as $pid => $tableCounts ) {
				$counts[$pid] = $this->addCounts( $counts[$pid] ?? [ 0, 0 ], $tableCounts );
			}
		}

		return $counts;
	}

	/**
	 * @param PropertyTableDefinition $propertyTable
	 * @param int[] $ids
	 *
	 * @return array<int, int[]> The usage and null count by property ID
	 */
	private function countByProperty( PropertyTableDefinition $propertyTable, array $ids ): array {
		$nullCounts = $this->fetchCountsByProperty(
			$propertyTable->getName(),
			array_merge( [ 'p_id' => $ids ], $this->nullConditions( $propertyTable ) )
		);

		$counts = [];

		foreach ( $this->fetchCountsByProperty( $propertyTable->getName(), [ 'p_id' => $ids ] ) as $pid => $total ) {
			$counts[$pid] = $this->toCounts( $total, $nullCounts[$pid] ?? 0 );
		}

		return $counts;
	}

	/**
	 * @return int[] The usage and null count of the table's values
	 */
	private function countFixedTable( PropertyTableDefinition $propertyTable ): array {
		return $this->toCounts(
			$this->fetchCount( $propertyTable->getName(), [] ),
			$this->fetchCount( $propertyTable->getName(), $this->nullConditions( $propertyTable ) )
		);
	}

	/**
	 * Rows whose value fields are all NULL, such as a blob row that only uses the hash field, count as nulls
	 * rather than usages.
	 *
	 * @return string[]
	 */
	private function nullConditions( PropertyTableDefinition $propertyTable ): array {
		$fields = $this->store->getDataItemHandlerForDIType( $propertyTable->getDiType() )->getTableFields();

		return array_map( static fn ( string $field ) => "$field IS NULL", array_keys( $fields ) );
	}

	/**
	 * @return array<int, int> The number of rows by property ID
	 */
	private function fetchCountsByProperty( string $tableName, array $conditions ): array {
		$rows = $this->store->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( [ 'p_id', 'count' => 'COUNT(*)' ] )
			->from( $tableName )
			->where( $conditions )
			->groupBy( 'p_id' )
			->caller( __METHOD__ )
			->fetchResultSet();

		$counts = [];

		foreach ( $rows as $row ) {
			$counts[(int)$row->p_id] = (int)$row->count;
		}

		return $counts;
	}

	private function fetchCount( string $tableName, array $conditions ): int {
		return (int)$this->store->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( 'COUNT(*)' )
			->from( $tableName )
			->where( $conditions )
			->caller( __METHOD__ )
			->fetchField();
	}

	/**
	 * @return int[] The usage and null count
	 */
	private function toCounts( int $total, int $nullCount ): array {
		return [ $total - $nullCount, $nullCount ];
	}

	/**
	 * @param int[] $counts
	 * @param int[] $moreCounts
	 *
	 * @return int[]
	 */
	private function addCounts( array $counts, array $moreCounts ): array {
		return [ $counts[0] + $moreCounts[0], $counts[1] + $moreCounts[1] ];
	}

	protected function reportMessage( $message ): void {
		$this->messageReporter->reportMessage( $message );
	}

}
