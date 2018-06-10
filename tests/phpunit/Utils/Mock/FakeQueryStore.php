<?php

namespace SMW\Tests\Utils\Mock;

use SMW\QueryEngine;
use SMW\Store;
use SMW\StoreAware;
use SMWQuery;
use SMWQueryResult;

/**
 * FIXME One would wish to have a FakeStore but instead SMWSQLStore3 is used in
 * order to avoid to implement all abstract methods specified by SMW\Store
 *
 * @group SMW
 * @group SMWExtension
 * @group medium
 * @group semantic-mediawiki-integration
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since   1.9.2
 *
 * @author mwjames
 */
class FakeQueryStore implements QueryEngine, StoreAware {

	protected $store;

	public function setQueryResult( SMWQueryResult $queryResult ) {
		$this->queryResult = $queryResult;
	}

	public function setStore( Store $store ) {
		$this->store = $store;
	}

	// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.CodeAnalysis.UnusedFunctionParameter
	public function getQueryResult( SMWQuery $query ) { // @codingStandardsIgnoreEnd
		return $this->store->getQueryResult( $query );
	}
}
