<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\DIProperty;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Elastic\QueryEngine\Condition;
use SMW\Query\Language\ClassDescription;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ClassDescriptionInterpreter {

	/**
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

	/**
	 * @since 3.0
	 *
	 * @param ConditionBuilder $conditionBuilder
	 */
	public function __construct( ConditionBuilder $conditionBuilder ) {
		$this->conditionBuilder = $conditionBuilder;
	}

	/**
	 * @since 3.0
	 *
	 * @param ClassDescription $description
	 *
	 * @return Condition
	 */
	public function interpretDescription( ClassDescription $description, $isConjunction = false ) {

		$pid = 'P:' . $this->conditionBuilder->getID( new DIProperty( '_INST' ) );
		$field = 'wpgID';

		$dataItems = $description->getCategories();
		$hierarchyDepth = $description->getHierarchyDepth();

		$should = false;
		$params = [];

		// More than one member per list means OR
		if ( count( $dataItems ) > 1 ) {
			$should = true;
		}

		$fieldMapper = $this->conditionBuilder->getFieldMapper();

		foreach ( $dataItems as $dataItem ) {
			$value = $this->conditionBuilder->getID( $dataItem );

			$p = $fieldMapper->term( "$pid.$field", $value );
			$hierarchy = [];

			$ids = $this->conditionBuilder->findHierarchyMembers( $dataItem, $hierarchyDepth );

			if ( $ids !== [] ) {
				$hierarchy[] = $fieldMapper->terms( "$pid.$field", $ids );
			}

			// Hierarchies cannot be build as part of the normal index process
			// therefore use the consecutive list to build a chain of disjunctive
			// (should === OR) queries to match members of the list
			if ( $hierarchy !== [] ) {
				$params[] = $fieldMapper->bool( Condition::TYPE_SHOULD, array_merge( [ $p ], $hierarchy ) );
			} else {
				$params[] = $p;
			}
		}

		// This feature is NOT supported by the SQLStore!!
		// Encapsulate condition for something like `[[Category:!CatTest1]] ...`
		if ( isset( $description->isNegation ) && $description->isNegation ) {
			$condition = $this->conditionBuilder->newCondition( $params );
			$condition->type( Condition::TYPE_MUST_NOT );
		} else {
			// ??!! If the description contains more than one category then it is
			// interpret as OR (same as the SQLStore) and only in the case of an AND
			// it is represented as Conjunction
			$condition = $this->conditionBuilder->newCondition( $params );
			$condition->type( ( $should ? Condition::TYPE_SHOULD : Condition::TYPE_FILTER ) );
		}

		$condition->log( [ 'ClassDescription' => $description->getQueryString() ] );

		return $condition;
	}

}
