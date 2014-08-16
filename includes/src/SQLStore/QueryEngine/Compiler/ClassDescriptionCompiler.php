<?php

namespace SMW\SQLStore\QueryEngine\Compiler;

use SMW\SQLStore\QueryEngine\QueryCompiler;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\QueryContainer;

use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Description;

use SMW\DIProperty;

/**
 * @since 2.1
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
	 * @var array
	 */
	private $errors = array();

	/**
	 * @since  2.1
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
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
	 * @return boolean
	 */
	public function canCompileDescription( Description $description ) {
		return $description instanceOf ClassDescription;
	}

	/**
	 * @since  2.1
	 *
	 * @param Description $description
	 *
	 * @return QueryContainer
	 */
	public function compileDescription( Description $description ) {

		$query = new QueryContainer();

		$cqid = QueryContainer::$qnum;
		$cquery = new QueryContainer();
		$cquery->type = QueryContainer::Q_CLASS_HIERARCHY;
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
			$query->type = QueryContainer::Q_VALUE;
			$query->jointable = '';
			$query->joinfield = '';
		} else { // Instance query with disjunction of classes (categories)
			$query->jointable = $this->getTableNameForProperty( new DIProperty( '_INST' ) );
			$query->joinfield = "$query->alias.s_id";
			$query->components[$cqid] = "$query->alias.o_id";
			$this->queryBuilder->addQueryContainerForId( $cqid, $cquery );
		}

		return $query;
	}

	private function getTableNameForProperty( DIProperty $property ) {
		return $this->queryBuilder->getStore()->getDatabase()->tableName(
			$this->queryBuilder->getStore()->findPropertyTableID( $property )
		);
	}

}
