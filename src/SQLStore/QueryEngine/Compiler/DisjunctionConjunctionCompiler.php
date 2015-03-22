<?php

namespace SMW\SQLStore\QueryEngine\Compiler;

use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\QueryCompiler;
use SMW\SQLStore\QueryEngine\SqlQueryPart;

/**
 * @license GNU GPL v2+
 * @since 2.2
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
		return $description instanceof Conjunction || $description instanceof Disjunction;
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
		$query->type = $description instanceof Conjunction ? SqlQueryPart::Q_CONJUNCTION : SqlQueryPart::Q_DISJUNCTION;

		foreach ( $description->getDescriptions() as $subDescription ) {

			$subQueryId = $this->queryBuilder->buildSqlQueryPartFor( $subDescription );

			if ( $subQueryId >= 0 ) {
				$query->components[$subQueryId] = true;
			}
		}

		// All subconditions failed, drop this as well.
		if ( count( $query->components ) == 0 ) {
			$query->type = SqlQueryPart::Q_NOQUERY;
		}

		return $query;
	}

}
