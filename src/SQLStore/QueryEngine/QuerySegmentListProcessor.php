<?php

namespace SMW\SQLStore\QueryEngine;

use RuntimeException;
use SMW\MediaWiki\Database;
use SMW\SQLStore\TableBuilder\TemporaryTableBuilder;
use SMWQuery as Query;
use Wikimedia\Rdbms\JoinGroup;
use Wikimedia\Rdbms\JoinGroupBase;
use Wikimedia\Rdbms\Platform\ISQLPlatform;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class QuerySegmentListProcessor {

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
	 * @param int $id
	 *
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
			case QuerySegment::Q_TABLE:
				$this->table( $query );
				break;
			case QuerySegment::Q_CONJUNCTION:
				$this->conjunction( $query );
				break;
			case QuerySegment::Q_DISJUNCTION:
				$this->disjunction( $query );
				break;
			case QuerySegment::Q_PROP_HIERARCHY:
			case QuerySegment::Q_CLASS_HIERARCHY:
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

				$seg = new QuerySegment();
				$seg->joinType = $joinType;
				$seg->joinTable = $subQuery->joinTable;
				$seg->alias = $subQuery->alias;

				// Move conditions referencing the joined table into the join condition
				if ( $subQuery->where !== '' && $subQuery->where !== null ) {
					$seg->where = "$joinField$op=" . $subQuery->joinfield;
					if ( $joinType === 'LEFT' || $joinType === 'LEFT OUTER' ) {
						$seg->where .= ' AND (' . $subQuery->where . ')';
					} else {
						$query->where .= ( ( $query->where === '' ) ? '' : ' AND ' ) . '(' . $subQuery->where . ')';
					}
				} else {
					$seg->where = "$joinField$op=" . $subQuery->joinfield;
				}

				// Merge any nested joins
				if ( !empty( $subQuery->fromSegs ) ) {
					$seg->fromSegs = array_merge( $seg->fromSegs, $subQuery->fromSegs );
				}

				$query->fromSegs[] = $seg;

				if ( $joinType === 'LEFT' ) {
					$query->where .= ( ( $query->where === '' ) ? '' : ' AND ' ) . '(' . $subQuery->joinfield . ' IS NULL)';
				}

			} elseif ( $subQuery->joinfield !== '' ) { // Require joinfield as "value" via WHERE.
				$condition = '';

				if ( $subQuery->null === true ) {
					$condition .= ( $condition ? ' OR ' : '' ) . "$joinField IS NULL";
				} else {
					foreach ( $subQuery->joinfield as $value ) {
						$op = $subQuery->not ? '!' : '';
						$condition .= ( $condition ? ' OR ' : '' ) . "$joinField$op=" . $this->connection->addQuotes( $value );
					}
				}

				if ( count( $subQuery->joinfield ) > 1 ) {
					$condition = "($condition)";
				}

				$query->where .= ( ( $query->where === '' || $subQuery->where === null ) ? '' : ' AND ' ) . $condition;

				// Merge any nested joins
				if ( !empty( $subQuery->fromSegs ) ) {
					$query->fromSegs = array_merge( $query->fromSegs, $subQuery->fromSegs );
				}

			} else { // interpret empty joinfields as impossible condition (empty result)
				$query->joinfield = ''; // make whole query false
				$query->joinTable = '';
				$query->where = '';
				$query->fromSegs = [];
				break;
			}
		}

		$query->components = [];
	}

	private function conjunction( QuerySegment &$query ) {
		reset( $query->components );
		$key = false;

		foreach ( $query->components as $qkey => $qid ) {
			$key = $qkey;
			if ( $this->querySegmentList[$qkey]->joinTable !== '' ) {
				break;
			}
		}

		$result = $this->querySegmentList[$key];
		unset( $query->components[$key] );

		$this->segment( $result );

		foreach ( $query->components as $qid => $joinfield ) {
			$result->components[$qid] = $result->joinfield;
		}

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
					   " AS $subQuery->alias $subQuery->from" . ( $subQuery->where ? " WHERE $subQuery->where" : '' );
			} elseif ( $subQuery->joinfield !== '' ) {
				$values = '';
				foreach ( $subQuery->joinfield as $value ) {
					$values .= ( $values ? ',' : '' ) . '(' . $this->connection->addQuotes( $value ) . ')';
				}
				$sql = 'INSERT ' . 'IGNORE ' . 'INTO ' . $this->connection->tableName( $query->alias ) . " (id) VALUES $values";
			}

			if ( $sql ) {
				$this->executedQueries[$query->alias][] = $sql;

				if ( $this->queryMode !== Query::MODE_NONE ) {
					$this->connection->query(
						$sql,
						__METHOD__,
						ISQLPlatform::QUERY_CHANGE_ROWS
					);
				}
			}
		}

		$query->type = QuerySegment::Q_TABLE;
		$query->where = '';
		$query->components = [];
		$query->joinTable = $query->alias;
		$query->joinfield = "$query->alias.id";
		$query->sortfields = [];
	}

	private function hierarchy( QuerySegment &$query ) {
		switch ( $query->type ) {
			case QuerySegment::Q_PROP_HIERARCHY:
				$type = 'property';
				break;
			case QuerySegment::Q_CLASS_HIERARCHY:
				$type = 'class';
				break;
		}

		[ $smwtable, $depth ] = $this->hierarchyTempTableBuilder->getTableDefinitionByType(
			$type
		);

		if ( $query->depth !== null ) {
			$depth = $query->depth;
		}

		if ( $depth <= 0 ) {
			$query->type = QuerySegment::Q_VALUE;
			return;
		}

		$values = '';
		$valuecond = '';

		foreach ( $query->joinfield as $value ) {
			$values .= ( $values ? ',' : '' ) . '(' . $this->connection->addQuotes( $value ) . ')';
			$valuecond .= ( $valuecond ? ' OR ' : '' ) . 'o_id=' . $this->connection->addQuotes( $value );
		}

		$res = $this->connection->select(
			$smwtable,
			's_id',
			$valuecond,
			__METHOD__,
			[ 'LIMIT' => 1 ]
		);

		if ( !$res->fetchObject() ) {
			$res->free();
			$query->type = QuerySegment::Q_VALUE;
			return;
		}

		$res->free();
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

	public function cleanUp() {
		foreach ( $this->executedQueries as $table => $log ) {
			$this->temporaryTableBuilder->drop( $this->connection->tableName( $table ) );
		}
	}

	/**
	 * Apply QuerySegment->fromSegs to a SelectQueryBuilder.
	 * @since 4.2
	 *
	 * @param QuerySegment $qobj QuerySegment to build the joins from
	 * @param JoinGroupBase $builder First call must be SelectQueryBuilder, but become JoinGroup on recursive calls.
	 * @param SelectQueryBuilder|null $topBuilder Top level builder passed in from the original call
	 * @throws InvalidArgumentException if QuerySegment->joinType is not empty, LEFT, LEFT OUTER, or INNER.
	 */
	public static function applyFromSegments( QuerySegment $qobj, JoinGroupBase $builder, ?SelectQueryBuilder $topBuilder = null ): void {
		if ( $topBuilder === null ) {
			$topBuilder = $builder;
		}
		foreach ( $qobj->fromSegs as $seg ) {
			$joinMethod = 'join';
			if ( $seg->joinType === 'LEFT' || $seg->joinType === 'LEFT OUTER' ) {
				$joinMethod = 'leftJoin';
			} elseif ( !empty( $seg->joinType ) && $seg->joinType !== 'INNER' ) {
				throw new InvalidArgumentException( "Unknown QuerySegment->joinType `{$seg->joinType}`" );
			}
			$table = $seg->joinTable;
			if ( $table === $seg->alias ) {
				$table = $topBuilder->newSubquery()->select( '*' )->from( $table );
			}
			if ( empty( $seg->fromSegs ) ) {
				$builder->{$joinMethod}( $table, $seg->alias, $seg->where );
			} else {
				$grp = new JoinGroup( $seg->alias . 'jg' );
				$grp->table( $table, $seg->alias );
				self::applyFromSegments( $seg, $grp, $topBuilder );
				$builder->{$joinMethod}( $grp, $grp->getAlias(), $seg->where );
			}
		}
	}
}
