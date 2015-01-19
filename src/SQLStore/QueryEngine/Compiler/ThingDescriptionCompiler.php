<?php

namespace SMW\SQLStore\QueryEngine\Compiler;

use SMW\SQLStore\QueryEngine\QueryCompiler;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\QueryContainer;

use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\Description;

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
	 * @since 2.1
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function canCompileDescription( Description $description ) {
		return $description instanceOf ThingDescription;
	}

	/**
	 * @since 2.1
	 *
	 * @param Description $description
	 *
	 * @return QueryContainer
	 */
	public function compileDescription( Description $description ) {

		$query = new QueryContainer();
		$query->type = QueryContainer::Q_NOQUERY;

		return $query;
	}

}
