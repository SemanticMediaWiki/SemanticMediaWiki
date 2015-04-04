<?php

namespace SMW\SPARQLStore\QueryEngine\Interpreter;

use SMW\Query\Language\Description;
use SMW\Query\Language\NamespaceDescription;
use SMW\SPARQLStore\QueryEngine\CompoundConditionBuilder;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
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
		return $description instanceof NamespaceDescription;
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function interpretDescription( Description $description ) {

		$joinVariable = $this->compoundConditionBuilder->getJoinVariable();
		$orderByProperty = $this->compoundConditionBuilder->getOrderByProperty();

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
