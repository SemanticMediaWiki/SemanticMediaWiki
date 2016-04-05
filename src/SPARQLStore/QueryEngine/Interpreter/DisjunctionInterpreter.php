<?php

namespace SMW\SPARQLStore\QueryEngine\Interpreter;

use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\Condition\TrueCondition;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
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
class DisjunctionInterpreter implements DescriptionInterpreter {

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
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function canInterpretDescription( Description $description ) {
		return $description instanceof Disjunction;
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function interpretDescription( Description $description ) {

		$joinVariable = $this->compoundConditionBuilder->getJoinVariable();
		$orderByProperty = $this->compoundConditionBuilder->getOrderByProperty();

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

		if ( $subConditionElements instanceof TrueCondition ) {
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

			$this->compoundConditionBuilder->setJoinVariable( $joinVariable );
			$this->compoundConditionBuilder->setOrderByProperty( $orderByProperty );

			return $this->compoundConditionBuilder->mapDescriptionToCondition(
				reset( $subDescriptions )
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

			$this->compoundConditionBuilder->setJoinVariable( $joinVariable );
			$this->compoundConditionBuilder->setOrderByProperty( null );

			$subCondition = $this->compoundConditionBuilder->mapDescriptionToCondition(
				$subDescription
			);

			if ( $subCondition instanceof FalseCondition ) {
				// empty parts in a disjunction can be ignored
			} elseif ( $subCondition instanceof TrueCondition ) {
				return $this->compoundConditionBuilder->newTrueCondition(
					$joinVariable,
					$orderByProperty
				);
			} elseif ( $subCondition instanceof WhereCondition ) {
				$hasSafeSubconditions = $hasSafeSubconditions || $subCondition->isSafe();
				$subConditionElements->unionCondition .= ( $subConditionElements->unionCondition ? ' UNION ' : '' ) .
				                   "{\n" . $subCondition->condition . "}";
			} elseif ( $subCondition instanceof FilterCondition ) {
				$subConditionElements->filter .= ( $subConditionElements->filter ? ' || ' : '' ) . $subCondition->filter;
			} elseif ( $subCondition instanceof SingletonCondition ) {

				$hasSafeSubconditions = $hasSafeSubconditions || $subCondition->isSafe();
				$matchElement = $subCondition->matchElement;

				if ( $matchElement instanceof ExpElement ) {
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

				// Relates to wikipage [[Foo::~*a*||~*A*]] in value regex disjunction
				// where a singleton is required to search against the sortkey but
				// replacing the filter with the condition temporary stored in
				// weakconditions
				if ( $subConditionElements->unionCondition && $subCondition->weakConditions !== array() ) {
					$weakCondition = array_shift( $subCondition->weakConditions );
					$subConditionElements->unionCondition = str_replace(
						"FILTER( ?$joinVariable = $matchElementName )",
						$weakCondition,
						$subConditionElements->unionCondition
					);
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
