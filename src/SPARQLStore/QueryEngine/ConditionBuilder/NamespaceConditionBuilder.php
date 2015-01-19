<?php

namespace SMW\SPARQLStore\QueryEngine\ConditionBuilder;

use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;

use SMW\Query\Language\Description;
use SMW\Query\Language\NamespaceDescription;

use SMWDataItem as DataItem;
use SMWExpLiteral as ExpLiteral;
use SMWExporter as Exporter;
use SMWTurtleSerializer as TurtleSerializer;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class NamespaceConditionBuilder implements ConditionBuilder {

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
		return $description instanceOf NamespaceDescription;
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
	 * Create an Condition from a NamespaceDescription
	 *
	 * @param NamespaceDescription $description
	 * @param string $joinVariable
	 * @param DIProperty|null $orderByProperty
	 *
	 * @return Condition
	 */
	public function buildCondition( Description $description, $joinVariable, $orderByProperty = null ) {

		$nspropExpElement = $this->exporter->getSpecialNsResource( 'swivt', 'wikiNamespace' );
		$nsExpElement = new ExpLiteral( strval( $description->getNamespace() ), 'http://www.w3.org/2001/XMLSchema#integer' );

		$nsName = TurtleSerializer::getTurtleNameForExpElement( $nsExpElement );
		$condition = "{ ?$joinVariable " . $nspropExpElement->getQName() . " $nsName . }\n";

		$result = new WhereCondition( $condition, true, array() );

		$this->compoundConditionBuilder->addOrderByDataForProperty(
			$result,
			$joinVariable,
			$orderByProperty,
			DataItem::TYPE_WIKIPAGE
		);

		return $result;
	}

}
