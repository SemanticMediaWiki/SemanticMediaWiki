<?php

namespace SMW;

/**
 * Interface describing a Id generrator
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Interface describing a Id generrator
 *
 * @ingroup Utility
 */
interface IdGenerator {

	/**
	 * Generates an id
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function generateId();

}
