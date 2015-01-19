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
use SMW\Query\Language\Conjunction;

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
class ConjunctionConditionBuilder implements ConditionBuilder {

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
		return $description instanceOf Conjunction;
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
	 * Recursively create an Condition from an Conjunction
	 *
	 * @param Conjunction $description
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
			$joinVariable
		);

		if ( $subConditionElements instanceOf FalseCondition ) {
			return $subConditionElements;
		}

		$result = $this->createConditionFromSubConditionElements( $subConditionElements );

		$result->weakConditions = $subConditionElements->weakConditions;
		$result->orderVariables = $subConditionElements->orderVariables;

		$this->compoundConditionBuilder->addOrderByDataForProperty(
			$result,
			$joinVariable,
			$orderByProperty
		);

		return $result;
	}

	private function doPreliminarySubDescriptionCheck( $subDescriptions, $joinVariable, $orderByProperty ) {

		$count = count( $subDescriptions );

		// empty conjunction: true
		if ( $count == 0 ) {
			return $this->compoundConditionBuilder->buildTrueCondition(
				$joinVariable,
				$orderByProperty
			);
		}

		// conjunction with one element
		if ( $count == 1 ) {
			return $this->compoundConditionBuilder->mapDescriptionToCondition(
				reset( $subDescriptions ),
				$joinVariable,
				$orderByProperty
			);
		}

		return null;
	}

	private function doResolveSubDescriptionsRecursively( $subDescriptions, $joinVariable ) {

		// Using a stdClass as data container for simpler handling in follow-up tasks
		// and as the class is not exposed publicly we don't need to create
		// an extra "real" class to manage its elements
		$subConditionElements = new \stdClass;

		$subConditionElements->condition = '';
		$subConditionElements->filter = '';
		$subConditionElements->singletonMatchElement = null;

		$namespaces = $weakConditions = $orderVariables = array();
		$singletonMatchElementName = '';
		$hasSafeSubconditions = false;

		foreach ( $subDescriptions as $subDescription ) {

			$subCondition = $this->compoundConditionBuilder->mapDescriptionToCondition( $subDescription, $joinVariable, null );

			if ( $subCondition instanceof FalseCondition ) {
				return new FalseCondition();
			} elseif ( $subCondition instanceof TrueCondition ) {
				// ignore true conditions in a conjunction
			} elseif ( $subCondition instanceof WhereCondition ) {
				$subConditionElements->condition .= $subCondition->condition;
			} elseif ( $subCondition instanceof FilterCondition ) {
				$subConditionElements->filter .= ( $subConditionElements->filter ? ' && ' : '' ) . $subCondition->filter;
			} elseif ( $subCondition instanceof SingletonCondition ) {
				$matchElement = $subCondition->matchElement;

				if ( $matchElement instanceOf ExpElement ) {
					$matchElementName = TurtleSerializer::getTurtleNameForExpElement( $matchElement );
				} else {
					$matchElementName = $matchElement;
				}

				if ( $matchElement instanceof ExpNsResource ) {
					$namespaces[$matchElement->getNamespaceId()] = $matchElement->getNamespace();
				}

				if ( ( $subConditionElements->singletonMatchElement !== null ) &&
				     ( $singletonMatchElementName !== $matchElementName ) ) {
					return new FalseCondition();
				}

				$subConditionElements->condition .= $subCondition->condition;
				$subConditionElements->singletonMatchElement = $subCondition->matchElement;
				$singletonMatchElementName = $matchElementName;
			}

			$hasSafeSubconditions = $hasSafeSubconditions || $subCondition->isSafe();
			$namespaces = array_merge( $namespaces, $subCondition->namespaces );
			$weakConditions = array_merge( $weakConditions, $subCondition->weakConditions );
			$orderVariables = array_merge( $orderVariables, $subCondition->orderVariables );
		}

		$subConditionElements->hasSafeSubconditions = $hasSafeSubconditions;
		$subConditionElements->namespaces = $namespaces;
		$subConditionElements->weakConditions = $weakConditions;
		$subConditionElements->orderVariables = $orderVariables;

		return $subConditionElements;
	}

	private function createConditionFromSubConditionElements( $subConditionElements ) {

		if ( $subConditionElements->singletonMatchElement !== null ) {
			return $this->createSingletonCondition( $subConditionElements );
		}

		if ( $subConditionElements->condition === '' ) {
			return $this->createFilterCondition( $subConditionElements );
		}

		return $this->createWhereCondition( $subConditionElements );
	}

	private function createSingletonCondition( $subConditionElements ) {

		if ( $subConditionElements->filter !== '' ) {
			$subConditionElements->condition .= "FILTER( $subConditionElements->filter )";
		}

		$result = new SingletonCondition(
			$subConditionElements->singletonMatchElement,
			$subConditionElements->condition,
			$subConditionElements->hasSafeSubconditions,
			$subConditionElements->namespaces
		);

		return $result;
	}

	private function createFilterCondition( $subConditionElements ) {
		return new FilterCondition(
			$subConditionElements->filter,
			$subConditionElements->namespaces
		);
	}

	private function createWhereCondition( $subConditionElements ) {

		if ( $subConditionElements->filter !== '' ) {
			$subConditionElements->condition .= "FILTER( $subConditionElements->filter )";
		}

		$result = new WhereCondition(
			$subConditionElements->condition,
			$subConditionElements->hasSafeSubconditions,
			$subConditionElements->namespaces
		);

		return $result;
	}

}
