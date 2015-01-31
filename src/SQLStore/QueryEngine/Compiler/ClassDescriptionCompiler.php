<?php

namespace SMW\SQLStore\QueryEngine\Compiler;

use SMW\DIProperty;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Description;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\QueryCompiler;
use SMW\SQLStore\QueryEngine\SqlQueryPart;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class ClassDescriptionCompiler implements QueryCompiler {

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @since 2.2
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function canCompileDescription( Description $description ) {
		return $description instanceOf ClassDescription;
	}

	/**
	 * @since 2.2
	 *
	 * @param Description $description
	 *
	 * @return SqlQueryPart
	 */
	public function compileDescription( Description $description ) {

		$query = new SqlQueryPart();

		$cqid = SqlQueryPart::$qnum;
		$cquery = new SqlQueryPart();
		$cquery->type = SqlQueryPart::Q_CLASS_HIERARCHY;
		$cquery->joinfield = array();

		foreach ( $description->getCategories() as $category ) {

			$categoryId = $this->queryBuilder->getStore()->getObjectIds()->getSMWPageID(
				$category->getDBkey(),
				NS_CATEGORY,
				$category->getInterwiki(),
				''
			);

			if ( $categoryId != 0 ) {
				$cquery->joinfield[] = $categoryId;
			}
		}

		if ( count( $cquery->joinfield ) == 0 ) { // Empty result.
			$query->type = SqlQueryPart::Q_VALUE;
			$query->joinTable = '';
			$query->joinfield = '';
		} else { // Instance query with disjunction of classes (categories)
			$query->joinTable = $this->queryBuilder->getStore()->findPropertyTableID( new DIProperty( '_INST' ) );
			$query->joinfield = "$query->alias.s_id";
			$query->components[$cqid] = "$query->alias.o_id";
			$this->queryBuilder->addQueryContainerForId( $cqid, $cquery );
		}

		return $query;
	}

}
