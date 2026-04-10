<?php

namespace SMW\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\DataItems\DataItem;
use SMW\Export\Exporter;
use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Serializer\TurtleSerializer;
use SMW\Query\Language\Description;
use SMW\Query\Language\NamespaceDescription;
use SMW\SPARQLStore\QueryEngine\Condition\WhereCondition;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreter;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class NamespaceDescriptionInterpreter implements DescriptionInterpreter {

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
		return $description instanceof NamespaceDescription;
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function interpretDescription( Description $description ): WhereCondition {
		$joinVariable = $this->conditionBuilder->getJoinVariable();
		$orderByProperty = $this->conditionBuilder->getOrderByProperty();

		$nspropExpElement = $this->exporter->newExpNsResourceById( 'swivt', 'wikiNamespace' );
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
