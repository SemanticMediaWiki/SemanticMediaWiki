<?php

namespace SMW\SQLStore\QueryEngine;

use RuntimeException;
use SMW\MediaWiki\Database;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;

/**
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class HierarchyTempTableBuilder {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var TemporaryTableBuilder
	 */
	private $temporaryTableBuilder;

	/**
	 * Cache of computed hierarchy queries for reuse ("catetgory/property value
	 * string" => "tablename").
	 *
	 * @var string[]
	 */
	private $hierarchyCache = [];

	/**
	 * @var array
	 */
	private $tableDefinitions = [];

	/**
	 * @since 2.3
	 *
	 * @param Database $connection
	 * @param TemporaryTableBuilder $temporaryTableBuilder
	 */
	public function __construct( Database $connection, TemporaryTableBuilder $temporaryTableBuilder ) {
		$this->connection = $connection;
		$this->temporaryTableBuilder = $temporaryTableBuilder;
	}

	/**
	 * @since 2.3
	 */
	public function emptyHierarchyCache() {
		$this->hierarchyCache = [];
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getHierarchyCache() {
		return $this->hierarchyCache;
	}

	/**
	 * @since 2.3
	 *
	 * @param array $tableDefinitions
	 */
	public function setTableDefinitions( array $tableDefinitions ) {
		foreach ( $tableDefinitions as $key => $tableDefinition ) {
			$this->tableDefinitions[$key] = [
				$this->connection->tableName( $tableDefinition['table'] ),
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
	 * @param string $type
	 * @param string $tablename
	 * @param string $valueComposite
	 * @param integer|null $depth
	 *
	 * @throws RuntimeException
	 */
	public function fillTempTable( $type, $tablename, $valueComposite, $depth = null ) {

		$this->temporaryTableBuilder->create( $tablename );

		list( $smwtable, $d ) = $this->getTableDefinitionByType( $type );

		if ( $depth === null ) {
			$depth = $d;
		}

		if ( array_key_exists( $valueComposite, $this->hierarchyCache ) ) { // Just copy known result.

			$this->connection->query(
				"INSERT INTO $tablename (id) SELECT id" . ' FROM ' . $this->hierarchyCache[$valueComposite],
				__METHOD__
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
	private function buildTempTable( $tablename, $values, $smwtable, $depth ) {

		$db = $this->connection;

		$tmpnew = 'smw_new';
		$tmpres = 'smw_res';

		$this->temporaryTableBuilder->create( $tmpnew );
		$this->temporaryTableBuilder->create( $tmpres );

		// Adding multiple values for the same column in sqlite is not supported
		foreach ( explode( ',', $values ) as $value ) {

			$db->query(
				"INSERT " . "IGNORE" . " INTO $tablename (id) VALUES $value",
				__METHOD__
			);

			$db->query(
				"INSERT " . "IGNORE" . " INTO $tmpnew (id) VALUES $value",
				__METHOD__
			);
		}

		for ( $i = 0; $i < $depth; $i++ ) {
			$db->query(
				"INSERT " . 'IGNORE ' .  "INTO $tmpres (id) SELECT s_id" . '@INT' . " FROM $smwtable, $tmpnew WHERE o_id=id",
				__METHOD__
			);

			if ( $db->affectedRows() == 0 ) { // no change, exit loop
				break;
			}

			$db->query(
				"INSERT " . 'IGNORE ' . "INTO $tablename (id) SELECT $tmpres.id FROM $tmpres",
				__METHOD__
			);

			if ( $db->affectedRows() == 0 ) { // no change, exit loop
				break;
			}

			// empty "new" table
			$db->query(
				'TRUNCATE TABLE ' . $tmpnew,
				__METHOD__
			);

			$tmpname = $tmpnew;
			$tmpnew = $tmpres;
			$tmpres = $tmpname;
		}

		$this->hierarchyCache[$values] = $tablename;

		$this->temporaryTableBuilder->drop( $tmpnew );
		$this->temporaryTableBuilder->drop( $tmpres );
	}

}
