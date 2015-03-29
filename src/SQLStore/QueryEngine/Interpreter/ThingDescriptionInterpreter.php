<?php

namespace SMW\SQLStore\QueryEngine\Interpreter;

use SMW\Query\Language\Description;
use SMW\Query\Language\ThingDescription;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\SqlQueryPart;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ThingDescriptionInterpreter implements DescriptionInterpreter {

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @since 2.2
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function canInterpretDescription( Description $description ) {
		return $description instanceof ThingDescription;
	}

	/**
	 * @since 2.2
	 *
	 * @param Description $description
	 *
	 * @return SqlQueryPart
	 */
	public function interpretDescription( Description $description ) {

		$query = new SqlQueryPart();
		$query->type = SqlQueryPart::Q_NOQUERY;

		return $query;
	}

}
