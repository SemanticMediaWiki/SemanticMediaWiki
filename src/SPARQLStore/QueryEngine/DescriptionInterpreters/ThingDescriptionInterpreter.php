<?php

namespace SMW\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\Query\Language\Description;
use SMW\Query\Language\ThingDescription;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreter;
use SMWExporter as Exporter;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ThingDescriptionInterpreter implements DescriptionInterpreter {

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
		return $description instanceof ThingDescription;
	}

	/**
	 * @since 2.2
	 *
	 * {@inheritDoc}
	 */
	public function interpretDescription( Description $description ) {
		return $this->conditionBuilder->newTrueCondition(
			$this->conditionBuilder->getJoinVariable(),
			$this->conditionBuilder->getOrderByProperty()
		);
	}

}
