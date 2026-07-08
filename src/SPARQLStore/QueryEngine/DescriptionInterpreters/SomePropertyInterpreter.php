<?php

namespace SMW\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\Export\Exporter;
use SMW\Exporter\Element\ExpElement;
use SMW\Exporter\Element\ExpNsResource;
use SMW\Exporter\Serializer\TurtleSerializer;
use SMW\Query\Language\Description;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\SPARQLStore\QueryEngine\Condition\Condition;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\FilterCondition;
use SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreter;
use UnexpectedValueException;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class SomePropertyInterpreter implements DescriptionInterpreter {

	private Exporter $exporter;

	/**
	 * @since 2.1
	 */
	public function __construct( private readonly ?ConditionBuilder $conditionBuilder = null ) {
		$this->exporter = Exporter::getInstance();
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function canInterpretDescription( Description $description ): bool {
		return $description instanceof SomeProperty;
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function interpretDescription( Description $description ): Condition {
		if ( !$description instanceof SomeProperty ) {
			throw new UnexpectedValueException(
				'Expected SomeProperty description'
			);
		}

		$joinVariable = $this->conditionBuilder->getJoinVariable();
		$orderByProperty = $this->conditionBuilder->getOrderByProperty();

		$property = $description->getProperty();
		$innerDescription = $description->getDescription();

		if ( $this->isNegatedPropertyExistenceDescription( $innerDescription ) ) {
			return $this->createConditionForNegatedPropertyExistence(
				$description,
				$property,
				$joinVariable,
				$orderByProperty
			);
		}

		[ $innerOrderByProperty, $innerCondition, $innerJoinVariable ] = $this->doResolveInnerConditionRecursively(
			$property,
			$innerDescription
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

		[ $subjectName, $objectName, $nonInverseProperty ] = $this->doExchangeForWhenInversePropertyIsUsed(
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

	private function isNegatedPropertyExistenceDescription( Description $description ): bool {
		return $description instanceof ThingDescription && $description->isNegation === true;
	}

	private function createConditionForNegatedPropertyExistence(
		SomeProperty $description,
		Property $property,
		string $joinVariable,
		$orderByProperty
	): WhereCondition {
		$namespaces = [];
		$objectName = '?' . $this->conditionBuilder->getNextVariable();

		[ $subjectName, $objectName, $nonInverseProperty ] = $this->doExchangeForWhenInversePropertyIsUsed(
			$property,
			$objectName,
			$joinVariable
		);

		$propertyName = $this->findMostSuitablePropertyRepresentation(
			$property,
			$nonInverseProperty,
			$namespaces
		);

		$absencePattern = 'FILTER NOT EXISTS {' . "\n" .
			$this->createPropertyAbsencePattern(
				$nonInverseProperty,
				$subjectName,
				$propertyName,
				$objectName,
				$description->getHierarchyDepth(),
				$namespaces
			) .
			'}' . "\n";

		// A bare FILTER NOT EXISTS does not bind its variable. When this
		// condition binds the result variable, bind it to a graph pattern so the
		// condition is self-contained (safe); otherwise it breaks inside a UNION
		// branch (disjunction), or when a weak condition suppresses the base
		// pattern that convertConditionToString() would otherwise inject.
		// When nested inside a property chain the join variable is a fresh
		// intermediate already bound by the enclosing property triple, so no
		// binding is added: it would be redundant, and a shared binding variable
		// would wrongly couple sibling chain conditions.
		if ( $joinVariable === $this->conditionBuilder->getResultVariable() ) {
			$swivtPageResource = $this->exporter->newExpNsResourceById( 'swivt', 'page' );
			$condition = '?' . $joinVariable . ' ' . $swivtPageResource->getQName() . " ?url .\n" . $absencePattern;
			$isSafe = true;
		} else {
			$condition = $absencePattern;
			$isSafe = false;
		}

		$result = new WhereCondition( $condition, $isSafe, $namespaces );

		$this->conditionBuilder->addOrderByDataForProperty(
			$result,
			$joinVariable,
			$orderByProperty,
			DataItem::TYPE_WIKIPAGE
		);

		return $result;
	}

	private function createPropertyAbsencePattern(
		Property $property,
		string $subjectName,
		string $propertyName,
		string $objectName,
		$depth,
		array &$namespaces
	): string {
		if ( !$this->canUsePropertyHierarchyInAbsencePattern( $property, $depth ) ) {
			return "$subjectName $propertyName $objectName .\n";
		}

		[ $propertyByVariable, $subpropertyPathCondition, $subpropertyPathNamespaces ] = $this->createSubpropertyPathPattern(
			$propertyName,
			$depth
		);
		$namespaces = array_merge( $namespaces, $subpropertyPathNamespaces );

		return $subpropertyPathCondition .
			"$subjectName $propertyByVariable $objectName .\n";
	}

	private function createSubpropertyPathPattern( string $propertyName, $depth ): array {
		$subPropExpElement = $this->exporter->getSpecialPropertyResource( '_SUBP', SMW_NS_PROPERTY );

		// A discret depth other than 0 or 1 is difficult to achieve
		// @see https://stackoverflow.com/questions/18126949/limit-the-sparql-query-result-to-first-level-in-hierarchy
		// Path operator is defined as:
		// - elt* ZeroOrMorePath
		// - elt? ZeroOrOnePath
		$pathOp = $depth > 1 || $depth === null ? '*' : '?';

		$propertyByVariable = '?' . $this->conditionBuilder->getNextVariable( 'sp' );

		return [
			$propertyByVariable,
			"$propertyByVariable " . $subPropExpElement->getQName() . "$pathOp $propertyName .\n",
			[ $subPropExpElement->getNamespaceId() => $subPropExpElement->getNamespace() ]
		];
	}

	private function canUsePropertyHierarchyInAbsencePattern( Property $property, $depth ): bool {
		if ( !$this->canUsePropertyHierarchy( $property, $depth ) ) {
			return false;
		}

		if ( $this->conditionBuilder->getHierarchyLookup() == null || !$this->conditionBuilder->getHierarchyLookup()->hasSubproperty( $property ) ) {
			return false;
		}

		return true;
	}

	private function canUsePropertyHierarchy( Property $property, $depth ): bool {
		if ( !$this->conditionBuilder->isSetFlag( SMW_SPARQL_QF_SUBP ) ) {
			return false;
		}

		if ( !$property->isUserDefined() ) {
			return false;
		}

		return $depth === null || $depth >= 1;
	}

	private function doResolveInnerConditionRecursively( Property $property, Description $description ): array {
		$innerOrderByProperty = null;

		$key = $property->getKey();
		// Find out if we should order by the values of this property
		if ( array_key_exists( $key, $this->conditionBuilder->getSortKeys() ) ) {
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

	private function findObjectNameFromInnerCondition( $innerCondition, string $innerJoinVariable, array &$namespaces ): string {
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

	private function findMostSuitablePropertyRepresentation( Property $property, Property $nonInverseProperty, array &$namespaces ) {
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
		} elseif ( !$property->isUserDefined() ) {
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

		if ( $propertyExpElement === null ) {
			throw new UnexpectedValueException(
				'Expected ExpElement for property representation'
			);
		}

		return TurtleSerializer::getTurtleNameForExpElement( $propertyExpElement );
	}

	private function doExchangeForWhenInversePropertyIsUsed( Property $property, string $objectName, string $joinVariable ): array {
		$subjectName = '?' . $joinVariable;
		$nonInverseProperty = $property;

		// Exchange arguments when property is inverse
		// don't check if this really makes sense
		if ( $property->isInverse() ) {
			$subjectName = $objectName;
			$objectName = '?' . $joinVariable;
			$nonInverseProperty = new Property( $property->getKey(), false );
		}

		return [ $subjectName, $objectName, $nonInverseProperty ];
	}

	private function concatenateToConditionString( $subjectName, $propertyName, $objectName, $innerCondition ): string {
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
	private function tryToAddPropertyPathForSaturatedHierarchy( &$condition, Property $property, &$propertyName, $depth ): void {
		if ( !$this->canUsePropertyHierarchy( $property, $depth ) ) {
			return;
		}

		if ( $this->conditionBuilder->getHierarchyLookup() == null || !$this->conditionBuilder->getHierarchyLookup()->hasSubproperty( $property ) ) {
			return;
		}

		[ $propertyByVariable, $subpropertyPathCondition, $subpropertyPathNamespaces ] = $this->createSubpropertyPathPattern(
			$propertyName,
			$depth
		);
		$condition->namespaces = array_merge( $condition->namespaces, $subpropertyPathNamespaces );
		$condition->weakConditions[$propertyName] = "\n" . $subpropertyPathCondition;
		$propertyName = $propertyByVariable;
	}

}
