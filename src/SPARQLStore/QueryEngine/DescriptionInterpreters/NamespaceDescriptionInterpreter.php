<?php

namespace SMW\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\Query\Language\Description;
use SMW\Query\Language\NamespaceDescription;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreter;
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
class NamespaceDescriptionInterpreter implements DescriptionInterpreter {

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
		return $description instanceof NamespaceDescription;
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function interpretDescription( Description $description ) {

		$joinVariable = $this->conditionBuilder->getJoinVariable();
		$orderByProperty = $this->conditionBuilder->getOrderByProperty();

		$nspropExpElement = $this->exporter->getSpecialNsResource( 'swivt', 'wikiNamespace' );
		$nsExpElement = new ExpLiteral( strval( $description->getNamespace() ), 'http://www.w3.org/2001/XMLSchema#integer' );

		$nsName = TurtleSerializer::getTurtleNameForExpElement( $nsExpElement );
		$condition = "{ ?$joinVariable " . $nspropExpElement->getQName() . " $nsName . }\n";

		$result = new WhereCondition( $condition, true, [] );

		$this->conditionBuilder->addOrderByDataForProperty(
			$result,
			$joinVariable,
			$orderByProperty,
			DataItem::TYPE_WIKIPAGE
		);

		return $result;
	}

}
