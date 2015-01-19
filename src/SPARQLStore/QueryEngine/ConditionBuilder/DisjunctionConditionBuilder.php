<?php

namespace SMW\SPARQLStore\QueryEngine\ConditionBuilder;

use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\Condition\Condition;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\TrueCondition;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;

use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;

use SMWDataItem as DataItem;
use SMWExpElement as ExpElement;
use SMWExpLiteral as ExpLiteral;
use SMWExpNsResource as ExpNsResource;
use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class DisjunctionConditionBuilder implements ConditionBuilder {

	/**
	 * @var CompoundConditionBuilder
	 */
	private $compoundConditionBuilder;

	/**
	 * @var Exporter
	 */
	private $exporter;

	/**
	 * @since 2.1
	 *
	 * @param CompoundConditionBuilder|null $compoundConditionBuilder
	 */
	public function __construct( CompoundConditionBuilder $compoundConditionBuilder = null ) {
		$this->compoundConditionBuilder = $compoundConditionBuilder;
		$this->exporter = Exporter::getInstance();
	}

	/**
	 * @since 2.1
	 *
	 * @param Description $description
	 *
	 * @return boolean
	 */
	public function canBuildConditionFor( Description $description ) {
		return $description instanceOf Disjunction;
	}

	/**
	 * @since 2.1
	 *
	 * @param CompoundConditionBuilder $compoundConditionBuilder
	 *
	 * @return self
	 */
	public function setCompoundConditionBuilder( CompoundConditionBuilder $compoundConditionBuilder ) {
		$this->compoundConditionBuilder = $compoundConditionBuilder;
		return $this;
	}

	/**
	 * Recursively create an Condition from an Disjunction
	 *
	 * @param Disjunction $description
	 * @param string $joinVariable
	 * @param DIProperty|null $orderByProperty
	 *
	 * @return Condition
	 */
	public function buildCondition( Description $description, $joinVariable, $orderByProperty = null ) {

		$subDescriptions = $description->getDescriptions();

		$result = $this->doPreliminarySubDescriptionCheck(
			$subDescriptions,
			$joinVariable,
			$orderByProperty
		);

		if ( $result !== null ) {
			return $result;
		}

		$subConditionElements = $this->doResolveSubDescriptionsRecursively(
			$subDescriptions,
			$joinVariable,
			$orderByProperty
		);

		if ( $subConditionElements instanceOf TrueCondition ) {
			return $subConditionElements;
		}

		if ( ( $subConditionElements->unionCondition === '' ) && ( $subConditionElements->filter === '' ) ) {
			return new FalseCondition();
		}

		$result = $this->createConditionFromSubConditionElements(
			$subConditionElements,
			$joinVariable
		);

		$result->weakConditions = $subConditionElements->weakConditions;

		$this->compoundConditionBuilder->addOrderByDataForProperty(
			$result,
			$joinVariable,
			$orderByProperty
		);

		return $result;
	}

	private function doPreliminarySubDescriptionCheck( $subDescriptions, $joinVariable, $orderByProperty ) {

		$count = count( $subDescriptions );

		// empty Disjunction: true
		if ( $count == 0 ) {
			return new FalseCondition();
		}

		// Disjunction with one element
		// else: proper disjunction; note that orderVariables found in subconditions cannot be used for the whole disjunction
		if ( $count == 1 ) {
			return $this->compoundConditionBuilder->mapDescriptionToCondition(
				reset( $subDescriptions ),
				$joinVariable,
				$orderByProperty
			);
		}

		return null;
	}

	private function doResolveSubDescriptionsRecursively( $subDescriptions, $joinVariable, $orderByProperty ) {

		// Using a stdClass as data container for simpler handling in follow-up tasks
		// and as the class is not exposed publicly we don't need to create
		// an extra "real" class to manage its elements
		$subConditionElements = new \stdClass;

		$subConditionElements->unionCondition = '';
		$subConditionElements->filter = '';

		$namespaces = $weakConditions = array();
		$hasSafeSubconditions = false;

		foreach ( $subDescriptions as $subDescription ) {

			$subCondition = $this->compoundConditionBuilder->mapDescriptionToCondition( $subDescription, $joinVariable, null );

			if ( $subCondition instanceof FalseCondition ) {
				// empty parts in a disjunction can be ignored
			} elseif ( $subCondition instanceof TrueCondition ) {
				return  $this->compoundConditionBuilder->buildTrueCondition( $joinVariable, $orderByProperty );
			} elseif ( $subCondition instanceof WhereCondition ) {
				$hasSafeSubconditions = $hasSafeSubconditions || $subCondition->isSafe();
				$subConditionElements->unionCondition .= ( $subConditionElements->unionCondition ? ' UNION ' : '' ) .
				                   "{\n" . $subCondition->condition . "}";
			} elseif ( $subCondition instanceof FilterCondition ) {
				$subConditionElements->filter .= ( $subConditionElements->filter ? ' || ' : '' ) . $subCondition->filter;
			} elseif ( $subCondition instanceof SingletonCondition ) {

				$hasSafeSubconditions = $hasSafeSubconditions || $subCondition->isSafe();
				$matchElement = $subCondition->matchElement;

				if ( $matchElement instanceOf ExpElement ) {
					$matchElementName = TurtleSerializer::getTurtleNameForExpElement( $matchElement );
				} else {
					$matchElementName = $matchElement;
				}

				if ( $matchElement instanceof ExpNsResource ) {
					$namespaces[$matchElement->getNamespaceId()] = $matchElement->getNamespace();
				}

				if ( $subCondition->condition === '' ) {
					$subConditionElements->filter .= ( $subConditionElements->filter ? ' || ' : '' ) . "?$joinVariable = $matchElementName";
				} else {
					$subConditionElements->unionCondition .= ( $subConditionElements->unionCondition ? ' UNION ' : '' ) .
				                   "{\n" . $subCondition->condition . " FILTER( ?$joinVariable = $matchElementName ) }";
				}
			}

			$namespaces = array_merge( $namespaces, $subCondition->namespaces );
			$weakConditions = array_merge( $weakConditions, $subCondition->weakConditions );
		}

		$subConditionElements->namespaces = $namespaces;
		$subConditionElements->weakConditions = $weakConditions;
		$subConditionElements->hasSafeSubconditions = $hasSafeSubconditions;

		return $subConditionElements;
	}

	private function createConditionFromSubConditionElements( $subConditionElements, $joinVariable ) {

		if ( $subConditionElements->unionCondition === '' ) {
			return $this->createFilterCondition( $subConditionElements );
		}

		if ( $subConditionElements->filter === '' ) {
			return $this->createWhereCondition( $subConditionElements );
		}

		$subJoinVariable = $this->compoundConditionBuilder->getNextVariable();

		$subConditionElements->unionCondition = str_replace(
			"?$joinVariable ",
			"?$subJoinVariable ",
			$subConditionElements->unionCondition
		);

		$subConditionElements->filter .= " || ?$joinVariable = ?$subJoinVariable";
		$subConditionElements->hasSafeSubconditions = false;

		$subConditionElements->unionCondition = "OPTIONAL { $subConditionElements->unionCondition }\n FILTER( $subConditionElements->filter )\n";

		return $this->createWhereCondition( $subConditionElements );
	}

	private function createFilterCondition( $subConditionElements ) {
		return new FilterCondition(
			$subConditionElements->filter,
			$subConditionElements->namespaces
		);
	}

	private function createWhereCondition( $subConditionElements ) {
		return new WhereCondition(
			$subConditionElements->unionCondition,
			$subConditionElements->hasSafeSubconditions,
			$subConditionElements->namespaces
		);
	}

}
