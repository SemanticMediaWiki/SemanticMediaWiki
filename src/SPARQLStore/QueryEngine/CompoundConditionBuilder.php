<?php

namespace SMW\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\Condition\Condition;
use SMW\SPARQLStore\QueryEngine\Condition\TrueCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;

use SMW\Query\Language\Description;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;

use SMW\DataTypeRegistry;
use SMW\DIProperty;
use SMW\DIWikiPage;

use SMWDataItem as DataItem;
use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;
use SMWExpNsResource as ExpNsResource;
use SMWExpElement as ExpElement;

use RuntimeException;

/**
 * Build an internal representation for a SPARQL condition from individual query
 * descriptions
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 */
class CompoundConditionBuilder {

	/**
	 * @var ConditionBuilderStrategyFinder
	 */
	private $conditionBuilderStrategyFinder = null;

	/**
	 * Counter used to generate globally fresh variables.
	 * @var integer
	 */
	private $variableCounter = 0;

	/**
	 * Sortkeys that are being used while building the query conditions
	 * @var array
	 */
	private $sortkeys = array();

	/**
	 * The name of the SPARQL variable that represents the query result
	 * @var string
	 */
	private $resultVariable = 'result';

	/**
	 * @since 2.0
	 *
	 * @param string $resultVariable
	 */
	public function setResultVariable( $resultVariable ) {
		$this->resultVariable = $resultVariable;
		return $this;
	}

	/**
	 * @since 2.0
	 *
	 * @param array $sortkeys
	 */
	public function setSortKeys( $sortkeys ) {
		$this->sortkeys = $sortkeys;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getSortKeys() {
		return $this->sortkeys;
	}

	/**
	 * Get a Condition object for an Description.
	 *
	 * This conversion is implemented by a number of recursive functions,
	 * and this is the main entry point for this recursion. In particular,
	 * it resets global variables that are used for the construction.
	 *
	 * If property value variables should be recorded for ordering results
	 * later on, the keys of the respective properties need to be given in
	 * sortkeys earlier.
	 *
	 * @param Description $description
	 *
	 * @return Condition
	 */
	public function buildCondition( Description $description ) {
		$this->variableCounter = 0;
		$condition = $this->mapDescriptionToCondition( $description, $this->resultVariable, null );
		$this->addMissingOrderByConditions( $condition );
		return $condition;
	}

	/**
	 * Build the condition (WHERE) string for a given Condition.
	 * The function also expresses the single value of
	 * SingletonCondition objects in the condition, which may
	 * lead to additional namespaces for serializing its URI.
	 *
	 * @param Condition $condition
	 *
	 * @return string
	 */
	public function convertConditionToString( Condition &$condition ) {

		$conditionAsString = $condition->getWeakConditionString();

		if ( ( $conditionAsString === '' ) && !$condition->isSafe() ) {
			$swivtPageResource = Exporter::getSpecialNsResource( 'swivt', 'page' );
			$conditionAsString = '?' . $this->resultVariable . ' ' . $swivtPageResource->getQName() . " ?url .\n";
		}

		$conditionAsString .= $condition->getCondition();

		if ( $condition instanceof SingletonCondition ) { // prepare for ASK, maybe rather use BIND?

			$matchElement = $condition->matchElement;

			if ( $matchElement instanceof ExpElement ) {
				$matchElementName = TurtleSerializer::getTurtleNameForExpElement( $matchElement );
			} else {
				$matchElementName = $matchElement;
			}

			if ( $matchElement instanceof ExpNsResource ) {
				$condition->namespaces[$matchElement->getNamespaceId()] = $matchElement->getNamespace();
			}

			$conditionAsString = str_replace( '?' . $this->resultVariable . ' ', "$matchElementName ", $conditionAsString );
		}

		return $conditionAsString;
	}

	/**
	 * Recursively create an Condition from an Description.
	 *
	 * @param $description Description
	 * @param $joinVariable string name of the variable that conditions
	 * will refer to
	 * @param $orderByProperty mixed DIProperty or null, if given then
	 * this is the property the values of which this condition will refer
	 * to, and the condition should also enable ordering by this value
	 * @return Condition
	 */
	public function mapDescriptionToCondition( Description $description, $joinVariable, $orderByProperty ) {
		return $this->findBuildStrategyForDescription( $description )->buildCondition(
			$description,
			$joinVariable,
			$orderByProperty
		);
	}

	/**
	 * Create an Condition from an empty (true) description.
	 * May still require helper conditions for ordering.
	 *
	 * @param $joinVariable string name, see mapDescriptionToCondition()
	 * @param $orderByProperty mixed DIProperty or null, see mapDescriptionToCondition()
	 *
	 * @return Condition
	 */
	public function buildTrueCondition( $joinVariable, $orderByProperty ) {
		$result = new TrueCondition();
		$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty );
		return $result;
	}

	/**
	 * Get a fresh unused variable name for building SPARQL conditions.
	 *
	 * @return string
	 */
	public function getNextVariable() {
		return 'v' . ( ++$this->variableCounter );
	}

	/**
	 * Extend the given SPARQL condition by a suitable order by variable,
	 * if an order by property is set.
	 *
	 * @param Condition $sparqlCondition condition to modify
	 * @param string $mainVariable the variable that represents the value to be ordered
	 * @param mixed $orderByProperty DIProperty or null
	 * @param integer $diType DataItem type id if known, or DataItem::TYPE_NOTYPE to determine it from the property
	 */
	public function addOrderByDataForProperty( Condition &$sparqlCondition, $mainVariable, $orderByProperty, $diType = DataItem::TYPE_NOTYPE ) {
		if ( is_null( $orderByProperty ) ) {
			return;
		}

		if ( $diType == DataItem::TYPE_NOTYPE ) {
			$diType = DataTypeRegistry::getInstance()->getDataItemId( $orderByProperty->findPropertyTypeID() );
		}

		$this->addOrderByData( $sparqlCondition, $mainVariable, $diType );
	}

	/**
	 * Extend the given SPARQL condition by a suitable order by variable,
	 * possibly adding conditions if required for the type of data.
	 *
	 * @param Condition $sparqlCondition condition to modify
	 * @param string $mainVariable the variable that represents the value to be ordered
	 * @param integer $diType DataItem type id
	 */
	public function addOrderByData( Condition &$condition, $mainVariable, $diType ) {

		if ( $diType !== DataItem::TYPE_WIKIPAGE ) {
			return $condition->orderByVariable = $mainVariable;
		}

		$condition->orderByVariable = $mainVariable . 'sk';
		$skeyExpElement = Exporter::getSpecialPropertyResource( '_SKEY' );

		$weakConditions = array(
			$condition->orderByVariable =>"?$mainVariable " . $skeyExpElement->getQName() . " ?{$condition->orderByVariable} .\n"
		);

		$condition->weakConditions += $weakConditions;
	}

	/**
	 * Extend the given Condition with additional conditions to
	 * ensure that it can be ordered by all requested properties. After
	 * this operation, every key in sortkeys is assigned to a query
	 * variable by $sparqlCondition->orderVariables.
	 *
	 * @param Condition $condition condition to modify
	 */
	protected function addMissingOrderByConditions( Condition &$condition ) {
		foreach ( $this->sortkeys as $propertyKey => $order ) {

			if ( !is_string( $propertyKey ) ) {
				throw new RuntimeException( "Expected a string value as sortkey" );
			}

			if ( !array_key_exists( $propertyKey, $condition->orderVariables ) ) { // Find missing property to sort by.
				$this->addOrderForUnknownPropertyKey( $condition, $propertyKey );
			}
		}
	}

	private function addOrderForUnknownPropertyKey( Condition &$condition, $propertyKey ) {

		if ( $propertyKey === '' ) { // order by result page sortkey

			$this->addOrderByData(
				$condition,
				$this->resultVariable,
				DataItem::TYPE_WIKIPAGE
			);

			$condition->orderVariables[$propertyKey] = $condition->orderByVariable;
			return;
		}

		$auxDescription = new SomeProperty(
			new DIProperty( $propertyKey ),
			new ThingDescription()
		);

		$auxCondition = $this->mapDescriptionToCondition(
			$auxDescription,
			$this->resultVariable,
			null
		);

		// orderVariables MUST be set for $propertyKey -- or there is a bug; let it show!
		$condition->orderVariables[$propertyKey] = $auxCondition->orderVariables[$propertyKey];
		$condition->weakConditions[$condition->orderVariables[$propertyKey]] = $auxCondition->getWeakConditionString() . $auxCondition->getCondition();
		$condition->namespaces = array_merge( $condition->namespaces, $auxCondition->namespaces );
	}

	private function findBuildStrategyForDescription( Description $description ) {

		if ( $this->conditionBuilderStrategyFinder === null ) {
			 $this->conditionBuilderStrategyFinder = new ConditionBuilderStrategyFinder( $this );
		}

		return $this->conditionBuilderStrategyFinder->findStrategyForDescription( $description );
	}

}
