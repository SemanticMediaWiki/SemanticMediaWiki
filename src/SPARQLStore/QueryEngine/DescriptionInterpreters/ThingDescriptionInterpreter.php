<?php

namespace SMW\SPARQLStore\QueryEngine\DescriptionInterpreters;

use SMW\Query\Language\Description;
use SMW\Query\Language\ThingDescription;
use SMW\SPARQLStore\QueryEngine\ConditionBuilder;
use SMW\SPARQLStore\QueryEngine\DescriptionInterpreter;
use SMWExporter as Exporter;

/**
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ThingDescriptionInterpreter implements DescriptionInterpreter {

	/**
	 * @var Exporter
	 */
	private $exporter;

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
