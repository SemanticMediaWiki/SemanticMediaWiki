<?php

namespace SMW;

/**
 * Contains interfaces and implementation classes to
 * enable a Observer-Subject (or Publisher-Subcriber) pattern where
 * objects can indepentanly be notfied about a state change and initiate
 * an update of its registered Publisher
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Interface describing a Subsriber
 *
 * @ingroup Observer
 */
interface Subscriber {

	/**
	 * Receive update from a Publisher
	 *
	 * @since  1.9
	 *
	 * @param Publisher $publisher
	 */
	public function update( Publisher $publisher );

}
