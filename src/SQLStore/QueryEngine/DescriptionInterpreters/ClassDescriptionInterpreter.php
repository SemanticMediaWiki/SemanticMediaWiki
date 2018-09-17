<?php

namespace SMW\SQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DIProperty;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Description;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class ClassDescriptionInterpreter implements DescriptionInterpreter {

	/**
	 * @var QuerySegmentListBuilder
	 */
	private $querySegmentListBuilder;

	/**
	 * @since 2.2
	 *
	 * @param QuerySegmentListBuilder $querySegmentListBuilder
	 */
	public function __construct( QuerySegmentListBuilder $querySegmentListBuilder ) {
		$this->querySegmentListBuilder = $querySegmentListBuilder;
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
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

			$categoryId = $this->querySegmentListBuilder->getStore()->getObjectIds()->getSMWPageID(
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
			$query->joinTable = $this->querySegmentListBuilder->getStore()->findPropertyTableID( new DIProperty( '_INST' ) );
			$query->joinfield = "$query->alias.s_id";
			$query->components[$cqid] = "$query->alias.o_id";

			$this->querySegmentListBuilder->addQuerySegment( $cquery );
		}

		return $query;
	}

}
