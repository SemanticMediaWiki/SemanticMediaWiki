<?php

namespace SMW\SQLStore\QueryEngine;

use RuntimeException;
use SMW\MediaWiki\Database;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;
use SMWQuery as Query;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class QuerySegmentListProcessor {

	// ConditionTreeProcessor

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var TemporaryTableBuilder
	 */
	private $temporaryTableBuilder;

	/**
	 * @var HierarchyTempTableBuilder
	 */
	private $hierarchyTempTableBuilder;

	/**
	 * Array of arrays of executed queries, indexed by the temporary table names
	 * results were fed into.
	 *
	 * @var array
	 */
	private $executedQueries = [];

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
	private $querySegmentList = [];

	/**
	 * @param Database $connection
	 * @param TemporaryTableBuilder $temporaryTableBuilder
	 * @param HierarchyTempTableBuilder $hierarchyTempTableBuilder
	 */
	public function __construct( Database $connection, TemporaryTableBuilder $temporaryTableBuilder, HierarchyTempTableBuilder $hierarchyTempTableBuilder ) {
		$this->connection = $connection;
		$this->temporaryTableBuilder = $temporaryTableBuilder;
		$this->hierarchyTempTableBuilder = $hierarchyTempTableBuilder;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getExecutedQueries() {
		return $this->executedQueries;
	}

	/**
	 * @since 2.2
	 *
	 * @param &$querySegmentList
	 */
	public function setQuerySegmentList( &$querySegmentList ) {
		$this->querySegmentList =& $querySegmentList;
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
	public function process( $id ) {

		$this->hierarchyTempTableBuilder->emptyHierarchyCache();
		$this->executedQueries = [];

		// Should never happen
		if ( !isset( $this->querySegmentList[$id] ) ) {
			throw new RuntimeException( "$id doesn't exist" );
		}

		$this->segment( $this->querySegmentList[$id] );
	}

	private function segment( QuerySegment &$query ) {

		switch ( $query->type ) {
			case QuerySegment::Q_TABLE: // .
				$this->table( $query );
			break;
			case QuerySegment::Q_CONJUNCTION:
				$this->conjunction( $query );
			break;
			case QuerySegment::Q_DISJUNCTION:
				$this->disjunction( $query );
			break;
			case QuerySegment::Q_PROP_HIERARCHY:
			case QuerySegment::Q_CLASS_HIERARCHY: // make a saturated hierarchy
				$this->hierarchy( $query );
			break;
			case QuerySegment::Q_VALUE:
			break; // nothing to do
		}
	}

	/**
	 * Resolves normal queries with possible conjunctive subconditions
	 */
	private function table( QuerySegment &$query ) {

		foreach ( $query->components as $qid => $joinField ) {
			$subQuery = $this->querySegmentList[$qid];
			$this->segment( $subQuery );

			if ( $subQuery->joinTable !== '' ) { // Join with jointable.joinfield
				$op = $subQuery->not ? '!' : '';

				$joinType = $subQuery->joinType ? $subQuery->joinType : 'INNER';
				$t = $this->connection->tableName( $subQuery->joinTable ) ." AS $subQuery->alias";

				if ( $subQuery->from ) {
					$t = "($t $subQuery->from)";
				}

				$query->from .= " $joinType JOIN $t ON $joinField$op=" . $subQuery->joinfield;

				if ( $joinType === 'LEFT' ) {
					$query->where .= ( ( $query->where === '' ) ? '' : ' AND ' ) . '(' . $subQuery->joinfield . ' IS NULL)';
				}

			} elseif ( $subQuery->joinfield !== '' ) { // Require joinfield as "value" via WHERE.
				$condition = '';

				if ( $subQuery->null === true ) {
						$condition .= ( $condition ? ' OR ': '' ) . "$joinField IS NULL";
				} else {
					foreach ( $subQuery->joinfield as $value ) {
						$op = $subQuery->not ? '!' : '';
						$condition .= ( $condition ? ' OR ': '' ) . "$joinField$op=" . $this->connection->addQuotes( $value );
					}
				}

				if ( count( $subQuery->joinfield ) > 1 ) {
					$condition = "($condition)";
				}

				$query->where .= ( ( $query->where === '' || $subQuery->where === null ) ? '' : ' AND ' ) . $condition;
				$query->from .= $subQuery->from;
			} else { // interpret empty joinfields as impossible condition (empty result)
				$query->joinfield = ''; // make whole query false
				$query->joinTable = '';
				$query->where = '';
				$query->from = '';
				break;
			}

			if ( $subQuery->where !== '' && $subQuery->where !== null ) {
				if ( $subQuery->joinType === 'LEFT' || $subQuery->joinType == 'LEFT OUTER' ) {
					$query->from .= ' AND (' . $subQuery->where . ')';
				} else {
					$query->where .= ( ( $query->where === '' ) ? '' : ' AND ' ) . '(' . $subQuery->where . ')';
				}
			}
		}

		$query->components = [];
	}

	private function conjunction( QuerySegment &$query ) {
		reset( $query->components );
		$key = false;

		// Pick one subquery as anchor point ...
		foreach ( $query->components as $qkey => $qid ) {
			$key = $qkey;

			if ( $this->querySegmentList[$qkey]->joinTable !== '' ) {
				break;
			}
		}

		$result = $this->querySegmentList[$key];
		unset( $query->components[$key] );

		// Execute it first (may change jointable and joinfield, e.g. when
		// making temporary tables)
		$this->segment( $result );

		// ... and append to this query the remaining queries.
		foreach ( $query->components as $qid => $joinfield ) {
			$result->components[$qid] = $result->joinfield;
		}

		// Second execute, now incorporating remaining conditions.
		$this->segment( $result );
		$query = $result;
	}

	private function disjunction( QuerySegment &$query ) {

		if ( $this->queryMode !== Query::MODE_NONE ) {
			$this->temporaryTableBuilder->create( $this->connection->tableName( $query->alias ) );
		}

		$this->executedQueries[$query->alias] = [];

		foreach ( $query->components as $qid => $joinField ) {
			$subQuery = $this->querySegmentList[$qid];
			$this->segment( $subQuery );
			$sql = '';

			if ( $subQuery->joinTable !== '' ) {
				$sql = 'INSERT ' . 'IGNORE ' . 'INTO ' .
				       $this->connection->tableName( $query->alias ) .
					   " SELECT DISTINCT $subQuery->joinfield FROM " . $this->connection->tableName( $subQuery->joinTable ) .
					   " AS $subQuery->alias $subQuery->from" . ( $subQuery->where ? " WHERE $subQuery->where":'' );
			} elseif ( $subQuery->joinfield !== '' ) {
				// NOTE: this works only for single "unconditional" values without further
				// WHERE or FROM. The execution must take care of not creating any others.
				$values = '';

				// This produces an error on postgres with
				// pg_query(): Query failed: ERROR:  duplicate key value violates
				// unique constraint "sunittest_t3_pkey" DETAIL:  Key (id)=(274) already exists.

				foreach ( $subQuery->joinfield as $value ) {
					$values .= ( $values ? ',' : '' ) . '(' . $this->connection->addQuotes( $value ) . ')';
				}

				$sql = 'INSERT ' . 'IGNORE ' .  'INTO ' . $this->connection->tableName( $query->alias ) . " (id) VALUES $values";
			} // else: // interpret empty joinfields as impossible condition (empty result), ignore

			if ( $sql ) {
				$this->executedQueries[$query->alias][] = $sql;

				if ( $this->queryMode !== Query::MODE_NONE ) {
					$this->connection->query(
						$sql,
						__METHOD__
					);
				}
			}
		}

		$query->type = QuerySegment::Q_TABLE;
		$query->where = '';
		$query->components = [];

		$query->joinTable = $query->alias;
		$query->joinfield = "$query->alias.id";
		$query->sortfields = []; // Make sure we got no sortfields.

		// TODO: currently this eliminates sortkeys, possibly keep them (needs
		// different temp table format though, maybe not such a good thing to do)
	}

	/**
	 * Find subproperties or subcategories. This may require iterative computation,
	 * and temporary tables are used in many cases.
	 *
	 * @param QuerySegment $query
	 */
	private function hierarchy( QuerySegment &$query ) {

		switch ( $query->type ) {
			case QuerySegment::Q_PROP_HIERARCHY:
				$type = 'property';
				break;
			case QuerySegment::Q_CLASS_HIERARCHY:
				$type = 'class';
				break;
		}

		list( $smwtable, $depth ) = $this->hierarchyTempTableBuilder->getTableDefinitionByType(
			$type
		);

		// An individual depth was annotated as part of the query
		if ( $query->depth !== null ) {
			$depth = $query->depth;
		}

		if ( $depth <= 0 ) { // treat as value, no recursion
			$query->type = QuerySegment::Q_VALUE;
			return;
		}

		$values = '';
		$valuecond = '';

		foreach ( $query->joinfield as $value ) {
			$values .= ( $values ? ',':'' ) . '(' . $this->connection->addQuotes( $value ) . ')';
			$valuecond .= ( $valuecond ? ' OR ':'' ) . 'o_id=' . $this->connection->addQuotes( $value );
		}

		// Try to safe time (SELECT is cheaper than creating/dropping 3 temp tables):
		$res = $this->connection->select(
			$smwtable,
			's_id',
			$valuecond,
			__METHOD__,
			[ 'LIMIT' => 1 ]
		);

		if ( !$this->connection->fetchObject( $res ) ) { // no subobjects, we are done!
			$this->connection->freeResult( $res );
			$query->type = QuerySegment::Q_VALUE;
			return;
		}

		$this->connection->freeResult( $res );
		$tablename = $this->connection->tableName( $query->alias );
		$this->executedQueries[$query->alias] = [
			"Recursively computed hierarchy for element(s) $values.",
			"SELECT s_id FROM $smwtable WHERE $valuecond LIMIT 1"
		];

		$query->joinTable = $query->alias;
		$query->joinfield = "$query->alias.id";

		$this->hierarchyTempTableBuilder->fillTempTable(
			$type,
			$tablename,
			$values,
			$depth
		);
	}

	/**
	 * After querying, make sure no temporary database tables are left.
	 * @todo I might be better to keep the tables and possibly reuse them later
	 * on. Being temporary, the tables will vanish with the session anyway.
	 */
	public function cleanUp() {
		foreach ( $this->executedQueries as $table => $log ) {
			$this->temporaryTableBuilder->drop( $this->connection->tableName( $table ) );
		}
	}

}
