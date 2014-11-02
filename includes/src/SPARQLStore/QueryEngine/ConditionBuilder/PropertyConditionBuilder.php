<?php

namespace SMW\SPARQLStore\QueryEngine\ConditionBuilder;

use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;

use SMW\Query\Language\Description;
use SMW\Query\Language\SomeProperty;

use SMW\DIProperty;

use SMWDataItem as DataItem;
use SMWExpLiteral as ExpLiteral;
use SMWExpNsResource as ExpNsResource;
use SMWExpElement as ExpElement;
use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class PropertyConditionBuilder implements ConditionBuilder {

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
		return $description instanceOf SomeProperty;
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
	 * Recursively create an Condition from SomeProperty
	 *
	 * @param SomeProperty $description
	 * @param string $joinVariable
	 * @param DIProperty|null $orderByProperty
	 *
	 * @return Condition
	 */
	public function buildCondition( Description $description, $joinVariable, $orderByProperty = null ) {

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

		$propertyName = $this->getPropertyNameByUsingTurtleSerializer(
			$property,
			$nonInverseProperty,
			$namespaces
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
			$result->orderVariables[ $property->getKey() ] = $innerCondition->orderByVariable;
		}

		$this->compoundConditionBuilder->addOrderByDataForProperty(
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
		if ( array_key_exists( $property->getKey(), $this->compoundConditionBuilder->getSortKeys() ) ) {
			$innerOrderByProperty = $property;
		}

		// Prepare inner condition
		$innerJoinVariable = $this->compoundConditionBuilder->getNextVariable();

		$innerCondition = $this->compoundConditionBuilder->mapDescriptionToCondition(
			$description,
			$innerJoinVariable,
			$innerOrderByProperty
		);

		return array( $innerOrderByProperty, $innerCondition, $innerJoinVariable );
	}

	private function findObjectNameFromInnerCondition( $innerCondition, $innerJoinVariable, &$namespaces ) {

		if ( !$innerCondition instanceof SingletonCondition ) {
			return '?' . $innerJoinVariable;
		}

		$matchElement = $innerCondition->matchElement;

		if ( $matchElement instanceOf ExpElement ) {
			$objectName = TurtleSerializer::getTurtleNameForExpElement( $matchElement );
		} else {
			$objectName = $matchElement;
		}

		if ( $matchElement instanceof ExpNsResource ) {
			$namespaces[ $matchElement->getNamespaceId() ] = $matchElement->getNamespace();
		}

		return $objectName;
	}

	private function getPropertyNameByUsingTurtleSerializer( DIProperty $property, DIProperty $nonInverseProperty, &$namespaces ) {

		// Use helper properties in encoding values, refer to this helper property:
		if ( $this->exporter->hasHelperExpElement( $property ) ) {
			$propertyExpElement = $this->exporter->getResourceElementForProperty( $nonInverseProperty, true );
		} else {
			$propertyExpElement = $this->exporter->getResourceElementForProperty( $nonInverseProperty );
		}

		if ( $propertyExpElement instanceof ExpNsResource ) {
			$namespaces[ $propertyExpElement->getNamespaceId() ] = $propertyExpElement->getNamespace();
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

		return array( $subjectName, $objectName, $nonInverseProperty );
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

}
