<?php

namespace SMW\SQLStore\QueryEngine\Compiler;

use RuntimeException;
use SMW\DataTypeRegistry;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\QueryCompiler;
use SMW\SQLStore\QueryEngine\SqlQueryPart;
use SMWDataItem as DataItem;
use SMWDataItemHandler as DataItemHandler;
use SMWSql3SmwIds;
use SMWSQLStore3Table;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class SomePropertyCompiler implements QueryCompiler {

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @var CompilerHelper
	 */
	private $compilerHelper;

	/**
	 * @since 2.2
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
		$this->compilerHelper = new CompilerHelper();
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function canCompileDescription( Description $description ) {
		return $description instanceOf SomeProperty;
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
	 * @return SqlQueryPart
	 */
	public function compileDescription( Description $description ) {

		$query = new SqlQueryPart();

		$this->compileSomePropertyDescription( $query, $description );

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
	protected function compileSomePropertyDescription( SqlQueryPart $query, SomeProperty $description ) {

		$db = $this->queryBuilder->getStore()->getConnection( 'mw.db' );

		$property = $description->getProperty();

		$tableid = $this->queryBuilder->getStore()->findPropertyTableID( $property );

		if ( $tableid === '' ) { // Give up
			$query->type = SqlQueryPart::Q_NOQUERY;
			return;
		}

		$proptables = $this->queryBuilder->getStore()->getPropertyTables();
		$proptable = $proptables[$tableid];

		if ( !$proptable->usesIdSubject() ) {
			// no queries with such tables
			// (only redirects are affected in practice)
			$query->type = SqlQueryPart::Q_NOQUERY;
			return;
		}

		$typeid = $property->findPropertyTypeID();
		$diType = DataTypeRegistry::getInstance()->getDataItemId( $typeid );

		if ( $property->isInverse() && $diType !== DataItem::TYPE_WIKIPAGE ) {
			// can only invert properties that point to pages
			$query->type = SqlQueryPart::Q_NOQUERY;
			return;
		}

		$diHandler = $this->queryBuilder->getStore()->getDataItemHandlerForDIType( $diType );
		$indexField = $diHandler->getIndexField();

		// TODO: strictly speaking, the DB key is not what we want here,
		// since sortkey is based on a "wiki value"
		$sortkey = $property->getKey();

		// *** Now construct the query ... ***//
		$query->joinTable = $proptable->getName();

		// *** Add conditions for selecting rows for this property ***//
		if ( !$proptable->isFixedPropertyTable() ) {
			$pid = $this->queryBuilder->getStore()->getObjectIds()->getSMWPropertyID( $property );

			// Construct property hierarchy:
			$pqid = SqlQueryPart::$qnum;
			$pquery = new SqlQueryPart();
			$pquery->type = SqlQueryPart::Q_PROP_HIERARCHY;
			$pquery->joinfield = array( $pid );
			$query->components[$pqid] = "{$query->alias}.p_id";
			$this->queryBuilder->addSqlQueryPartForId( $pqid, $pquery );

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
			$sub = $this->queryBuilder->buildSqlQueryPartFor( $description->getDescription() );

			if ( $sub >= 0 ) {
				$query->components[$sub] = "{$query->alias}.{$o_id}";
			}

			if ( array_key_exists( $sortkey, $this->queryBuilder->getSortKeys() ) ) {
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
			if ( array_key_exists( $sortkey, $this->queryBuilder->getSortKeys() ) ) {
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
			$this->compileValueDescription( $query, $description, $diHandler, $operator );
		} elseif ( ( $description instanceof Conjunction ) ||
				( $description instanceof Disjunction ) ) {
			$op = ( $description instanceof Conjunction ) ? 'AND' : 'OR';

			foreach ( $description->getDescriptions() as $subdesc ) {
				$this->compilePropertyValueDescription( $query, $subdesc, $proptable, $diHandler, $op );
			}
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
	 * @param Description $description
	 * @param DataItemHandler $diHandler for that table
	 * @param string $operator SQL operator "AND" or "OR"
	 */
	protected function compileValueDescription(
			$query, ValueDescription $description, DataItemHandler $diHandler, $operator ) {

		$where = '';
		$dataItem = $description->getDataItem();
		$db = $this->queryBuilder->getStore()->getConnection( 'mw.db' );

		// TODO Better get the handle from the property type
		// Some comparators (e.g. LIKE) could use DI values of
		// a different type; we care about the property table, not
		// about the value
		$diType = $dataItem->getDIType();

		// Try comparison based on value field and comparator,
		// but only if no join with SMW IDs table is needed.
		if ( $diType !== DataItem::TYPE_WIKIPAGE ) {
			// Do not support smw_id joined data for now.

			if ( $where == '' ) {
				$indexField = $diHandler->getIndexField();
				//Hack to get to the field used as index
				$keys = $diHandler->getWhereConds( $dataItem );
				$value = $keys[$indexField];

				$comparator = $this->compilerHelper->getSQLComparatorToValue( $description, $value );
				$where = "$query->alias.{$indexField}{$comparator}" . $db->addQuotes( $value );
			}
		} else { // exact match (like comparator = above, but not using $valueField
				throw new RuntimeException("Debug -- this code might be dead.");
			foreach ( $diHandler->getWhereConds( $dataItem ) as $fieldname => $value ) {
				$where .= ( $where ? ' AND ' : '' ) . "{$query->alias}.$fieldname=" . $db->addQuotes( $value );
			}
		}

		if ( $where !== '' ) {
			$query->where .= ( $query->where ? " $operator " : '' ) . "($where)";
		}
	}

}
