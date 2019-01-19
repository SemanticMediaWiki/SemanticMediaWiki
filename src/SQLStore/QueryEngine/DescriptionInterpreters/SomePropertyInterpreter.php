<?php

namespace SMW\SQLStore\QueryEngine\DescriptionInterpreters;

use RuntimeException;
use SMW\DataTypeRegistry;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMWDataItem as DataItem;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMWSQLStore3Table;
use SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class SomePropertyInterpreter implements DescriptionInterpreter {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

	/**
	 * @var ValueMatchConditionBuilder
	 */
	private $valueMatchConditionBuilder;

	/**
	 * @var ComparatorMapper
	 */
	private $comparatorMapper;

	/**
	 * @var FulltextSearchTableFactory
	 */
	private $fulltextSearchTableFactory;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param ConditionBuilder $conditionBuilder
	 */
	public function __construct( Store $store, ConditionBuilder $conditionBuilder, ValueMatchConditionBuilder $valueMatchConditionBuilder ) {
		$this->store = $store;
		$this->conditionBuilder = $conditionBuilder;
		$this->valueMatchConditionBuilder = $valueMatchConditionBuilder;
		$this->comparatorMapper = new ComparatorMapper();
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function canInterpretDescription( Description $description ) {
		return $description instanceof SomeProperty;
	}

	/**
	 * @todo The case of nominal classes (top-level ValueDescription) still
	 * makes some assumptions about the table structure, especially about the
	 * name of the joinfield (o_id). Better extend
	 * compilePropertyValueDescription to deal with this case.
	 *
	 * @since 2.2
	 *
	 * @param Description $description
	 *
	 * @return QuerySegment
	 */
	public function interpretDescription( Description $description ) {

		$query = new QuerySegment();

		$this->interpretPropertyConditionForDescription(
			$query,
			$description
		);

		return $query;
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
	private function interpretPropertyConditionForDescription( QuerySegment $query, SomeProperty $description ) {

		$connection = $this->store->getConnection( 'mw.db.queryengine' );
		$property = $description->getProperty();

		$tableid = $this->store->findPropertyTableID( $property );

		if ( $tableid === '' ) { // Give up
			$query->type = QuerySegment::Q_NOQUERY;
			return;
		}

		$proptables = $this->store->getPropertyTables();
		$proptable = $proptables[$tableid];

		if ( !$proptable->usesIdSubject() ) {
			// no queries with such tables
			// (only redirects are affected in practice)
			$query->type = QuerySegment::Q_NOQUERY;
			return;
		}

		$typeid = $property->findPropertyTypeID();
		$diType = DataTypeRegistry::getInstance()->getDataItemId( $typeid );

		if ( $property->isInverse() && $diType !== DataItem::TYPE_WIKIPAGE ) {
			// can only invert properties that point to pages
			$query->type = QuerySegment::Q_NOQUERY;
			return;
		}

		$diHandler = $this->store->getDataItemHandlerForDIType( $diType );
		$indexField = $diHandler->getIndexField();

		// TODO: strictly speaking, the DB key is not what we want here,
		// since sortkey is based on a "wiki value"
		$sortkey = $property->getKey();

		// *** Now construct the query ... ***//
		$query->joinTable = $proptable->getName();
		$query->depth = $description->getHierarchyDepth();

		// *** Add conditions for selecting rows for this property ***//
		if ( !$proptable->isFixedPropertyTable() ) {
			$pid = $this->store->getObjectIds()->getSMWPropertyID( $property );

			// Construct property hierarchy:
			$pqid = QuerySegment::$qnum;
			$pquery = new QuerySegment();
			$pquery->type = QuerySegment::Q_PROP_HIERARCHY;
			$pquery->joinfield = [ $pid ];
			$pquery->depth = $description->getHierarchyDepth();
			$query->components[$pqid] = "{$query->alias}.p_id";

			$this->conditionBuilder->addQuerySegment( $pquery );

			// Alternative code without property hierarchies:
			// $query->where = "{$query->alias}.p_id=" . $this->m_dbs->addQuotes( $pid );
		} // else: no property column, no hierarchy queries

		// *** Add conditions on the value of the property ***//
		if ( $diType === DataItem::TYPE_WIKIPAGE ) {
			$o_id = $indexField;
			if ( $property->isInverse() ) {
				$s_id = $o_id;
				$o_id = 's_id';
			} else {
				$s_id = 's_id';
			}
			$query->joinfield = "{$query->alias}.{$s_id}";

			// process page description like main query
			$sub = $this->conditionBuilder->buildFromDescription(
				$description->getDescription()
			);

			if ( $sub >= 0 ) {
				$subQuery = $this->conditionBuilder->findQuerySegment(
					$sub
				);

				$o_id = $subQuery->indexField !== '' ? $subQuery->indexField : $o_id;
				$query->components[$sub] = "{$query->alias}.{$o_id}";
			}

			if ( array_key_exists( $sortkey, $this->conditionBuilder->getSortKeys() ) ) {
				// TODO: This SMW IDs table is possibly duplicated in the query.
				// Example: [[has capital::!Berlin]] with sort=has capital
				// Can we prevent that? (PERFORMANCE)
				$query->from = ' INNER JOIN ' .	$connection->tableName( SQLStore::ID_TABLE ) .
						" AS ids{$query->alias} ON ids{$query->alias}.smw_id={$query->alias}.{$o_id}";
				$query->sortfields[$sortkey] = "ids{$query->alias}.smw_sort";
			}
		} else { // non-page value description
			$query->joinfield = "{$query->alias}.s_id";
			$this->compilePropertyValueDescription( $query, $description->getDescription(), $proptable, $diHandler, 'AND' );
			if ( array_key_exists( $sortkey, $this->conditionBuilder->getSortKeys() ) ) {
				$query->sortfields[$sortkey] = isset( $query->sortIndexField ) ? $query->sortIndexField : "{$query->alias}.{$indexField}";
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
	private function compilePropertyValueDescription(
			$query, Description $description, SMWSQLStore3Table $proptable, DataItemHandler $diHandler, $operator ) {

		if ( $description instanceof ValueDescription ) {
			$this->mapValueDescription( $query, $description, $diHandler, $operator );
		} elseif ( ( $description instanceof Conjunction ) ||
				( $description instanceof Disjunction ) ) {
			$op = ( $description instanceof Conjunction ) ? 'AND' : 'OR';

			// #556 ensure correct parentheses are applied for something
			// like "(a OR b OR c) AND d AND e"
			if ( $query->where && substr( $query->where, -1 ) != '(' ) {
				$query->where .= " $operator ";
			}

			$query->where .= "(";

			foreach ( $description->getDescriptions() as $subdesc ) {
				$this->compilePropertyValueDescription( $query, $subdesc, $proptable, $diHandler, $op );
			}

			$query->where .= ")";

		} elseif ( $description instanceof ThingDescription ) {
			// nothing to do
		} else {
			throw new RuntimeException( "Cannot process this type of Description." );
		}
	}

	/**
	 * Given an Description that is just a conjunction or disjunction of
	 * ValueDescription objects, create and return a plain WHERE condition
	 * string for it.
	 *
	 * @param $query
	 * @param ValueDescription $description
	 * @param DataItemHandler $diHandler for that table
	 * @param string $operator SQL operator "AND" or "OR"
	 */
	private function mapValueDescription( $query, ValueDescription $description, DataItemHandler $diHandler, $operator ) {

		$where = '';
		$dataItem = $description->getDataItem();
		$connection = $this->store->getConnection( 'mw.db.queryengine' );

		// TODO Better get the handle from the property type
		// Some comparators (e.g. LIKE) could use DI values of
		// a different type; we care about the property table, not
		// about the value

		// Do not support smw_id joined data for now.
		$indexField = $diHandler->getIndexField();

		//Hack to get to the field used as index
		$keys = $diHandler->getWhereConds( $dataItem );
		$value = $keys[$indexField];

		// See if the getSQLCondition method exists and call it if this is the case.
		// Invoked by SMAreaValueDescription, SMGeoCoordsValueDescription
		if ( method_exists( $description, 'getSQLCondition' ) ) {
			$fields = $diHandler->getTableFields();

			$where = $description->getSQLCondition(
				$query->alias,
				array_keys( $fields ),
				$this->store->getConnection( DB_SLAVE )
			);
		}

		if ( $where == '' && $this->valueMatchConditionBuilder->canHaveMatchCondition( $description ) ) {
			$query->joinTable = $this->valueMatchConditionBuilder->getTableName();

			$query->sortIndexField = $this->valueMatchConditionBuilder->getSortIndexField(
				$query->alias
			);

			$query->components = [];

			$where = $this->valueMatchConditionBuilder->getWhereCondition(
				$description,
				$query->alias
			);
		} elseif ( $where == '' ) {

			$comparator = $this->comparatorMapper->mapComparator(
				$description,
				$value
			);

			$where = "$query->alias.{$indexField}{$comparator}" . $connection->addQuotes( $value );
		}

		if ( $where !== '' ) {

			if ( $query->where && substr( $query->where, -1 ) != '(' ) {
				$query->where .= " $operator ";
			}

			$query->where .= "($where)";
		}
	}

}
