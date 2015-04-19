<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\Query\Language\Description;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
interface DescriptionInterpreter {

	/**
	 * @since 2.2
	 *
	 * @param Description $description
	 *
	 * @return boolean
	 */
	public function canInterpretDescription( Description $description );

	/**
	 * @since 2.2
	 *
	 * @param Description $description
	 *
	 * @return QuerySegment
	 */
	public function interpretDescription( Description $description );

}
