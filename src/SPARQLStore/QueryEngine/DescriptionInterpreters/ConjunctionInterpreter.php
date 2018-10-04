<?php

namespace SMW\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\Condition\TrueCondition;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreter;
use SMWExpElement as ExpElement;
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
class ConjunctionInterpreter implements DescriptionInterpreter {

	/**
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

	/**
	 * @var Exporter
	 */
	private $exporter;

	/**
	 * @since 2.1
	 *
	 * @param ConditionBuilder|null $conditionBuilder
	 */
	public function __construct( ConditionBuilder $conditionBuilder = null ) {
		$this->conditionBuilder = $conditionBuilder;
		$this->exporter = Exporter::getInstance();
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function canInterpretDescription( Description $description ) {
		return $description instanceof Conjunction;
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function interpretDescription( Description $description ) {

		$joinVariable = $this->conditionBuilder->getJoinVariable();
		$orderByProperty = $this->conditionBuilder->getOrderByProperty();

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

		if ( $subConditionElements instanceof FalseCondition ) {
			return $subConditionElements;
		}

		$result = $this->createConditionFromSubConditionElements( $subConditionElements );

		$result->weakConditions = $subConditionElements->weakConditions;
		$result->orderVariables = $subConditionElements->orderVariables;

		$this->conditionBuilder->addOrderByDataForProperty(
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
			return $this->conditionBuilder->newTrueCondition(
				$joinVariable,
				$orderByProperty
			);
		}

		// conjunction with one element
		if ( $count == 1 ) {

			$this->conditionBuilder->setJoinVariable( $joinVariable );
			$this->conditionBuilder->setOrderByProperty( $orderByProperty );

			return $this->conditionBuilder->mapDescriptionToCondition(
				reset( $subDescriptions )
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

		$namespaces = $weakConditions = $orderVariables = [];
		$singletonMatchElementName = '';
		$hasSafeSubconditions = false;

		foreach ( $subDescriptions as $subDescription ) {

			$this->conditionBuilder->setJoinVariable( $joinVariable );
			$this->conditionBuilder->setOrderByProperty( null );

			$subCondition = $this->conditionBuilder->mapDescriptionToCondition(
				$subDescription
			);

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

				if ( $matchElement instanceof ExpElement ) {
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

		if ( $subConditionElements->singletonMatchElement instanceof ExpElement ) {
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
