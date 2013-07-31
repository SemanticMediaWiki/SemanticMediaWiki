<?php

namespace SMW;

/**
 * Specifies interfaces to access MediaWiki specific objects
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Interface describing access to a Title object
 *
 * @ingroup Provider
 * @ingroup Utility
 */
interface TitleProvider {

	/**
	 * Returns a Title object
	 *
	 * @since  1.9
	 *
	 * @return Title
	 */
	public function getTitle();

}