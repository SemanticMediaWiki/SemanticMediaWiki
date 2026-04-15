<?php

namespace SMW\Lookup;

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
	 */
	public function fetchList(): array;

	/**
	 * @since 2.2
	 */
	public function isFromCache(): bool;

	/**
	 * A unique identifier that can describe a specific lookup instance to
	 * distinguish it from other lookup's of the same list
	 *
	 * @since 2.2
	 */
	public function getHash(): string;

	/**
	 * @since 2.2
	 *
	 * @return int
	 */
	public function getTimestamp();

}

/**
 * @deprecated since 7.0.0, use \SMW\Lookup\ListLookup
 */
class_alias( ListLookup::class, 'SMW\SQLStore\Lookup\ListLookup' );
