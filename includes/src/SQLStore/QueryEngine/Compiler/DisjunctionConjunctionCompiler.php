<?php

namespace SMW\SQLStore\QueryEngine\Compiler;

use SMW\SQLStore\QueryEngine\QueryCompiler;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\QueryContainer;

use SMW\Query\Language\Disjunction;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;

/**
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class DisjunctionConjunctionCompiler implements QueryCompiler {

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @var array
	 */
	private $errors = array();

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
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function canCompileDescription( Description $description ) {
		return $description instanceof Conjunction || $description instanceof Disjunction;
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
		$query->type = $description instanceof Conjunction ? QueryContainer::Q_CONJUNCTION : QueryContainer::Q_DISJUNCTION;

		foreach ( $description->getDescriptions() as $subDescription ) {

			$subQueryId = $this->queryBuilder->compileQueries( $subDescription );

			if ( $subQueryId >= 0 ) {
				$query->components[ $subQueryId ] = true;
			}
		}

		// All subconditions failed, drop this as well.
		if ( count( $query->components ) == 0 ) {
			$query->type = QueryContainer::Q_NOQUERY;
		}

		return $query;
	}

}
