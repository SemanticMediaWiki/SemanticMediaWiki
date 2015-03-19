<?php

namespace SMW\SQLStore\QueryEngine\Compiler;

use SMW\Query\Language\Description;
use SMW\Query\Language\ThingDescription;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\QueryCompiler;
use SMW\SQLStore\QueryEngine\SqlQueryPart;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ThingDescriptionCompiler implements QueryCompiler {

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
	public function canCompileDescription( Description $description ) {
		return $description instanceof ThingDescription;
	}

	/**
	 * @since 2.2
	 *
	 * @param Description $description
	 *
	 * @return SqlQueryPart
	 */
	public function compileDescription( Description $description ) {

		$query = new SqlQueryPart();
		$query->type = SqlQueryPart::Q_NOQUERY;

		return $query;
	}

}
