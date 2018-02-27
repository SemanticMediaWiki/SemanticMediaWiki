<?php

namespace SMW\Query;

use SMW\PropertyAnnotator;

/**
 * Specifying the ProfileAnnotator interface
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface ProfileAnnotator extends PropertyAnnotator {

	/**
	 * Returns the query meta data property
	 *
	 * @since 1.9
	 *
	 * @return DIProperty
	 */
	public function getProperty();

	/**
	 * Returns the query meta data container
	 *
	 * @since 1.9
	 *
	 * @return DIContainer
	 */
	public function getContainer();

}
