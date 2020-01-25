<?php

namespace SMW\Schema;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
interface SchemaFilter {

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getName() : string;

	/**
	 * @since 3.2
	 *
	 * @return bool
	 */
	public function hasMatches() : bool;

	/**
	 * @since 3.2
	 *
	 * @return iterable
	 */
	public function getMatches() : iterable;

	/**
	 * Inject a filter as a node to build a decision tree by chaining together
	 * different filters so that each "root" returns matches for next node to
	 * continue.
	 *
	 * @since 3.2
	 *
	 * @param SchemaFilter $nodeFilter
	 */
	public function setNodeFilter( SchemaFilter $nodeFilter );

	/**
	 * @since 3.2
	 *
	 * @param iterable $comparators
	 */
	public function filter( iterable $comparators );

}
