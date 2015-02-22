<?php

namespace SMW\SQLStore;

/**
 * A basic interface for fetching a simple list either from a DB or cache
 * instance
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
interface SimpleListLookup {

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function fetchResultList();

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function isCached();

	/**
	 * A unique identifier that describes the specific lookup instance to
	 * distinguish it from other lookup's using the same interface
	 *
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getLookupIdentifier();

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getTimestamp();

}
