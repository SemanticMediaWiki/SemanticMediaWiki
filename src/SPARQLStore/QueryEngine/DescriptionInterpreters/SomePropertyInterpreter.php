<?php

namespace SMW\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DIProperty;
use SMW\Query\Language\Description;
use SMW\Query\Language\SomeProperty;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreter;
use SMWDataItem as DataItem;
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
class SomePropertyInterpreter implements DescriptionInterpreter {

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
		return $description instanceof SomeProperty;
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function interpretDescription( Description $description ) {

		$joinVariable = $this->conditionBuilder->getJoinVariable();
		$orderByProperty = $this->conditionBuilder->getOrderByProperty();

		$property = $description->getProperty();

		list( $innerOrderByProperty, $innerCondition, $innerJoinVariable ) = $this->doResolveInnerConditionRecursively(
			$property,
			$description->getDescription()
		);

		if ( $innerCondition instanceof FalseCondition ) {
			return new FalseCondition();
		}

		$namespaces = $innerCondition->namespaces;

		$objectName = $this->findObjectNameFromInnerCondition(
			$innerCondition,
			$innerJoinVariable,
			$namespaces
		);

		list ( $subjectName, $objectName, $nonInverseProperty ) = $this->doExchangeForWhenInversePropertyIsUsed(
			$property,
			$objectName,
			$joinVariable
		);

		$propertyName = $this->findMostSuitablePropertyRepresentation(
			$property,
			$nonInverseProperty,
			$namespaces
		);

		$this->tryToAddPropertyPathForSaturatedHierarchy(
			$innerCondition,
			$nonInverseProperty,
			$propertyName,
			$description->getHierarchyDepth()
		);

		$condition = $this->concatenateToConditionString(
			$subjectName,
			$propertyName,
			$objectName,
			$innerCondition
		);

		$result = new WhereCondition( $condition, true, $namespaces );

		// Record inner ordering variable if found
		$result->orderVariables = $innerCondition->orderVariables;

		if ( $innerOrderByProperty !== null && $innerCondition->orderByVariable !== '' ) {
			$result->orderVariables[$property->getKey()] = $innerCondition->orderByVariable;
		}

		$this->conditionBuilder->addOrderByDataForProperty(
			$result,
			$joinVariable,
			$orderByProperty,
			DataItem::TYPE_WIKIPAGE
		);

		return $result;
	}

	private function doResolveInnerConditionRecursively( DIProperty $property, Description $description ) {

		$innerOrderByProperty = null;

		// Find out if we should order by the values of this property
		if ( array_key_exists( $property->getKey(), $this->conditionBuilder->getSortKeys() ) ) {
			$innerOrderByProperty = $property;
		}

		// Prepare inner condition
		$innerJoinVariable = $this->conditionBuilder->getNextVariable();

		$this->conditionBuilder->setJoinVariable( $innerJoinVariable );
		$this->conditionBuilder->setOrderByProperty( $innerOrderByProperty );

		$innerCondition = $this->conditionBuilder->mapDescriptionToCondition(
			$description
		);

		return [ $innerOrderByProperty, $innerCondition, $innerJoinVariable ];
	}

	private function findObjectNameFromInnerCondition( $innerCondition, $innerJoinVariable, &$namespaces ) {

		if ( !$innerCondition instanceof SingletonCondition ) {
			return '?' . $innerJoinVariable;
		}

		$matchElement = $innerCondition->matchElement;

		if ( $matchElement instanceof ExpElement ) {
			$objectName = TurtleSerializer::getTurtleNameForExpElement( $matchElement );
		} else {
			$objectName = $matchElement;
		}

		if ( $matchElement instanceof ExpNsResource ) {
			$namespaces[$matchElement->getNamespaceId()] = $matchElement->getNamespace();
		}

		return $objectName;
	}

	private function findMostSuitablePropertyRepresentation( DIProperty $property, DIProperty $nonInverseProperty, &$namespaces ) {

		$redirectByVariable = $this->conditionBuilder->tryToFindRedirectVariableForDataItem(
			$nonInverseProperty->getDiWikiPage()
		);

		// If the property is represented by a redirect then use the variable instead
		if ( $redirectByVariable !== null ) {
			return $redirectByVariable;
		}

		// Use helper properties in encoding values, refer to this helper property:
		if ( $this->exporter->hasHelperExpElement( $property ) ) {
			$propertyExpElement = $this->exporter->getResourceElementForProperty( $nonInverseProperty, true );
		} elseif( !$property->isUserDefined() ) {
			$propertyExpElement = $this->exporter->getSpecialPropertyResource(
				$nonInverseProperty->getKey(),
				SMW_NS_PROPERTY
			);
		} else {
			$propertyExpElement = $this->exporter->getResourceElementForProperty( $nonInverseProperty );
		}

		if ( $propertyExpElement instanceof ExpNsResource ) {
			$namespaces[$propertyExpElement->getNamespaceId()] = $propertyExpElement->getNamespace();
		}

		return TurtleSerializer::getTurtleNameForExpElement( $propertyExpElement );
	}

	private function doExchangeForWhenInversePropertyIsUsed( DIProperty $property, $objectName, $joinVariable ) {

		$subjectName = '?' . $joinVariable;
		$nonInverseProperty = $property;

		// Exchange arguments when property is inverse
		// don't check if this really makes sense
		if ( $property->isInverse() ) {
			$subjectName = $objectName;
			$objectName = '?' . $joinVariable;
			$nonInverseProperty = new DIProperty( $property->getKey(), false );
		}

		return [ $subjectName, $objectName, $nonInverseProperty ];
	}

	private function concatenateToConditionString( $subjectName, $propertyName, $objectName, $innerCondition ) {

		$condition = "$subjectName $propertyName $objectName .\n";

		$innerConditionString = $innerCondition->getCondition() . $innerCondition->getWeakConditionString();

		if ( $innerConditionString === '' ) {
			return $condition;
		}

		if ( $innerCondition instanceof FilterCondition ) {
			return $condition . $innerConditionString;
		}

		return $condition . "{ $innerConditionString}\n";
	}

	/**
	 * @note rdfs:subPropertyOf* where * means a property path of arbitrary length
	 * can be found using the "zero or more" will resolve the complete path
	 *
	 * @see http://www.w3.org/TR/sparql11-query/#propertypath-arbitrary-length
	 */
	private function tryToAddPropertyPathForSaturatedHierarchy( &$condition, DIProperty $property, &$propertyName, $depth ) {

		if ( !$this->conditionBuilder->isSetFlag( SMW_SPARQL_QF_SUBP ) || !$property->isUserDefined() || ( $depth !== null && $depth < 1 ) ) {
			return null;
		}

		if ( $this->conditionBuilder->getHierarchyLookup() == null || !$this->conditionBuilder->getHierarchyLookup()->hasSubproperty( $property ) ) {
			return null;
		}

		$subPropExpElement = $this->exporter->getSpecialPropertyResource( '_SUBP', SMW_NS_PROPERTY );

		// A discret depth other than 0 or 1 is difficult to achieve
		// @see https://stackoverflow.com/questions/18126949/limit-the-sparql-query-result-to-first-level-in-hierarchy
		// Path operator is defined as:
		// - elt* ZeroOrMorePath
		// - elt? ZeroOrOnePath
		$pathOp = $depth > 1 || $depth === null ? '*' : '?';

		$propertyByVariable = '?' . $this->conditionBuilder->getNextVariable( 'sp' );
		$condition->weakConditions[$propertyName] = "\n". "$propertyByVariable " . $subPropExpElement->getQName() . "$pathOp $propertyName .\n"."";
		$propertyName = $propertyByVariable;
	}

}
