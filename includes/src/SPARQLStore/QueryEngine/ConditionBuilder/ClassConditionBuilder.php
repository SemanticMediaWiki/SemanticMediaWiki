<?php

namespace SMW\SPARQLStore\QueryEngine\ConditionBuilder;

use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\Condition\FalseCondition;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;

use SMW\Query\Language\Description;
use SMW\Query\Language\ClassDescription;

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
class ClassConditionBuilder implements ConditionBuilder {

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
		return $description instanceOf ClassDescription;
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
	 * Create an Condition from an ClassDescription.
	 *
	 * @param ClassDescription $description
	 * @param string $joinVariable
	 * @param DIProperty|null $orderByProperty
	 *
	 * @return Condition
	 */
	public function buildCondition( Description $description, $joinVariable, $orderByProperty = null ) {

		list( $condition, $namespaces ) = $this->mapCategoriesToConditionElements( $description->getCategories(), $joinVariable );

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
			$categoryName = TurtleSerializer::getTurtleNameForExpElement( $categoryExpElement );

			$namespaces[$categoryExpElement->getNamespaceId()] = $categoryExpElement->getNamespace();
			$newcondition = "{ ?$joinVariable " . $instExpElement->getQName() . " $categoryName . }\n";

			if ( $condition === '' ) {
				$condition = $newcondition;
			} else {
				$condition .= "UNION\n$newcondition";
			}
		}

		return array( $condition, $namespaces );
	}

}
