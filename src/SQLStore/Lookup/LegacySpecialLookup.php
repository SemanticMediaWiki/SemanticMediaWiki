<?php

namespace SMW\SQLStore\Lookup;

use SMW\Store;
use SMW\RequestOptions;
use SMW\ApplicationFactory;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class LegacySpecialLookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var array
	 */
	private $callbacks = [];

	/**
	 * @since 3.0
	 *
	 * @param SQLStore $store
	 * @param array $callbacks
	 */
	public function __construct( Store $store, array $callbacks = [] ) {
		$this->store = $store;
		$this->callbacks = $callbacks;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $serviceName
	 * @param callable $callback
	 */
	public function registerCallback( $serviceName, callable $callback ) {
		$this->callbacks[strtolower($serviceName)] = $callback;
	}

	/**
	 * Return all properties that have been used on pages in the wiki. The
	 * result is an array of arrays, each containing a property data item
	 * and a count. The expected order is alphabetical w.r.t. to property
	 * names.
	 *
	 * If there is an error on creating some property object, then a
	 * suitable SMWDIError object might be returned in its place. Even if
	 * there are errors, the function should always return the number of
	 * results requested (otherwise callers might assume that there are no
	 * further results to ask for).
	 *
	 * @param SMWrequestOptions $requestOptions
	 *
	 * @return array of array( DIProperty|SMWDIError, integer )
	 */
	public function getPropertiesSpecial( $requestOptions = null ) {
		return call_user_func_array( $this->callbacks['special.properties'], [ $requestOptions ] );
	}

	/**
	 * Return all properties that have been declared in the wiki but that
	 * are not used on any page. Stores might restrict here to those
	 * properties that have been given a type if they have no efficient
	 * means of accessing the set of all pages in the property namespace.
	 *
	 * If there is an error on creating some property object, then a
	 * suitable SMWDIError object might be returned in its place. Even if
	 * there are errors, the function should always return the number of
	 * results requested (otherwise callers might assume that there are no
	 * further results to ask for).
	 *
	 * @param SMWrequestOptions $requestOptions
	 *
	 * @return array of DIProperty|SMWDIError
	 */
	public function getUnusedPropertiesSpecial( $requestOptions = null ) {
		return call_user_func_array( $this->callbacks['special.unused.properties'], [ $requestOptions ] );
	}

	/**
	 * Return all properties that are used on some page but that do not
	 * have any page describing them. Stores that have no efficient way of
	 * accessing the set of all existing pages can extend this list to all
	 * properties that are used but do not have a type assigned to them.
	 *
	 * @param SMWrequestOptions $requestOptions
	 *
	 * @return array of array( DIProperty, int )
	 */
	public function getWantedPropertiesSpecial( $requestOptions = null ) {
		return call_user_func_array( $this->callbacks['special.wanted.properties'], [ $requestOptions ] );
	}

	/**
	 * Return statistical information as an associative array with the
	 * following keys:
	 * - 'PROPUSES': Number of property instances (value assignments) in the datatbase
	 * - 'USEDPROPS': Number of properties that are used with at least one value
	 * - 'DECLPROPS': Number of properties that have been declared (i.e. assigned a type)
	 * - 'OWNPAGE': Number of properties with their own page
	 * - 'QUERY': Number of inline queries
	 * - 'QUERYSIZE': Represents collective query size
	 * - 'CONCEPTS': Number of declared concepts
	 * - 'SUBOBJECTS': Number of declared subobjects
	 *
	 * @return array
	 */
	public function getStatistics() {
		return call_user_func_array( $this->callbacks['statistics'], [] )->lookup();
	}

	/**
	 * @since 3.0
	 */
	public function invalidateCache() {

		$deferredCallableUpdate = ApplicationFactory::getInstance()->newDeferredTransactionalCallableUpdate( function() {
			foreach ( $this->callbacks as $key => $callback ) {

				$listLookup = call_user_func_array( $callback, [] );

				if ( $listLookup instanceof CachedListLookup ) {
					$listLookup->invalidateCache();
				}
			}
		} );

		$deferredCallableUpdate->setOrigin( __METHOD__ );
		$deferredCallableUpdate->waitOnTransactionIdle();
		$deferredCallableUpdate->pushUpdate();
	}

}
