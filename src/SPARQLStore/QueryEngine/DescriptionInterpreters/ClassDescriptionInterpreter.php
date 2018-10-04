<?php

namespace SMW\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\Description;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
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
		return $description instanceof ClassDescription;
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function interpretDescription( Description $description ) {

		$joinVariable = $this->conditionBuilder->getJoinVariable();
		$orderByProperty = $this->conditionBuilder->getOrderByProperty();

		list( $condition, $namespaces ) = $this->mapCategoriesToConditionElements(
			$description->getCategories(),
			$description->getHierarchyDepth(),
			$joinVariable
		);

		 // empty disjunction: always false, no results to order
		if ( $condition === '' ) {
			return new FalseCondition();
		}

		$result = new WhereCondition( $condition, true, $namespaces );

		$this->conditionBuilder->addOrderByDataForProperty(
			$result,
			$joinVariable,
			$orderByProperty,
			DataItem::TYPE_WIKIPAGE
		);

		return $result;
	}

	private function mapCategoriesToConditionElements( array $categories, $depth, $joinVariable ) {

		$condition = '';
		$namespaces = [];
		$instExpElement = $this->exporter->getSpecialPropertyResource( '_INST' );

		foreach( $categories as $category ) {

			$categoryExpElement = $this->exporter->getResourceElementForWikiPage( $category );
			$categoryExpName = TurtleSerializer::getTurtleNameForExpElement( $categoryExpElement );

			$namespaces[$categoryExpElement->getNamespaceId()] = $categoryExpElement->getNamespace();

			$classHierarchyPattern = $this->tryToAddClassHierarchyPattern(
				$category,
				$depth,
				$categoryExpName
			);

			$newcondition   = $classHierarchyPattern === '' ? "{ " : "{\n" . $classHierarchyPattern;
			$newcondition  .= "?$joinVariable " . $instExpElement->getQName() . " $categoryExpName . }\n";

			if ( $condition === '' ) {
				$condition = $newcondition;
			} else {
				$condition .= "UNION\n$newcondition";
			}
		}

		return [ $condition, $namespaces ];
	}

	private function tryToAddClassHierarchyPattern( $category, $depth, &$categoryExpName ) {

		if ( !$this->conditionBuilder->isSetFlag( SMW_SPARQL_QF_SUBC ) || ( $depth !== null && $depth < 1 ) ) {
			return '';
		}

		if ( $this->conditionBuilder->getHierarchyLookup() === null || !$this->conditionBuilder->getHierarchyLookup()->hasSubcategory( $category ) ) {
			return '';
		}

		$subClassExpElement = $this->exporter->getSpecialPropertyResource( '_SUBC' );

		// @see notes in SomePropertyInterpreter
		$pathOp = $depth > 1 || $depth === null ? '*' : '?';

		$classHierarchyByVariable = "?" . $this->conditionBuilder->getNextVariable( 'sc' );
		$condition = "$classHierarchyByVariable " . $subClassExpElement->getQName() . "$pathOp $categoryExpName .\n";
		$categoryExpName = "$classHierarchyByVariable";

		return $condition;
	}

}
