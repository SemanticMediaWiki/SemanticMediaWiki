<?php

namespace SMW\SPARQLStore\QueryEngine\Interpreter;

use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Description;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreter;
use SMWDataItem as DataItem;
use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ClassDescriptionInterpreter implements DescriptionInterpreter {

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
		return $description instanceof ClassDescription;
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function interpretDescription( Description $description ) {

		$joinVariable = $this->compoundConditionBuilder->getJoinVariable();
		$orderByProperty = $this->compoundConditionBuilder->getOrderByProperty();

		list( $condition, $namespaces ) = $this->mapCategoriesToConditionElements(
			$description->getCategories(),
			$joinVariable
		);

		 // empty disjunction: always false, no results to order
		if ( $condition === '' ) {
			return new FalseCondition();
		}

		$result = new WhereCondition( $condition, true, $namespaces );

		$this->compoundConditionBuilder->addOrderByDataForProperty(
			$result,
			$joinVariable,
			$orderByProperty,
			DataItem::TYPE_WIKIPAGE
		);

		return $result;
	}

	private function mapCategoriesToConditionElements( array $categories, $joinVariable ) {

		$condition = '';
		$namespaces = array();
		$instExpElement = $this->exporter->getSpecialPropertyResource( '_INST' );

		foreach( $categories as $category ) {

			$categoryExpElement = $this->exporter->getResourceElementForWikiPage( $category );
			$categoryExpName = TurtleSerializer::getTurtleNameForExpElement( $categoryExpElement );

			$namespaces[$categoryExpElement->getNamespaceId()] = $categoryExpElement->getNamespace();

			$classHierarchyPattern = $this->tryToAddClassHierarchyPattern( $category, $categoryExpName );
			$newcondition   = $classHierarchyPattern === '' ? "{ " : "{\n" . $classHierarchyPattern;
			$newcondition  .= "?$joinVariable " . $instExpElement->getQName() . " $categoryExpName . }\n";

			if ( $condition === '' ) {
				$condition = $newcondition;
			} else {
				$condition .= "UNION\n$newcondition";
			}
		}

		return array( $condition, $namespaces );
	}

	private function tryToAddClassHierarchyPattern( $category, &$categoryExpName ) {

		if ( !$this->compoundConditionBuilder->canUseQFeature( SMW_SPARQL_QF_SUBC ) ) {
			return '';
		}

		if ( $this->compoundConditionBuilder->getPropertyHierarchyLookup() === null || !$this->compoundConditionBuilder->getPropertyHierarchyLookup()->hasSubcategoryFor( $category ) ) {
			return '';
		}

		$subClassExpElement = $this->exporter->getSpecialPropertyResource( '_SUBC' );

		$classHierarchyByVariable = "?" . $this->compoundConditionBuilder->getNextVariable( 'sc' );
		$condition = "$classHierarchyByVariable " . $subClassExpElement->getQName() . "*" . " $categoryExpName .\n";
		$categoryExpName = "$classHierarchyByVariable";

		return $condition;
	}

}
