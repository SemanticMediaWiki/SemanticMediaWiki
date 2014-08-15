<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\Query\Language\Description;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
interface QueryCompiler {

	/**
	 * @since 2.1
	 *
	 * @param Description $description
	 *
	 * @return boolean
	 */
	public function canCompileDescription( Description $description );

	/**
	 * @since 2.1
	 *
	 * @param Description $description
	 *
	 * @return QueryContainer
	 */
	public function compileDescription( Description $description );

	/**
	 * @since 2.1
	 *
	 * @return array
	 */
	public function getErrors();

}
