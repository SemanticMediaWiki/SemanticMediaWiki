<?php

namespace SMW\Query;

/**
 * "Query contexts" define restrictions during query parsing and
 * are used to preconfigure query (e.g. special pages show no further
 * results link)
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author Markus Krötzsch
 */
interface QueryContext {

	/**
	 * Query for special page
	 */
	const SPECIAL_PAGE = 1000;

	/**
	 * Query for inline use
	 */
	const INLINE_QUERY = 1001;

	/**
	 * Query for concept definition
	 */
	const CONCEPT_DESC = 1002;

	/**
	 * normal instance retrieval
	 */
	const MODE_INSTANCES = 1;

	/**
	 * find result count only
	 */
	const MODE_COUNT = 2;

	/**
	 * prepare query, but show debug data instead of executing it
	 */
	const MODE_DEBUG = 3;

	/**
	 * do nothing with the query
	 */
	const MODE_NONE = 4;

}
