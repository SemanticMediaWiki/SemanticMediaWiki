<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\Elastic\QueryEngine\QueryBuilder;
use SMW\Query\Language\Conjunction;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ConjunctionInterpreter {

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @since 3.0
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @since 3.0
	 *
	 * @param Conjunction $description
	 *
	 * @return Condition
	 */
	public function interpretDescription( Conjunction $description, $isConjunction = false ) {

		$params = [];
		$fieldMapper = $this->queryBuilder->getFieldMapper();

		foreach ( $description->getDescriptions() as $desc ) {
			$desc->isPartOfConjunction = true;

			if ( ( $param = $this->queryBuilder->interpretDescription( $desc, true ) ) !== [] ) {
				$params[] = $param;
			}
		}

		if ( $params === [] ) {
			return [];
		}

		$condition = $this->queryBuilder->newCondition( $params );
		$condition->type( 'must' );

		$condition->log( [ 'Conjunction' => $description->getQueryString() ] );

		return $condition;
	}

}
