<?php

namespace SMW\SQLStore\QueryEngine\DescriptionInterpreters;

use SMW\Query\Language\Description;
use SMW\Query\Language\NamespaceDescription;
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class NamespaceDescriptionInterpreter implements DescriptionInterpreter {

	/**
	 * @since 2.2
	 */
	public function __construct(
		private readonly Store $store,
		private readonly ConditionBuilder $conditionBuilder,
	) {
	}

	/**
	 * @since 2.2
	 *
	 * @return bool
	 */
	public function canInterpretDescription( Description $description ): bool {
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
		$connection = $this->store->getConnection( 'mw.db.queryengine' );

		$query = new QuerySegment();
		$query->joinTable = SQLStore::ID_TABLE;
		$query->joinfield = "$query->alias.smw_id";
		$query->where = "$query->alias.smw_namespace=" . $connection->addQuotes( $description->getNamespace() );

		return $query;
	}

}
