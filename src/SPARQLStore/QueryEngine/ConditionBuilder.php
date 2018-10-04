<?php

namespace SMW\SPARQLStore\QueryEngine;

use RuntimeException;
use SMW\DataTypeRegistry;
use SMW\DataValues\PropertyChainValue;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\HierarchyLookup;
use SMW\Message;
use SMW\Query\DescriptionFactory;
use SMW\Query\Language\Description;
use SMW\SPARQLStore\HierarchyFinder;
use SMW\SPARQLStore\QueryEngine\Condition\Condition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\Condition\TrueCondition;
use SMW\Utils\CircularReferenceGuard;
use SMWDataItem as DataItem;
use SMWExpElement as ExpElement;
use SMWExpNsResource as ExpNsResource;
use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;

/**
 * Build an internal representation for a SPARQL condition from individual query
 * descriptions
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ConditionBuilder {

	/**
	 * @var EngineOptions
	 */
	private $engineOptions;

	/**
	 * @var DispatchingDescriptionInterpreter
	 */
	private $dispatchingDescriptionInterpreter;

	/**
	 * @var CircularReferenceGuard
	 */
	private $circularReferenceGuard;

	/**
	 * @var HierarchyLookup
	 */
	private $hierarchyLookup;

	/**
	 * @var DescriptionFactory
	 */
	private $descriptionFactory;

	/**
	 * @var array
	 */
	private $errors = [];

	/**
	 * Counter used to generate globally fresh variables.
	 * @var integer
	 */
	private $variableCounter = 0;

	/**
	 * sortKeys that are being used while building the query conditions
	 * @var array
	 */
	private $sortKeys = [];

	/**
	 * The name of the SPARQL variable that represents the query result
	 * @var string
	 */
	private $resultVariable = 'result';

	/**
	 * @var string
	 */
	private $joinVariable;

	/**
	 * @var DIProperty|null
	 */
	private $orderByProperty;

	/**
	 * @var array
	 */
	private $redirectByVariableReplacementMap = [];

	/**
	 * @since 2.2
	 *
	 * @param DescriptionInterpreterFactory $descriptionInterpreterFactory
	 * @param EngineOptions|null $engineOptions
	 */
	public function __construct( DescriptionInterpreterFactory $descriptionInterpreterFactory, EngineOptions $engineOptions = null ) {
		$this->dispatchingDescriptionInterpreter = $descriptionInterpreterFactory->newDispatchingDescriptionInterpreter( $this );
		$this->engineOptions = $engineOptions;

		if ( $this->engineOptions === null ) {
			$this->engineOptions = new EngineOptions();
		}

		$this->descriptionFactory = new DescriptionFactory();
	}

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
	 * Get a fresh unused variable name for building SPARQL conditions.
	 *
	 * @return string
	 */
	public function getNextVariable( $prefix = 'v' ) {
		return $prefix . ( ++$this->variableCounter );
	}

	/**
	 * @since 2.0
	 *
	 * @param array $sortKeys
	 */
	public function setSortKeys( $sortKeys ) {
		$this->sortKeys = $sortKeys;
		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getSortKeys() {
		return $this->sortKeys;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $error
	 */
	public function addError( $error, $type = Message::TEXT ) {
		$this->errors[Message::getHash( $error, $type )] = Message::encode( $error, $type );
	}

	/**
	 * @since 2.2
	 *
	 * @param CircularReferenceGuard $circularReferenceGuard
	 */
	public function setCircularReferenceGuard( CircularReferenceGuard $circularReferenceGuard ) {
		$this->circularReferenceGuard = $circularReferenceGuard;
	}

	/**
	 * @since 2.2
	 *
	 * @return CircularReferenceGuard
	 */
	public function getCircularReferenceGuard() {
		return $this->circularReferenceGuard;
	}

	/**
	 * @since 2.3
	 *
	 * @param HierarchyLookup $hierarchyLookup
	 */
	public function setHierarchyLookup( HierarchyLookup $hierarchyLookup ) {
		$this->hierarchyLookup = $hierarchyLookup;
	}

	/**
	 * @since 2.3
	 *
	 * @return HierarchyLookup
	 */
	public function getHierarchyLookup() {
		return $this->hierarchyLookup;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $joinVariable name of the variable that conditions
	 * will refer to
	 */
	public function setJoinVariable( $joinVariable ) {
		$this->joinVariable = $joinVariable;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getJoinVariable() {
		return $this->joinVariable;
	}

	/**
	 * @since 2.2
	 *
	 * @param DIProperty|null $orderByProperty if given then
	 * this is the property the values of which this condition will refer
	 * to, and the condition should also enable ordering by this value
	 */
	public function setOrderByProperty( $orderByProperty ) {
		$this->orderByProperty = $orderByProperty;
	}

	/**
	 * @since 2.2
	 *
	 * @return DIProperty|null
	 */
	public function getOrderByProperty() {
		return $this->orderByProperty;
	}

	/**
	 * Get a Condition object for a Description.
	 *
	 * This conversion is implemented by a number of recursive functions,
	 * and this is the main entry point for this recursion. In particular,
	 * it resets global variables that are used for the construction.
	 *
	 * If property value variables should be recorded for ordering results
	 * later on, the keys of the respective properties need to be given in
	 * sortKeys earlier.
	 *
	 * @param Description $description
	 *
	 * @return Condition
	 */
	public function getConditionFrom( Description $description ) {
		$this->variableCounter = 0;

		$this->setJoinVariable( $this->resultVariable );
		$this->setOrderByProperty( null );

		$condition = $this->mapDescriptionToCondition( $description );

		$this->addMissingOrderByConditions(
			$condition
		);

		$this->addPropertyPathToMatchRedirectTargets(
			$condition
		);

		$this->addFilterToRemoveEntitiesThatContainRedirectPredicate(
			$condition
		);

		return $condition;
	}

	/**
	 * Recursively create a Condition from a Description
	 *
	 * @param Description $description
	 *
	 * @return Condition
	 */
	public function mapDescriptionToCondition( Description $description ) {
		return $this->dispatchingDescriptionInterpreter->interpretDescription( $description );
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
			$swivtPageResource = Exporter::getInstance()->getSpecialNsResource( 'swivt', 'page' );
			$conditionAsString = '?' . $this->resultVariable . ' ' . $swivtPageResource->getQName() . " ?url .\n";
		}

		$conditionAsString .= $condition->getCondition();
		$conditionAsString .= $condition->getCogentConditionString();

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
	 * Create an Condition from an empty (true) description.
	 * May still require helper conditions for ordering.
	 *
	 * @param $joinVariable string name, see mapDescriptionToCondition()
	 * @param $orderByProperty mixed DIProperty or null, see mapDescriptionToCondition()
	 *
	 * @return Condition
	 */
	public function newTrueCondition( $joinVariable, $orderByProperty ) {
		$result = new TrueCondition();
		$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty );
		return $result;
	}

	/**
	 * @since 2.3
	 *
	 * @param DataItem|null $dataItem
	 *
	 * @return string|null
	 */
	public function tryToFindRedirectVariableForDataItem( DataItem $dataItem = null ) {

		if ( !$dataItem instanceof DIWikiPage || !$this->isSetFlag( SMW_SPARQL_QF_REDI ) ) {
			return null;
		}

		// Maybe there is a better way to verify the "isRedirect" state other
		// than by using the Title object
		if ( $dataItem->getTitle() === null || !$dataItem->getTitle()->isRedirect() ) {
			return null;
		}

		$redirectExpElement = Exporter::getInstance()->getResourceElementForWikiPage( $dataItem );

		// If the resource was matched to an imported vocab then no redirect is required
		if ( $redirectExpElement->isImported() ) {
			return null;
		}

		$valueName = TurtleSerializer::getTurtleNameForExpElement( $redirectExpElement );

		// Add unknow redirect target/variable for value
		if ( !isset( $this->redirectByVariableReplacementMap[$valueName] ) ) {

			$namespaces[$redirectExpElement->getNamespaceId()] = $redirectExpElement->getNamespace();
			$redirectByVariable = '?' . $this->getNextVariable( 'r' );

			$this->redirectByVariableReplacementMap[$valueName] = [
				$redirectByVariable,
				$namespaces
			];
		}

		// Reuse an existing variable for the value to allow to be used more than
		// once when referring to the same property/value redirect
		list( $redirectByVariable, $namespaces ) = $this->redirectByVariableReplacementMap[$valueName];

		return $redirectByVariable;
	}

	/**
	 * @since 2.3
	 *
	 * @param integer $featureFlag
	 *
	 * @return boolean
	 */
	public function isSetFlag( $featureFlag ) {

		$canUse = true;

		// Adhere additional condition
		if ( $featureFlag === SMW_SPARQL_QF_SUBP ) {
			$canUse = $this->engineOptions->get( 'smwgQSubpropertyDepth' ) > 0;
		}

		if ( $featureFlag === SMW_SPARQL_QF_SUBC ) {
			$canUse = $this->engineOptions->get( 'smwgQSubcategoryDepth' ) > 0;
		}

		return $this->engineOptions->get( 'smwgSparqlQFeatures' ) === ( (int)$this->engineOptions->get( 'smwgSparqlQFeatures' ) | (int)$featureFlag ) && $canUse;
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

		if ( $this->isSetFlag( SMW_SPARQL_QF_COLLATION ) ) {
			$skeyExpElement = Exporter::getInstance()->getSpecialNsResource( 'swivt', 'sort' );
		} else {
			$skeyExpElement = Exporter::getInstance()->getSpecialPropertyResource( '_SKEY' );
		}

		$weakConditions = [
			$condition->orderByVariable =>"?$mainVariable " . $skeyExpElement->getQName() . " ?{$condition->orderByVariable} .\n"
		];

		$condition->weakConditions += $weakConditions;
	}

	/**
	 * Extend the given Condition with additional conditions to
	 * ensure that it can be ordered by all requested properties. After
	 * this operation, every key in sortKeys is assigned to a query
	 * variable by $sparqlCondition->orderVariables.
	 *
	 * @param Condition $condition condition to modify
	 */
	protected function addMissingOrderByConditions( Condition &$condition ) {
		foreach ( $this->sortKeys as $propertyKey => $order ) {

			if ( !is_string( $propertyKey ) ) {
				throw new RuntimeException( "Expected a string value as sortkey" );
			}

			if ( strpos( $propertyKey, " " ) !== false ) {
				throw new RuntimeException( "Expected the canonical form of {$propertyKey} (without any whitespace)" );
			}

			if ( !array_key_exists( $propertyKey, $condition->orderVariables ) ) { // Find missing property to sort by.
				$this->addOrderForUnknownPropertyKey( $condition, $propertyKey, $order );
			}
		}
	}

	private function addOrderForUnknownPropertyKey( Condition &$condition, $propertyKey, $order ) {

		if ( $propertyKey === '' || $propertyKey === '#' ) { // order by result page sortkey

			$this->addOrderByData(
				$condition,
				$this->resultVariable,
				DataItem::TYPE_WIKIPAGE
			);

			$condition->orderVariables[$propertyKey] = $condition->orderByVariable;
			return;
		} elseif ( PropertyChainValue::isChained( $propertyKey ) ) { // Try to extend query.
			$propertyChainValue = new PropertyChainValue();
			$propertyChainValue->setUserValue( $propertyKey );

			if ( !$propertyChainValue->isValid() ) {
				return $description;
			}

			$lastDataItem = $propertyChainValue->getLastPropertyChainValue()->getDataItem();

			$description = $this->descriptionFactory->newSomeProperty(
				$lastDataItem,
				$this->descriptionFactory->newThingDescription()
			);

			foreach ( $propertyChainValue->getPropertyChainValues() as $val ) {
				$description = $this->descriptionFactory->newSomeProperty(
					$val->getDataItem(),
					$description
				);
			}

			// Add and replace Foo.Bar=asc with Bar=asc as we ultimately only
			// order to the result of the last element
			$this->sortKeys[$lastDataItem->getKey()] = $order;
			unset( $this->sortKeys[$propertyKey] );
			$propertyKey = $lastDataItem->getKey();

			$auxDescription = $description;
		} else {
			$auxDescription = $this->descriptionFactory->newSomeProperty(
				new DIProperty( $propertyKey ),
				$this->descriptionFactory->newThingDescription()
			);
		}

		$this->setJoinVariable( $this->resultVariable );
		$this->setOrderByProperty( null );

		$auxCondition = $this->mapDescriptionToCondition(
			$auxDescription
		);

		// orderVariables MUST be set for $propertyKey -- or there is a bug; let it show!
		$condition->orderVariables[$propertyKey] = $auxCondition->orderVariables[$propertyKey];
		$condition->weakConditions[$condition->orderVariables[$propertyKey]] = $auxCondition->getWeakConditionString() . $auxCondition->getCondition();
		$condition->namespaces = array_merge( $condition->namespaces, $auxCondition->namespaces );
	}

	/**
	 * @see http://www.w3.org/TR/sparql11-query/#propertypaths
	 *
	 * Query of:
	 *
	 * SELECT DISTINCT ?result WHERE {
	 *	?result swivt:wikiPageSortKey ?resultsk .
	 *	{
	 *		?result property:FOO ?v1 .
	 *		FILTER( ?v1sk >= "=BAR" )
	 *		?v1 swivt:wikiPageSortKey ?v1sk .
	 *	} UNION {
	 *		?result property:FOO ?v2 .
	 *	}
	 * }
	 *
	 * results in:
	 *
	 * SELECT DISTINCT ?result WHERE {
	 *	?result swivt:wikiPageSortKey ?resultsk .
	 *	?r2 ^swivt:redirectsTo property:FOO .
	 *	{
	 *		?result ?r2 ?v1 .
	 *		FILTER( ?v1sk >= "=BAR" )
	 *		?v1 swivt:wikiPageSortKey ?v1sk .
	 *	} UNION {
	 *		?result ?r2 ?v3 .
	 *	}
	 * }
	 */
	private function addPropertyPathToMatchRedirectTargets( Condition &$condition ) {

		if ( $this->redirectByVariableReplacementMap === [] ) {
			return;
		}

		$weakConditions = [];
		$namespaces = [];

		$rediExpElement = Exporter::getInstance()->getSpecialPropertyResource( '_REDI' );
		$namespaces[$rediExpElement->getNamespaceId()] = $rediExpElement->getNamespace();

		foreach ( $this->redirectByVariableReplacementMap as $valueName => $content ) {
			list( $redirectByVariable, $ns ) = $content;
			$weakConditions[] = "$redirectByVariable " . "^" . $rediExpElement->getQName() . " $valueName .\n";
			$namespaces = array_merge( $namespaces, $ns );
		}

		$condition->namespaces = array_merge( $condition->namespaces, $namespaces );
		$condition->weakConditions += $weakConditions;
	}

	/**
	 * @see https://www.w3.org/TR/rdf-sparql-query/#func-bound
	 *
	 * Remove entities that contain a "swivt:redirectsTo" predicate
	 */
	private function addFilterToRemoveEntitiesThatContainRedirectPredicate( Condition &$condition ) {

		$rediExpElement = Exporter::getInstance()->getSpecialPropertyResource( '_REDI' );
		$namespaces[$rediExpElement->getNamespaceId()] = $rediExpElement->getNamespace();

		$boundVariable = '?' . $this->getNextVariable( 'o' );
		$cogentCondition = " OPTIONAL { ?$this->resultVariable " . $rediExpElement->getQName() . " $boundVariable } .\n FILTER ( !bound( $boundVariable ) ) .\n";

		$condition->addNamespaces( $namespaces );
		$condition->cogentConditions[$boundVariable] = $cogentCondition;
	}

}
