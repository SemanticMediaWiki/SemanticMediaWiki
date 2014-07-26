<?php

namespace SMW\SPARQLStore\QueryEngine;

use SMW\SPARQLStore\QueryEngine\Condition\Condition;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\TrueCondition;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;

use SMW\DataTypeRegistry;
use SMW\Store;
use SMW\DIProperty;
use SMW\DIWikiPage;

use SMWDataItem as DataItem;
use SMWDIBlob as DIBlob;
use SMWDescription as Description;
use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;
use SMWExpNsResource as ExpNsResource;
use SMWExpLiteral as ExpLiteral;
use SMWSomeProperty as SomeProperty;
use SMWNamespaceDescription as NamespaceDescription;
use SMWConjunction as Conjunction;
use SMWDisjunction as Disjunction;
use SMWClassDescription as ClassDescription;
use SMWValueDescription as ValueDescription;
use SMWConceptDescription as ConceptDescription;
use SMWThingDescription as ThingDescription;

/**
 * Condition mapping from Query objects to SPARQL
 *
 * @ingroup SMWStore
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author Markus Krötzsch
 */
class QueryConditionBuilder {

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
		$condition = $this->mapConditionBuilderToDescription( $description, $this->resultVariable, null );
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
			$matchElementName = TurtleSerializer::getTurtleNameForExpElement( $matchElement );

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
	protected function mapConditionBuilderToDescription( Description $description, $joinVariable, $orderByProperty ) {

		if ( $description instanceof SomeProperty ) {
			return $this->buildPropertyCondition( $description, $joinVariable, $orderByProperty );
		} elseif ( $description instanceof NamespaceDescription ) {
			return $this->buildNamespaceCondition( $description, $joinVariable, $orderByProperty );
		} elseif ( $description instanceof Conjunction ) {
			return $this->buildConjunctionCondition( $description, $joinVariable, $orderByProperty );
		} elseif ( $description instanceof Disjunction ) {
			return $this->buildDisjunctionCondition( $description, $joinVariable, $orderByProperty );
		} elseif ( $description instanceof ClassDescription ) {
			return $this->buildClassCondition( $description, $joinVariable, $orderByProperty );
		} elseif ( $description instanceof ValueDescription ) {
			return $this->buildValueCondition( $description, $joinVariable, $orderByProperty );
		} elseif ( $description instanceof ConceptDescription ) {
			return new TrueCondition(); ///TODO Implement concept queries
		}

		 // (e.g. ThingDescription)
		return $this->buildTrueCondition( $joinVariable, $orderByProperty );
	}

	/**
	 * Recursively create an Condition from an Conjunction.
	 *
	 * @param $description Conjunction
	 * @param $joinVariable string name, see mapConditionBuilderToDescription()
	 * @param $orderByProperty mixed DIProperty or null, see mapConditionBuilderToDescription()
	 *
	 * @return Condition
	 */
	protected function buildConjunctionCondition( Conjunction $description, $joinVariable, $orderByProperty ) {

		$subDescriptions = $description->getDescriptions();

		if ( count( $subDescriptions ) == 0 ) { // empty conjunction: true
			return $this->buildTrueCondition( $joinVariable, $orderByProperty );
		} elseif ( count( $subDescriptions ) == 1 ) { // conjunction with one element
			return $this->mapConditionBuilderToDescription( reset( $subDescriptions ), $joinVariable, $orderByProperty );
		}

		$condition = '';
		$filter = '';
		$namespaces = $weakConditions = $orderVariables = array();
		$singletonMatchElement = null;
		$singletonMatchElementName = '';
		$hasSafeSubconditions = false;

		foreach ( $subDescriptions as $subDescription ) {

			$subCondition = $this->mapConditionBuilderToDescription( $subDescription, $joinVariable, null );

			if ( $subCondition instanceof FalseCondition ) {
				return new FalseCondition();
			} elseif ( $subCondition instanceof TrueCondition ) {
				// ignore true conditions in a conjunction
			} elseif ( $subCondition instanceof WhereCondition ) {
				$condition .= $subCondition->condition;
			} elseif ( $subCondition instanceof FilterCondition ) {
				$filter .= ( $filter ? ' && ' : '' ) . $subCondition->filter;
			} elseif ( $subCondition instanceof SingletonCondition ) {
				$matchElement = $subCondition->matchElement;
				$matchElementName = TurtleSerializer::getTurtleNameForExpElement( $matchElement );

				if ( $matchElement instanceof ExpNsResource ) {
					$namespaces[$matchElement->getNamespaceId()] = $matchElement->getNamespace();
				}

				if ( ( !is_null( $singletonMatchElement ) ) &&
				     ( $singletonMatchElementName !== $matchElementName ) ) {
					return new FalseCondition();
				}

				$condition .= $subCondition->condition;
				$singletonMatchElement = $subCondition->matchElement;
				$singletonMatchElementName = $matchElementName;
			}

			$hasSafeSubconditions = $hasSafeSubconditions || $subCondition->isSafe();
			$namespaces = array_merge( $namespaces, $subCondition->namespaces );
			$weakConditions = array_merge( $weakConditions, $subCondition->weakConditions );
			$orderVariables = array_merge( $orderVariables, $subCondition->orderVariables );
		}

		if ( !is_null( $singletonMatchElement ) ) {
			if ( $filter !== '' ) {
				$condition .= "FILTER( $filter )";
			}

			$result = new SingletonCondition(
				$singletonMatchElement,
				$condition,
				$hasSafeSubconditions,
				$namespaces
			);

		} elseif ( $condition === '' ) {
			$result = new FilterCondition( $filter, $namespaces );
		} else {
			if ( $filter !== '' ) {
				$condition .= "FILTER( $filter )";
			}

			$result = new WhereCondition( $condition, $hasSafeSubconditions, $namespaces );
		}

		$result->weakConditions = $weakConditions;
		$result->orderVariables = $orderVariables;

		$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty );

		return $result;
	}

	/**
	 * Recursively create an Condition from an Disjunction.
	 *
	 * @param $description Disjunction
	 * @param $joinVariable string name, see mapConditionBuilderToDescription()
	 * @param $orderByProperty mixed DIProperty or null, see mapConditionBuilderToDescription()
	 *
	 * @return Condition
	 */
	protected function buildDisjunctionCondition( Disjunction $description, $joinVariable, $orderByProperty ) {
		$subDescriptions = $description->getDescriptions();
		if ( count( $subDescriptions ) == 0 ) { // empty disjunction: false
			return new FalseCondition();
		} elseif ( count( $subDescriptions ) == 1 ) { // disjunction with one element
			return $this->mapConditionBuilderToDescription( reset( $subDescriptions ), $joinVariable, $orderByProperty );
		} // else: proper disjunction; note that orderVariables found in subconditions cannot be used for the whole disjunction

		$unionCondition = '';
		$filter = '';
		$namespaces = $weakConditions = array();
		$hasSafeSubconditions = false;
		foreach ( $subDescriptions as $subDescription ) {
			$subCondition = $this->mapConditionBuilderToDescription( $subDescription, $joinVariable, null );
			if ( $subCondition instanceof FalseCondition ) {
				// empty parts in a disjunction can be ignored
			} elseif ( $subCondition instanceof TrueCondition ) {
				return  $this->buildTrueCondition( $joinVariable, $orderByProperty );
			} elseif ( $subCondition instanceof WhereCondition ) {
				$hasSafeSubconditions = $hasSafeSubconditions || $subCondition->isSafe();
				$unionCondition .= ( $unionCondition ? ' UNION ' : '' ) .
				                   "{\n" . $subCondition->condition . "}";
			} elseif ( $subCondition instanceof FilterCondition ) {
				$filter .= ( $filter ? ' || ' : '' ) . $subCondition->filter;
			} elseif ( $subCondition instanceof SingletonCondition ) {
				$hasSafeSubconditions = $hasSafeSubconditions || $subCondition->isSafe();
				$matchElement = $subCondition->matchElement;
				$matchElementName = TurtleSerializer::getTurtleNameForExpElement( $matchElement );
				if ( $matchElement instanceof ExpNsResource ) {
					$namespaces[$matchElement->getNamespaceId()] = $matchElement->getNamespace();
				}
				if ( $subCondition->condition === '' ) {
					$filter .= ( $filter ? ' || ' : '' ) . "?$joinVariable = $matchElementName";
				} else {
					$unionCondition .= ( $unionCondition ? ' UNION ' : '' ) .
				                   "{\n" . $subCondition->condition . " FILTER( ?$joinVariable = $matchElementName ) }";
				}
			}
			$namespaces = array_merge( $namespaces, $subCondition->namespaces );
			$weakConditions = array_merge( $weakConditions, $subCondition->weakConditions );
		}

		if ( ( $unionCondition === '' ) && ( $filter === '' ) ) {
			return new FalseCondition();
		} elseif ( $unionCondition === '' ) {
			$result = new FilterCondition( $filter, $namespaces );
		} elseif ( $filter === '' ) {
			$result = new WhereCondition( $unionCondition, $hasSafeSubconditions, $namespaces );
		} else {
			$subJoinVariable = $this->getNextVariable();
			$unionCondition = str_replace( "?$joinVariable ", "?$subJoinVariable ", $unionCondition );
			$filter .= " || ?$joinVariable = ?$subJoinVariable";
			$result = new WhereCondition( "OPTIONAL { $unionCondition }\n FILTER( $filter )\n", false, $namespaces );
		}

		$result->weakConditions = $weakConditions;

		$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty );

		return $result;
	}

	/**
	 * Recursively create an Condition from an SomeProperty.
	 *
	 * @param $description SomeProperty
	 * @param $joinVariable string name, see mapConditionBuilderToDescription()
	 * @param $orderByProperty mixed DIProperty or null, see mapConditionBuilderToDescription()
	 *
	 * @return Condition
	 */
	protected function buildPropertyCondition( SomeProperty $description, $joinVariable, $orderByProperty ) {
		$diProperty = $description->getProperty();

		//*** Find out if we should order by the values of this property ***//
		if ( array_key_exists( $diProperty->getKey(), $this->sortkeys ) ) {
			$innerOrderByProperty = $diProperty;
		} else {
			$innerOrderByProperty = null;
		}

		//*** Prepare inner condition ***//
		$innerJoinVariable = $this->getNextVariable();
		$innerCondition = $this->mapConditionBuilderToDescription( $description->getDescription(), $innerJoinVariable, $innerOrderByProperty );
		$namespaces = $innerCondition->namespaces;

		if ( $innerCondition instanceof FalseCondition ) {
			return new FalseCondition();
		} elseif ( $innerCondition instanceof SingletonCondition ) {
			$matchElement = $innerCondition->matchElement;
			$objectName = TurtleSerializer::getTurtleNameForExpElement( $matchElement );
			if ( $matchElement instanceof ExpNsResource ) {
				$namespaces[$matchElement->getNamespaceId()] = $matchElement->getNamespace();
			}
		} else {
			$objectName = '?' . $innerJoinVariable;
		}

		//*** Exchange arguments when property is inverse ***//
		if ( $diProperty->isInverse() ) { // don't check if this really makes sense
			$subjectName = $objectName;
			$objectName = '?' . $joinVariable;
			$diNonInverseProperty = new DIProperty( $diProperty->getKey(), false );
		} else {
			$subjectName = '?' . $joinVariable;
			$diNonInverseProperty = $diProperty;
		}

		//*** Build the condition ***//
		// Use helper properties in encoding values, refer to this helper property:
		if ( Exporter::hasHelperExpElement( $diProperty ) ) {
			$propertyExpElement = Exporter::getResourceElementForProperty( $diNonInverseProperty, true );
		} else {
			$propertyExpElement = Exporter::getResourceElementForProperty( $diNonInverseProperty );
		}

		$propertyName = TurtleSerializer::getTurtleNameForExpElement( $propertyExpElement );

		if ( $propertyExpElement instanceof ExpNsResource ) {
			$namespaces[$propertyExpElement->getNamespaceId()] = $propertyExpElement->getNamespace();
		}

		$condition = "$subjectName $propertyName $objectName .\n";
		$innerConditionString = $innerCondition->getCondition() . $innerCondition->getWeakConditionString();

		if ( $innerConditionString !== '' ) {
			if ( $innerCondition instanceof FilterCondition ) {
				$condition .= $innerConditionString;
			} else {
				$condition .= "{ $innerConditionString}\n";
			}
		}

		$result = new WhereCondition( $condition, true, $namespaces );

		//*** Record inner ordering variable if found ***//
		$result->orderVariables = $innerCondition->orderVariables;
		if ( !is_null( $innerOrderByProperty ) && ( $innerCondition->orderByVariable !== '' ) ) {
			$result->orderVariables[$diProperty->getKey()] = $innerCondition->orderByVariable;
		}

		$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty, DataItem::TYPE_WIKIPAGE );

		return $result;
	}

	/**
	 * Create an Condition from an ClassDescription.
	 *
	 * @param $description ClassDescription
	 * @param $joinVariable string name, see mapConditionBuilderToDescription()
	 * @param $orderByProperty mixed DIProperty or null, see mapConditionBuilderToDescription()
	 *
	 * @return Condition
	 */
	protected function buildClassCondition( ClassDescription $description, $joinVariable, $orderByProperty ) {

		$condition = '';
		$namespaces = array();
		$instExpElement = Exporter::getSpecialPropertyResource( '_INST' );

		foreach( $description->getCategories() as $diWikiPage ) {
			$categoryExpElement = Exporter::getResourceElementForWikiPage( $diWikiPage );
			$categoryName = TurtleSerializer::getTurtleNameForExpElement( $categoryExpElement );
			$namespaces[$categoryExpElement->getNamespaceId()] = $categoryExpElement->getNamespace();
			$newcondition = "{ ?$joinVariable " . $instExpElement->getQName() . " $categoryName . }\n";
			if ( $condition === '' ) {
				$condition = $newcondition;
			} else {
				$condition .= "UNION\n$newcondition";
			}
		}

		if ( $condition === '' ) { // empty disjunction: always false, no results to order
			return new FalseCondition();
		}

		$result = new WhereCondition( $condition, true, $namespaces );

		$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty, DataItem::TYPE_WIKIPAGE );

		return $result;
	}

	/**
	 * Create an Condition from an NamespaceDescription.
	 *
	 * @param $description NamespaceDescription
	 * @param $joinVariable string name, see mapConditionBuilderToDescription()
	 * @param $orderByProperty mixed DIProperty or null, see mapConditionBuilderToDescription()
	 *
	 * @return Condition
	 */
	protected function buildNamespaceCondition( NamespaceDescription $description, $joinVariable, $orderByProperty ) {
		$nspropExpElement = Exporter::getSpecialNsResource( 'swivt', 'wikiNamespace' );
		$nsExpElement = new ExpLiteral( strval( $description->getNamespace() ), 'http://www.w3.org/2001/XMLSchema#integer' );
		$nsName = TurtleSerializer::getTurtleNameForExpElement( $nsExpElement );
		$condition = "{ ?$joinVariable " . $nspropExpElement->getQName() . " $nsName . }\n";

		$result = new WhereCondition( $condition, true, array() );

		$this->addOrderByDataForProperty(
			$result,
			$joinVariable,
			$orderByProperty,
			DataItem::TYPE_WIKIPAGE
		);

		return $result;
	}

	/**
	 * Create an Condition from an ValueDescription.
	 *
	 * @param $description ValueDescription
	 * @param $joinVariable string name, see mapConditionBuilderToDescription()
	 * @param $orderByProperty mixed DIProperty or null, see mapConditionBuilderToDescription()
	 *
	 * @return Condition
	 */
	protected function buildValueCondition( ValueDescription $description, $joinVariable, $orderByProperty ) {
		$dataItem = $description->getDataItem();

		switch ( $description->getComparator() ) {
			case SMW_CMP_EQ:   $comparator = '='; break;
			case SMW_CMP_LESS: $comparator = '<'; break;
			case SMW_CMP_GRTR: $comparator = '>'; break;
			case SMW_CMP_LEQ:  $comparator = '<='; break;
			case SMW_CMP_GEQ:  $comparator = '>='; break;
			case SMW_CMP_NEQ:  $comparator = '!='; break;
			case SMW_CMP_LIKE: $comparator = 'regex'; break;
			case SMW_CMP_NLKE: $comparator = '!regex'; break;
			default:           $comparator = ''; // unkown, unsupported
		}

		if ( $comparator === '' ) {
			$result = $this->buildTrueCondition( $joinVariable, $orderByProperty );
		} elseif ( $comparator == '=' ) {
			$expElement = Exporter::getDataItemHelperExpElement( $dataItem );
			if ( is_null( $expElement ) ) {
				$expElement = Exporter::getDataItemExpElement( $dataItem );
			}
			$result = new SingletonCondition( $expElement );
			$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty, $dataItem->getDIType() );
		} elseif ( $comparator == 'regex' || $comparator == '!regex' ) {
			if ( $dataItem instanceof DIBlob ) {
				$pattern = '^' . str_replace( array( '^', '.', '\\', '+', '{', '}', '(', ')', '|', '^', '$', '[', ']', '*', '?' ),
				                              array( '\^', '\.', '\\\\', '\+', '\{', '\}', '\(', '\)', '\|', '\^', '\$', '\[', '\]', '.*', '.' ),
				                              $dataItem->getString() ) . '$';
				$result = new FilterCondition( "$comparator( ?$joinVariable, \"$pattern\", \"s\")", array() );
				$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty, $dataItem->getDIType() );
			} else {
				$result = $this->buildTrueCondition( $joinVariable, $orderByProperty );
			}
		} else {
			$result = new FilterCondition( '', array() );
			$this->addOrderByData( $result, $joinVariable, $dataItem->getDIType() );
			$orderByVariable = $result->orderByVariable;

			if ( $dataItem instanceof DIWikiPage ) {
				$expElement = Exporter::getDataItemExpElement( $dataItem->getSortKeyDataItem() );
			} else {
				$expElement = Exporter::getDataItemHelperExpElement( $dataItem );
				if ( is_null( $expElement ) ) {
					$expElement = Exporter::getDataItemExpElement( $dataItem );
				}
			}

			$valueName = TurtleSerializer::getTurtleNameForExpElement( $expElement );

			if ( $expElement instanceof ExpNsResource ) {
				$result->namespaces[$expElement->getNamespaceId()] = $expElement->getNamespace();
			}

			$result->filter = "?$orderByVariable $comparator $valueName";
		}

		return $result;
	}

	/**
	 * Create an Condition from an empty (true) description.
	 * May still require helper conditions for ordering.
	 *
	 * @param $joinVariable string name, see mapConditionBuilderToDescription()
	 * @param $orderByProperty mixed DIProperty or null, see mapConditionBuilderToDescription()
	 *
	 * @return Condition
	 */
	protected function buildTrueCondition( $joinVariable, $orderByProperty ) {
		$result = new TrueCondition();
		$this->addOrderByDataForProperty( $result, $joinVariable, $orderByProperty );
		return $result;
	}

	/**
	 * Get a fresh unused variable name for building SPARQL conditions.
	 *
	 * @return string
	 */
	protected function getNextVariable() {
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
	protected function addOrderByDataForProperty( Condition &$sparqlCondition, $mainVariable, $orderByProperty, $diType = DataItem::TYPE_NOTYPE ) {
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
	protected function addOrderByData( Condition &$sparqlCondition, $mainVariable, $diType ) {
		if ( $diType == DataItem::TYPE_WIKIPAGE ) {
			$sparqlCondition->orderByVariable = $mainVariable . 'sk';
			$skeyExpElement = Exporter::getSpecialPropertyResource( '_SKEY' );
			$sparqlCondition->weakConditions = array( $sparqlCondition->orderByVariable =>
			      "?$mainVariable " . $skeyExpElement->getQName() . " ?{$sparqlCondition->orderByVariable} .\n" );
		} else {
			$sparqlCondition->orderByVariable = $mainVariable;
		}
	}

	/**
	 * Extend the given Condition with additional conditions to
	 * ensure that it can be ordered by all requested properties. After
	 * this operation, every key in sortkeys is assigned to a query
	 * variable by $sparqlCondition->orderVariables.
	 *
	 * @param Condition $sparqlCondition condition to modify
	 */
	protected function addMissingOrderByConditions( Condition &$sparqlCondition ) {
		foreach ( $this->sortkeys as $propkey => $order ) {
			if ( !array_key_exists( $propkey, $sparqlCondition->orderVariables ) ) { // Find missing property to sort by.

				if ( $propkey === '' ) { // order by result page sortkey
					$this->addOrderByData( $sparqlCondition, $this->resultVariable, DataItem::TYPE_WIKIPAGE );
					$sparqlCondition->orderVariables[$propkey] = $sparqlCondition->orderByVariable;
				} else { // extend query to order by other property values
					$diProperty = new DIProperty( $propkey );
					$auxDescription = new SomeProperty( $diProperty, new ThingDescription() );
					$auxSparqlCondition = $this->mapConditionBuilderToDescription( $auxDescription, $this->resultVariable, null );
					// orderVariables MUST be set for $propkey -- or there is a bug; let it show!
					$sparqlCondition->orderVariables[$propkey] = $auxSparqlCondition->orderVariables[$propkey];
					$sparqlCondition->weakConditions[$sparqlCondition->orderVariables[$propkey]] = $auxSparqlCondition->getWeakConditionString() . $auxSparqlCondition->getCondition();
					$sparqlCondition->namespaces = array_merge( $sparqlCondition->namespaces, $auxSparqlCondition->namespaces );
				}
			}
		}
	}

}
