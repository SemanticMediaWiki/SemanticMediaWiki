<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\SQLStore\QueryEngine\Compiler\NamespaceCompiler;
use SMW\SQLStore\QueryEngine\Compiler\DisjunctionConjunctionCompiler;
use SMW\SQLStore\QueryEngine\Compiler\ClassDescriptionCompiler;
use SMW\SQLStore\QueryEngine\Compiler\ValueDescriptionCompiler;
use SMW\SQLStore\QueryEngine\Compiler\ConceptDescriptionCompiler;

use SMW\Query\Language\Description;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ClassDescription;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\DataTypeRegistry;
use SMW\Store;

use SMWSQLStore3;
use SMWSQLStore3Table;
use SMWDataItemHandler as DataItemHandler;

use SMWQueryParser as QueryParser;
use SMWDataItem as DataItem;
use SMWQuery as Query;
use SMWSql3SmwIds;

use MWException;

/**
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class QueryBuilder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var QueryCompiler[]
	 */
	private $queryCompilers = array();

	/**
	 * Array of generated QueryContainer query descriptions (index => object).
	 */
	private $queries = array();

	/**
	 * Array of sorting requests ("Property_name" => "ASC"/"DESC"). Used during query
	 * processing (where these property names are searched while compiling the query
	 * conditions).
	 */
	private $sortkeys = array();

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * @var integer
	 */
	private $lastContainerId = -1;

	/**
	 * @since  2.1
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;

		$this->setToInitialBuildState();

		$this->registerQueryCompiler( new DisjunctionConjunctionCompiler( $this ) );
		$this->registerQueryCompiler( new NamespaceCompiler( $this ) );
		$this->registerQueryCompiler( new ClassDescriptionCompiler( $this ) );
		$this->registerQueryCompiler( new ValueDescriptionCompiler( $this ) );
		$this->registerQueryCompiler( new ConceptDescriptionCompiler( $this ) );
	}

	/**
	 * @since 2.1
	 *
	 * @return Store
	 */
	public function getStore() {
		return $this->store;
	}

	/**
	 * @since  2.1
	 *
	 * @param QueryCompiler $queryCompiler
	 */
	public function registerQueryCompiler( QueryCompiler $queryCompiler ) {
		$this->queryCompilers[] = $queryCompiler;
	}

	/**
	 * @since 2.1
	 *
	 * @param array $sortkeys
	 *
	 * @return QueryBuilder
	 */
	public function setSortkeys( $sortkeys ) {
		$this->sortkeys = $sortkeys;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param array $sortkeys
	 */
	public function getSortkeys() {
		return $this->sortkeys;
	}

	/**
	 * @since  2.1
	 *
	 * @return array
	 */
	public function getQueryContainer( $id = null ) {

		if ( $id === null ) {
			return $this->queries;
		}

		return isset( $this->queries[ $id ] ) ? $this->queries[ $id ] : array();
	}

	/**
	 * @since  2.1
	 *
	 * @param $id
	 * @param QueryContainer $query
	 *
	 * @return QueryBuilder
	 */
	public function addQueryContainerForId( $id, QueryContainer $query ) {
		$this->queries[ $id ] = $query;
		return $this;
	}

	/**
	 * @since  2.1
	 *
	 * @return integer
	 */
	public function getLastContainerId() {
		return $this->lastContainerId;
	}

	/**
	 * @since  2.1
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since  2.1
	 *
	 * @return string $error
	 */
	public function addError( $error ) {
		$this->errors[] = $error;
	}

	/**
	 * @since 2.1
	 *
	 * @return QueryBuilder
	 */
	public function setToInitialBuildState() {
		QueryContainer::$qnum = 0;
		$this->lastContainerId = -1;
		$this->sortkeys = array();
		$this->queries = array();

		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param  Description $description
	 *
	 * @return integer
	 */
	public function buildQueryContainer( Description $description ) {
		return $this->compileQueries( $description );
	}

	/**
	 * Create a new QueryContainer object that can be used to obtain results
	 * for the given description. The result is stored in $this->queries
	 * using a numeric key that is returned as a result of the function.
	 * Returns -1 if no query was created.
	 *
	 * @todo The case of nominal classes (top-level ValueDescription) still
	 * makes some assumptions about the table structure, especially about the
	 * name of the joinfield (o_id). Better extend
	 * compilePropertyValueDescription to deal with this case.
	 *
	 * @param Description $description
	 *
	 * @return integer
	 */
	public function compileQueries( Description $description ) {

		// Used only temporary until all comilers are registered
		$hasNoCompiler = true;

		if ( $description instanceof ClassDescription || $description instanceof NamespaceDescription || $description instanceof Conjunction || $description instanceof Disjunction || $description instanceof ValueDescription || $description instanceof ConceptDescription ) {
			$hasNoCompiler = false;
			$queryCompiler = $this->getQueryCompiler( $description );
			$query = $queryCompiler->compileDescription( $description );
		} else {
			$query = new QueryContainer();
		}

		if ( $description instanceof SomeProperty ) {
			$this->compileSomePropertyDescription( $query, $description );
		} elseif ( $hasNoCompiler ) { // (e.g. ThingDescription)
			$query->type = QueryContainer::Q_NOQUERY; // no condition
		}

		$this->registerQuery( $query );

		return $this->lastContainerId = $query->type !== QueryContainer::Q_NOQUERY ? $query->queryNumber : -1;
	}

	protected function getQueryCompiler( Description $description ) {
		foreach ( $this->queryCompilers as $queryCompiler ) {
			if ( $queryCompiler->canCompileDescription( $description ) ) {
				return $queryCompiler;
			}
		}

		// throw new RuntimeException( "Description has no registered compiler" );
		return null;
	}

	/**
	 * Register a query object to the internal query list, if the query is
	 * valid. Also make sure that sortkey information is propagated down
	 * from subqueries of this query.
	 */
	protected function registerQuery( QueryContainer $query ) {

		if ( $query->type === QueryContainer::Q_NOQUERY ) {
			return null;
		}

		$this->addQueryContainerForId( $query->queryNumber, $query );

		// Propagate sortkeys from subqueries:
		if ( $query->type !== QueryContainer::Q_DISJUNCTION ) {
			// Sortkeys are killed by disjunctions (not all parts may have them),
			// NOTE: preprocessing might try to push disjunctions downwards to safe sortkey, but this seems to be minor
			foreach ( $query->components as $cid => $field ) {
				$query->sortfields = array_merge( $this->getQueryContainer( $cid )->sortfields, $query->sortfields );
			}
		}
	}

	/**
	 * Modify the given query object to account for some property condition for
	 * the given property. If it is not possible to generate a query for the
	 * given data, the query type is changed to QueryContainer::Q_NOQUERY. Callers need
	 * to check for this and discard the query in this case.
	 *
	 * @note This method does not support sortkey (_SKEY) property queries,
	 * since they do not have a normal property table. This should not be a
	 * problem since comparators on sortkeys are supported indirectly when
	 * using comparators on wikipages. There is no reason to create any
	 * query with _SKEY ad users cannot do so either (no user label).
	 *
	 * @since 1.8
	 */
	protected function compileSomePropertyDescription( QueryContainer $query, SomeProperty $description ) {

		$db = $this->store->getDatabase();

		$property = $description->getProperty();

		$tableid = $this->store->findPropertyTableID( $property );
		if ( $tableid === '' ) { // Give up
			$query->type = QueryContainer::Q_NOQUERY;
			return;
		}

		$proptables = $this->store->getPropertyTables();
		$proptable = $proptables[$tableid];
		if ( !$proptable->usesIdSubject() ) {
			// no queries with such tables
			// (only redirects are affected in practice)
			$query->type = QueryContainer::Q_NOQUERY;
			return;
		}

		$typeid = $property->findPropertyTypeID();
		$diType = DataTypeRegistry::getInstance()->getDataItemId( $typeid );
		if ( $property->isInverse() && $diType != DataItem::TYPE_WIKIPAGE ) {
			// can only invert properties that point to pages
			$query->type = QueryContainer::Q_NOQUERY;
			return;
		}
		$diHandler = $this->store->getDataItemHandlerForDIType( $diType );
		$indexField = $diHandler->getIndexField();
		$sortkey = $property->getKey(); // TODO: strictly speaking, the DB key is not what we want here, since sortkey is based on a "wiki value"

		// *** Now construct the query ... ***//
		$query->jointable = $proptable->getName();

		// *** Add conditions for selecting rows for this property ***//
		if ( !$proptable->isFixedPropertyTable() ) {
			$pid = $this->store->smwIds->getSMWPropertyID( $property );
			// Construct property hierarchy:
			$pqid = QueryContainer::$qnum;
			$pquery = new QueryContainer();
			$pquery->type = QueryContainer::Q_PROP_HIERARCHY;
			$pquery->joinfield = array( $pid );
			$query->components[$pqid] = "{$query->alias}.p_id";
			$this->addQueryContainerForId( $pqid, $pquery );
			// Alternative code without property hierarchies:
			// $query->where = "{$query->alias}.p_id=" . $this->m_dbs->addQuotes( $pid );
		} // else: no property column, no hierarchy queries

		// *** Add conditions on the value of the property ***//
		if ( $diType == DataItem::TYPE_WIKIPAGE ) {
			$o_id = $indexField;
			if ( $property->isInverse() ) {
				$s_id = $o_id;
				$o_id = 's_id';
			} else {
				$s_id = 's_id';
			}
			$query->joinfield = "{$query->alias}.{$s_id}";

			// process page description like main query
			$sub = $this->compileQueries( $description->getDescription() );
			if ( $sub >= 0 ) {
				$query->components[$sub] = "{$query->alias}.{$o_id}";
			}

			if ( array_key_exists( $sortkey, $this->sortkeys ) ) {
				// TODO: This SMW IDs table is possibly duplicated in the query.
				// Example: [[has capital::!Berlin]] with sort=has capital
				// Can we prevent that? (PERFORMANCE)
				$query->from = ' INNER JOIN ' .	$db->tableName( SMWSql3SmwIds::tableName ) .
						" AS ids{$query->alias} ON ids{$query->alias}.smw_id={$query->alias}.{$o_id}";
				$query->sortfields[$sortkey] = "ids{$query->alias}.smw_sortkey";
			}
		} else { // non-page value description
			$query->joinfield = "{$query->alias}.s_id";
			$this->compilePropertyValueDescription( $query, $description->getDescription(), $proptable, $diHandler, 'AND' );
			if ( array_key_exists( $sortkey, $this->sortkeys ) ) {
				$query->sortfields[$sortkey] = "{$query->alias}.{$indexField}";
			}
		}
	}

	/**
	 * Given an Description that is just a conjunction or disjunction of
	 * ValueDescription objects, create and return a plain WHERE condition
	 * string for it.
	 *
	 * @param $query
	 * @param Description $description
	 * @param SMWSQLStore3Table $proptable
	 * @param DataItemHandler $diHandler for that table
	 * @param string $operator SQL operator "AND" or "OR"
	 */
	protected function compilePropertyValueDescription(
			$query, Description $description, SMWSQLStore3Table $proptable, DataItemHandler $diHandler, $operator ) {
		if ( $description instanceof ValueDescription ) {
			$this->compileValueDescription( $query, $description, $proptable, $diHandler, $operator );
		} elseif ( ( $description instanceof Conjunction ) ||
				( $description instanceof Disjunction ) ) {
			$op = ( $description instanceof Conjunction ) ? 'AND' : 'OR';

			foreach ( $description->getDescriptions() as $subdesc ) {
				$this->compilePropertyValueDescription( $query, $subdesc, $proptable, $diHandler, $op );
			}
		} elseif ( $description instanceof ThingDescription ) {
			// nothing to do
		} else {
			throw new MWException( "Cannot process this type of Description." );
		}
	}

	/**
	 * Given an Description that is just a conjunction or disjunction of
	 * ValueDescription objects, create and return a plain WHERE condition
	 * string for it.
	 *
	 * @param $query
	 * @param Description $description
	 * @param SMWSQLStore3Table $proptable
	 * @param DataItemHandler $diHandler for that table
	 * @param string $operator SQL operator "AND" or "OR"
	 */
	protected function compileValueDescription(
			$query, ValueDescription $description, SMWSQLStore3Table $proptable, DataItemHandler $diHandler, $operator ) {
		$where = '';
		$dataItem = $description->getDataItem();
		// TODO Better get the handle from the property type
		// Some comparators (e.g. LIKE) could use DI values of
		// a different type; we care about the property table, not
		// about the value
		$diType = $dataItem->getDIType();

		// Try comparison based on value field and comparator,
		// but only if no join with SMW IDs table is needed.
		if ( $diType != DataItem::TYPE_WIKIPAGE ) {
			// Do not support smw_id joined data for now.

			if ( $where == '' ) {
				$indexField = $diHandler->getIndexField();
				//Hack to get to the field used as index
				$keys = $diHandler->getWhereConds( $dataItem );
				$value = $keys[$indexField];

				switch ( $description->getComparator() ) {
					case SMW_CMP_EQ: $comparator = '='; break;
					case SMW_CMP_LESS: $comparator = '<'; break;
					case SMW_CMP_GRTR: $comparator = '>'; break;
					case SMW_CMP_LEQ: $comparator = '<='; break;
					case SMW_CMP_GEQ: $comparator = '>='; break;
					case SMW_CMP_NEQ: $comparator = '!='; break;
					case SMW_CMP_LIKE: case SMW_CMP_NLKE:
						if ( $description->getComparator() == SMW_CMP_LIKE ) {
							$comparator = ' LIKE ';
						} else {
							$comparator = ' NOT LIKE ';
						}
						// Escape to prepare string matching:
						$value = str_replace( array( '%', '_', '*', '?' ), array( '\%', '\_', '%', '_' ), $value );
						break;
					default:
						throw new MWException( "Unsupported comparator '" . $description->getComparator() . "' in query condition." );
				}

				$where = "$query->alias.{$indexField}{$comparator}" . $this->store->getDatabase()->addQuotes( $value );
			}
		} else { // exact match (like comparator = above, but not using $valueField
				throw new MWException("Debug -- this code might be dead.");
			foreach ( $diHandler->getWhereConds( $dataItem ) as $fieldname => $value ) {
				$where .= ( $where ? ' AND ' : '' ) . "{$query->alias}.$fieldname=" . $this->store->getDatabase()->addQuotes( $value );
			}
		}

		if ( $where !== '' ) {
			$query->where .= ( $query->where ? " $operator " : '' ) . "($where)";
		}
	}

}
