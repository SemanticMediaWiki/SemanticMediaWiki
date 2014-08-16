<?php

namespace SMW\SQLStore\QueryEngine\Compiler;

use SMW\SQLStore\QueryEngine\QueryCompiler;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\QueryContainer;

use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\Description;

use SMWSql3SmwIds;

/**
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class NamespaceCompiler implements QueryCompiler {

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * @since  2.1
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @since  2.1
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since  2.1
	 *
	 * @return boolean
	 */
	public function canCompileDescription( Description $description ) {
		return $description instanceOf NamespaceDescription;
	}

	/**
	 * TODO: One instance of the SMW IDs table on s_id always suffices (swm_id is KEY)! Doable in execution ... (PERFORMANCE)
	 *
	 * @since  2.1
	 *
	 * @param Description $description
	 *
	 * @return QueryContainer
	 */
	public function compileDescription( Description $description ) {

		$query = new QueryContainer();
		$query->jointable = SMWSql3SmwIds::tableName;
		$query->joinfield = "$query->alias.smw_id";
		$query->where = "$query->alias.smw_namespace=" . $this->queryBuilder->getStore()->getDatabase()->addQuotes( $description->getNamespace() );

		return $query;
	}

}
