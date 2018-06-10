<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\Elastic\QueryEngine\QueryBuilder;
use SMW\Query\Language\NamespaceDescription;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class NamespaceDescriptionInterpreter {

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
	 * @param NamespaceDescription $description
	 *
	 * @return Condition
	 */
	public function interpretDescription( NamespaceDescription $description, $isConjunction = false ) {

		$params = [];
		$fieldMapper = $this->queryBuilder->getFieldMapper();

		$namespace = $description->getNamespace();
		$params = $fieldMapper->term( 'subject.namespace', $namespace );

		$condition = $this->queryBuilder->newCondition( $params );
		$condition->type( '' );

		if ( !$isConjunction ) {
			$condition->type( 'filter' );
		}

		$condition->log( [ 'NamespaceDescription' => $description->getQueryString() ] );

		return $condition;
	}

}
