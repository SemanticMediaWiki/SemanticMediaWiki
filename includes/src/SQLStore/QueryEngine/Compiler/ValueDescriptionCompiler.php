<?php

namespace SMW\SQLStore\QueryEngine\Compiler;

use SMW\SQLStore\QueryEngine\QueryCompiler;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\QueryContainer;

use SMW\Query\Language\ValueDescription;
use SMW\Query\Language\Description;

use SMW\DIWikiPage;

use SMWSql3SmwIds;

/**
 * @since 2.1
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
	 * @since 2.1
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
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

			$query->where = "{$query->alias}.smw_sortkey$comp" . $this->queryBuilder->getStore()->getDatabase()->addQuotes( $value );
		}

		return $query;
	}

}
