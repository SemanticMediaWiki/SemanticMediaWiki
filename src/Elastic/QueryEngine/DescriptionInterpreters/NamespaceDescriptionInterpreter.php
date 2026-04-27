<?php

namespace SMW\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\Elastic\QueryEngine\Condition;
use SMW\Elastic\QueryEngine\ConditionBuilder;
use SMW\Query\Language\NamespaceDescription;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class NamespaceDescriptionInterpreter {

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly ConditionBuilder $conditionBuilder ) {
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
		$fieldMapper = $this->conditionBuilder->getFieldMapper();

		$namespace = $description->getNamespace();
		$params = $fieldMapper->term( 'subject.namespace', $namespace );

		$condition = $this->conditionBuilder->newCondition( $params );
		$condition->type( '' );

		if ( !$isConjunction ) {
			$condition->type( 'filter' );
		}

		$condition->log( [ 'NamespaceDescription' => $description->getQueryString() ] );

		return $condition;
	}

}
