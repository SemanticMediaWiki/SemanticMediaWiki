<?php

namespace SMW\SQLStore\Lookup;

use SMW\Store;
use SMW\QueryEngine;
use SMWQuery as Query;
use SMW\Query\QueryResult;
use SMW\ApplicationFactory;
use SMW\Query\Language\ValueDescription;
use SMW\SQLStore\QueryDependency\QueryDependencyLinksStore;
use SMW\DIWikiPage;
use RuntimeException;

/**
 * `#show` will only make a request to one particular entity therefore instead of
 * generating a generalized query, identify the entity and create a corresponding
 * `QueryResult` hereby by behave as any other `QueryEngine` implementation but
 * without the query footprint.
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SingleEntityQueryLookup implements QueryEngine {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.1
	 *
	 * @param Query $query
	 *
	 * @return QueryResult
	 */
	public function getQueryResult( Query $query ) {

		$description = $query->getDescription();
		$results = [];
		$furtherResults = false;

		// #4349 We only expect a `ValueDescription` instance while other uses such
		// as `{{ #show: [[someX]][[SomeX]] ...}}` that would produce a non
		// `ValueDescription` description aren't supported!
		if ( !$description instanceof ValueDescription ) {
			$results = [];
			$furtherResults = false;
		} else if ( $query->getLimit() == 0 ) {
			$results = [];
			$furtherResults = true;
		} else {
			$dataItem = $description->getDataItem();

			// #4370
			$dataItem = $this->store->getRedirectTarget( $dataItem );

			// Instead of relying on Title::exists, find an associated revision
			// ID to see whether it is a known page in MW or not
			$associatedRev = $this->store->getObjectIds()->findAssociatedRev(
				$dataItem->asBase()
			);

			// #3588
			// Does the entity exists or not?
			if ( $associatedRev > 0 ) {
				$results = [ $dataItem ];
			}
		}

		$queryResult = new QueryResult(
			$description->getPrintrequests(),
			$query,
			$results,
			$this->store,
			$furtherResults
		);

		\Hooks::run( 'SMW::Store::AfterQueryResultLookupComplete', [ $this->store, &$queryResult ] );

		return $queryResult;
	}

}
