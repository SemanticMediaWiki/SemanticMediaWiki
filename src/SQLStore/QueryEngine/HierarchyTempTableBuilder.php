<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\MediaWiki\Database;
use SMW\SQLStore\TemporaryIdTableCreator;
use RuntimeException;

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
	 * @var TemporaryIdTableCreator
	 */
	private $tempIdTableCreator;

	/**
	 * Cache of computed hierarchy queries for reuse ("catetgory/property value
	 * string" => "tablename").
	 *
	 * @var string[]
	 */
	private $hierarchyCache = array();

	/**
	 * @var array
	 */
	private $hierarchyTypeTable = array();

	/**
	 * @since 2.3
	 *
	 * @param Database $connection
	 * @param TemporaryIdTableCreator $temporaryIdTableCreator
	 */
	public function __construct( Database $connection, TemporaryIdTableCreator $temporaryIdTableCreator ) {
		$this->connection = $connection;
		$this->tempIdTableCreator = $temporaryIdTableCreator;
	}

	/**
	 * @since 2.3
	 */
	public function emptyHierarchyCache() {
		$this->hierarchyCache = array();
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
	 * @param string $table
	 * @param integer $depth
	 */
	public function setPropertyHierarchyTableDefinition( $table, $depth ) {
		$this->hierarchyTypeTable['property'] = array( $this->connection->tableName( $table ), $depth );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $table
	 * @param integer $depth
	 */
	public function setClassHierarchyTableDefinition( $table, $depth ) {
		$this->hierarchyTypeTable['class'] = array( $this->connection->tableName( $table ), $depth );
	}

	/**
	 * @since 2.3
	 *
	 * @param string $type
	 *
	 * @return array
	 * @throws RuntimeException
	 */
	public function getHierarchyTableDefinitionForType( $type ) {

		if ( !isset( $this->hierarchyTypeTable[$type] ) ) {
			throw new RuntimeException( "$type is unknown" );
		}

		return $this->hierarchyTypeTable[$type];
	}

	/**
	 * @since 2.3
	 *
	 * @param string $type
	 * @param string $tablename
	 * @param string $valueComposite
	 *
	 * @throws RuntimeException
	 */
	public function createHierarchyTempTableFor( $type, $tablename, $valueComposite ) {

		list( $smwtable, $depth ) = $this->getHierarchyTableDefinitionForType( $type );

		if ( array_key_exists( $valueComposite, $this->hierarchyCache ) ) { // Just copy known result.

			$this->connection->query(
				"INSERT INTO $tablename (id) SELECT id" . ' FROM ' . $this->hierarchyCache[$valueComposite],
				__METHOD__
			);

			return;
		}

		$this->buildTempTableFor( $tablename, $valueComposite, $smwtable, $depth );
	}

	/**
	 * @note we use two helper tables. One holds the results of each new iteration, one holds the
	 * results of the previous iteration. One could of course do with only the above result table,
	 * but then every iteration would use all elements of this table, while only the new ones
	 * obtained in the previous step are relevant. So this is a performance measure.
	 */
	private function buildTempTableFor( $tablename, $values, $smwtable, $depth ) {

		$db = $this->connection;

		$tmpnew = 'smw_new';
		$tmpres = 'smw_res';

		$db->query(
			$this->tempIdTableCreator->getSqlToCreate( $tmpnew ),
			__METHOD__
		);

		$db->query(
			$this->tempIdTableCreator->getSqlToCreate( $tmpres ),
			__METHOD__
		);

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

		$db->query(
			'DROP TEMPORARY TABLE smw_new',
			__METHOD__
		);

		$db->query(
			'DROP TEMPORARY TABLE smw_res',
			__METHOD__
		);
	}

}
