<?php

namespace SMW\SQLStore\QueryEngine;

use RuntimeException;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;
use Wikimedia\Rdbms\IDatabase;

/**
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class HierarchyTempTableBuilder {

	/**
	 * Cache of computed hierarchy queries for reuse ("catetgory/property value
	 * string" => "tablename").
	 *
	 * @var string[]
	 */
	private $hierarchyCache = [];

	/**
	 * Hierarchy-table definitions keyed by type (`'class'` / `'property'`),
	 * each entry shaped as `[ bareTableName, depth ]`. Names are stored
	 * bare; QueryBuilder consumers (insertSelect, newSelectQueryBuilder)
	 * apply the prefix internally.
	 */
	private array $tableDefinitions = [];

	/**
	 * @since 2.3
	 */
	public function __construct(
		private readonly Database $connection,
		private readonly TemporaryTableBuilder $temporaryTableBuilder,
	) {
	}

	/**
	 * @since 2.3
	 */
	public function emptyHierarchyCache(): void {
		$this->hierarchyCache = [];
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getHierarchyCache(): array {
		return $this->hierarchyCache;
	}

	/**
	 * @since 2.3
	 *
	 * @param array $tableDefinitions
	 */
	public function setTableDefinitions( array $tableDefinitions ): void {
		foreach ( $tableDefinitions as $key => $tableDefinition ) {
			$this->tableDefinitions[$key] = [
				$tableDefinition['table'],
				$tableDefinition['depth']
			];
		}
	}

	/**
	 * @since 2.3
	 *
	 * @param string $type
	 *
	 * @return array
	 * @throws RuntimeException
	 */
	public function getTableDefinitionByType( $type ) {
		if ( !isset( $this->tableDefinitions[$type] ) ) {
			throw new RuntimeException( "$type is unknown" );
		}

		return $this->tableDefinitions[$type];
	}

	/**
	 * @since 2.3
	 *
	 * @throws RuntimeException
	 */
	public function fillTempTable( string $type, string $tablename, string $valueComposite, ?int $depth = null ): void {
		$this->temporaryTableBuilder->create( $tablename );

		[ $smwtable, $d ] = $this->getTableDefinitionByType( $type );

		if ( $depth === null ) {
			$depth = $d;
		}

		if ( array_key_exists( $valueComposite, $this->hierarchyCache ) ) { // Just copy known result.

			// No IGNORE: the cache source is already deduped, matching legacy
			// SQL semantics (a plain INSERT...SELECT with no conflict clause).
			$this->connection->insertSelect(
				$tablename,
				$this->hierarchyCache[$valueComposite],
				[ 'id' => 'id' ],
				'*',
				__METHOD__,
				[],
				[],
				[]
			);

			return;
		}

		$this->buildTempTable( $tablename, $valueComposite, $smwtable, $depth );
	}

	/**
	 * @note we use two helper tables. One holds the results of each new iteration, one holds the
	 * results of the previous iteration. One could of course do with only the above result table,
	 * but then every iteration would use all elements of this table, while only the new ones
	 * obtained in the previous step are relevant. So this is a performance measure.
	 */
	private function buildTempTable( string $tablename, string $values, string $smwtable, int $depth ): void {
		$db = $this->connection;

		$tmpnew = 'smw_new';
		$tmpres = 'smw_res';

		$this->temporaryTableBuilder->create( $tmpnew );
		$this->temporaryTableBuilder->create( $tmpres );

		// Seed both temp tables with the supplied id values. `$values` is a
		// comma-joined string of parenthesised single literals like
		// '(123),(456)'. Use one builder per destination, accumulate rows
		// via row(), then a single execute() per builder. The IGNORE option
		// produces platform-correct INSERT IGNORE / OR IGNORE / ON CONFLICT
		// DO NOTHING automatically.
		$tablenameQb = $db->newInsertQueryBuilder()
			->insertInto( $tablename )
			->ignore()
			->caller( __METHOD__ );
		$tmpnewQb = $db->newInsertQueryBuilder()
			->insertInto( $tmpnew )
			->ignore()
			->caller( __METHOD__ );

		foreach ( explode( ',', $values ) as $value ) {
			// $value looks like '(123)'. Strip parens/spaces to get the int id.
			$id = (int)trim( $value, '() ' );
			$tablenameQb->row( [ 'id' => $id ] );
			$tmpnewQb->row( [ 'id' => $id ] );
		}

		$tablenameQb->execute();
		$tmpnewQb->execute();

		for ( $i = 0; $i < $depth; $i++ ) {
			// INSERT IGNORE INTO $tmpres (id)
			//   SELECT CAST(s_id AS INTEGER) FROM $smwtable, $tmpnew WHERE o_id=id
			$db->insertSelect(
				$tmpres,
				[ $smwtable, $tmpnew ],
				[ 'id' => 'CAST(s_id AS INTEGER)' ],
				[ 'o_id=id' ],
				__METHOD__,
				[ 'IGNORE' ],
				[],
				[]
			);

			if ( $db->affectedRows() == 0 ) { // no change, exit loop
				break;
			}

			// INSERT IGNORE INTO $tablename (id) SELECT $tmpres.id FROM $tmpres
			$db->insertSelect(
				$tablename,
				$tmpres,
				[ 'id' => 'id' ],
				'*',
				__METHOD__,
				[ 'IGNORE' ],
				[],
				[]
			);

			if ( $db->affectedRows() == 0 ) { // no change, exit loop
				break;
			}

			$db->newDeleteQueryBuilder()
				->deleteFrom( $tmpnew )
				->where( IDatabase::ALL_ROWS )
				->caller( __METHOD__ )
				->execute();

			$tmpname = $tmpnew;
			$tmpnew = $tmpres;
			$tmpres = $tmpname;
		}

		$this->hierarchyCache[$values] = $tablename;

		$this->temporaryTableBuilder->drop( $tmpnew );
		$this->temporaryTableBuilder->drop( $tmpres );
	}

}
