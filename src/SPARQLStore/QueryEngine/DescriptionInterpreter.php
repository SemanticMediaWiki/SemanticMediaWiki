<?php

namespace SMW\SPARQLStore\QueryEngine;

use SMW\Query\Language\Description;

/**
 * @license GPL-2.0-or-later
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
	 * @return bool
	 */
	public function canInterpretDescription( Description $description );

	/**
	 * @since 2.2
	 *
	 * @param Description $description
	 *
	 * @return Condition
	 */
	public function interpretDescription( Description $description );

}
