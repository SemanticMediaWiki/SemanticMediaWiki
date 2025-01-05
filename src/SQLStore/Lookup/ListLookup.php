<?php

namespace SMW\SQLStore\Lookup;

/**
 * A simple interface for fetching a list from either a DB or being used as
 * decorator to cache results
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
interface ListLookup {

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function fetchList();

	/**
	 * @since 2.2
	 *
	 * @return bool
	 */
	public function isFromCache();

	/**
	 * A unique identifier that can describe a specific lookup instance to
	 * distinguish it from other lookup's of the same list
	 *
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getHash();

	/**
	 * @since 2.2
	 *
	 * @return int
	 */
	public function getTimestamp();

}
