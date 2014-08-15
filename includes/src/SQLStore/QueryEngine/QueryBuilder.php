<?php

namespace SMW\SQLStore\QueryEngine;

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
use SMWDescription as Description;
use SMWQuery as Query;
use SMWSql3SmwIds;

use MWException;

/**
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 */
class QueryBuilder {

	/**
	 * @var Store
	 */
	private $store;

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
		$query = new QueryContainer();
		$db = $this->store->getDatabase();

		if ( $description instanceof SomeProperty ) {
			$this->compileSomePropertyDescription( $query, $description );
		} elseif ( $description instanceof NamespaceDescription ) {
			// TODO: One instance of the SMW IDs table on s_id always suffices (swm_id is KEY)! Doable in execution ... (PERFORMANCE)
			$this->buildQueryArtifactForNamespaceDescription( $query, $description );
		} elseif ( ( $description instanceof Conjunction ) ||
				( $description instanceof Disjunction ) ) {
			$query->type = ( $description instanceof Conjunction ) ? QueryContainer::Q_CONJUNCTION : QueryContainer::Q_DISJUNCTION;

			foreach ( $description->getDescriptions() as $subdesc ) {
				$sub = $this->compileQueries( $subdesc );
				if ( $sub >= 0 ) {
					$query->components[$sub] = true;
				}
			}

			// All subconditions failed, drop this as well.
			if ( count( $query->components ) == 0 ) {
				$query->type = QueryContainer::Q_NOQUERY;
			}
		} elseif ( $description instanceof ClassDescription ) {
			$cqid = QueryContainer::$qnum;
			$cquery = new QueryContainer();
			$cquery->type = QueryContainer::Q_CLASS_HIERARCHY;
			$cquery->joinfield = array();

			foreach ( $description->getCategories() as $cat ) {
				$cid = $this->store->smwIds->getSMWPageID( $cat->getDBkey(), NS_CATEGORY, $cat->getInterwiki(), '' );
				if ( $cid != 0 ) {
					$cquery->joinfield[] = $cid;
				}
			}

			if ( count( $cquery->joinfield ) == 0 ) { // Empty result.
				$query->type = QueryContainer::Q_VALUE;
				$query->jointable = '';
				$query->joinfield = '';
			} else { // Instance query with disjunction of classes (categories)
				$query->jointable = $db->tableName(
					$this->store->findPropertyTableID(
						new DIProperty( '_INST' ) ) );
				$query->joinfield = "$query->alias.s_id";
				$query->components[$cqid] = "$query->alias.o_id";
				$this->addQueryContainerForId( $cqid, $cquery );
			}

		} elseif ( $description instanceof ValueDescription ) { // Only type '_wpg' objects can appear on query level (essentially as nominal classes).
			if ( $description->getDataItem() instanceof DIWikiPage ) {
				if ( $description->getComparator() == SMW_CMP_EQ ) {
					$query->type = QueryContainer::Q_VALUE;
					$oid = $this->store->smwIds->getSMWPageID(
						$description->getDataItem()->getDBkey(),
						$description->getDataItem()->getNamespace(),
						$description->getDataItem()->getInterwiki(),
						$description->getDataItem()->getSubobjectName() );
					$query->joinfield = array( $oid );
				} else { // Join with SMW IDs table needed for other comparators (apply to title string).
					$query->jointable = SMWSql3SmwIds::tableName;
					$query->joinfield = "{$query->alias}.smw_id";
					$value = $description->getDataItem()->getSortKey();

					switch ( $description->getComparator() ) {
						case SMW_CMP_LEQ: $comp = '<='; break;
						case SMW_CMP_GEQ: $comp = '>='; break;
						case SMW_CMP_LESS: $comp = '<'; break;
						case SMW_CMP_GRTR: $comp = '>'; break;
						case SMW_CMP_NEQ: $comp = '!='; break;
						case SMW_CMP_LIKE: case SMW_CMP_NLKE:
							$comp = ' LIKE ';
							if ( $description->getComparator() == SMW_CMP_NLKE ) $comp = " NOT{$comp}";
							$value =  str_replace( array( '%', '_', '*', '?' ), array( '\%', '\_', '%', '_' ), $value );
						break;
					}
					$query->where = "{$query->alias}.smw_sortkey$comp" . $db->addQuotes( $value );
				}
			}
		} elseif ( $description instanceof ConceptDescription ) { // fetch concept definition and insert it here
			$cid = $this->store->smwIds->getSMWPageID( $description->getConcept()->getDBkey(), SMW_NS_CONCEPT, '', '' );
			// We bypass the storage interface here (which is legal as we control it, and safe if we are careful with changes ...)
			// This should be faster, but we must implement the unescaping that concepts do on getWikiValue()
			$row = $db->selectRow(
				'smw_fpt_conc',
				array( 'concept_txt', 'concept_features', 'concept_size', 'concept_depth', 'cache_date' ),
				array( 's_id' => $cid ),
				'SMWSQLStore3Queries::compileQueries'
			);

			if ( $row === false ) { // No description found, concept does not exist.
				// keep the above query object, it yields an empty result
				// TODO: announce an error here? (maybe not, since the query processor can check for
				// non-existing concept pages which is probably the main reason for finding nothing here)
			} else {
				global $smwgQConceptCaching, $smwgQMaxSize, $smwgQMaxDepth, $smwgQFeatures, $smwgQConceptCacheLifetime;

				$may_be_computed = ( $smwgQConceptCaching == CONCEPT_CACHE_NONE ) ||
				    ( ( $smwgQConceptCaching == CONCEPT_CACHE_HARD ) && ( ( ~( ~( $row->concept_features + 0 ) | $smwgQFeatures ) ) == 0 ) &&
				      ( $smwgQMaxSize >= $row->concept_size ) && ( $smwgQMaxDepth >= $row->concept_depth ) );

				if ( $row->cache_date &&
				     ( ( $row->cache_date > ( strtotime( "now" ) - $smwgQConceptCacheLifetime * 60 ) ) ||
				       !$may_be_computed ) ) { // Cached concept, use cache unless it is dead and can be revived.

					$query->jointable = SMWSQLStore3::CONCEPT_CACHE_TABLE;
					$query->joinfield = "$query->alias.s_id";
					$query->where = "$query->alias.o_id=" . $db->addQuotes( $cid );
				} elseif ( $row->concept_txt ) { // Parse description and process it recursively.
					if ( $may_be_computed ) {
						$qp = new QueryParser();

						// No defaultnamespaces here; If any, these are already in the concept.
						// Unescaping is the same as in SMW_DV_Conept's getWikiValue().
						$desc = $qp->getQueryDescription( str_replace( array( '&lt;', '&gt;', '&amp;' ), array( '<', '>', '&' ), $row->concept_txt ) );
						$qid = $this->compileQueries( $desc );
						if ($qid != -1) {
							$query = $this->getQueryContainer( $qid );
						} else { // somehow the concept query is no longer valid; maybe some syntax changed (upgrade) or global settings were modified since storing it
							$this->errors[] = wfMessage( 'smw_emptysubquery' )->text(); // not quite the right message, but this case is very rare; let us not make detailed messages for this
						}
					} else {
						$this->errors[] = wfMessage( 'smw_concept_cache_miss', $description->getConcept()->getText() )->text();
					}
				} // else: no cache, no description (this may happen); treat like empty concept
			}
		} else { // (e.g. ThingDescription)
			$query->type = QueryContainer::Q_NOQUERY; // no condition
		}

		$this->registerQuery( $query );

		return $this->lastContainerId = $query->type !== QueryContainer::Q_NOQUERY ? $query->queryNumber : -1;
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

	private function buildQueryArtifactForNamespaceDescription( QueryContainer $query, NamespaceDescription $description ) {
		$query->jointable = SMWSql3SmwIds::tableName;
		$query->joinfield = "$query->alias.smw_id";
		$query->where = "$query->alias.smw_namespace=" . $this->store->getDatabase()->addQuotes( $description->getNamespace() );
	}

}
