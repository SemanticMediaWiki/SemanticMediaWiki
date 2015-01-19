<?php

namespace SMW\SQLStore\QueryEngine\Compiler;

use SMW\DIWikiPage;
use SMW\Query\Language\Description;
use SMW\Query\Language\ValueDescription;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\QueryCompiler;
use SMW\SQLStore\QueryEngine\QueryContainer;
use SMWSql3SmwIds;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class ValueDescriptionCompiler implements QueryCompiler {

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @var CompilerHelper
	 */
	private $compilerHelper;

	/**
	 * @since 2.1
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
		$this->compilerHelper = new CompilerHelper();
	}

	/**
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function canCompileDescription( Description $description ) {
		return $description instanceOf ValueDescription;
	}

	/**
	 * Only type '_wpg' objects can appear on query level (essentially as nominal classes)
	 *
	 * @since 2.1
	 *
	 * @param Description $description
	 *
	 * @return QueryContainer
	 */
	public function compileDescription( Description $description ) {

		$query = new QueryContainer();

		if ( !$description->getDataItem() instanceof DIWikiPage ) {
			return $query;
		}

		if ( $description->getComparator() === SMW_CMP_EQ ) {
			$query->type = QueryContainer::Q_VALUE;
			$oid = $this->queryBuilder->getStore()->getObjectIds()->getSMWPageID(
				$description->getDataItem()->getDBkey(),
				$description->getDataItem()->getNamespace(),
				$description->getDataItem()->getInterwiki(),
				$description->getDataItem()->getSubobjectName() );
			$query->joinfield = array( $oid );
		} else { // Join with SMW IDs table needed for other comparators (apply to title string).
			$query->jointable = SMWSql3SmwIds::tableName;
			$query->joinfield = "{$query->alias}.smw_id";
			$value = $description->getDataItem()->getSortKey();

			$comparator = $this->compilerHelper->getSQLComparatorToValue( $description, $value );
			$query->where = "{$query->alias}.smw_sortkey$comparator" . $this->queryBuilder->getStore()->getConnection( 'mw.db' )->addQuotes( $value );
		}

		return $query;
	}

}
