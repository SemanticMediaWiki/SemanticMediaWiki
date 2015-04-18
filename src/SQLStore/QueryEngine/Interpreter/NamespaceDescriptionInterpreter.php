<?php

namespace SMW\SQLStore\QueryEngine\Interpreter;

use SMW\Query\Language\Description;
use SMW\Query\Language\NamespaceDescription;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMWSql3SmwIds;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class NamespaceDescriptionInterpreter implements DescriptionInterpreter {

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
		return $description instanceof NamespaceDescription;
	}

	/**
	 * TODO: One instance of the SMW IDs table on s_id always suffices (swm_id is KEY)! Doable in execution ... (PERFORMANCE)
	 *
	 * @since 2.2
	 *
	 * @param Description $description
	 *
	 * @return QuerySegment
	 */
	public function interpretDescription( Description $description ) {

		$query = new QuerySegment();
		$query->joinTable = SMWSql3SmwIds::tableName;
		$query->joinfield = "$query->alias.smw_id";
		$query->where = "$query->alias.smw_namespace=" . $this->queryBuilder->getStore()->getConnection( 'mw.db' )->addQuotes( $description->getNamespace() );

		return $query;
	}

}
