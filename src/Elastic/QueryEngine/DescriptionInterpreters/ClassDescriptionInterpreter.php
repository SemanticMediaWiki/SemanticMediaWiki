<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\DIProperty;
use SMW\Elastic\QueryEngine\QueryBuilder;
use SMW\Query\Language\ClassDescription;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ClassDescriptionInterpreter {

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @since 3.0
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @since 3.0
	 *
	 * @param ClassDescription $description
	 *
	 * @return Condition
	 */
	public function interpretDescription( ClassDescription $description, $isConjunction = false ) {

		$pid = 'P:' . $this->queryBuilder->getID( new DIProperty( '_INST' ) );
		$field = 'wpgID';

		$dataItems = $description->getCategories();
		$hierarchyDepth = $description->getHierarchyDepth();

		$should = !$isConjunction;
		$params = [];

		// More than one member per list means OR
		if ( count( $dataItems ) > 1 ) {
			$should = true;
		}

		$fieldMapper = $this->queryBuilder->getFieldMapper();

		foreach ( $dataItems as $dataItem ) {
			$value = $this->queryBuilder->getID( $dataItem );

			$p = $fieldMapper->term( "$pid.$field", $value );
			$hierarchy = [];

			$ids = $this->queryBuilder->findHierarchyMembers( $dataItem, $hierarchyDepth );

			if ( $ids !== [] ) {
				$hierarchy[] = $fieldMapper->terms( "$pid.$field", $ids );
			}

			// Hierarchies cannot be build as part of the normal index process
			// therefore use the consecutive list to build a chain of disjunctive
			// (should === OR) queries to match members of the list
			if ( $hierarchy !== [] ) {
				$params[] = $fieldMapper->bool( 'should', array_merge( [ $p ], $hierarchy ) );
			} else {
				$params[] = $p;
			}
		}

		// Feature that is NOT supported by the SQLStore!!
		// Encapsulate condition for something like `[[Category:!CatTest1]] ...`
		if ( isset( $description->isNegation ) && $description->isNegation ) {
			$params = $this->queryBuilder->newCondition( $params );
			$params->type( 'must_not' );
		}

		// ??!! If the description contains more than one category then it is
		// interpret as OR (same as the SQLStore) and only in the case of an AND
		// it is represented as Conjunction
		$condition = $this->queryBuilder->newCondition( $params );

		$condition->type( ( $should ? 'should' : 'must' ) );
		$condition->log( [ 'ClassDescription' => $description->getQueryString() ] );

		return $condition;
	}

}
