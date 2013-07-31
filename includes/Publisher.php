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
 * Interface describing a Publisher
 *
 * @ingroup Observer
 */
interface Publisher {

	/**
	 * Attach an Subscriber
	 *
	 * @since  1.9
	 *
	 * @param Subscriber $subscriber
	 */
	public function attach( Subscriber $subscriber );

	/**
	 * Detach an Subscriber
	 *
	 * @since  1.9
	 *
	 * @param Subscriber $subscriber
	 */
	public function detach( Subscriber $subscriber );

	/**
	 * Notify an Subscriber
	 *
	 * @since  1.9
	 */
	public function notify();

}
