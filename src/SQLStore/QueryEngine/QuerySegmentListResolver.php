<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\MediaWiki\Database;
use SMW\SQLStore\TemporaryIdTableCreator;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMWQuery as Query;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class QuerySegmentListResolver {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var TemporaryIdTableCreator
	 */
	private $tempIdTableCreator;

	/**
	 * @var ResolverOptions
	 */
	private $resolverOptions;

	/**
	 * Array of arrays of executed queries, indexed by the temporary table names
	 * results were fed into.
	 *
	 * @var array
	 */
	private $executedQueries = array();

	/**
	 * Cache of computed hierarchy queries for reuse ("catetgory/property value
	 * string" => "tablename").
	 *
	 * @var string[]
	 */
	private $hierarchyCache = array();

	/**
	 * Query mode copied from given query. Some submethods act differently when
	 * in Query::MODE_DEBUG.
	 *
	 * @var int
	 */
	private $queryMode;

	/**
	 * @var array
	 */
	private $querySegments = array();

	/**
	 * @param Database $connection
	 * @param TemporaryIdTableCreator $temporaryIdTableCreator
	 * @param ResolverOptions $resolverOptions
	 */
	public function __construct( Database $connection, TemporaryIdTableCreator $temporaryIdTableCreator, ResolverOptions $resolverOptions ) {
		$this->connection = $connection;
		$this->tempIdTableCreator = $temporaryIdTableCreator;
		$this->resolverOptions = $resolverOptions;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getListOfResolvedQueries() {
		return $this->executedQueries;
	}

	/**
	 * @since 2.2
	 *
	 * @param &$querySegments
	 */
	public function setQuerySegmentList( &$querySegments ) {
		$this->querySegments =& $querySegments;
	}

	/**
	 * @since 2.2
	 *
	 * @param integer
	 */
	public function setQueryMode( $queryMode ) {
		$this->queryMode = $queryMode;
	}

	/**
	 * Process stored queries and change store accordingly. The query obj is modified
	 * so that it contains non-recursive description of a select to execute for getting
	 * the actual result.
	 *
	 * @param integer $id
	 * @throws RuntimeException
	 */
	public function resolveForSegmentId( $id ) {

		$this->hierarchyCache = array();
		$this->executedQueries = array();

		// Should never happen
		if ( !isset( $this->querySegments[$id] ) ) {
			throw new RuntimeException( "$id doesn't exist" );
		}

		$this->resolveForSegment( $this->querySegments[$id] );
	}

	/**
	 * Process stored queries and change store accordingly. The query obj is modified
	 * so that it contains non-recursive description of a select to execute for getting
	 * the actual result.
	 *
	 * @param QuerySegment $query
	 */
	public function resolveForSegment( QuerySegment &$query ) {

		$db = $this->connection;

		switch ( $query->type ) {
			case QuerySegment::Q_TABLE: // Normal query with conjunctive subcondition.
				foreach ( $query->components as $qid => $joinField ) {
					$subQuery = $this->querySegments[$qid];
					$this->resolveForSegment( $subQuery );

					if ( $subQuery->joinTable !== '' ) { // Join with jointable.joinfield
						$query->from .= ' INNER JOIN ' . $db->tableName( $subQuery->joinTable ) . " AS $subQuery->alias ON $joinField=" . $subQuery->joinfield;
					} elseif ( $subQuery->joinfield !== '' ) { // Require joinfield as "value" via WHERE.
						$condition = '';

						foreach ( $subQuery->joinfield as $value ) {
							$condition .= ( $condition ? ' OR ':'' ) . "$joinField=" . $db->addQuotes( $value );
						}

						if ( count( $subQuery->joinfield ) > 1 ) {
							$condition = "($condition)";
						}

						$query->where .= ( ( $query->where === '' ) ? '':' AND ' ) . $condition;
					} else { // interpret empty joinfields as impossible condition (empty result)
						$query->joinfield = ''; // make whole query false
						$query->joinTable = '';
						$query->where = '';
						$query->from = '';
						break;
					}

					if ( $subQuery->where !== '' ) {
						$query->where .= ( ( $query->where === '' ) ? '':' AND ' ) . '(' . $subQuery->where . ')';
					}

					$query->from .= $subQuery->from;
				}

				$query->components = array();
			break;
			case QuerySegment::Q_CONJUNCTION:
				// pick one subquery with jointable as anchor point ...
				reset( $query->components );
				$key = false;

				foreach ( $query->components as $qkey => $qid ) {
					if ( $this->querySegments[$qkey]->joinTable !== '' ) {
						$key = $qkey;
						break;
					}
				}

				if ( $key !== false ) {
					$result = $this->querySegments[$key];
					unset( $query->components[$key] );

					// Execute it first (may change jointable and joinfield, e.g. when making temporary tables)
					$this->resolveForSegment( $result );

					// ... and append to this query the remaining queries.
					foreach ( $query->components as $qid => $joinField ) {
						$result->components[$qid] = $result->joinfield;
					}

					// Second execute, now incorporating remaining conditions.
					$this->resolveForSegment( $result );
				} else { // Only fixed values in conjunction, make a new value without joining.
					$key = $qkey;
					$result = $this->querySegments[$key];
					unset( $query->components[$key] );

					foreach ( $query->components as $qid => $joinField ) {
						if ( $result->joinfield != $this->querySegments[$qid]->joinfield ) {
							$result->joinfield = ''; // all other values should already be ''
							break;
						}
					}
				}
				$query = $result;
			break;
			case QuerySegment::Q_DISJUNCTION:
				if ( $this->queryMode !== Query::MODE_DEBUG ) {
					$db->query(
						$this->getCreateTempIDTableSQL( $db->tableName( $query->alias ) ),
						__METHOD__
					);
				}

				$this->executedQueries[$query->alias] = array();

				foreach ( $query->components as $qid => $joinField ) {
					$subQuery = $this->querySegments[$qid];
					$this->resolveForSegment( $subQuery );
					$sql = '';

					if ( $subQuery->joinTable !== '' ) {
						$sql = 'INSERT ' . 'IGNORE ' . 'INTO ' .
						       $db->tableName( $query->alias ) .
							   " SELECT $subQuery->joinfield FROM " . $db->tableName( $subQuery->joinTable ) .
							   " AS $subQuery->alias $subQuery->from" . ( $subQuery->where ? " WHERE $subQuery->where":'' );
					} elseif ( $subQuery->joinfield !== '' ) {
						// NOTE: this works only for single "unconditional" values without further
						// WHERE or FROM. The execution must take care of not creating any others.
						$values = '';

						foreach ( $subQuery->joinfield as $value ) {
							$values .= ( $values ? ',' : '' ) . '(' . $db->addQuotes( $value ) . ')';
						}

						$sql = 'INSERT ' . 'IGNORE ' .  'INTO ' . $db->tableName( $query->alias ) . " (id) VALUES $values";
					} // else: // interpret empty joinfields as impossible condition (empty result), ignore
					if ( $sql ) {
						$this->executedQueries[$query->alias][] = $sql;

						if ( $this->queryMode !== Query::MODE_DEBUG ) {
							$db->query(
								$sql,
								__METHOD__
							);
						}
					}
				}

				$query->joinTable = $query->alias;
				$query->joinfield = "$query->alias.id";
				$query->sortfields = array(); // Make sure we got no sortfields.
				// TODO: currently this eliminates sortkeys, possibly keep them (needs different temp table format though, maybe not such a good thing to do)
			break;
			case QuerySegment::Q_PROP_HIERARCHY:
			case QuerySegment::Q_CLASS_HIERARCHY: // make a saturated hierarchy
				$this->resolveHierarchyForSegment( $query );
			break;
			case QuerySegment::Q_VALUE:
			break; // nothing to do
		}
	}

	/**
	 * Find subproperties or subcategories. This may require iterative computation,
	 * and temporary tables are used in many cases.
	 *
	 * @param QuerySegment $query
	 */
	private function resolveHierarchyForSegment( QuerySegment &$query ) {

		$db = $this->connection;
		$hierarchytables = $this->resolverOptions->get( 'hierarchytables' );

		if ( $query->type === QuerySegment::Q_PROP_HIERARCHY ) {
			$depth = $this->resolverOptions->get( 'smwgQSubpropertyDepth' );
			$smwtable = $db->tableName( $hierarchytables['_SUBP'] );
		} else {
			$depth = $this->resolverOptions->get( 'smwgQSubcategoryDepth' );
			$smwtable = $db->tableName( $hierarchytables['_SUBC'] );
		}

		if ( $depth <= 0 ) { // treat as value, no recursion
			$query->type = QuerySegment::Q_VALUE;
			return;
		}

		$values = '';
		$valuecond = '';

		foreach ( $query->joinfield as $value ) {
			$values .= ( $values ? ',':'' ) . '(' . $db->addQuotes( $value ) . ')';
			$valuecond .= ( $valuecond ? ' OR ':'' ) . 'o_id=' . $db->addQuotes( $value );
		}

		// Try to safe time (SELECT is cheaper than creating/dropping 3 temp tables):
		$res = $db->select( $smwtable, 's_id', $valuecond, __METHOD__, array( 'LIMIT' => 1 ) );

		if ( !$db->fetchObject( $res ) ) { // no subobjects, we are done!
			$db->freeResult( $res );
			$query->type = QuerySegment::Q_VALUE;
			return;
		}

		$db->freeResult( $res );
		$tablename = $db->tableName( $query->alias );
		$this->executedQueries[$query->alias] = array( "Recursively computed hierarchy for element(s) $values." );
		$query->joinTable = $query->alias;
		$query->joinfield = "$query->alias.id";

		if ( $this->queryMode == Query::MODE_DEBUG ) {
			return; // No real queries in debug mode.
		}

		$db->query(
			$this->getCreateTempIDTableSQL( $tablename ),
			__METHOD__
		);

		if ( array_key_exists( $values, $this->hierarchyCache ) ) { // Just copy known result.

			$db->query(
				"INSERT INTO $tablename (id) SELECT id" . ' FROM ' . $this->hierarchyCache[$values],
				__METHOD__
			);

			return;
		}

		$this->fillHierarchyCacheForTableId( $tablename, $values, $smwtable, $depth );
	}

	/**
	 * @note we use two helper tables. One holds the results of each new iteration, one holds the
	 * results of the previous iteration. One could of course do with only the above result table,
	 * but then every iteration would use all elements of this table, while only the new ones
	 * obtained in the previous step are relevant. So this is a performance measure.
	 */
	private function fillHierarchyCacheForTableId( $tablename, $values, $smwtable, $depth ) {

		$db = $this->connection;

		$tmpnew = 'smw_new';
		$tmpres = 'smw_res';

		$db->query(
			$this->getCreateTempIDTableSQL( $tmpnew ),
			__METHOD__
		);

		$db->query(
			$this->getCreateTempIDTableSQL( $tmpres ),
			__METHOD__
		);

		$db->query(
			"INSERT " . "IGNORE" . " INTO $tablename (id) VALUES $values",
			__METHOD__
		);

		$db->query(
			"INSERT " . "IGNORE" . " INTO $tmpnew (id) VALUES $values",
			__METHOD__
		);

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

	/**
	 * After querying, make sure no temporary database tables are left.
	 * @todo I might be better to keep the tables and possibly reuse them later
	 * on. Being temporary, the tables will vanish with the session anyway.
	 */
	public function cleanUp() {

		if ( $this->queryMode  === Query::MODE_DEBUG ) {
			return;
		}

		foreach ( $this->executedQueries as $table => $log ) {
			$this->connection->query(
				"DROP TEMPORARY TABLE " . $this->connection->tableName( $table ),
				__METHOD__
			);
		}
	}

	/**
	 * Get SQL code suitable to create a temporary table of the given name, used to store ids.
	 * MySQL can do that simply by creating new temporary tables. PostgreSQL first checks if such
	 * a table exists, so the code is ready to reuse existing tables if the code was modified to
	 * keep them after query answering. Also, PostgreSQL tables will use a RULE to achieve built-in
	 * duplicate elimination. The latter is done using INSERT IGNORE in MySQL.
	 *
	 * @param string $tableName
	 *
	 * @return string
	 */
	private function getCreateTempIDTableSQL( $tableName ) {
		return $this->tempIdTableCreator->getSqlToCreate( $tableName );
	}

}
