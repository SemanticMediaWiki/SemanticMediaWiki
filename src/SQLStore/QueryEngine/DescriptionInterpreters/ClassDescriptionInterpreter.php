<?php

namespace SMW\SQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DIProperty;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Description;
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class ClassDescriptionInterpreter implements DescriptionInterpreter {

	/**
	 * @since 2.2
	 */
	public function __construct(
		private readonly Store $store,
		private readonly ConditionBuilder $conditionBuilder,
	) {
	}

	/**
	 * @since 2.2
	 *
	 * @return bool
	 */
	public function canInterpretDescription( Description $description ) {
		return $description instanceof ClassDescription;
	}

	/**
	 * @since 2.2
	 *
	 * @param Description $description
	 *
	 * @return QuerySegment
	 */
	public function interpretDescription( Description $description ) {
		$query = new QuerySegment();

		$cqid = QuerySegment::$qnum;
		$cquery = new QuerySegment();
		$cquery->type = QuerySegment::Q_CLASS_HIERARCHY;
		$cquery->joinfield = [];
		$cquery->depth = $description->getHierarchyDepth();

		foreach ( $description->getCategories() as $category ) {

			$categoryId = $this->store->getObjectIds()->getSMWPageID(
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
			$query->type = QuerySegment::Q_VALUE;
			$query->joinTable = '';
			$query->joinfield = '';
		} else { // Instance query with disjunction of classes (categories)
			$query->joinTable = $this->store->findPropertyTableID( new DIProperty( '_INST' ) );
			$query->joinfield = "$query->alias.s_id";
			$query->components[$cqid] = "$query->alias.o_id";

			$this->conditionBuilder->addQuerySegment( $cquery );
		}

		return $query;
	}

}
