<?php

namespace SMW\SQLStore\Lookup;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Store;
use SMW\Utils\CircularReferenceGuard;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class RedirectTargetLookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var CircularReferenceGuard
	 */
	private $circularReferenceGuard;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param CircularReferenceGuard $circularReferenceGuard
	 */
	public function __construct( Store $store, CircularReferenceGuard $circularReferenceGuard ) {
		$this->store = $store;
		$this->circularReferenceGuard = $circularReferenceGuard;
	}

	/**
	 * @since 2.5
	 *
	 * @param $dataItem
	 *
	 * @return DataItem
	 */
	public function findRedirectTarget( $dataItem ) {

		if ( !$dataItem instanceof DIWikiPage && !$dataItem instanceof DIProperty ) {
			return $dataItem;
		}

		$hash = $dataItem->getSerialization();

		// Guard against a dataItem that points to itself
		$this->circularReferenceGuard->mark( $hash );

		if ( !$this->circularReferenceGuard->isCircular( $hash ) ) {
			$dataItem = $this->store->getRedirectTarget( $dataItem );
		}

		$this->circularReferenceGuard->unmark( $hash );

		return $dataItem;
	}

}
