<?php

namespace SMW\Schema;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
interface ChainableFilter extends SchemaFilter {

	/**
	 * Identifies the filter by name.
	 *
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getName() : string;

	/**
	 * Inject a filter as a node to build a decision tree by chaining together
	 * different filters so that each "root" returns matches for next node to
	 * continue to restrict the match pool.
	 *
	 * @since 3.2
	 *
	 * @param SchemaFilter $nodeFilter
	 */
	public function setNodeFilter( SchemaFilter $nodeFilter );

}
